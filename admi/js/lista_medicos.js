document.addEventListener('DOMContentLoaded', function () {
    const filtroIps = document.getElementById('filtro_ips');
    const filtroDoc = document.getElementById('filtro_doc_medico');
    const filtroEstado = document.getElementById('filtro_estado_usuario');
    const btnLimpiar = document.getElementById('btn_limpiar_filtros');
    const tablaBody = document.getElementById('tabla_medicos_body');
    const paginacionContainer = document.getElementById('paginacion_lista');
    const modalContainer = document.getElementById('modalContainer');
    const btnGenerarReporte = document.getElementById('btn_generar_reporte_medicos');
    const modalConfirmarReporte = new bootstrap.Modal(document.getElementById('modalConfirmarReporteMedicos'));
    const confirmarReporteTexto = document.getElementById('confirmarReporteTextoMedicos');
    const btnConfirmarGeneracion = document.getElementById('btnConfirmarGeneracionMedicos');

    let currentPage = 1;
    let debounceTimer;

    function cargarMedicos(pagina = 1) {
        currentPage = pagina;
        const url = new URL('ajax_get_medicos.php', window.location.href);
        url.searchParams.set('pagina', pagina);
        url.searchParams.set('filtro_ips', filtroIps.value);
        url.searchParams.set('filtro_doc_medico', filtroDoc.value);
        url.searchParams.set('filtro_estado_usuario', filtroEstado.value);
        
        tablaBody.innerHTML = '<tr><td colspan="7" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                tablaBody.innerHTML = data.html_body || '<tr><td colspan="7" class="text-center">No se encontraron datos.</td></tr>';
                renderizarPaginacion(data.paginacion);
                btnGenerarReporte.disabled = (data.paginacion.total_registros === 0);
                agregarEventListenersBotones();
            })
            .catch(error => {
                tablaBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger">Error al cargar los datos.</td></tr>`;
                btnGenerarReporte.disabled = true;
            });
    }

    function renderizarPaginacion(paginacion) {
        paginacionContainer.innerHTML = '';
        if (!paginacion || paginacion.total <= 1) return;

        const { actual, total } = paginacion;
        let html = `<li class="page-item ${actual <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${actual - 1}"><</a></li>`;
        html += `<li class="page-item active" style="pointer-events: none;"><span class="page-link">${actual} / ${total}</span></li>`;
        html += `<li class="page-item ${actual >= total ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${actual + 1}">></a></li>`;
        paginacionContainer.innerHTML = html;
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

        document.querySelectorAll('.btn-eliminar-medico').forEach(button => {
            button.addEventListener('click', function() {
                const docUsu = this.dataset.docUsu;
                const nomUsu = this.dataset.nomUsu;
                const tipoDoc = this.dataset.idTipoDoc;

                Swal.fire({
                    title: '¿Confirmar Eliminación?',
                    html: `Esta acción marcará al médico <strong>${nomUsu}</strong> como eliminado y sus asignaciones activas a IPS serán inactivadas. Podrá revertir esta acción más tarde.`,
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
                                    .then(() => data.success && cargarMedicos(currentPage));
                            });
                    }
                });
            });
        });
        
        document.querySelectorAll('.btn-cambiar-estado').forEach(button => {
            button.addEventListener('click', function() {
                const docUsu = this.dataset.docUsu;
                const nomUsu = this.dataset.nomUsu;
                const accion = this.dataset.accion;
                const correoUsu = this.closest('tr').querySelector('td:nth-child(3) span')?.title || '';

                Swal.fire({
                    title: `¿Confirmar ${accion === 'revertir' ? 'reversión' : (accion === 'activar' ? 'activación' : 'inactivación')}?`,
                    html: `¿Seguro que deseas continuar para el médico <strong>${nomUsu}</strong>?`,
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
                                    didOpen: () => { Swal.showLoading(); }
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
                                            cargarMedicos(currentPage);
                                        });
                                    });
                            } else {
                                Swal.fire(data.success ? '¡Éxito!' : 'Error', data.message, data.success ? 'success' : 'error')
                                    .then(() => data.success && cargarMedicos(currentPage));
                            }
                        });
                    }
                });
            });
        });
    }

    const aplicarFiltros = () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => cargarMedicos(1), 300);
    };

    [filtroIps, filtroDoc, filtroEstado].forEach(el => {
        const event = el.tagName === 'SELECT' ? 'change' : 'input';
        el.addEventListener(event, aplicarFiltros);
    });

    btnLimpiar.addEventListener('click', function () {
        filtroIps.value = '';
        filtroDoc.value = '';
        filtroEstado.value = '';
        cargarMedicos(1);
    });
    
    paginacionContainer.addEventListener('click', function (e) {
        e.preventDefault();
        const link = e.target.closest('a.page-link');
        if (link && link.dataset.page && !link.parentElement.classList.contains('disabled') && !link.parentElement.classList.contains('active')) {
            cargarMedicos(parseInt(link.dataset.page, 10));
        }
    });

    btnGenerarReporte.addEventListener('click', () => {
        if (btnGenerarReporte.disabled) return;
        let texto = "<ul>";
        
        if (filtroIps.value) {
            texto += `<li>Asignación IPS: <strong>${filtroIps.options[filtroIps.selectedIndex].text}</strong></li>`;
        }
        if (filtroDoc.value) {
            texto += `<li>Documento: <strong>${filtroDoc.value}</strong></li>`;
        }
        if (filtroEstado.value) {
            texto += `<li>Estado: <strong>${filtroEstado.options[filtroEstado.selectedIndex].text}</strong></li>`;
        }

        if (texto === "<ul>") {
            texto += "<li>Se incluirán <strong>TODOS</strong> los médicos (excepto eliminados).</li>";
        }
        texto += "</ul>";
        confirmarReporteTexto.innerHTML = texto;
        modalConfirmarReporte.show();
    });

    btnConfirmarGeneracion.addEventListener('click', () => {
        const params = new URLSearchParams();
        params.append('filtro_ips', filtroIps.value);
        params.append('filtro_doc_medico', filtroDoc.value);
        params.append('filtro_estado_usuario', filtroEstado.value);

        window.location.href = `reporte_medicos.php?${params.toString()}`;
        modalConfirmarReporte.hide();
    });

    cargarMedicos(1);
});