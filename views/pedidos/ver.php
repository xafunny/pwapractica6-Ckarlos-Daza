<?php
/**
 * views/pedidos/ver.php - Detalle de un pedido (venta o encargo).
 *   - Para encargos: línea de tiempo del estado y botones para avanzar
 *     o cancelar (pedidos.js recarga la página al terminar).
 * Variables: $pedido, $lineas
 */
defined('APP') or exit('Acceso directo no permitido');
$scripts = ['pedidos.js'];
$p = $pedido;
$esEncargo = $p['tipo'] === 'Encargo';
$pos = array_search($p['estado'], Pedido::FLUJO, true);
$siguiente = $pos !== false ? (Pedido::FLUJO[$pos + 1] ?? null) : null;
?>
<a href="<?= url('pedidos/index', $esEncargo ? [] : ['filtro' => 'todos']) ?>" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Volver</a>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card pe-card">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-2">
          <div>
            <h1 class="pe-titulo mb-0"><?= $esEncargo ? 'Encargo' : 'Venta' ?> #<?= (int) $p['id'] ?></h1>
            <p class="text-body-secondary mb-0">Registrado el <?= fecha($p['fecha'], true) ?> por <?= e($p['empleado']) ?: '—' ?></p>
          </div>
          <div><?= badge_estado($p['estado']) ?></div>
        </div>

        <?php if ($esEncargo && $p['estado'] !== 'Cancelado'): ?>
          <ol class="pe-pasos my-4">
            <?php foreach (Pedido::FLUJO as $i => $paso): ?>
              <li class="<?= $i <= $pos ? 'hecho' : '' ?>"><span><i class="bi bi-<?= ESTADOS_PEDIDO[$paso]['icono'] ?>"></i></span><?= $paso ?></li>
            <?php endforeach; ?>
          </ol>
        <?php elseif ($p['estado'] === 'Cancelado'): ?>
          <div class="alert alert-secondary my-3"><i class="bi bi-x-circle"></i> Este pedido fue cancelado y su stock se devolvió al inventario.</div>
        <?php endif; ?>

        <div class="table-responsive mt-3">
          <table class="table align-middle">
            <thead><tr><th>Producto</th><th class="text-center">Cant.</th><th class="text-end">Precio</th><th class="text-end">Subtotal</th></tr></thead>
            <tbody>
              <?php foreach ($lineas as $l): ?>
                <tr>
                  <td><?= e($l['nombre']) ?></td>
                  <td class="text-center"><?= (int) $l['cantidad'] ?></td>
                  <td class="text-end"><?= dinero($l['precio_unitario']) ?></td>
                  <td class="text-end"><?= dinero($l['subtotal']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot><tr><th colspan="3" class="text-end">Total</th><th class="text-end fs-5"><?= dinero($p['total']) ?></th></tr></tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card pe-card mb-3">
      <div class="card-header fw-bold"><i class="bi bi-person"></i> Cliente</div>
      <div class="card-body">
        <p class="mb-1 fw-semibold"><?= e($p['cliente']) ?></p>
        <?php if ($p['telefono']): ?><p class="mb-1"><i class="bi bi-telephone"></i> <?= e($p['telefono']) ?></p><?php endif; ?>
        <?php if ($p['id_cliente']): ?><a href="<?= url('clientes/ver', ['id' => $p['id_cliente']]) ?>" class="small">Ver historial del cliente</a><?php endif; ?>
        <?php if ($esEncargo): ?>
          <hr>
          <p class="mb-1"><i class="bi bi-alarm"></i> Entrega: <strong><?= fecha($p['fecha_entrega'], true) ?></strong></p>
        <?php endif; ?>
        <?php if ($p['notas']): ?><p class="mb-0 fst-italic mt-2">“<?= e($p['notas']) ?>”</p><?php endif; ?>
      </div>
    </div>

    <?php if ($esEncargo && !in_array($p['estado'], ['Entregado', 'Cancelado'], true)): ?>
      <div class="d-flex gap-2" data-pedido="<?= (int) $p['id'] ?>" data-recargar>
        <?php if ($siguiente): ?>
          <button class="btn btn-primary flex-fill" data-estado="<?= $siguiente ?>"><i class="bi bi-arrow-right-circle"></i> Pasar a <?= $siguiente ?></button>
        <?php endif; ?>
        <button class="btn btn-outline-secondary" data-estado="Cancelado"><i class="bi bi-x-lg"></i> Cancelar</button>
      </div>
    <?php endif; ?>
  </div>
</div>
