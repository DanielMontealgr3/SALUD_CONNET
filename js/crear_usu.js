document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formCrearUsuario');
    const paso1Div = document.getElementById('paso1');
    const paso2Div = document.getElementById('paso2');
    const btnSiguiente = document.getElementById('btnSiguiente');
    const btnAnterior = document.getElementById('btnAnterior');
    const btnCrearUsuario = form.querySelector('button[name="crear_usuario"]');
    const mensajesServidorGlobalDiv = document.getElementById('mensajesServidorGlobal');

    const idTipoDoc = document.getElementById('id_tipo_doc');
    const docUsu = document.getElementById('doc_usu');
    const fechaNac = document.getElementById('fecha_nac');
    const nomUsu = document.getElementById('nom_usu');
    const telUsu = document.getElementById('tel_usu');
    const correoUsu = document.getElementById('correo_usu');
    const idGen = document.getElementById('id_gen');

    const idDepSelect = document.getElementById('id_dep');
    const idMunSelect = document.getElementById('id_mun');
    const idBarrioSelect = document.getElementById('id_barrio');
    const direccionUsu = document.getElementById('direccion_usu');
    const idRolSelect = document.getElementById('id_rol');
    const idEspecialidadSelect = document.getElementById('id_especialidad');
    const idEstSelect = document.getElementById('id_est');
    const contrasena = document.getElementById('contraseña');
    
    const errorDocUsuDiv = docUsu.nextElementSibling; 
    const errorCorreoUsuDiv = correoUsu.nextElementSibling;

    const ID_ESPECIALIDAD_NO_APLICA_JS = 46; 
    const ID_ROL_MEDICO_JS = 4;
    let timerAjax; 
    let docVerificadoServidor = true; 
    let correoVerificadoServidor = true;


    const camposPaso1 = [
        {el: idTipoDoc, req: true, fn: null, name: "Tipo Documento", interacted: false, serverValidation: true},
        {el: docUsu, req: true, fn: v => {
            if (v === "") return {isValid: false, message: "Documento es requerido."};
            if (!/^\d+$/.test(v)) return {isValid: false, message: "Solo números permitidos."};
            if (v.length < 7 || v.length > 11) return {isValid: false, message: "Debe tener 7-11 dígitos."};
            return {isValid: true};
        }, name: "Documento", interacted: false, serverValidation: true},
        {el: fechaNac, req: true, fn: v => {
            if (v === "") return {isValid: false, message: "Fecha Nacimiento es requerida."};
            const hoy = new Date(); hoy.setHours(0,0,0,0);
            const fechaN = new Date(v + "T00:00:00Z"); 
            const minDate = new Date(hoy.getFullYear() - 120, hoy.getMonth(), hoy.getDate());
            if(isNaN(fechaN.getTime())) return {isValid: false, message: "Fecha inválida."};
            if(fechaN >= hoy) return {isValid: false, message: "Fecha debe ser anterior a hoy."};
            if(fechaN < minDate) return {isValid: false, message: "Fecha demasiado antigua."};
            return {isValid: true};
        }, name: "Fecha Nacimiento", interacted: false},
        {el: nomUsu, req: true, fn: v => {
            if (v === "") return {isValid: false, message: "Nombre es requerido."};
            if (!/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/.test(v)) return {isValid: false, message: "Solo letras y espacios."};
            if (v.length < 5 || v.length > 100) return {isValid: false, message: "Debe tener 5-100 caracteres."};
            return {isValid: true};
        }, name: "Nombre", interacted: false},
        {el: telUsu, req: false, fn: v => {
            if (v === "") return {isValid: true};
            if (!/^\d+$/.test(v)) return {isValid: false, message: "Solo números permitidos."};
            if (v.length < 7 || v.length > 11) return {isValid: false, message: "Debe tener 7-11 dígitos."};
            return {isValid: true};
        }, name: "Teléfono", interacted: false},
        {el: correoUsu, req: true, fn: v => {
             if (v === "") return {isValid: false, message: "Correo es requerido."};
             if(!/^\S+@\S+\.\S+$/.test(v)) return {isValid: false, message: "Formato de correo inválido."};
             return {isValid: true};
        }, name: "Correo", interacted: false, serverValidation: true},
        {el: idGen, req: true, fn: null, name: "Género", interacted: false}
    ];

    const camposPaso2 = [
        {el: idDepSelect, req: true, fn: null, name: "Departamento", interacted: false},
        {el: idMunSelect, req: true, fn: null, name: "Municipio", interacted: false},
        {el: idBarrioSelect, req: true, fn: null, name: "Barrio", interacted: false},
        {el: direccionUsu, req: false, fn: v => v.length <= 200 || v === "", name: "Dirección", msg: "Máximo 200 caracteres.", interacted: false},
        {el: idRolSelect, req: true, fn: null, name: "Rol", interacted: false},
        {el: idEspecialidadSelect, req: false, fn: null, name: "Especialidad", interacted: false},
        {el: idEstSelect, req: true, fn: null, name: "Estado Usuario", interacted: false},
        {el: contrasena, req: true, fn: v => {
            if (v === "") return {isValid: false, message: "Contraseña es requerida."};
            if (!(v.length >= 8 && /[a-z]/.test(v) && /[A-Z]/.test(v) && /\d/.test(v) && /[\W_]/.test(v))) return {isValid: false, message: "Mín 8: Mayús, minús, núm, símb."};
            return {isValid: true};
        }, name: "Contraseña", interacted: false}
    ];

    function setFieldValidationUI(el, isValid, message = "", forceShowError = false) {
        const feedbackEl = el.nextElementSibling;
        if (feedbackEl && feedbackEl.classList.contains('invalid-feedback')) {
            feedbackEl.textContent = message;
        }
        
        if (el.value.trim() === "" && !forceShowError && el.dataset.interacted !== "true") {
             el.classList.remove('is-valid', 'is-invalid');
             if (feedbackEl) feedbackEl.textContent = '';
             return;
        }

        if (isValid) {
            el.classList.remove('is-invalid');
            el.classList.add('is-valid');
        } else { 
            el.classList.remove('is-valid');
            el.classList.add('is-invalid');
        }
    }
    
    function validateSingleField(fieldConfig, forceShowError = false) {
        const { el, req, fn, name, msg, serverValidation } = fieldConfig;
        if (!el) return true;

        let result = { isValid: true, message: "" };
        const val = el.value.trim();

        if (req && val === "" && !el.disabled) {
            result = { isValid: false, message: `${name} es requerido.` };
        } else if (val !== "" && fn) {
            const fnRes = fn(el.value); 
            if (typeof fnRes === 'object') { 
                result = fnRes;
            } else { 
                result.isValid = fnRes;
                if (!fnRes) result.message = msg || `${name} inválido.`;
            }
        } else if (el.tagName === 'SELECT' && req && el.value === "" && !el.disabled){
             result = { isValid: false, message: `${name} es requerido.` };
        }
        
        if (serverValidation) {
            if (el.id === 'correo_usu' && !correoVerificadoServidor && result.isValid) {
                 setFieldValidationUI(el, false, errorCorreoUsuDiv.textContent || "Este correo ya está en uso.", true);
                 return false; 
            } else if (el.id === 'doc_usu' && !docVerificadoServidor && result.isValid) {
                setFieldValidationUI(el, false, errorDocUsuDiv.textContent || "Documento ya registrado.", true);
                return false;
            } else {
                 setFieldValidationUI(el, result.isValid, result.message, forceShowError || el.dataset.interacted === "true");
            }
        } else {
             setFieldValidationUI(el, result.isValid, result.message, forceShowError || el.dataset.interacted === "true");
        }
        return result.isValid;
    }
    
    function validateFormStep(stepFields, updateButton, forceShowAllErrors = false) {
        let isStepValid = true;
        stepFields.forEach(fieldConfig => {
            if (forceShowAllErrors) fieldConfig.el.dataset.interacted = "true";
            if (!validateSingleField(fieldConfig, forceShowAllErrors)) {
                isStepValid = false;
            }
        });
        
        if (stepFields === camposPaso1) {
            if(!docVerificadoServidor || !correoVerificadoServidor) isStepValid = false;
        }

        if(updateButton){
            if(stepFields === camposPaso1 && btnSiguiente){
                btnSiguiente.disabled = !isStepValid;
                btnSiguiente.classList.toggle('btn-success', isStepValid);
                btnSiguiente.classList.toggle('btn-secondary', !isStepValid);
            } else if (stepFields === camposPaso2 && btnCrearUsuario){
                const paso1EsValido = validateFormStep(camposPaso1, false, false) && docVerificadoServidor && correoVerificadoServidor;
                btnCrearUsuario.disabled = !isStepValid || !paso1EsValido;
                btnCrearUsuario.classList.toggle('btn-success', isStepValid && paso1EsValido);
                btnCrearUsuario.classList.toggle('btn-secondary', !isStepValid || !paso1EsValido);
            }
        }
        return isStepValid;
    }

    function debounce(func, delay) {
        clearTimeout(timerAjax);
        timerAjax = setTimeout(func, delay);
    }

    async function verificarCampoServidor(url, formData, campoEl, estadoGlobalFlagName, divErrorCampoAsociado) {
        divErrorCampoAsociado.textContent = "Verificando...";
        campoEl.classList.remove('is-valid', 'is-invalid');

        try {
            const response = await fetch(url, { method: 'POST', body: formData });
            if (!response.ok) throw new Error(`HTTP error ${response.status}`);
            const data = await response.json();

            if (data.isAvailable) {
                if(estadoGlobalFlagName === 'docVerificadoServidor') docVerificadoServidor = true;
                if(estadoGlobalFlagName === 'correoVerificadoServidor') correoVerificadoServidor = true;
                setFieldValidationUI(campoEl, true, "", true);
                divErrorCampoAsociado.textContent = ""; 
            } else {
                if(estadoGlobalFlagName === 'docVerificadoServidor') docVerificadoServidor = false;
                if(estadoGlobalFlagName === 'correoVerificadoServidor') correoVerificadoServidor = false;
                setFieldValidationUI(campoEl, false, data.message || "Dato ya en uso.", true);
            }
        } catch (error) {
            console.error(`Error verificando ${campoEl.id}:`, error);
            if(estadoGlobalFlagName === 'docVerificadoServidor') docVerificadoServidor = false;
            if(estadoGlobalFlagName === 'correoVerificadoServidor') correoVerificadoServidor = false;
            setFieldValidationUI(campoEl, false, `Error al verificar. (${error.message})`, true);
        }
        validateFormStep(camposPaso1, true, false);
    }
    
    function limpiarErroresServidorPHP() {
        if(mensajesServidorGlobalDiv){
            const erroresDuplicidadPHP = mensajesServidorGlobalDiv.querySelectorAll('.error-servidor-duplicidad');
            erroresDuplicidadPHP.forEach(error => error.remove());
        }
    }
    
    if(docUsu && idTipoDoc){
        const verificarDocDebounced = () => {
            limpiarErroresServidorPHP();
            const docVal = docUsu.value.trim();
            const tipoDocVal = idTipoDoc.value;
            
            if(errorDocUsuDiv) errorDocUsuDiv.textContent = ""; 
            docUsu.classList.remove('is-valid', 'is-invalid');

            if(docVal && tipoDocVal && /^\d{7,11}$/.test(docVal)){
                docVerificadoServidor = false; 
                const formData = new FormData();
                formData.append('doc_usu', docVal);
                formData.append('id_tipo_doc', tipoDocVal);
                debounce(() => verificarCampoServidor('../ajax/verificar_documento.php', formData, docUsu, 'docVerificadoServidor', errorDocUsuDiv), 800);
            } else {
                 docVerificadoServidor = !docUsu.required || (docUsu.required && docVal !== ""); // Si no es requerido y vacío, está ok
                 validateSingleField(camposPaso1.find(f => f.el === docUsu), true);
                 validateFormStep(camposPaso1, true, false);
            }
        };
        docUsu.addEventListener('input', () => { docUsu.dataset.interacted = "true"; verificarDocDebounced(); });
        idTipoDoc.addEventListener('change', () => { idTipoDoc.dataset.interacted = "true"; verificarDocDebounced(); });
    }

    if(correoUsu){
        const verificarCorreoDebounced = () => {
            limpiarErroresServidorPHP();
            const correoVal = correoUsu.value.trim();

            if(errorCorreoUsuDiv) errorCorreoUsuDiv.textContent = "";
            correoUsu.classList.remove('is-valid', 'is-invalid');

             if (correoVal && /^\S+@\S+\.\S+$/.test(correoVal)) {
                correoVerificadoServidor = false;
                const formData = new FormData();
                formData.append('correo', correoVal);
                debounce(() => verificarCampoServidor('../ajax/verificar_correo.php', formData, correoUsu, 'correoVerificadoServidor', errorCorreoUsuDiv), 800);
            } else {
                correoVerificadoServidor = !correoUsu.required || (correoUsu.required && correoVal !== "");
                validateSingleField(camposPaso1.find(f => f.el === correoUsu), true);
                validateFormStep(camposPaso1, true, false);
            }
        };
        correoUsu.addEventListener('input', () => { correoUsu.dataset.interacted = "true"; verificarCorreoDebounced(); });
    }


    if (btnSiguiente) {
        btnSiguiente.addEventListener('click', async () => {
            limpiarErroresServidorPHP();
            let todasLasValidacionesAjaxCompletas = true;
            
            const docVal = docUsu.value.trim();
            const tipoDocVal = idTipoDoc.value;
            if(docVal && tipoDocVal && /^\d{7,11}$/.test(docVal)){
                const formDataDoc = new FormData(); formDataDoc.append('doc_usu', docVal); formDataDoc.append('id_tipo_doc', tipoDocVal);
                await verificarCampoServidor('../ajax/verificar_documento.php', formDataDoc, docUsu, 'docVerificadoServidor', errorDocUsuDiv);
            } else if (docUsu.required && !docVal) {
                todasLasValidacionesAjaxCompletas = false;
            }


            const correoVal = correoUsu.value.trim();
            if (correoVal && /^\S+@\S+\.\S+$/.test(correoVal)) {
                const formDataCorreo = new FormData(); formDataCorreo.append('correo', correoVal);
                await verificarCampoServidor('../ajax/verificar_correo.php', formDataCorreo, correoUsu, 'correoVerificadoServidor', errorCorreoUsuDiv);
            } else if (correoUsu.required && !correoVal) {
                 todasLasValidacionesAjaxCompletas = false;
            }


            if (validateFormStep(camposPaso1, true, true) && docVerificadoServidor && correoVerificadoServidor && todasLasValidacionesAjaxCompletas) {
                paso1Div.style.display = 'none';
                paso2Div.style.display = 'block';
                validateFormStep(camposPaso2, true, false); 
            } else {
                 alert('Por favor, corrija los errores en el Paso 1.');
            }
        });
    }

    if (btnAnterior) {
        btnAnterior.addEventListener('click', () => {
            paso2Div.style.display = 'none';
            paso1Div.style.display = 'block';
            validateFormStep(camposPaso1, true, false); 
            btnCrearUsuario.disabled = true; 
            btnCrearUsuario.classList.remove('btn-success');
            btnCrearUsuario.classList.add('btn-secondary');
        });
    }

    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault(); 
            limpiarErroresServidorPHP();
            let todasLasValidacionesAjaxCompletasSubmit = true;

            const docVal = docUsu.value.trim();
            const tipoDocVal = idTipoDoc.value;
            if(docVal && tipoDocVal && /^\d{7,11}$/.test(docVal)){
                 const formDataDoc = new FormData(); formDataDoc.append('doc_usu', docVal); formDataDoc.append('id_tipo_doc', tipoDocVal);
                await verificarCampoServidor('../ajax/verificar_documento.php', formDataDoc, docUsu, 'docVerificadoServidor', errorDocUsuDiv);
            } else if (docUsu.required && !docVal) {
                 todasLasValidacionesAjaxCompletasSubmit = false;
            }

             const correoVal = correoUsu.value.trim();
            if (correoVal && /^\S+@\S+\.\S+$/.test(correoVal)) {
                const formDataCorreo = new FormData(); formDataCorreo.append('correo', correoVal);
                await verificarCampoServidor('../ajax/verificar_correo.php', formDataCorreo, correoUsu, 'correoVerificadoServidor', errorCorreoUsuDiv);
            } else if (correoUsu.required && !correoVal) {
                 todasLasValidacionesAjaxCompletasSubmit = false;
            }

            const paso1Valido = validateFormStep(camposPaso1, false, true) && docVerificadoServidor && correoVerificadoServidor && todasLasValidacionesAjaxCompletasSubmit; 
            const paso2Valido = validateFormStep(camposPaso2, true, true);  

            if (!paso1Valido || !paso2Valido) {
                alert('Por favor, corrija los errores en el formulario.');
                if (!paso1Valido && paso2Div.style.display !== 'none') {
                    paso2Div.style.display = 'none';
                    paso1Div.style.display = 'block';
                    validateFormStep(camposPaso1, true, true); 
                }
            } else {
                btnCrearUsuario.disabled = true;
                btnCrearUsuario.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creando...';
                form.submit(); 
            }
        });
    }

    camposPaso1.concat(camposPaso2).forEach(fieldConfig => {
        if(fieldConfig.el && !fieldConfig.serverValidation){ 
            const eventType = (fieldConfig.el.tagName === 'SELECT' || fieldConfig.el.type === 'date') ? 'change' : 'input';
            fieldConfig.el.addEventListener(eventType, () => {
                limpiarErroresServidorPHP();
                fieldConfig.el.dataset.interacted = "true";
                validateSingleField(fieldConfig, true); 
                if(paso1Div.style.display !== 'none'){ 
                    validateFormStep(camposPaso1, true, false); 
                }
                if(paso2Div.style.display !== 'none'){ 
                    validateFormStep(camposPaso2, true, false); 
                }
            });
            if (fieldConfig.el.type === 'text' || fieldConfig.el.type === 'password') {
                fieldConfig.el.addEventListener('blur', () => {
                    limpiarErroresServidorPHP();
                    fieldConfig.el.dataset.interacted = "true";
                    validateSingleField(fieldConfig, true);
                    if(paso1Div.style.display !== 'none') validateFormStep(camposPaso1, true, false);
                    if(paso2Div.style.display !== 'none') validateFormStep(camposPaso2, true, false);
                });
            }
        }
    });
    
    function cargarOpciones(selectElement, opciones, placeholder, valorActual = null) {
        selectElement.innerHTML = `<option value="">${placeholder}</option>`;
        let valorEncontrado = false;
        opciones.forEach(opcion => {
            const optionTag = document.createElement('option');
            optionTag.value = String(opcion.id);
            optionTag.textContent = opcion.nombre;
            if (valorActual !== null && String(valorActual) === String(opcion.id)) {
                 optionTag.selected = true; valorEncontrado = true;
            }
            selectElement.appendChild(optionTag);
        });
        selectElement.disabled = false;
        selectElement.title = '';
        selectElement.value = valorEncontrado ? String(valorActual) : "";
        setFieldValidationUI(selectElement, true, "", false); 
    }

    function resetSelect(selectElement, placeholder, tooltip) {
         setFieldValidationUI(selectElement, true, "", false); 
         selectElement.innerHTML = `<option value="">${placeholder}</option>`;
         selectElement.disabled = true;
         selectElement.title = tooltip;
    }

    if (idDepSelect) {
        idDepSelect.addEventListener('change', function() {
            this.dataset.interacted = "true"; 
            const idDep = this.value;
            resetSelect(idBarrioSelect, 'Seleccione municipio...', 'Seleccione un municipio primero');
            idBarrioSelect.dataset.interacted = "false"; 
            if (idDep) {
                 idMunSelect.disabled = false; idMunSelect.title = '';
                 idMunSelect.dataset.interacted = "false"; 
                 idMunSelect.innerHTML = '<option value="">Cargando municipios...</option>';
                 fetch(`../ajax/get_municipios.php?id_dep=${encodeURIComponent(idDep)}`)
                    .then(response => response.ok ? response.json() : Promise.reject(`Error ${response.status}`))
                    .then(data => {
                        if (data && Array.isArray(data)) {
                            if (data.length > 0) { cargarOpciones(idMunSelect, data, 'Seleccione municipio...', null); }
                            else { resetSelect(idMunSelect, 'No hay municipios', 'No hay municipios para este departamento'); }
                        } else { throw new Error('Formato de respuesta inesperado (municipios)'); }
                        idMunSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    })
                    .catch(error => {
                        console.error(`Error cargando municipios: ${error}`);
                        resetSelect(idMunSelect, 'Error al cargar', 'Error de red, servidor o formato');
                        idMunSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    });
            } else {
                resetSelect(idMunSelect, 'Seleccione departamento...', 'Seleccione un departamento primero');
                 idMunSelect.dataset.interacted = "false";
                idMunSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
            validateFormStep(camposPaso2, true, false);
        });
    }

    if (idMunSelect) {
        idMunSelect.addEventListener('change', function() {
            this.dataset.interacted = "true";
            const idMun = this.value;
            if (idMun && !idMunSelect.disabled) {
                idBarrioSelect.disabled = false; idBarrioSelect.title = '';
                idBarrioSelect.dataset.interacted = "false";
                idBarrioSelect.innerHTML = '<option value="">Cargando barrios...</option>';
                fetch(`../ajax/get_barrios.php?id_mun=${encodeURIComponent(idMun)}`)
                    .then(response => response.ok ? response.json() : Promise.reject(`Error ${response.status}`))
                    .then(data => {
                         if (data && Array.isArray(data)) {
                            if (data.length > 0) { cargarOpciones(idBarrioSelect, data, 'Seleccione barrio...', null); }
                            else { resetSelect(idBarrioSelect, 'No hay barrios', 'No hay barrios para este municipio'); }
                        } else { throw new Error('Formato de respuesta inesperado (barrios)'); }
                    })
                    .catch(error => {
                         console.error(`Error cargando barrios: ${error}`);
                         resetSelect(idBarrioSelect, 'Error al cargar', 'Error de red, servidor o formato');
                    });
            } else {
                 resetSelect(idBarrioSelect, 'Seleccione municipio...', 'Seleccione un municipio primero');
                 idBarrioSelect.dataset.interacted = "false";
            }
            validateFormStep(camposPaso2, true, false);
        });
    }

    function manejarEspecialidad() {
        if (!idRolSelect || !idEspecialidadSelect) return;
        const selectedRolId = parseInt(idRolSelect.value);
        setFieldValidationUI(idEspecialidadSelect, true, "", false);
        idEspecialidadSelect.title = '';
        
        const espConfig = camposPaso2.find(f => f.el === idEspecialidadSelect);

        if (selectedRolId === ID_ROL_MEDICO_JS) {
            idEspecialidadSelect.disabled = false;
            if(espConfig) espConfig.req = true;
            let noAplicaOption = null;
            for (let option of idEspecialidadSelect.options) {
                if (parseInt(option.value) === ID_ESPECIALIDAD_NO_APLICA_JS) {
                    option.style.display = 'none'; noAplicaOption = option;
                } else { option.style.display = ''; }
            }
            if (noAplicaOption && noAplicaOption.selected) { idEspecialidadSelect.value = ""; }
            const placeholderOption = idEspecialidadSelect.querySelector('option[value=""]');
            if (placeholderOption) placeholderOption.textContent = "Seleccione especialidad (*)";
            if (idEspecialidadSelect.value === "" || parseInt(idEspecialidadSelect.value || 0) === ID_ESPECIALIDAD_NO_APLICA_JS) {
                 idEspecialidadSelect.value = "";
            }
        } else if (idRolSelect.value !== "") {
            idEspecialidadSelect.disabled = true; idEspecialidadSelect.title = 'Aplica solo para rol Médico';
            if(espConfig) espConfig.req = false;
            const noAplicaValueString = String(ID_ESPECIALIDAD_NO_APLICA_JS);
            let noAplicaOptionExists = false;
            for (let option of idEspecialidadSelect.options) {
                if (option.value === noAplicaValueString) {
                    option.selected = true; option.style.display = ''; noAplicaOptionExists = true;
                } else { option.style.display = 'none';}
            }
            if (!noAplicaOptionExists) {
                let placeholderOpt = idEspecialidadSelect.querySelector('option[value=""]');
                if (placeholderOpt) { placeholderOpt.textContent = "No aplica"; idEspecialidadSelect.value = "";}
                else { idEspecialidadSelect.value = ""; }
            } else { idEspecialidadSelect.value = noAplicaValueString; }
        } else {
            idEspecialidadSelect.disabled = true; idEspecialidadSelect.title = 'Seleccione un rol primero';
            if(espConfig) espConfig.req = false;
            idEspecialidadSelect.value = "";
            const placeholderOption = idEspecialidadSelect.querySelector('option[value=""]');
            if (placeholderOption) placeholderOption.textContent = "Seleccione rol...";
            for (let option of idEspecialidadSelect.options) { option.style.display = ''; }
        }
        if(selectedRolId !== ID_ROL_MEDICO_JS) setFieldValidationUI(idEspecialidadSelect, true, "", false);
    }

    if (idRolSelect) { 
        idRolSelect.addEventListener('change', () => {
            idRolSelect.dataset.interacted = "true";
            manejarEspecialidad();
            validateFormStep(camposPaso2, true, false);
        });
    }
    
    if (idDepSelect && idDepSelect.value) {
        const idMunActualPHP = idMunSelect.value; 
        const idBarrioActualPHP = idBarrioSelect.value;

        if (idMunActualPHP) {
            if (idBarrioActualPHP && idBarrioSelect.options.length > 1) {
            } else if (idBarrioSelect.options.length <=1){ 
                 setTimeout(() => idMunSelect.dispatchEvent(new Event('change', {bubbles: true})), 0);
            }
        } else if (idMunSelect.options.length <=1 ){ 
            setTimeout(() => idDepSelect.dispatchEvent(new Event('change', {bubbles:true})), 0);
        }
    } else {
        if(idMunSelect) resetSelect(idMunSelect, 'Seleccione departamento...', 'Seleccione un departamento primero');
        if(idBarrioSelect) resetSelect(idBarrioSelect, 'Seleccione municipio...', 'Seleccione un municipio primero');
    }
    manejarEspecialidad();
    btnSiguiente.disabled = true; 
    btnSiguiente.classList.remove('btn-success');
    btnSiguiente.classList.add('btn-secondary');
    btnCrearUsuario.disabled = true;
    btnCrearUsuario.classList.remove('btn-success');
    btnCrearUsuario.classList.add('btn-secondary');
});