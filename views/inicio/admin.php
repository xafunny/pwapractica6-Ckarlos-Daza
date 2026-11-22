<?php
/**
 * views/inicio/admin.php - Panel del ADMINISTRADOR.
 * Variables: $ventasN, $ventasTotal, $porEstado, $encargosHoy, $stockBajo,
 *            $resumen (finanzas del mes), $serie (7 días), $masVendidos, $clientes
 */
defined('APP') or exit('Acceso directo no permitido');
$usarGraficas = true;
$scripts = ['panel.js', 'pedidos.js'];
?>
<div class="pe-encabezado">
  <div>
    <h1 class="pe-titulo">Panel de administración</h1>
    <p class="text-body-secondary mb-0"><?= nombre_mes((int) date('n')) ?> <?= date('Y') ?> · resumen de la panadería</p>
  </div>
  <a href="<?= url('pedidos/nuevo') ?>" class="btn btn-primary"><i class="bi bi-cart-plus"></i> Nueva venta</a>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="card pe-card pe-stat h-100"><div class="card-body">
      <i class="bi bi-receipt pe-stat-icono"></i>
      <div class="pe-stat-valor"><?= dinero($ventasTotal) ?></div>
      <div class="pe-stat-etiqueta">Cobrado hoy (<?= $ventasN ?>)</div>
    </div></div>
  </div>
  <div class="col-6 col-xl-3">
    <a href="<?= url('finanzas/index') ?>" class="card pe-card pe-card-link pe-stat h-100 text-decoration-none"><div class="card-body">
      <i class="bi bi-arrow-down-circle pe-stat-icono"></i>
      <div class="pe-stat-valor"><?= dinero($resumen['ingresos']) ?></div>
      <div class="pe-stat-etiqueta">Ingresos del mes</div>
    </div></a>
  </div>
  <div class="col-6 col-xl-3">
    <a href="<?= url('finanzas/index') ?>" class="card pe-card pe-card-link pe-stat h-100 text-decoration-none"><div class="card-body">
      <i class="bi bi-cash-stack pe-stat-icono"></i>
      <div class="pe-stat-valor <?= $resumen['balance'] < 0 ? 'text-danger' : '' ?>"><?= dinero($resumen['balance']) ?></div>
      <div class="pe-stat-etiqueta">Balance del mes</div>
    </div></a>
  </div>
  <div class="col-6 col-xl-3">
    <a href="<?= url('clientes/index') ?>" class="card pe-card pe-card-link pe-stat h-100 text-decoration-none"><div class="card-body">
      <i class="bi bi-people pe-stat-icono"></i>
      <div class="pe-stat-valor"><?= $clientes ?></div>
      <div class="pe-stat-etiqueta">Clientes</div>
    </div></a>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card pe-card h-100">
      <div class="card-header fw-bold"><i class="bi bi-bar-chart"></i> Ingresos y gastos · últimos 7 días</div>
      <div class="card-body"><div class="pe-grafica"><canvas id="graficaFinanzas" aria-label="Gráfica de ingresos y gastos"></canvas></div></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card pe-card h-100">
      <div class="card-header fw-bold"><i class="bi bi-award"></i> Más vendidos</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($masVendidos as $i => $m): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><span class="pe-puesto"><?= $i + 1 ?></span> <?= e($m['nombre']) ?></span>
            <span class="badge text-bg-light"><?= (int) $m['unidades'] ?> u.</span>
          </li>
        <?php endforeach; ?>
        <?php if (!$masVendidos): ?><li class="list-group-item text-body-secondary">Aún no hay ventas.</li><?php endif; ?>
      </ul>
    </div>
  </div>
  <div class="col-lg-8">
    <h2 class="h5 fw-bold mb-3"><i class="bi bi-alarm"></i> Encargos para entregar hoy</h2>
    <div class="row g-3">
      <?php foreach ($encargosHoy as $p): ?>
        <div class="col-md-6"><?php require __DIR__ . '/../pedidos/tarjeta.php'; ?></div>
      <?php endforeach; ?>
      <?php if (!$encargosHoy): ?>
        <div class="col-12"><div class="pe-vacio"><i class="bi bi-calendar-check"></i><p>No hay encargos pendientes para hoy.</p></div></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-4"><?php require __DIR__ . '/stock.php'; ?></div>
</div>

<script>window.PE_SERIE = <?= json_encode($serie) ?>;</script>
