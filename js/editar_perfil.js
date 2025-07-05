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
    const imagePreview = form.querySelector('#imagePreviewModal');
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
        const formData = new FormData(form);
        for (let pair of formData.entries()) {
            if (pair[0] !== 'foto_usu_modal') {
                initialFormState[pair[0]] = pair[1];
            }
        }
    }

    // VERIFICA SI SE HA REALIZADO ALGÚN CAMBIO EN EL FORMULARIO.
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

    // MUESTRA MENSAJES DE VALIDACIÓN Y APLICA ESTILOS DE ÉXITO/ERROR A LOS CAMPOS.
    function setValidationMessage(el, msg, isValid) {
        const feedbackDiv = el.parentElement.querySelector('.invalid-feedback');
        if (feedbackDiv) feedbackDiv.textContent = msg || '';
        el.classList.remove('is-invalid', 'is-valid');
        if (!isValid) {
            el.classList.add('is-invalid');
        } else if (checkFormDirty()){
             el.classList.add('is-valid');
        }
    }
    
    // FUNCIÓN ESPECÍFICA PARA VALIDAR LA FECHA DE NACIMIENTO Y LA EDAD SEGÚN EL TIPO DE DOCUMENTO.
    function validateFechaNac(i) {
        if (!i.value && i.required) return { isValid: false, message: "Fecha requerida." };
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        const fechaNacDate = new Date(i.value + "T00:00:00Z");
        if (isNaN(fechaNacDate.getTime()) || fechaNacDate >= hoy) return { isValid: false, message: "Fecha inválida o futura." };
        
        const tipoDocId = parseInt(tipoDocIdHidden.value);
        let edad = hoy.getFullYear() - fechaNacDate.getFullYear();
        const m = hoy.getMonth() - fechaNacDate.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < fechaNacDate.getDate())) edad--;

        if (tipoDocId === ID_TIPO_DOC_CEDULA && edad < 18) return { isValid: false, message: "Con C.C. debe ser mayor de 18." };
        if (tipoDocId === ID_TIPO_DOC_TI && edad >= 18) return { isValid: false, message: "Con T.I. debe ser menor de 18." };
        
        return { isValid: true, message: "" };
    }
    
    // ARRAY DE CONFIGURACIÓN CON TODOS LOS CAMPOS Y SUS RESPECTIVAS FUNCIONES DE VALIDACIÓN.
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
        { el: newPass, validator: (i) => i.value===''||/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/.test(i.value)?{isValid:true}:{isValid:false, message:"Mín 8 car, mayús, minús, núm, símb."}},
        { el: confirmPass, validator: (i) => i.value === newPass.value ? {isValid:true} : {isValid:false, message:"No coinciden."}}
    ];

    // FUNCIÓN QUE EJECUTA TODAS LAS VALIDACIONES Y ACTUALIZA EL ESTADO DEL BOTÓN DE GUARDAR.
    function checkAllValidity() {
        let formIsValid = true;
        inputsConfig.forEach(conf => {
            if (conf.el && !conf.el.disabled) {
                const { isValid, message } = conf.validator(conf.el);
                setValidationMessage(conf.el, message, isValid);
                if (!isValid) formIsValid = false;
            }
        });
        if (saveButton) saveButton.disabled = !(formIsValid && checkFormDirty());
    }

    // ASIGNA EVENTOS 'INPUT' O 'CHANGE' A CADA CAMPO PARA VALIDAR EN TIEMPO REAL.
    inputsConfig.forEach(conf => {
        if(conf.el){
            conf.el.addEventListener(conf.el.tagName === 'SELECT' ? 'change' : 'input', () => {
                 if (conf.el === newPass) {
                    const {isValid, message} = inputsConfig.find(c => c.el === confirmPass).validator(confirmPass);
                    setValidationMessage(confirmPass, message, isValid);
                 }
                 checkAllValidity();
            });
        }
    });

    // FUNCIÓN PARA CARGAR DINÁMICAMENTE LAS OPCIONES DE UN SELECT (MUNICIPIOS Y BARRIOS).
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
    
    // LÓGICA PARA CARGAR MUNICIPIOS CUANDO CAMBIA EL DEPARTAMENTO.
    if (selectDepartamento) {
        selectDepartamento.addEventListener('change', () => {
            if (selectDepartamento.value) {
                // UTILIZA AppConfig.BASE_URL PARA CONSTRUIR LA RUTA DE LA API.
                populateSelect(selectMunicipio, `${AppConfig.BASE_URL}/include/get_municipios.php?id_dep=${selectDepartamento.value}`, 'Cargando...', 'No hay Municipios');
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
                // UTILIZA AppConfig.BASE_URL PARA CONSTRUIR LA RUTA DE LA API.
                populateSelect(selectBarrio, `${AppConfig.BASE_URL}/include/get_barrios.php?id_mun=${selectMunicipio.value}`, 'Cargando...', 'No hay Barrios');
            }
            checkAllValidity();
        });
    }

    // ALMACENA EL ESTADO INICIAL Y REALIZA LA PRIMERA VALIDACIÓN AL CARGAR.
    storeInitialFormState();
    checkAllValidity();
    
    // DEVUELVE LA FUNCIÓN DE VALIDACIÓN PARA SER USADA DESDE OTROS SCRIPTS SI ES NECESARIO.
    return checkAllValidity;
}