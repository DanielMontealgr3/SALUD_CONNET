function inicializarModalEdicionEntidad() {
    const modalContainer = document.getElementById('modalEditarEntidadContainer');
    if (!modalContainer) return;

    let activeModalInstance = null;
    let initialFormStateModal = {};
    let formIsDirtyModal = false;

    document.querySelectorAll('.btn-editar-entidad').forEach(button => {
        button.addEventListener('click', function() {
            const entidadId = this.dataset.id;
            const entidadTipo = this.dataset.tipo;
            loadModalContent(entidadId, entidadTipo);
        });
    });

    function loadModalContent(id, tipo) {
        modalContainer.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div><p>Cargando datos...</p></div>';
        fetch(`modal_editar_entidad.php?id=${encodeURIComponent(id)}&tipo=${encodeURIComponent(tipo)}`)
            .then(response => response.ok ? response.text() : Promise.reject(`Error ${response.status}`))
            .then(html => {
                modalContainer.innerHTML = html;
                const modalElement = document.getElementById('dynamicEditEntidadModal');
                if (modalElement) {
                    activeModalInstance = new bootstrap.Modal(modalElement);
                    activeModalInstance.show();
                    setupModalEventListeners(modalElement, tipo);
                    storeInitialModalFormState(modalElement);
                    checkModalFormDirtyAndValidity(modalElement, tipo); 
                } else {
                    modalContainer.innerHTML = '<div class="alert alert-danger">Error: No se pudo cargar el contenido del modal.</div>';
                }
            })
            .catch(error => {
                console.error('Error al cargar modal:', error);
                modalContainer.innerHTML = `<div class="alert alert-danger">Error al cargar datos para editar: ${error}</div>`;
            });
    }
    
    function storeInitialModalFormState(modalEl) {
        initialFormStateModal = {};
        formIsDirtyModal = false;
        const form = modalEl.querySelector('form');
        if (!form) return;
        form.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach(el => {
            initialFormStateModal[el.name] = el.value;
        });
        const btnGuardar = modalEl.querySelector('#btnGuardarCambiosEntidad');
        if(btnGuardar) btnGuardar.disabled = true;
    }

    function checkModalFormDirty(modalEl){
        formIsDirtyModal = false;
        const form = modalEl.querySelector('form');
        if (!form) return false;
        for (const elName in initialFormStateModal) {
            const currentEl = form.elements[elName];
            if (currentEl && initialFormStateModal[elName] !== currentEl.value) {
                formIsDirtyModal = true;
                break;
            }
        }
        return formIsDirtyModal;
    }

    function setupModalEventListeners(modalEl, tipoEntidad) {
        const form = modalEl.querySelector('#formActualizarEntidad');
        const btnGuardar = modalEl.querySelector('#btnGuardarCambiosEntidad');
        const messageDiv = modalEl.querySelector('#modalEditEntidadMessage');

        if (form && btnGuardar) {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                if (!validateModalForm(form, tipoEntidad)) {
                    if(messageDiv) messageDiv.innerHTML = '<div class="alert alert-danger">Por favor, corrija los errores.</div>';
                    return;
                }
                if(!formIsDirtyModal){
                     if(messageDiv) messageDiv.innerHTML = '<div class="alert alert-warning">No se han realizado cambios.</div>';
                     btnGuardar.disabled = true;
                     return;
                }

                btnGuardar.disabled = true;
                if(messageDiv) messageDiv.innerHTML = '<div class="alert alert-info">Guardando...</div>';
                const formData = new FormData(form);

                fetch('editar_entidad.php', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (messageDiv) {
                            messageDiv.className = 'mt-3 alert ' + (data.success ? 'alert-success' : 'alert-danger');
                            messageDiv.innerHTML = data.message;
                        }
                        if (data.success) {
                            setTimeout(() => {
                                if (activeModalInstance) activeModalInstance.hide();
                                window.location.reload(); 
                            }, 1800);
                        } else {
                            btnGuardar.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error al enviar formulario:', error);
                        if(messageDiv) messageDiv.innerHTML = `<div class="alert alert-danger">Error de conexión: ${error}</div>`;
                        btnGuardar.disabled = false;
                    });
            });

            form.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach(input => {
                const eventType = (input.tagName === 'SELECT' || input.type === 'date') ? 'change' : 'input';
                input.addEventListener(eventType, () => checkModalFormDirtyAndValidity(modalEl, tipoEntidad));
                input.addEventListener('blur', () => checkModalFormDirtyAndValidity(modalEl, tipoEntidad));
            });
        }

        if (tipoEntidad === 'ips') {
            const depSelect = modalEl.querySelector('#id_dep_ips_modal');
            const munSelect = modalEl.querySelector('#ubicacion_mun_ips_modal');
            if (depSelect && munSelect) {
                depSelect.addEventListener('change', function() {
                    const idDep = this.value;
                    munSelect.innerHTML = '<option value="">Cargando...</option>';
                    munSelect.disabled = true;
                    if (idDep) {
                        fetch(`../ajax/get_municipios.php?id_dep=${idDep}`)
                            .then(response => response.json())
                            .then(data => {
                                populateSelectWithOptions(munSelect, data, "Seleccione Municipio...");
                                checkModalFormDirtyAndValidity(modalEl, tipoEntidad);
                            })
                            .catch(err => {
                                console.error("Error cargando municipios para IPS:", err);
                                munSelect.innerHTML = '<option value="">Error al cargar</option>';
                                checkModalFormDirtyAndValidity(modalEl, tipoEntidad);
                            });
                    } else {
                        munSelect.innerHTML = '<option value="">Seleccione Departamento...</option>';
                        checkModalFormDirtyAndValidity(modalEl, tipoEntidad);
                    }
                });
            }
        }
         modalEl.addEventListener('hidden.bs.modal', function () {
            modalContainer.innerHTML = ''; 
            activeModalInstance = null;
        });
    }
    
    function checkModalFormDirtyAndValidity(modalEl, tipoEntidad) {
        const form = modalEl.querySelector('form');
        const btnGuardar = modalEl.querySelector('#btnGuardarCambiosEntidad');
        if (!form || !btnGuardar) return;
        
        const isValid = validateModalForm(form, tipoEntidad);
        const isDirty = checkModalFormDirty(modalEl);
        btnGuardar.disabled = !(isValid && isDirty);
    }

    function populateSelectWithOptions(selectElement, options, placeholder) {
        selectElement.innerHTML = `<option value="">${placeholder}</option>`;
        if (options && options.length > 0) {
            options.forEach(opt => {
                const optionEl = document.createElement('option');
                optionEl.value = opt.id;
                optionEl.textContent = opt.nombre;
                selectElement.appendChild(optionEl);
            });
            selectElement.disabled = false;
        } else {
            selectElement.innerHTML = `<option value="">No hay opciones</option>`;
            selectElement.disabled = true;
        }
    }

    function setFieldValidationStyle(el, isValid, message = "") {
        const feedbackEl = el.nextElementSibling && el.nextElementSibling.classList.contains('invalid-feedback') ? el.nextElementSibling : null;
        if (feedbackEl) {
            feedbackEl.textContent = message;
            feedbackEl.style.display = isValid ? 'none' : 'block';
        }
        el.classList.toggle('is-invalid', !isValid && message !== "");
        el.classList.remove('is-valid'); // No usamos is-valid
    }

    function validateModalField(el, isRequired, validationFn, fieldName, specificMsg = "") {
        let result = { isValid: true, message: "" };
        const val = el.value.trim();

        if (isRequired && val === "" && !el.disabled) {
            result = { isValid: false, message: `${fieldName} es requerido.` };
        } else if (val !== "" && validationFn) {
            result = validationFn(val);
            if(!result.isValid && specificMsg) result.message = specificMsg;
            else if (!result.isValid && !result.message) result.message = `${fieldName} inválido.`;
        }
        setFieldValidationStyle(el, result.isValid, result.message);
        return result.isValid;
    }

    function validateModalForm(form, tipo) {
        let allValid = true;
        if (tipo === 'farmacias') {
            if (!validateModalField(form.nom_farm_modal, true, v => ({isValid: v.length >= 3 && v.length <= 150 && /^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s.,'-]+$/.test(v)}), "Nombre Farmacia", "Nombre inválido (mín 3, máx 150, solo letras, números y algunos símbolos).")) allValid = false;
            if (!validateModalField(form.tel_farm_modal, false, v => ({isValid: /^\d{7,15}$/.test(v) || v === ""}), "Teléfono", "Solo numeros 7-15 dígitos.")) allValid = false;
            if (!validateModalField(form.correo_farm_modal, false, v => ({isValid: /^\S+@\S+\.\S+$/.test(v) || v === ""}), "Correo", "Correo inválido.")) allValid = false;
            if (!validateModalField(form.nom_gerente_farm_modal, false, v => ({isValid: /^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s.,'-]+$/.test(v) || v === ""}), "Nombre Gerente", "Nombre inavlido solo letras y espacios.")) allValid = false;

        } else if (tipo === 'eps') {
            if (!validateModalField(form.nombre_eps_modal, true, v => ({isValid: v.length >= 3 && v.length <= 150 && /^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s.,'-]+$/.test(v)}), "Nombre EPS", "Nombre inválido (mín 3, máx 150, solo letras, números y algunos símbolos).")) allValid = false;
            if (!validateModalField(form.telefono_eps_modal, false, v => ({isValid: /^\d{7,15}$/.test(v) || v === ""}), "Teléfono", "Solo numeros 7-15 dígitos.")) allValid = false;
            if (!validateModalField(form.correo_eps_modal, false, v => ({isValid: /^\S+@\S+\.\S+$/.test(v) || v === ""}), "Correo", "Correo inválido.")) allValid = false;
            if (!validateModalField(form.nom_gerente_eps_modal, false, v => ({isValid: /^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s.,'-]+$/.test(v) || v === ""}), "Nombre Gerente", "Nombre inavlido solo letras y espacios.")) allValid = false;

        } else if (tipo === 'ips') {
            if (!validateModalField(form.nom_ips_modal, true, v => ({isValid: v.length >= 3 && v.length <= 150 && /^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s.,'-]+$/.test(v)}), "Nombre IPS", "Nombre inválido (mín 3, máx 150, solo letras, números y algunos símbolos).")) allValid = false;
            if (!validateModalField(form.id_dep_ips_modal, true, null, "Departamento")) allValid = false;
            if (!validateModalField(form.ubicacion_mun_ips_modal, true, null, "Municipio")) allValid = false;
            if (!validateModalField(form.tel_ips_modal, false, v => ({isValid: /^\d{7,15}$/.test(v) || v === ""}), "Teléfono", "Solo numeros 7-15 dígitos.")) allValid = false;
            if (!validateModalField(form.correo_ips_modal, false, v => ({isValid: /^\S+@\S+\.\S+$/.test(v) || v === ""}), "Correo", "Correo inválido.")) allValid = false;
            if (!validateModalField(form.nom_gerente_ips_modal, false, v => ({isValid: /^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s.,'-]+$/.test(v) || v === ""}), "Nombre Gerente", "Nombre inavlido solo letras y espacios.")) allValid = false;
        }
        return allValid;
    }
}