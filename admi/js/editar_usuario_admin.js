document.addEventListener('DOMContentLoaded', () => {
    const observer = new MutationObserver((mutationsList, obs) => {
        for (const mutation of mutationsList) {
            if (mutation.type === 'childList') {
                const editModal = document.getElementById('editUserModal');
                if (editModal && !editModal.dataset.initialized) {
                    editModal.dataset.initialized = 'true';
                    initializeEditUserForm();
                    obs.disconnect(); 
                }
            }
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });
});

function initializeEditUserForm() {
    const form = document.getElementById('editUserFormAdmin');
    if (!form) return;

    const allInputs = Array.from(form.querySelectorAll('input:not([type="hidden"]), select'));
    const saveButton = document.getElementById('saveUserChangesAdminButton');
    const selectDepartamento = document.getElementById('id_departamento_edit');
    const selectMunicipio = document.getElementById('id_municipio_edit');
    const selectBarrio = document.getElementById('id_barrio_edit');
    const idRol = document.getElementById('id_rol_edit');
    const divEspecialidad = document.getElementById('div_especialidad_edit');
    const selectEspecialidad = document.getElementById('id_especialidad_edit');

    let initialFormState = {};
    const initialMunicipioId = form.dataset.initialMunicipio;
    const initialBarrioId = form.dataset.initialBarrio;

    const storeInitialState = () => {
        initialFormState = {};
        allInputs.forEach(input => {
            if (input && input.name) {
                initialFormState[input.name] = input.value;
            }
        });
    };

    const checkFormDirty = () => {
        for (const input of allInputs) {
            if (input && input.name && initialFormState[input.name] !== input.value) {
                return true;
            }
        }
        return false;
    };

    const setValidation = (element, isValid, message = '') => {
        if (!element || element.disabled) return;
        const feedback = element.nextElementSibling;
        
        element.classList.remove('is-invalid', 'is-valid');
        
        if (isValid) {
            if (initialFormState[element.name] !== element.value) {
                 element.classList.add('is-valid');
            }
            if (feedback) feedback.textContent = '';
        } else {
            element.classList.add('is-invalid');
            if (feedback) feedback.textContent = message;
        }
    };
    
    const validators = {
        nom_usu_edit: val => val ? (/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(val) ? {valid: true} : {valid: false, message: 'Solo letras y espacios.'}) : {valid: false, message: 'El nombre es obligatorio.'},
        correo_usu_edit: val => val ? (/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val) ? {valid: true} : {valid: false, message: 'Formato de correo inválido.'}) : {valid: false, message: 'El correo es obligatorio.'},
        tel_usu_edit: val => val ? (/^\d{7,10}$/.test(val) ? {valid: true} : {valid: false, message: 'Debe ser de 7 a 10 dígitos.'}) : {valid: false, message: 'El teléfono es obligatorio.'},
        direccion_usu_edit: val => val ? (val.length >= 7 ? {valid: true} : {valid: false, message: 'Mínimo 7 caracteres.'}) : {valid: false, message: 'La dirección es obligatoria.'},
        requiredSelect: val => val ? {valid: true} : {valid: false, message: 'Debe seleccionar una opción.'},
        especialidad: val => (idRol.value === '4' && (!val || val === '46')) ? {valid: false, message: 'Especialidad es requerida.'} : {valid: true}
    };

    const validateField = (input) => {
        if (!input || input.disabled) return true;
        let validator = input.tagName === 'SELECT' ? 
            ((input.id === 'id_especialidad_edit') ? validators.especialidad : validators.requiredSelect) 
            : validators[input.id];

        if(validator) {
            const result = validator(input.value.trim());
            setValidation(input, result.valid, result.message);
            return result.valid;
        }
        return true;
    };

    const updateButtonState = () => {
        if (!saveButton) return;
        let isFormValid = allInputs.every(input => validateField(input));
        const isDirty = checkFormDirty();
        saveButton.disabled = !(isFormValid && isDirty);
    };

    const populateSelect = (select, data, placeholder, selectedValue = null, valueKey, textKey) => {
        select.innerHTML = `<option value="">${placeholder}</option>`;
        if (data && data.length > 0) {
            data.forEach(item => {
                const option = new Option(item[textKey], item[valueKey]);
                if (selectedValue && option.value == selectedValue) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
            select.disabled = false;
        } else {
            select.disabled = true;
        }
    };

    async function loadMunicipios(idDep, selectedValue = null) {
        selectMunicipio.innerHTML = '<option value="">Cargando...</option>';
        selectMunicipio.disabled = true;
        if (!idDep) {
            populateSelect(selectMunicipio, [], "Seleccione Departamento", null, 'id_mun', 'nom_mun');
            return;
        }
        try {
            const response = await fetch(`../ajax/get_municipios.php?id_dep=${idDep}`);
            const data = await response.json();
            populateSelect(selectMunicipio, data, "Seleccione Municipio...", selectedValue, 'id_mun', 'nom_mun');
        } catch (error) {
            populateSelect(selectMunicipio, [], "Error al cargar", null, 'id_mun', 'nom_mun');
        }
    }

    async function loadBarrios(idMun, selectedValue = null) {
        selectBarrio.innerHTML = '<option value="">Cargando...</option>';
        selectBarrio.disabled = true;
        if (!idMun) {
            populateSelect(selectBarrio, [], "Seleccione Municipio", null, 'id_barrio', 'nom_barrio');
            return;
        }
        try {
            const response = await fetch(`../ajax/get_barrios.php?id_mun=${idMun}`);
            const data = await response.json();
            populateSelect(selectBarrio, data, "Seleccione Barrio...", selectedValue, 'id_barrio', 'nom_barrio');
        } catch (error) {
            populateSelect(selectBarrio, [], "Error al cargar", null, 'id_barrio', 'nom_barrio');
        }
    }

    selectDepartamento.addEventListener('change', async function() {
        await loadMunicipios(this.value);
        await loadBarrios(null);
        updateButtonState();
    });

    selectMunicipio.addEventListener('change', async function() {
        await loadBarrios(this.value);
        updateButtonState();
    });

    idRol.addEventListener('change', function() {
        const isMedico = this.value === '4';
        divEspecialidad.style.display = isMedico ? 'block' : 'none';
        selectEspecialidad.required = isMedico;
        if (!isMedico) {
            selectEspecialidad.value = '46';
        }
        updateButtonState();
    });

    allInputs.forEach(input => {
        input.addEventListener('input', updateButtonState);
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        updateButtonState();
        if(saveButton.disabled) return;

        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
        
        const modalEl = document.getElementById('editUserModal');
        const modal = bootstrap.Modal.getInstance(modalEl);

        fetch(form.action, { method: 'POST', body: new FormData(form) })
            .then(response => response.json())
            .then(data => {
                if(modal) {
                    modal.hide();
                }
                
                Swal.fire({
                    title: data.success ? '¡Éxito!' : 'Error',
                    text: data.message,
                    icon: data.success ? 'success' : 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#0d6efd'
                }).then((result) => {
                    if (data.success && result.isConfirmed) {
                        location.reload();
                    }
                });
            })
            .catch(err => {
                 if(modal) {
                    modal.hide();
                }
                Swal.fire({
                    title: 'Error de Conexión',
                    text: 'No se pudo comunicar con el servidor. ' + err,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#0d6efd'
                });
            }).finally(() => {
                saveButton.disabled = false;
                saveButton.innerHTML = 'Guardar Cambios';
            });
    });

    async function initializeForm() {
        const idDep = selectDepartamento.value;
        if (idDep) {
            await loadMunicipios(idDep, initialMunicipioId);
            if (initialMunicipioId) {
                await loadBarrios(initialMunicipioId, initialBarrioId);
            }
        }
        storeInitialState();
        updateButtonState();
    }
    
    initializeForm();
}