document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formCrearBarrio');
    const btnCrearBarrioSubmit = form.querySelector('button[name="crear_barrio"]');
    const idDepSelect = document.getElementById('id_dep');
    const idMunSelect = document.getElementById('id_mun');
    // const idBarInput = document.getElementById('id_bar'); // Comentado o eliminado
    const nomBarInput = document.getElementById('nom_bar');
    
    let mensajesGeneralesLimpiados = false;
    const formularioDeshabilitado = idDepSelect.disabled;

    const campos = [
        { 
            el: idDepSelect, 
            req: true, 
            fn: null, 
            name: "Departamento" 
        },
        { 
            el: idMunSelect, 
            req: true, 
            fn: null, 
            name: "Municipio" 
        },
        // {  // Comentado o eliminado el campo id_bar
        //     el: idBarInput, 
        //     req: true, 
        //     fn: v => {
        //         if (v === "") return { isValid: false, message: "ID Barrio es requerido." };
        //         if (!/^\d+$/.test(v)) return { isValid: false, message: "ID Barrio solo números." };
        //         if (v.length > 10) return { isValid: false, message: "ID Barrio máx 10 dígitos." };
        //         return { isValid: true };
        //     }, 
        //     name: "ID Barrio" 
        // },
        { 
            el: nomBarInput, 
            req: true, 
            fn: v => {
                if (v === "") return { isValid: false, message: "Nombre Barrio es requerido." };
                if (!/^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\s.,#-]+$/u.test(v)) return { isValid: false, message: "Nombre Barrio caracteres inválidos." };
                if (v.length < 3 || v.length > 150) return { isValid: false, message: "Nombre Barrio 3-150 caracteres." };
                return { isValid: true };
            }, 
            name: "Nombre Barrio" 
        }
    ];

    const mensajesServidorGlobal = document.getElementById('mensajesServidorGlobal');
    const errorServidorEspecificoDiv = mensajesServidorGlobal?.querySelector('.alert-danger.error-servidor-especifico[data-campo-error]');

    if (errorServidorEspecificoDiv) {
        const nombreCampoConError = errorServidorEspecificoDiv.dataset.campoError;
        const inputConErrorServidor = form.querySelector(`[name="${nombreCampoConError}"]`) || form.querySelector(`#${nombreCampoConError}`);
        if (inputConErrorServidor) {
            inputConErrorServidor.classList.add('is-invalid');
            inputConErrorServidor.dataset.interacted = "true"; 

            const feedbackEl = inputConErrorServidor.nextElementSibling;
            if (feedbackEl && feedbackEl.classList.contains('invalid-feedback')) {
                feedbackEl.textContent = errorServidorEspecificoDiv.textContent.trim();
            }
        }
    }

    function setFieldValidationUI(el, isValid, message = "", forceShowError = false) {
        const feedbackEl = el.nextElementSibling;
        if (feedbackEl && feedbackEl.classList.contains('invalid-feedback')) {
            feedbackEl.textContent = message;
        }
        
        if (el.disabled) {
            el.classList.remove('is-valid', 'is-invalid');
            if (feedbackEl) feedbackEl.textContent = '';
            return;
        }

        if (isValid && el.value.trim() !== "" && (el.dataset.interacted === "true" || forceShowError)) {
            el.classList.remove('is-invalid');
            el.classList.add('is-valid');
        } else if (!isValid && (el.dataset.interacted === "true" || forceShowError)) {
            el.classList.remove('is-valid');
            el.classList.add('is-invalid');
        } else {
            el.classList.remove('is-valid', 'is-invalid');
            if (feedbackEl) feedbackEl.textContent = '';
        }
    }

    function validateSingleField(fieldConfig, forceShowError = false) {
        const { el, req, fn, name } = fieldConfig;
        if (!el || el.disabled) return true; 

        let result = { isValid: true, message: "" };
        const val = el.value.trim();

        if (req && val === "") {
            result = { isValid: false, message: `${name} es requerido.` };
        } else if (fn && val !== "") { 
            const fnRes = fn(el.value); 
            if (typeof fnRes === 'object') {
                result = fnRes;
            } else {
                result.isValid = fnRes;
                if (!fnRes) result.message = `${name} inválido.`;
            }
        } else if (el.tagName === 'SELECT' && req && el.value === "") { 
             result = { isValid: false, message: `${name} es requerido.` };
        }
        
        setFieldValidationUI(el, result.isValid, result.message, forceShowError || el.dataset.interacted === "true");
        return result.isValid;
    }

    function validateForm(forceShowAllErrors = false) {
        if (formularioDeshabilitado) {
            if (btnCrearBarrioSubmit) btnCrearBarrioSubmit.disabled = true;
            return false;
        }

        let isFormValid = true;
        campos.forEach(fieldConfig => {
            if (forceShowAllErrors && fieldConfig.el) fieldConfig.el.dataset.interacted = "true";
            if (!validateSingleField(fieldConfig, forceShowAllErrors)) {
                isFormValid = false;
            }
        });

        if (btnCrearBarrioSubmit) {
            btnCrearBarrioSubmit.disabled = !isFormValid;
            btnCrearBarrioSubmit.classList.toggle('btn-success', isFormValid);
            btnCrearBarrioSubmit.classList.toggle('btn-primary', !isFormValid);
        }
        return isFormValid;
    }

    async function cargarMunicipios(idDep) {
        idMunSelect.innerHTML = '<option value="">Cargando municipios...</option>';
        idMunSelect.disabled = true;
        setFieldValidationUI(idMunSelect, true); 

        if (!idDep) {
            idMunSelect.innerHTML = '<option value="">Seleccione un municipio...</option>';
            validateSingleField(campos.find(c => c.el === idMunSelect), idMunSelect.dataset.interacted === "true");
            validateForm(false);
            return;
        }

        try {
            const response = await fetch(`../../ajax/get_municipios.php?id_dep=${idDep}`);
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            const municipios = await response.json();

            idMunSelect.innerHTML = '<option value="">Seleccione un municipio...</option>';
            if (municipios.length > 0) {
                municipios.forEach(mun => {
                    const option = document.createElement('option');
                    option.value = mun.id;
                    option.textContent = mun.nombre;
                    idMunSelect.appendChild(option);
                });
                idMunSelect.disabled = false;
            } else {
                idMunSelect.innerHTML = '<option value="">No hay municipios para este departamento</option>';
                 idMunSelect.disabled = true; 
            }
        } catch (error) {
            console.error('Error al cargar municipios:', error);
            idMunSelect.innerHTML = '<option value="">Error al cargar municipios</option>';
            idMunSelect.disabled = true;
        }
        validateSingleField(campos.find(c => c.el === idMunSelect), idMunSelect.dataset.interacted === "true");
        validateForm(false);
    }
    
    if (idDepSelect && !formularioDeshabilitado) {
        idDepSelect.addEventListener('change', () => {
            const idDep = idDepSelect.value;
            idMunSelect.value = ""; 
            idMunSelect.dataset.interacted = "false"; 
            setFieldValidationUI(idMunSelect, true); 
            
            cargarMunicipios(idDep);
            validateForm(false); 
        });

        if (idDepSelect.value && idMunSelect.options.length <=1) { 
             if (!idMunSelect.querySelector('option[value="' + idMunSelect.value + '"]')) {
                cargarMunicipios(idDepSelect.value).then(() => {
                    if(idMunSelect.value){ 
                        idMunSelect.dataset.interacted = "true";
                        validateSingleField(campos.find(c => c.el === idMunSelect), true);
                    }
                    validateForm(false);
                });
            } else {
                idMunSelect.disabled = false; 
                if(idMunSelect.value){
                     idMunSelect.dataset.interacted = "true";
                     validateSingleField(campos.find(c => c.el === idMunSelect), true);
                }
                validateForm(false);
            }
        } else if (!idDepSelect.value) {
            idMunSelect.disabled = true;
            idMunSelect.innerHTML = '<option value="">Seleccione un municipio...</option>';
        }
    }


    if (!formularioDeshabilitado) {
        campos.forEach(fieldConfig => {
            if (fieldConfig.el) {
                const eventType = (fieldConfig.el.tagName === 'SELECT') ? 'change' : 'input';
                fieldConfig.el.addEventListener(eventType, () => {
                    if (!mensajesGeneralesLimpiados) {
                        const msgsGlobal = document.getElementById('mensajesServidorGlobal');
                        if (msgsGlobal) {
                            const mensajesAEliminar = msgsGlobal.querySelectorAll('.alert-success, .alert-info, .alert-warning:not(.error-servidor-especifico)');
                            mensajesAEliminar.forEach(msg => msg.remove());
                        }
                        mensajesGeneralesLimpiados = true;
                    }

                    const errorServidorGlobalDivActual = document.getElementById('mensajesServidorGlobal')?.querySelector('.alert-danger.error-servidor-especifico[data-campo-error]');
                    if (errorServidorGlobalDivActual) {
                        const campoErroneoServidor = errorServidorGlobalDivActual.dataset.campoError;
                        if (fieldConfig.el.name === campoErroneoServidor || fieldConfig.el.id === campoErroneoServidor) {
                            errorServidorGlobalDivActual.remove();
                        }
                    }

                    fieldConfig.el.dataset.interacted = "true";
                    validateSingleField(fieldConfig, true);
                    validateForm(false);
                });
                 if (fieldConfig.el.tagName !== 'SELECT') {
                    fieldConfig.el.addEventListener('blur', () => {
                        fieldConfig.el.dataset.interacted = "true";
                        validateSingleField(fieldConfig, true);
                        validateForm(false);
                    });
                } else { 
                     fieldConfig.el.addEventListener('blur', () => {
                        fieldConfig.el.dataset.interacted = "true";
                        validateSingleField(fieldConfig, true);
                        validateForm(false);
                    });
                }
            }
        });

        if (form) {
            form.addEventListener('submit', (event) => {
                if (!validateForm(true)) {
                    event.preventDefault();
                    const firstInvalid = campos.find(f => f.el && f.el.classList.contains('is-invalid') && !f.el.disabled);
                    if (firstInvalid && firstInvalid.el) firstInvalid.el.focus();
                }
            });
        }
        
        campos.forEach(fc => { 
            if (fc.el && fc.el.value && !fc.el.disabled && !errorServidorEspecificoDiv) {
                if(fc.el.tagName !== 'SELECT' || (fc.el.tagName === 'SELECT' && fc.el.value !== '')) {
                    fc.el.dataset.interacted = "true"; 
                }
                validateSingleField(fc, true); 
            }
        });
        validateForm(false); 

        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('status') && !errorServidorEspecificoDiv) {
            const camposConValor = campos.some(c => c.el && !c.el.disabled && c.el.value.trim() !== '' && c.el.tagName !== 'SELECT');
            if (camposConValor) {
                mensajesGeneralesLimpiados = true;
            }
        }
    } else {
        if (btnCrearBarrioSubmit) btnCrearBarrioSubmit.disabled = true;
    }
});