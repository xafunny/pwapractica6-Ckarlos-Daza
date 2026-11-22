<?php
/**
 * views/productos/index.php - CRUD de productos y categorías (ADMINISTRADOR).
 * Incluye el registro de producción: el botón «+» suma al stock lo recién
 * horneado sin recargar la página (produccion.js).
 * Variables: $productos, $categorias (con conteo), $categoria, $buscar
 */
defined('APP') or exit('Acceso directo no permitido');
$scripts = ['interruptores.js', 'produccion.js'];
?>
<div class="pe-encabezado">
  <div>
    <h1 class="pe-titulo">Productos</h1>
    <p class="text-body-secondary mb-0">Precios, inventario y producción del día.</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCategorias"><i class="bi bi-tags"></i> Categorías</button>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProducto"><i class="bi bi-plus-lg"></i> Nuevo producto</button>
  </div>
</div>

<form class="d-flex flex-wrap gap-2 mb-3" method="get">
  <input type="hidden" name="r" value="productos/index">
  <select name="categoria" class="form-select w-auto" aria-label="Categoría" onchange="this.form.submit()">
    <option value="0">Todas las categorías</option>
    <?php foreach ($categorias as $c): ?>
      <option value="<?= (int) $c['id'] ?>" <?= $categoria === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nombre']) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="search" name="q" class="form-control w-auto" value="<?= e($buscar) ?>" placeholder="Buscar…" aria-label="Buscar">
  <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
</form>

<div class="card pe-card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Producto</th><th class="d-none d-md-table-cell">Categoría</th><th class="text-end">Precio</th><th class="text-center">Stock</th><th>A la venta</th><th class="text-end">Acciones</th></tr></thead>
      <tbody>
      <?php foreach ($productos as $p):
          $json = json_encode(array_intersect_key($p, array_flip(['id', 'id_categoria', 'nombre', 'descripcion', 'precio', 'stock'])));
      ?>
        <tr class="<?= $p['activo'] ? '' : 'opacity-50' ?>">
          <td><span class="fw-bold"><?= e($p['nombre']) ?></span><br><small class="text-body-secondary"><?= e($p['descripcion']) ?></small></td>
          <td class="d-none d-md-table-cell"><i class="bi bi-<?= e($p['icono']) ?>"></i> <?= e($p['categoria']) ?></td>
          <td class="text-end fw-semibold"><?= dinero($p['precio']) ?></td>
          <td class="text-center">
            <span data-stock class="badge <?= (int) $p['stock'] === 0 ? 'text-bg-danger' : ($p['stock'] <= Producto::STOCK_BAJO ? 'text-bg-warning' : 'text-bg-light') ?>"><?= (int) $p['stock'] ?></span>
          </td>
          <td>
            <div class="form-check form-switch m-0">
              <input class="form-check-input" type="checkbox" role="switch" aria-label="Disponible para la venta" data-interruptor="<?= e(url('productos/activo')) ?>" data-id="<?= (int) $p['id'] ?>" <?= $p['activo'] ? 'checked' : '' ?>>
            </div>
          </td>
          <td class="text-end text-nowrap">
            <button class="btn btn-sm btn-outline-success" data-produccion="<?= (int) $p['id'] ?>" data-nombre="<?= e($p['nombre']) ?>" title="Registrar producción" aria-label="Registrar producción"><i class="bi bi-plus-circle"></i></button>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalProducto" data-editar='<?= e($json) ?>' aria-label="Editar"><i class="bi bi-pencil"></i></button>
            <form method="post" action="<?= url('productos/eliminar') ?>" class="d-inline" data-confirmar="¿Eliminar «<?= e($p['nombre']) ?>»?">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
              <button class="btn btn-sm btn-outline-danger" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$productos): ?><tr><td colspan="6" class="text-center text-body-secondary py-4">No se encontraron productos.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ============== MODAL PRODUCTO ============== -->
<div class="modal fade" id="modalProducto" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content needs-validation" method="post" action="<?= url('productos/guardar') ?>" novalidate
          data-titulo-nuevo="Nuevo producto" data-titulo-editar="Editar producto">
      <div class="modal-header">
        <h2 class="modal-title h5">Nuevo producto</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <?= csrf_field() ?>
        <input type="hidden" name="id">
        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label">Nombre</label>
            <input class="form-control" name="nombre" required maxlength="60">
            <div class="invalid-feedback">Obligatorio.</div>
          </div>
          <div class="col-md-5">
            <label class="form-label">Categoría</label>
            <select class="form-select" name="id_categoria" required>
              <?php foreach ($categorias as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['nombre']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Descripción</label>
            <input class="form-control" name="descripcion" maxlength="255">
          </div>
          <div class="col-6">
            <label class="form-label">Precio ($)</label>
            <input type="number" class="form-control" name="precio" required min="0.05" max="1000" step="0.05">
            <div class="invalid-feedback">Mayor a 0.</div>
          </div>
          <div class="col-6">
            <label class="form-label">Stock</label>
            <input type="number" class="form-control" name="stock" required min="0" max="10000" value="0">
            <div class="invalid-feedback">No puede ser negativo.</div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- ============== MODAL CATEGORÍAS ============== -->
<div class="modal fade" id="modalCategorias" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5">Categorías</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <ul class="list-group mb-3">
          <?php foreach ($categorias as $c): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span><i class="bi bi-<?= e($c['icono']) ?>"></i> <?= e($c['nombre']) ?> <small class="text-body-secondary">(<?= (int) $c['productos'] ?>)</small></span>
              <?php if (!(int) $c['productos']): ?>
                <form method="post" action="<?= url('productos/quitarcategoria') ?>" data-confirmar="¿Eliminar la categoría «<?= e($c['nombre']) ?>»?">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                  <button class="btn btn-sm btn-link text-danger p-0" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
                </form>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <form method="post" action="<?= url('productos/categoria') ?>" class="row g-2 needs-validation" novalidate>
          <?= csrf_field() ?>
          <div class="col-7"><input class="form-control" name="nombre" required maxlength="40" placeholder="Nueva categoría" aria-label="Nombre de la categoría"></div>
          <div class="col-5">
            <select class="form-select" name="icono" aria-label="Icono">
              <option value="basket">Pan</option><option value="cake2">Pastel</option><option value="cookie">Dulce</option>
              <option value="cup-hot">Bebida</option><option value="egg">Desayuno</option><option value="star">Especial</option>
            </select>
          </div>
          <div class="col-12"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Agregar categoría</button></div>
        </form>
      </div>
    </div>
  </div>
</div>
