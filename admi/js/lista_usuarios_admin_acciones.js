function inicializarAccionesListaUsuariosAdmin(config) {
    const modalConfirmAccion = document.getElementById('modalConfirmacionAccion');
    const tituloConfirmAccion = document.getElementById('tituloConfirmacionAccion');
    const msgModalConfirmAccion = document.getElementById('mensajeModalConfirmacionAccion');
    const btnConfirmarAccion = document.getElementById('btnConfirmarAccionModal');
    const btnCancelarAccion = document.getElementById('btnCancelarAccionModal');

    const modalAlerta = document.getElementById('modalAlertaSimple');
    const tituloAlertaSimple = document.getElementById('tituloAlertaSimple');
    const msgAlertaSimple = document.getElementById('mensajeAlertaSimple');
    const btnAceptarAlertaSimple = document.getElementById('btnAceptarAlertaSimple');

    let datosAccionActual = null;

    document.querySelectorAll('.btn-cambiar-estado').forEach(boton => {
        boton.addEventListener('click', function() {
            datosAccionActual = {
                docUsu: this.dataset.docUsu,
                nomUsu: this.dataset.nomUsu,
                correoUsu: this.dataset.correoUsu,
                accion: this.dataset.accion,
                tipoOperacion: 'cambiarEstado'
            };

            const accionTexto = datosAccionActual.accion === 'activar' ? 'Activar' : 'Inactivar';
            tituloConfirmAccion.textContent = `${accionTexto} Usuario`;
            msgModalConfirmAccion.textContent = `¿Está seguro que desea ${datosAccionActual.accion.toLowerCase()} al usuario ${datosAccionActual.nomUsu} (Doc: ${datosAccionActual.docUsu})?`;
            btnConfirmarAccion.className = datosAccionActual.accion === 'activar' ? 'btn btn-success' : 'btn btn-warning';
            btnConfirmarAccion.textContent = accionTexto;
            modalConfirmAccion.style.display = 'flex';
        });
    });

    document.querySelectorAll('.btn-eliminar').forEach(boton => {
        boton.addEventListener('click', function() {
            const id = this.dataset.id;
            const tipoDoc = this.dataset.tipodoc;
            const nombre = this.dataset.nombre;
            const tipoRegistro = this.dataset.tipo;
            const asignadoFarmacia = this.dataset.asignadoFarmacia;
            const estadoAfiliacion = this.dataset.estadoAfiliacion;
            
            let mensajeBloqueo = "";
            if (config.rolUsuarioActual === 'farmaceuta') {
                if (asignadoFarmacia === '1') {
                    mensajeBloqueo = `El farmaceuta ${nombre} (Doc: ${id}) está asignado a una farmacia activa. Debe desasignarlo primero.`;
                }
            }
            
            if (mensajeBloqueo) {
                tituloAlertaSimple.textContent = 'Eliminación no permitida';
                msgAlertaSimple.textContent = mensajeBloqueo;
                modalAlerta.style.display = 'flex';
                return;
            }

            datosAccionActual = {
                idRegistro: id,
                tipoDoc: tipoDoc,
                nombreRegistro: nombre,
                tipoRegistro: tipoRegistro,
                tipoOperacion: 'eliminar'
            };
            
            tituloConfirmAccion.textContent = 'Confirmar Eliminación';
            msgModalConfirmAccion.innerHTML = `¿Está seguro que desea eliminar al usuario <strong>${nombre}</strong> (Doc: ${id})? Esta acción no se puede deshacer.`;
            btnConfirmarAccion.className = 'btn btn-danger';
            btnConfirmarAccion.textContent = 'Eliminar';
            modalConfirmAccion.style.display = 'flex';
        });
    });

    btnConfirmarAccion.addEventListener('click', function() {
        if (!datosAccionActual) return;

        const formData = new FormData();
        formData.append('csrf_token', config.csrfToken);

        let urlAccion = '';

        if (datosAccionActual.tipoOperacion === 'cambiarEstado') {
            formData.append('doc_usu', datosAccionActual.docUsu);
            formData.append('accion', datosAccionActual.accion);
            formData.append('correo_usu', datosAccionActual.correoUsu);
            urlAccion = config.urlCambiarEstado;

            fetch(urlAccion, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                modalConfirmAccion.style.display = 'none';
                if (data.success) {
                    if (data.redirect_to_email) { 
                        window.location.href = config.urlCorreoActivacion; 
                    } else {
                        window.location.href = window.location.pathname + window.location.search + 
                                               (window.location.search ? '&' : '?') + 
                                               'msg_estado=' + encodeURIComponent(data.message) + 
                                               '&tipo_msg_estado=success';
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                modalConfirmAccion.style.display = 'none';
                console.error('Error:', error);
                alert('Ocurrió un error al procesar la solicitud.');
            });

        } else if (datosAccionActual.tipoOperacion === 'eliminar') {
            formData.append('id_registro', datosAccionActual.idRegistro);
            formData.append('tipo_registro', datosAccionActual.tipoRegistro);
            if(datosAccionActual.tipoDoc) formData.append('id_tipo_doc', datosAccionActual.tipoDoc);
            urlAccion = config.urlEliminar;
            
            const formParaEnviar = document.createElement('form');
            formParaEnviar.method = 'POST';
            formParaEnviar.action = urlAccion;
            for (const pair of formData.entries()) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = pair[0];
                input.value = pair[1];
                formParaEnviar.appendChild(input);
            }
            document.body.appendChild(formParaEnviar);
            formParaEnviar.submit();
        }
        datosAccionActual = null;
    });

    btnCancelarAccion.addEventListener('click', () => {
        modalConfirmAccion.style.display = 'none';
        datosAccionActual = null;
    });

    btnAceptarAlertaSimple.addEventListener('click', () => {
        modalAlerta.style.display = 'none';
    });
    
    window.addEventListener('click', (event) => {
        if (event.target === modalConfirmAccion) {
            modalConfirmAccion.style.display = 'none';
            datosAccionActual = null;
        }
        if (event.target === modalAlerta) {
            modalAlerta.style.display = 'none';
        }
    });

    const modalEditUserContainer = document.getElementById('editarUsuarioModalPlaceholder');
    let currentEditModalInstance = null;
    document.querySelectorAll('.btn-editar-usuario').forEach(button => {
        button.addEventListener('click', function() {
            const docUsu = this.dataset.docUsu;
            if (!docUsu) return;

            modalEditUserContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center p-5" style="min-height:200px;"><div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"><span class="visually-hidden">Cargando...</span></div></div>';
            fetch(`${config.urlEditarModal}?doc_usu_editar=${encodeURIComponent(docUsu)}&csrf_token=${encodeURIComponent(config.csrfToken)}`)
                .then(response => {
                    if (!response.ok) throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
                    return response.text();
                })
                .then(html => {
                    modalEditUserContainer.innerHTML = html;
                    const editModalElement = document.getElementById('editUserModal');
                    if (editModalElement) {
                        if (currentEditModalInstance && typeof currentEditModalInstance.dispose === 'function') {
                            currentEditModalInstance.dispose();
                        }
                        currentEditModalInstance = new bootstrap.Modal(editModalElement);
                        editModalElement.addEventListener('shown.bs.modal', function onModalShown() {
                            if (typeof inicializarValidacionesEdicionAdmin === "function") {
                                inicializarValidacionesEdicionAdmin();
                            }
                            editModalElement.removeEventListener('shown.bs.modal', onModalShown);
                        });
                        editModalElement.addEventListener('hidden.bs.modal', function onModalHidden() {
                            if (currentEditModalInstance && typeof currentEditModalInstance.dispose === 'function') {
                                currentEditModalInstance.dispose();
                            }
                            currentEditModalInstance = null;
                            modalEditUserContainer.innerHTML = ''; 
                            editModalElement.removeEventListener('hidden.bs.modal', onModalHidden);
                        });
                        currentEditModalInstance.show();
                    } else {
                         modalEditUserContainer.innerHTML = '<div class="alert alert-danger m-3">Error: No se pudo encontrar el elemento #editUserModal en la respuesta del servidor.</div>';
                    }
                })
                .catch(error => {
                    modalEditUserContainer.innerHTML = `<div class="alert alert-danger m-3">No se pudo cargar el modal de edición: ${error.message}.</div>`;
                    console.error("Error al cargar modal de edición:", error);
                });
        });
    });
}