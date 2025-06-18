document.addEventListener('DOMContentLoaded', function () {
    const filtroFarmacia = document.getElementById('filtro_farmacia');
    const filtroDoc = document.getElementById('filtro_doc_farmaceuta');
    const filtroEstado = document.getElementById('filtro_estado_usuario');
    const btnLimpiar = document.getElementById('btn_limpiar_filtros');
    const tablaBody = document.getElementById('tabla_farmaceutas_body');
    const paginacionContainer = document.getElementById('paginacion_lista');
    const modalContainer = document.getElementById('modalContainer');

    let currentPage = 1;
    let debounceTimer;

    function cargarFarmaceutas(pagina = 1) {
        currentPage = pagina;
        const url = new URL('ajax_get_farmaceutas.php', window.location.href);
        url.searchParams.set('pagina', pagina);
        url.searchParams.set('filtro_farmacia', filtroFarmacia.value);
        url.searchParams.set('filtro_doc_farmaceuta', filtroDoc.value);
        url.searchParams.set('filtro_estado_usuario', filtroEstado.value);
        
        tablaBody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                tablaBody.innerHTML = data.html_body || '<tr><td colspan="6" class="text-center">No se encontraron datos.</td></tr>';
                renderizarPaginacion(data.paginacion);
                agregarEventListenersBotones();
            })
            .catch(error => {
                tablaBody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error al cargar los datos.</td></tr>`;
            });
    }

    function renderizarPaginacion(paginacion) {
        paginacionContainer.innerHTML = '';
        if (!paginacion || paginacion.total <= 1) return;

        const { actual, total } = paginacion;
        const crearItem = (texto, page, activo = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${activo ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.dataset.page = page;
            a.innerHTML = texto;
            li.appendChild(a);
            return li;
        };

        paginacionContainer.appendChild(crearItem('«', actual - 1, false, actual === 1));
        for (let i = 1; i <= total; i++) {
            paginacionContainer.appendChild(crearItem(i, i, i === actual));
        }
        paginacionContainer.appendChild(crearItem('»', actual + 1, false, actual === total));

        paginacionContainer.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const pageNum = this.dataset.page;
                if (pageNum) cargarFarmaceutas(parseInt(pageNum, 10));
            });
        });
    }

    function agregarEventListenersBotones() {
        document.querySelectorAll('.btn-editar-usuario').forEach(button => {
            button.addEventListener('click', function() {
                const docUsu = this.dataset.docUsu;
                fetch(`../includes/modal_editar_usuario.php?doc_usu_editar=${docUsu}`)
                    .then(response => response.text())
                    .then(html => {
                        modalContainer.innerHTML = html;
                        const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                        modal.show();
                    });
            });
        });

        // --- INICIO DE LA MODIFICACIÓN ---
        document.querySelectorAll('.btn-eliminar-farmaceuta').forEach(button => {
            button.addEventListener('click', function() {
                const docUsu = this.dataset.docUsu;
                const nomUsu = this.dataset.nomUsu;
                const tipoDoc = this.dataset.idTipoDoc;

                Swal.fire({
                    title: '¿Confirmar Eliminación?',
                    html: `Esta acción marcará al farmaceuta <strong>${nomUsu}</strong> como eliminado y sus asignaciones activas serán inactivadas. Podrá revertir esta acción más tarde.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, ¡Eliminar!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('doc_usu', docUsu);
                        formData.append('id_tipo_doc', tipoDoc);
                        formData.append('csrf_token', csrfToken);
                        
                        fetch('../includes/eliminar_registro.php', { method: 'POST', body: formData })
                            .then(response => response.json())
                            .then(data => {
                                Swal.fire(data.success ? '¡Éxito!' : 'Error', data.message, data.success ? 'success' : 'error')
                                    .then(() => data.success && cargarFarmaceutas(currentPage));
                            });
                    }
                });
            });
        });
        // --- FIN DE LA MODIFICACIÓN ---
        
        document.querySelectorAll('.btn-cambiar-estado').forEach(button => {
            button.addEventListener('click', function() {
                const docUsu = this.dataset.docUsu;
                const nomUsu = this.dataset.nomUsu;
                const accion = this.dataset.accion;
                const correoUsu = this.closest('tr').querySelector('td:nth-child(3) span')?.title || '';

                Swal.fire({
                    title: `¿Confirmar ${accion === 'revertir' ? 'reversión' : (accion === 'activar' ? 'activación' : 'inactivación')}?`,
                    html: `¿Seguro que deseas continuar para el farmaceuta <strong>${nomUsu}</strong>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, confirmar'
                }).then(result => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('doc_usu', docUsu);
                        formData.append('accion', accion);
                        formData.append('correo_usu', correoUsu);
                        formData.append('csrf_token', csrfToken);

                        fetch('../ajax/cambiar_estado_usuario.php', { method: 'POST', body: formData })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.send_email) {
                                Swal.fire({
                                    title: 'Enviando correo...',
                                    text: 'Por favor, espere un momento.',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });

                                const emailData = new FormData();
                                emailData.append('correo', data.email_data.correo);
                                emailData.append('nombre', data.email_data.nombre);
                                emailData.append('documento', data.email_data.documento);
                                
                                fetch('../includes/correo_activacion.php', { method: 'POST', body: emailData })
                                    .then(res => res.json())
                                    .then(emailRes => {
                                        Swal.fire({
                                            title: emailRes.success ? '¡Éxito!' : 'Error',
                                            text: emailRes.message,
                                            icon: emailRes.success ? 'success' : 'error'
                                        }).then(() => {
                                            cargarFarmaceutas(currentPage);
                                        });
                                    });
                            } else {
                                Swal.fire(data.success ? '¡Éxito!' : 'Error', data.message, data.success ? 'success' : 'error')
                                    .then(() => data.success && cargarFarmaceutas(currentPage));
                            }
                        });
                    }
                });
            });
        });
    }

    const aplicarFiltros = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => cargarFarmaceutas(1), 300);
    };

    [filtroFarmacia, filtroDoc, filtroEstado].forEach(el => {
        const event = el.tagName === 'SELECT' ? 'change' : 'input';
        el.addEventListener(event, aplicarFiltros);
    });

    btnLimpiar.addEventListener('click', function () {
        filtroFarmacia.value = '';
        filtroDoc.value = '';
        filtroEstado.value = '';
        cargarFarmaceutas(1);
    });

    cargarFarmaceutas(1);
});