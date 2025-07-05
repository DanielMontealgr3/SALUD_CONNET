document.addEventListener('DOMContentLoaded', function() {
    // --- 1. SELECCIÓN DE ELEMENTOS Y CONFIGURACIÓN ---
    const formFiltros = document.getElementById('formFiltros');
    const tbody = document.getElementById('movimientos-tbody');
    const paginacionContainer = document.getElementById('paginacion-container');
    const btnLimpiar = document.getElementById('btnLimpiar');
    const btnGenerarReporte = document.getElementById('btnGenerarReporte');
    const btnMasFiltros = document.getElementById('btnMasFiltros');
    const badgeFiltrosAvanzados = document.getElementById('badge-filtros-avanzados');

    const modalFiltrosAvanzadosElem = document.getElementById('modalFiltrosAvanzados');
    const modalFiltrosAvanzados = modalFiltrosAvanzadosElem ? new bootstrap.Modal(modalFiltrosAvanzadosElem) : null;
    const modalVerDetallesElem = document.getElementById('modalVerDetalles');
    const modalVerDetalles = modalVerDetallesElem ? new bootstrap.Modal(modalVerDetallesElem) : null;
    const modalConfirmarReporteElem = document.getElementById('modalConfirmarReporte');
    const modalConfirmarReporte = modalConfirmarReporteElem ? new bootstrap.Modal(modalConfirmarReporteElem) : null;
    
    const contenidoModalDetalles = document.getElementById('contenidoModalDetalles');
    const confirmarReporteTexto = document.getElementById('confirmarReporteTexto');
    const btnConfirmarGeneracion = document.getElementById('btnConfirmarGeneracion');
    const btnAplicarFiltrosModal = document.getElementById('btnAplicarFiltrosModal');
    const btnLimpiarFiltrosModal = document.getElementById('btnLimpiarFiltrosModal');

    // Inputs de fecha (en el DOM principal y en el modal)
    const filtroFechaInicioInput = document.getElementById('filtro_fecha_inicio');
    const filtroFechaFinInput = document.getElementById('filtro_fecha_fin');
    const filtroVencimientoInput = document.getElementById('filtro_vencimiento');
    const modalFiltroFechaInicio = document.getElementById('modal_filtro_fecha_inicio');
    const modalFiltroFechaFin = document.getElementById('modal_filtro_fecha_fin');
    const modalFiltroVencimiento = document.getElementById('modal_filtro_vencimiento');
    
    // MEJORA: Se obtienen las rutas desde el DOM.
    const API_BASE_URL = document.querySelector('meta[name="api-base-url"]')?.getAttribute('content') || '/SALUDCONNECT/farma/movimientos/';

    let debounceTimer;
    let hayResultados = tbody ? !tbody.querySelector('td[colspan="8"]') : false;

    // --- 2. FUNCIONES AUXILIARES ---

    function cargarMovimientos(pagina = 1) {
        if (!formFiltros || !tbody || !paginacionContainer) return;
        
        const params = new URLSearchParams(new FormData(formFiltros));
        params.set('ajax_search', 1);
        params.set('pagina', pagina);
        
        // MEJORA: La URL de la API es ahora dinámica.
        const url = `${API_BASE_URL}movimientos_inventario.php?${params.toString()}`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = data.filas;
                paginacionContainer.innerHTML = data.paginacion;
                hayResultados = data.total_registros > 0;
                actualizarEstadoBotonReporte();
            })
            .catch(error => {
                console.error('Error al cargar movimientos:', error);
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
        if (!filtroFechaInicioInput || !filtroFechaFinInput || !filtroVencimientoInput || !btnMasFiltros || !badgeFiltrosAvanzados) return;
        const isActive = filtroFechaInicioInput.value || filtroFechaFinInput.value || filtroVencimientoInput.value;
        btnMasFiltros.classList.toggle('btn-secondary', isActive);
        btnMasFiltros.classList.toggle('btn-outline-secondary', !isActive);
        badgeFiltrosAvanzados.classList.toggle('d-none', !isActive);
    }

    function actualizarEstadoBotonReporte() {
        if (!btnGenerarReporte) return;
        const fechaInicioInvalida = modalFiltroFechaInicio?.classList.contains('is-invalid-date');
        const fechaFinInvalida = modalFiltroFechaFin?.classList.contains('is-invalid-date');
        btnGenerarReporte.disabled = !hayResultados || fechaInicioInvalida || fechaFinInvalida;
    }

    // --- 3. INICIALIZACIÓN DE EVENTOS ---
    
    formFiltros?.addEventListener('keyup', (e) => {
        if (e.target.type === 'text') aplicarFiltrosPrincipales();
    });
    formFiltros?.addEventListener('change', (e) => {
        if (e.target.tagName === 'SELECT') cargarMovimientos(1);
    });

    btnLimpiar?.addEventListener('click', () => {
        formFiltros.reset();
        if(filtroFechaInicioInput) filtroFechaInicioInput.value = '';
        if(filtroFechaFinInput) filtroFechaFinInput.value = '';
        if(filtroVencimientoInput) filtroVencimientoInput.value = '';
        modalFiltroFechaInicio?.classList.remove('is-invalid-date');
        modalFiltroFechaFin?.classList.remove('is-invalid-date');
        actualizarIndicadorFiltrosAvanzados();
        cargarMovimientos(1);
    });
    
    modalFiltrosAvanzadosElem?.addEventListener('show.bs.modal', () => {
        if(modalFiltroFechaInicio) modalFiltroFechaInicio.value = filtroFechaInicioInput.value;
        if(modalFiltroFechaFin) modalFiltroFechaFin.value = filtroFechaFinInput.value;
        if(modalFiltroVencimiento) modalFiltroVencimiento.value = filtroVencimientoInput.value;
    });
    
    btnAplicarFiltrosModal?.addEventListener('click', () => {
        filtroFechaInicioInput.value = modalFiltroFechaInicio.value;
        filtroFechaFinInput.value = modalFiltroFechaFin.value;
        filtroVencimientoInput.value = modalFiltroVencimiento.value;
        actualizarIndicadorFiltrosAvanzados();
        modalFiltrosAvanzados.hide();
        cargarMovimientos(1);
    });
    
    btnLimpiarFiltrosModal?.addEventListener('click', () => {
        modalFiltroFechaInicio.value = '';
        modalFiltroFechaFin.value = '';
        modalFiltroVencimiento.value = '';
        modalFiltroFechaInicio.classList.remove('is-invalid-date');
        modalFiltroFechaFin.classList.remove('is-invalid-date');
    });

    [modalFiltroFechaInicio, modalFiltroFechaFin].forEach(input => {
        input?.addEventListener('change', () => {
            input.classList.toggle('is-invalid-date', input.value && new Date(input.value) > new Date());
            actualizarEstadoBotonReporte();
        });
    });

    btnGenerarReporte?.addEventListener('click', () => {
        if (btnGenerarReporte.disabled) return;
        confirmarReporteTexto.innerHTML = ''; // Limpiar contenido anterior
        const ul = document.createElement('ul');
        let hayFiltros = false;
        
        new FormData(formFiltros).forEach((value, key) => {
            if (value && value !== 'todos') {
                const inputElement = document.getElementById(key) || document.getElementById(`modal_${key}`);
                const labelElement = document.querySelector(`label[for="${inputElement.id}"]`);
                const label = labelElement ? labelElement.innerText.replace(':', '').trim() : key;
                let displayValue = value;

                if (inputElement?.tagName === 'SELECT') {
                    displayValue = inputElement.options[inputElement.selectedIndex].text;
                }

                const li = document.createElement('li');
                li.innerHTML = `${label}: <strong></strong>`;
                li.querySelector('strong').textContent = displayValue;
                ul.appendChild(li);
                hayFiltros = true;
            }
        });
        
        if (!hayFiltros) ul.innerHTML = "<li><strong>Se incluirán TODOS los registros sin filtros.</strong></li>";
        
        confirmarReporteTexto.appendChild(ul);
        modalConfirmarReporte.show();
    });

    btnConfirmarGeneracion?.addEventListener('click', () => {
        const params = new URLSearchParams(new FormData(formFiltros));
        // MEJORA: La URL de la API es ahora dinámica.
        window.open(`${API_BASE_URL}reporte_movimientos.php?${params.toString()}`, '_blank');
        modalConfirmarReporte.hide();
    });

    paginacionContainer?.addEventListener('click', (e) => {
        e.preventDefault();
        const link = e.target.closest('a.page-link');
        if (link && !link.parentElement.classList.contains('disabled')) {
            cargarMovimientos(parseInt(link.dataset.pagina));
        }
    });

    tbody?.addEventListener('click', (e) => {
        const botonDetalles = e.target.closest('.btn-ver-detalles');
        if (botonDetalles) {
            const idMovimiento = botonDetalles.dataset.idMovimiento;
            contenidoModalDetalles.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>';
            modalVerDetalles.show();
            // MEJORA: La URL de la API es ahora dinámica.
            fetch(`${API_BASE_URL}detalles_movimiento.php?id_movimiento=${idMovimiento}`)
                .then(response => response.json())
                .then(result => {
                    // ... (Tu lógica para renderizar el detalle era correcta y se mantiene)
                    if (result.success) {
                        const data = result.data;
                        const fechaMov = new Date(data.fecha_movimiento).toLocaleString('es-CO');
                        const fechaVenc = data.fecha_vencimiento ? new Date(data.fecha_vencimiento + 'T00:00:00').toLocaleDateString('es-CO') : 'N/A';
                        const claseBadge = [1, 3, 5].includes(parseInt(data.id_tipo_mov)) ? 'bg-success' : 'bg-danger';
                        contenidoModalDetalles.innerHTML = `<h6><i class="bi bi-info-circle-fill"></i> Información General</h6><p><strong>ID Movimiento:</strong> ${data.id_movimiento}<br><strong>Tipo:</strong> <span class="badge ${claseBadge}">${data.nom_mov}</span><br><strong>Fecha y Hora:</strong> ${fechaMov}<br><strong>Farmacia:</strong> ${data.nom_farm}</p><hr><h6><i class="bi bi-capsule"></i> Detalles del Medicamento</h6><p><strong>Nombre:</strong> ${data.nom_medicamento}<br><strong>Cantidad Movida:</strong> <strong>${data.cantidad} unidades</strong><br><strong>Lote:</strong> ${data.lote || 'N/A'}<br><strong>Fecha Vencimiento:</strong> ${fechaVenc}<br><strong>Código Barras:</strong> ${data.codigo_barras || 'N/A'}</p><hr><h6><i class="bi bi-person-fill"></i> Responsable del Movimiento</h6><p><strong>Nombre:</strong> ${data.nombre_responsable || 'Sistema'}<br><strong>Documento:</strong> ${data.doc_responsable || 'N/A'}</p><hr><h6><i class="bi bi-card-text"></i> Notas Adicionales</h6><p class="text-muted">${data.notas || 'Sin notas adicionales.'}</p>`;
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