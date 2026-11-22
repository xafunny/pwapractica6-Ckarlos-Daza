<?php
/**
 * views/pedidos/index.php - Pedidos (ADMINISTRADOR y EMPLEADO).
 *   - Sin filtro: tablero de ENCARGOS en curso (Pendiente / Preparando / Listo),
 *     ordenados por fecha de entrega. Los botones los mueven de columna (pedidos.js).
 *   - Con filtro (Hoy, Entregados, Cancelados, Todos): tabla de pedidos.
 * Variables: $pedidos, $filtro, $porEstado (conteo de encargos por estado)
 */
defined('APP') or exit('Acceso directo no permitido');
$scripts = ['pedidos.js'];
$columnas = ['Pendiente', 'Preparando', 'Listo'];
$filtros = ['' => 'Encargos en curso', 'hoy' => 'Ventas de hoy', 'Entregado' => 'Entregados', 'Cancelado' => 'Cancelados', 'todos' => 'Todos'];
?>
<div class="pe-encabezado">
  <div>
    <h1 class="pe-titulo">Pedidos</h1>
    <p class="text-body-secondary mb-0">Ventas de mostrador y encargos con fecha de entrega.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('pedidos/nuevo', ['tipo' => 'Encargo']) ?>" class="btn btn-outline-primary"><i class="bi bi-calendar-plus"></i> Nuevo encargo</a>
    <a href="<?= url('pedidos/nuevo') ?>" class="btn btn-primary"><i class="bi bi-cart-plus"></i> Nueva venta</a>
  </div>
</div>

<ul class="nav nav-pills pe-pills mb-3">
  <?php foreach ($filtros as $k => $txt): ?>
    <li class="nav-item"><a class="nav-link <?= $filtro === $k ? 'active' : '' ?>" href="<?= url('pedidos/index', array_filter(['filtro' => $k])) ?>"><?= $txt ?></a></li>
  <?php endforeach; ?>
</ul>

<?php if ($filtro === ''): ?>
  <div class="row g-3">
    <?php foreach ($columnas as $col): $info = ESTADOS_PEDIDO[$col]; ?>
      <div class="col-md-4">
        <div class="pe-columna" data-columna="<?= $col ?>">
          <div class="pe-columna-titulo">
            <span><i class="bi bi-<?= $info['icono'] ?>"></i> <?= $col ?></span>
            <span class="badge rounded-pill text-bg-<?= $info['color'] ?>" data-contador><?= (int) $porEstado[$col] ?></span>
          </div>
          <div data-lista>
            <?php foreach ($pedidos as $p): if ($p['estado'] !== $col) continue; ?>
              <?php require __DIR__ . '/tarjeta.php'; ?>
            <?php endforeach; ?>
          </div>
          <p class="pe-vacio-col small text-body-secondary text-center py-3 mb-0">Sin encargos</p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="card pe-card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>#</th><th>Fecha</th><th>Tipo</th><th>Cliente</th><th class="text-end">Total</th><th>Estado</th><th class="d-none d-lg-table-cell">Registró</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pedidos as $p): ?>
          <tr>
            <td class="fw-bold">#<?= (int) $p['id'] ?></td>
            <td class="text-nowrap"><?= fecha($p['fecha'], true) ?></td>
            <td><span class="pe-chip <?= $p['tipo'] === 'Encargo' ? 'pe-chip-encargo' : '' ?>"><?= e($p['tipo']) ?></span></td>
            <td><?= e($p['cliente']) ?></td>
            <td class="text-end fw-semibold"><?= dinero($p['total']) ?></td>
            <td><?= badge_estado($p['estado']) ?></td>
            <td class="d-none d-lg-table-cell small"><?= e($p['empleado']) ?: '—' ?></td>
            <td class="text-end text-nowrap">
              <a class="btn btn-sm btn-outline-secondary" href="<?= url('pedidos/ver', ['id' => $p['id']]) ?>" aria-label="Ver pedido"><i class="bi bi-eye"></i></a>
              <?php if (Auth::es(Auth::ADMIN)): ?>
                <form method="post" action="<?= url('pedidos/eliminar') ?>" class="d-inline" data-confirmar="¿Eliminar el pedido #<?= (int) $p['id'] ?>? Esta acción no se puede deshacer.">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" aria-label="Eliminar pedido"><i class="bi bi-trash"></i></button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$pedidos): ?><tr><td colspan="8" class="text-center text-body-secondary py-4">No hay pedidos.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
