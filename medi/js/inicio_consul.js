document.addEventListener('DOMContentLoaded', function () {
    const consultaModalEl = document.getElementById('modalConsulta');
    if (!consultaModalEl) return;

    const consultaModal = new bootstrap.Modal(consultaModalEl);
    const formConsulta = document.getElementById('formConsultaMedica');
    const btnGuardar = document.getElementById('btnGuardarConsulta');
    
    // ... (Reglas de validación y funciones de validación sin cambios)
    const validationRules = {
        texto: /^[a-zA-Z0-9\s.,áéíóúÁÉÍÓÚñÑ\/()-]+$/,
        presion: /^\d{2,3}\/\d{2,3}$/,
        saturacion: /^\d{2,3}$/,
        numerodecimal: /^\d+(\.\d{1,2})?$/
    };
    function validateField(input) { /* ... */ }
    function checkAllFieldsValidity() { /* ... */ }
    formConsulta.addEventListener('input', checkAllFieldsValidity);
    consultaModalEl.addEventListener('show.bs.modal', function (event) { /* ... */ });
    // (Incluyo las funciones completas para que solo copies y pegues)
    function validateField(input) {
        const ruleKey = input.dataset.validate;
        const rule = validationRules[ruleKey];
        if (!rule) return true;
        const isRequired = input.hasAttribute('required');
        const value = input.value.trim();
        let isValid = true;
        if (isRequired && value === '') {
            isValid = false;
        } else if (value !== '' && !rule.test(value)) {
            isValid = false;
        }
        input.classList.toggle('is-valid', isValid && value !== '');
        input.classList.toggle('is-invalid', !isValid && isRequired);
        return isValid;
    }
    function checkAllFieldsValidity() {
        const allValid = Array.from(formConsulta.querySelectorAll('[required]')).every(validateField);
        btnGuardar.disabled = !allValid;
    }
    consultaModalEl.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) return;
        formConsulta.reset();
        formConsulta.querySelectorAll('.is-valid, .is-invalid').forEach(el => el.classList.remove('is-valid', 'is-invalid'));
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="bi bi-save me-2"></i>Guardar y Continuar';
        const idCita = button.dataset.idCita || '';
        const docPaciente = button.dataset.docPaciente || '';
        const nomPaciente = button.dataset.nomPaciente || 'Paciente';
        formConsulta.querySelector('#modalIdCita').value = idCita;
        formConsulta.querySelector('#modalDocPacHidden').value = docPaciente;
        consultaModalEl.querySelector('#modalPacienteNombre').innerHTML = `<strong>Paciente:</strong> ${nomPaciente}`;
        consultaModalEl.querySelector('#modalPacienteDocumento').innerHTML = `<small class="text-muted">Documento: ${docPaciente}</small>`;
    });


    // --- BLOQUE DE SUBMIT TOTALMENTE CORREGIDO ---
    formConsulta.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        // 1. Detener el polling para evitar que la interfaz se actualice en segundo plano.
        if (typeof window.stopCitasPolling === 'function') {
            window.stopCitasPolling();
        }

        checkAllFieldsValidity();
        if (btnGuardar.disabled) {
            Swal.fire('Campos incompletos', 'Por favor, complete todos los campos requeridos.', 'warning');
            return;
        }
        
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

        const formData = new FormData(formConsulta);
        const url = `${AppConfig.BASE_URL}/medi/guarda_consul.php`;

        try {
            const response = await fetch(url, { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success && result.redirect_url) {
                // 2. Eliminar la tarjeta de la cita de la vista principal.
                const idCita = formData.get('id_cita');
                const cardToRemove = document.getElementById(`cita-row-${idCita}`);
                if (cardToRemove) {
                    cardToRemove.classList.add('row-removing');
                    setTimeout(() => cardToRemove.remove(), 500);
                }

                // 3. Ocultar el modal y mostrar el mensaje de éxito
                consultaModal.hide();
                
                await Swal.fire({
                    title: '¡Operación Exitosa!',
                    text: 'La historia clínica ha sido guardada. Será redirigido para continuar.',
                    icon: 'success',
                    timer: 1500, // Se redirige automáticamente después de 1.5 segundos
                    timerProgressBar: true,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false
                });

                // 4. Redirigir INMEDIATAMENTE a la página de detalles.
                window.location.href = result.redirect_url;

            } else {
                throw new Error(result.message || 'Error desconocido al guardar.');
            }
        } catch (error) {
            Swal.fire('Error de Conexión', error.message, 'error');
            console.error('Error en fetch:', error);
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="bi bi-save me-2"></i>Guardar y Continuar';
        }
    });
});