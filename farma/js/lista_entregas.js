document.addEventListener('DOMContentLoaded', function() {
    // --- 1. SELECCIÓN DE ELEMENTOS Y CONFIGURACIÓN ---
    const filtroDocInput = document.getElementById('filtro_doc');
    const filtroIdInput = document.getElementById('filtro_id');
    const filtroOrdenFechaSelect = document.getElementById('filtro_orden_fecha');
    const filtroFechaInicioInput = document.getElementById('filtro_fecha_inicio');
    const filtroFechaFinInput = document.getElementById('filtro_fecha_fin');
    const btnLimpiar = document.getElementById('btnLimpiar');
    const btnGenerarReporte = document.getElementById('btnGenerarReporte');
    const tbody = document.getElementById('entregas-tbody');
    const paginacionContainer = document.getElementById('paginacion-container');

    const modalVerDetallesElem = document.getElementById('modalVerDetalles');
    const modalVerDetalles = modalVerDetallesElem ? new bootstrap.Modal(modalVerDetallesElem) : null;
    const contenidoModalDetalles = document.getElementById('contenidoModalDetalles');
    const modalConfirmarReporteElem = document.getElementById('modalConfirmarReporte');
    const modalConfirmarReporte = modalConfirmarReporteElem ? new bootstrap.Modal(modalConfirmarReporteElem) : null;
    const confirmarReporteTexto = document.getElementById('confirmarReporteTexto');
    const btnConfirmarGeneracion = document.getElementById('btnConfirmarGeneracion');
    
    // MEJORA: Se obtienen las rutas desde el DOM para no tenerlas fijas en el JS.
    const API_BASE_URL = document.querySelector('meta[name="api-base-url"]')?.getAttribute('content') || '/SALUDCONNECT/farma/entregar/';

    let debounceTimer;
    let hayResultados = false;

    // --- 2. FUNCIONES AUXILIARES ---

    function cargarEntregas(pagina = 1) {
        if (!tbody || !paginacionContainer) return;

        const params = getCurrentFilters();
        params.set('ajax_search', 1);
        params.set('pagina', pagina);
        
        // MEJORA: La URL de la API es ahora dinámica.
        const url = `${API_BASE_URL}lista_entregas.php?${params.toString()}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = data.filas;
                paginacionContainer.innerHTML = data.paginacion;
                hayResultados = data.total_registros > 0;
                actualizarEstadoBotonReporte();
            })
            .catch(error => {
                console.error('Error al cargar las entregas:', error);
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger p-4">Error al cargar los datos.</td></tr>';
                hayResultados = false;
                actualizarEstadoBotonReporte();
            });
    }

    function getCurrentFilters() {
        return new URLSearchParams({
            filtro_doc: filtroDocInput?.value || '',
            filtro_id: filtroIdInput?.value || '',
            filtro_orden_fecha: filtroOrdenFechaSelect?.value || 'desc',
            filtro_fecha_inicio: filtroFechaInicioInput?.value || '',
            filtro_fecha_fin: filtroFechaFinInput?.value || ''
        });
    }

    function aplicarFiltros() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => cargarEntregas(1), 400);
    }

    function validarFecha(inputElement) {
        if (!inputElement) return true;
        const fechaSeleccionada = inputElement.value;
        let esValida = true;
        
        if (fechaSeleccionada) {
            const hoy = new Date();
            const fechaInput = new Date(fechaSeleccionada);
            hoy.setHours(0, 0, 0, 0);
            fechaInput.setMinutes(fechaInput.getMinutes() + fechaInput.getTimezoneOffset());

            if (fechaInput > hoy) {
                inputElement.classList.add('is-invalid-date');
                esValida = false;
            } else {
                inputElement.classList.remove('is-invalid-date');
            }
        } else {
            inputElement.classList.remove('is-invalid-date');
        }
        actualizarEstadoBotonReporte();
        return esValida;
    }

    function actualizarEstadoBotonReporte() {
        if (!btnGenerarReporte) return;
        const fechaInicioInvalida = filtroFechaInicioInput?.classList.contains('is-invalid-date');
        const fechaFinInvalida = filtroFechaFinInput?.classList.contains('is-invalid-date');
        btnGenerarReporte.disabled = !(hayResultados && !fechaInicioInvalida && !fechaFinInvalida);
    }
    
    // --- 3. INICIALIZACIÓN DE EVENTOS ---
    
    filtroDocInput?.addEventListener('keyup', aplicarFiltros);
    filtroIdInput?.addEventListener('keyup', aplicarFiltros);
    filtroOrdenFechaSelect?.addEventListener('change', () => cargarEntregas(1));
    
    filtroFechaInicioInput?.addEventListener('change', function() {
        validarFecha(this);
        cargarEntregas(1);
    });
    filtroFechaFinInput?.addEventListener('change', function() {
        validarFecha(this);
        cargarEntregas(1);
    });

    btnLimpiar?.addEventListener('click', () => {
        document.getElementById('formFiltros')?.reset();
        filtroFechaInicioInput?.classList.remove('is-invalid-date');
        filtroFechaFinInput?.classList.remove('is-invalid-date');
        cargarEntregas(1);
    });
    
    btnGenerarReporte?.addEventListener('click', () => {
        if (btnGenerarReporte.disabled) return;

        // MEJORA DE SEGURIDAD: Construcción segura del HTML.
        confirmarReporteTexto.innerHTML = '';
        const ul = document.createElement('ul');
        let hayFiltros = false;
        
        const addFilterItem = (label, value) => {
            const li = document.createElement('li');
            li.innerHTML = `${label}: <strong></strong>`;
            li.querySelector('strong').textContent = value;
            ul.appendChild(li);
            hayFiltros = true;
        };
        
        if (filtroDocInput.value) { addFilterItem('Doc. Paciente', filtroDocInput.value); hayFiltros = true; }
        if (filtroIdInput.value) { addFilterItem('ID Entrega', filtroIdInput.value); hayFiltros = true; }
        if (filtroFechaInicioInput.value) { addFilterItem('Fecha Inicio', filtroFechaInicioInput.value); hayFiltros = true; }
        if (filtroFechaFinInput.value) { addFilterItem('Fecha Fin', filtroFechaFinInput.value); hayFiltros = true; }
        
        if (!hayFiltros) {
            const li = document.createElement('li');
            li.innerHTML = '<strong>Se incluirán TODOS los registros sin filtros.</strong>';
            ul.appendChild(li);
        }

        confirmarReporteTexto.appendChild(ul);
        modalConfirmarReporte.show();
    });

    btnConfirmarGeneracion?.addEventListener('click', () => {
        const params = getCurrentFilters();
        // MEJORA: URL dinámica.
        const urlReporte = `${API_BASE_URL}generar_reporte.php?${params.toString()}`;
        window.open(urlReporte, '_blank');
        modalConfirmarReporte.hide();
    });

    paginacionContainer?.addEventListener('click', (e) => {
        e.preventDefault();
        const link = e.target.closest('a.page-link');
        if (link && !link.parentElement.classList.contains('disabled')) {
            const pagina = link.getAttribute('data-pagina');
            if (pagina) cargarEntregas(parseInt(pagina));
        }
    });

    tbody?.addEventListener('click', (e) => {
        const boton = e.target.closest('.btn-ver-detalles');
        if (boton) {
            const idEntrega = boton.getAttribute('data-id-entrega');
            contenidoModalDetalles.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>';
            modalVerDetalles.show();
            
            // MEJORA: URL dinámica.
            fetch(`${API_BASE_URL}ajax_detalles_entrega.php?id=${idEntrega}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error || !data.success) { // Se revisa también `success` por consistencia
                        contenidoModalDetalles.innerHTML = `<div class="alert alert-danger">${data.message || data.error}</div>`;
                        return;
                    }
                    
                    let html = `<h6><i class="bi bi-person-fill"></i> Datos del Paciente</h6><p><strong>Nombre:</strong> ${data.data.paciente.nombre_paciente}<br><strong>Documento:</strong> ${data.data.paciente.doc_paciente}</p><hr><h6><i class="bi bi-person-badge-fill"></i> Datos de la Entrega</h6><p><strong>Entregado por:</strong> ${data.data.entrega.nombre_farmaceuta}<br><strong>Fecha y Hora:</strong> ${data.data.entrega.fecha_entrega}</p><hr><h6><i class="bi bi-capsule"></i> Medicamento Entregado</h6><p><strong>Medicamento:</strong> ${data.data.medicamento.nom_medicamento}<br><strong>Cantidad Entregada:</strong> ${data.data.entrega.cantidad_entregada} unidades<br><strong>Observaciones:</strong> ${data.data.entrega.observaciones}</p><hr>`;
                    if(data.data.pendiente){
                        html += `<div class="alert alert-warning"><h6 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Pendiente Generado</h6><p><strong>Medicamento:</strong> ${data.data.medicamento.nom_medicamento}<br><strong>Cantidad Pendiente:</strong> ${data.data.pendiente.cantidad_pendiente} unidades<br><strong>Fecha de Generación:</strong> ${data.data.pendiente.fecha_generacion}</p></div>`;
                    } else {
                         html += `<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> No se generaron pendientes para esta entrega.</div>`;
                    }
                    contenidoModalDetalles.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error al cargar detalles:', error);
                    contenidoModalDetalles.innerHTML = '<div class="alert alert-danger">No se pudieron cargar los detalles.</div>';
                });
        }
    });

    // Carga inicial de datos.
    cargarEntregas(1);
});