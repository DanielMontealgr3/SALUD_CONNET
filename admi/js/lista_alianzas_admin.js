document.addEventListener('DOMContentLoaded', function() {
    const filtros = {
        tipo: document.getElementById('filtro_tipo'),
        estado: document.getElementById('filtro_estado'),
        ordenFecha: document.getElementById('filtro_orden_fecha'),
        nombreEps: document.getElementById('filtro_nombre_eps'),
        entidadAliada: document.getElementById('filtro_nombre_entidad_aliada')
    };
    const btnLimpiar = document.getElementById('btn_limpiar_filtros');
    const tablaBody = document.getElementById('tabla_alianzas_body');
    const paginacionContainer = document.getElementById('paginacion_lista');
    const responseModal = new bootstrap.Modal(document.getElementById('responseModal'));
    const modalVerAlianza = new bootstrap.Modal(document.getElementById('modalVerAlianza'));
    let debounceTimer;

    const showResponseModal = (status, title, message) => {
        const modalIcon = document.getElementById('modalIcon');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const successIcon = `<svg class="modal-icon-svg checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg>`;
        const errorIcon = `<svg class="modal-icon-svg crossmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="crossmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="crossmark__line" fill="none" d="M16 16 36 36 M36 16 16 36"/></svg>`;
        modalIcon.innerHTML = (status === 'success') ? successIcon : errorIcon;
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        responseModal.show();
    };

    function fetchAlianzas(pagina = 1) {
        let params = new URLSearchParams({
            pagina: pagina,
            filtro_tipo: filtros.tipo.value,
            filtro_estado: filtros.estado.value,
            filtro_orden_fecha: filtros.ordenFecha.value,
            filtro_nombre_eps: filtros.nombreEps.value.trim(),
            filtro_nombre_entidad_aliada: filtros.entidadAliada.value.trim()
        });
        fetch(`lista_alianzas.php?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(response => response.json())
            .then(data => {
                tablaBody.innerHTML = data.html_body;
                actualizarPaginacion(data.paginacion);
                inicializarListenersBotones();
            }).catch(error => {
                console.error('Error al cargar alianzas:', error);
                tablaBody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error de comunicación con el servidor.</td></tr>';
            });
    }

    function actualizarPaginacion({ actual, total }) {
        paginacionContainer.innerHTML = '';
        if (total <= 1) return;
        let html = `<li class="page-item ${actual <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-pagina="${actual - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        html += `<li class="page-item active"><a class="page-link" href="#">${actual} / ${total}</a></li>`;
        html += `<li class="page-item ${actual >= total ? 'disabled' : ''}"><a class="page-link" href="#" data-pagina="${actual + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        paginacionContainer.innerHTML = html;
    }

    function inicializarListenersBotones() {
        document.querySelectorAll('.btn-cambiar-estado, .btn-eliminar-alianza').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const tabla = this.dataset.tabla;
                const accion = this.dataset.accion || 'eliminar';
                const info = this.dataset.info || `la alianza (ID: ${id})`;
                const esEliminar = accion === 'eliminar';
                const titulo = esEliminar ? '¿Confirmar Eliminación?' : `¿Confirmar ${accion === 'activar' ? 'Activación' : 'Inactivación'}?`;
                const texto = esEliminar ? `¿Seguro que deseas eliminar ${info}?` : `¿Seguro que deseas ${accion} ${info}?`;

                Swal.fire({
                    title: titulo, html: texto, icon: 'warning', showCancelButton: true,
                    confirmButtonColor: esEliminar ? '#e74c3c' : (accion === 'activar' ? '#28a745' : '#ffc107'),
                    cancelButtonColor: '#6c757d', confirmButtonText: 'Sí, ¡Confirmar!', cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) { gestionarAlianza(id, tabla, accion); }
                });
            });
        });

        document.querySelectorAll('.btn-ver-detalles-alianza').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const tabla = this.dataset.tabla;
                const modalBody = document.getElementById('modalVerAlianzaBody');
                modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div><p>Cargando detalles...</p></div>';
                modalVerAlianza.show();
                fetch(`ajax_detalle_alianza.php?id=${id}&tabla=${tabla}`)
                    .then(response => response.text())
                    .then(html => modalBody.innerHTML = html)
                    .catch(error => {
                        modalBody.innerHTML = '<div class="alert alert-danger">Error al cargar los detalles.</div>';
                        console.error('Error:', error);
                    });
            });
        });
    }

    function gestionarAlianza(id, tabla, accion) {
        const formData = new FormData();
        formData.append('id_alianza', id);
        formData.append('tabla_origen', tabla);
        formData.append('accion', accion);
        formData.append('csrf_token', csrfTokenAlianzas);

        fetch('ajax_gestionar_alianza.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                showResponseModal(data.success ? 'success' : 'error', data.success ? '¡Éxito!' : 'Error', data.message);
                if (data.success) {
                    const currentPage = parseInt(paginacionContainer.querySelector('.page-item.active .page-link')?.textContent.split(' ')[0] || '1', 10);
                    fetchAlianzas(currentPage);
                }
            }).catch(error => {
                console.error('Error en la operación:', error);
                showResponseModal('error', 'Error de Conexión', 'No se pudo comunicar con el servidor.');
            });
    }
    
    // Carga inicial de paginación
    fetchAlianzas(new URLSearchParams(window.location.search).get('pagina') || 1);

    Object.values(filtros).forEach(filtro => {
        const eventType = (filtro.tagName === 'SELECT') ? 'change' : 'input';
        filtro.addEventListener(eventType, () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchAlianzas(1), 400);
        });
    });

    btnLimpiar.addEventListener('click', () => {
        filtros.tipo.value = 'todas';
        filtros.estado.value = '';
        filtros.ordenFecha.value = 'desc';
        filtros.nombreEps.value = '';
        filtros.entidadAliada.value = '';
        fetchAlianzas(1);
    });

    paginacionContainer.addEventListener('click', (e) => {
        e.preventDefault();
        const link = e.target.closest('a.page-link');
        if (link && !link.parentElement.classList.contains('disabled') && !link.parentElement.classList.contains('active')) {
            fetchAlianzas(link.dataset.pagina);
        }
    });
});