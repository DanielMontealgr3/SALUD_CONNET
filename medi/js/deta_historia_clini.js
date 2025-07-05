document.addEventListener('DOMContentLoaded', function() {
    
    const modalAgregarEl = document.getElementById('modalAgregarDetalles');
    if (!modalAgregarEl) return;

    const form = document.getElementById('formModalDetalles');
    const agregarModal = new bootstrap.Modal(modalAgregarEl);
    
    const modalVerEl = document.getElementById('modalVerDetalle');
    const modalVerBody = document.getElementById('modalVerDetalleBody');
    const verModal = new bootstrap.Modal(modalVerEl);
    
    const detallesContainer = document.getElementById('listaDetallesContainer');

    const NO_APLICA_IDS = { TIPO_ENFERMEDAD: '22', ENFERMEDAD: '116', DIAGNOSTICO: '49', TIPO_MEDICAMENTO: '26', MEDICAMENTO: '40', PROCEDIMIENTO: '36' };
    
    const selects = {
        tipoEnfermedad: document.getElementById('modal_id_tipo_enfer'),
        enfermedad: document.getElementById('modal_id_enferme'),
        diagnostico: document.getElementById('modal_id_diagnostico'),
        tipoMedicamento: document.getElementById('modal_id_tip_medic'),
        medicamento: document.getElementById('modal_id_medicam'),
        procedimiento: document.getElementById('modal_id_proced')
    };

    const inputs = {
        cantidadMed: document.getElementById('modal_can_medica'),
        posologia: document.getElementById('modal_prescripcion_texto'),
        cantidadProc: document.getElementById('modal_cant_proced')
    };
    
    const buttons = {
        prev: document.getElementById('btnPrevTab'),
        next: document.getElementById('btnNextTab'),
        save: document.getElementById('btnGuardarDetallesAjax'),
        finalizar: document.getElementById('btnFinalizarConsulta')
    };

    const tabButtons = Array.from(modalAgregarEl.querySelectorAll('#detalleTabs .nav-link'));
    let currentTabIndex = 0;

    const resetFieldValidation = (field) => field.classList.remove('is-valid', 'is-invalid');
    const validateField = (field) => {
        if (!field.required) { resetFieldValidation(field); return true; }
        const isValid = field.value.trim() !== '';
        field.classList.toggle('is-valid', isValid);
        field.classList.toggle('is-invalid', !isValid);
        return isValid;
    };

    const isDiagnosisTabValid = () => validateField(selects.tipoEnfermedad) && validateField(selects.enfermedad) && validateField(selects.diagnostico);
    const isPrescriptionTabValid = () => {
        const selectsValid = validateField(selects.tipoMedicamento) && validateField(selects.medicamento);
        return (selects.medicamento.value === NO_APLICA_IDS.MEDICAMENTO) ? selectsValid : selectsValid && validateField(inputs.cantidadMed) && validateField(inputs.posologia);
    };
    const isProcedureTabValid = () => {
        const selectValid = validateField(selects.procedimiento);
        return (selects.procedimiento.value === NO_APLICA_IDS.PROCEDIMIENTO) ? selectValid : selectValid && validateField(inputs.cantidadProc);
    };

    const checkCurrentTabValidity = () => {
        if (currentTabIndex === 0) return isDiagnosisTabValid();
        if (currentTabIndex === 1) return isPrescriptionTabValid();
        if (currentTabIndex === 2) return isProcedureTabValid();
        return false;
    };
    
    const updateUI = () => {
        buttons.prev.style.display = currentTabIndex > 0 ? 'inline-block' : 'none';
        buttons.next.style.display = currentTabIndex < tabButtons.length - 1 ? 'inline-block' : 'none';
        buttons.save.style.display = currentTabIndex === tabButtons.length - 1 ? 'inline-block' : 'none';
        const isTabValid = checkCurrentTabValidity();
        buttons.next.disabled = !isTabValid;
        buttons.save.disabled = !isTabValid;
    };
    
    selects.tipoEnfermedad.addEventListener('change', () => {
        const tipoId = selects.tipoEnfermedad.value;
        selects.enfermedad.innerHTML = '';
        if (tipoId === NO_APLICA_IDS.TIPO_ENFERMEDAD) {
            selects.enfermedad.add(new Option('No aplica', NO_APLICA_IDS.ENFERMEDAD, true, true));
        } else {
            selects.enfermedad.add(new Option('Seleccione una enfermedad...', '', true, true));
            window.phpData.enfermedades
                .filter(e => e.id_tipo_enfer == tipoId && e.id_enferme != NO_APLICA_IDS.ENFERMEDAD)
                .forEach(e => selects.enfermedad.add(new Option(e.nom_enfer, e.id_enferme)));
        }
        isDiagnosisTabValid();
        updateUI();
    });

    selects.tipoMedicamento.addEventListener('change', () => {
        const tipoId = selects.tipoMedicamento.value;
        selects.medicamento.innerHTML = '';
        if (tipoId === NO_APLICA_IDS.TIPO_MEDICAMENTO) {
            selects.medicamento.add(new Option('No Aplica', NO_APLICA_IDS.MEDICAMENTO, true, true));
            inputs.cantidadMed.value = '0';
            inputs.posologia.value = 'No Aplica';
            inputs.cantidadMed.required = false;
            inputs.posologia.required = false;
            resetFieldValidation(inputs.cantidadMed);
            resetFieldValidation(inputs.posologia);
        } else {
            selects.medicamento.add(new Option('Seleccione un medicamento...', '', true, true));
            window.phpData.medicamentos
                .filter(m => m.id_tipo_medic == tipoId && m.id_medicamento != NO_APLICA_IDS.MEDICAMENTO)
                .forEach(m => selects.medicamento.add(new Option(m.nom_medicamento, m.id_medicamento)));
            inputs.cantidadMed.value = '';
            inputs.posologia.value = '';
            inputs.cantidadMed.required = true;
            inputs.posologia.required = true;
        }
        isPrescriptionTabValid();
        updateUI();
    });
    
    selects.procedimiento.addEventListener('change', () => {
        const noAplica = selects.procedimiento.value === NO_APLICA_IDS.PROCEDIMIENTO;
        inputs.cantidadProc.required = !noAplica;
        if(noAplica) { inputs.cantidadProc.value = '0'; resetFieldValidation(inputs.cantidadProc); } 
        else { inputs.cantidadProc.value = ''; }
        isProcedureTabValid();
        updateUI();
    });

    form.addEventListener('input', updateUI);
    form.addEventListener('change', updateUI);
    buttons.next.addEventListener('click', () => { if (currentTabIndex < tabButtons.length - 1) bootstrap.Tab.getOrCreateInstance(tabButtons[++currentTabIndex]).show(); });
    buttons.prev.addEventListener('click', () => { if (currentTabIndex > 0) bootstrap.Tab.getOrCreateInstance(tabButtons[--currentTabIndex]).show(); });
    tabButtons.forEach((button, index) => button.addEventListener('shown.bs.tab', () => { currentTabIndex = index; updateUI(); }));
    
    modalAgregarEl.addEventListener('show.bs.modal', () => {
        form.reset();
        form.querySelectorAll('.is-valid, .is-invalid').forEach(el => el.classList.remove('is-valid', 'is-invalid'));
        selects.enfermedad.innerHTML = '<option value="" disabled selected>Seleccione un tipo primero...</option>';
        selects.medicamento.innerHTML = '<option value="" disabled selected>Seleccione un tipo primero...</option>';
        currentTabIndex = 0;
        if(tabButtons.length > 0) bootstrap.Tab.getOrCreateInstance(tabButtons[0]).show();
        updateUI();
    });
    
    buttons.save.addEventListener('click', async function() {
        if (!checkCurrentTabValidity()) { Swal.fire('Campos incompletos', 'Por favor, complete todos los campos requeridos.', 'warning'); return; }
        
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';
        const formData = new FormData(form);
        const url = `${AppConfig.BASE_URL}/medi/includes_medi/ajax_guardar_detalles.php`;
        
        try {
            const response = await fetch(url, { method: 'POST', body: formData });
            const result = await response.json();
            if (response.ok && result.success) {
                agregarModal.hide();
                await Swal.fire({ icon: 'success', title: '¡Guardado!', text: result.message, timer: 1500, showConfirmButton: false });
                window.location.reload();
            } else { throw new Error(result.message || 'Error del servidor.'); }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Error al Guardar', text: error.message });
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-save-fill me-1"></i> Guardar y Cerrar';
        }
    });

    detallesContainer.addEventListener('click', async function(e) {
        const verBtn = e.target.closest('.ver-detalle-btn');
        if (!verBtn) return;

        const detalleId = verBtn.dataset.idDetalle;
        modalVerBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"></div></div>';
        
        try {
            const response = await fetch(`${AppConfig.BASE_URL}/medi/includes_medi/ajax_get_detalle.php?id=${detalleId}`);
            if (!response.ok) throw new Error('Error de red al contactar al servidor.');
            
            const result = await response.json();

            if (result.success) {
                let html = '';
                const detalle = result.data;

                if (detalle.nombre_diagnostico) {
                    html += `<div class="detail-section"><h5>Diagnóstico</h5><p><strong>Tipo:</strong> ${detalle.nom_enfer || 'N/A'}<br><strong>Diagnóstico (CIE-10):</strong> ${detalle.nombre_diagnostico}</p></div>`;
                }
                
                if (detalle.nom_medicamento) {
                    html += `<div class="detail-section"><h5>Prescripción</h5><p><strong>Medicamento:</strong> ${detalle.nom_medicamento}<br><strong>Cantidad:</strong> ${detalle.can_medica}<br><strong>Posología:</strong> ${detalle.prescripcion}</p></div>`;
                }

                if (detalle.nombre_procedimiento) {
                    html += `<div class="detail-section"><h5>Procedimiento</h5><p><strong>Procedimiento:</strong> ${detalle.nombre_procedimiento}<br><strong>Cantidad:</strong> ${detalle.cant_proced || 'N/A'}</p></div>`;
                }
                modalVerBody.innerHTML = html || '<p class="text-muted">No hay información específica en este registro.</p>';
            } else {
                throw new Error(result.message || 'No se pudo cargar el detalle.');
            }
        } catch (error) {
            modalVerBody.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        }
    });
});