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
    const responseModal = new bootstrap.Modal(document.getElementById('responseModal'));
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
    
    const showLoadingModal = (title, message) => {
        const loadingSpinner = `<div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>`;
        const modalIcon = document.getElementById('modalIcon');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        modalIcon.innerHTML = loadingSpinner;
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        responseModal.show();
    };

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
        html += `<li class="page-item active"><a class="page-link" href="#">${actual} / ${total}</a></li>`;
        html += `<li class="page-item ${actual >= total ? 'disabled' : ''}"><a class="page-link" href="#" data-pagina="${actual + 1}"><i class="bi bi-chevron-right"></i></a></li>`;
        paginacionContainer.innerHTML = html;
    }

    function inicializarListenersBotones() {
        // --- Lógica para el botón Editar (Corregida) ---
        document.querySelectorAll('.btn-editar-usuario').forEach(button => {
            button.addEventListener('click', function() {
                const docUsu = this.dataset.docUsu;
                modalContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center p-5"><div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div></div>';
                
                // Usamos la ruta correcta según la estructura de carpetas
                fetch(`../includes/modal_editar_usuario.php?doc_usu_editar=${docUsu}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error al cargar el modal.');
                        }
                        return response.text();
                    })
                    .then(html => {
                        modalContainer.innerHTML = html;
                        const modalElement = document.getElementById('editUserModal');
                        const modalInstance = new bootstrap.Modal(modalElement);
                        modalInstance.show();
                        
                        modalElement.addEventListener('hidden.bs.modal', () => {
                           modalContainer.innerHTML = ''; 
                        });
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        modalContainer.innerHTML = `<div class="alert alert-danger">No se pudo cargar el contenido para editar.</div>`;
                    });
            });
        });

        // --- Lógica para Cambiar Estado (Sin modificar) ---
        document.querySelectorAll('.btn-cambiar-estado').forEach(button => {
            button.addEventListener('click', function() {
                const doc = this.dataset.docUsu;
                const nom = this.dataset.nomUsu;
                const accion = this.dataset.accion;
                const correo = this.dataset.correoUsu;
                
                let tituloSwal = `¿Confirmar ${accion}?`;
                if(accion === 'revertir') tituloSwal = `¿Revertir Eliminación?`;
                else if (accion === 'activar') tituloSwal = `¿Confirmar Activación?`;
                else if (accion === 'inactivar') tituloSwal = `¿Confirmar Inactivación?`;

                Swal.fire({
                    title: tituloSwal,
                    html: `¿Seguro que deseas continuar con esta acción para el paciente <strong>${nom}</strong>?`,
                    icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, confirmar', cancelButtonText: 'Cancelar'
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
                                if (data.send_email) {
                                    showLoadingModal('Enviando correo...', 'Por favor, espere un momento.');
                                    const emailData = new FormData();
                                    emailData.append('correo', data.email_data.correo);
                                    emailData.append('nombre', data.email_data.nombre);
                                    emailData.append('documento', data.email_data.documento);
                                    fetch('../includes/correo_activacion.php', { method: 'POST', body: emailData })
                                        .then(res => res.json())
                                        .then(emailRes => {
                                            showResponseModal(emailRes.success ? 'success' : 'error', emailRes.success ? 'Éxito' : 'Error', emailRes.message);
                                            fetchPacientes();
                                        });
                                } else {
                                    showResponseModal(data.success ? 'success' : 'error', data.success ? 'Éxito' : 'Error', data.message);
                                    if (data.success) fetchPacientes();
                                }
                            });
                    }
                });
            });
        });

        // --- Lógica para Eliminar (Sin modificar) ---
        document.querySelectorAll('.btn-eliminar-paciente').forEach(button => {
            button.addEventListener('click', function() {
                const doc = this.dataset.docUsu;
                const tipoDoc = this.dataset.idTipoDoc;
                const nom = this.dataset.nomUsu;
                Swal.fire({
                    title: '¿Confirmar Eliminación?',
                    html: `Esta acción marcará al paciente <strong>${nom}</strong> como eliminado. Podrá revertir esta acción más tarde. ¿Continuar?`,
                    icon: 'warning', showCancelButton: true, confirmButtonColor: '#e74c3c', confirmButtonText: 'Sí, ¡Eliminar!', cancelButtonText: 'Cancelar'
                }).then(result => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('doc_usu', doc);
                        formData.append('id_tipo_doc', tipoDoc);
                        formData.append('csrf_token', csrfToken);
                        fetch('../includes/eliminar_registro.php', { method: 'POST', body: formData })
                            .then(response => response.json())
                            .then(data => {
                                showResponseModal(data.success ? 'success' : 'error', data.success ? 'Éxito' : 'Error', data.message);
                                if (data.success) fetchPacientes();
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
        if (link && !link.parentElement.classList.contains('disabled')) {
            fetchPacientes(link.dataset.pagina);
        }
    });

    fetchPacientes();
});