document.addEventListener('DOMContentLoaded', function() {
    const formCrearAlianza = document.getElementById('formCrearAlianza');
    const selectEpsAlianza = document.getElementById('id_eps_alianza');
    const selectTipoAlianza = document.getElementById('tipo_alianza');
    const contenedorSelectEntidadAliada = document.getElementById('contenedor_select_entidad_aliada');
    const selectEntidadAliada = document.getElementById('id_entidad_aliada');
    const labelEntidadAliada = document.querySelector('label[for="id_entidad_aliada"]');
    const selectEstadoAlianza = document.getElementById('id_estado_alianza');
    const mensajesServidor = document.getElementById('mensajesAlianzaServidor');
    const btnSubmit = formCrearAlianza.querySelector('button[type="submit"]');

    const fieldsToValidate = [
        { id: 'id_eps_alianza', name: 'EPS' },
        { id: 'tipo_alianza', name: 'Tipo de Alianza' },
        { id: 'id_entidad_aliada', name: 'Entidad Aliada' },
        { id: 'id_estado_alianza', name: 'Estado de la Alianza' }
    ];

    function setFieldValidity(fieldElement, isValid, message = '') {
        const feedbackElement = fieldElement.nextElementSibling; // Asume que .invalid-feedback está justo después
        if (isValid) {
            fieldElement.classList.remove('is-invalid');
            fieldElement.classList.add('is-valid');
            if (feedbackElement && feedbackElement.classList.contains('invalid-feedback')) {
                feedbackElement.textContent = '';
            }
        } else {
            fieldElement.classList.remove('is-valid');
            fieldElement.classList.add('is-invalid');
            if (feedbackElement && feedbackElement.classList.contains('invalid-feedback')) {
                feedbackElement.textContent = message;
            }
        }
    }

    function validateField(fieldElement) {
        let isValid = true;
        let message = '';
        if (!fieldElement.value) {
            isValid = false;
            const fieldLabel = document.querySelector(`label[for="${fieldElement.id}"]`)?.textContent.replace('(*):', '').trim() || 'Este campo';
            message = `${fieldLabel} es requerido.`;
        }
        
        // Caso especial para id_entidad_aliada que depende de tipo_alianza
        if (fieldElement.id === 'id_entidad_aliada' && selectTipoAlianza.value && !fieldElement.value) {
            isValid = false;
            message = 'Debe seleccionar la entidad específica.';
        } else if (fieldElement.id === 'id_entidad_aliada' && !selectTipoAlianza.value) {
            // No validar si el tipo de alianza no está seleccionado aún, ya que está deshabilitado
            return true; 
        }

        setFieldValidity(fieldElement, isValid, message);
        return isValid;
    }
    
    fieldsToValidate.forEach(field => {
        const element = document.getElementById(field.id);
        if (element) {
            element.addEventListener('change', () => validateField(element));
            element.addEventListener('blur', () => validateField(element));
        }
    });


    function cargarEntidadesAliadas(tipo) {
        selectEntidadAliada.innerHTML = '<option value="">Cargando...</option>';
        selectEntidadAliada.disabled = true;
        setFieldValidity(selectEntidadAliada, true); // Resetear validación
        contenedorSelectEntidadAliada.style.display = 'none';
        if (labelEntidadAliada) labelEntidadAliada.textContent = 'Seleccione tipo primero';

        if (!tipo) {
            selectEntidadAliada.innerHTML = '<option value="">Seleccione tipo de alianza...</option>';
            return;
        }

        let urlFetch = '';
        if (tipo === 'farmacia') {
            urlFetch = '../ajax/get_farmacias.php'; 
            if (labelEntidadAliada) labelEntidadAliada.textContent = 'Seleccione Farmacia (*):';
        } else if (tipo === 'ips') {
            urlFetch = '../ajax/get_ips.php'; 
            if (labelEntidadAliada) labelEntidadAliada.textContent = 'Seleccione IPS (*):';
        } else {
            selectEntidadAliada.innerHTML = '<option value="">Tipo de alianza no válido</option>';
            return;
        }

        fetch(urlFetch)
            .then(response => response.json())
            .then(data => {
                selectEntidadAliada.innerHTML = '<option value="">Seleccione...</option>';
                if (data && data.length > 0) {
                    data.forEach(entidad => {
                        const option = document.createElement('option');
                        if (tipo === 'farmacia') {
                            option.value = entidad.nit_farm;
                            option.textContent = entidad.nom_farm;
                        } else if (tipo === 'ips') {
                            option.value = entidad.Nit_IPS; 
                            option.textContent = entidad.nom_IPS;
                        }
                        selectEntidadAliada.appendChild(option);
                    });
                    selectEntidadAliada.disabled = false;
                    contenedorSelectEntidadAliada.style.display = 'block';
                } else {
                    selectEntidadAliada.innerHTML = `<option value="">No hay ${tipo}s disponibles</option>`;
                    contenedorSelectEntidadAliada.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error cargando entidades aliadas:', error);
                selectEntidadAliada.innerHTML = '<option value="">Error al cargar</option>';
                selectEntidadAliada.disabled = false;
                contenedorSelectEntidadAliada.style.display = 'block';
            });
    }

    if (selectTipoAlianza) {
        selectTipoAlianza.addEventListener('change', function() {
            validateField(this); // Validar este campo al cambiar
            cargarEntidadesAliadas(this.value);
        });
         if (selectTipoAlianza.value) { // Carga inicial si hay valor preseleccionado
            cargarEntidadesAliadas(selectTipoAlianza.value);
        }
    }
    
    if (selectEpsAlianza) {
        selectEpsAlianza.addEventListener('change', () => validateField(selectEpsAlianza));
        selectEpsAlianza.addEventListener('blur', () => validateField(selectEpsAlianza));
    }
    if (selectEstadoAlianza) {
        selectEstadoAlianza.addEventListener('change', () => validateField(selectEstadoAlianza));
        selectEstadoAlianza.addEventListener('blur', () => validateField(selectEstadoAlianza));
    }


    if (formCrearAlianza) {
        formCrearAlianza.addEventListener('submit', function(event) {
            event.preventDefault();
            mensajesServidor.innerHTML = ''; 
            
            let formIsValid = true;
            fieldsToValidate.forEach(field => {
                const element = document.getElementById(field.id);
                if (element && !validateField(element)) {
                    formIsValid = false;
                }
            });

            if (!formIsValid) {
                 mensajesServidor.innerHTML = `<div class="alert alert-danger">Por favor, corrija los errores en el formulario.</div>`;
                return;
            }
            
            const formData = new FormData(formCrearAlianza);
            const originalButtonHtml = btnSubmit.innerHTML;
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

            fetch('crear_alianza.php', { 
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mensajesServidor.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    formCrearAlianza.reset();
                    fieldsToValidate.forEach(field => {
                        const element = document.getElementById(field.id);
                        if(element) element.classList.remove('is-valid', 'is-invalid');
                    });
                    selectEntidadAliada.innerHTML = '<option value="">Seleccione tipo de alianza...</option>';
                    selectEntidadAliada.disabled = true;
                    contenedorSelectEntidadAliada.style.display = 'none';
                    if(labelEntidadAliada) labelEntidadAliada.textContent = 'Seleccione tipo primero';
                } else {
                    mensajesServidor.innerHTML = `<div class="alert alert-danger">${data.message || 'Error al procesar la solicitud.'}</div>`;
                }
            })
            .catch(error => {
                console.error('Error en fetch:', error);
                mensajesServidor.innerHTML = `<div class="alert alert-danger">Error de red o comunicación con el servidor. Revise la consola.</div>`;
            })
            .finally(() => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalButtonHtml;
                 setTimeout(() => {
                    if(mensajesServidor) mensajesServidor.innerHTML = '';
                }, 7000);
            });
        });
    }
});