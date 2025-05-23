document.addEventListener('DOMContentLoaded', function () {
    const modalAfiliacionElement = document.getElementById('modalAfiliacionUsuario');
    if (!modalAfiliacionElement) {
        console.error("El elemento del modal 'modalAfiliacionUsuario' no fue encontrado.");
        return;
    }
    let modalAfiliacionInstance = null;
    try {
        modalAfiliacionInstance = new bootstrap.Modal(modalAfiliacionElement);
    } catch (e) {
        console.error("Error inicializando el modal de Bootstrap:", e);
        const bodyElement = document.body;
        const errorDiv = document.createElement('div');
        errorDiv.className = 'container mt-3';
        errorDiv.innerHTML = '<div class="alert alert-danger">Error crítico al cargar componentes del modal.</div>';
        bodyElement.insertBefore(errorDiv, bodyElement.firstChild);
        return;
    }
    
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

    const elementosFaltantes = [
        {el: formAfiliacionModal, id: 'formAfiliacionUsuarioModal'}, {el: tipoEntidadSelect, id: 'tipo_entidad_afiliacion_modal'},
        {el: contenedorEpsSelect, id: 'contenedor_select_entidad_eps_modal'}, {el: selectEps, id: 'entidad_especifica_eps_modal'},
        {el: contenedorArlSelect, id: 'contenedor_select_entidad_arl_modal'}, {el: selectArl, id: 'entidad_especifica_arl_modal'},
        {el: docAfiliadoModalHidden, id: 'doc_afiliado_modal_hidden'}, {el: idTipoDocModalHidden, id: 'id_tipo_doc_modal_hidden'},
        {el: docAfiliadoModalDisplay, id: 'doc_afiliado_modal_display'}, {el: modalMessageDiv, id: 'modalAfiliacionMessage'},
        {el: btnGuardarAfiliacionModal, id: 'btnGuardarAfiliacionModal'}, {el: modalGlobalErrorDiv, id: 'modalAfiliacionGlobalError'}
    ].filter(item => !item.el);

    if (elementosFaltantes.length > 0) {
        const idsFaltantes = elementosFaltantes.map(item => item.id).join(', ');
        console.error(`Elementos del DOM no encontrados en el modal: ${idsFaltantes}.`);
        if(modalGlobalErrorDiv) modalGlobalErrorDiv.innerHTML = `<div class='alert alert-danger'>Error interno: componentes del formulario no encontrados (${idsFaltantes}). Contacte a soporte.</div>`;
        return;
    }

    function resetModalForm() {
        formAfiliacionModal.reset();
        contenedorEpsSelect.style.display = 'none';
        selectEps.innerHTML = '<option value="">Seleccione EPS...</option>';
        selectEps.removeAttribute('required');

        contenedorArlSelect.style.display = 'none';
        selectArl.innerHTML = '<option value="">Seleccione ARL...</option>';
        selectArl.removeAttribute('required');
        
        docAfiliadoModalDisplay.value = '';
        docAfiliadoModalHidden.value = '';
        idTipoDocModalHidden.value = '';
        modalMessageDiv.innerHTML = '';
        modalGlobalErrorDiv.innerHTML = '';
        
        clearValidationErrorsModal();
        const estadoModalSelect = document.getElementById('id_estado_modal');
        if (estadoModalSelect) estadoModalSelect.value = '1'; 
        tipoEntidadSelect.value = '';
    }

    function clearValidationErrorsModal() {
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
        } else { console.warn(`Elemento con ID '${elementId}' no encontrado para mostrar error.`); }

        if (errorDiv) {
            errorDiv.textContent = message;
        } else { console.warn(`Div de feedback 'error-${elementId}' no encontrado.`); }
    }
    
    window.abrirModalAfiliacion = function (docUsuario, idTipoDoc) {
        resetModalForm();
        docAfiliadoModalDisplay.value = docUsuario;
        docAfiliadoModalHidden.value = docUsuario;
        idTipoDocModalHidden.value = idTipoDoc;
        if (modalAfiliacionInstance) {
            modalAfiliacionInstance.show();
        } else {
            console.error("Instancia del modal no disponible para mostrar.");
            if(modalGlobalErrorDiv) modalGlobalErrorDiv.innerHTML = "<div class='alert alert-danger'>Error: No se puede mostrar el formulario de afiliación.</div>";
        }
    };
    
    tipoEntidadSelect.addEventListener('change', function () {
        const tipo = this.value;
        
        contenedorEpsSelect.style.display = 'none';
        selectEps.innerHTML = '<option value="">Seleccione EPS...</option>';
        selectEps.removeAttribute('required');

        contenedorArlSelect.style.display = 'none';
        selectArl.innerHTML = '<option value="">Seleccione ARL...</option>';
        selectArl.removeAttribute('required');

        modalMessageDiv.innerHTML = '';
        clearValidationErrorsModal();

        if (tipo === 'eps') {
            contenedorEpsSelect.style.display = 'block';
            selectEps.setAttribute('required', 'required');
            selectEps.innerHTML = '<option value="">Cargando EPS...</option>';
            
            fetch('../ajax/get_eps.php') 
                .then(response => {
                    if (!response.ok) throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
                    return response.json();
                })
                .then(data => {
                    selectEps.innerHTML = '<option value="">Seleccione EPS...</option>';
                    if (data.success && data.data && data.data.length > 0) {
                        data.data.forEach(eps => {
                            const option = document.createElement('option');
                            option.value = eps.id;
                            option.textContent = eps.nombre; 
                            selectEps.appendChild(option);
                        });
                    } else {
                        selectEps.innerHTML = `<option value="">${data.message || 'No se encontraron EPS'}</option>`;
                        if(modalMessageDiv && !data.success) modalMessageDiv.innerHTML = `<div class="alert alert-warning">${data.message || 'No se pudieron cargar las EPS.'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching EPS:', error);
                    selectEps.innerHTML = '<option value="">Error al cargar EPS</option>';
                    if(modalMessageDiv) modalMessageDiv.innerHTML = `<div class="alert alert-danger">Error al cargar la lista de EPS: ${error.message}.</div>`;
                });
        } else if (tipo === 'arl') {
            contenedorArlSelect.style.display = 'block';
            selectArl.setAttribute('required', 'required');
            selectArl.innerHTML = '<option value="">Cargando ARL...</option>';

            fetch('../ajax/get_arl.php') 
                .then(response => {
                    if (!response.ok) throw new Error(`Error HTTP ${response.status}: ${response.statusText}`);
                    return response.json();
                })
                .then(data => {
                    selectArl.innerHTML = '<option value="">Seleccione ARL...</option>';
                    if (data.success && data.data && data.data.length > 0) {
                        data.data.forEach(arl_item => {
                            const option = document.createElement('option');
                            option.value = arl_item.id; 
                            option.textContent = arl_item.nombre; 
                           selectArl.appendChild(option);
                        });
                    } else {
                        selectArl.innerHTML = `<option value="">${data.message || 'No se encontraron ARL'}</option>`;
                        if(modalMessageDiv && !data.success) modalMessageDiv.innerHTML = `<div class="alert alert-warning">${data.message || 'No se pudieron cargar las ARL.'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error fetching ARL:', error);
                    selectArl.innerHTML = '<option value="">Error al cargar ARL</option>';
                    if(modalMessageDiv) modalMessageDiv.innerHTML = `<div class="alert alert-danger">Error al cargar la lista de ARL: ${error.message}.</div>`;
                });
        }
    });

    formAfiliacionModal.addEventListener('submit', function (event) {
        event.preventDefault();
        event.stopPropagation();
        clearValidationErrorsModal();
        modalMessageDiv.innerHTML = '';
        let isValid = true;

        if (!docAfiliadoModalHidden.value.trim()) { 
            modalGlobalErrorDiv.innerHTML = "<div class='alert alert-danger'>Error: Documento del afiliado no especificado.</div>";
            isValid = false; 
        }
        if (!tipoEntidadSelect.value) {
            showValidationErrorModal('tipo_entidad_afiliacion_modal', 'Debe seleccionar un tipo de entidad.');
            isValid = false;
        } else {
            if (tipoEntidadSelect.value === 'eps' && !selectEps.value) {
                showValidationErrorModal('entidad_especifica_eps_modal', 'Debe seleccionar una EPS específica.');
                isValid = false;
            }
            if (tipoEntidadSelect.value === 'arl' && !selectArl.value) {
                showValidationErrorModal('entidad_especifica_arl_modal', 'Debe seleccionar una ARL específica.');
                isValid = false;
            }
        }
        const regimenModal = document.getElementById('id_regimen_modal');
        const estadoModal = document.getElementById('id_estado_modal');

        if (regimenModal && !regimenModal.value) {
            showValidationErrorModal('id_regimen_modal', 'Debe seleccionar un régimen.');
            isValid = false;
        }
        if (estadoModal && !estadoModal.value) {
            showValidationErrorModal('id_estado_modal', 'Debe seleccionar un estado de afiliación.');
            isValid = false;
        }

        if (isValid) {
            btnGuardarAfiliacionModal.disabled = true;
            btnGuardarAfiliacionModal.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
            
            const formData = new FormData(formAfiliacionModal);
            formData.append('guardar_afiliacion_modal_submit', '1');

            fetch(window.location.pathname + window.location.search, { 
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error HTTP ${response.status} al guardar: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    modalMessageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    setTimeout(() => {
                        if (modalAfiliacionInstance) modalAfiliacionInstance.hide(); 
                    }, 2000);
                } else {
                    modalMessageDiv.innerHTML = `<div class="alert alert-danger">${data.message || 'Ocurrió un error desconocido al guardar la afiliación.'}</div>`;
                }
            })
            .catch(error => {
                console.error('Error en submit del modal de afiliación:', error);
                modalMessageDiv.innerHTML = `<div class="alert alert-danger">Error de comunicación con el servidor: ${error.message}. Intente de nuevo.</div>`;
            })
            .finally(() => {
                 btnGuardarAfiliacionModal.disabled = false;
                 btnGuardarAfiliacionModal.innerHTML = '<i class="bi bi-check-circle me-1"></i>Guardar Afiliación';
            });
        } else {
             modalMessageDiv.innerHTML = `<div class="alert alert-warning">Por favor, corrija los campos marcados.</div>`;
        }
    });
});