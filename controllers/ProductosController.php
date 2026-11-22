<?php
/**
 * ---------------------------------------------------------------------
 * controllers/ProductosController.php     (solo ADMINISTRADOR)
 * ---------------------------------------------------------------------
 * CRUD de productos y categorías, y registro de la producción diaria.
 *   productos/index           listado con filtros
 *   productos/guardar         crear / editar producto           [POST]
 *   productos/produccion      sumar unidades horneadas al stock [POST JSON]
 *   productos/activo          mostrar / ocultar en ventas       [POST JSON]
 *   productos/eliminar        eliminar producto                 [POST]
 *   productos/categoria       crear / editar categoría          [POST]
 *   productos/quitarcategoria eliminar categoría vacía          [POST]
 * ---------------------------------------------------------------------
 */

defined('APP') or exit('Acceso directo no permitido');

class ProductosController extends Controller
{
    public function index(): void
    {
        $this->soloRoles(Auth::ADMIN);
        $categoria = (int) ($_GET['categoria'] ?? 0);
        $buscar = trim((string) ($_GET['q'] ?? ''));
        $this->view('productos/index', [
            'titulo'     => 'Productos',
            'productos'  => Producto::listar(false, $categoria, $buscar),
            'categorias' => Categoria::conConteo(),
            'categoria'  => $categoria,
            'buscar'     => $buscar,
        ]);
    }

    public function guardar(): void
    {
        $this->soloRoles(Auth::ADMIN);
        $this->soloPost();
        $id = post_int('id') ?? 0;
        $d = [
            'id_categoria' => post_int('id_categoria') ?? 0,
            'nombre'       => post('nombre'),
            'descripcion'  => post('descripcion'),
            'precio'       => post_decimal('precio'),
            'stock'        => post_int('stock') ?? 0,
        ];

        $err = null;
        if ($d['nombre'] === '' || mb_strlen($d['nombre']) > 60) $err = 'El nombre es obligatorio (máx. 60 caracteres).';
        elseif (!Categoria::find($d['id_categoria'])) $err = 'Selecciona una categoría.';
        elseif ($d['precio'] === null || $d['precio'] <= 0 || $d['precio'] > 1000) $err = 'El precio debe ser mayor a 0.';
        elseif ($d['stock'] < 0 || $d['stock'] > 10000) $err = 'El stock no puede ser negativo.';
        if ($err) {
            flash('danger', $err);
            redirect('productos/index');
        }

        if ($id) {
            Producto::update($id, $d);
            flash('success', 'Producto actualizado.');
        } else {
            Producto::create($d);
            flash('success', 'Producto agregado.');
        }
        redirect('productos/index');
    }

    public function produccion(): void
    {
        $this->soloRoles(Auth::ADMIN);
        $this->soloPost();
        $p = Producto::find(post_int('id') ?? 0);
        $unidades = post_int('unidades') ?? 0;
        if (!$p) $this->json(['ok' => false, 'error' => 'Producto no encontrado.'], 404);
        if ($unidades < 1 || $unidades > 2000) $this->json(['ok' => false, 'error' => 'Indica entre 1 y 2000 unidades.'], 422);
        Producto::agregarStock((int) $p['id'], $unidades);
        $this->json(['ok' => true, 'stock' => (int) $p['stock'] + $unidades, 'mensaje' => "+$unidades {$p['nombre']}"]);
    }

    public function activo(): void
    {
        $this->soloRoles(Auth::ADMIN);
        $this->soloPost();
        $p = Producto::find(post_int('id') ?? 0);
        if (!$p) $this->json(['ok' => false, 'error' => 'Producto no encontrado.'], 404);
        $nuevo = $p['activo'] ? 0 : 1;
        Producto::update((int) $p['id'], ['activo' => $nuevo]);
        $this->json(['ok' => true, 'activo' => $nuevo]);
    }

    public function eliminar(): void
    {
        $this->soloRoles(Auth::ADMIN);
        $this->soloPost();
        $id = post_int('id') ?? 0;
        if (Producto::enPedidos($id)) {
            // Se conserva para no romper el historial de pedidos.
            Producto::update($id, ['activo' => 0]);
            flash('warning', 'El producto tiene pedidos registrados: se ocultó de las ventas en lugar de eliminarse.');
        } else {
            Producto::delete($id);
            flash('success', 'Producto eliminado.');
        }
        redirect('productos/index');
    }

    public function categoria(): void
    {
        $this->soloRoles(Auth::ADMIN);
        $this->soloPost();
        $id = post_int('id') ?? 0;
        $d = ['nombre' => post('nombre'), 'icono' => post('icono') ?: 'basket'];
        if ($d['nombre'] === '' || mb_strlen($d['nombre']) > 40 || !preg_match('/^[a-z0-9-]+$/', $d['icono'])) {
            flash('danger', 'Escribe un nombre de categoría válido.');
            redirect('productos/index');
        }
        try {
            $id ? Categoria::update($id, $d) : Categoria::create($d);
            flash('success', 'Categoría guardada.');
        } catch (PDOException $e) {
            flash('danger', 'Ya existe una categoría con ese nombre.');
        }
        redirect('productos/index');
    }

    public function quitarcategoria(): void
    {
        $this->soloRoles(Auth::ADMIN);
        $this->soloPost();
        $id = post_int('id') ?? 0;
        if (Producto::listar(false, $id)) {
            flash('danger', 'La categoría tiene productos. Muévelos a otra categoría antes de eliminarla.');
        } else {
            Categoria::delete($id);
            flash('success', 'Categoría eliminada.');
        }
        redirect('productos/index');
    }
}
