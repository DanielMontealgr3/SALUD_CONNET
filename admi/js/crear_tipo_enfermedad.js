document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formCrearTipoEnfermedad');
    if (!form) return;

    const btnSubmit = form.querySelector('button[type="submit"]');
    const tipoEnfermerInput = document.getElementById('tipo_enfermer');
    let mensajesGeneralesLimpiados = false;
    
    const campos = [
        { 
            el: tipoEnfermerInput, 
            req: true, 
            fn: v => {
                if (v === "") return { isValid: false, message: "Nombre es requerido." };
                if (!/^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\s.,()#-]+$/u.test(v)) return { isValid: false, message: "Nombre contiene caracteres inválidos." };
                if (v.length < 3 || v.length > 150) return { isValid: false, message: "Nombre 3-150 caracteres." };
                return { isValid: true };
            }, 
            name: "Nombre Tipo Enfermedad" 
        }
    ];

    function setFieldValidationUI(el, isValid, message = "", forceShowError = false) {
        const feedbackEl = el.nextElementSibling;
        if (feedbackEl && feedbackEl.classList.contains('invalid-feedback')) { feedbackEl.textContent = message; }
        if (isValid && el.value.trim() !== "" && (el.dataset.interacted === "true" || forceShowError)) {
            el.classList.remove('is-invalid'); el.classList.add('is-valid');
        } else if (!isValid && (el.dataset.interacted === "true" || forceShowError)) {
            el.classList.remove('is-valid'); el.classList.add('is-invalid');
        } else {
            el.classList.remove('is-valid', 'is-invalid');
            if (feedbackEl) feedbackEl.textContent = '';
        }
    }

    function validateSingleField(fieldConfig, forceShowError = false) {
        const { el, req, fn, name } = fieldConfig;
        let result = { isValid: true, message: "" };
        const val = el.value.trim();
        if (req && val === "") { result = { isValid: false, message: `${name} es requerido.` }; }
        else if (fn && val !== "") { const fnRes = fn(el.value); result = (typeof fnRes === 'object') ? fnRes : { isValid: fnRes, message: fnRes ? "" : `${name} inválido.`}; }
        setFieldValidationUI(el, result.isValid, result.message, forceShowError || el.dataset.interacted === "true");
        return result.isValid;
    }

    function validateForm(forceShowAllErrors = false) {
        let isFormValid = true;
        campos.forEach(fieldConfig => {
            if (forceShowAllErrors && fieldConfig.el) fieldConfig.el.dataset.interacted = "true";
            if (!validateSingleField(fieldConfig, forceShowAllErrors)) isFormValid = false;
        });
        if (btnSubmit) {
            btnSubmit.disabled = !isFormValid;
            btnSubmit.classList.toggle('btn-success', isFormValid);
            btnSubmit.classList.toggle('btn-primary', !isFormValid);
        }
        return isFormValid;
    }
    
    const errorServidorGlobal = document.getElementById('mensajesServidorGlobal')?.querySelector('.alert-danger.error-servidor-especifico[data-campo-error]');
    if (errorServidorGlobal) {
        const campoConError = errorServidorGlobal.dataset.campoError;
        const inputConError = campos.find(c => c.el && (c.el.id === campoConError || c.el.name === campoConError))?.el;
        if(inputConError){
            inputConError.classList.add('is-invalid');
            inputConError.dataset.interacted = "true";
            const feedback = inputConError.nextElementSibling;
            if(feedback && feedback.classList.contains('invalid-feedback')){
                feedback.textContent = errorServidorGlobal.textContent.trim();
            }
        }
    }

    campos.forEach(fieldConfig => {
        if (fieldConfig.el) {
            fieldConfig.el.addEventListener('input', () => {
                if (!mensajesGeneralesLimpiados) {
                    const msgsGlobal = document.getElementById('mensajesServidorGlobal');
                    if (msgsGlobal) {
                        const mensajesAEliminar = msgsGlobal.querySelectorAll('.alert:not(.error-servidor-especifico)');
                        mensajesAEliminar.forEach(msg => msg.remove());
                        const errorEspecificoActual = msgsGlobal.querySelector(`.error-servidor-especifico[data-campo-error="${fieldConfig.el.id}"]`) || msgsGlobal.querySelector(`.error-servidor-especifico[data-campo-error="${fieldConfig.el.name}"]`);
                        if(errorEspecificoActual) errorEspecificoActual.remove();
                    }
                    mensajesGeneralesLimpiados = true;
                }
                fieldConfig.el.dataset.interacted = "true";
                validateSingleField(fieldConfig, true); validateForm(false);
            });
            fieldConfig.el.addEventListener('blur', () => {
                fieldConfig.el.dataset.interacted = "true";
                validateSingleField(fieldConfig, true); validateForm(false);
            });
            if (fieldConfig.el.value && !errorServidorGlobal) {
                fieldConfig.el.dataset.interacted = "true"; 
                validateSingleField(fieldConfig, true); 
            }
        }
    });
    form.addEventListener('submit', (event) => {
        if (!validateForm(true)) {
            event.preventDefault();
            const firstInvalid = campos.find(f => f.el && f.el.classList.contains('is-invalid'));
            if (firstInvalid && firstInvalid.el) firstInvalid.el.focus();
        }
    });
    validateForm(false);
});