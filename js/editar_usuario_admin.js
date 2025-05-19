function inicializarValidacionesEdicionAdmin() {
    const form = document.getElementById('editUserFormAdmin');
    if (!form) { return null; }
    const nomUsu = document.getElementById('nom_usu_edit');
    const correoUsu = document.getElementById('correo_usu_edit');
    const telUsu = document.getElementById('tel_usu_edit');
    const fechaNac = document.getElementById('fecha_nac_edit');
    const direccionUsu = document.getElementById('direccion_usu_edit');
    const selectDepartamento = document.getElementById('id_departamento_edit');
    const selectMunicipio = document.getElementById('id_municipio_edit');
    const selectBarrio = document.getElementById('id_barrio_edit');
    const idGen = document.getElementById('id_gen_edit');
    const idEst = document.getElementById('id_est_edit');
    const idRol = document.getElementById('id_rol_edit');
    const divEspecialidad = document.getElementById('div_especialidad_edit');
    const selectEspecialidad = document.getElementById('id_especialidad_edit');
    const saveButton = document.getElementById('saveUserChangesAdminButton');
    const globalMessageDiv = document.getElementById('modalEditUserUpdateMessage');
    
    const ID_ESPECIALIDAD_NO_APLICA = "46"; 
    let initialFormState = {};
    let formIsDirty = false;

    function storeInitialFormState() {
        initialFormState = {};
        formIsDirty = false;
        if (saveButton) saveButton.disabled = true;
        inputsCfg.forEach(c => { 
            if (c.el) { 
                initialFormState[c.el.id] = c.el.value; 
            } 
        });
    }

    function checkFormDirty() {
        formIsDirty = false;
        for (const c of inputsCfg) {
            if (c.el && initialFormState.hasOwnProperty(c.el.id)) {
                 if (initialFormState[c.el.id] !== c.el.value) {
                    formIsDirty = true; break;
                }
            }
        }
        return formIsDirty;
    }
    
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
        if (val !== "" && !/^\d{7,11}$/.test(val)) return { isValid: false, message: "7-11 dígitos." }; 
        return { isValid: true, message: "" }; 
    }
    function validateFechaNac(i) { if (!i.value && i.hasAttribute('required')) return { isValid: false, message: "F. Nacimiento requerida."}; if (i.value) { const hoy = new Date(); hoy.setHours(0,0,0,0); const fechaN = new Date(i.value + "T00:00:00Z"); if (isNaN(fechaN.getTime())) return { isValid: false, message: "Fecha inválida."}; if (fechaN >= hoy) return { isValid: false, message: "Anterior a hoy." }; const edadMin = new Date(hoy.getFullYear() - 120, hoy.getMonth(), hoy.getDate()); if (fechaN < edadMin) return { isValid: false, message: "Fecha muy antigua."}; } return { isValid: true, message: "" }; }
    function validateDireccion(i) { if (i.value.trim().length > 200) return { isValid: false, message: "Máx 200."}; return { isValid: true, message: "" }; }
    function validateSelect(i, field) { if (i.value === "" && i.hasAttribute('required') && !i.disabled) return { isValid: false, message: `${field} requerido.`}; return { isValid: true, message: "" }; }
    
    const inputsCfg = [ 
        { el: nomUsu, v: validateNombre, r: true, fN: "Nombre" }, 
        { el: correoUsu, v: validateCorreo, r: true, fN: "Correo" }, 
        { el: telUsu, v: validateTelefono, r: false, fN: "Teléfono" }, 
        { el: fechaNac, v: validateFechaNac, r: true, fN: "F. Nac." }, 
        { el: direccionUsu, v: validateDireccion, r: false, fN: "Dirección" }, 
        { el: selectDepartamento, v: validateSelect, r: true, fN: "Dpto." }, 
        { el: selectMunicipio, v: validateSelect, r: true, fN: "Mcipio." }, 
        { el: selectBarrio, v: validateSelect, r: true, fN: "Barrio" }, 
        { el: idGen, v: validateSelect, r: true, fN: "Género" }, 
        { el: idEst, v: validateSelect, r: true, fN: "Estado" }, 
        { el: idRol, v: validateSelect, r: true, fN: "Rol" }, 
        { el: selectEspecialidad, v: validateSelect, r: false, fN: "Especialidad" }
    ];
    
    function toggleEspecialidadField() {
        const isRolMedico = idRol && idRol.value === '4'; 
        if (divEspecialidad && selectEspecialidad) {
            divEspecialidad.style.display = isRolMedico ? 'block' : 'none';
            selectEspecialidad.required = isRolMedico;
            
            const espConfig = inputsCfg.find(c => c.el === selectEspecialidad);
            if (espConfig) { espConfig.r = isRolMedico; }

            if (!isRolMedico) { 
                selectEspecialidad.value = ID_ESPECIALIDAD_NO_APLICA; 
                setValidationMessage(selectEspecialidad, '', true);
            } else if (selectEspecialidad.value === ID_ESPECIALIDAD_NO_APLICA || selectEspecialidad.value === "") {
                 selectEspecialidad.value = "";
            }
        }
        checkValidityAndDirty();
    }

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
    if (idRol) { idRol.addEventListener('change', toggleEspecialidadField); }
    
    function checkValidityAndDirty() { 
        let formIsValid = true; 
        inputsCfg.forEach(c => { if (c.el) { 
            let vRes; 
            if (c.el.tagName === 'SELECT') vRes = validateSelect(c.el, c.fN || 'elemento'); 
            else vRes = c.v(c.el); 
            
            let fieldIsInvalid = false;
            if (c.r && (c.el.value === null || String(c.el.value).trim() === "") && !c.el.disabled) {
                setValidationMessage(c.el, `${c.fN || 'Campo'} requerido.`, false);
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
    inputsCfg.forEach(c => { if (c.el) { const evType = (c.el.tagName === 'SELECT' || c.el.type === 'date') ? 'change' : 'input'; c.el.addEventListener(evType, () => { checkValidityAndDirty(); }); c.el.addEventListener('blur', () => { checkValidityAndDirty(); }); } });
    
    storeInitialFormState(); 
    toggleEspecialidadField(); 
    
    form.addEventListener('submit', function(ev) {
        ev.preventDefault();
        toggleEspecialidadField(); 
        if (!checkValidityAndDirty()) { 
            if (globalMessageDiv) { globalMessageDiv.innerHTML = '<div class="alert alert-danger">Corrija los errores marcados o realice algún cambio para guardar.</div>'; } 
            return; 
        }
        if (saveButton) saveButton.disabled = true;
        if (globalMessageDiv) globalMessageDiv.innerHTML = '<div class="alert alert-info d-flex align-items-center"><div class="spinner-border spinner-border-sm me-2"></div><span>Guardando cambios...</span></div>';
        const formDt = new FormData(form);
        fetch('editar_usuario.php', { method: 'POST', body: formDt })
        .then(r => {
            if (!r.ok) { 
                return r.text().then(text => { throw new Error(`Error HTTP ${r.status}: ${text}`); });
            }
            const contentType = r.headers.get("content-type");
            if (contentType && contentType.indexOf("application/json") !== -1) {
                return r.json();
            } else {
                return r.text().then(text => { throw new Error(`Respuesta inesperada del servidor (no es JSON): ${text}`); });
            }
        })
        .then(data => {
            if (globalMessageDiv) { globalMessageDiv.className = 'mt-3 alert ' + (data.success ? 'alert-success' : 'alert-danger'); globalMessageDiv.innerHTML = data.message; }
            if (data.success) { 
                storeInitialFormState(); 
                setTimeout(() => { 
                    const modalEl = document.getElementById('editUserModal'); 
                    if(modalEl){ const modalInst = bootstrap.Modal.getInstance(modalEl); if(modalInst) modalInst.hide(); } 
                    if (typeof cargarUsuarios === 'function') { cargarUsuarios(); } else { window.location.reload(); }
                }, 1800);
            } else { 
                if (saveButton) saveButton.disabled = false; 
            }
        })
        .catch(e => { 
            console.error('Error en el fetch o procesamiento:', e); 
            if (globalMessageDiv) globalMessageDiv.innerHTML = `<div class="alert alert-danger">Error de conexión o al procesar la respuesta: ${e.message}</div>`; 
            if (saveButton) saveButton.disabled = false; 
        });
    });
    
    if (selectDepartamento.value) {
        populateSel(selectMunicipio, [], "Cargando Municipios...", null); selectMunicipio.disabled = true;
        fetch('../ajax/get_municipios.php?id_dep=' + selectDepartamento.value)
            .then(r => r.ok ? r.json() : Promise.reject(new Error("Error al cargar municipios")))
            .then(d => { 
                const currentMunicipioVal = initialFormState['id_municipio_edit'];
                populateSel(selectMunicipio, d, d.length > 0 ? "Seleccione Municipio..." : "No hay Municipios", currentMunicipioVal);
                if(currentMunicipioVal && selectMunicipio.value === currentMunicipioVal && initialFormState['id_barrio_edit']){
                    populateSel(selectBarrio, [], "Cargando Barrios...", null); selectBarrio.disabled = true;
                     fetch('../ajax/get_barrios.php?id_mun=' + currentMunicipioVal)
                        .then(r => r.ok ? r.json() : Promise.reject(new Error("Error al cargar barrios")))
                        .then(dataBarrios => {
                            populateSel(selectBarrio, dataBarrios, dataBarrios.length > 0 ? "Seleccione Barrio..." : "No hay Barrios", initialFormState['id_barrio_edit']);
                        }).catch(eBarrios => {
                            populateSel(selectBarrio, [], "Error al cargar Barrios.", null);
                        }).finally(() => {
                             checkValidityAndDirty();
                        });
                } else {
                     checkValidityAndDirty(); 
                }
            }).catch(e => { 
                populateSel(selectMunicipio, [], "Error al cargar.", null); 
                checkValidityAndDirty(); 
            });
    } else {
        selectMunicipio.disabled = true;
        selectBarrio.disabled = true;
        checkValidityAndDirty();
    }
    
    return checkValidityAndDirty;
}