<?php
/**
 * views/clientes/index.php - Clientes de la panadería (ADMINISTRADOR y EMPLEADO).
 * Variables: $clientes (con pedidos, comprado, ultimo_pedido), $buscar
 */
defined('APP') or exit('Acceso directo no permitido');
?>
<div class="pe-encabezado">
  <div>
    <h1 class="pe-titulo">Clientes</h1>
    <p class="text-body-secondary mb-0"><?= count($clientes) ?> cliente(s) registrados.</p>
  </div>
  <div class="d-flex gap-2">
    <form class="d-flex gap-2" method="get">
      <input type="hidden" name="r" value="clientes/index">
      <input type="search" name="q" class="form-control" value="<?= e($buscar) ?>" placeholder="Nombre o teléfono" aria-label="Buscar cliente">
      <button class="btn btn-outline-secondary" aria-label="Buscar"><i class="bi bi-search"></i></button>
    </form>
    <button class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#modalCliente"><i class="bi bi-person-plus"></i> Nuevo</button>
  </div>
</div>

<div class="card pe-card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Cliente</th><th>Teléfono</th><th class="d-none d-md-table-cell">Correo</th><th class="text-center">Pedidos</th><th class="text-end">Comprado</th><th class="d-none d-lg-table-cell">Último pedido</th><th class="text-end">Acciones</th></tr></thead>
      <tbody>
      <?php foreach ($clientes as $c):
          $json = json_encode(array_intersect_key($c, array_flip(['id', 'nombre', 'apellido', 'telefono', 'correo', 'direccion'])));
      ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <span class="pe-avatar"><?= e(iniciales($c['nombre'], $c['apellido'])) ?></span>
              <a href="<?= url('clientes/ver', ['id' => $c['id']]) ?>" class="fw-bold text-decoration-none"><?= e($c['nombre'] . ' ' . $c['apellido']) ?></a>
            </div>
          </td>
          <td><?= e($c['telefono']) ?></td>
          <td class="d-none d-md-table-cell small"><?= e($c['correo']) ?: '—' ?></td>
          <td class="text-center"><?= (int) $c['pedidos'] ?></td>
          <td class="text-end fw-semibold"><?= dinero($c['comprado']) ?></td>
          <td class="d-none d-lg-table-cell small"><?= $c['ultimo_pedido'] ? fecha($c['ultimo_pedido']) : '—' ?></td>
          <td class="text-end text-nowrap">
            <a class="btn btn-sm btn-outline-secondary" href="<?= url('pedidos/nuevo', ['tipo' => 'Encargo']) ?>" title="Nuevo encargo" aria-label="Nuevo encargo"><i class="bi bi-calendar-plus"></i></a>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalCliente" data-editar='<?= e($json) ?>' aria-label="Editar"><i class="bi bi-pencil"></i></button>
            <?php if (Auth::es(Auth::ADMIN)): ?>
              <form method="post" action="<?= url('clientes/eliminar') ?>" class="d-inline" data-confirmar="¿Eliminar a <?= e($c['nombre']) ?>? Sus pedidos quedarán como «Consumidor final».">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$clientes): ?><tr><td colspan="7" class="text-center text-body-secondary py-4">No se encontraron clientes.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ============== MODAL CLIENTE ============== -->
<div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content needs-validation" method="post" action="<?= url('clientes/guardar') ?>" novalidate
          data-titulo-nuevo="Nuevo cliente" data-titulo-editar="Editar cliente">
      <div class="modal-header">
        <h2 class="modal-title h5">Nuevo cliente</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <?= csrf_field() ?>
        <input type="hidden" name="id">
        <div class="row g-3">
          <div class="col-6"><label class="form-label">Nombre</label><input class="form-control" name="nombre" required minlength="2" maxlength="50"><div class="invalid-feedback">Obligatorio.</div></div>
          <div class="col-6"><label class="form-label">Apellido</label><input class="form-control" name="apellido" maxlength="50"></div>
          <div class="col-md-5"><label class="form-label">Teléfono</label><input class="form-control" name="telefono" required pattern="\d{7,15}" inputmode="numeric"><div class="invalid-feedback">Solo números (7 a 15).</div></div>
          <div class="col-md-7"><label class="form-label">Correo <small class="text-body-secondary">(opcional)</small></label><input type="email" class="form-control" name="correo" maxlength="100"><div class="invalid-feedback">Correo no válido.</div></div>
          <div class="col-12"><label class="form-label">Dirección <small class="text-body-secondary">(opcional)</small></label><input class="form-control" name="direccion" maxlength="255"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
      </div>
    </form>
  </div>
</div>
