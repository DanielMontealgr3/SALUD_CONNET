document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formCrearDepartamento');
    const btnCrearDepartamento = form.querySelector('button[name="crear_departamento"]'); // Asegurado que el name coincida
    const idDepInput = document.getElementById('id_dep');
    const nomDepInput = document.getElementById('nom_dep');
    
    let mensajesGeneralesLimpiados = false;

    const campos = [
        { 
            el: idDepInput, 
            req: true, 
            fn: v => {
                if (v === "") return { isValid: false, message: "ID Departamento es requerido." };
                if (!/^\d+$/.test(v)) return { isValid: false, message: "ID solo números." };
                if (v.length > 10) return { isValid: false, message: "ID máx 10 dígitos." }; // Unificado
                return { isValid: true };
            }, 
            name: "ID Departamento" 
        },
        { 
            el: nomDepInput, 
            req: true, 
            fn: v => {
                if (v === "") return { isValid: false, message: "Nombre es requerido." };
                if (!/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/u.test(v)) return { isValid: false, message: "Nombre solo letras y espacios." };
                if (v.length < 3 || v.length > 80) return { isValid: false, message: "Nombre 3-80 caracteres." };
                return { isValid: true };
            }, 
            name: "Nombre Departamento" 
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
        if (!el) return true;

        let result = { isValid: true, message: "" };
        const val = el.value.trim();

        if (req && val === "") {
            result = { isValid: false, message: `${name} es requerido.` };
        } else if (fn && val !== "") { // Asegurar que solo se llame a fn si hay valor
            const fnRes = fn(el.value); // Pasar el valor completo, no el trim() para fn
            if (typeof fnRes === 'object') {
                result = fnRes;
            } else {
                result.isValid = fnRes;
                if (!fnRes) result.message = `${name} inválido.`;
            }
        }
        
        setFieldValidationUI(el, result.isValid, result.message, forceShowError || el.dataset.interacted === "true");
        return result.isValid;
    }

    function validateForm(forceShowAllErrors = false) {
        let isFormValid = true;
        campos.forEach(fieldConfig => {
            if (forceShowAllErrors && fieldConfig.el) fieldConfig.el.dataset.interacted = "true";
            if (!validateSingleField(fieldConfig, forceShowAllErrors)) {
                isFormValid = false;
            }
        });

        if (btnCrearDepartamento) { // Verifica que el botón exista
            btnCrearDepartamento.disabled = !isFormValid;
            btnCrearDepartamento.classList.toggle('btn-success', isFormValid); 
            btnCrearDepartamento.classList.toggle('btn-primary', !isFormValid); 
        }
        return isFormValid;
    }

    campos.forEach(fieldConfig => {
        if (fieldConfig.el) {
            fieldConfig.el.addEventListener('input', () => {
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
             fieldConfig.el.addEventListener('blur', () => {
                fieldConfig.el.dataset.interacted = "true";
                validateSingleField(fieldConfig, true);
                validateForm(false);
            });
        }
    });

    if (form) {
        form.addEventListener('submit', (event) => {
            if (!validateForm(true)) {
                event.preventDefault();
                const firstInvalid = campos.find(f => f.el && f.el.classList.contains('is-invalid'));
                if (firstInvalid && firstInvalid.el) firstInvalid.el.focus();
            }
        });
    }

    campos.forEach(fc => { 
        if (fc.el && fc.el.value && !errorServidorEspecificoDiv) { 
            fc.el.dataset.interacted = "true"; 
            validateSingleField(fc, true); 
        }
    });
    validateForm(false); 

    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('status') && !errorServidorEspecificoDiv) {
        const camposConValor = campos.some(c => c.el && c.el.value.trim() !== '');
        if (camposConValor) {
            mensajesGeneralesLimpiados = true;
        }
    }
});