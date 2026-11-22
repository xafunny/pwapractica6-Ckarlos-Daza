<?php
/**
 * ---------------------------------------------------------------------
 * models/Cliente.php
 * ---------------------------------------------------------------------
 * Acceso a la tabla `clientes`. Los clientes no inician sesión: los
 * registra el personal para asociarles pedidos y encargos.
 * ---------------------------------------------------------------------
 */

defined('APP') or exit('Acceso directo no permitido');

class Cliente extends Model
{
    protected static string $tabla = 'clientes';

    /**
     * Clientes con resumen de compras: nº de pedidos, total comprado
     * (pedidos entregados) y fecha del último pedido.
     */
    public static function listar(string $buscar = ''): array
    {
        $sql = "SELECT c.*,
                       COUNT(p.id) AS pedidos,
                       COALESCE(SUM(CASE WHEN p.estado = 'Entregado' THEN p.total END), 0) AS comprado,
                       MAX(p.fecha) AS ultimo_pedido
                  FROM clientes c
                  LEFT JOIN pedidos p ON p.id_cliente = c.id";
        $params = [];
        if ($buscar !== '') {
            $sql .= ' WHERE c.nombre LIKE ? OR c.apellido LIKE ? OR c.telefono LIKE ? OR c.correo LIKE ?';
            $params = array_fill(0, 4, "%$buscar%");
        }
        return self::query($sql . ' GROUP BY c.id ORDER BY c.nombre, c.apellido', $params)->fetchAll();
    }

    /** Lista corta (id y nombre) para el selector del punto de venta. */
    public static function paraSelector(): array
    {
        return self::query("SELECT id, CONCAT(nombre, ' ', apellido) AS nombre, telefono FROM clientes ORDER BY nombre")->fetchAll();
    }

    /** ¿Ya hay un cliente con ese teléfono? */
    public static function telefonoExiste(string $telefono, int $exceptoId = 0): bool
    {
        return (bool) self::query('SELECT 1 FROM clientes WHERE telefono = ? AND id <> ?', [$telefono, $exceptoId])->fetch();
    }

    /** Cantidad de clientes registrados. */
    public static function total(): int
    {
        return (int) self::query('SELECT COUNT(*) FROM clientes')->fetchColumn();
    }
}
