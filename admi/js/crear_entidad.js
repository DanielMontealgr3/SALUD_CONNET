document.addEventListener('DOMContentLoaded', () => {
    const tipoEntidadSelector = document.getElementById('tipo_entidad_selector');
    const formCrearEntidad = document.getElementById('formCrearEntidad');
    const submitButton = formCrearEntidad.querySelector('button[type="submit"]');
    const globalMessagesContainer = document.getElementById('global-messages-container');
    const responseModal = new bootstrap.Modal(document.getElementById('responseModal'));

    const formFarmacia = document.getElementById('form_farmacia');
    const formEps = document.getElementById('form_eps');
    const formIps = document.getElementById('form_ips');
    const allForms = [formFarmacia, formEps, formIps];
    const allInputs = formCrearEntidad.querySelectorAll('input, select');

    const nitFarm = document.getElementById('nit_farm');
    const nomFarm = document.getElementById('nom_farm');
    const direcFarm = document.getElementById('direc_farm');
    const nomGerenteFarm = document.getElementById('nom_gerente_farm');
    const telFarm = document.getElementById('tel_farm');
    const correoFarm = document.getElementById('correo_farm');

    const nitEps = document.getElementById('nit_eps');
    const nomEps = document.getElementById('nombre_eps');
    const direcEps = document.getElementById('direc_eps');
    const nomGerenteEps = document.getElementById('nom_gerente_eps');
    const telEps = document.getElementById('telefono_eps');
    const correoEps = document.getElementById('correo_eps');

    const nitIps = document.getElementById('nit_ips');
    const nomIps = document.getElementById('nom_ips');
    const depIpsSelect = document.getElementById('id_dep_ips');
    const munIpsSelect = document.getElementById('ubicacion_mun_ips');
    const direcIps = document.getElementById('direc_ips');
    const nomGerenteIps = document.getElementById('nom_gerente_ips');
    const telIps = document.getElementById('tel_ips');
    const correoIps = document.getElementById('correo_ips');
    
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

    const setError = (element, message) => {
        if (!element) return;
        element.classList.add('is-invalid');
        element.classList.remove('is-valid');
        const feedbackElement = document.getElementById(`feedback-${element.id}`);
        if (feedbackElement) {
            feedbackElement.textContent = message;
        }
    };

    const setSuccess = (element) => {
        if (!element) return;
        element.classList.remove('is-invalid');
        element.classList.add('is-valid');
        const feedbackElement = document.getElementById(`feedback-${element.id}`);
        if (feedbackElement) {
            feedbackElement.textContent = '';
        }
    };
    
    const clearValidation = (element) => {
        if (!element) return;
        element.classList.remove('is-invalid', 'is-valid');
        const feedbackElement = document.getElementById(`feedback-${element.id}`);
        if (feedbackElement) {
            feedbackElement.textContent = '';
        }
    };

    const clearAllValidations = () => {
        allInputs.forEach(input => clearValidation(input));
    };

    function validateNit(field) {
        if (!field || field.disabled) return true;
        const value = field.value.trim();
        if (value === "") { setError(field, 'El NIT es obligatorio.'); return false; }
        if (!/^\d+$/.test(value)) { setError(field, 'El NIT solo debe contener números.'); return false; }
        if (value.length < 7) { setError(field, 'El NIT debe tener al menos 7 dígitos.'); return false; }
        setSuccess(field); return true;
    }

    function validateNameField(field, fieldName = "Nombre") {
        if (!field || field.disabled) return true;
        const value = field.value.trim();
        if (value === "") { setError(field, `El ${fieldName} es obligatorio.`); return false; }
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\.]+$/u.test(value)) { setError(field, `El ${fieldName} solo debe contener letras, puntos y espacios.`); return false; }
        setSuccess(field); return true;
    }

    function validateAddress(field, fieldName = "Dirección") {
        if (!field || field.disabled) return true;
        const value = field.value.trim();
        if (value === "") { setError(field, `La ${fieldName} es obligatoria.`); return false;}
        setSuccess(field); return true;
    }

    function validatePhone(field) {
        if (!field || field.disabled) return true;
        const value = field.value.trim();
        if (value === "") { setError(field, 'El teléfono es obligatorio.'); return false; } 
        if (!/^\d+$/.test(value)) { setError(field, 'El teléfono solo debe contener números.'); return false; }
        if (value.length < 7 || value.length > 10) { setError(field, 'El teléfono debe tener entre 7 y 10 dígitos.'); return false; }
        setSuccess(field); return true;
    }

    function validateEmail(field) {
        if (!field || field.disabled) return true;
        const value = field.value.trim();
        if (value === "") { setError(field, 'El correo es obligatorio.'); return false; } 
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/; 
        if (!emailRegex.test(value)) { setError(field, 'El formato del correo no es válido.'); return false; }
        setSuccess(field); return true;
    }
    
    function validateSelect(field, fieldName = "Campo") {
        if (!field || field.disabled) return true;
        if (field.value === "") { setError(field, `Debe seleccionar un ${fieldName}.`); return false; }
        setSuccess(field); return true;
    }

    function setFieldStatus(formElement, isDisabled) {
        if (!formElement) return;
        formElement.querySelectorAll('input, select').forEach(input => {
            input.disabled = isDisabled;
            if (isDisabled) clearValidation(input);
        });
    }
    
    function showSelectedForm() {
        allForms.forEach(form => {
            if (form) {
                form.style.display = 'none';
                setFieldStatus(form, true);
            }
        });
        if (submitButton) submitButton.disabled = true; 

        const selectedType = tipoEntidadSelector.value;
        let activeForm = null;

        if (selectedType === 'farmacia') activeForm = formFarmacia;
        else if (selectedType === 'eps') activeForm = formEps;
        else if (selectedType === 'ips') activeForm = formIps;

        if (activeForm) {
            activeForm.style.display = 'block';
            setFieldStatus(activeForm, false);
            if(selectedType === 'ips' && munIpsSelect && depIpsSelect){
                munIpsSelect.disabled = !depIpsSelect.value;
            }
            if (submitButton) submitButton.disabled = false;
            if (selectedType !== "") setSuccess(tipoEntidadSelector); else clearValidation(tipoEntidadSelector);
        } else {
             if(selectedType === "") {
                setError(tipoEntidadSelector, "Debe seleccionar un tipo de entidad.");
             } else {
                 clearValidation(tipoEntidadSelector);
             }
        }
    }

    if (tipoEntidadSelector) {
        tipoEntidadSelector.addEventListener('change', showSelectedForm);
        showSelectedForm();
    }

    if (depIpsSelect) {
        depIpsSelect.addEventListener('change', function() {
            const idDep = this.value;
            munIpsSelect.innerHTML = '<option value="">Cargando municipios...</option>';
            munIpsSelect.disabled = true;
            clearValidation(munIpsSelect); 
            
            if(this.value === "" && !this.disabled) { 
                setError(this, "Debe seleccionar un Departamento.");
            } else if (!this.disabled) {
                setSuccess(this);
            }

            if (idDep) {
                fetch(`../ajax/get_municipios.php?id_dep=${encodeURIComponent(idDep)}`)
                    .then(response => response.json())
                    .then(data => {
                        munIpsSelect.innerHTML = '<option value="">Seleccione Municipio...</option>';
                        if (data && data.length > 0) {
                            data.forEach(mun => {
                                const option = new Option(mun.nombre, mun.id);
                                munIpsSelect.appendChild(option);
                            });
                            munIpsSelect.disabled = false;
                        } else {
                            munIpsSelect.innerHTML = '<option value="">No hay municipios</option>';
                            setError(munIpsSelect, "No hay municipios para este departamento.");
                        }
                    })
                    .catch(error => {
                        console.error('Error cargando municipios:', error);
                        munIpsSelect.innerHTML = '<option value="">Error al cargar</option>';
                        setError(munIpsSelect, "Error al cargar municipios.");
                    });
            } else {
                munIpsSelect.innerHTML = '<option value="">Seleccione Departamento primero...</option>';
                munIpsSelect.disabled = true;
            }
        });
    }
    
    if(nitFarm) nitFarm.addEventListener('input', () => validateNit(nitFarm));
    if(nomFarm) nomFarm.addEventListener('input', () => validateNameField(nomFarm, "Nombre Farmacia"));
    if(direcFarm) direcFarm.addEventListener('input', () => validateAddress(direcFarm));
    if(nomGerenteFarm) nomGerenteFarm.addEventListener('input', () => validateNameField(nomGerenteFarm, "Nombre Gerente"));
    if(telFarm) telFarm.addEventListener('input', () => validatePhone(telFarm));
    if(correoFarm) correoFarm.addEventListener('input', () => validateEmail(correoFarm));

    if(nitEps) nitEps.addEventListener('input', () => validateNit(nitEps));
    if(nomEps) nomEps.addEventListener('input', () => validateNameField(nomEps, "Nombre EPS"));
    if(direcEps) direcEps.addEventListener('input', () => validateAddress(direcEps));
    if(nomGerenteEps) nomGerenteEps.addEventListener('input', () => validateNameField(nomGerenteEps, "Nombre Gerente"));
    if(telEps) telEps.addEventListener('input', () => validatePhone(telEps));
    if(correoEps) correoEps.addEventListener('input', () => validateEmail(correoEps));
    
    if(nitIps) nitIps.addEventListener('input', () => validateNit(nitIps));
    if(nomIps) nomIps.addEventListener('input', () => validateNameField(nomIps, "Nombre IPS"));
    if(direcIps) direcIps.addEventListener('input', () => validateAddress(direcIps, "Dirección (Detalle)"));
    if(depIpsSelect) depIpsSelect.addEventListener('change', () => validateSelect(depIpsSelect, 'Departamento'));
    if(munIpsSelect) munIpsSelect.addEventListener('change', () => validateSelect(munIpsSelect, 'Municipio'));
    if(nomGerenteIps) nomGerenteIps.addEventListener('input', () => validateNameField(nomGerenteIps, "Nombre Gerente"));
    if(telIps) telIps.addEventListener('input', () => validatePhone(telIps));
    if(correoIps) correoIps.addEventListener('input', () => validateEmail(correoIps));

    if (formCrearEntidad) {
        formCrearEntidad.addEventListener('submit', function(e) {
            e.preventDefault(); 
            let isValid = true;
            const selectedType = tipoEntidadSelector.value;
            
            globalMessagesContainer.innerHTML = '';

            if (!validateSelect(tipoEntidadSelector, "Tipo de entidad")) isValid = false;

            if (selectedType === 'farmacia') {
                if (!validateNit(nitFarm)) isValid = false;
                if (!validateNameField(nomFarm, "Nombre Farmacia")) isValid = false;
                if (!validateAddress(direcFarm)) isValid = false;
                if (!validateNameField(nomGerenteFarm, "Nombre Gerente")) isValid = false;
                if (!validatePhone(telFarm)) isValid = false;
                if (!validateEmail(correoFarm)) isValid = false;
            } else if (selectedType === 'eps') {
                if (!validateNit(nitEps)) isValid = false;
                if (!validateNameField(nomEps, "Nombre EPS")) isValid = false;
                if (!validateAddress(direcEps)) isValid = false;
                if (!validateNameField(nomGerenteEps, "Nombre Gerente")) isValid = false;
                if (!validatePhone(telEps)) isValid = false;
                if (!validateEmail(correoEps)) isValid = false;
            } else if (selectedType === 'ips') {
                if (!validateNit(nitIps)) isValid = false;
                if (!validateNameField(nomIps, "Nombre IPS")) isValid = false;
                if (!validateAddress(direcIps, "Dirección (Detalle)")) isValid = false;
                if (!validateSelect(depIpsSelect, "Departamento Ubicación")) isValid = false;
                if (!validateSelect(munIpsSelect, "Municipio Ubicación")) isValid = false;
                if (!validateNameField(nomGerenteIps, "Nombre Gerente")) isValid = false;
                if (!validatePhone(telIps)) isValid = false;
                if (!validateEmail(correoIps)) isValid = false;
            }

            if (!isValid) {
                globalMessagesContainer.innerHTML = '<div class="alert alert-danger">Por favor, corrija los campos marcados en rojo.</div>';
                const firstError = formCrearEntidad.querySelector('.is-invalid:not([disabled])');
                if (firstError) {
                    firstError.focus();
                }
                return;
            }

            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

            const formData = new FormData(formCrearEntidad);

            fetch('crear_entidad.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showResponseModal('success', '¡Éxito!', data.message);
                    formCrearEntidad.reset();
                    clearAllValidations();
                    tipoEntidadSelector.dispatchEvent(new Event('change'));
                } else {
                    showResponseModal('error', 'Error', data.message || 'Ocurrió un error desconocido.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showResponseModal('error', 'Error de Conexión', 'No se pudo comunicar con el servidor. Revise su conexión a internet.');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Guardar Entidad <i class="bi bi-check-circle"></i>';
            });
        });
    }
});