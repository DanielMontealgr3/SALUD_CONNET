// FUNCIÓN PRINCIPAL QUE INICIALIZA TODAS LAS VALIDACIONES Y EVENTOS DEL FORMULARIO DE PERFIL.
function inicializarValidacionesPerfil() {
    // SELECCIÓN DE TODOS LOS ELEMENTOS DEL DOM NECESARIOS.
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
    const newPass = form.querySelector('#pass_modal');
    const confirmPass = form.querySelector('#confirm_pass_modal');
    const saveButton = document.getElementById('saveProfileChangesButton');
    const globalMessageDiv = document.getElementById('modalUpdateMessage');
    const tipoDocIdHidden = document.getElementById('tipo_doc_id_hidden');
    
    // OBJETO PARA ALMACENAR EL ESTADO INICIAL DEL FORMULARIO Y DETECTAR CAMBIOS.
    let initialFormState = {};
    const ID_TIPO_DOC_CEDULA = 1;
    const ID_TIPO_DOC_TI = 2;

    // GUARDA LOS VALORES INICIALES DE TODOS LOS CAMPOS DEL FORMULARIO.
    function storeInitialFormState() {
        initialFormState = {};
        new FormData(form).forEach((value, key) => {
            if (key !== 'foto_usu_modal') initialFormState[key] = value;
        });
    }

    // VERIFICA SI SE HA REALIZADO ALGÚN CAMBIO EN EL FORMULARIO.
    function checkFormDirty() {
        if (fotoInput.files.length > 0) return true;
        for (const key in initialFormState) {
            if (form.elements[key] && initialFormState[key] != form.elements[key].value) return true;
        }
        return false;
    }

    // MUESTRA MENSAJES DE VALIDACIÓN Y APLICA ESTILOS DE ÉXITO/ERROR A LOS CAMPOS.
    function setValidationState(el, isValid, message) {
        const feedbackDiv = el.parentElement.querySelector('.invalid-feedback');
        el.classList.remove('is-valid', 'is-invalid');
        
        // SOLO APLICA ESTILOS SI EL CAMPO HA SIDO 'TOCADO' POR EL USUARIO.
        if (el.dataset.interacted === 'true') {
            el.classList.add(isValid ? 'is-valid' : 'is-invalid');
            if (feedbackDiv) feedbackDiv.textContent = message || '';
        }
    }
    
    // FUNCIÓN ESPECÍFICA PARA VALIDAR LA FECHA DE NACIMIENTO Y LA EDAD SEGÚN EL TIPO DE DOCUMENTO.
    function validateFechaNac(i) {
        if (!i.value && i.required) return { isValid: false, message: "Fecha requerida." };
        const hoy = new Date();
        const fechaNacDate = new Date(i.value + "T00:00:00Z");
        if (isNaN(fechaNacDate.getTime()) || fechaNacDate >= hoy) return { isValid: false, message: "Fecha inválida o futura." };
        
        const tipoDocId = parseInt(tipoDocIdHidden.value);
        let edad = hoy.getFullYear() - fechaNacDate.getFullYear();
        const m = hoy.getMonth() - fechaNacDate.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < fechaNacDate.getDate())) edad--;

        if (tipoDocId === ID_TIPO_DOC_CEDULA && edad < 18) return { isValid: false, message: "Con C.C. debe ser > 18 años." };
        if (tipoDocId === ID_TIPO_DOC_TI && edad >= 18) return { isValid: false, message: "Con T.I. debe ser < 18 años." };
        
        return { isValid: true, message: "" };
    }
    
    // ARRAY DE CONFIGURACIÓN CON TODOS LOS CAMPOS Y SUS RESPECTIVAS FUNCIONES DE VALIDACIÓN.
    const inputsConfig = [
        { el: nomUsu, validator: (i) => /^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]{5,100}$/.test(i.value.trim()) ? {isValid:true} : {isValid:false, message:"Solo letras, 5-100 caracteres."}},
        { el: correoUsu, validator: (i) => /^\w+([.-_+]?\w+)*@\w+([.-]?\w+)*(\.\w{2,10})+$/.test(i.value.trim()) ? {isValid:true} : {isValid:false, message:"Formato de correo inválido."}},
        { el: telUsu, validator: (i) => i.value.trim() === '' || /^\d{7,10}$/.test(i.value.trim()) ? {isValid:true} : {isValid:false, message:"Teléfono inválido (7-10 dígitos)."}},
        { el: fechaNac, validator: validateFechaNac },
        { el: direccionUsu, validator: (i) => i.value.trim().length <= 200 ? {isValid:true} : {isValid:false, message:"Máximo 200 caracteres."}},
        { el: selectDepartamento, validator: (i) => i.value !== '' ? {isValid:true} : {isValid:false, message:"Campo requerido."}},
        { el: selectMunicipio, validator: (i) => i.value !== '' ? {isValid:true} : {isValid:false, message:"Campo requerido."}},
        { el: selectBarrio, validator: (i) => i.value !== '' ? {isValid:true} : {isValid:false, message:"Campo requerido."}},
        { el: idGen, validator: (i) => i.value !== '' ? {isValid:true} : {isValid:false, message:"Campo requerido."}},
        { el: newPass, validator: (i) => i.value===''||/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/.test(i.value)?{isValid:true}:{isValid:false, message:"Mínimo 8 caracteres, debe incluir mayúscula, minúscula, número y símbolo."}},
        { el: confirmPass, validator: (i) => i.value === newPass.value ? {isValid:true} : {isValid:false, message:"Las contraseñas no coinciden."}}
    ];

    // FUNCIÓN QUE EJECUTA TODAS LAS VALIDACIONES Y ACTUALIZA EL ESTADO DEL BOTÓN DE GUARDAR.
    function checkAllValidity() {
        let formIsValid = true;
        inputsConfig.forEach(conf => {
            if (conf.el && !conf.el.disabled) {
                const { isValid, message } = conf.validator(conf.el);
                setValidationState(conf.el, isValid, message);
                if (!isValid) formIsValid = false;
            }
        });
        if (saveButton) saveButton.disabled = !(formIsValid && checkFormDirty());
    }

    // ASIGNA EVENTOS PARA VALIDAR EN TIEMPO REAL DESPUÉS DE LA PRIMERA INTERACCIÓN.
    inputsConfig.forEach(conf => {
        if(conf.el){
            const markAsInteracted = () => conf.el.dataset.interacted = 'true';
            const eventType = ['SELECT', 'FILE', 'date'].includes(conf.el.tagName) || conf.el.type === 'date' ? 'change' : 'input';
            
            conf.el.addEventListener('focus', markAsInteracted, { once: true });
            conf.el.addEventListener(eventType, () => {
                 if (conf.el === newPass) {
                    confirmPass.dataset.interacted = 'true';
                 }
                 checkAllValidity();
            });
        }
    });
    if (fotoInput) fotoInput.addEventListener('change', () => { fotoInput.dataset.interacted = 'true'; checkAllValidity(); });

    // FUNCIÓN PARA CARGAR DINÁMICAMENTE LAS OPCIONES DE UN SELECT (MUNICIPIOS Y BARRIOS).
    function populateSelect(selectEl, url) {
        selectEl.disabled = true;
        fetch(url).then(r => r.json())
        .then(data => {
            selectEl.innerHTML = `<option value="">Seleccione...</option>`;
            if (data.length > 0) {
                data.forEach(item => selectEl.add(new Option(item.nombre, item.id)));
                selectEl.disabled = false;
            }
        }).catch(e => console.error('Fetch error:', e)).finally(checkAllValidity);
    }
    
    // LÓGICA PARA CARGAR MUNICIPIOS CUANDO CAMBIA EL DEPARTAMENTO.
    if (selectDepartamento) {
        selectDepartamento.addEventListener('change', () => {
            if (selectDepartamento.value) {
                populateSelect(selectMunicipio, `${AppConfig.BASE_URL}/include/get_municipios.php?id_dep=${selectDepartamento.value}`);
            }
            selectBarrio.innerHTML = `<option value="">Seleccione Municipio</option>`;
            selectBarrio.disabled = true;
            checkAllValidity();
        });
    }

    // LÓGICA PARA CARGAR BARRIOS CUANDO CAMBIA EL MUNICIPIO.
    if (selectMunicipio) {
        selectMunicipio.addEventListener('change', () => {
            if (selectMunicipio.value) {
                populateSelect(selectBarrio, `${AppConfig.BASE_URL}/include/get_barrios.php?id_mun=${selectMunicipio.value}`);
            }
            checkAllValidity();
        });
    }

    // ALMACENA EL ESTADO INICIAL Y DEJA EL BOTÓN DESHABILITADO.
    storeInitialFormState();
    if (saveButton) saveButton.disabled = true;
    
    // GESTIONA EL ENVÍO DEL FORMULARIO.
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        inputsConfig.forEach(conf => { if(conf.el) conf.el.dataset.interacted = 'true'; });
        
        if (!checkAllValidity()) {
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
                if (data.new_foto_usu_path_for_modal) { const imagePreview = document.getElementById('imagePreviewModal'); if (imagePreview) imagePreview.src = data.new_foto_usu_path_for_modal + '?' + new Date().getTime(); }
                storeInitialFormState();
                if (saveButton) saveButton.disabled = true;
                setTimeout(() => { const modalEl = document.getElementById('userProfileModal'); if (modalEl) { bootstrap.Modal.getInstance(modalEl)?.hide(); } }, 2500);
            } else { if (saveButton) saveButton.disabled = false; }
        }).catch(error => { if (globalMessageDiv) { globalMessageDiv.className = 'mt-3 alert alert-danger'; globalMessageDiv.textContent = 'Error de conexión: ' + error.message; } if (saveButton) saveButton.disabled = false; });
    });
}