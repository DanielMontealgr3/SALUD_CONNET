document.addEventListener('DOMContentLoaded', function () {
    const consultaModalEl = document.getElementById('modalConsulta');
    const exitoModalEl = document.getElementById('modalExitoConsulta');

    if (!consultaModalEl || !exitoModalEl) return;

    const consultaModal = new bootstrap.Modal(consultaModalEl);
    const exitoModal = new bootstrap.Modal(exitoModalEl);

    const formConsulta = document.getElementById('formConsultaMedica');
    const btnGuardar = document.getElementById('btnGuardarConsulta');
    const camposRequeridos = Array.from(formConsulta.querySelectorAll('[required]'));

    // --- Definición de Reglas de Validación ---
    const validationRules = {
        motivo_de_cons: /^[a-zA-Z0-9\s.,áéíóúÁÉÍÓÚñÑ]+$/,
        presion: /^[0-9]{2,3}\/[0-9]{2,3}$/,
        saturacion: /^[0-9]{2,3}$/,
        peso: /^[0-9]+(\.[0-9]{1,2})?$/,
        estatura: /^[0-9]+(\.[0-9]{1,2})?$/,
        observaciones: /^[a-zA-Z0-9\s.,áéíóúÁÉÍÓÚñÑ]+$/
    };

    function validateField(input) {
        const rule = validationRules[input.id];
        if (!rule) return true; // Si no hay regla, se considera válido

        const isValid = rule.test(input.value);

        if (input.value.trim() === '') {
            input.classList.remove('is-valid', 'is-invalid');
            return false;
        }

        if (isValid) {
            input.classList.add('is-valid');
            input.classList.remove('is-invalid');
        } else {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
        }
        return isValid;
    }

    function checkAllFieldsValidity() {
        const allValid = camposRequeridos.every(validateField);
        btnGuardar.disabled = !allValid;
    }

    camposRequeridos.forEach(campo => {
        campo.addEventListener('input', () => {
            validateField(campo);
            checkAllFieldsValidity();
        });
    });

    consultaModalEl.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) return;

        const idCita = button.getAttribute('data-id-cita');
        const docPaciente = button.getAttribute('data-doc-paciente');
        const nomPaciente = button.getAttribute('data-nom-paciente');

        consultaModalEl.querySelector('#modalIdCita').value = idCita || '';
        consultaModalEl.querySelector('#modalDocPacHidden').value = docPaciente || '';
        consultaModalEl.querySelector('#modalConsultaLabel').textContent = 'Consulta Médica para ' + (nomPaciente || 'Paciente');
        consultaModalEl.querySelector('#modalPacienteNombre').innerHTML = `<strong>Paciente:</strong> ${nomPaciente || 'N/A'}`;
        consultaModalEl.querySelector('#modalPacienteDocumento').innerHTML = `<small class="text-muted">Documento: ${docPaciente || 'N/A'}</small>`;

        formConsulta.reset();
        camposRequeridos.forEach(campo => {
            campo.classList.remove('is-valid', 'is-invalid');
        });
        btnGuardar.disabled = true;
    });

    formConsulta.addEventListener('submit', async function(event) {
        event.preventDefault();
        checkAllFieldsValidity();

        if (btnGuardar.disabled) {
            alert('Por favor, corrija los campos marcados en rojo antes de guardar.');
            return;
        }
        
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

        const formData = new FormData(formConsulta);

        try {
            // ----- CORRECCIÓN AQUÍ: de 'guardar_consul.php' a 'guarda_consul.php' -----
            const response = await fetch('guarda_consul.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                consultaModal.hide();
                exitoModal.show();
                const btnRedirigir = document.getElementById('btnRedirigir');
                btnRedirigir.onclick = () => window.location.href = result.redirect_url;
                exitoModalEl.addEventListener('hidden.bs.modal', () => {
                    window.location.href = result.redirect_url;
                }, { once: true });
            } else {
                alert('Error al guardar: ' + (result.message || 'Ocurrió un error.'));
            }
        } catch (error) {
            alert('Error de conexión o respuesta inválida del servidor. Verifique la consola.');
            console.error('Error en fetch:', error);
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = 'Guardar Consulta';
            checkAllFieldsValidity();
        }
    });
});