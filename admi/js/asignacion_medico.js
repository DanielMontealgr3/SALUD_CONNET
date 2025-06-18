function inicializarLogicaAsignacionMedico() {
    const form = document.getElementById('formAsignarIPSModalInterno');
    const saveButton = document.getElementById('btnGuardarAsignacionModalInterno');
    const selectIps = document.getElementById('nit_ips_asignar_modal');
    const messageDiv = document.getElementById('modalAsignacionMessageInterno');

    if (!form || !saveButton || !selectIps) {
        console.error("Error: Elementos del formulario de asignación de médico no encontrados.");
        return;
    }

    const checkFormValidity = () => {
        saveButton.disabled = !selectIps.value;
    };

    const validateField = (field) => {
        if (!field.value) {
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            return false;
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            return true;
        }
    };

    selectIps.addEventListener('change', () => {
        validateField(selectIps);
        checkFormValidity();
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        event.stopPropagation();
        
        if (!validateField(selectIps)) {
            if (messageDiv) {
                messageDiv.innerHTML = `<div class="alert alert-warning">Debe seleccionar una IPS.</div>`;
            }
            return;
        }

        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

        const formData = new FormData(form);
        
        fetch(window.location.href, { 
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            Swal.fire({
                title: data.success ? '¡Éxito!' : 'Error',
                text: data.message,
                icon: data.success ? 'success' : 'error',
                timer: data.success ? 2000 : 4000,
                showConfirmButton: false,
                timerProgressBar: true
            }).then(() => {
                if (data.success) {
                    const modalElement = document.getElementById('modalContenedorAsignacion');
                    const modalInstance = bootstrap.Modal.getInstance(modalElement);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
            });
        })
        .catch(error => {
            Swal.fire({
                title: 'Error de Conexión',
                text: 'No se pudo comunicar con el servidor.',
                icon: 'error'
            });
        })
        .finally(() => {
            saveButton.innerHTML = '<i class="bi bi-building-add me-1"></i>Guardar Asignación';
            checkFormValidity();
        });
    });

    checkFormValidity();
}