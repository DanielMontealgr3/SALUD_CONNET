function inicializarLogicaFormularioModalFarmaceuta() {
    const formAsignarFarmaciaInterno = document.getElementById('formAsignarFarmaciaModalInterno');
    const btnGuardarAsignacionInterno = document.getElementById('btnGuardarAsignacionFarmaceutaModalInterno');
    const modalMessageDivInterno = document.getElementById('modalAsignacionFarmaceutaMessageInterno');
    const modalGlobalErrorDivInterno = document.getElementById('modalAsignacionFarmaceutaGlobalErrorInterno');

    const selectFarmaciaInterno = document.getElementById('nit_farma_asignar_modal_select_interno');
    const selectEstadoInterno = document.getElementById('id_estado_asignacion_farmaceuta_modal_select_interno');
    const errorFarmaciaInterno = document.getElementById('error-nit_farma_asignar_modal_interno');
    const errorEstadoInterno = document.getElementById('error-id_estado_asignacion_farmaceuta_modal_interno'); // Aunque no mostraremos el mensaje, lo referenciamos para ocultarlo

    if (!formAsignarFarmaciaInterno || !btnGuardarAsignacionInterno) {
        if(modalGlobalErrorDivInterno) modalGlobalErrorDivInterno.innerHTML = '<div class="alert alert-danger">Error interno: No se pudieron inicializar los controles del formulario dentro del modal.</div>';
        return;
    }

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
    
    // Inicializar el campo de estado como válido visualmente
    if (selectEstadoInterno) {
        selectEstadoInterno.classList.add('is-valid'); // Añade el borde verde
        selectEstadoInterno.classList.remove('is-invalid');
        if (errorEstadoInterno) {
            errorEstadoInterno.style.display = 'none'; // Asegura que el mensaje de error esté oculto
        }
    }

    if(selectFarmaciaInterno) {
        selectFarmaciaInterno.addEventListener('change', function() {
            validarCampoSelectObligatorio(this, errorFarmaciaInterno, 'Debe seleccionar una farmacia.');
        });
    }

    if(selectEstadoInterno) {
        selectEstadoInterno.addEventListener('change', function() {
            // Mantenerlo visualmente válido sin importar la selección (Activo o Inactivo)
            this.classList.add('is-valid');
            this.classList.remove('is-invalid');
            if (errorEstadoInterno) {
                errorEstadoInterno.style.display = 'none';
            }
        });
    }

    formAsignarFarmaciaInterno.addEventListener('submit', function (event) {
        event.preventDefault();
        event.stopPropagation();

        if (modalMessageDivInterno) modalMessageDivInterno.innerHTML = '';
        if (modalGlobalErrorDivInterno) modalGlobalErrorDivInterno.innerHTML = '';
        
        let esValidoFarmacia = validarCampoSelectObligatorio(selectFarmaciaInterno, errorFarmaciaInterno, 'Debe seleccionar una farmacia.');
        
        // La validación lógica del estado sigue siendo importante en el backend,
        // pero visualmente no mostramos error aquí ya que siempre tendrá un valor.
        let esValidoEstado = true; // Asumimos que siempre es válido visualmente
        if (selectEstadoInterno && !selectEstadoInterno.value) { // Salvaguarda por si el HTML cambia
            esValidoEstado = false;
            if(modalMessageDivInterno) modalMessageDivInterno.innerHTML = `<div class="alert alert-warning">El estado de asignación es requerido.</div>`;
        }


        if (!esValidoFarmacia || !esValidoEstado) {
            if (modalMessageDivInterno && !modalMessageDivInterno.innerHTML) { // Solo muestra si no hay ya un mensaje específico de estado
                modalMessageDivInterno.innerHTML = `<div class="alert alert-warning">Por favor, corrija los campos marcados.</div>`;
            }
            return;
        }

        btnGuardarAsignacionInterno.disabled = true;
        btnGuardarAsignacionInterno.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

        const formData = new FormData(formAsignarFarmaciaInterno);
        
        fetch(window.location.pathname + window.location.search, { 
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (modalMessageDivInterno) modalMessageDivInterno.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                if(selectFarmaciaInterno) selectFarmaciaInterno.classList.remove('is-valid');
                // El estado ya debería estar 'is-valid'
                setTimeout(() => {
                    const modalElement = document.getElementById('modalContenedorAsignacionFarmaceuta');
                    const bootstrapModalInstance = bootstrap.Modal.getInstance(modalElement);
                    if (bootstrapModalInstance) {
                        bootstrapModalInstance.hide();
                    }
                }, 2000);
            } else {
                 if (modalMessageDivInterno) modalMessageDivInterno.innerHTML = `<div class="alert alert-danger">${data.message || 'Error desconocido al guardar.'}</div>`;
            }
        })
        .catch(error => {
            if (modalGlobalErrorDivInterno) modalGlobalErrorDivInterno.innerHTML = `<div class="alert alert-danger">Error de conexión o del servidor: ${error}. Intente de nuevo.</div>`;
            console.error('Error en fetch (guardado asignación farmaceuta - interno):', error);
        })
        .finally(() => {
            btnGuardarAsignacionInterno.disabled = false;
            btnGuardarAsignacionInterno.innerHTML = '<i class="bi bi-shop-window me-1"></i>Guardar Asignación';
        });
    });
}