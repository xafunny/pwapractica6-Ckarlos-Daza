<?php
/**
 * views/pedidos/nuevo.php - PUNTO DE VENTA (Administrador y Empleado).
 *   Izquierda: productos por categoría (clic = agregar al ticket).
 *   Derecha: ticket con cantidades, total, cliente y tipo de pedido.
 * Todo el ticket se arma en el navegador con JavaScript (pos.js); al
 * cobrar se envían los productos como items[id] = cantidad y el servidor
 * vuelve a validar precios y stock.
 * Variables: $productos, $categorias, $clientes, $tipo ('Mostrador' | 'Encargo')
 */
defined('APP') or exit('Acceso directo no permitido');
$scripts = ['pos.js'];
$minEntrega = date('Y-m-d\TH:i', strtotime('+1 hour'));
?>
<div class="row g-4">
  <!-- ================= PRODUCTOS ================= -->
  <div class="col-lg-7 col-xl-8">
    <div class="pe-encabezado mb-3">
      <h1 class="pe-titulo mb-0">Nueva venta</h1>
      <input type="search" class="form-control pe-buscar" id="buscarProducto" placeholder="Buscar producto…" aria-label="Buscar producto">
    </div>
    <div class="d-flex flex-wrap gap-2 mb-3" id="filtroCategorias">
      <button type="button" class="btn btn-sm btn-primary" data-categoria="">Todo</button>
      <?php foreach ($categorias as $c): ?>
        <button type="button" class="btn btn-sm btn-outline-primary" data-categoria="<?= (int) $c['id'] ?>"><i class="bi bi-<?= e($c['icono']) ?>"></i> <?= e($c['nombre']) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="pe-grid-productos" id="gridProductos">
      <?php foreach ($productos as $p): $agotado = (int) $p['stock'] === 0; ?>
        <button type="button" class="pe-pos-producto" <?= $agotado ? 'disabled' : '' ?>
                data-id="<?= (int) $p['id'] ?>" data-nombre="<?= e($p['nombre']) ?>" data-precio="<?= e($p['precio']) ?>"
                data-stock="<?= (int) $p['stock'] ?>" data-categoria="<?= (int) $p['id_categoria'] ?>" data-buscar="<?= e(mb_strtolower($p['nombre'])) ?>">
          <i class="bi bi-<?= e($p['icono']) ?>"></i>
          <span class="pe-pos-nombre"><?= e($p['nombre']) ?></span>
          <span class="pe-pos-precio"><?= dinero($p['precio']) ?></span>
          <small class="<?= $p['stock'] <= Producto::STOCK_BAJO ? 'text-danger' : 'text-body-secondary' ?>"><?= $agotado ? 'Agotado' : (int) $p['stock'] . ' disp.' ?></small>
        </button>
      <?php endforeach; ?>
    </div>
    <p class="text-body-secondary text-center d-none mt-3" id="sinResultados">Ningún producto coincide.</p>
  </div>

  <!-- ================= TICKET ================= -->
  <div class="col-lg-5 col-xl-4">
    <form class="card pe-card pe-ticket needs-validation" method="post" action="<?= url('pedidos/guardar') ?>" id="formTicket" novalidate>
      <?= csrf_field() ?>
      <div class="card-header">
        <div class="btn-group w-100" role="group" aria-label="Tipo de pedido">
          <input type="radio" class="btn-check" name="tipo" id="tipoMostrador" value="Mostrador" <?= $tipo === 'Mostrador' ? 'checked' : '' ?>>
          <label class="btn btn-outline-primary" for="tipoMostrador"><i class="bi bi-shop"></i> Mostrador</label>
          <input type="radio" class="btn-check" name="tipo" id="tipoEncargo" value="Encargo" <?= $tipo === 'Encargo' ? 'checked' : '' ?>>
          <label class="btn btn-outline-primary" for="tipoEncargo"><i class="bi bi-calendar-event"></i> Encargo</label>
        </div>
      </div>
      <div class="card-body">
        <!-- Cliente -->
        <label class="form-label small fw-bold" for="selectCliente">Cliente</label>
        <div class="input-group mb-3">
          <select class="form-select" name="id_cliente" id="selectCliente">
            <option value="">Consumidor final</option>
            <?php foreach ($clientes as $c): ?>
              <option value="<?= (int) $c['id'] ?>"><?= e($c['nombre']) ?> · <?= e($c['telefono']) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalClienteRapido" title="Registrar cliente" aria-label="Registrar cliente"><i class="bi bi-person-plus"></i></button>
          <div class="invalid-feedback">Un encargo necesita un cliente.</div>
        </div>

        <!-- Solo encargos -->
        <div class="mb-3 <?= $tipo === 'Encargo' ? '' : 'd-none' ?>" id="grupoEntrega">
          <label class="form-label small fw-bold" for="fechaEntrega">Fecha y hora de entrega</label>
          <input type="datetime-local" class="form-control" name="fecha_entrega" id="fechaEntrega" min="<?= $minEntrega ?>">
          <div class="invalid-feedback">Indica una fecha futura.</div>
        </div>

        <!-- Líneas del ticket (las arma pos.js) -->
        <ul class="list-group list-group-flush pe-lineas" id="lineasTicket"></ul>
        <p class="text-body-secondary text-center small py-4 mb-0" id="ticketVacio"><i class="bi bi-basket fs-3 d-block"></i>Toca un producto para agregarlo.</p>
        <div id="itemsOcultos"></div>

        <input class="form-control form-control-sm mt-3" name="notas" maxlength="255" placeholder="Notas (opcional): dedicatoria, sin azúcar…" aria-label="Notas">
      </div>
      <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-bold">Total</span>
          <span class="pe-total" id="totalTicket">$0.00</span>
        </div>
        <button class="btn btn-primary w-100 py-2 fw-bold" id="btnCobrar" disabled><i class="bi bi-check2-circle"></i> <span id="textoCobrar">Cobrar</span></button>
        <button type="button" class="btn btn-link btn-sm w-100 text-body-secondary mt-1" id="btnVaciar">Vaciar ticket</button>
      </div>
    </form>
  </div>
</div>

<!-- ============== MODAL ALTA RÁPIDA DE CLIENTE ============== -->
<div class="modal fade" id="modalClienteRapido" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <form class="modal-content" id="formClienteRapido" novalidate>
      <div class="modal-header">
        <h2 class="modal-title h6">Registrar cliente</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label small">Nombre</label><input class="form-control" name="nombre" required minlength="2" maxlength="50"></div>
        <div class="mb-2"><label class="form-label small">Apellido</label><input class="form-control" name="apellido" maxlength="50"></div>
        <div class="mb-2"><label class="form-label small">Teléfono</label><input class="form-control" name="telefono" required pattern="\d{7,15}" inputmode="numeric"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary w-100">Guardar y seleccionar</button>
      </div>
    </form>
  </div>
</div>

<script>window.PE_POS = { urlCliente: <?= json_encode(url('clientes/rapido')) ?> };</script>
