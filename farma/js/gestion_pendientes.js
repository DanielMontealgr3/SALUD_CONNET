document.addEventListener('DOMContentLoaded', function() {
    const formFiltros = document.getElementById('formFiltrosPendientes');
    const tablaBody = document.getElementById('tabla-pendientes-body');
    const paginacionContainer = document.getElementById('paginacion-container');
    const modalEntregaPlaceholder = document.getElementById('modal-entrega-placeholder');
    const btnGenerarReporte = document.getElementById('btnGenerarReportePendientes');
    const fechaInicioInput = document.getElementById('fecha_inicio');
    const fechaFinInput = document.getElementById('fecha_fin');

    const modalDetalles = new bootstrap.Modal(document.getElementById('modalDetallesPendiente'));
    const cuerpoModalDetalles = document.getElementById('cuerpoModalDetalles');
    const modalConfirmarReporte = new bootstrap.Modal(document.getElementById('modalConfirmarReportePendientes'));
    const confirmarReporteTexto = document.getElementById('confirmarReporteTextoPendientes');
    const btnConfirmarGeneracion = document.getElementById('btnConfirmarGeneracionPendientes');

    let debounceTimer;

    function validarFechas() {
        let esInvalido = false;
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        fechaInicioInput.classList.remove('is-invalid');
        fechaFinInput.classList.remove('is-invalid');

        const fechaInicioVal = fechaInicioInput.value;
        const fechaFinVal = fechaFinInput.value;

        if (fechaInicioVal) {
            const fechaInicioDate = new Date(fechaInicioVal + 'T00:00:00');
            if (fechaInicioDate > hoy) {
                fechaInicioInput.classList.add('is-invalid');
                esInvalido = true;
            }
        }

        if (fechaFinVal) {
            const fechaFinDate = new Date(fechaFinVal + 'T00:00:00');
            if (fechaFinDate > hoy) {
                fechaFinInput.classList.add('is-invalid');
                esInvalido = true;
            }
        }
        
        if (fechaInicioVal && fechaFinVal) {
            const fechaInicioDate = new Date(fechaInicioVal + 'T00:00:00');
            const fechaFinDate = new Date(fechaFinVal + 'T00:00:00');
            if (fechaInicioDate > fechaFinDate) {
                 fechaInicioInput.classList.add('is-invalid');
                 fechaFinInput.classList.add('is-invalid');
                 esInvalido = true;
            }
        }

        const totalRegistrosActual = parseInt(tablaBody.dataset.totalRegistros || '1');
        btnGenerarReporte.disabled = esInvalido || (totalRegistrosActual === 0);
    }

    function construirPaginacion(pagina_actual, total_paginas) {
        if (!paginacionContainer || total_paginas <= 1) {
            if (paginacionContainer) paginacionContainer.innerHTML = '';
            return;
        }
        let paginacionHtml = `<nav aria-label="Paginación de pendientes"><ul class="pagination pagination-sm justify-content-center">`;
        paginacionHtml += `<li class="page-item ${pagina_actual <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${pagina_actual - 1}">«</a></li>`;
        paginacionHtml += `<li class="page-item active" aria-current="page"><span class="page-link">${pagina_actual} de ${total_paginas}</span></li>`;
        paginacionHtml += `<li class="page-item ${pagina_actual >= total_paginas ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${parseInt(pagina_actual) + 1}">»</a></li>`;
        paginacionHtml += `</ul></nav>`;
        paginacionContainer.innerHTML = paginacionHtml;
    }

    function cargarPendientes(pagina = 1) {
        const formData = new FormData(formFiltros);
        const params = new URLSearchParams(formData);
        params.append('ajax', '1');
        params.append('pagina', pagina);

        tablaBody.innerHTML = '<tr><td colspan="6" class="text-center p-4"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>';

        fetch(`entregas_pendientes.php?${params.toString()}`)
            .then(response => {
                if (!response.ok) throw new Error('Error en la respuesta del servidor');
                return response.json();
            })
            .then(data => {
                tablaBody.innerHTML = data.html_tabla;
                tablaBody.dataset.totalRegistros = data.total_registros;
                construirPaginacion(data.pagina_actual, data.total_paginas);
                validarFechas();
            })
            .catch(error => {
                console.error('Error al cargar pendientes:', error);
                tablaBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger p-4">Error al cargar los datos. Revise la consola.</td></tr>';
                btnGenerarReporte.disabled = true;
            });
    }

    formFiltros.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            cargarPendientes(1);
        }, 350);
    });

    fechaInicioInput.addEventListener('input', validarFechas);
    fechaFinInput.addEventListener('input', validarFechas);

    if (paginacionContainer) {
        paginacionContainer.addEventListener('click', function(e) {
            const link = e.target.closest('.page-link');
            if (link && !link.parentElement.classList.contains('disabled')) {
                e.preventDefault();
                cargarPendientes(link.dataset.page);
            }
        });
    }

    btnGenerarReporte.addEventListener('click', function() {
        if (this.disabled) return;

        const formData = new FormData(formFiltros);
        let texto = "<ul class='list-unstyled'>";

        const radicado = formData.get('q_radicado');
        const documento = formData.get('q_documento');
        const fechaInicio = formData.get('fecha_inicio');
        const fechaFin = formData.get('fecha_fin');
        const estadoSelect = formFiltros.querySelector('#estado');
        const estadoTexto = estadoSelect.options[estadoSelect.selectedIndex].text;

        if (radicado) texto += `<li><i class='bi bi-search me-2'></i>Radicado: <strong>${radicado}</strong></li>`;
        if (documento) texto += `<li><i class='bi bi-person-badge me-2'></i>Documento: <strong>${documento}</strong></li>`;
        if (fechaInicio) texto += `<li><i class='bi bi-calendar-date me-2'></i>Desde: <strong>${fechaInicio}</strong></li>`;
        if (fechaFin) texto += `<li><i class='bi bi-calendar-date-fill me-2'></i>Hasta: <strong>${fechaFin}</strong></li>`;
        texto += `<li><i class='bi bi-toggles me-2'></i>Estado: <strong>${estadoTexto}</strong></li>`;

        if (!radicado && !documento && !fechaInicio && !fechaFin) {
            texto += "<li class='mt-2'><em>Se generará con el estado seleccionado y sin otros filtros.</em></li>";
        }
        texto += "</ul>";

        confirmarReporteTexto.innerHTML = texto;
        modalConfirmarReporte.show();
    });

    btnConfirmarGeneracion.addEventListener('click', function() {
        const formData = new FormData(formFiltros);
        const params = new URLSearchParams(formData);
        const urlReporte = `reporte_pendientes.php?${params.toString()}`;
        window.open(urlReporte, '_blank');
        modalConfirmarReporte.hide();
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
                        const fecha = new Date(d.fecha_generacion).toLocaleString('es-CO', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        const html = `<div class="row g-3"><div class="col-md-6"><h5><i class="bi bi-person-circle text-primary me-2"></i>Datos Paciente</h5><dl class="row mb-0"><dt class="col-sm-4">Nombre</dt><dd class="col-sm-8">${d.nom_usu}</dd><dt class="col-sm-4">Documento</dt><dd class="col-sm-8">${d.doc_usu}</dd><dt class="col-sm-4">Teléfono</dt><dd class="col-sm-8">${d.tel_usu || 'N/A'}</dd><dt class="col-sm-4">Correo</dt><dd class="col-sm-8">${d.correo_usu || 'N/A'}</dd></dl></div><div class="col-md-6 border-start"><h5><i class="bi bi-file-earmark-medical-fill text-danger me-2"></i>Detalles Pendiente</h5><dl class="row mb-0"><dt class="col-sm-5">Radicado</dt><dd class="col-sm-7"><span class="badge bg-warning text-dark">${d.radicado_pendiente}</span></dd><dt class="col-sm-5">Medicamento</dt><dd class="col-sm-7"><strong>${d.nom_medicamento}</strong></dd><dt class="col-sm-5">Cant. Pendiente</dt><dd class="col-sm-7"><strong class="text-danger fs-5">${d.cantidad_pendiente}</strong></dd><dt class="col-sm-5">Fecha</dt><dd class="col-sm-7">${fecha}</dd><dt class="col-sm-5">Generado por</dt><dd class="col-sm-7">${d.farmaceuta_genera}</dd></dl></div></div>`;
                        cuerpoModalDetalles.innerHTML = html;
                    } else {
                        cuerpoModalDetalles.innerHTML = `<div class="alert alert-danger">${resultado.message}</div>`;
                    }
                }).catch(() => {
                    cuerpoModalDetalles.innerHTML = `<div class="alert alert-danger">Error de conexión al obtener los detalles.</div>`;
                });
        }

        if (btnEntregar) {
            e.preventDefault();
            const originalHtml = btnEntregar.innerHTML;
            btnEntregar.disabled = true;
            btnEntregar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

            const idHistoria = btnEntregar.dataset.idHistoria;
            const idDetalle = btnEntregar.dataset.idDetalle;
            const idEntregaPendiente = btnEntregar.dataset.idEntregaPendiente;

            const formData = new FormData();
            formData.append('accion', 'verificar_stock_pendiente');
            formData.append('id_detalle', idDetalle);

            fetch('ajax_procesar_entrega.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const url = `modal_entrega.php?id_historia=${idHistoria}&id_turno=${idEntregaPendiente}&id_detalle_unico=${idDetalle}&id_entrega_pendiente=${idEntregaPendiente}`;
                        fetch(url)
                            .then(response => response.text())
                            .then(html => {
                                modalEntregaPlaceholder.innerHTML = html;
                                const modalEl = document.getElementById('modalRealizarEntrega');
                                const modalInstance = new bootstrap.Modal(modalEl);
                                modalEl.addEventListener('hidden.bs.modal', () => cargarPendientes(1), {
                                    once: true
                                });
                                modalInstance.show();
                                if (typeof inicializarLogicaEntrega === 'function') {
                                    inicializarLogicaEntrega(modalEl, 'entregar_pendiente');
                                }
                            });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'No se puede entregar',
                            text: data.message || 'El inventario no es suficiente.'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Conexión',
                        text: 'No se pudo verificar el stock del pendiente.'
                    });
                })
                .finally(() => {
                    btnEntregar.disabled = false;
                    btnEntregar.innerHTML = originalHtml;
                });
        }
    });

    cargarPendientes(1);
    validarFechas();
});