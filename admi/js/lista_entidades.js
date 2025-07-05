document.addEventListener('DOMContentLoaded', function () {
    const filtroNombre = document.getElementById('filtro_nombre');
    const filtroTipo = document.getElementById('tipo_entidad_select');
    const tablaHeader = document.getElementById('tabla-entidades-header');
    const tablaBody = document.getElementById('tabla-entidades-body');
    const paginacionContainer = document.getElementById('paginacion-lista');
    const loader = document.getElementById('loaderBusqueda');
    const btnLimpiar = document.getElementById('btnLimpiarFiltros');
    const tituloTabla = document.getElementById('titulo-de-tabla');
    
    const btnGenerarReporte = document.getElementById('btnGenerarReporte');
    const modalConfirmarReporte = new bootstrap.Modal(document.getElementById('modalConfirmarReporte'));
    const confirmarReporteTexto = document.getElementById('confirmarReporteTexto');
    const btnConfirmarGeneracion = document.getElementById('btnConfirmarGeneracion');

    let modalDetalles = new bootstrap.Modal(document.getElementById('modalVerDetalles'));
    let debounceTimer;
    let tieneResultados = btnGenerarReporte.disabled === false;

    function fetchEntidades(pagina = 1) {
        loader.style.display = 'block';
        const nombre = filtroNombre.value.trim();
        const tipo = filtroTipo.value;
        const url = `ver_entidades.php?pagina=${pagina}&filtro_nombre=${encodeURIComponent(nombre)}&tipo_entidad=${encodeURIComponent(tipo)}`;
        
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(response => response.json())
        .then(data => {
            tituloTabla.textContent = data.titulo_tabla;
            tablaHeader.innerHTML = '<tr>' + data.html_header + '</tr>';
            tablaBody.innerHTML = data.html_body;
            actualizarPaginacion(data.paginacion.pagina_actual, data.paginacion.total_paginas);
            
            tieneResultados = !tablaBody.querySelector('td[colspan]');
            actualizarEstadoBotonReporte();

            actualizarURL(pagina, nombre, tipo);
            inicializarListenersBotones();
        })
        .catch(error => {
            console.error('Error al cargar entidades:', error);
            tablaBody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error al cargar los datos.</td></tr>';
            tieneResultados = false;
            actualizarEstadoBotonReporte();
        })
        .finally(() => { loader.style.display = 'none'; });
    }
    
    function actualizarEstadoBotonReporte() {
        btnGenerarReporte.disabled = !tieneResultados;
    }

    function actualizarPaginacion(actual, total) {
        paginacionContainer.innerHTML = '';
        if (total <= 1) return;
        let html = `<li class="page-item ${actual <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-pagina="${actual - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        html += `<li class="page-item active"><a class="page-link" href="#">${actual} / ${total}</a></li>`;
        html += `<li class="page-item ${actual >= total ? 'disabled' : ''}"><a class="page-link" href="#" data-pagina="${actual + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        paginacionContainer.innerHTML = html;
    }

    function actualizarURL(pagina, nombre, tipo) {
        const url = new URL(window.location);
        url.searchParams.set('pagina', pagina);
        url.searchParams.set('filtro_nombre', nombre);
        url.searchParams.set('tipo_entidad', tipo);
        window.history.pushState({}, '', url);
    }

    function inicializarListenersBotones() {
        if (typeof inicializarModalEdicionEntidad === 'function') {
            inicializarModalEdicionEntidad();
        }

        document.querySelectorAll('.btn-eliminar').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.dataset.id;
                const nombre = this.dataset.nombre;
                Swal.fire({
                    title: '¿Confirmar Eliminación?', html: `¿Seguro que deseas eliminar <strong>${nombre}</strong> (ID: ${id})?`, icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#e74c3c', cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, ¡Eliminar!', cancelButtonText: 'Cancelar'
                }).then((result) => { if (result.isConfirmed) { eliminarRegistro(this.dataset.id, this.dataset.tipo); } });
            });
        });

        document.querySelectorAll('.btn-ver-detalles').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const tipo = this.dataset.tipo;
                const modalBody = document.getElementById('modalVerDetallesBody');
                modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div><p>Cargando...</p></div>';
                modalDetalles.show();
                fetch(`ajax_detalle_entidad.php?id=${id}&tipo=${tipo}`).then(r => r.text()).then(h => modalBody.innerHTML = h);
            });
        });
    }

    function eliminarRegistro(id, tipo) {
        const formData = new FormData();
        formData.append('id_registro', id);
        formData.append('tipo_registro', tipo);
        formData.append('csrf_token', initialData.csrf_token);
        
        fetch('eliminar_entidad.php', { method: 'POST', body: formData }).then(r => r.json()).then(data => {
            Swal.fire({ title: data.success ? '¡Éxito!' : 'Error', text: data.message, icon: data.success ? 'success' : 'error' });
            if (data.success) { 
                const currentPage = parseInt(document.querySelector('.page-item.active .page-link')?.textContent.split(' ')[0] || '1', 10);
                fetchEntidades(currentPage);
            }
        });
    }
    
    btnGenerarReporte.addEventListener('click', () => {
        if (btnGenerarReporte.disabled) return;
        let texto = "<ul>";
        let hayFiltros = false;
        const tipoSeleccionado = filtroTipo.options[filtroTipo.selectedIndex].text;
        texto += `<li>Tipo de Entidad: <strong>${tipoSeleccionado}</strong></li>`;
        if (filtroNombre.value) {
            texto += `<li>Nombre contiene: <strong>${filtroNombre.value}</strong></li>`;
            hayFiltros = true;
        }
        if (filtroTipo.value === 'todas' && !hayFiltros) {
            texto = "<ul><li><strong>Se incluirán TODAS las entidades sin filtros.</strong></li></ul>";
        } else if (!hayFiltros) {
            texto += "<li><strong>Se incluirán TODOS los registros para este tipo.</strong></li>";
        }
        texto += "</ul>";
        confirmarReporteTexto.innerHTML = texto;
        modalConfirmarReporte.show();
    });

    btnConfirmarGeneracion.addEventListener('click', () => {
        const params = new URLSearchParams({
            tipo_entidad: filtroTipo.value,
            filtro_nombre: filtroNombre.value
        });
        const urlReporte = `reportes_entidades.php?${params.toString()}`;
        window.location.href = urlReporte;
        modalConfirmarReporte.hide();
    });
    
    filtroNombre.addEventListener('input', () => { clearTimeout(debounceTimer); debounceTimer = setTimeout(() => fetchEntidades(1), 350); });
    filtroTipo.addEventListener('change', () => fetchEntidades(1));
    btnLimpiar.addEventListener('click', () => { filtroNombre.value = ''; filtroTipo.value = 'todas'; fetchEntidades(1); });

    paginacionContainer.addEventListener('click', (e) => {
        e.preventDefault();
        const link = e.target.closest('a');
        if (link && !link.closest('.page-item').classList.contains('disabled') && !link.closest('.page-item').classList.contains('active')) {
            fetchEntidades(link.dataset.pagina);
        }
    });
    
    actualizarPaginacion(initialData.pagina_actual, initialData.total_paginas);
    inicializarListenersBotones();
});