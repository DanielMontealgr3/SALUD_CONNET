document.addEventListener('DOMContentLoaded', function() {
    const formFiltros = document.getElementById('formFiltros');
    const tbody = document.getElementById('movimientos-tbody');
    const paginacionContainer = document.getElementById('paginacion-container');
    const btnLimpiar = document.getElementById('btnLimpiar');
    const btnGenerarReporte = document.getElementById('btnGenerarReporte');
    const btnMasFiltros = document.getElementById('btnMasFiltros');
    const badgeFiltrosAvanzados = document.getElementById('badge-filtros-avanzados');

    const modalFiltrosAvanzados = new bootstrap.Modal(document.getElementById('modalFiltrosAvanzados'));
    const modalVerDetalles = new bootstrap.Modal(document.getElementById('modalVerDetalles'));
    const modalConfirmarReporte = new bootstrap.Modal(document.getElementById('modalConfirmarReporte'));
    
    const contenidoModalDetalles = document.getElementById('contenidoModalDetalles');
    const confirmarReporteTexto = document.getElementById('confirmarReporteTexto');
    const btnConfirmarGeneracion = document.getElementById('btnConfirmarGeneracion');
    const btnAplicarFiltrosModal = document.getElementById('btnAplicarFiltrosModal');
    const btnLimpiarFiltrosModal = document.getElementById('btnLimpiarFiltrosModal');

    const filtroFechaInicioInput = document.getElementById('filtro_fecha_inicio');
    const filtroFechaFinInput = document.getElementById('filtro_fecha_fin');
    const filtroVencimientoInput = document.getElementById('filtro_vencimiento');
    const modalFiltroFechaInicio = document.getElementById('modal_filtro_fecha_inicio');
    const modalFiltroFechaFin = document.getElementById('modal_filtro_fecha_fin');
    const modalFiltroVencimiento = document.getElementById('modal_filtro_vencimiento');
    
    let debounceTimer;
    let hayResultados = !tbody.querySelector('td[colspan="8"]');

    function cargarMovimientos(pagina = 1) {
        const params = new URLSearchParams(new FormData(formFiltros));
        params.set('ajax_search', 1);
        params.set('pagina', pagina);
        const url = `movimientos_inventario.php?${params.toString()}`;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = data.filas;
                paginacionContainer.innerHTML = data.paginacion;
                hayResultados = data.total_registros > 0;
                actualizarEstadoBotonReporte();
            })
            .catch(error => {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger p-4">Error al cargar los datos.</td></tr>';
                hayResultados = false;
                actualizarEstadoBotonReporte();
            });
    }

    function aplicarFiltrosPrincipales() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => cargarMovimientos(1), 400);
    }
    
    function actualizarIndicadorFiltrosAvanzados() {
        const isActive = filtroFechaInicioInput.value || filtroFechaFinInput.value || filtroVencimientoInput.value;
        if (isActive) {
            btnMasFiltros.classList.remove('btn-outline-secondary');
            btnMasFiltros.classList.add('btn-secondary');
            badgeFiltrosAvanzados.classList.remove('d-none');
        } else {
            btnMasFiltros.classList.add('btn-outline-secondary');
            btnMasFiltros.classList.remove('btn-secondary');
            badgeFiltrosAvanzados.classList.add('d-none');
        }
    }

    function actualizarEstadoBotonReporte() {
        const fechaInicioInvalida = modalFiltroFechaInicio.classList.contains('is-invalid-date');
        const fechaFinInvalida = modalFiltroFechaFin.classList.contains('is-invalid-date');
        btnGenerarReporte.disabled = !hayResultados || fechaInicioInvalida || fechaFinInvalida;
    }

    formFiltros.addEventListener('keyup', (e) => {
        if (e.target.type === 'text') aplicarFiltrosPrincipales();
    });
    formFiltros.addEventListener('change', (e) => {
        if (e.target.tagName === 'SELECT') cargarMovimientos(1);
    });

    btnLimpiar.addEventListener('click', () => {
        formFiltros.reset();
        modalFiltroFechaInicio.value = '';
        modalFiltroFechaFin.value = '';
        modalFiltroVencimiento.value = '';
        modalFiltroFechaInicio.classList.remove('is-invalid-date');
        modalFiltroFechaFin.classList.remove('is-invalid-date');
        actualizarIndicadorFiltrosAvanzados();
        cargarMovimientos(1);
    });
    
    document.getElementById('modalFiltrosAvanzados').addEventListener('show.bs.modal', () => {
        modalFiltroFechaInicio.value = filtroFechaInicioInput.value;
        modalFiltroFechaFin.value = filtroFechaFinInput.value;
        modalFiltroVencimiento.value = filtroVencimientoInput.value;
    });
    
    btnAplicarFiltrosModal.addEventListener('click', () => {
        filtroFechaInicioInput.value = modalFiltroFechaInicio.value;
        filtroFechaFinInput.value = modalFiltroFechaFin.value;
        filtroVencimientoInput.value = modalFiltroVencimiento.value;
        actualizarIndicadorFiltrosAvanzados();
        modalFiltrosAvanzados.hide();
        cargarMovimientos(1);
    });
    
    btnLimpiarFiltrosModal.addEventListener('click', () => {
        modalFiltroFechaInicio.value = '';
        modalFiltroFechaFin.value = '';
        modalFiltroVencimiento.value = '';
        modalFiltroFechaInicio.classList.remove('is-invalid-date');
        modalFiltroFechaFin.classList.remove('is-invalid-date');
    });

    [modalFiltroFechaInicio, modalFiltroFechaFin].forEach(input => {
        input.addEventListener('change', () => {
            if (input.value) {
                const hoy = new Date();
                const fechaInput = new Date(input.value);
                hoy.setHours(0, 0, 0, 0);
                fechaInput.setMinutes(fechaInput.getMinutes() + fechaInput.getTimezoneOffset());
                if (fechaInput > hoy) input.classList.add('is-invalid-date');
                else input.classList.remove('is-invalid-date');
            } else {
                input.classList.remove('is-invalid-date');
            }
            actualizarEstadoBotonReporte();
        });
    });

    btnGenerarReporte.addEventListener('click', () => {
        if (btnGenerarReporte.disabled) return;
        let texto = "<ul>";
        let hayFiltros = false;
        const formData = new FormData(formFiltros);
        for (const [key, value] of formData.entries()) {
            if (value && value !== 'todos') {
                const labelElement = document.querySelector(`label[for="${key}"], label[for="modal_${key}"]`);
                const label = labelElement ? labelElement.innerText.replace(':', '') : key;
                let displayValue = value;
                if(document.getElementById(key).tagName === 'SELECT'){
                    displayValue = document.getElementById(key).options[document.getElementById(key).selectedIndex].text;
                }
                texto += `<li>${label}: <strong>${displayValue}</strong></li>`;
                hayFiltros = true;
            }
        }
        if (!hayFiltros) texto += "<li><strong>Se incluirán TODOS los registros sin filtros.</strong></li>";
        texto += "</ul>";
        confirmarReporteTexto.innerHTML = texto;
        modalConfirmarReporte.show();
    });

    btnConfirmarGeneracion.addEventListener('click', () => {
        const params = new URLSearchParams(new FormData(formFiltros));
        window.location.href = `reporte_movimientos.php?${params.toString()}`;
        modalConfirmarReporte.hide();
    });

    paginacionContainer.addEventListener('click', (e) => {
        e.preventDefault();
        const link = e.target.closest('a.page-link');
        if (link && !link.parentElement.classList.contains('disabled')) {
            cargarMovimientos(parseInt(link.getAttribute('data-pagina')));
        }
    });

    tbody.addEventListener('click', (e) => {
        const botonDetalles = e.target.closest('.btn-ver-detalles');
        if (botonDetalles) {
            const idMovimiento = botonDetalles.dataset.idMovimiento;
            contenidoModalDetalles.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>';
            modalVerDetalles.show();
            fetch(`detalles_movimiento.php?id_movimiento=${idMovimiento}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const data = result.data;
                        const fechaMov = new Date(data.fecha_movimiento).toLocaleString('es-CO');
                        const fechaVenc = data.fecha_vencimiento ? new Date(data.fecha_vencimiento + 'T00:00:00').toLocaleDateString('es-CO') : 'N/A';
                        const claseBadge = [1, 3, 5].includes(parseInt(data.id_tipo_mov)) ? 'bg-success' : 'bg-danger';
                        let html = `<h6><i class="bi bi-info-circle-fill"></i> Información General</h6><p><strong>ID Movimiento:</strong> ${data.id_movimiento}<br><strong>Tipo:</strong> <span class="badge ${claseBadge}">${data.nom_mov}</span><br><strong>Fecha y Hora:</strong> ${fechaMov}<br><strong>Farmacia:</strong> ${data.nom_farm}</p><hr><h6><i class="bi bi-capsule"></i> Detalles del Medicamento</h6><p><strong>Nombre:</strong> ${data.nom_medicamento}<br><strong>Cantidad Movida:</strong> <strong>${data.cantidad} unidades</strong><br><strong>Lote:</strong> ${data.lote || 'N/A'}<br><strong>Fecha Vencimiento:</strong> ${fechaVenc}<br><strong>Código Barras:</strong> ${data.codigo_barras || 'N/A'}</p><hr><h6><i class="bi bi-person-fill"></i> Responsable del Movimiento</h6><p><strong>Nombre:</strong> ${data.nombre_responsable || 'Sistema'}<br><strong>Documento:</strong> ${data.doc_responsable || 'N/A'}</p><hr><h6><i class="bi bi-card-text"></i> Notas Adicionales</h6><p class="text-muted">${data.notas || 'Sin notas adicionales.'}</p>`;
                        contenidoModalDetalles.innerHTML = html;
                    } else {
                        contenidoModalDetalles.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
                    }
                })
                .catch(error => {
                    contenidoModalDetalles.innerHTML = '<div class="alert alert-danger">No se pudieron cargar los detalles.</div>';
                });
        }
    });
    
    actualizarIndicadorFiltrosAvanzados();
    actualizarEstadoBotonReporte();
});