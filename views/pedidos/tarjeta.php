<?php
/**
 * views/pedidos/tarjeta.php - Tarjeta de un encargo en curso (parcial).
 * Se usa en el tablero de pedidos y en el panel. Los botones cambian el
 * estado sin recargar la página (assets/js/pedidos.js).
 * Variable: $p (pedido con cliente, unidades, total, fecha_entrega, estado...)
 */
defined('APP') or exit('Acceso directo no permitido');

$i = array_search($p['estado'], Pedido::FLUJO, true);
$siguiente = Pedido::FLUJO[$i + 1] ?? null;
$atrasado = $p['fecha_entrega'] && strtotime($p['fecha_entrega']) < time();
?>
<div class="card pe-card pe-pedido mb-3 <?= $atrasado ? 'pe-atrasado' : '' ?>" data-pedido="<?= (int) $p['id'] ?>">
  <div class="card-body p-3">
    <div class="d-flex justify-content-between align-items-start">
      <a href="<?= url('pedidos/ver', ['id' => $p['id']]) ?>" class="fw-bold text-decoration-none">Encargo #<?= (int) $p['id'] ?></a>
      <span class="pe-entrega <?= $atrasado ? 'text-danger' : '' ?>" title="Entrega">
        <i class="bi bi-alarm"></i> <?= cuando($p['fecha_entrega']) ?>
      </span>
    </div>
    <div class="fw-semibold mt-1"><?= e($p['cliente']) ?></div>
    <div class="small text-body-secondary">
      <?php if ($p['telefono']): ?><i class="bi bi-telephone"></i> <?= e($p['telefono']) ?> · <?php endif; ?>
      <?= (int) $p['unidades'] ?> u. · <strong class="text-body"><?= dinero($p['total']) ?></strong>
    </div>
    <?php if ($p['notas']): ?><div class="small fst-italic mt-1">“<?= e($p['notas']) ?>”</div><?php endif; ?>
    <div class="d-flex gap-2 mt-3">
      <?php if ($siguiente): ?>
        <button class="btn btn-sm btn-primary flex-fill" data-estado="<?= $siguiente ?>">
          <i class="bi bi-arrow-right-circle"></i> <?= $siguiente ?>
        </button>
      <?php endif; ?>
      <button class="btn btn-sm btn-outline-secondary" data-estado="Cancelado" title="Cancelar encargo" aria-label="Cancelar encargo"><i class="bi bi-x-lg"></i></button>
    </div>
  </div>
</div>
