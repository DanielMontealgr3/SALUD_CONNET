document.addEventListener('DOMContentLoaded', function() {
    const filtros = {
        eps: document.getElementById('filtro_eps'),
        doc: document.getElementById('filtro_doc_paciente'),
        estado: document.getElementById('filtro_estado_paciente')
    };
    const btnLimpiar = document.getElementById('btn_limpiar_filtros');
    const tablaBody = document.getElementById('tabla_pacientes_body');
    const paginacionContainer = document.getElementById('paginacion_lista');
    const modalContainer = document.getElementById('modalContainer');
    let debounceTimer;

    function fetchPacientes(pagina = 1) {
        tablaBody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>';
        const params = new URLSearchParams({
            pagina: pagina,
            filtro_eps: filtros.eps.value,
            filtro_doc_paciente: filtros.doc.value.trim(),
            filtro_estado_paciente: filtros.estado.value
        });

        fetch(`ajax_get_pacientes.php?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                tablaBody.innerHTML = data.html_body;
                actualizarPaginacion(data.paginacion);
                inicializarListenersBotones();
            }).catch(error => {
                console.error('Error al cargar pacientes:', error);
                tablaBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error de comunicación. Intente de nuevo.</td></tr>';
            });
    }

    function actualizarPaginacion({ actual, total }) {
        paginacionContainer.innerHTML = '';
        if (total <= 1) return;
        let html = `<li class="page-item ${actual <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-pagina="${actual - 1}"><i class="bi bi-chevron-left"></i></a></li>`;
        for (let i = 1; i <= total; i++) {
            html += `<li class="page-item ${i === actual ? 'active' : ''}"><a class="page-link" href="#" data-pagina="${i}">${i}</a></li>`;
        }
        html += `<li class="page-item ${actual >= total ? 'disabled' : ''}"><a class="page-link" href="#" data-pagina="${actual + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        paginacionContainer.innerHTML = html;
    }

    function inicializarListenersBotones() {
        document.querySelectorAll('.btn-editar-usuario').forEach(button => {
            button.addEventListener('click', function() {
                const docUsu = this.dataset.docUsu;
                fetch(`../includes/modal_editar_usuario.php?doc_usu_editar=${docUsu}`)
                    .then(response => response.text())
                    .then(html => {
                        modalContainer.innerHTML = html;
                        const modalElement = document.getElementById('editUserModal');
                        new bootstrap.Modal(modalElement).show();
                    });
            });
        });

        document.querySelectorAll('.btn-cambiar-estado').forEach(button => {
            button.addEventListener('click', function() {
                const doc = this.dataset.docUsu;
                const nom = this.dataset.nomUsu;
                const accion = this.dataset.accion;
                const correo = this.dataset.correoUsu;
                
                let tituloSwal = `¿Confirmar ${accion === 'revertir' ? 'reversión' : (accion === 'activar' ? 'activación' : 'inactivación')}?`;

                Swal.fire({
                    title: tituloSwal,
                    html: `¿Seguro que deseas continuar con esta acción para el paciente <strong>${nom}</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, confirmar',
                    cancelButtonText: 'Cancelar'
                }).then(result => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('doc_usu', doc);
                        formData.append('accion', accion);
                        formData.append('correo_usu', correo);
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
                                                fetchPacientes(currentPage);
                                            });
                                        });
                                } else {
                                    Swal.fire(data.success ? '¡Éxito!' : 'Error', data.message, data.success ? 'success' : 'error')
                                        .then(() => {
                                            if(data.success) fetchPacientes(currentPage);
                                        });
                                }
                            });
                    }
                });
            });
        });

        document.querySelectorAll('.btn-eliminar-paciente').forEach(button => {
            button.addEventListener('click', function() {
                const doc = this.dataset.docUsu;
                const tipoDoc = this.dataset.idTipoDoc;
                const nom = this.dataset.nomUsu;
                Swal.fire({
                    title: '¿Confirmar Eliminación?',
                    html: `Esta acción marcará al paciente <strong>${nom}</strong> como eliminado y limpiará sus afiliaciones activas. Podrá revertir esta acción más tarde. ¿Continuar?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, ¡Eliminar!',
                    cancelButtonText: 'Cancelar'
                }).then(result => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('doc_usu', doc);
                        formData.append('id_tipo_doc', tipoDoc);
                        formData.append('csrf_token', csrfToken);
                        
                        fetch('../includes/eliminar_registro.php', { method: 'POST', body: formData })
                            .then(response => response.json())
                            .then(data => {
                                Swal.fire(data.success ? '¡Éxito!' : 'Error', data.message, data.success ? 'success' : 'error')
                                    .then(() => {
                                        if (data.success) fetchPacientes(currentPage);
                                    });
                            });
                    }
                });
            });
        });
    }

    Object.values(filtros).forEach(filtro => {
        const eventType = filtro.tagName === 'SELECT' ? 'change' : 'input';
        filtro.addEventListener(eventType, () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchPacientes(1), 400);
        });
    });

    btnLimpiar.addEventListener('click', () => {
        filtros.eps.value = '';
        filtros.doc.value = '';
        filtros.estado.value = '';
        fetchPacientes(1);
    });

    paginacionContainer.addEventListener('click', e => {
        e.preventDefault();
        const link = e.target.closest('a.page-link');
        if (link && !link.parentElement.classList.contains('disabled') && link.dataset.pagina) {
            fetchPacientes(parseInt(link.dataset.pagina));
        }
    });

    fetchPacientes(1);
});