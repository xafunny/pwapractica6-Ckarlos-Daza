<?php
/**
 * views/auth/login.php - Inicio de sesión del personal.
 * Variables: $error (string), $correo (string)
 */
defined('APP') or exit('Acceso directo no permitido');
?>
<div class="pe-auth">
  <div class="pe-auth-portada d-none d-lg-block">
    <img src="<?= asset('img/logo.svg') ?>" alt="" width="80" height="80" class="mb-3">
    <h1 class="pe-titulo display-4">Panadería <span>La Espiga</span></h1>
    <p class="lead text-body-secondary">Ventas de mostrador, encargos, inventario y finanzas de la panadería en un solo lugar.</p>
    <ul class="list-unstyled pe-lista-portada mt-4">
      <li><i class="bi bi-cart-check"></i> Punto de venta rápido</li>
      <li><i class="bi bi-calendar-event"></i> Encargos con fecha de entrega</li>
      <li><i class="bi bi-graph-up"></i> Control de ingresos y gastos</li>
    </ul>
  </div>

  <div class="card pe-card pe-auth-caja">
    <div class="card-body p-4 p-md-5">
      <div class="text-center d-lg-none mb-3"><img src="<?= asset('img/logo.svg') ?>" alt="" width="56" height="56"></div>
      <h2 class="h4 fw-bold mb-1">Ingreso del personal</h2>
      <p class="text-body-secondary small mb-4">Usa el correo y la contraseña que te dio el administrador.</p>

      <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle"></i> <?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= url('auth/login') ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3">
          <label for="correo" class="form-label">Correo electrónico</label>
          <input type="email" class="form-control" id="correo" name="correo" value="<?= e($correo) ?>" required autofocus autocomplete="email">
          <div class="invalid-feedback">Ingresa un correo válido.</div>
        </div>
        <div class="mb-4">
          <label for="contrasena" class="form-label">Contraseña</label>
          <div class="input-group">
            <input type="password" class="form-control" id="contrasena" name="contrasena" required autocomplete="current-password">
            <button class="btn btn-outline-secondary" type="button" data-toggle-pass="contrasena" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
            <div class="invalid-feedback">Ingresa tu contraseña.</div>
          </div>
        </div>
        <button class="btn btn-primary w-100 py-2 fw-bold">Entrar</button>
      </form>
    </div>
  </div>
</div>
