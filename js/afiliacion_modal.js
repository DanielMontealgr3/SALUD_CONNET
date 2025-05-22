document.addEventListener('DOMContentLoaded', function () {
    const modalAfiliacionElement = document.getElementById('modalAfiliacionUsuario');
    if (!modalAfiliacionElement) {
        console.error("El elemento del modal 'modalAfiliacionUsuario' no fue encontrado.");
        return;
    }
    const modalAfiliacion = new bootstrap.Modal(modalAfiliacionElement);
    
    const formAfiliacionModal = document.getElementById('formAfiliacionUsuarioModal');
    const tipoEntidadSelect = document.getElementById('tipo_entidad_afiliacion_modal');
    const contenedorEpsSelect = document.getElementById('contenedor_select_entidad_eps_modal');
    const selectEps = document.getElementById('entidad_especifica_eps_modal');
    const contenedorArlSelect = document.getElementById('contenedor_select_entidad_arl_modal');
    const selectArl = document.getElementById('entidad_especifica_arl_modal');
    const docAfiliadoModalHidden = document.getElementById('doc_afiliado_modal_hidden');
    const idTipoDocModalHidden = document.getElementById('id_tipo_doc_modal_hidden');
    const docAfiliadoModalDisplay = document.getElementById('doc_afiliado_modal_display');
    const modalMessageDiv = document.getElementById('modalAfiliacionMessage');
    const btnGuardarAfiliacionModal = document.getElementById('btnGuardarAfiliacionModal');
    const modalGlobalErrorDiv = document.getElementById('modalAfiliacionGlobalError');

    if (!formAfiliacionModal || !tipoEntidadSelect || !contenedorEpsSelect || !selectEps || 
        !contenedorArlSelect || !selectArl || !docAfiliadoModalHidden || !idTipoDocModalHidden ||
        !docAfiliadoModalDisplay || !modalMessageDiv || !btnGuardarAfiliacionModal || !modalGlobalErrorDiv) {
        console.error("Uno o más elementos del formulario del modal de afiliación no fueron encontrados. Verifica los IDs.");
        if(modalGlobalErrorDiv) modalGlobalErrorDiv.innerHTML = "<div class='alert alert-danger'>Error interno al cargar el formulario. Contacte a soporte.</div>";
        return;
    }

    function resetModalForm() {
        if(formAfiliacionModal) formAfiliacionModal.reset();
        if(contenedorEpsSelect) contenedorEpsSelect.style.display = 'none';
        if(contenedorArlSelect) contenedorArlSelect.style.display = 'none';
        if(selectEps) {
            selectEps.innerHTML = '<option value="">Seleccione EPS...</option>';
            selectEps.required = false;
        }
        if(selectArl) {
            selectArl.innerHTML = '<option value="">Seleccione ARL...</option>';
            selectArl.required = false;
        }
        if(docAfiliadoModalDisplay) docAfiliadoModalDisplay.value = '';
        if(docAfiliadoModalHidden) docAfiliadoModalHidden.value = '';
        if(idTipoDocModalHidden) idTipoDocModalHidden.value = '';
        if(modalMessageDiv) modalMessageDiv.innerHTML = '';
        if(modalGlobalErrorDiv) modalGlobalErrorDiv.innerHTML = '';
        
        clearValidationErrorsModal();
        const estadoModalSelect = document.getElementById('id_estado_modal');
        if(estadoModalSelect) estadoModalSelect.value = '1'; 
        if(tipoEntidadSelect) tipoEntidadSelect.value = '';
    }

    function clearValidationErrorsModal() {
        if (!formAfiliacionModal) return;
        const errorMessages = formAfiliacionModal.querySelectorAll('.invalid-feedback');
        errorMessages.forEach(msg => { msg.textContent = ''; });
        const formControls = formAfiliacionModal.querySelectorAll('.form-control, .form-select');
        formControls.forEach(control => { control.classList.remove('is-invalid'); });
    }

    function showValidationErrorModal(elementId, message) {
        const element = document.getElementById(elementId);
        const errorDiv = document.getElementById('error-' + elementId);
        
        if (element) {
             element.classList.add('is-invalid');
        } else { console.warn(`Elemento con ID '${elementId}' no encontrado.`); }

        if (errorDiv) {
            errorDiv.textContent = message;
        } else { console.warn(`Div de error 'error-${elementId}' no encontrado.`); }
    }
    
    window.abrirModalAfiliacion = function (docUsuario, idTipoDoc) {
        resetModalForm();
        if(docAfiliadoModalDisplay) docAfiliadoModalDisplay.value = docUsuario;
        if(docAfiliadoModalHidden) docAfiliadoModalHidden.value = docUsuario;
        if(idTipoDocModalHidden) idTipoDocModalHidden.value = idTipoDoc;
        modalAfiliacion.show();
    };
    
    tipoEntidadSelect.addEventListener('change', function () {
        const tipo = this.value;
        
        if(contenedorEpsSelect) contenedorEpsSelect.style.display = 'none';
        if(selectEps) {
            selectEps.innerHTML = '<option value="">Seleccione EPS...</option>';
            selectEps.required = false;
        }
        if(contenedorArlSelect) contenedorArlSelect.style.display = 'none';
        if(selectArl) {
            selectArl.innerHTML = '<option value="">Seleccione ARL...</option>';
            selectArl.required = false;
        }
        if(modalMessageDiv) modalMessageDiv.innerHTML = '';
        clearValidationErrorsModal(); 

        if (tipo === 'eps') {
            if(contenedorEpsSelect) contenedorEpsSelect.style.display = 'block';
            if(selectEps) {
                selectEps.required = true;
                selectEps.innerHTML = '<option value="">Cargando EPS...</option>';
            }
            fetch('../ajax/get_eps.php') 
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    return response.json();
                })
                .then(data => {
                    if(selectEps) selectEps.innerHTML = '<option value="">Seleccione EPS...</option>';
                    if (data.success && data.data && data.data.length > 0) {
                        data.data.forEach(eps => {
                            const option = document.createElement('option');
                            option.value = eps.nit_eps;
                            option.textContent = `${eps.nombre_eps} (NIT: ${eps.nit_eps})`;
                            if(selectEps) selectEps.appendChild(option);
                        });
                    } else {
                        if(selectEps) selectEps.innerHTML = `<option value="">${data.message || 'No se encontraron EPS'}</option>`;
                        if(modalMessageDiv && !data.success) modalMessageDiv.innerHTML = `<div class="alert alert-warning">${data.message || 'No se pudieron cargar las EPS.'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching EPS:', error);
                    if(selectEps) selectEps.innerHTML = '<option value="">Error al cargar EPS</option>';
                    if(modalMessageDiv) modalMessageDiv.innerHTML = `<div class="alert alert-danger">Error cargando EPS: ${error.message}.</div>`;
                });
        } else if (tipo === 'arl') {
            if(contenedorArlSelect) contenedorArlSelect.style.display = 'block';
            if(selectArl) {
                selectArl.required = true;
                selectArl.innerHTML = '<option value="">Cargando ARL...</option>';
            }
            fetch('../ajax/get_arl.php') 
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    return response.json();
                })
                .then(data => {
                    if(selectArl) selectArl.innerHTML = '<option value="">Seleccione ARL...</option>';
                    if (data.success && data.data && data.data.length > 0) {
                        data.data.forEach(arl_item => {
                            const option = document.createElement('option');
                            option.value = arl_item.id_arl;
                            option.textContent = arl_item.nom_arl;
                           if(selectArl) selectArl.appendChild(option);
                        });
                    } else {
                        if(selectArl) selectArl.innerHTML = `<option value="">${data.message || 'No se encontraron ARL'}</option>`;
                        if(modalMessageDiv && !data.success) modalMessageDiv.innerHTML = `<div class="alert alert-warning">${data.message || 'No se pudieron cargar las ARL.'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching ARL:', error);
                    if(selectArl) selectArl.innerHTML = '<option value="">Error al cargar ARL</option>';
                     if(modalMessageDiv) modalMessageDiv.innerHTML = `<div class="alert alert-danger">Error cargando ARL: ${error.message}.</div>`;
                });
        }
    });

    formAfiliacionModal.addEventListener('submit', function (event) {
        event.preventDefault();
        event.stopPropagation();
        clearValidationErrorsModal();
        if(modalMessageDiv) modalMessageDiv.innerHTML = '';
        let isValid = true;

        if (!docAfiliadoModalHidden.value.trim()) { isValid = false; }
        if (!tipoEntidadSelect.value) {
            showValidationErrorModal('tipo_entidad_afiliacion_modal', 'Seleccione un tipo de entidad.');
            isValid = false;
        } else {
            if (tipoEntidadSelect.value === 'eps' && !selectEps.value) {
                showValidationErrorModal('entidad_especifica_eps_modal', 'Seleccione una EPS.');
                isValid = false;
            }
            if (tipoEntidadSelect.value === 'arl' && !selectArl.value) {
                showValidationErrorModal('entidad_especifica_arl_modal', 'Seleccione una ARL.');
                isValid = false;
            }
        }
        const regimenModal = document.getElementById('id_regimen_modal');
        const estadoModal = document.getElementById('id_estado_modal');

        if (regimenModal && !regimenModal.value) {
            showValidationErrorModal('id_regimen_modal', 'Seleccione un régimen.');
            isValid = false;
        }
        if (estadoModal && !estadoModal.value) {
            showValidationErrorModal('id_estado_modal', 'Seleccione un estado.');
            isValid = false;
        }

        if (isValid) {
            if(btnGuardarAfiliacionModal) {
                btnGuardarAfiliacionModal.disabled = true;
                btnGuardarAfiliacionModal.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
            }
            
            const formData = new FormData(formAfiliacionModal);
            formData.append('guardar_afiliacion_modal_submit', '1');

            fetch(window.location.pathname, { 
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if(modalMessageDiv) modalMessageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    setTimeout(() => {
                        modalAfiliacion.hide(); 
                    }, 1500);
                } else {
                    if(modalMessageDiv) modalMessageDiv.innerHTML = `<div class="alert alert-danger">${data.message || 'Error desconocido al guardar.'}</div>`;
                }
            })
            .catch(error => {
                console.error('Error en submit del modal:', error);
                if(modalMessageDiv) modalMessageDiv.innerHTML = `<div class="alert alert-danger">Error de comunicación con el servidor al guardar.</div>`;
            })
            .finally(() => {
                 if(btnGuardarAfiliacionModal){
                    btnGuardarAfiliacionModal.disabled = false;
                    btnGuardarAfiliacionModal.innerHTML = 'Guardar Afiliación';
                 }
            });
        }
    });
});