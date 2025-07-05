function inicializarValidacionesPerfil() {
    const form = document.getElementById('profileFormModalActual');
    if (!form) return;

    const nomUsu = form.querySelector('#nom_usu_modal');
    const correoUsu = form.querySelector('#correo_usu_modal');
    const telUsu = form.querySelector('#tel_usu_modal');
    const fechaNac = form.querySelector('#fecha_nac_modal');
    const direccionUsu = form.querySelector('#direccion_usu_modal');
    const selectDepartamento = form.querySelector('#id_departamento_modal');
    const selectMunicipio = form.querySelector('#id_municipio_modal');
    const selectBarrio = form.querySelector('#id_barrio_modal');
    const idGen = form.querySelector('#id_gen_modal');
    const fotoInput = form.querySelector('#foto_usu_modal');
    const imagePreview = form.querySelector('#imagePreviewModal');
    const newPass = form.querySelector('#pass_modal');
    const confirmPass = form.querySelector('#confirm_pass_modal');
    const saveButton = document.getElementById('saveProfileChangesButton');
    const globalMessageDiv = document.getElementById('modalUpdateMessage');
    const tipoDocIdHidden = document.getElementById('tipo_doc_id_hidden');
    
    let initialFormState = {};
    const ID_TIPO_DOC_CEDULA = 1;
    const ID_TIPO_DOC_TI = 2;

    function storeInitialFormState() {
        initialFormState = {};
        const formData = new FormData(form);
        for (let pair of formData.entries()) {
            if (pair[0] !== 'foto_usu_modal') {
                initialFormState[pair[0]] = pair[1];
            }
        }
    }

    function checkFormDirty() {
        if (fotoInput.files.length > 0) return true;
        for (const key in initialFormState) {
            const currentElement = form.elements[key];
            if (currentElement && initialFormState[key] !== currentElement.value) {
                return true;
            }
        }
        return false;
    }

    function setValidationMessage(el, msg, isValid) {
        const feedbackDiv = el.parentElement.querySelector('.invalid-feedback') || el.parentElement.parentElement.querySelector('.foto-feedback');
        if (feedbackDiv) feedbackDiv.textContent = msg || '';
        
        el.classList.remove('is-invalid', 'is-valid');

        if (!isValid) {
            el.classList.add('is-invalid');
        } else {
            const isDirty = (initialFormState[el.id] !== el.value) || (el.type === 'file' && el.files.length > 0);
            if (isDirty && el.type !== 'file') {
                el.classList.add('is-valid');
            }
        }
    }
    
    function validateFechaNac(i) {
        if (!i.value && i.required) return { isValid: false, message: "Fecha requerida." };
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        const fechaNacDate = new Date(i.value + "T00:00:00Z");
        if (isNaN(fechaNacDate.getTime()) || fechaNacDate >= hoy) return { isValid: false, message: "Fecha inválida o futura." };
        
        const tipoDocId = parseInt(tipoDocIdHidden.value);
        let edad = hoy.getFullYear() - fechaNacDate.getFullYear();
        const m = hoy.getMonth() - fechaNacDate.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < fechaNacDate.getDate())) {
            edad--;
        }

        if (tipoDocId === ID_TIPO_DOC_CEDULA && edad < 18) return { isValid: false, message: "Con C.C. debe ser mayor de 18. Contacte al admin para actualizar su tipo de documento." };
        if (tipoDocId === ID_TIPO_DOC_TI && edad >= 18) return { isValid: false, message: "Con T.I. debe ser menor de 18. Contacte al admin para actualizar su tipo de documento." };
        
        return { isValid: true, message: "" };
    }
    
    function validateFoto(i) {
        return { isValid: true, message: "" };
    }

    const inputsConfig = [
        { el: nomUsu, validator: (i) => /^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]{5,100}$/.test(i.value.trim()) ? {isValid:true} : {isValid:false, message:"Solo letras, 5-100 car."}},
        { el: correoUsu, validator: (i) => /^\w+([.-_+]?\w+)*@\w+([.-]?\w+)*(\.\w{2,10})+$/.test(i.value.trim()) ? {isValid:true} : {isValid:false, message:"Correo inválido."}},
        { el: telUsu, validator: (i) => i.value.trim() === '' || /^\d{7,10}$/.test(i.value.trim()) ? {isValid:true} : {isValid:false, message:"Inválido (7-10 dígitos)."}},
        { el: fechaNac, validator: validateFechaNac },
        { el: direccionUsu, validator: (i) => i.value.trim().length <= 200 ? {isValid:true} : {isValid:false, message:"Máx 200 car."}},
        { el: selectDepartamento, validator: (i) => i.value !== '' ? {isValid:true} : {isValid:false, message:"Requerido."}},
        { el: selectMunicipio, validator: (i) => i.value !== '' ? {isValid:true} : {isValid:false, message:"Requerido."}},
        { el: selectBarrio, validator: (i) => i.value !== '' ? {isValid:true} : {isValid:false, message:"Requerido."}},
        { el: idGen, validator: (i) => i.value !== '' ? {isValid:true} : {isValid:false, message:"Requerido."}},
        { el: fotoInput, validator: validateFoto },
        { el: newPass, validator: (i) => i.value===''||/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/.test(i.value)?{isValid:true}:{isValid:false, message:"Mín 8 car, mayús, minús, núm, símb."}},
        { el: confirmPass, validator: (i) => i.value === newPass.value ? {isValid:true} : {isValid:false, message:"No coinciden."}}
    ];

    function checkAllValidity() {
        let formIsValid = true;
        inputsConfig.forEach(conf => {
            if (conf.el && !conf.el.disabled) {
                const { isValid, message } = conf.validator(conf.el);
                setValidationMessage(conf.el, message, isValid);
                if (!isValid) formIsValid = false;
            } else if (conf.el) {
                setValidationMessage(conf.el, '', true);
                conf.el.classList.remove('is-valid', 'is-invalid');
            }
        });
        if (saveButton) saveButton.disabled = !(formIsValid && checkFormDirty());
    }

    inputsConfig.forEach(conf => {
        if(conf.el){
            const eventType = ['SELECT', 'DATE', 'FILE'].includes(conf.el.tagName) || ['date', 'file'].includes(conf.el.type) ? 'change' : 'input';
            conf.el.addEventListener(eventType, () => {
                 if (conf.el === newPass) {
                    const confirmConfig = inputsConfig.find(c => c.el === confirmPass);
                    const {isValid, message} = confirmConfig.validator(confirmPass);
                    setValidationMessage(confirmPass, message, isValid);
                 }
                 checkAllValidity();
            });
        }
    });

    if(fotoInput && imagePreview) { fotoInput.addEventListener('change', (e) => { const file = e.target.files[0]; if (file) { const reader = new FileReader(); reader.onload = (ev) => imagePreview.src = ev.target.result; reader.readAsDataURL(file); } }); }
    
    function populateSelect(selectEl, url, placeholder, disabledPlaceholder) {
        selectEl.innerHTML = `<option value="">${placeholder}</option>`;
        selectEl.disabled = true;
        fetch(url).then(r => r.ok ? r.json() : Promise.reject(r))
        .then(data => {
            selectEl.innerHTML = `<option value="">Seleccione...</option>`;
            if (data.length > 0) {
                data.forEach(item => selectEl.add(new Option(item.nombre, item.id)));
                selectEl.disabled = false;
            } else { 
                selectEl.innerHTML = `<option value="">${disabledPlaceholder}</option>`; 
                selectEl.disabled = true;
            }
        }).catch(e => { console.error('Fetch error:', e); selectEl.innerHTML = `<option value="">Error al cargar</option>`; }).finally(checkAllValidity);
    }
    
    if (selectDepartamento) {
        selectDepartamento.addEventListener('change', () => {
            const idDep = selectDepartamento.value;
            selectMunicipio.value = "";
            selectMunicipio.innerHTML = `<option value="">Seleccione Departamento</option>`;
            selectMunicipio.disabled = true;
            selectBarrio.value = "";
            selectBarrio.innerHTML = `<option value="">Seleccione Municipio</option>`;
            selectBarrio.disabled = true;
            
            if (idDep) {
                populateSelect(selectMunicipio, `/SALUDCONNECT/include/get_municipios.php?id_dep=${idDep}`, 'Cargando...', 'No hay Municipios');
            }
            checkAllValidity();
        });
    }

    if (selectMunicipio) {
        selectMunicipio.addEventListener('change', () => {
            const idMun = selectMunicipio.value;
            selectBarrio.value = "";
            selectBarrio.innerHTML = `<option value="">Seleccione Municipio</option>`;
            selectBarrio.disabled = true;
            if (idMun) {
                populateSelect(selectBarrio, `/SALUDCONNECT/include/get_barrios.php?id_mun=${idMun}`, 'Cargando...', 'No hay Barrios');
            }
            checkAllValidity();
        });
    }

    storeInitialFormState();
    checkAllValidity();

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        let isFormValid = true;
        inputsConfig.forEach(conf => {
            if (conf.el && !conf.el.disabled) {
                const { isValid, message } = conf.validator(conf.el);
                setValidationMessage(conf.el, message, isValid);
                if (!isValid) isFormValid = false;
            }
        });

        if (!isFormValid) {
            if (globalMessageDiv) globalMessageDiv.innerHTML = '<div class="alert alert-warning">Por favor, corrija los errores del formulario.</div>';
            return;
        }

        if (saveButton) saveButton.disabled = true;
        if (globalMessageDiv) globalMessageDiv.innerHTML = '<div class="alert alert-info d-flex align-items-center"><div class="spinner-border spinner-border-sm me-2"></div><span>Actualizando...</span></div>';
        
        fetch(form.action, { method: 'POST', body: new FormData(form) })
        .then(r => r.json())
        .then(data => {
            if (globalMessageDiv) { globalMessageDiv.className = 'mt-3 alert ' + (data.success ? 'alert-success' : 'alert-danger'); globalMessageDiv.textContent = data.message; }
            if (data.success) {
                if (data.new_nom_usu) { const userNameOnMenu = document.getElementById('menuUserNameDisplay'); if (userNameOnMenu) userNameOnMenu.textContent = data.new_nom_usu; }
                if (data.new_foto_usu_path_for_modal) { if (imagePreview) imagePreview.src = data.new_foto_usu_path_for_modal + '?' + new Date().getTime(); }
                storeInitialFormState();
                checkAllValidity();
                setTimeout(() => { const modalEl = document.getElementById('userProfileModal'); if (modalEl) { const modalInstance = bootstrap.Modal.getInstance(modalEl); if (modalInstance) modalInstance.hide(); } }, 2500);
            } else { if (saveButton) saveButton.disabled = false; }
        }).catch(error => { if (globalMessageDiv) { globalMessageDiv.className = 'mt-3 alert alert-danger'; globalMessageDiv.textContent = 'Error de conexión: ' + error.message; } if (saveButton) saveButton.disabled = false; });
    });
    
    return checkAllValidity;
}