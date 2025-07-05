document.addEventListener('DOMContentLoaded', function() {
    const filtros = {
        tipo: document.getElementById('filtro_tipo'),
        estado: document.getElementById('filtro_estado'),
        orden_fecha: document.getElementById('filtro_orden_fecha'),
        nombre_eps: document.getElementById('filtro_nombre_eps'),
        nombre_entidad_aliada: document.getElementById('filtro_nombre_entidad_aliada'),
        fecha_inicio: document.getElementById('filtro_fecha_inicio'),
        fecha_fin: document.getElementById('filtro_fecha_fin'),
        fecha_exacta: document.getElementById('filtro_fecha_exacta')
    };

    const dateInputs = [filtros.fecha_inicio, filtros.fecha_fin, filtros.fecha_exacta];
    const btnLimpiar = document.getElementById('btn_limpiar_filtros');
    const tablaBody = document.getElementById('tabla_alianzas_body');
    const paginacionContainer = document.getElementById('paginacion_lista');
    
    const modalMasFiltros = new bootstrap.Modal(document.getElementById('modalMasFiltros'));
    const btnLimpiarFechas = document.getElementById('btn_limpiar_fechas');
    const btnAplicarFiltrosFecha = document.getElementById('btn_aplicar_filtros_fecha');
    const indicadorFiltroAvanzado = document.getElementById('filtro_avanzado_indicator');

    const btnGenerarReporte = document.getElementById('btn_generar_reporte');
    const modalConfirmarReporte = new bootstrap.Modal(document.getElementById('modalConfirmarReporteAlianzas'));
    const confirmarReporteTexto = document.getElementById('confirmarReporteTextoAlianzas');
    const btnConfirmarGeneracion = document.getElementById('btnConfirmarGeneracionAlianzas');
    
    const modalVerAlianza = new bootstrap.Modal(document.getElementById('modalVerAlianza'));
    let debounceTimer;

    function actualizarIndicadorFiltros() {
        if (dateInputs.some(input => input.value)) {
            indicadorFiltroAvanzado.style.display = 'flex';
        } else {
            indicadorFiltroAvanzado.style.display = 'none';
        }
    }

    function fetchAlianzas(pagina = 1) {
        let params = new URLSearchParams();
        params.append('pagina', pagina);
        for (const key in filtros) {
            params.append(`filtro_${key}`, filtros[key].value.trim());
        }

        fetch(`lista_alianzas.php?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                tablaBody.innerHTML = data.html_body;
                actualizarPaginacion(data.paginacion);
                btnGenerarReporte.disabled = (data.paginacion.total_registros === 0);
                inicializarListenersBotones();
            }).catch(error => {
                console.error("Fetch Error:", error);
                tablaBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger p-4">Error de comunicación. Intente de nuevo.</td></tr>';
                btnGenerarReporte.disabled = true;
            });
    }

    function actualizarPaginacion({ actual, total }) {
        paginacionContainer.innerHTML = '';
        if (total <= 1) return;
        let html = `<li class="page-item ${actual <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-pagina="${actual - 1}"><</a></li>`;
        html += `<li class="page-item active"><a class="page-link" href="#">${actual} / ${total}</a></li>`;
        html += `<li class="page-item ${actual >= total ? 'disabled' : ''}"><a class="page-link" href="#" data-pagina="${actual + 1}">></a></li>`;
        paginacionContainer.innerHTML = html;
    }

    function inicializarListenersBotones() {
        document.querySelectorAll('.btn-cambiar-estado, .btn-eliminar-alianza').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id; const tabla = this.dataset.tabla;
                const accion = this.dataset.accion || 'eliminar'; const info = this.dataset.info || `la alianza (ID: ${id})`;
                const esEliminar = accion === 'eliminar';
                const titulo = esEliminar ? '¿Confirmar Eliminación?' : `¿Confirmar ${accion === 'activar' ? 'Activación' : 'Inactivación'}?`;
                const texto = esEliminar ? `¿Seguro que deseas eliminar ${info}?` : `¿Seguro que deseas ${accion} ${info}?`;
                Swal.fire({
                    title: titulo, html: texto, icon: 'warning', showCancelButton: true,
                    confirmButtonColor: esEliminar ? '#e74c3c' : (accion === 'activar' ? '#28a745' : '#ffc107'),
                    cancelButtonColor: '#6c757d', confirmButtonText: 'Sí, ¡Confirmar!', cancelButtonText: 'Cancelar'
                }).then((result) => { if (result.isConfirmed) { gestionarAlianza(id, tabla, accion); } });
            });
        });
        document.querySelectorAll('.btn-ver-detalles-alianza').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id; const tabla = this.dataset.tabla;
                const modalBody = document.getElementById('modalVerAlianzaBody');
                modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div><p>Cargando...</p></div>';
                modalVerAlianza.show();
                fetch(`ajax_detalle_alianza.php?id=${id}&tabla=${tabla}`).then(r => r.text()).then(h => modalBody.innerHTML = h);
            });
        });
    }

    function gestionarAlianza(id, tabla, accion) {
        const formData = new FormData();
        formData.append('id_alianza', id); formData.append('tabla_origen', tabla);
        formData.append('accion', accion); formData.append('csrf_token', csrfTokenAlianzas);
        fetch('ajax_gestionar_alianza.php', { method: 'POST', body: formData }).then(r => r.json())
        .then(data => { if (data.success) { fetchAlianzas(parseInt(paginacionContainer.querySelector('.page-item.active .page-link')?.textContent.split(' ')[0] || '1', 10)); } });
    }

    function validarFecha(input) {
        const feedbackEl = input.nextElementSibling;
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        input.classList.remove('is-invalid', 'is-valid');
        feedbackEl.textContent = '';
        if (!input.value) return true;
        const fechaInput = new Date(input.value + 'T00:00:00');
        if (fechaInput > hoy) {
            input.classList.add('is-invalid');
            feedbackEl.textContent = 'La fecha no puede ser futura.';
            return false;
        }
        input.classList.add('is-valid');
        return true;
    }

    function actualizarEstadoBotonAplicar() {
        const todasValidas = dateInputs.every(validarFecha);
        btnAplicarFiltrosFecha.disabled = !todasValidas;
    }
    
    fetchAlianzas(1);
    actualizarIndicadorFiltros();

    [filtros.tipo, filtros.estado, filtros.orden_fecha].forEach(f => f.addEventListener('change', () => fetchAlianzas(1)));
    [filtros.nombre_eps, filtros.nombre_entidad_aliada].forEach(f => f.addEventListener('input', () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => fetchAlianzas(1), 400); }));
    
    btnLimpiar.addEventListener('click', () => {
        Object.values(filtros).forEach(input => input.value = '');
        filtros.tipo.value = 'todas';
        filtros.orden_fecha.value = 'desc';
        dateInputs.forEach(input => input.classList.remove('is-valid', 'is-invalid'));
        actualizarEstadoBotonAplicar();
        fetchAlianzas(1);
        actualizarIndicadorFiltros();
    });

    dateInputs.forEach(input => input.addEventListener('input', actualizarEstadoBotonAplicar));
    
    btnAplicarFiltrosFecha.addEventListener('click', () => {
        if (btnAplicarFiltrosFecha.disabled) return;
        if(filtros.fecha_exacta.value) {
            filtros.fecha_inicio.value = '';
            filtros.fecha_fin.value = '';
        }
        fetchAlianzas(1); 
        actualizarIndicadorFiltros(); 
        modalMasFiltros.hide(); 
    });
    btnLimpiarFechas.addEventListener('click', () => { 
        dateInputs.forEach(input => {
            input.value = '';
            input.classList.remove('is-valid', 'is-invalid');
        });
        actualizarEstadoBotonAplicar();
        fetchAlianzas(1); 
        actualizarIndicadorFiltros(); 
    });

    paginacionContainer.addEventListener('click', (e) => {
        e.preventDefault(); const link = e.target.closest('a.page-link');
        if (link && !link.parentElement.classList.contains('disabled') && !link.parentElement.classList.contains('active')) { fetchAlianzas(link.dataset.pagina); }
    });

    btnGenerarReporte.addEventListener('click', () => {
        if (btnGenerarReporte.disabled) return; let texto = "<ul>";
        if (filtros.tipo.value !== 'todas') texto += `<li>Tipo: <strong>${filtros.tipo.options[filtros.tipo.selectedIndex].text}</strong></li>`;
        if (filtros.estado.value !== '') texto += `<li>Estado: <strong>${filtros.estado.options[filtros.estado.selectedIndex].text}</strong></li>`;
        if (filtros.nombre_eps.value) texto += `<li>Nombre EPS: <strong>${filtros.nombre_eps.value}</strong></li>`;
        if (filtros.nombre_entidad_aliada.value) texto += `<li>Nombre Aliada: <strong>${filtros.nombre_entidad_aliada.value}</strong></li>`;
        
        if (filtros.fecha_exacta.value) {
            texto += `<li>Fecha Exacta: <strong>${filtros.fecha_exacta.value}</strong></li>`;
        } else {
            if (filtros.fecha_inicio.value) texto += `<li>Fecha Inicio: <strong>${filtros.fecha_inicio.value}</strong></li>`;
            if (filtros.fecha_fin.value) texto += `<li>Fecha Fin: <strong>${filtros.fecha_fin.value}</strong></li>`;
        }

        if (texto === "<ul>") texto += "<li>Se incluirán <strong>TODOS</strong> los registros sin filtros.</li>";
        texto += "</ul>"; confirmarReporteTexto.innerHTML = texto; modalConfirmarReporte.show();
    });

    btnConfirmarGeneracion.addEventListener('click', () => {
        const params = new URLSearchParams();
        for (const key in filtros) {
            params.append(`filtro_${key}`, filtros[key].value.trim());
        }
        window.location.href = `reporte_alianzas.php?${params.toString()}`; modalConfirmarReporte.hide();
    });
});