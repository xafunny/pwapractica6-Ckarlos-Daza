<?php
/**
 * ---------------------------------------------------------------------
 * controllers/PedidosController.php    (ADMINISTRADOR y EMPLEADO)
 * ---------------------------------------------------------------------
 *   pedidos/index     encargos en curso (tablero) o listado filtrado
 *   pedidos/nuevo     punto de venta: venta de mostrador o encargo
 *   pedidos/guardar   registra el pedido                      [POST]
 *   pedidos/ver       detalle de un pedido
 *   pedidos/estado    avanzar o cancelar un encargo           [POST JSON]
 *   pedidos/eliminar  eliminar un pedido (solo administrador) [POST]
 * ---------------------------------------------------------------------
 */

defined('APP') or exit('Acceso directo no permitido');

class PedidosController extends Controller
{
    public function index(): void
    {
        $this->soloRoles(Auth::ADMIN, Auth::EMPLEADO);
        $filtros = array_merge(['', 'hoy', 'todos'], array_keys(ESTADOS_PEDIDO));
        $pedido = (string) ($_GET['filtro'] ?? '');
        $filtro = in_array($pedido, $filtros, true) ? $pedido : '';
        $this->view('pedidos/index', [
            'titulo'    => 'Pedidos',
            'pedidos'   => Pedido::listar($filtro),
            'filtro'    => $filtro,
            'porEstado' => Pedido::conteoPorEstado(),
        ]);
    }

    public function nuevo(): void
    {
        $this->soloRoles(Auth::ADMIN, Auth::EMPLEADO);
        $this->view('pedidos/nuevo', [
            'titulo'     => 'Nueva venta',
            'productos'  => Producto::listar(true),
            'categorias' => Categoria::all('nombre'),
            'clientes'   => Cliente::paraSelector(),
            'tipo'       => ($_GET['tipo'] ?? '') === 'Encargo' ? 'Encargo' : 'Mostrador',
        ]);
    }

    public function guardar(): void
    {
        $this->soloRoles(Auth::ADMIN, Auth::EMPLEADO);
        $this->soloPost();

        $tipo = post('tipo') === 'Encargo' ? 'Encargo' : 'Mostrador';
        $cliente = post_int('id_cliente') ?: null;
        $notas = nulo(mb_substr(post('notas'), 0, 255));
        $entrega = str_replace('T', ' ', post('fecha_entrega'));

        // Productos del ticket: items[idProducto] = cantidad
        $items = [];
        foreach ((array) ($_POST['items'] ?? []) as $id => $cant) {
            $cant = (int) $cant;
            if ((int) $id > 0 && $cant > 0) $items[(int) $id] = min($cant, 500);
        }

        $err = null;
        if (!$items) $err = 'Agrega al menos un producto.';
        elseif ($cliente && !Cliente::find($cliente)) $err = 'El cliente seleccionado no existe.';
        elseif ($tipo === 'Encargo' && !$cliente) $err = 'Un encargo necesita un cliente (para avisarle cuando esté listo).';
        elseif ($tipo === 'Encargo' && (!strtotime($entrega) || strtotime($entrega) < time())) $err = 'Indica una fecha y hora de entrega futura.';
        if ($err) {
            flash('danger', $err);
            redirect('pedidos/nuevo', ['tipo' => $tipo]);
        }

        $fechaEntrega = $tipo === 'Encargo' ? date('Y-m-d H:i:s', strtotime($entrega)) : null;
        [$id, $error] = Pedido::crear($cliente, Auth::id(), $tipo, $fechaEntrega, $notas, $items);
        if ($error) {
            flash('danger', $error);
            redirect('pedidos/nuevo', ['tipo' => $tipo]);
        }

        flash('success', $tipo === 'Mostrador' ? "Venta #$id registrada." : "Encargo #$id registrado para el " . fecha($fechaEntrega, true) . '.');
        redirect($tipo === 'Mostrador' ? 'pedidos/nuevo' : 'pedidos/ver', $tipo === 'Mostrador' ? [] : ['id' => $id]);
    }

    public function ver(): void
    {
        $this->soloRoles(Auth::ADMIN, Auth::EMPLEADO);
        $p = Pedido::detalle((int) ($_GET['id'] ?? 0));
        if (!$p) {
            flash('danger', 'Pedido no encontrado.');
            redirect('pedidos/index');
        }
        $this->view('pedidos/ver', [
            'titulo' => 'Pedido #' . $p['id'],
            'pedido' => $p,
            'lineas' => Pedido::lineas((int) $p['id']),
        ]);
    }

    public function estado(): void
    {
        $this->soloRoles(Auth::ADMIN, Auth::EMPLEADO);
        $this->soloPost();
        $nuevo = post('estado');
        if (!isset(ESTADOS_PEDIDO[$nuevo])) {
            $this->json(['ok' => false, 'error' => 'Estado no válido.'], 400);
        }
        $error = Pedido::cambiarEstado(post_int('id') ?? 0, $nuevo, Auth::id());
        if ($error) $this->json(['ok' => false, 'error' => $error], 409);

        // Siguiente estado posible (para actualizar el botón en pantalla).
        $i = array_search($nuevo, Pedido::FLUJO, true);
        $siguiente = ($i !== false && $i < count(Pedido::FLUJO) - 1) ? Pedido::FLUJO[$i + 1] : null;
        $this->json(['ok' => true, 'estado' => $nuevo, 'siguiente' => $siguiente]);
    }

    public function eliminar(): void
    {
        $this->soloRoles(Auth::ADMIN);
        $this->soloPost();
        $p = Pedido::find(post_int('id') ?? 0);
        if ($p) {
            // Un encargo activo se cancela primero para devolver el stock.
            if (!in_array($p['estado'], ['Entregado', 'Cancelado'], true)) {
                Pedido::cambiarEstado((int) $p['id'], 'Cancelado', Auth::id());
            }
            Pedido::delete((int) $p['id']);
            flash('success', 'Pedido #' . $p['id'] . ' eliminado.');
        }
        redirect('pedidos/index', ['filtro' => 'todos']);
    }
}
