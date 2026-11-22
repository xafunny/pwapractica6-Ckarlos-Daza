<?php
/**
 * views/inicio/resumen.php - Tarjetas de encargos por estado (parcial).
 * Variables: $porEstado
 */
defined('APP') or exit('Acceso directo no permitido');
?>
<div class="row g-3 mb-4">
  <?php foreach (['Pendiente' => 'Encargos pendientes', 'Preparando' => 'En preparación', 'Listo' => 'Listos para entregar'] as $e => $etiqueta): $info = ESTADOS_PEDIDO[$e]; ?>
    <div class="col-4">
      <a href="<?= url('pedidos/index') ?>" class="card pe-card pe-card-link pe-stat h-100 text-decoration-none"><div class="card-body">
        <i class="bi bi-<?= $info['icono'] ?> pe-stat-icono"></i>
        <div class="pe-stat-valor"><?= (int) $porEstado[$e] ?></div>
        <div class="pe-stat-etiqueta"><?= $etiqueta ?></div>
      </div></a>
    </div>
  <?php endforeach; ?>
</div>
