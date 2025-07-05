document.addEventListener('DOMContentLoaded', function() {
    const filtroDocInput = document.getElementById('filtro_doc');
    const filtroIdInput = document.getElementById('filtro_id');
    const filtroOrdenFechaSelect = document.getElementById('filtro_orden_fecha');
    const filtroFechaInicioInput = document.getElementById('filtro_fecha_inicio');
    const filtroFechaFinInput = document.getElementById('filtro_fecha_fin');
    const btnLimpiar = document.getElementById('btnLimpiar');
    const btnGenerarReporte = document.getElementById('btnGenerarReporte');
    const tbody = document.getElementById('entregas-tbody');
    const paginacionContainer = document.getElementById('paginacion-container');
    const modalVerDetalles = new bootstrap.Modal(document.getElementById('modalVerDetalles'));
    const contenidoModalDetalles = document.getElementById('contenidoModalDetalles');
    const modalConfirmarReporte = new bootstrap.Modal(document.getElementById('modalConfirmarReporte'));
    const confirmarReporteTexto = document.getElementById('confirmarReporteTexto');
    const btnConfirmarGeneracion = document.getElementById('btnConfirmarGeneracion');
    let debounceTimer;

    let hayResultados = false; // Variable para rastrear si hay datos en la tabla

    function cargarEntregas(pagina = 1) {
        const params = getCurrentFilters();
        params.set('ajax_search', 1);
        params.set('pagina', pagina);
        
        const url = `lista_entregas.php?${params.toString()}`;

        fetch(url)
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = data.filas;
                paginacionContainer.innerHTML = data.paginacion;

                hayResultados = data.total_registros > 0;
                actualizarEstadoBotonReporte(); // Actualizar el estado del botón
            })
            .catch(error => {
                console.error('Error al cargar las entregas:', error);
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger p-4">Error al cargar los datos. Intente de nuevo.</td></tr>';
                hayResultados = false;
                actualizarEstadoBotonReporte();
            });
    }

    function getCurrentFilters() {
        return new URLSearchParams({
            filtro_doc: filtroDocInput.value,
            filtro_id: filtroIdInput.value,
            filtro_orden_fecha: filtroOrdenFechaSelect.value,
            filtro_fecha_inicio: filtroFechaInicioInput.value,
            filtro_fecha_fin: filtroFechaFinInput.value
        });
    }

    function aplicarFiltros() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => cargarEntregas(1), 400);
    }

    function validarFecha(inputElement) {
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
        actualizarEstadoBotonReporte(); // Actualizar después de validar
        return esValida;
    }

    // --- ¡NUEVA FUNCIÓN CLAVE! ---
    function actualizarEstadoBotonReporte() {
        const fechaInicioInvalida = filtroFechaInicioInput.classList.contains('is-invalid-date');
        const fechaFinInvalida = filtroFechaFinInput.classList.contains('is-invalid-date');

        if (hayResultados && !fechaInicioInvalida && !fechaFinInvalida) {
            btnGenerarReporte.disabled = false;
        } else {
            btnGenerarReporte.disabled = true;
        }
    }
    
    filtroDocInput.addEventListener('keyup', aplicarFiltros);
    filtroIdInput.addEventListener('keyup', aplicarFiltros);
    filtroOrdenFechaSelect.addEventListener('change', () => cargarEntregas(1));
    
    filtroFechaInicioInput.addEventListener('change', function() {
        validarFecha(this);
        cargarEntregas(1);
    });
    filtroFechaFinInput.addEventListener('change', function() {
        validarFecha(this);
        cargarEntregas(1);
    });

    btnLimpiar.addEventListener('click', () => {
        document.getElementById('formFiltros').reset();
        filtroFechaInicioInput.classList.remove('is-invalid-date');
        filtroFechaFinInput.classList.remove('is-invalid-date');
        cargarEntregas(1);
    });
    
    btnGenerarReporte.addEventListener('click', () => {
        // La comprobación ahora está implícita porque el botón estará deshabilitado.
        // Pero mantenemos esto como una doble seguridad.
        if (btnGenerarReporte.disabled) {
            return;
        }

        let texto = "<ul>";
        let hayFiltros = false;
        
        if (filtroDocInput.value) { texto += `<li>Doc. Paciente: <strong>${filtroDocInput.value}</strong></li>`; hayFiltros = true; }
        if (filtroIdInput.value) { texto += `<li>ID Entrega: <strong>${filtroIdInput.value}</strong></li>`; hayFiltros = true; }
        if (filtroFechaInicioInput.value) { texto += `<li>Fecha Inicio: <strong>${filtroFechaInicioInput.value}</strong></li>`; hayFiltros = true; }
        if (filtroFechaFinInput.value) { texto += `<li>Fecha Fin: <strong>${filtroFechaFinInput.value}</strong></li>`; hayFiltros = true; }
        
        if (!hayFiltros) {
            texto += "<li><strong>Se incluirán TODOS los registros sin filtros.</strong></li>";
        }
        texto += "</ul>";

        confirmarReporteTexto.innerHTML = texto;
        modalConfirmarReporte.show();
    });

    btnConfirmarGeneracion.addEventListener('click', () => {
        const params = getCurrentFilters();
        const urlReporte = `generar_reporte.php?${params.toString()}`;
        window.location.href = urlReporte;
        modalConfirmarReporte.hide();
    });

    paginacionContainer.addEventListener('click', (e) => {
        e.preventDefault();
        const link = e.target.closest('a.page-link');
        if (link && !link.parentElement.classList.contains('disabled')) {
            const pagina = link.getAttribute('data-pagina');
            if (pagina) {
                cargarEntregas(parseInt(pagina));
            }
        }
    });

    tbody.addEventListener('click', (e) => {
        const boton = e.target.closest('.btn-ver-detalles');
        if (boton) {
            const idEntrega = boton.getAttribute('data-id-entrega');
            contenidoModalDetalles.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
            modalVerDetalles.show();
            
            fetch(`ajax_detalles_entrega.php?id=${idEntrega}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        contenidoModalDetalles.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                        return;
                    }
                    
                    let html = `<h6><i class="bi bi-person-fill"></i> Datos del Paciente</h6><p><strong>Nombre:</strong> ${data.paciente.nombre_paciente}<br><strong>Documento:</strong> ${data.paciente.doc_paciente}</p><hr><h6><i class="bi bi-person-badge-fill"></i> Datos de la Entrega</h6><p><strong>Entregado por:</strong> ${data.entrega.nombre_farmaceuta}<br><strong>Fecha y Hora:</strong> ${data.entrega.fecha_entrega}</p><hr><h6><i class="bi bi-capsule"></i> Medicamento Entregado</h6><p><strong>Medicamento:</strong> ${data.medicamento.nom_medicamento}<br><strong>Cantidad Entregada:</strong> ${data.entrega.cantidad_entregada} unidades<br><strong>Observaciones:</strong> ${data.entrega.observaciones}</p><hr>`;
                    if(data.pendiente){
                        html += `<div class="alert alert-warning"><h6 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Pendiente Generado</h6><p><strong>Medicamento:</strong> ${data.pendiente.nom_medicamento}<br><strong>Cantidad Pendiente:</strong> ${data.pendiente.cantidad_pendiente} unidades<br><strong>Fecha de Generación:</strong> ${data.pendiente.fecha_generacion}</p></div>`;
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

    cargarEntregas(1);
});