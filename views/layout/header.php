<?php
/**
 * ---------------------------------------------------------------------
 * views/layout/header.php
 * ---------------------------------------------------------------------
 * Encabezado común: <head>, librerías (Bootstrap 5, iconos, fuentes),
 * barra de navegación según el ROL y mensajes flash.
 * Variables: $titulo (string)
 * ---------------------------------------------------------------------
 */
defined('APP') or exit('Acceso directo no permitido');

$rutaActual = is_string($_GET['r'] ?? null) ? $_GET['r'] : '';
$usuario = Auth::user();

/** Marca como activo el enlace del menú correspondiente a la página actual. */
$activo = function (string $ruta) use ($rutaActual): string {
    if ($ruta === 'pedidos/nuevo') return $rutaActual === 'pedidos/nuevo' ? 'active' : '';
    $modulo = explode('/', $ruta)[0];
    return str_starts_with($rutaActual, $modulo . '/') && $rutaActual !== 'pedidos/nuevo' ? 'active' : '';
};

// Menú: [ruta, icono, texto]. El administrador ve además productos, finanzas y personal.
$menu = [
    ['inicio/index', 'speedometer2', 'Panel'],
    ['pedidos/nuevo', 'cart-plus', 'Nueva venta'],
    ['pedidos/index', 'journal-text', 'Pedidos'],
];
if (Auth::es(Auth::ADMIN)) $menu[] = ['productos/index', 'basket', 'Productos'];
$menu[] = ['clientes/index', 'people', 'Clientes'];
if (Auth::es(Auth::ADMIN)) {
    $menu[] = ['finanzas/index', 'cash-stack', 'Finanzas'];
    $menu[] = ['personal/index', 'person-badge', 'Personal'];
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Panadería La Espiga - ventas, encargos e inventario">
  <!-- Token CSRF que usa assets/js/app.js en las peticiones fetch() -->
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($titulo) ?> · <?= e(APP_NAME) ?></title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Lato:wght@400;700&display=swap">
  <link rel="stylesheet" href="<?= asset('css/styles.css') ?>">
  <link rel="icon" href="<?= asset('img/logo.svg') ?>" type="image/svg+xml">
</head>
<body>

<?php if ($usuario): ?>
<nav class="navbar navbar-expand-xl pe-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand pe-marca" href="<?= url('inicio/index') ?>">
      <img src="<?= asset('img/logo.svg') ?>" alt="" width="34" height="34"> La <span>Espiga</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal"
            aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menú">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="menuPrincipal">
      <ul class="navbar-nav me-auto mb-2 mb-xl-0">
        <?php foreach ($menu as [$ruta, $icono, $texto]): ?>
          <li class="nav-item">
            <a class="nav-link <?= $activo($ruta) ?>" href="<?= url($ruta) ?>"><i class="bi bi-<?= $icono ?>"></i> <?= $texto ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="dropdown">
        <button class="btn btn-link nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
          <span class="pe-avatar"><?= e(iniciales($usuario['nombre'], $usuario['apellido'])) ?></span>
          <span class="text-start lh-sm">
            <span class="d-block fw-bold"><?= e($usuario['nombre']) ?></span>
            <small class="text-body-secondary"><?= e($usuario['rol']) ?></small>
          </span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
          <li><a class="dropdown-item" href="<?= url('perfil/index') ?>"><i class="bi bi-person-gear"></i> Mi perfil</a></li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <form method="post" action="<?= url('auth/salir') ?>">
              <?= csrf_field() ?>
              <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</button>
            </form>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>
<?php endif; ?>

<main class="container py-4 pe-main">
  <?php foreach (flash() as $f): ?>
    <div class="alert alert-<?= e($f['tipo']) ?> alert-dismissible fade show pe-flash" role="alert">
      <?= e($f['mensaje']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
  <?php endforeach; ?>
