/**
 * assets/js/produccion.js  -  Registro de producción (Productos, Administrador)
 * ---------------------------------------------------------------------
 * El botón [data-produccion="id"] de cada producto pide cuántas
 * unidades salieron del horno y las suma al stock con fetch()
 * (productos/produccion). La insignia de stock se actualiza sin recargar.
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-produccion]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const texto = prompt(`¿Cuántas unidades de «${btn.dataset.nombre}» salieron del horno?`, '10');
      if (texto === null) return;
      const unidades = parseInt(texto, 10);
      if (!unidades || unidades < 1) return PE.toast('Escribe un número mayor a 0.', 'warning');

      btn.disabled = true;
      try {
        const r = await PE.api('index.php?r=productos/produccion', { id: btn.dataset.produccion, unidades });
        const badge = btn.closest('tr').querySelector('[data-stock]');
        badge.textContent = r.stock;
        badge.className = 'badge ' + (r.stock <= 5 ? 'text-bg-warning' : 'text-bg-light');
        PE.toast(`Producción registrada: ${r.mensaje}`);
      } catch (err) {
        PE.toast(err.message, 'danger');
      } finally {
        btn.disabled = false;
      }
    });
  });
});
