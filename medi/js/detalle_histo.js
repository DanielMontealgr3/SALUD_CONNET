// ARCHIVO: /medi/js/detalle_histo.js

document.addEventListener('DOMContentLoaded', function() {
    
    // --- LÓGICA DE VALIDACIÓN EN TIEMPO REAL CON MENSAJES ---

    const form = document.getElementById('formModalDetalles');
    if (!form) return;
    
    const btnGuardar = document.getElementById('btnGuardarDirecto');
    const inputsAValidar = form.querySelectorAll('[data-validate]');

    const reglas = {
        requerido: (valor) => valor.trim() !== '',
        numerico: (valor) => /^[0-9]+$/.test(valor) || valor.trim() === '', // Válido si está vacío o es número
        alfanumerico: (valor) => /^[a-zA-Z0-9\s.,áéíóúÁÉÍÓÚñÑ()-/]+$/.test(valor) || valor.trim() === ''
    };

    const mensajesError = {
        requerido: 'Este campo es obligatorio.',
        numerico: 'Este campo solo admite números.',
        alfanumerico: 'No se admiten caracteres especiales.'
    };

    const validarCampo = (input) => {
        const tiposValidacion = input.dataset.validate.split(' ');
        let esValido = true;
        
        for (const tipo of tiposValidacion) {
            if (!reglas[tipo](input.value)) {
                esValido = false;
                mostrarError(input, mensajesError[tipo]);
                break; // Muestra solo el primer error encontrado
            }
        }
        
        if (esValido) {
            ocultarError(input);
        }
        
        return esValido;
    };

    const mostrarError = (input, mensaje) => {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        const feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = mensaje;
        }
    };

    const ocultarError = (input) => {
        input.classList.remove('is-invalid');
        if (input.value.trim() !== '') {
            input.classList.add('is-valid');
        }
        const feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = '';
        }
    };

    const chequearValidezFormulario = () => {
        let todoValido = true;
        inputsAValidar.forEach(input => {
            if (!validarCampo(input)) {
                todoValido = false;
            }
        });
        // Deshabilitar botón solo en la última pestaña si el formulario no es válido.
        if (btnGuardar) {
            btnGuardar.disabled = !todoValido;
        }
    };

    inputsAValidar.forEach(input => {
        input.addEventListener('input', () => {
            validarCampo(input);
            chequearValidezFormulario();
        });
    });

    // --- LÓGICA DE FUNCIONAMIENTO DEL MODAL (código movido desde el PHP) ---
    const enfermedadesData = window.enfermedadesData || [];
    const medicamentosData = window.medicamentosData || [];
    
    const mainModalEl = document.getElementById('modalAgregarDetallesConsulta');
    if (!mainModalEl) return;

    // Lógica de navegación por pestañas
    const tabButtonArray = Array.from(mainModalEl.querySelectorAll('#detalleTabs .nav-link'));
    let currentTabIndex = 0;
    const TABS_COUNT = tabButtonArray.length;
    
    const btnPrev = document.getElementById('btnPrevTab');
    const btnNext = document.getElementById('btnNextTab');

    const updateModalFooter = () => {
        btnPrev.style.display = (currentTabIndex > 0) ? 'inline-block' : 'none';
        btnNext.style.display = (currentTabIndex < TABS_COUNT - 1) ? 'inline-block' : 'none';
        if(btnGuardar) btnGuardar.style.display = (currentTabIndex === TABS_COUNT - 1) ? 'inline-block' : 'none';
        
        if (currentTabIndex === TABS_COUNT - 1) {
            chequearValidezFormulario();
        } else {
             if(btnGuardar) btnGuardar.disabled = true; // Deshabilitar si no es la última pestaña
        }
    };

    tabButtonArray.forEach((button, index) => {
        button.addEventListener('shown.bs.tab', () => { currentTabIndex = index; updateModalFooter(); });
    });
    btnPrev.addEventListener('click', () => { if(currentTabIndex > 0) bootstrap.Tab.getOrCreateInstance(tabButtonArray[--currentTabIndex]).show(); });
    btnNext.addEventListener('click', () => { if(currentTabIndex < TABS_COUNT - 1) bootstrap.Tab.getOrCreateInstance(tabButtonArray[++currentTabIndex]).show(); });
    
    // Resetear el modal al abrirlo
    mainModalEl.addEventListener('show.bs.modal', () => {
        form.reset();
        inputsAValidar.forEach(ocultarError);
        // Resetear selects dependientes
        document.getElementById('modal_id_enferme').innerHTML = '<option value="">Primero seleccione tipo...</option>';
        document.getElementById('modal_id_medicam').innerHTML = '<option value="">Primero seleccione tipo...</option>';
        updateModalFooter();
    });

    // Lógica de selects dependientes
    const selectTipoEnfermedad = document.getElementById('modal_id_tipo_enfer');
    const selectEnfermedad = document.getElementById('modal_id_enferme');
    if (selectTipoEnfermedad) {
        selectTipoEnfermedad.addEventListener('change', () => {
            const tipoId = selectTipoEnfermedad.value;
            selectEnfermedad.innerHTML = `<option value="">${tipoId ? 'Seleccione...' : 'Primero seleccione tipo'}</option>`;
            enfermedadesData.filter(e => e.id_tipo_enfer == tipoId).forEach(e => {
                selectEnfermedad.innerHTML += `<option value="${e.id_enferme}">${e.nom_enfer}</option>`;
            });
        });
    }

    const selectTipoMedicamento = document.getElementById('modal_id_tip_medic');
    const selectMedicamento = document.getElementById('modal_id_medicam');
    if (selectTipoMedicamento) {
        selectTipoMedicamento.addEventListener('change', () => {
            const tipoId = selectTipoMedicamento.value;
            selectMedicamento.innerHTML = `<option value="">${tipoId ? 'Seleccione...' : 'Primero seleccione tipo'}</option>`;
            medicamentosData.filter(m => m.id_tipo_medic == tipoId).forEach(m => {
                selectMedicamento.innerHTML += `<option value="${m.id_medicamento}">${m.nom_medicamento}</option>`;
            });
        });
    }
});