/**
 * assets/js/pos.js  -  Punto de venta (pedidos/nuevo)
 * ---------------------------------------------------------------------
 * Arma el ticket en el navegador:
 *   - Clic en un producto: se agrega 1 unidad (sin pasar del stock).
 *   - Botones + / − / quitar en cada línea; el total se recalcula.
 *   - Antes de enviar se generan campos ocultos items[id] = cantidad.
 *   - "Mostrador" cobra en el momento; "Encargo" pide cliente y fecha.
 *   - Filtro por categoría y búsqueda instantánea de productos.
 *   - Alta rápida de cliente con fetch() (clientes/rapido) sin salir.
 * El servidor vuelve a validar precios y stock al guardar.
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  const ticket = new Map(); // id -> { nombre, precio, stock, cantidad }

  const lista = document.getElementById('lineasTicket');
  const vacio = document.getElementById('ticketVacio');
  const ocultos = document.getElementById('itemsOcultos');
  const total = document.getElementById('totalTicket');
  const btnCobrar = document.getElementById('btnCobrar');
  const textoCobrar = document.getElementById('textoCobrar');
  const selectCliente = document.getElementById('selectCliente');
  const fechaEntrega = document.getElementById('fechaEntrega');
  const form = document.getElementById('formTicket');

  const esEncargo = () => document.getElementById('tipoEncargo').checked;

  /* ---------- Agregar productos ---------- */
  document.querySelectorAll('.pe-pos-producto').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.id;
      const linea = ticket.get(id) || { nombre: btn.dataset.nombre, precio: parseFloat(btn.dataset.precio), stock: parseInt(btn.dataset.stock, 10), cantidad: 0 };
      if (linea.cantidad >= linea.stock) {
        PE.toast(`Solo hay ${linea.stock} unidades de ${linea.nombre}.`, 'warning');
        return;
      }
      linea.cantidad++;
      ticket.set(id, linea);
      btn.classList.remove('pe-pulso');
      void btn.offsetWidth; // reinicia la animación
      btn.classList.add('pe-pulso');
      pintar();
    });
  });

  /* ---------- + / − / quitar en el ticket ---------- */
  lista.addEventListener('click', e => {
    const btn = e.target.closest('button[data-accion]');
    if (!btn) return;
    const id = btn.closest('[data-id]').dataset.id;
    const linea = ticket.get(id);
    if (btn.dataset.accion === 'mas') {
      if (linea.cantidad >= linea.stock) return PE.toast(`Solo hay ${linea.stock} unidades.`, 'warning');
      linea.cantidad++;
    }
    if (btn.dataset.accion === 'menos') linea.cantidad--;
    if (btn.dataset.accion === 'quitar' || linea.cantidad <= 0) ticket.delete(id);
    pintar();
  });

  document.getElementById('btnVaciar').addEventListener('click', () => {
    ticket.clear();
    pintar();
  });

  /** Vuelve a dibujar el ticket, el total y los campos ocultos. */
  function pintar() {
    lista.innerHTML = '';
    ocultos.innerHTML = '';
    let suma = 0;
    ticket.forEach((l, id) => {
      const subtotal = l.precio * l.cantidad;
      suma += subtotal;
      const li = document.createElement('li');
      li.className = 'list-group-item px-0';
      li.dataset.id = id;
      li.innerHTML = `
        <div class="d-flex justify-content-between align-items-center gap-2">
          <div class="flex-grow-1"><div class="fw-semibold pe-linea-nombre"></div><small class="text-body-secondary">${PE.dinero(l.precio)} c/u</small></div>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary" data-accion="menos" aria-label="Quitar uno"><i class="bi bi-dash"></i></button>
            <span class="btn btn-light disabled fw-bold">${l.cantidad}</span>
            <button type="button" class="btn btn-outline-secondary" data-accion="mas" aria-label="Agregar uno"><i class="bi bi-plus"></i></button>
          </div>
          <span class="fw-bold text-end" style="min-width:64px">${PE.dinero(subtotal)}</span>
          <button type="button" class="btn btn-sm btn-link text-danger p-0" data-accion="quitar" aria-label="Quitar producto"><i class="bi bi-x-lg"></i></button>
        </div>`;
      li.querySelector('.pe-linea-nombre').textContent = l.nombre; // texto seguro
      lista.append(li);

      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = `items[${id}]`;
      input.value = l.cantidad;
      ocultos.append(input);
    });
    total.textContent = PE.dinero(suma);
    vacio.classList.toggle('d-none', ticket.size > 0);
    btnCobrar.disabled = ticket.size === 0;
    textoCobrar.textContent = esEncargo() ? `Registrar encargo · ${PE.dinero(suma)}` : `Cobrar ${PE.dinero(suma)}`;
  }

  /* ---------- Mostrador / Encargo ---------- */
  function actualizarTipo() {
    const encargo = esEncargo();
    document.getElementById('grupoEntrega').classList.toggle('d-none', !encargo);
    fechaEntrega.required = encargo;
    validarCliente();
    pintar();
  }
  /** Un encargo necesita un cliente: se marca el select como inválido si falta. */
  function validarCliente() {
    selectCliente.setCustomValidity(esEncargo() && !selectCliente.value ? 'Elige un cliente' : '');
  }
  document.querySelectorAll('input[name="tipo"]').forEach(r => r.addEventListener('change', actualizarTipo));
  selectCliente.addEventListener('change', validarCliente);
  actualizarTipo();

  // Evita registrar dos veces si se hace doble clic en "Cobrar".
  form.addEventListener('submit', () => {
    if (form.checkValidity()) setTimeout(() => { btnCobrar.disabled = true; }, 0);
  });

  /* ---------- Filtro por categoría y búsqueda ---------- */
  let categoria = '';
  const buscador = document.getElementById('buscarProducto');
  function filtrar() {
    const q = buscador.value.trim().toLowerCase();
    let visibles = 0;
    document.querySelectorAll('.pe-pos-producto').forEach(p => {
      const ok = (!categoria || p.dataset.categoria === categoria) && p.dataset.buscar.includes(q);
      p.classList.toggle('d-none', !ok);
      if (ok) visibles++;
    });
    document.getElementById('sinResultados').classList.toggle('d-none', visibles > 0);
  }
  buscador.addEventListener('input', filtrar);
  document.querySelectorAll('#filtroCategorias [data-categoria]').forEach(b => {
    b.addEventListener('click', () => {
      categoria = b.dataset.categoria;
      document.querySelectorAll('#filtroCategorias [data-categoria]').forEach(x => {
        x.classList.toggle('btn-primary', x === b);
        x.classList.toggle('btn-outline-primary', x !== b);
      });
      filtrar();
    });
  });

  /* ---------- Alta rápida de cliente ---------- */
  const formCliente = document.getElementById('formClienteRapido');
  formCliente.addEventListener('submit', async e => {
    e.preventDefault();
    if (!formCliente.checkValidity()) {
      formCliente.classList.add('was-validated');
      return;
    }
    const datos = Object.fromEntries(new FormData(formCliente));
    try {
      const r = await PE.api(window.PE_POS.urlCliente, datos);
      selectCliente.add(new Option(`${r.nombre} · ${r.telefono}`, r.id, true, true));
      validarCliente();
      bootstrap.Modal.getInstance(document.getElementById('modalClienteRapido')).hide();
      formCliente.reset();
      formCliente.classList.remove('was-validated');
      PE.toast('Cliente registrado y seleccionado.');
    } catch (err) {
      PE.toast(err.message, 'danger');
    }
  });
});
