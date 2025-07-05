// ARCHIVO: /medi/js/deta_historia_clini.js (VERSIÓN FINAL CON GUARDADO PERMISIVO)

document.addEventListener('DOMContentLoaded', function() {
    
    const form = document.getElementById('formModalDetalles');
    if (!form) return;
    
    const btnGuardar = document.getElementById('btnGuardarDirecto');
    const mainModalEl = document.getElementById('modalAgregarDetallesConsulta');
    
    // --- LÓGICA DE VALIDACIÓN DE FORMATO ---
    const reglas = {
        numerico: (valor) => /^[0-9]+$/.test(valor) || valor.trim() === '',
        alfanumerico: (valor) => /^[a-zA-Z0-9\s.,áéíóúÁÉÍÓÚñÑ()-/]+$/.test(valor) || valor.trim() === ''
    };
    const mensajesError = {
        numerico: 'Este campo solo admite números.',
        alfanumerico: 'No se admiten caracteres especiales.'
    };

    const validarFormatoCampo = (input) => {
        if (!input.dataset.validate) return true;
        const tiposValidacion = input.dataset.validate.split(' ');
        let esValido = true;
        for (const tipo of tiposValidacion) {
            if (reglas[tipo] && !reglas[tipo](input.value.split(' ')[0])) { // Valida solo la parte numérica
                esValido = false;
                mostrarError(input, mensajesError[tipo]);
                break;
            }
        }
        if (esValido) ocultarError(input);
        return esValido;
    };

    const mostrarError = (input, mensaje) => {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        const feedback = input.nextElementSibling;
        if (feedback) feedback.textContent = mensaje;
    };

    const ocultarError = (input) => {
        input.classList.remove('is-invalid');
        const feedback = input.nextElementSibling;
        if (feedback) feedback.textContent = '';
        if (input.value.trim() !== '') input.classList.add('is-valid');
        else input.classList.remove('is-valid');
    };

    // --- NUEVA LÓGICA SIMPLIFICADA PARA HABILITAR EL BOTÓN GUARDAR ---
    const actualizarEstadoBotonGuardar = () => {
        let formatoGeneralValido = true;
        form.querySelectorAll('[data-validate]').forEach(input => {
            if (!validarFormatoCampo(input)) {
                formatoGeneralValido = false;
            }
        });
        
        // El botón guardar solo se deshabilita si hay un error de FORMATO.
        if (btnGuardar) {
            btnGuardar.disabled = !formatoGeneralValido;
        }
    };
    
    // Escuchar cambios para re-evaluar el formato
    form.addEventListener('input', actualizarEstadoBotonGuardar);
    form.addEventListener('change', actualizarEstadoBotonGuardar);

    // --- LÓGICA DE NAVEGACIÓN DEL MODAL (LIBRE) ---
    const tabButtonArray = Array.from(mainModalEl.querySelectorAll('#detalleTabs .nav-link'));
    let currentTabIndex = 0;
    const TABS_COUNT = tabButtonArray.length;
    const btnPrev = document.getElementById('btnPrevTab');
    const btnNext = document.getElementById('btnNextTab');

    const updateModalFooter = () => {
        btnPrev.style.display = (currentTabIndex > 0) ? 'inline-block' : 'none';
        btnNext.style.display = (currentTabIndex < TABS_COUNT - 1) ? 'inline-block' : 'none';
        if (btnGuardar) btnGuardar.style.display = (currentTabIndex === TABS_COUNT - 1) ? 'inline-block' : 'none';
        
        if (btnNext) btnNext.disabled = false;
        
        actualizarEstadoBotonGuardar();
    };

    tabButtonArray.forEach((button, index) => {
        button.addEventListener('shown.bs.tab', () => { 
            currentTabIndex = index; 
            updateModalFooter(); 
        });
    });

    btnPrev.addEventListener('click', () => { if(currentTabIndex > 0) bootstrap.Tab.getOrCreateInstance(tabButtonArray[--currentTabIndex]).show(); });
    btnNext.addEventListener('click', () => { if(currentTabIndex < TABS_COUNT - 1) bootstrap.Tab.getOrCreateInstance(tabButtonArray[++currentTabIndex]).show(); });
    
    mainModalEl.addEventListener('show.bs.modal', () => {
        form.reset();
        form.querySelectorAll('.is-invalid, .is-valid').forEach(el => el.classList.remove('is-invalid', 'is-valid'));
        currentTabIndex = 0;
        if(tabButtonArray.length > 0) bootstrap.Tab.getOrCreateInstance(tabButtonArray[0]).show();
        updateModalFooter();
    });

    // --- LÓGICA DE SELECTS DEPENDIENTES ---
    const selectTipoEnfermedad = document.getElementById('modal_id_tipo_enfer');
    const selectEnfermedad = document.getElementById('modal_id_enferme');
    if (selectTipoEnfermedad) {
        selectTipoEnfermedad.addEventListener('change', () => {
            const tipoId = selectTipoEnfermedad.value;
            selectEnfermedad.innerHTML = '<option value="0" selected>No Aplica</option>';
            if (tipoId !== '0') {
                 window.enfermedadesData.filter(e => e.id_tipo_enfer == tipoId).forEach(e => {
                    selectEnfermedad.innerHTML += `<option value="${e.id_enferme}">${e.nom_enfer}</option>`;
                });
            }
            actualizarEstadoBotonGuardar();
        });
    }

    const selectTipoMedicamento = document.getElementById('modal_id_tip_medic');
    const selectMedicamento = document.getElementById('modal_id_medicam');
    if (selectTipoMedicamento) {
        selectTipoMedicamento.addEventListener('change', () => {
            const tipoId = selectTipoMedicamento.value;
            selectMedicamento.innerHTML = '<option value="0" selected>No Aplica</option>';
             if (tipoId !== '0') {
                window.medicamentosData.filter(m => m.id_tipo_medic == tipoId).forEach(m => {
                    selectMedicamento.innerHTML += `<option value="${m.id_medicamento}">${m.nom_medicamento}</option>`;
                });
             }
            actualizarEstadoBotonGuardar();
        });
    }
});