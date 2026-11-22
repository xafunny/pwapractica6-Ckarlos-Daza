<?php
/** views/errores/404.php - Página no encontrada. */
defined('APP') or exit('Acceso directo no permitido');
?>
<div class="text-center py-5">
  <div class="pe-titulo" style="font-size:6rem;color:var(--pe-caramelo)">404</div>
  <p class="lead">Esta página no salió del horno… no existe.</p>
  <a href="<?= url('inicio/index') ?>" class="btn btn-primary"><i class="bi bi-house"></i> Volver al inicio</a>
</div>
