<?php
/**
 * views/inicio/empleado.php - Panel del EMPLEADO.
 * Variables: $ventasN, $ventasTotal, $porEstado, $encargosHoy, $stockBajo
 */
defined('APP') or exit('Acceso directo no permitido');
$scripts = ['pedidos.js'];
?>
<div class="pe-encabezado">
  <div>
    <h1 class="pe-titulo">Buen día, <?= e(Auth::user()['nombre']) ?></h1>
    <p class="text-body-secondary mb-0">Hoy se cobraron <?= $ventasN ?> pedido(s) por <?= dinero($ventasTotal) ?>.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('pedidos/nuevo', ['tipo' => 'Encargo']) ?>" class="btn btn-outline-primary"><i class="bi bi-calendar-plus"></i> Encargo</a>
    <a href="<?= url('pedidos/nuevo') ?>" class="btn btn-primary"><i class="bi bi-cart-plus"></i> Nueva venta</a>
  </div>
</div>

<?php require __DIR__ . '/resumen.php'; ?>

<div class="row g-4">
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
