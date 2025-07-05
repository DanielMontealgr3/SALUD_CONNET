document.addEventListener('DOMContentLoaded', function () {
    const modalAfiliacionElement = document.getElementById('modalAfiliacionUsuario');
    if (!modalAfiliacionElement) return;

    const modalAfiliacionInstance = new bootstrap.Modal(modalAfiliacionElement);
    const responseModalInstance = new bootstrap.Modal(document.getElementById('responseModal'));
    
    const form = document.getElementById('formAfiliacionUsuarioModal');
    const tipoEntidadSelect = document.getElementById('tipo_entidad_afiliacion_modal');
    const selectEps = document.getElementById('entidad_especifica_eps_modal');
    const selectArl = document.getElementById('entidad_especifica_arl_modal');
    const selectRegimen = document.getElementById('id_regimen_modal');
    const selectEstado = document.getElementById('id_estado_modal');
    
    const contenedorEpsSelect = document.getElementById('contenedor_select_entidad_eps_modal');
    const contenedorArlSelect = document.getElementById('contenedor_select_entidad_arl_modal');
    
    const docAfiliadoModalHidden = document.getElementById('doc_afiliado_modal_hidden');
    const idTipoDocModalHidden = document.getElementById('id_tipo_doc_modal_hidden');
    const docAfiliadoModalDisplay = document.getElementById('doc_afiliado_modal_display');
    const btnGuardar = document.getElementById('btnGuardarAfiliacionModal');

    window.lastAffiliationSuccess = false;

    const setError = (el, message) => {
        el.classList.add('is-invalid');
        el.classList.remove('is-valid');
        const feedback = document.getElementById(`error-${el.id}`);
        if (feedback) feedback.textContent = message;
    };

    const setSuccess = (el) => {
        el.classList.remove('is-invalid');
        el.classList.add('is-valid');
        const feedback = document.getElementById(`error-${el.id}`);
        if (feedback) feedback.textContent = '';
    };

    const validateForm = () => {
        let isValid = true;
        
        if (!tipoEntidadSelect.value) {
            setError(tipoEntidadSelect, 'Debe seleccionar un tipo de entidad.');
            isValid = false;
        } else {
            setSuccess(tipoEntidadSelect);
            if (tipoEntidadSelect.value === 'eps' && !selectEps.value) {
                setError(selectEps, 'Debe seleccionar una EPS.');
                isValid = false;
            } else if (tipoEntidadSelect.value === 'eps') {
                setSuccess(selectEps);
            }
            if (tipoEntidadSelect.value === 'arl' && !selectArl.value) {
                setError(selectArl, 'Debe seleccionar una ARL.');
                isValid = false;
            } else if (tipoEntidadSelect.value === 'arl') {
                setSuccess(selectArl);
            }
        }

        if (!selectRegimen.value) {
            setError(selectRegimen, 'Debe seleccionar un régimen.');
            isValid = false;
        } else {
            setSuccess(selectRegimen);
        }

        if (!selectEstado.value) {
            setError(selectEstado, 'Debe seleccionar un estado.');
            isValid = false;
        } else {
            setSuccess(selectEstado);
        }

        btnGuardar.disabled = !isValid;
        return isValid;
    };

    const showResponseModal = (status, title, message) => {
        const modalIcon = document.getElementById('modalIcon');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const successIcon = `<svg class="modal-icon-svg checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg>`;
        const errorIcon = `<svg class="modal-icon-svg crossmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="crossmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="crossmark__line" fill="none" d="M16 16 36 36 M36 16 16 36"/></svg>`;
        modalIcon.innerHTML = (status === 'success') ? successIcon : errorIcon;
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        responseModalInstance.show();
    };

    function resetModalForm() {
        form.reset();
        [tipoEntidadSelect, selectEps, selectArl, selectRegimen, selectEstado].forEach(el => {
            el.classList.remove('is-invalid', 'is-valid');
            const feedback = document.getElementById(`error-${el.id}`);
            if (feedback) feedback.textContent = '';
        });
        contenedorEpsSelect.style.display = 'none';
        selectEps.innerHTML = '<option value="">Seleccione EPS...</option>';
        contenedorArlSelect.style.display = 'none';
        selectArl.innerHTML = '<option value="">Seleccione ARL...</option>';
        btnGuardar.disabled = true; // Deshabilitar al resetear
    }

    window.abrirModalAfiliacion = function (docUsuario, idTipoDoc) {
        resetModalForm();
        docAfiliadoModalDisplay.value = docUsuario;
        docAfiliadoModalHidden.value = docUsuario;
        idTipoDocModalHidden.value = idTipoDoc;
        window.lastAffiliationSuccess = false;
        modalAfiliacionInstance.show();
    };
    
    tipoEntidadSelect.addEventListener('change', function () {
        const tipo = this.value;
        contenedorEpsSelect.style.display = 'none';
        contenedorArlSelect.style.display = 'none';
        selectEps.value = '';
        selectArl.value = '';
        
        if (tipo === 'eps') {
            contenedorEpsSelect.style.display = 'block';
            selectEps.innerHTML = '<option value="">Cargando EPS...</option>';
            fetch('../ajax/get_eps.php')
                .then(response => response.json())
                .then(data => {
                    selectEps.innerHTML = '<option value="">Seleccione EPS...</option>';
                    if (data.success && data.data) {
                        data.data.forEach(eps => { selectEps.add(new Option(eps.nombre, eps.id)); });
                    }
                }).catch(e => selectEps.innerHTML = '<option value="">Error al cargar</option>');
        } else if (tipo === 'arl') {
             contenedorArlSelect.style.display = 'block';
             selectArl.innerHTML = '<option value="">Cargando ARL...</option>';
             fetch('../ajax/get_arl.php')
                .then(response => response.json())
                .then(data => {
                    selectArl.innerHTML = '<option value="">Seleccione ARL...</option>';
                    if (data.success && data.data) {
                        data.data.forEach(arl => { selectArl.add(new Option(arl.nombre, arl.id)); });
                    }
                }).catch(e => selectArl.innerHTML = '<option value="">Error al cargar</option>');
        }
        validateForm(); // Validar después de cambiar
    });

    [tipoEntidadSelect, selectEps, selectArl, selectRegimen, selectEstado].forEach(el => {
        el.addEventListener('change', validateForm);
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        
        if (!validateForm()) {
            return;
        }
        
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
        
        const formData = new FormData(form);

        fetch('ajax_procesar_afiliacion.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            modalAfiliacionInstance.hide();
            window.lastAffiliationSuccess = data.success;
            showResponseModal(data.success ? 'success' : 'error', data.success ? 'Éxito' : 'Error', data.message);
        })
        .catch(error => {
            modalAfiliacionInstance.hide();
            window.lastAffiliationSuccess = false;
            showResponseModal('error', 'Error de Comunicación', 'No se pudo procesar la solicitud. ' + error.message);
        })
        .finally(() => {
             btnGuardar.disabled = false;
             btnGuardar.innerHTML = '<i class="bi bi-check-circle me-1"></i>Guardar Afiliación';
        });
    });
});