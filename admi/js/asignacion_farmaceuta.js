function inicializarLogicaFormularioModalFarmaceuta() {
    const formAsignarFarmaciaInterno = document.getElementById('formAsignarFarmaciaModalInterno');
    const btnGuardarAsignacionInterno = document.getElementById('btnGuardarAsignacionFarmaceutaModalInterno');
    const modalMessageDivInterno = document.getElementById('modalAsignacionFarmaceutaMessageInterno');
    const modalGlobalErrorDivInterno = document.getElementById('modalAsignacionFarmaceutaGlobalErrorInterno');

    const selectFarmaciaInterno = document.getElementById('nit_farma_asignar_modal_select_interno');
    const selectEstadoInterno = document.getElementById('id_estado_asignacion_farmaceuta_modal_select_interno');
    const errorFarmaciaInterno = document.getElementById('error-nit_farma_asignar_modal_interno');
    const errorEstadoInterno = document.getElementById('error-id_estado_asignacion_farmaceuta_modal_interno');

    if (!formAsignarFarmaciaInterno || !btnGuardarAsignacionInterno) {
        if(modalGlobalErrorDivInterno) modalGlobalErrorDivInterno.innerHTML = '<div class="alert alert-danger">Error interno: No se pudieron inicializar los controles del formulario.</div>';
        return;
    }

    // --- INICIO DE LA MODIFICACIÓN ---
    function actualizarEstadoBoton() {
        if (selectFarmaciaInterno.value) {
            btnGuardarAsignacionInterno.disabled = false;
        } else {
            btnGuardarAsignacionInterno.disabled = true;
        }
    }
    // --- FIN DE LA MODIFICACIÓN ---

    function validarCampoSelectObligatorio(selectElement, errorElement, mensajeError) {
        if (!selectElement.value) {
            selectElement.classList.add('is-invalid');
            selectElement.classList.remove('is-valid');
            errorElement.textContent = mensajeError;
            errorElement.style.display = 'block';
            return false;
        } else {
            selectElement.classList.remove('is-invalid');
            selectElement.classList.add('is-valid');
            errorElement.style.display = 'none';
            return true;
        }
    }
    
    if(selectEstadoInterno) {
        selectEstadoInterno.classList.add('is-valid');
        if (errorEstadoInterno) errorEstadoInterno.style.display = 'none';
    }

    if(selectFarmaciaInterno) {
        selectFarmaciaInterno.addEventListener('change', function() {
            validarCampoSelectObligatorio(this, errorFarmaciaInterno, 'Debe seleccionar una farmacia.');
            actualizarEstadoBoton(); // Llama a la función para habilitar/deshabilitar el botón
        });
    }

    // Llamada inicial para establecer el estado del botón al cargar el modal
    actualizarEstadoBoton();

    formAsignarFarmaciaInterno.addEventListener('submit', function (event) {
        event.preventDefault();
        event.stopPropagation();

        if (modalMessageDivInterno) modalMessageDivInterno.innerHTML = '';
        if (modalGlobalErrorDivInterno) modalGlobalErrorDivInterno.innerHTML = '';
        
        let esValidoFarmacia = validarCampoSelectObligatorio(selectFarmaciaInterno, errorFarmaciaInterno, 'Debe seleccionar una farmacia.');
        
        if (!esValidoFarmacia) {
            if (modalMessageDivInterno) {
                modalMessageDivInterno.innerHTML = `<div class="alert alert-warning">Por favor, corrija los campos marcados.</div>`;
            }
            return;
        }

        btnGuardarAsignacionInterno.disabled = true;
        btnGuardarAsignacionInterno.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

        const formData = new FormData(formAsignarFarmaciaInterno);
        
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
                    const modalElement = document.getElementById('modalContenedorAsignacionFarmaceuta');
                    const bootstrapModalInstance = bootstrap.Modal.getInstance(modalElement);
                    if (bootstrapModalInstance) {
                        bootstrapModalInstance.hide();
                    }
                }
            });
        })
        .catch(error => {
            if (modalGlobalErrorDivInterno) modalGlobalErrorDivInterno.innerHTML = `<div class="alert alert-danger">Error de conexión o del servidor. Intente de nuevo.</div>`;
            console.error('Error en fetch:', error);
        })
        .finally(() => {
            // No se vuelve a habilitar el botón aquí, se controla con la función actualizarEstadoBoton
            btnGuardarAsignacionInterno.innerHTML = '<i class="bi bi-shop-window me-1"></i>Guardar Asignación';
            actualizarEstadoBoton(); 
        });
    });
}