document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formCrearBarrio');
    const btnCrearBarrio = form.querySelector('button[name="insertar_barrio"]');
    const idDepSelect = document.getElementById('id_dep');
    const idMunSelect = document.getElementById('id_mun');
    const nomBarrioInput = document.getElementById('nom_barrio');
    const mensajeNoMunicipios = document.getElementById('mensajeNoMunicipios');

    console.log('Script crear_barrio.js cargado.');
    console.log('PHP Initial Data:', typeof phpInitialData !== 'undefined' ? phpInitialData : 'phpInitialData no definido');

    let mensajesGeneralesLimpiados = false;
    const formularioDeshabilitadoPorNoDep = (typeof phpInitialData !== 'undefined' && phpInitialData.formularioDeshabilitadoNoDep === true);

    const campos = [
        { el: idDepSelect, req: true, fn: null, name: "Departamento" },
        { el: idMunSelect, req: true, fn: null, name: "Municipio" },
        {
            el: nomBarrioInput,
            req: true,
            fn: v => {
                if (v === "") return { isValid: false, message: "Nombre Barrio es requerido." };
                if (!/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s\-\#\.]+$/u.test(v)) return { isValid: false, message: "Nombre solo letras, números, espacios y . - #" };
                if (v.length < 3 || v.length > 120) return { isValid: false, message: "Nombre 3-120 caracteres." };
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
        if (el.id === 'id_mun' && el.nextElementSibling && el.nextElementSibling.id === 'mensajeNoMunicipios') {
        }

        if (feedbackEl && feedbackEl.classList.contains('invalid-feedback')) {
            feedbackEl.textContent = message;
        }

        if (el.disabled) {
            el.classList.remove('is-valid', 'is-invalid');
            if (feedbackEl && feedbackEl.classList.contains('invalid-feedback')) feedbackEl.textContent = '';
            return;
        }

        const hasInteracted = el.dataset.interacted === "true";
        if (isValid && el.value.trim() !== "" && (hasInteracted || forceShowError)) {
            el.classList.remove('is-invalid'); el.classList.add('is-valid');
        } else if (!isValid && (hasInteracted || forceShowError)) {
            el.classList.remove('is-valid'); el.classList.add('is-invalid');
        } else {
            el.classList.remove('is-valid', 'is-invalid');
            if (feedbackEl && feedbackEl.classList.contains('invalid-feedback')) feedbackEl.textContent = '';
        }
    }

    function validateSingleField(fieldConfig, forceShowError = false) {
        const { el, req, fn, name } = fieldConfig;
        if (!el || el.disabled) return true;
        let result = { isValid: true, message: "" };
        const val = el.value.trim();

        if (req && val === "") result = { isValid: false, message: `${name} es requerido.` };
        else if (fn && val !== "") {
            const fnRes = fn(el.value);
            if (typeof fnRes === 'object') result = fnRes;
            else { result.isValid = fnRes; if (!fnRes) result.message = `${name} inválido.`;}
        } else if (el.tagName === 'SELECT' && req && el.value === "") {
            result = { isValid: false, message: `${name} es requerido.` };
        }
        setFieldValidationUI(el, result.isValid, result.message, forceShowError);
        return result.isValid;
    }

    function updateFormState() {
        if (formularioDeshabilitadoPorNoDep) {
            if (btnCrearBarrio) btnCrearBarrio.disabled = true;
            campos.forEach(fc => { if (fc.el) fc.el.disabled = true; });
            return false;
        }

        let isFormValid = true;
        campos.forEach(fieldConfig => {
            if (!validateSingleField(fieldConfig, fieldConfig.el.dataset.interacted === "true")) {
                isFormValid = false;
            }
        });

        if (!idMunSelect.disabled && idMunSelect.value === "") {
            if (idDepSelect.value !== "") {
                 isFormValid = false;
            }
        }
        if (!idMunSelect.disabled && idMunSelect.options.length === 1 && idMunSelect.options[0].value === "" && idMunSelect.options[0].textContent.includes("No hay municipios")) {
            isFormValid = false;
        }
        if (!nomBarrioInput.disabled && nomBarrioInput.value.trim() === "") {
            isFormValid = false;
        }

        if (btnCrearBarrio) {
            btnCrearBarrio.disabled = !isFormValid;
            btnCrearBarrio.classList.toggle('btn-success', isFormValid);
            btnCrearBarrio.classList.toggle('btn-primary', !isFormValid);
        }
        console.log('updateFormState - isFormValid:', isFormValid, 'Button disabled:', btnCrearBarrio ? btnCrearBarrio.disabled : 'N/A');
        return isFormValid;
    }

    function cargarMunicipios(idDep, valorActualMun = null, isInitialLoad = false) {
        console.log('cargarMunicipios - idDep:', idDep, 'valorActualMun:', valorActualMun, 'isInitialLoad:', isInitialLoad);
        idMunSelect.innerHTML = '<option value="">Cargando municipios...</option>';
        idMunSelect.disabled = true;
        nomBarrioInput.disabled = true;
        if (mensajeNoMunicipios) mensajeNoMunicipios.style.display = 'none';
        setFieldValidationUI(idMunSelect, true);

        if (idDep) {
            fetch(`../ajax/get_municipios.php?id_dep=${encodeURIComponent(idDep)}`)
                .then(response => {
                    console.log('cargarMunicipios - Fetch response status:', response.status);
                    if (!response.ok) {
                        console.error('cargarMunicipios - Fetch error:', response.statusText);
                        throw new Error(`Error de red: ${response.status} ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('cargarMunicipios - Datos recibidos (JSON):', data);
                    idMunSelect.innerHTML = '';
                    if (data && Array.isArray(data) && data.length > 0) {
                        idMunSelect.appendChild(new Option('Seleccione un municipio...', ''));
                        data.forEach(mun => {
                            const option = new Option(mun.nombre, mun.id);
                            idMunSelect.appendChild(option);
                        });
                        idMunSelect.disabled = false;
                        if (valorActualMun) {
                            idMunSelect.value = valorActualMun;
                        }
                        nomBarrioInput.disabled = (idMunSelect.value === "");
                        if(mensajeNoMunicipios) mensajeNoMunicipios.style.display = 'none';
                        console.log('Municipios cargados en el select.');
                    } else {
                        idMunSelect.appendChild(new Option('No hay municipios para este departamento', ''));
                        idMunSelect.disabled = true;
                        nomBarrioInput.disabled = true;
                        if(mensajeNoMunicipios) mensajeNoMunicipios.style.display = 'block';
                        console.log('No se encontraron municipios o datos inválidos.');
                    }
                })
                .catch(error => {
                    console.error('cargarMunicipios - Catch error:', error);
                    idMunSelect.innerHTML = '<option value="">Error al cargar municipios</option>';
                    idMunSelect.disabled = true;
                    nomBarrioInput.disabled = true;
                    if(mensajeNoMunicipios) {
                        mensajeNoMunicipios.style.display = 'block';
                        mensajeNoMunicipios.textContent = 'Error al cargar municipios.';
                    }
                })
                .finally(() => {
                    if (isInitialLoad && idMunSelect.value) {
                        idMunSelect.dataset.interacted = "true";
                    }
                    if (isInitialLoad && nomBarrioInput.value && !nomBarrioInput.disabled) {
                        nomBarrioInput.dataset.interacted = "true";
                    }
                    updateFormState();
                });
        } else {
            console.log('cargarMunicipios - idDep está vacío. No se cargarán municipios.');
            idMunSelect.innerHTML = '<option value="">Seleccione departamento...</option>';
            idMunSelect.disabled = true;
            nomBarrioInput.disabled = true;
            updateFormState();
        }
    }

    if (!formularioDeshabilitadoPorNoDep) {
        console.log('Formulario NO está deshabilitado por falta de departamentos. Añadiendo listeners.');
        idDepSelect.addEventListener('change', function() {
            console.log('Departamento cambiado a:', this.value);
            this.dataset.interacted = "true";
            validateSingleField(campos.find(c => c.el === this), true);
            idMunSelect.value = "";
            nomBarrioInput.value = "";
            nomBarrioInput.classList.remove('is-valid', 'is-invalid');
            idMunSelect.classList.remove('is-valid', 'is-invalid');
            cargarMunicipios(this.value);
        });

        idMunSelect.addEventListener('change', function() {
            console.log('Municipio cambiado a:', this.value);
            this.dataset.interacted = "true";
            validateSingleField(campos.find(c => c.el === this), true);
            nomBarrioInput.disabled = this.value === "" || this.disabled;
            if (this.value !== "" && !this.disabled) {
                if (!nomBarrioInput.value) nomBarrioInput.focus();
            } else {
                nomBarrioInput.value = "";
                nomBarrioInput.classList.remove('is-valid', 'is-invalid');
            }
            updateFormState();
        });

        nomBarrioInput.addEventListener('input', function() {
            this.dataset.interacted = "true";
            validateSingleField(campos.find(c => c.el === this), true);
            updateFormState();
        });
         nomBarrioInput.addEventListener('blur', function() {
            this.dataset.interacted = "true";
            validateSingleField(campos.find(c => c.el === this), true);
            updateFormState();
        });

        campos.forEach(fieldConfig => {
            if (fieldConfig.el && (fieldConfig.el.tagName === 'INPUT' || fieldConfig.el.tagName === 'SELECT')) {
                 fieldConfig.el.addEventListener('focus', () => {
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
                });
            }
        });

        if (form) {
            form.addEventListener('submit', (event) => {
                console.log('Formulario submit intentado.');
                campos.forEach(fc => { if (fc.el && !fc.el.disabled) fc.el.dataset.interacted = "true"; });
                if (!updateFormState()) {
                    event.preventDefault();
                    console.log('Submit prevenido por validación fallida.');
                    const firstInvalid = campos.find(f => f.el && f.el.classList.contains('is-invalid') && !f.el.disabled);
                    if (firstInvalid && firstInvalid.el) firstInvalid.el.focus();
                } else {
                    console.log('Submit permitido, formulario válido.');
                }
            });
        }

        const idDepInicial = (typeof phpInitialData !== 'undefined' ? phpInitialData.idDepSelForm : null) || idDepSelect.value;
        const idMunInicial = (typeof phpInitialData !== 'undefined' ? phpInitialData.idMunSelForm : null);
        console.log('Valores iniciales - idDepInicial:', idDepInicial, 'idMunInicial:', idMunInicial);

        if (idDepInicial) {
            idDepSelect.dataset.interacted = "true";
            if (typeof phpInitialData !== 'undefined' && phpInitialData.municipiosPreload && phpInitialData.municipiosPreload.length > 0 && idDepSelect.value === idDepInicial) {
                console.log('Usando municipios precargados por PHP.');
                idMunSelect.innerHTML = '';
                idMunSelect.appendChild(new Option('Seleccione un municipio...', ''));
                phpInitialData.municipiosPreload.forEach(mun => {
                    const option = new Option(mun.nom_mun, mun.id_mun);
                    idMunSelect.appendChild(option);
                });
                idMunSelect.disabled = false;
                if (idMunInicial) {
                    idMunSelect.value = idMunInicial;
                }
                nomBarrioInput.disabled = (idMunSelect.value === "");
                if (idMunSelect.value) idMunSelect.dataset.interacted = "true";
                if (nomBarrioInput.value && !nomBarrioInput.disabled) nomBarrioInput.dataset.interacted = "true";
                updateFormState();
            } else if (idDepSelect.value) {
                console.log('Departamento inicial seleccionado, llamando a cargarMunicipios.');
                cargarMunicipios(idDepSelect.value, idMunInicial, true);
            } else {
                 console.log('Sin departamento inicial en el select, deshabilitando selects dependientes.');
                 idMunSelect.disabled = true;
                 nomBarrioInput.disabled = true;
                 updateFormState();
            }
        } else {
             console.log('Sin departamento inicial, deshabilitando selects dependientes.');
             idMunSelect.disabled = true;
             nomBarrioInput.disabled = true;
             updateFormState();
        }

        if (!errorServidorEspecificoDiv) {
            campos.forEach(fc => {
                if (fc.el && fc.el.value && !fc.el.disabled) {
                    fc.el.dataset.interacted = "true";
                    validateSingleField(fc, true);
                }
            });
        }
        if (idMunSelect.value === "" || idMunSelect.disabled) {
            nomBarrioInput.disabled = true;
        } else {
            nomBarrioInput.disabled = false;
        }
        updateFormState();

        const urlParams = new URLSearchParams(window.location.search);
        if (!urlParams.has('status') && !errorServidorEspecificoDiv) {
            const camposConValor = campos.some(c => c.el && !c.el.disabled && c.el.value.trim() !== '');
            if (camposConValor) {
                mensajesGeneralesLimpiados = true;
            }
        }

    } else {
        console.log('Formulario DESHABILITADO por falta de departamentos.');
        if (btnCrearBarrio) btnCrearBarrio.disabled = true;
        if (idMunSelect) idMunSelect.disabled = true;
        if (nomBarrioInput) nomBarrioInput.disabled = true;
        campos.forEach(fc => { if (fc.el) fc.el.disabled = true; });
    }
});