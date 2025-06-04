document.addEventListener('DOMContentLoaded', () => {
    const tipoEntidadSelector = document.getElementById('tipo_entidad_selector');
    const formCrearEntidad = document.getElementById('formCrearEntidad');
    const submitButton = formCrearEntidad.querySelector('button[type="submit"]');
    const globalMessagesContainer = document.getElementById('global-messages-container');

    const formFarmacia = document.getElementById('form_farmacia');
    const formEps = document.getElementById('form_eps');
    const formIps = document.getElementById('form_ips');
    const allForms = [formFarmacia, formEps, formIps];

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
    
    function validateOptionalNameField(field, fieldName = "Nombre Gerente") {
        if (!field || field.disabled) return true;
        const value = field.value.trim();
        if (value !== "" && !/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u.test(value)) {
            setError(field, `El ${fieldName} solo debe contener letras y espacios si se ingresa.`); return false;
        }
        if(value === "") clearValidation(field); else setSuccess(field);
        return true;
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
        if (value === "") { clearValidation(field); return true; } 
        if (!/^\d+$/.test(value)) { setError(field, 'El teléfono solo debe contener números.'); return false; }
        if (value.length < 7 || value.length > 10) { setError(field, 'El teléfono debe tener entre 7 y 10 dígitos.'); return false; }
        setSuccess(field); return true;
    }

    function validateEmail(field) {
        if (!field || field.disabled) return true;
        const value = field.value.trim();
        if (value === "") { clearValidation(field); return true; } 
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

        if (selectedType === 'farmacia' && formFarmacia) activeForm = formFarmacia;
        else if (selectedType === 'eps' && formEps) activeForm = formEps;
        else if (selectedType === 'ips' && formIps) activeForm = formIps;

        if (activeForm) {
            activeForm.style.display = 'block';
            setFieldStatus(activeForm, false);
            if(selectedType === 'ips' && munIpsSelect && depIpsSelect){ 
                depIpsSelect.disabled = false;
                if(depIpsSelect.value && munIpsSelect.options.length <= 1 && munIpsSelect.dataset.currentValue) { 
                    depIpsSelect.dispatchEvent(new Event('change')); 
                } else if(!depIpsSelect.value) {
                     munIpsSelect.disabled = true; 
                     munIpsSelect.innerHTML = '<option value="">Seleccione Departamento primero...</option>';
                     clearValidation(munIpsSelect); 
                }
            }
            if (submitButton) submitButton.disabled = false;
            if (selectedType !== "") setSuccess(tipoEntidadSelector); else clearValidation(tipoEntidadSelector);
        } else {
            if(selectedType === "" && tipoEntidadSelector.name === "tipo_entidad_selector") { 
                setError(tipoEntidadSelector, "Debe seleccionar un tipo de entidad.");
            } else {
                clearValidation(tipoEntidadSelector);
            }
        }
    }

    if (tipoEntidadSelector) {
        tipoEntidadSelector.addEventListener('change', showSelectedForm);
        showSelectedForm(); // Call on initial load
    }

    if (depIpsSelect) {
        depIpsSelect.addEventListener('change', function() {
            const idDep = this.value;
            const currentMunValue = munIpsSelect.dataset.currentValue || "";
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
                    .then(response => {
                        if (!response.ok) throw new Error('Error en la respuesta de red');
                        return response.json();
                    })
                    .then(data => {
                        munIpsSelect.innerHTML = '<option value="">Seleccione Municipio...</option>';
                        if (data && data.length > 0) {
                            data.forEach(mun => {
                                const option = document.createElement('option');
                                option.value = mun.id;
                                option.textContent = mun.nombre;
                                if(mun.id === currentMunValue) option.selected = true;
                                munIpsSelect.appendChild(option);
                            });
                            munIpsSelect.disabled = false;
                            if(munIpsSelect.value) setSuccess(munIpsSelect); else setError(munIpsSelect, "Debe seleccionar un Municipio.");

                        } else {
                            munIpsSelect.innerHTML = '<option value="">No hay municipios</option>';
                            setError(munIpsSelect, "No hay municipios para este departamento.");
                        }
                        if(currentMunValue && munIpsSelect.value === currentMunValue) setSuccess(munIpsSelect); 
                    })
                    .catch(error => {
                        console.error('Error cargando municipios:', error);
                        munIpsSelect.innerHTML = '<option value="">Error al cargar</option>';
                        if(!this.disabled) setError(this, "Error cargando municipios.");
                    });
            } else {
                munIpsSelect.innerHTML = '<option value="">Seleccione Departamento primero...</option>';
                if(!munIpsSelect.disabled) setError(munIpsSelect, "Debe seleccionar un Municipio (después del Departamento).");
            }
        });
        if(munIpsSelect.value) munIpsSelect.dataset.currentValue = munIpsSelect.value;
        if (depIpsSelect.value && munIpsSelect.options.length <= 1 && tipoEntidadSelector.value === 'ips') {
             depIpsSelect.dispatchEvent(new Event('change'));
        }
    }
    
    if(nitFarm) nitFarm.addEventListener('input', () => validateNit(nitFarm));
    if(nomFarm) nomFarm.addEventListener('input', () => validateNameField(nomFarm, "Nombre Farmacia"));
    if(direcFarm) direcFarm.addEventListener('input', () => validateAddress(direcFarm));
    if(nomGerenteFarm) nomGerenteFarm.addEventListener('input', () => validateOptionalNameField(nomGerenteFarm, "Nombre Gerente"));
    if(telFarm) telFarm.addEventListener('input', () => validatePhone(telFarm));
    if(correoFarm) correoFarm.addEventListener('input', () => validateEmail(correoFarm));

    if(nitEps) nitEps.addEventListener('input', () => validateNit(nitEps));
    if(nomEps) nomEps.addEventListener('input', () => validateNameField(nomEps, "Nombre EPS"));
    if(direcEps) direcEps.addEventListener('input', () => validateAddress(direcEps));
    if(nomGerenteEps) nomGerenteEps.addEventListener('input', () => validateOptionalNameField(nomGerenteEps, "Nombre Gerente"));
    if(telEps) telEps.addEventListener('input', () => validatePhone(telEps));
    if(correoEps) correoEps.addEventListener('input', () => validateEmail(correoEps));
    
    if(nitIps) nitIps.addEventListener('input', () => validateNit(nitIps));
    if(nomIps) nomIps.addEventListener('input', () => validateNameField(nomIps, "Nombre IPS"));
    if(direcIps) direcIps.addEventListener('input', () => validateAddress(direcIps, "Dirección (Detalle)"));
    if(depIpsSelect) depIpsSelect.addEventListener('change', () => validateSelect(depIpsSelect, 'Departamento'));
    if(munIpsSelect) munIpsSelect.addEventListener('change', () => validateSelect(munIpsSelect, 'Municipio'));
    if(nomGerenteIps) nomGerenteIps.addEventListener('input', () => validateOptionalNameField(nomGerenteIps, "Nombre Gerente"));
    if(telIps) telIps.addEventListener('input', () => validatePhone(telIps));
    if(correoIps) correoIps.addEventListener('input', () => validateEmail(correoIps));

    if (formCrearEntidad) {
        formCrearEntidad.addEventListener('submit', function(e) {
            let isValid = true;
            const selectedType = tipoEntidadSelector.value;
            
            if (globalMessagesContainer) globalMessagesContainer.innerHTML = '';

            if (!validateSelect(tipoEntidadSelector, "Tipo de entidad")) isValid = false;

            if (selectedType === 'farmacia') {
                if (!validateNit(nitFarm)) isValid = false;
                if (!validateNameField(nomFarm, "Nombre Farmacia")) isValid = false;
                if (!validateAddress(direcFarm)) isValid = false;
                if (!validateOptionalNameField(nomGerenteFarm, "Nombre Gerente")) isValid = false;
                if (!validatePhone(telFarm)) isValid = false;
                if (!validateEmail(correoFarm)) isValid = false;
            } else if (selectedType === 'eps') {
                if (!validateNit(nitEps)) isValid = false;
                if (!validateNameField(nomEps, "Nombre EPS")) isValid = false;
                if (!validateAddress(direcEps)) isValid = false;
                if (!validateOptionalNameField(nomGerenteEps, "Nombre Gerente")) isValid = false;
                if (!validatePhone(telEps)) isValid = false;
                if (!validateEmail(correoEps)) isValid = false;
            } else if (selectedType === 'ips') {
                if (!validateNit(nitIps)) isValid = false;
                if (!validateNameField(nomIps, "Nombre IPS")) isValid = false;
                if (!validateAddress(direcIps, "Dirección (Detalle)")) isValid = false;
                if (!validateSelect(depIpsSelect, "Departamento Ubicación")) isValid = false;
                if (!validateSelect(munIpsSelect, "Municipio Ubicación")) isValid = false;
                if (!validateOptionalNameField(nomGerenteIps, "Nombre Gerente")) isValid = false;
                if (!validatePhone(telIps)) isValid = false;
                if (!validateEmail(correoIps)) isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                if (globalMessagesContainer) {
                    globalMessagesContainer.innerHTML = '<div class="alert alert-danger">Por favor, corrija los campos marcados en rojo.</div>';
                }
                const primerError = formCrearEntidad.querySelector('.is-invalid:not([disabled])');
                if (primerError) {
                    primerError.focus();
                     setTimeout(() => {
                         primerError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                }
            } else {
                if(submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
                }
            }
        });
    }
});