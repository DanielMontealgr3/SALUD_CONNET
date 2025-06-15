document.addEventListener('DOMContentLoaded', function() {
    const formFiltros = document.getElementById('formFiltrosPendientes');
    const tablaBody = document.getElementById('tabla-pendientes-body');
    const paginacionContainer = document.getElementById('paginacion-container');
    const modalDetallesElement = document.getElementById('modalDetallesPendiente');
    const modalDetalles = new bootstrap.Modal(modalDetallesElement);
    const cuerpoModalDetalles = document.getElementById('cuerpoModalDetalles');
    const modalEntregaPlaceholder = document.getElementById('modal-entrega-placeholder');
    let debounceTimer;

    function construirPaginacion(pagina_actual, total_paginas) {
        if (total_paginas <= 1) {
            paginacionContainer.innerHTML = ''; return;
        }
        let paginacionHtml = `<nav><ul class="pagination pagination-sm">`;
        paginacionHtml += `<li class="page-item ${pagina_actual <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${pagina_actual - 1}">«</a></li>`;
        paginacionHtml += `<li class="page-item active"><span class="page-link">${pagina_actual} / ${total_paginas}</span></li>`;
        paginacionHtml += `<li class="page-item ${pagina_actual >= total_paginas ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${pagina_actual + 1}">»</a></li>`;
        paginacionHtml += `</ul></nav>`;
        paginacionContainer.innerHTML = paginacionHtml;
    }

    function cargarPendientes(pagina = 1) {
        const formData = new FormData(formFiltros);
        const params = new URLSearchParams(formData);
        params.append('ajax', '1');
        params.append('pagina', pagina);
        
        fetch(`entregas_pendientes.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                tablaBody.innerHTML = data.html_tabla;
                construirPaginacion(data.pagina_actual, data.total_paginas);
            })
            .catch(error => {
                tablaBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger p-4">Error al cargar los datos.</td></tr>';
            });
    }

    cargarPendientes(1);

    formFiltros.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => { cargarPendientes(1); }, 350);
    });
    
    paginacionContainer.addEventListener('click', function(e) {
        const link = e.target.closest('.page-link');
        if (link && !link.parentElement.classList.contains('disabled')) {
            e.preventDefault();
            cargarPendientes(link.dataset.page);
        }
    });

    tablaBody.addEventListener('click', function(e) {
        const btnVer = e.target.closest('.btn-ver-pendiente');
        const btnEntregar = e.target.closest('.btn-entregar-pendiente');

        if (btnVer) {
            const id = btnVer.dataset.idPendiente;
            cuerpoModalDetalles.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';
            modalDetalles.show();
            fetch(`ajax_detalles_pendiente.php?id=${id}`)
                .then(response => response.json())
                .then(resultado => {
                    if (resultado.success) {
                        const d = resultado.data;
                        const fecha = new Date(d.fecha_generacion).toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' });
                        const html = `<div class="row"><div class="col-md-6"><h5><i class="bi bi-person-circle text-primary me-2"></i>Datos Paciente</h5><dl class="row"><dt class="col-sm-4">Nombre:</dt><dd class="col-sm-8">${d.nom_usu}</dd><dt class="col-sm-4">Documento:</dt><dd class="col-sm-8">${d.doc_usu}</dd><dt class="col-sm-4">Teléfono:</dt><dd class="col-sm-8">${d.tel_usu || 'N/A'}</dd><dt class="col-sm-4">Correo:</dt><dd class="col-sm-8">${d.correo_usu || 'N/A'}</dd><dt class="col-sm-4">Dirección:</dt><dd class="col-sm-8">${d.direccion_usu || 'No definida'}</dd></dl></div><div class="col-md-6 border-start"><h5><i class="bi bi-file-earmark-medical-fill text-danger me-2"></i>Detalles Pendiente</h5><dl class="row"><dt class="col-sm-4">Radicado:</dt><dd class="col-sm-8"><span class="badge bg-warning text-dark">${d.radicado_pendiente}</span></dd><dt class="col-sm-4">Medicamento:</dt><dd class="col-sm-8"><strong>${d.nom_medicamento}</strong></dd><dt class="col-sm-4">Cant. Pendiente:</dt><dd class="col-sm-8"><strong class="text-danger fs-5">${d.cantidad_pendiente}</strong></dd><dt class="col-sm-4">Fecha:</dt><dd class="col-sm-8">${fecha}</dd><dt class="col-sm-4">Generado por:</dt><dd class="col-sm-8">${d.farmaceuta_genera}</dd></dl></div></div>`;
                        cuerpoModalDetalles.innerHTML = html;
                    } else {
                        cuerpoModalDetalles.innerHTML = `<div class="alert alert-danger">${resultado.message}</div>`;
                    }
                });
        }

        if(btnEntregar) {
            e.preventDefault();
            btnEntregar.disabled = true;
            btnEntregar.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            const idHistoria = btnEntregar.dataset.idHistoria;
            const idDetalle = btnEntregar.dataset.idDetalle;
            const idEntregaPendiente = btnEntregar.dataset.idEntregaPendiente;
            
            const formData = new FormData();
            formData.append('accion', 'verificar_stock_pendiente');
            formData.append('id_detalle', idDetalle);

            fetch('ajax_procesar_entrega.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const turnoFicticio = idEntregaPendiente;
                    const url = `modal_entrega.php?id_historia=${idHistoria}&id_turno=${turnoFicticio}&id_detalle_unico=${idDetalle}&id_entrega_pendiente=${idEntregaPendiente}`;
                    
                    fetch(url)
                        .then(response => response.text())
                        .then(html => {
                            modalEntregaPlaceholder.innerHTML = html;
                            const modalEl = document.getElementById('modalRealizarEntrega');
                            const modalInstance = new bootstrap.Modal(modalEl);
                            modalEl.addEventListener('hidden.bs.modal', () => cargarPendientes(1), { once: true });
                            modalInstance.show();
                            if (typeof inicializarLogicaEntrega === 'function') {
                                inicializarLogicaEntrega(modalEl, 'entregar_pendiente');
                            }
                        });
                } else {
                    Swal.fire({ icon: 'error', title: 'No se puede entregar', text: data.message });
                }
            })
            .catch(error => {
                Swal.fire({ icon: 'error', title: 'Error de Conexión', text: 'No se pudo verificar el stock del pendiente.' });
            })
            .finally(() => {
                btnEntregar.disabled = false;
                btnEntregar.innerHTML = '<i class="bi bi-check-circle-fill"></i> Entregar';
            });
        }
    });
});