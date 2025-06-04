function inicializarValidacionesPerfil() {
    const form = document.getElementById('profileFormModalActual');
    if (!form) return null;
    const nomUsu = document.getElementById('nom_usu_modal');
    const correoUsu = document.getElementById('correo_usu_modal');
    const telUsu = document.getElementById('tel_usu_modal');
    const fechaNac = document.getElementById('fecha_nac_modal');
    const direccionUsu = document.getElementById('direccion_usu_modal');
    const selectDepartamento = document.getElementById('id_departamento_modal');
    const selectMunicipio = document.getElementById('id_municipio_modal');
    const selectBarrio = document.getElementById('id_barrio_modal');
    const idGen = document.getElementById('id_gen_modal');
    const fotoInput = document.getElementById('foto_usu_modal');
    const imagePreview = document.getElementById('imagePreviewModal');
    const newPass = document.getElementById('pass_modal');
    const confirmPass = document.getElementById('confirm_pass_modal');
    const saveButton = document.getElementById('saveProfileChangesButton');
    const globalMessageDiv = document.getElementById('modalUpdateMessage');
    
    const defaultAvatarPath = '../img/perfiles/default_avatar.png';
    const originalImageSrc = imagePreview ? imagePreview.src : defaultAvatarPath;
    let initialFormState = {};
    let formIsDirty = false;

    function storeInitialFormState() {
        initialFormState = {};
        formIsDirty = false;
        if (saveButton) saveButton.disabled = true;
        inputsConfig.forEach(c => { 
            if (c.el) { 
                initialFormState[c.el.id] = c.el.type === 'file' ? '' : c.el.value; 
            } 
        });
        if (imagePreview) { initialFormState['imagePreviewModal_src'] = imagePreview.src; }
    }
    
    function checkFormDirty() {
        formIsDirty = false;
        for (const c of inputsConfig) {
            if (c.el && initialFormState.hasOwnProperty(c.el.id)) {
                if (c.el.type === 'file') {
                    if (c.el.files.length > 0) { formIsDirty = true; break; }
                } else if (initialFormState[c.el.id] !== c.el.value) {
                    formIsDirty = true; break;
                }
            }
        }
        if (!formIsDirty && imagePreview && initialFormState['imagePreviewModal_src'] !== imagePreview.src && fotoInput && fotoInput.files.length > 0) { 
            formIsDirty = true;
        }
        return formIsDirty;
    }

    const inputsConfig = [
        { el: nomUsu,           validator: validateNombre,       required: true, fieldName: "Nombre" },
        { el: correoUsu,        validator: validateCorreo,       required: true, fieldName: "Correo" },
        { el: telUsu,           validator: validateTelefono,     required: false, fieldName: "Teléfono" },
        { el: fechaNac,         validator: validateFechaNac,     required: true, fieldName: "F. Nac." },
        { el: direccionUsu,     validator: validateDireccion,    required: false, fieldName: "Dirección" },
        { el: selectDepartamento, validator: validateSelect,   required: true, fieldName: "Dpto." },
        { el: selectMunicipio,  validator: validateSelect,   required: true, fieldName: "Mcipio." },
        { el: selectBarrio,     validator: validateSelect,   required: true, fieldName: "Barrio" },
        { el: idGen,            validator: validateSelect,       required: true, fieldName: "Género" },
        { el: fotoInput,        validator: validateFoto,         required: false, fieldName: "Foto"},
        { el: newPass,          validator: validateNewPassword,  required: false, fieldName: "Nueva Contraseña" },
        { el: confirmPass,      validator: validateConfirmPass,  required: false, fieldName: "Confirmar Contraseña" }
    ];

    function setValidationMessage(el, msg, isValid) { const errDiv = el.nextElementSibling; if (errDiv && errDiv.classList.contains('invalid-feedback')) { errDiv.textContent = msg; } if (isValid) { el.classList.remove('is-invalid'); } else if (msg) { el.classList.add('is-invalid'); } else { el.classList.remove('is-invalid'); } }
    
    function validateNombre(i) { 
        const val = i.value.trim();
        if (val === "" && i.hasAttribute('required')) return { isValid: false, message: "Nombre requerido." }; 
        if (val !== "" && !/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/.test(val)) return { isValid: false, message: "Solo letras y espacios." };
        if (val !== "" && val.length < 5) return { isValid: false, message: "Mínimo 5 caracteres."};
        if (val.length > 100) return { isValid: false, message: "Máximo 100 caracteres." }; 
        return { isValid: true, message: "" }; 
    }
    function validateCorreo(i) { if (i.value.trim() === "" && i.hasAttribute('required')) return { isValid: false, message: "Correo requerido." }; if (i.value.trim() !== "" && !/^\w+([.-_+]?\w+)*@\w+([.-]?\w+)*(\.\w{2,10})+$/.test(i.value.trim())) return { isValid: false, message: "Correo inválido." }; if (i.value.trim().length > 150) return { isValid: false, message: "Máx 150." }; return { isValid: true, message: "" }; }
    
    function validateTelefono(i) { 
        const val = i.value.trim();
        if (val === "" && !i.hasAttribute('required')) return { isValid: true, message: ""};
        if (val !== "" && !/^\d{7,11}$/.test(val)) return { isValid: false, message: "Solo numeros 7-10 digitos" }; 
        return { isValid: true, message: "" }; 
    }
    function validateFechaNac(i) { if (!i.value && i.hasAttribute('required')) return { isValid: false, message: "F. Nacimiento requerida."}; if (i.value) { const hoy = new Date(); hoy.setHours(0,0,0,0); const fechaN = new Date(i.value + "T00:00:00Z"); if (isNaN(fechaN.getTime())) return { isValid: false, message: "Fecha inválida."}; if (fechaN >= hoy) return { isValid: false, message: "Anterior a hoy." }; const edadMin = new Date(hoy.getFullYear() - 120, hoy.getMonth(), hoy.getDate()); if (fechaN < edadMin) return { isValid: false, message: "Fecha muy antigua."}; } return { isValid: true, message: "" }; }
    function validateDireccion(i) { if (i.value.trim().length > 200) return { isValid: false, message: "Máx 200."}; return { isValid: true, message: "" }; }
    function validateSelect(i, field) { if (i.value === "" && i.hasAttribute('required') && !i.disabled) return { isValid: false, message: `${field} requerido.`}; return { isValid: true, message: "" }; }
    function validateNewPassword(i) { if (i.value.trim() === "") return { isValid: true, message: "" }; if (i.value.length < 8) return { isValid: false, message: "Mín 8." }; if (!/[a-z]/.test(i.value)) return { isValid: false, message: "Req. minús." }; if (!/[A-Z]/.test(i.value)) return { isValid: false, message: "Req. mayús." }; if (!/\d/.test(i.value)) return { isValid: false, message: "Req. núm." }; if (!/[\W_]/.test(i.value)) return { isValid: false, message: "Req. símb." }; return { isValid: true, message: "" }; }
    function validateConfirmPass(i) { const newP = document.getElementById('pass_modal'); if (newP && newP.value.trim() === "" && i.value.trim() === "") return { isValid: true, message: "" }; if (newP && newP.value !== i.value) return { isValid: false, message: "No coinciden." }; if (newP && newP.value.trim() !== "" && i.value.trim() === "") return { isValid: false, message: "Confirme."}; return { isValid: true, message: "" }; }
    function validateFoto(i) { if (i.files && i.files[0]) { const f = i.files[0]; const types = ['image/jpeg', 'image/png', 'image/gif']; if (!types.includes(f.type)) return { isValid: false, message: "JPG, PNG, GIF." }; if (f.size > 2 * 1024 * 1024) return { isValid: false, message: "Máx 2MB." }; } return { isValid: true, message: "" }; }
    
    function populateSel(selEl, opts, ph, selVal = null) { 
        selEl.innerHTML = `<option value="">${ph}</option>`; 
        opts.forEach(o => { 
            const opt = document.createElement('option'); 
            opt.value = o.id; 
            opt.textContent = o.nombre; 
            if (selVal && String(o.id) === String(selVal)) {
                 opt.selected = true; 
            }
            selEl.appendChild(opt); 
        }); 
        selEl.disabled = false;
        if (opts.length === 0 && (ph.toLowerCase().includes("seleccione") || ph.toLowerCase().includes("no hay") || ph.toLowerCase().includes("error"))) {
            if (ph.toLowerCase().includes("seleccione") && selEl.hasAttribute('required')) {
                 selEl.disabled = true;
            }
        }
    }
    
    if(fotoInput && imagePreview){ fotoInput.addEventListener('change', function(e){ const f = e.target.files[0]; const vRes = validateFoto(this); setValidationMessage(this, vRes.message, vRes.isValid); if (f && vRes.isValid) { const reader = new FileReader(); reader.onload = function(ev) { imagePreview.src = ev.target.result; checkValidityAndDirty(); }; reader.readAsDataURL(f); } else if (!f) { imagePreview.src = originalImageSrc; checkValidityAndDirty();} else { checkValidityAndDirty(); }  }); }
    
    if(selectDepartamento){ 
        selectDepartamento.addEventListener('change', function() { 
            const idD = this.value; 
            populateSel(selectBarrio, [], "Seleccione Municipio...", null); 
            selectBarrio.disabled = true; 
            
            if (idD) { 
                populateSel(selectMunicipio, [], "Cargando Municipios...", null); 
                selectMunicipio.disabled = true;
                fetch('../ajax/get_municipios.php?id_dep=' + idD)
                .then(r => {
                    if (!r.ok) { throw new Error(`HTTP error ${r.status}`); }
                    return r.json();
                })
                .then(d => { 
                    populateSel(selectMunicipio, d, d.length > 0 ? "Seleccione Municipio..." : "No hay Municipios para este Dpto.", null); 
                }).catch(e => { 
                    console.error('Error fetching municipios:', e); 
                    populateSel(selectMunicipio, [], "Error al cargar Municipios.", null); 
                }).finally(() => {
                    checkValidityAndDirty();
                }); 
            } else {
                populateSel(selectMunicipio, [], "Seleccione Departamento...", null); 
                selectMunicipio.disabled = true;
                checkValidityAndDirty(); 
            }
        }); 
    }
    if(selectMunicipio){ 
        selectMunicipio.addEventListener('change', function() { 
            const idM = this.value; 
            if (idM) { 
                populateSel(selectBarrio, [], "Cargando Barrios...", null); 
                selectBarrio.disabled = true;
                fetch('../ajax/get_barrios.php?id_mun=' + idM)
                .then(r => {
                    if (!r.ok) { throw new Error(`HTTP error ${r.status}`); }
                    return r.json();
                })
                .then(d => { 
                    populateSel(selectBarrio, d, d.length > 0 ? "Seleccione Barrio..." : "No hay Barrios para este Mcipio.", null); 
                }).catch(e => { 
                    console.error('Error fetching barrios:', e); 
                    populateSel(selectBarrio, [], "Error al cargar Barrios.", null); 
                }).finally(() => {
                    checkValidityAndDirty();
                });
            } else {
                populateSel(selectBarrio, [], "Seleccione Municipio...", null); 
                selectBarrio.disabled = true;
                checkValidityAndDirty(); 
            }
        }); 
    }

    function checkValidityAndDirty() { 
        let formIsValid = true; 
        inputsConfig.forEach(c => { if (c.el) { 
            let vRes; 
            if (c.el.id === 'confirm_pass_modal') vRes = validateConfirmPass(c.el); 
            else if (c.el.tagName === 'SELECT') vRes = validateSelect(c.el, c.fieldName || 'elemento'); 
            else if (c.el.type === 'file') vRes = validateFoto(c.el); 
            else vRes = c.validator(c.el); 
            
            let fieldIsInvalid = false;
            if (c.required && (c.el.value === null || String(c.el.value).trim() === "") && c.el.type !== 'file' && !c.el.disabled) {
                setValidationMessage(c.el, `${c.fieldName || 'Campo'} requerido.`, false);
                fieldIsInvalid = true;
            } else {
                setValidationMessage(c.el, vRes.message, vRes.isValid);
                if (!vRes.isValid && !c.el.disabled) {
                    fieldIsInvalid = true;
                }
            }
            if (fieldIsInvalid) formIsValid = false;
        }}); 
        const dirty = checkFormDirty();
        if (globalMessageDiv) { if (formIsValid && globalMessageDiv.classList.contains('alert-danger')) { globalMessageDiv.innerHTML = ''; globalMessageDiv.className = 'mt-3'; } } 
        if (saveButton) saveButton.disabled = !(formIsValid && dirty); 
        return formIsValid; 
    }
    inputsConfig.forEach(c => { if (c.el && c.el.type !== 'file') { const evType = (c.el.tagName === 'SELECT' || c.el.type === 'date') ? 'change' : 'input'; c.el.addEventListener(evType, () => { if (c.el.id === 'pass_modal') { const confP = document.getElementById('confirm_pass_modal'); if(confP) { const res = validateConfirmPass(confP); setValidationMessage(confP, res.message, res.isValid); }} checkValidityAndDirty(); }); c.el.addEventListener('blur', () => { if (c.el.id === 'pass_modal') { const confP = document.getElementById('confirm_pass_modal'); if(confP) { const res = validateConfirmPass(confP); setValidationMessage(confP, res.message, res.isValid); }} checkValidityAndDirty(); }); } });
    
    storeInitialFormState(); 
    checkValidityAndDirty(); 
    
    form.addEventListener('submit', function(ev) {
        ev.preventDefault();
        if (!checkValidityAndDirty()) { if (globalMessageDiv) { globalMessageDiv.innerHTML = '<div class="alert alert-danger">Corrija errores o realice cambios.</div>'; } return; }
        if (saveButton) saveButton.disabled = true;
        if (globalMessageDiv) globalMessageDiv.innerHTML = '<div class="alert alert-info d-flex align-items-center"><div class="spinner-border spinner-border-sm me-2"></div><span>Guardando...</span></div>';
        const formDt = new FormData(form);

        fetch('mi_perfil.php', { method: 'POST', body: formDt }) 
        .then(r => {
            if (!r.ok) { 
                return r.text().then(text => { throw new Error(`HTTP error! status: ${r.status}, response: ${text}`); });
            }
            const contentType = r.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return r.json();
            } else {
                return r.text().then(text => { throw new Error(`Respuesta inesperada del servidor: ${text}`); });
            }
        })
        .then(data => {
            if (globalMessageDiv) { globalMessageDiv.className = 'mt-3 alert ' + (data.success ? 'alert-success' : 'alert-danger'); globalMessageDiv.innerHTML = data.message; }
            if (data.success) { 
                let newPhotoPathForDisplay = data.new_foto_usu_path_for_modal;
                if (newPhotoPathForDisplay && imagePreview) { 
                    imagePreview.src = newPhotoPathForDisplay + '?' + new Date().getTime(); 
                }
                if (data.new_nom_usu) {
                    const userNameNav = document.getElementById('userNameNavbar'); 
                    if (userNameNav) userNameNav.textContent = data.new_nom_usu;
                }
                if (newPhotoPathForDisplay) {
                    const userImageNav = document.getElementById('userImageNavbar'); 
                    if(userImageNav) userImageNav.src = newPhotoPathForDisplay + '?' + new Date().getTime();
                }
                storeInitialFormState(); 
                setTimeout(() => { 
                    const modalEl = document.getElementById('userProfileModal'); 
                    if(modalEl){ const modalInst = bootstrap.Modal.getInstance(modalEl); if(modalInst) modalInst.hide(); } 
                }, 1800);
            } else { if (saveButton) saveButton.disabled = false; }
        })
        .catch(e => { 
            console.error('Fetch error:', e); 
            if (globalMessageDiv) globalMessageDiv.innerHTML = `<div class="alert alert-danger">Error de conexión o procesamiento: ${e.message}</div>`; 
            if (saveButton) saveButton.disabled = false; 
        });
    });
    return checkValidityAndDirty;
}