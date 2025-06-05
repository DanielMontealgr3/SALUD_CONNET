document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formCrearUsuario');
    const paso1Div = document.getElementById('paso1');
    const paso2Div = document.getElementById('paso2');
    const btnSiguiente = document.getElementById('btnSiguiente');
    const btnAnterior = document.getElementById('btnAnterior');
    const btnCrearUsuario = form.querySelector('button[name="crear_usuario"]');
    const modalSendingEmail = document.getElementById('modalSendingEmail');

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

    const ID_ESPECIALIDAD_NO_APLICA_JS = 46;
    const ID_ROL_MEDICO_JS = 4;
    const ID_TIPO_DOC_CEDULA_CIUDADANIA_JS = 1;
    const ID_TIPO_DOC_TARJETA_IDENTIDAD_JS = 2;
    const ID_TIPO_DOC_REGISTRO_CIVIL_JS = 3;

    let mensajesGeneralesLimpiados = false;

    function validateFechaNac(v) {
        if (v === "") return { isValid: false, message: "Fecha Nacimiento es requerida." };
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        const ayer = new Date(hoy);
        ayer.setDate(ayer.getDate() - 1);
        
        const fechaNacDate = new Date(v + "T00:00:00Z"); 
        if (isNaN(fechaNacDate.getTime())) return { isValid: false, message: "Fecha inválida." };

        const minDateAllowed = new Date(hoy.getFullYear() - 120, hoy.getMonth(), hoy.getDate());
         minDateAllowed.setHours(0,0,0,0);


        if (fechaNacDate >= hoy) return { isValid: false, message: "Debe ser anterior a hoy." };
        if (fechaNacDate < minDateAllowed) return { isValid: false, message: "Fecha muy antigua (máx 120 años)." };

        const tipoDocSeleccionado = parseInt(idTipoDoc.value);

        if (tipoDocSeleccionado) {
            const fechaCumple7 = new Date(fechaNacDate);
            fechaCumple7.setFullYear(fechaNacDate.getFullYear() + 7);
            const fechaCumple18 = new Date(fechaNacDate);
            fechaCumple18.setFullYear(fechaNacDate.getFullYear() + 18);

            if (tipoDocSeleccionado === ID_TIPO_DOC_CEDULA_CIUDADANIA_JS) {
                if (fechaCumple18 > ayer) return { isValid: false, message: "Para C.C., debe tener 18 años cumplidos ayer." };
            } else if (tipoDocSeleccionado === ID_TIPO_DOC_TARJETA_IDENTIDAD_JS) {
                if (fechaCumple7 > ayer) return { isValid: false, message: "Para T.I., debe tener 7 años cumplidos ayer." };
                if (fechaCumple18 <= ayer) return { isValid: false, message: "Para T.I., debe ser menor de 18 años." };
            } else if (tipoDocSeleccionado === ID_TIPO_DOC_REGISTRO_CIVIL_JS) {
                if (fechaCumple7 <= ayer) return { isValid: false, message: "Para R.C., debe ser menor de 7 años." };
            }
        }
        return { isValid: true };
    }

    const camposPaso1 = [
        { el: idTipoDoc, req: true, fn: null, name: "Tipo Documento", interacted: false },
        { el: docUsu, req: true, fn: v => {
            if (v === "") return { isValid: false, message: "Documento es requerido." };
            if (!/^\d+$/.test(v)) return { isValid: false, message: "Solo números permitidos." };
            if (v.length < 7 || v.length > 11) return { isValid: false, message: "Debe tener 7-11 dígitos." };
            if (/^0+$/.test(v)) return { isValid: false, message: "No puede ser solo ceros." };
            if (/^(\d)\1+$/.test(v)) return { isValid: false, message: "No puede ser el mismo dígito repetido."};
            return { isValid: true };
        }, name: "Documento", interacted: false },
        { el: fechaNac, req: true, fn: validateFechaNac, name: "Fecha Nacimiento", interacted: false },
        { el: nomUsu, req: true, fn: v => {
            if (v === "") return { isValid: false, message: "Nombre es requerido." };
            if (!/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/.test(v)) return { isValid: false, message: "Solo letras y espacios." };
            if (v.length < 5 || v.length > 100) return { isValid: false, message: "Debe tener 5-100 caracteres." };
            return { isValid: true };
        }, name: "Nombre", interacted: false },
        { el: telUsu, req: true, fn: v => {
            if (v === "") return { isValid: false, message: "Teléfono es requerido." };
            if (!/^\d+$/.test(v)) return { isValid: false, message: "Solo números permitidos." };
            if (v.length < 7 || v.length > 11) return { isValid: false, message: "Debe tener 7-11 dígitos." };
            return { isValid: true };
        }, name: "Teléfono", interacted: false },
        { el: correoUsu, req: true, fn: v => {
            if (v === "") return { isValid: false, message: "Correo es requerido." };
            if (!/^\S+@\S+\.\S+$/.test(v)) return { isValid: false, message: "Formato de correo inválido." };
            if (v.length > 150) return { isValid: false, message: "Máx 150 caracteres." };
            return { isValid: true };
        }, name: "Correo", interacted: false },
        { el: idGen, req: true, fn: null, name: "Género", interacted: false }
    ];

    const camposPaso2 = [
        { el: idDepSelect, req: true, fn: null, name: "Departamento", interacted: false },
        { el: idMunSelect, req: true, fn: null, name: "Municipio", interacted: false },
        { el: idBarrioSelect, req: true, fn: null, name: "Barrio", interacted: false },
        { el: direccionUsu, req: false, fn: v => {
            if (v === "") return { isValid: true };
            if (v.length > 200) return { isValid: false, message: "Máx 200 caracteres." };
            return { isValid: true };
        }, name: "Dirección", interacted: false },
        { el: idRolSelect, req: true, fn: null, name: "Rol", interacted: false },
        { el: idEspecialidadSelect, req: false, fn: null, name: "Especialidad", interacted: false },
        { el: idEstSelect, req: true, fn: null, name: "Estado Usuario", interacted: false },
        { el: contrasena, req: true, fn: v => {
            if (v === "") return { isValid: false, message: "Contraseña es requerida." };
            if (!(v.length >= 8 && /[a-z]/.test(v) && /[A-Z]/.test(v) && /\d/.test(v) && /[\W_]/.test(v))) return { isValid: false, message: "Mín 8: Mayús, minús, núm, símb." };
            if (v.length > 200) return { isValid: false, message: "Máx 200 caracteres." };
            return { isValid: true };
        }, name: "Contraseña", interacted: false }
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
        } else if (val !== "" && fn) {
            const fnRes = fn(el.value); 
            if (typeof fnRes === 'object') {
                result = fnRes;
            } else {
                result.isValid = fnRes;
                if (!fnRes) result.message = fieldConfig.msg || `${name} inválido.`;
            }
        } else if (el.tagName === 'SELECT' && req && el.value === "") {
            result = { isValid: false, message: `${name} es requerido.` };
        }
        
        setFieldValidationUI(el, result.isValid, result.message, forceShowError || el.dataset.interacted === "true");
        return result.isValid;
    }
    
    function validateFormStep(stepFields, updateButton, forceShowAllErrors = false) {
        let isStepValid = true;
        stepFields.forEach(fieldConfig => {
            if (forceShowAllErrors && fieldConfig.el) fieldConfig.el.dataset.interacted = "true";
            if (!validateSingleField(fieldConfig, forceShowAllErrors)) {
                isStepValid = false;
            }
        });

        if (updateButton) {
            const targetButton = (stepFields === camposPaso1) ? btnSiguiente : btnCrearUsuario;
            if (targetButton) {
                targetButton.disabled = !isStepValid;
                targetButton.classList.toggle('btn-success', isStepValid);
                targetButton.classList.toggle('btn-secondary', !isStepValid);
            }
        }
        return isStepValid;
    }
    
    if (btnSiguiente) {
        btnSiguiente.addEventListener('click', () => {
            if (validateFormStep(camposPaso1, true, true)) {
                paso1Div.style.display = 'none';
                paso2Div.style.display = 'block';
                validateFormStep(camposPaso2, true, false);
                 const firstInteractiveP2 = camposPaso2.find(f => f.el && !f.el.disabled && f.el.tabIndex > 0 && f.el.offsetHeight > 0);
                 if (firstInteractiveP2 && firstInteractiveP2.el) firstInteractiveP2.el.focus();
            } else {
                const firstInvalid = camposPaso1.find(f => f.el && f.el.classList.contains('is-invalid'));
                if (firstInvalid && firstInvalid.el) firstInvalid.el.focus();
            }
        });
    }

    if (btnAnterior) {
        btnAnterior.addEventListener('click', () => {
            paso2Div.style.display = 'none';
            paso1Div.style.display = 'block';
            validateFormStep(camposPaso1, true, false);
            if (btnCrearUsuario) {
                btnCrearUsuario.disabled = true;
                btnCrearUsuario.classList.remove('btn-success');
                btnCrearUsuario.classList.add('btn-secondary');
            }
            const firstInteractiveP1 = camposPaso1.find(f => f.el && !f.el.disabled && f.el.tabIndex > 0 && f.el.offsetHeight > 0);
            if (firstInteractiveP1 && firstInteractiveP1.el) firstInteractiveP1.el.focus();
        });
    }

    if (form) {
        form.addEventListener('submit', (event) => {
            const paso1Valido = validateFormStep(camposPaso1, false, true);
            const paso2Valido = validateFormStep(camposPaso2, true, true);

            if (!paso1Valido || !paso2Valido) {
                event.preventDefault();
                if (!paso1Valido && paso2Div.style.display !== 'none') {
                    paso2Div.style.display = 'none';
                    paso1Div.style.display = 'block';
                    validateFormStep(camposPaso1, true, true);
                    const firstInvalidP1 = camposPaso1.find(f => f.el && f.el.classList.contains('is-invalid'));
                    if (firstInvalidP1 && firstInvalidP1.el) firstInvalidP1.el.focus();

                } else if (!paso2Valido) {
                    const firstInvalidP2 = camposPaso2.find(f => f.el && f.el.classList.contains('is-invalid'));
                    if (firstInvalidP2 && firstInvalidP2.el) firstInvalidP2.el.focus();
                }
            } else {
                if (modalSendingEmail) {
                    modalSendingEmail.style.display = 'block';
                }
            }
        });
    }

    camposPaso1.concat(camposPaso2).forEach(fieldConfig => {
        if (fieldConfig.el) {
            const eventType = (fieldConfig.el.tagName === 'SELECT' || fieldConfig.el.type === 'date') ? 'change' : 'input';
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
                
                if (fieldConfig.el === idTipoDoc || fieldConfig.el === fechaNac) {
                    const fechaNacConfig = camposPaso1.find(f => f.el === fechaNac);
                    if (fechaNacConfig) {
                         fechaNac.dataset.interacted = "true"; 
                         validateSingleField(fechaNacConfig, true);
                    }
                }
                validateSingleField(fieldConfig, true);
                
                if (paso1Div.style.display !== 'none') {
                    validateFormStep(camposPaso1, true, false);
                }
                if (paso2Div.style.display !== 'none') {
                    validateFormStep(camposPaso2, true, false);
                }
            });
            if (fieldConfig.el.type === 'text' || fieldConfig.el.type === 'email' || fieldConfig.el.type === 'password') {
                fieldConfig.el.addEventListener('blur', () => {
                    fieldConfig.el.dataset.interacted = "true";
                    validateSingleField(fieldConfig, true);
                    if (paso1Div.style.display !== 'none') validateFormStep(camposPaso1, true, false);
                    if (paso2Div.style.display !== 'none') validateFormStep(camposPaso2, true, false);
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

    function resetSelect(selectElement, placeholder, tooltipIfDisabled) {
        selectElement.innerHTML = `<option value="">${placeholder}</option>`;
        selectElement.disabled = true;
        selectElement.title = tooltipIfDisabled;
        setFieldValidationUI(selectElement, true, "", false);
        selectElement.dataset.interacted = "false";
    }

    if (idDepSelect) {
        idDepSelect.addEventListener('change', function() {
            this.dataset.interacted = "true";
            const idDep = this.value;
            resetSelect(idMunSelect, 'Seleccione departamento...', 'Seleccione un departamento primero');
            resetSelect(idBarrioSelect, 'Seleccione municipio...', 'Seleccione un municipio primero');
            
            if (idDep) {
                idMunSelect.disabled = false; idMunSelect.title = '';
                idMunSelect.innerHTML = '<option value="">Cargando municipios...</option>';
                fetch(`../ajax/get_municipios.php?id_dep=${encodeURIComponent(idDep)}`)
                    .then(response => response.ok ? response.json() : Promise.reject({ status: response.status, text: response.statusText }))
                    .then(data => {
                        if (data && Array.isArray(data)) {
                            const valorMunActual = idMunSelect.dataset.valorPhp || null;
                            cargarOpciones(idMunSelect, data, 'Seleccione municipio...', valorMunActual);
                            if (valorMunActual) idMunSelect.dispatchEvent(new Event('change', { bubbles: true }));
                            idMunSelect.dataset.valorPhp = '';
                        } else { throw new Error('Formato de respuesta inesperado (municipios)'); }
                    })
                    .catch(error => {
                        console.error(`Error cargando municipios: ${error.status} ${error.text}`, error);
                        resetSelect(idMunSelect, 'Error al cargar', 'Error de red o servidor');
                    })
                    .finally(() => {
                        validateFormStep(camposPaso2, true, false);
                    });
            } else {
                validateFormStep(camposPaso2, true, false);
            }
        });
    }

    if (idMunSelect) {
        idMunSelect.addEventListener('change', function() {
            this.dataset.interacted = "true";
            const idMun = this.value;
            resetSelect(idBarrioSelect, 'Seleccione municipio...', 'Seleccione un municipio primero');

            if (idMun && !idMunSelect.disabled) {
                idBarrioSelect.disabled = false; idBarrioSelect.title = '';
                idBarrioSelect.innerHTML = '<option value="">Cargando barrios...</option>';
                fetch(`../ajax/get_barrios.php?id_mun=${encodeURIComponent(idMun)}`)
                    .then(response => response.ok ? response.json() : Promise.reject({ status: response.status, text: response.statusText }))
                    .then(data => {
                        if (data && Array.isArray(data)) {
                            const valorBarrioActual = idBarrioSelect.dataset.valorPhp || null;
                            cargarOpciones(idBarrioSelect, data, 'Seleccione barrio...', valorBarrioActual);
                            idBarrioSelect.dataset.valorPhp = '';
                        } else { throw new Error('Formato de respuesta inesperado (barrios)'); }
                    })
                    .catch(error => {
                        console.error(`Error cargando barrios: ${error.status} ${error.text}`, error);
                        resetSelect(idBarrioSelect, 'Error al cargar', 'Error de red o servidor');
                    })
                    .finally(() => {
                        validateFormStep(camposPaso2, true, false);
                    });
            } else {
                validateFormStep(camposPaso2, true, false);
            }
        });
    }

    function manejarEspecialidad() {
        if (!idRolSelect || !idEspecialidadSelect) return;
        const selectedRolId = parseInt(idRolSelect.value);
        const espConfig = camposPaso2.find(f => f.el === idEspecialidadSelect);

        if (selectedRolId === ID_ROL_MEDICO_JS) {
            idEspecialidadSelect.disabled = false;
            idEspecialidadSelect.title = 'Seleccione especialidad';
            if (espConfig) espConfig.req = true;

            let noAplicaOption = null;
            for (let option of idEspecialidadSelect.options) {
                if (parseInt(option.value) === ID_ESPECIALIDAD_NO_APLICA_JS) {
                    option.style.display = 'none'; noAplicaOption = option;
                } else { option.style.display = ''; }
            }
            if (idEspecialidadSelect.value === String(ID_ESPECIALIDAD_NO_APLICA_JS)) {
                idEspecialidadSelect.value = "";
            }
            const placeholderOption = idEspecialidadSelect.querySelector('option[value=""]');
            if (placeholderOption) placeholderOption.textContent = "Seleccione especialidad (*)";

        } else if (idRolSelect.value !== "") {
            idEspecialidadSelect.disabled = true;
            idEspecialidadSelect.title = 'No aplica para este rol';
            if (espConfig) espConfig.req = false;
            
            let noAplicaOption = idEspecialidadSelect.querySelector(`option[value="${ID_ESPECIALIDAD_NO_APLICA_JS}"]`);
            if (noAplicaOption) {
                noAplicaOption.selected = true;
                noAplicaOption.style.display = '';
            } else {
                const opt = document.createElement('option');
                opt.value = ID_ESPECIALIDAD_NO_APLICA_JS;
                opt.text = "No Aplica"; 
                opt.selected = true;
                idEspecialidadSelect.add(opt, 0);
            }
            for (let option of idEspecialidadSelect.options) {
                if (parseInt(option.value) !== ID_ESPECIALIDAD_NO_APLICA_JS && option.value !== "") {
                    option.style.display = 'none';
                }
            }
            setFieldValidationUI(idEspecialidadSelect, true, "", false);
        } else {
            idEspecialidadSelect.disabled = true;
            idEspecialidadSelect.title = 'Seleccione un rol primero';
            if (espConfig) espConfig.req = false;
            idEspecialidadSelect.value = "";
            const placeholderOption = idEspecialidadSelect.querySelector('option[value=""]');
            if (placeholderOption) placeholderOption.textContent = "Seleccione rol...";
            for (let option of idEspecialidadSelect.options) { option.style.display = ''; }
            setFieldValidationUI(idEspecialidadSelect, true, "", false);
        }
    }

    if (idRolSelect) {
        idRolSelect.addEventListener('change', () => {
            idRolSelect.dataset.interacted = "true";
            manejarEspecialidad();
            validateFormStep(camposPaso2, true, false);
        });
    }
    
    if (idMunSelect && idMunSelect.value) idMunSelect.dataset.valorPhp = idMunSelect.value;
    if (idBarrioSelect && idBarrioSelect.value) idBarrioSelect.dataset.valorPhp = idBarrioSelect.value;

    if (idDepSelect && idDepSelect.value) {
        idDepSelect.dispatchEvent(new Event('change', { bubbles: true }));
    } else {
        if (idMunSelect) resetSelect(idMunSelect, 'Seleccione departamento...', 'Seleccione un departamento primero');
        if (idBarrioSelect) resetSelect(idBarrioSelect, 'Seleccione municipio...', 'Seleccione un municipio primero');
    }
    
    manejarEspecialidad();

    if (btnSiguiente) {
        btnSiguiente.disabled = true;
        btnSiguiente.classList.remove('btn-success');
        btnSiguiente.classList.add('btn-secondary');
    }
    if (btnCrearUsuario) {
        btnCrearUsuario.disabled = true;
        btnCrearUsuario.classList.remove('btn-success');
        btnCrearUsuario.classList.add('btn-secondary');
    }
    
    camposPaso1.forEach(fc => { if (fc.el && fc.el.value && !errorServidorEspecificoDiv) { fc.el.dataset.interacted = "true"; validateSingleField(fc, true); }});
    camposPaso2.forEach(fc => { if (fc.el && fc.el.value && !fc.el.disabled && !errorServidorEspecificoDiv) { fc.el.dataset.interacted = "true"; validateSingleField(fc, true); }});
    
    validateFormStep(camposPaso1, true, false); 
    validateFormStep(camposPaso2, true, false);


    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('correo_status') && !urlParams.has('doc_creado') && !errorServidorEspecificoDiv) {
        const camposConValor = camposPaso1.concat(camposPaso2).some(c => c.el && c.el.value.trim() !== '');
        if (camposConValor) {
            mensajesGeneralesLimpiados = true;
        }
    }
});