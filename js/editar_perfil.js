function inicializarValidacionesPerfil() {
    const form = document.getElementById('profileFormModalActual');
    if (!form) {
        console.error("El formulario de perfil 'profileFormModalActual' no se encontró.");
        return;
    }

    // --- SELECCIÓN DE ELEMENTOS DEL DOM ---
    const nomUsu = form.querySelector('#nom_usu_modal');
    const correoUsu = form.querySelector('#correo_usu_modal');
    const telUsu = form.querySelector('#tel_usu_modal');
    const fechaNac = form.querySelector('#fecha_nac_modal');
    const selectDepartamento = form.querySelector('#id_departamento_modal');
    const selectMunicipio = form.querySelector('#id_municipio_modal');
    const selectBarrio = form.querySelector('#id_barrio_modal');
    const idGen = form.querySelector('#id_gen_modal');
    const fotoInput = form.querySelector('#foto_usu_modal');
    const newPass = form.querySelector('#pass_modal');
    const confirmPass = form.querySelector('#confirm_pass_modal');
    const saveButton = document.getElementById('saveProfileChangesButton');
    const globalMessageDiv = document.getElementById('modalUpdateMessage');
    const tipoDocIdHidden = document.getElementById('tipo_doc_id_hidden');
    
    // Almacena el estado inicial para detectar cambios ("form dirty").
    let initialFormState = {};
    const ID_TIPO_DOC_CEDULA = 1;
    const ID_TIPO_DOC_TI = 2;

    const storeInitialFormState = () => {
        initialFormState = {};
        new FormData(form).forEach((value, key) => {
            if (key !== 'foto_usu_modal') {
                initialFormState[key] = value;
            }
        });
    };

    const checkFormDirty = () => {
        if (fotoInput.files.length > 0) return true;
        for (const key in initialFormState) {
            const currentElement = form.elements[key];
            if (currentElement && initialFormState[key] != currentElement.value) {
                return true;
            }
        }
        return false;
    };

    const setValidationState = (el, isValid, message) => {
        const feedbackDiv = el.parentElement.querySelector('.invalid-feedback');
        el.classList.remove('is-valid', 'is-invalid');
        el.classList.add(isValid ? 'is-valid' : 'is-invalid');
        if (feedbackDiv) {
            feedbackDiv.textContent = message || '';
        }
    };
    
    const validateFechaNac = (el) => {
        if (!el.value && el.required) return { isValid: false, message: "Fecha de nacimiento requerida." };
        const hoy = new Date();
        const fechaNacDate = new Date(el.value + "T00:00:00Z");
        if (isNaN(fechaNacDate.getTime()) || fechaNacDate >= hoy) return { isValid: false, message: "Fecha inválida o futura." };
        
        const tipoDocId = parseInt(tipoDocIdHidden.value, 10);
        let edad = hoy.getFullYear() - fechaNacDate.getFullYear();
        const m = hoy.getMonth() - fechaNacDate.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < fechaNacDate.getDate())) edad--;

        if (tipoDocId === ID_TIPO_DOC_CEDULA && edad < 18) return { isValid: false, message: "Con C.C. debe ser > 18 años." };
        if (tipoDocId === ID_TIPO_DOC_TI && edad >= 18) return { isValid: false, message: "Con T.I. debe ser < 18 años." };
        return { isValid: true, message: "" };
    };
    
    // --- CONFIGURACIÓN CENTRALIZADA DE VALIDACIONES ---
    const inputsConfig = [
        { el: nomUsu, validator: (i) => /^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]{5,100}$/.test(i.value.trim()) ? {isValid:true} : {isValid:false, message:"Solo letras, 5-100 caracteres."}},
        { el: correoUsu, validator: (i) => /^\w+([.-_+]?\w+)*@\w+([.-]?\w+)*(\.\w{2,10})+$/.test(i.value.trim()) ? {isValid:true} : {isValid:false, message:"Formato de correo inválido."}},
        { el: telUsu, validator: (i) => i.value.trim() === '' || /^\d{7,10}$/.test(i.value.trim()) ? {isValid:true} : {isValid:false, message:"Teléfono inválido (7-10 dígitos)."}},
        { el: fechaNac, validator: validateFechaNac },
        { el: form.querySelector('#direccion_usu_modal'), validator: (i) => i.value.trim().length <= 200 ? {isValid:true} : {isValid:false, message:"Máximo 200 caracteres."}},
        { el: selectDepartamento, validator: (i) => i.value !== '' ? {isValid:true} : {isValid:false, message:"Campo requerido."}},
        { el: selectMunicipio, validator: (i) => i.value !== '' ? {isValid:true} : {isValid:false, message:"Campo requerido."}},
        { el: selectBarrio, validator: (i) => i.value !== '' ? {isValid:true} : {isValid:false, message:"Campo requerido."}},
        { el: idGen, validator: (i) => i.value !== '' ? {isValid:true} : {isValid:false, message:"Campo requerido."}},
        { el: newPass, validator: (i) => i.value===''||/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/.test(i.value)?{isValid:true}:{isValid:false, message:"Mínimo 8 caracteres, mayúscula, minúscula, número y símbolo."}},
        { el: confirmPass, validator: (i) => i.value === newPass.value ? {isValid:true} : {isValid:false, message:"Las contraseñas no coinciden."}}
    ];

    const checkAllValidityAndUpdateButton = () => {
        let formIsValid = true;
        inputsConfig.forEach(conf => {
            if (conf.el && !conf.el.disabled) {
                const { isValid, message } = conf.validator(conf.el);
                setValidationState(conf.el, isValid, message);
                if (!isValid) formIsValid = false;
            }
        });
        
        if (saveButton) {
            saveButton.disabled = !(formIsValid && checkFormDirty());
        }
        return formIsValid;
    };

    // --- ASIGNACIÓN DE EVENTOS EN TIEMPO REAL ---
    inputsConfig.forEach(conf => {
        if(conf.el){
            const eventType = ['SELECT', 'FILE', 'date'].includes(conf.el.tagName) || conf.el.type === 'date' ? 'change' : 'input';
            conf.el.addEventListener(eventType, checkAllValidityAndUpdateButton);
        }
    });
    if (fotoInput) fotoInput.addEventListener('change', checkAllValidityAndUpdateButton);

    const populateSelect = (selectEl, url, selectedValue) => {
        selectEl.disabled = true;
        // Usa AppConfig para construir la URL correcta
        fetch(`${AppConfig.BASE_URL}${url}`)
            .then(r => r.json())
            .then(data => {
                selectEl.innerHTML = `<option value="">Seleccione...</option>`;
                if (data.length > 0) {
                    data.forEach(item => {
                        const option = new Option(item.nombre, item.id);
                        if (item.id == selectedValue) option.selected = true;
                        selectEl.add(option);
                    });
                    selectEl.disabled = false;
                }
            })
            .catch(e => console.error('Error al poblar select:', e))
            .finally(checkAllValidityAndUpdateButton);
    };
    
    if (selectDepartamento) {
        selectDepartamento.addEventListener('change', () => {
            selectBarrio.innerHTML = `<option value="">Seleccione Municipio</option>`;
            selectBarrio.disabled = true;
            if (selectDepartamento.value) {
                populateSelect(selectMunicipio, `/include/get_municipios.php?id_dep=${selectDepartamento.value}`);
            } else {
                selectMunicipio.innerHTML = `<option value="">Seleccione Departamento</option>`;
                selectMunicipio.disabled = true;
            }
            checkAllValidityAndUpdateButton();
        });
    }

    if (selectMunicipio) {
        selectMunicipio.addEventListener('change', () => {
            if (selectMunicipio.value) {
                populateSelect(selectBarrio, `/include/get_barrios.php?id_mun=${selectMunicipio.value}`);
            } else {
                selectBarrio.innerHTML = `<option value="">Seleccione Municipio</option>`;
                selectBarrio.disabled = true;
            }
            checkAllValidityAndUpdateButton();
        });
    }

    // --- INICIALIZACIÓN Y ENVÍO DEL FORMULARIO ---
    storeInitialFormState();
    checkAllValidityAndUpdateButton(); // ¡VALIDACIÓN INICIAL AL CARGAR!
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!checkAllValidityAndUpdateButton()) {
            globalMessageDiv.innerHTML = '<div class="alert alert-warning">Por favor, corrija los errores del formulario.</div>';
            return;
        }

        saveButton.disabled = true;
        globalMessageDiv.innerHTML = '<div class="alert alert-info d-flex align-items-center"><div class="spinner-border spinner-border-sm me-2"></div><span>Actualizando...</span></div>';
        
        fetch(form.action, { method: 'POST', body: new FormData(form) })
            .then(r => r.json())
            .then(data => {
                globalMessageDiv.className = 'mt-3 alert ' + (data.success ? 'alert-success' : 'alert-danger');
                globalMessageDiv.textContent = data.message;
                
                if (data.success) {
                    if (data.new_nom_usu) document.getElementById('menuUserNameDisplay').textContent = data.new_nom_usu;
                    if (data.new_foto_usu_path_for_modal) document.getElementById('imagePreviewModal').src = `${data.new_foto_usu_path_for_modal}?t=${new Date().getTime()}`;
                    
                    storeInitialFormState(); // Actualiza el estado inicial para que el form ya no esté "dirty".
                    saveButton.disabled = true;
                    
                    setTimeout(() => {
                        const modalEl = document.getElementById('userProfileModal');
                        if (modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();
                    }, 2500);
                } else {
                    saveButton.disabled = false;
                }
            })
            .catch(error => {
                globalMessageDiv.className = 'mt-3 alert alert-danger';
                globalMessageDiv.textContent = `Error de conexión: ${error.message}`;
                saveButton.disabled = false;
            });
    });
}