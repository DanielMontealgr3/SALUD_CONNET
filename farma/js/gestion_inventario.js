document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('inventario-tbody');
    const modalLotesPlaceholder = document.getElementById('modal-lotes-placeholder');
    const modalSecundarioPlaceholder = document.getElementById('modal-secundario-placeholder');
    const modalDetallesElement = document.getElementById('modalDetallesMedicamento');

    let activeLotesModal = null;
    let debounceTimer;

    function renderBarcodes() {
        try { JsBarcode(".barcode").init(); } catch (e) {}
    }

    function searchWithFilters() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const form = document.getElementById('formFiltros');
            if(!form) return;
            const formData = new FormData(form);
            const params = new URLSearchParams(formData).toString();
            fetch(`inventario.php?ajax_search=1&${params}`)
                .then(response => response.text())
                .then(html => {
                    if (tableBody) tableBody.innerHTML = html;
                    renderBarcodes();
                });
        }, 300);
    }
    
    renderBarcodes();

    document.getElementById('formFiltros')?.addEventListener('input', searchWithFilters);
    document.getElementById('formFiltros')?.addEventListener('submit', e => e.preventDefault());

    document.body.addEventListener('click', function(e) {
        const verLotesBtn = e.target.closest('.btn-ver-lotes');
        const detalleLoteBtn = e.target.closest('.btn-ver-detalle-lote');

        if (verLotesBtn) {
            const idMedicamento = verLotesBtn.dataset.idMedicamento;
            fetch(`../gestion_lotes/modal_ver_lotes.php?id=${idMedicamento}`)
                .then(response => response.text())
                .then(html => {
                    modalLotesPlaceholder.innerHTML = html;
                    const modalElement = document.getElementById('modalListaLotes');
                    activeLotesModal = new bootstrap.Modal(modalElement);
                    activeLotesModal.show();
                    modalElement.addEventListener('hidden.bs.modal', () => {
                        modalLotesPlaceholder.innerHTML = '';
                    }, { once: true });
                });
        }

        if (detalleLoteBtn) {
            const idMedicamento = detalleLoteBtn.dataset.idMedicamento;
            const lote = detalleLoteBtn.dataset.lote;
            fetch(`../gestion_lotes/ajax_detalle_lote.php?id=${idMedicamento}&lote=${lote}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        mostrarDetalleLote(result.data);
                    } else {
                        Swal.fire('Error', result.message, 'error');
                    }
                });
        }
    });
    
    if (modalDetallesElement) {
        modalDetallesElement.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const idMedicamento = button.getAttribute('data-id-medicamento');
            const modalBody = modalDetallesElement.querySelector('#contenidoModalDetalles');
            modalBody.innerHTML = `<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>`;
            
            fetch(`detalles_medicamento.php?id=${idMedicamento}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const med = data.medicamento;
                        let clase_badge = 'bg-secondary';
                        if (med.id_est == 13) clase_badge = 'bg-success';
                        if (med.id_est == 14) clase_badge = 'bg-warning text-dark';
                        if (med.id_est == 15) clase_badge = 'bg-danger';
                        let barcodeHTML = med.codigo_barras ? `<svg class="barcode-detail" jsbarcode-value="${med.codigo_barras}"></svg>` : 'No disponible';
                        const contenidoHTML = `<div class="row"><div class="col-md-8"><dl class="row"><dt class="col-sm-4">Nombre</dt><dd class="col-sm-8">${med.nom_medicamento||'N/A'}</dd><dt class="col-sm-4">Tipo</dt><dd class="col-sm-8">${med.nom_tipo_medi||'N/A'}</dd><dt class="col-sm-4">Descripción</dt><dd class="col-sm-8">${med.descripcion||'Sin descripción.'}</dd><dt class="col-sm-4">Cantidad Total</dt><dd class="col-sm-8"><strong>${med.cantidad_actual!==null?med.cantidad_actual:'N/A'}</strong></dd><dt class="col-sm-4">Estado</dt><dd class="col-sm-8"><span class="badge ${clase_badge}">${med.nom_est||'N/A'}</span></dd></dl></div><div class="col-md-4 text-center"><strong>Código Barras</strong><div class="mt-2 p-2 border rounded bg-light" style="min-height: 80px; display: flex; align-items-center; justify-content: center;">${barcodeHTML}</div></div></div>`;
                        modalBody.innerHTML = contenidoHTML;
                        if (med.codigo_barras) { JsBarcode(".barcode-detail").init(); }
                    } else {
                         modalBody.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                });
        });
    }

    function mostrarDetalleLote(data) {
        let titulo = `Detalle del Lote: ${data.lote}`;
        let html = `<dl class="row"><dt class="col-sm-4">Fecha Vencimiento</dt><dd class="col-sm-8">${data.fecha_vencimiento}</dd><dt class="col-sm-4">Stock en Lote</dt><dd class="col-sm-8"><strong>${data.stock_lote}</strong></dd></dl>`;
        let icon = 'info';
        let confirmButtonText = 'Entendido';
        let footer = '';

        if (data.dias_restantes < 0) {
            icon = 'error';
            titulo = `<i class="bi bi-calendar-x-fill text-danger"></i> Lote Vencido`;
            html += `<p class="text-danger fw-bold">Este lote ha expirado y debe ser retirado.</p>`;
            footer = `<button id="swal-retirar-btn" class="btn btn-danger">Retirar Lote</button>`;
        } else if (data.dias_restantes <= 15) {
            icon = 'warning';
            titulo = `<i class="bi bi-hourglass-split text-warning"></i> Lote Próximo a Vencer`;
            html += `<p class="text-warning fw-bold">A este lote le quedan ${data.dias_restantes} días. Se recomienda retirarlo.</p>`;
            footer = `<button id="swal-retirar-btn" class="btn btn-warning">Retirar Lote</button>`;
        } else if (data.dias_restantes <= 30) {
            icon = 'info';
            titulo = `<i class="bi bi-info-circle-fill text-info"></i> Lote Próximo a Vencer`;
            html += `<p>A este lote le quedan ${data.dias_restantes} días.</p>`;
        }

        Swal.fire({
            title: titulo,
            html: html,
            icon: icon,
            showConfirmButton: !footer,
            confirmButtonText: confirmButtonText,
            showCloseButton: true,
            footer: footer
        });

        const retirarBtn = document.getElementById('swal-retirar-btn');
        if(retirarBtn) {
            retirarBtn.addEventListener('click', () => {
                const tipo_retiro = data.dias_restantes < 0 ? 'vencidos' : 'por_vencer';
                if(activeLotesModal) activeLotesModal.hide();
                Swal.close();
                fetch(`../alertas/modal_retirar_inventario.php?tipo=${tipo_retiro}`)
                    .then(response => response.text())
                    .then(html => {
                        modalSecundarioPlaceholder.innerHTML = html;
                        const modalRetiro = new bootstrap.Modal(document.getElementById('modalRetiroInventario'));
                        modalRetiro.show();
                    });
            });
        }
    }
});