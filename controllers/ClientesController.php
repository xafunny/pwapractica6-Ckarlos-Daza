<?php
/**
 * ---------------------------------------------------------------------
 * controllers/ClientesController.php   (ADMINISTRADOR y EMPLEADO)
 * ---------------------------------------------------------------------
 *   clientes/index     listado con resumen de compras
 *   clientes/ver       ficha del cliente con su historial de pedidos
 *   clientes/guardar   crear / editar cliente                [POST]
 *   clientes/rapido    alta rápida desde el punto de venta   [POST JSON]
 *   clientes/eliminar  eliminar (solo administrador)         [POST]
 * ---------------------------------------------------------------------
 */

defined('APP') or exit('Acceso directo no permitido');

class ClientesController extends Controller
{
    public function index(): void
    {
        $this->soloRoles(Auth::ADMIN, Auth::EMPLEADO);
        $buscar = trim((string) ($_GET['q'] ?? ''));
        $this->view('clientes/index', [
            'titulo'   => 'Clientes',
            'clientes' => Cliente::listar($buscar),
            'buscar'   => $buscar,
        ]);
    }

    public function ver(): void
    {
        $this->soloRoles(Auth::ADMIN, Auth::EMPLEADO);
        $c = Cliente::find((int) ($_GET['id'] ?? 0));
        if (!$c) {
            flash('danger', 'Cliente no encontrado.');
            redirect('clientes/index');
        }
        $this->view('clientes/ver', [
            'titulo'  => $c['nombre'] . ' ' . $c['apellido'],
            'cliente' => $c,
            'pedidos' => Pedido::deCliente((int) $c['id']),
        ]);
    }

    public function guardar(): void
    {
        $this->soloRoles(Auth::ADMIN, Auth::EMPLEADO);
        $this->soloPost();
        $id = post_int('id') ?? 0;
        $d = $this->datos();
        $err = $this->validar($d, $id);
        if ($err) {
            flash('danger', $err);
            redirect('clientes/index');
        }
        if ($id) {
            Cliente::update($id, $d);
            flash('success', 'Cliente actualizado.');
        } else {
            Cliente::create($d);
            flash('success', 'Cliente registrado.');
        }
        redirect('clientes/index');
    }

    /** Alta rápida desde el punto de venta: devuelve el cliente creado en JSON. */
    public function rapido(): void
    {
        $this->soloRoles(Auth::ADMIN, Auth::EMPLEADO);
        $this->soloPost();
        $d = $this->datos();
        $err = $this->validar($d);
        if ($err) $this->json(['ok' => false, 'error' => $err], 422);
        $id = Cliente::create($d);
        $this->json(['ok' => true, 'id' => $id, 'nombre' => trim($d['nombre'] . ' ' . $d['apellido']), 'telefono' => $d['telefono']]);
    }

    public function eliminar(): void
    {
        $this->soloRoles(Auth::ADMIN);
        $this->soloPost();
        $c = Cliente::find(post_int('id') ?? 0);
        if ($c) {
            Cliente::delete((int) $c['id']); // sus pedidos quedan como "Consumidor final"
            flash('success', 'Cliente eliminado.');
        }
        redirect('clientes/index');
    }

    /** Lee los campos del formulario de cliente. */
    private function datos(): array
    {
        return [
            'nombre'    => post('nombre'),
            'apellido'  => post('apellido'),
            'telefono'  => post('telefono'),
            'correo'    => nulo(mb_strtolower(post('correo'))),
            'direccion' => nulo(post('direccion')),
        ];
    }

    /** Valida los datos del cliente; devuelve el mensaje de error o null. */
    private function validar(array $d, int $id = 0): ?string
    {
        if (mb_strlen($d['nombre']) < 2) return 'El nombre es obligatorio.';
        if (!preg_match('/^\d{7,15}$/', $d['telefono'])) return 'El teléfono debe tener solo números (7 a 15 dígitos).';
        if (Cliente::telefonoExiste($d['telefono'], $id)) return 'Ya hay un cliente con ese teléfono.';
        if ($d['correo'] && !filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) return 'Correo no válido.';
        return null;
    }
}
