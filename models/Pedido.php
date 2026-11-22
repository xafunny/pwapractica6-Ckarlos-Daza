<?php
/**
 * ---------------------------------------------------------------------
 * models/Pedido.php
 * ---------------------------------------------------------------------
 * Acceso a `pedidos` y `detalle_pedido`.
 *
 * Tipos de pedido:
 *  - Mostrador: venta inmediata. Se guarda ya "Entregado" y su ingreso
 *    se registra en `finanzas` en la misma transacción.
 *  - Encargo: se prepara para una fecha de entrega y avanza por
 *    Pendiente -> Preparando -> Listo -> Entregado.
 *
 * Reglas:
 *  - Registrar un pedido descuenta el stock (transacción con FOR UPDATE).
 *  - Cancelar un encargo devuelve el stock.
 *  - Al entregar un encargo se registra el ingreso en `finanzas`.
 * ---------------------------------------------------------------------
 */

defined('APP') or exit('Acceso directo no permitido');

class Pedido extends Model
{
    protected static string $tabla = 'pedidos';

    /** Orden en que avanzan los estados de un encargo. */
    public const FLUJO = ['Pendiente', 'Preparando', 'Listo', 'Entregado'];

    /**
     * Registra un pedido con sus productos ($items = [idProducto => cantidad]).
     * Devuelve [idPedido, null] o [null, mensajeDeError].
     */
    public static function crear(?int $cliente, int $empleado, string $tipo, ?string $fechaEntrega, ?string $notas, array $items): array
    {
        $db = self::db();
        $db->beginTransaction();
        try {
            $total = 0.0;
            $lineas = [];
            foreach ($items as $idProducto => $cantidad) {
                // Bloquea la fila del producto hasta terminar (evita vender dos veces la última unidad).
                $p = self::query('SELECT * FROM productos WHERE id = ? FOR UPDATE', [(int) $idProducto])->fetch();
                if (!$p || !$p['activo']) {
                    $db->rollBack();
                    return [null, 'Uno de los productos ya no está disponible.'];
                }
                if ($p['stock'] < $cantidad) {
                    $db->rollBack();
                    return [null, "Solo quedan {$p['stock']} unidades de «{$p['nombre']}»."];
                }
                $subtotal = round($p['precio'] * $cantidad, 2);
                $total += $subtotal;
                $lineas[] = [(int) $p['id'], $cantidad, $p['precio'], $subtotal];
            }
            $total = round($total, 2);
            $estado = $tipo === 'Mostrador' ? 'Entregado' : 'Pendiente';

            self::query('INSERT INTO pedidos (id_cliente, id_empleado, tipo, fecha_entrega, notas, total, estado) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$cliente, $empleado, $tipo, $fechaEntrega, $notas, $total, $estado]);
            $idPedido = (int) $db->lastInsertId();

            foreach ($lineas as [$idProducto, $cantidad, $precio, $subtotal]) {
                self::query('INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)',
                    [$idPedido, $idProducto, $cantidad, $precio, $subtotal]);
                self::query('UPDATE productos SET stock = stock - ? WHERE id = ?', [$cantidad, $idProducto]);
            }

            if ($estado === 'Entregado') {
                self::registrarIngreso($idPedido, $total, $empleado);
            }
            $db->commit();
            return [$idPedido, null];
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Cambia el estado de un encargo aplicando las reglas (stock y finanzas).
     * Devuelve null si todo salió bien o el mensaje de error.
     */
    public static function cambiarEstado(int $id, string $nuevo, int $empleado): ?string
    {
        $db = self::db();
        $db->beginTransaction();
        try {
            $p = self::query('SELECT * FROM pedidos WHERE id = ? FOR UPDATE', [$id])->fetch();
            if (!$p) {
                $db->rollBack();
                return 'Pedido no encontrado.';
            }
            $actual = $p['estado'];
            $permitido = $nuevo === 'Cancelado'
                ? !in_array($actual, ['Entregado', 'Cancelado'], true)
                : array_search($nuevo, self::FLUJO, true) === array_search($actual, self::FLUJO, true) + 1;
            if (!$permitido) {
                $db->rollBack();
                return "No se puede pasar de «{$actual}» a «{$nuevo}».";
            }

            self::query('UPDATE pedidos SET estado = ? WHERE id = ?', [$nuevo, $id]);
            if ($nuevo === 'Cancelado') {
                // Devolver al inventario lo que se había descontado.
                self::query('UPDATE productos pr JOIN detalle_pedido d ON d.id_producto = pr.id
                                SET pr.stock = pr.stock + d.cantidad WHERE d.id_pedido = ?', [$id]);
            }
            if ($nuevo === 'Entregado') {
                self::registrarIngreso($id, (float) $p['total'], $empleado);
            }
            $db->commit();
            return null;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** Registra en finanzas el ingreso de un pedido entregado. */
    private static function registrarIngreso(int $idPedido, float $monto, int $empleado): void
    {
        self::query("INSERT INTO finanzas (fecha, tipo, concepto, monto, id_pedido, creado_por)
                     VALUES (CURDATE(), 'Ingreso', ?, ?, ?, ?)", ['Pedido #' . $idPedido, $monto, $idPedido, $empleado]);
    }

    /** SELECT común con datos del cliente, empleado y número de productos. */
    private const BASE = "SELECT p.*,
               COALESCE(CONCAT(c.nombre, ' ', c.apellido), 'Consumidor final') AS cliente, c.telefono,
               CONCAT(e.nombre, ' ', e.apellido) AS empleado,
               (SELECT SUM(d.cantidad) FROM detalle_pedido d WHERE d.id_pedido = p.id) AS unidades
          FROM pedidos p
          LEFT JOIN clientes c ON c.id = p.id_cliente
          LEFT JOIN usuarios e ON e.id = p.id_empleado";

    /**
     * Listado según el filtro:
     *   ''      -> encargos en curso, ordenados por fecha de entrega
     *   'hoy'   -> todos los pedidos registrados hoy
     *   'todos' -> los últimos 300
     *   estado  -> pedidos en ese estado
     */
    public static function listar(string $filtro = ''): array
    {
        return match ($filtro) {
            ''      => self::query(self::BASE . " WHERE p.tipo = 'Encargo' AND p.estado IN ('Pendiente','Preparando','Listo') ORDER BY p.fecha_entrega")->fetchAll(),
            'hoy'   => self::query(self::BASE . ' WHERE DATE(p.fecha) = CURDATE() ORDER BY p.fecha DESC')->fetchAll(),
            'todos' => self::query(self::BASE . ' ORDER BY p.fecha DESC LIMIT 300')->fetchAll(),
            default => self::query(self::BASE . ' WHERE p.estado = ? ORDER BY p.fecha DESC LIMIT 300', [$filtro])->fetchAll(),
        };
    }

    /** Encargos en curso que se entregan hoy. */
    public static function encargosDeHoy(): array
    {
        return self::query(self::BASE . " WHERE p.tipo = 'Encargo' AND p.estado IN ('Pendiente','Preparando','Listo')
                                            AND DATE(p.fecha_entrega) = CURDATE() ORDER BY p.fecha_entrega")->fetchAll();
    }

    /** Pedidos de un cliente (más recientes primero). */
    public static function deCliente(int $cliente): array
    {
        return self::query(self::BASE . ' WHERE p.id_cliente = ? ORDER BY p.fecha DESC LIMIT 100', [$cliente])->fetchAll();
    }

    /** Un pedido con sus datos completos. */
    public static function detalle(int $id): ?array
    {
        return self::query(self::BASE . ' WHERE p.id = ?', [$id])->fetch() ?: null;
    }

    /** Productos (líneas) de un pedido. */
    public static function lineas(int $id): array
    {
        return self::query('SELECT d.*, pr.nombre FROM detalle_pedido d JOIN productos pr ON pr.id = d.id_producto
                             WHERE d.id_pedido = ? ORDER BY d.id', [$id])->fetchAll();
    }

    /** Cantidad de encargos por estado: ['Pendiente' => 3, ...]. */
    public static function conteoPorEstado(): array
    {
        $filas = self::query("SELECT estado, COUNT(*) total FROM pedidos WHERE tipo = 'Encargo' GROUP BY estado")->fetchAll();
        return array_column($filas, 'total', 'estado') + array_fill_keys(array_keys(ESTADOS_PEDIDO), 0);
    }

    /** Ventas cobradas hoy (pedidos entregados hoy): [cantidad, total]. */
    public static function ventasHoy(): array
    {
        $f = self::query("SELECT COUNT(*) n, COALESCE(SUM(monto), 0) t FROM finanzas
                           WHERE id_pedido IS NOT NULL AND fecha = CURDATE()")->fetch();
        return [(int) $f['n'], (float) $f['t']];
    }
}
