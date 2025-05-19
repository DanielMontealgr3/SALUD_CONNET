document.addEventListener('DOMContentLoaded', () => {
    const tipoEntidadSelector = document.getElementById('tipo_entidad_selector');
    const formCrearEntidad = document.getElementById('formCrearEntidad');
    const submitButton = formCrearEntidad.querySelector('button[type="submit"]');

    const formFarmacia = document.getElementById('form_farmacia');
    const formEps = document.getElementById('form_eps');
    const formIps = document.getElementById('form_ips');
    const allForms = [formFarmacia, formEps, formIps];

    const nitFarm = document.getElementById('nit_farm');
    const nomFarm = document.getElementById('nom_farm');
    const nomGerenteFarm = document.getElementById('nom_gerente_farm');
    const telFarm = document.getElementById('tel_farm');
    const correoFarm = document.getElementById('correo_farm');

    const nitEps = document.getElementById('nit_eps');
    const nomEps = document.getElementById('nombre_eps');
    const nomGerenteEps = document.getElementById('nom_gerente_eps');
    const telEps = document.getElementById('telefono_eps');
    const correoEps = document.getElementById('correo_eps');

    const nitIps = document.getElementById('nit_ips');
    const nomIps = document.getElementById('nom_ips');
    const depIpsSelect = document.getElementById('id_dep_ips');
    const munIpsSelect = document.getElementById('ubicacion_mun_ips');
    const nomGerenteIps = document.getElementById('nom_gerente_ips');
    const telIps = document.getElementById('tel_ips');
    const correoIps = document.getElementById('correo_ips');

    const setError = (element, message) => {
        if (!element) return;
        const errorSpan = document.getElementById(`error-${element.id}`);
        element.classList.add('input-error');
        element.classList.remove('input-success');
        if (errorSpan) {
            errorSpan.textContent = message;
            errorSpan.classList.add('visible');
        }
    };

    const setSuccess = (element) => {
        if (!element) return;
        const errorSpan = document.getElementById(`error-${element.id}`);
        element.classList.remove('input-error');
        element.classList.add('input-success');
        if (errorSpan) {
            errorSpan.textContent = '';
            errorSpan.classList.remove('visible');
        }
    };
    
    const clearValidation = (element) => {
        if (!element) return;
        const errorSpan = document.getElementById(`error-${element.id}`);
        element.classList.remove('input-error', 'input-success');
        if (errorSpan) {
            errorSpan.textContent = '';
            errorSpan.classList.remove('visible');
        }
    };

    function validateNit(field) {
        if (!field || field.disabled) return true;
        const value = field.value.trim();
        if (value === "") { setError(field, 'El NIT es obligatorio.'); return false; }
        if (!/^\d+$/.test(value)) { setError(field, 'El NIT solo debe contener números.'); return false; }
        setSuccess(field); return true;
    }

    function validateNameField(field, fieldName = "Nombre") {
        if (!field || field.disabled) return true;
        const value = field.value.trim();
        if (value === "") { setError(field, `El ${fieldName} es obligatorio.`); return false; }
        if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u.test(value)) { setError(field, `El ${fieldName} solo debe contener letras y espacios.`); return false; }
        setSuccess(field); return true;
    }
    
    function validateOptionalNameField(field, fieldName = "Nombre") {
        if (!field || field.disabled) return true;
        const value = field.value.trim();
        if (value !== "" && !/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u.test(value)) {
            setError(field, `El ${fieldName} solo debe contener letras y espacios si se ingresa.`); return false;
        }
        if(value === "") clearValidation(field); else setSuccess(field);
        return true;
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
                if(depIpsSelect.value) { // Si hay un valor precargado para departamento
                    depIpsSelect.dispatchEvent(new Event('change')); // Disparar change para cargar municipios
                } else {
                     munIpsSelect.disabled = true; 
                     munIpsSelect.innerHTML = '<option value="">Seleccione Departamento primero...</option>';
                }
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
        showSelectedForm(); // Para manejar estado inicial si hay POST data (ej. error de validación PHP)
    }

    if (depIpsSelect) {
        depIpsSelect.addEventListener('change', function() {
            const idDep = this.value;
            munIpsSelect.innerHTML = '<option value="">Cargando municipios...</option>';
            munIpsSelect.disabled = true;
            
            // Validar el select de departamento al cambiar
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
                        const currentMunValue = munIpsSelect.dataset.currentValue || ""; 
                        munIpsSelect.innerHTML = '<option value="">Seleccione Municipio...</option>';
                        if (data && data.length > 0) {
                            data.forEach(mun => {
                                const option = document.createElement('option');
                                option.value = mun.id;
                                option.textContent = mun.nombre;
                                if(mun.id === currentMunValue) option.selected = true; // Repoblar si es necesario
                                munIpsSelect.appendChild(option);
                            });
                            munIpsSelect.disabled = false;
                        } else {
                            munIpsSelect.innerHTML = '<option value="">No hay municipios</option>';
                        }
                        // Validar el select de municipio después de cargar/actualizar
                        if(munIpsSelect.value === "" && !munIpsSelect.disabled) {
                            setError(munIpsSelect, "Debe seleccionar un Municipio.");
                        } else if (!munIpsSelect.disabled) {
                             setSuccess(munIpsSelect);
                        }
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
        // Si hay un valor en el select de municipio al cargar la página (ej. por POST),
        // guardarlo en dataset para poder repoblarlo si el departamento cambia y luego vuelve.
        if(munIpsSelect.value) munIpsSelect.dataset.currentValue = munIpsSelect.value;
    }
    
    // Listeners para validaciones en tiempo real
    if(nitFarm) nitFarm.addEventListener('input', () => validateNit(nitFarm));
    if(nomFarm) nomFarm.addEventListener('input', () => validateNameField(nomFarm, "Nombre Farmacia"));
    if(nomGerenteFarm) nomGerenteFarm.addEventListener('input', () => validateOptionalNameField(nomGerenteFarm, "Nombre Gerente"));
    if(telFarm) telFarm.addEventListener('input', () => validatePhone(telFarm));
    if(correoFarm) correoFarm.addEventListener('input', () => validateEmail(correoFarm));

    if(nitEps) nitEps.addEventListener('input', () => validateNit(nitEps));
    if(nomEps) nomEps.addEventListener('input', () => validateNameField(nomEps, "Nombre EPS"));
    if(nomGerenteEps) nomGerenteEps.addEventListener('input', () => validateOptionalNameField(nomGerenteEps, "Nombre Gerente"));
    if(telEps) telEps.addEventListener('input', () => validatePhone(telEps));
    if(correoEps) correoEps.addEventListener('input', () => validateEmail(correoEps));
    
    if(nitIps) nitIps.addEventListener('input', () => validateNit(nitIps));
    if(nomIps) nomIps.addEventListener('input', () => validateNameField(nomIps, "Nombre IPS"));
    if(depIpsSelect) depIpsSelect.addEventListener('change', () => validateSelect(depIpsSelect, 'Departamento')); // Ya tiene un listener más específico, pero esto no daña
    if(munIpsSelect) munIpsSelect.addEventListener('change', () => validateSelect(munIpsSelect, 'Municipio'));
    if(nomGerenteIps) nomGerenteIps.addEventListener('input', () => validateOptionalNameField(nomGerenteIps, "Nombre Gerente"));
    if(telIps) telIps.addEventListener('input', () => validatePhone(telIps));
    if(correoIps) correoIps.addEventListener('input', () => validateEmail(correoIps));

    if (formCrearEntidad) {
        formCrearEntidad.addEventListener('submit', function(e) {
            let isValid = true;
            const selectedType = tipoEntidadSelector.value;

            if (!validateSelect(tipoEntidadSelector, "Tipo de entidad")) isValid = false;

            if (selectedType === 'farmacia') {
                if (!validateNit(nitFarm)) isValid = false;
                if (!validateNameField(nomFarm, "Nombre Farmacia")) isValid = false;
                if (!validateOptionalNameField(nomGerenteFarm, "Nombre Gerente")) isValid = false;
                if (!validatePhone(telFarm)) isValid = false;
                if (!validateEmail(correoFarm)) isValid = false;
            } else if (selectedType === 'eps') {
                if (!validateNit(nitEps)) isValid = false;
                if (!validateNameField(nomEps, "Nombre EPS")) isValid = false;
                if (!validateOptionalNameField(nomGerenteEps, "Nombre Gerente")) isValid = false;
                if (!validatePhone(telEps)) isValid = false;
                if (!validateEmail(correoEps)) isValid = false;
            } else if (selectedType === 'ips') {
                if (!validateNit(nitIps)) isValid = false;
                if (!validateNameField(nomIps, "Nombre IPS")) isValid = false;
                if (!validateSelect(depIpsSelect, "Departamento Ubicación")) isValid = false;
                if (!validateSelect(munIpsSelect, "Municipio Ubicación")) isValid = false;
                if (!validateOptionalNameField(nomGerenteIps, "Nombre Gerente")) isValid = false;
                if (!validatePhone(telIps)) isValid = false;
                if (!validateEmail(correoIps)) isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                const primerError = formCrearEntidad.querySelector('.input-error:not([disabled])');
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