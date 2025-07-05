document.addEventListener('DOMContentLoaded', () => {
    const medicoSelect = document.getElementById('doc_medico');
    const mensajeMedicoError = document.getElementById('mensaje_medico_error');
    const fechasInput = document.getElementById('fechas_horario_input');
    const meridianoSelect = document.getElementById('id_meridiano');
    const contenedorBloquesHora = document.getElementById('contenedor_bloques_hora');
    const formCrearHorario = document.getElementById('formCrearHorario');

    const bloquesHoraBase = {
        am: [7, 8, 9, 10, 11], 
        pm: [12, 13, 14, 15, 16, 17] 
    };

    function getNextSunday() {
        const today = new Date();
        const currentDay = today.getDay(); 
        const daysUntilSunday = (7 - currentDay) % 7;
        const nextSunday = new Date(today);
        nextSunday.setDate(today.getDate() + daysUntilSunday);
        return nextSunday;
    }

    function verificarAsignacionMedico() {
        const docMedico = medicoSelect.value;
        mensajeMedicoError.textContent = '';
        mensajeMedicoError.style.display = 'none';
        fechasInput.disabled = true;
        meridianoSelect.disabled = true;
        contenedorBloquesHora.innerHTML = '<small class="form-text text-muted">Seleccione un médico, fechas y un turno para ver los bloques de hora.</small>';
        disableSubmitButton(true);
        if (window.flatpickrInstance) {
            window.flatpickrInstance.clear();
        }


        if (!docMedico) {
            return;
        }

        fetch('../ajax/verificar_asignacion_medico.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'doc_medico=' + encodeURIComponent(docMedico)
        })
        .then(response => response.json())
        .then(data => {
            if (data.asignado) {
                mensajeMedicoError.style.display = 'none';
                fechasInput.disabled = false;
                meridianoSelect.disabled = false;
            } else {
                mensajeMedicoError.textContent = data.message || 'Este médico no está asignado a una IPS o la asignación no está activa.';
                mensajeMedicoError.style.display = 'block';
                fechasInput.disabled = true;
                meridianoSelect.disabled = true;
                contenedorBloquesHora.innerHTML = '<small class="form-text text-muted">Seleccione un médico, fechas y un turno para ver los bloques de hora.</small>';
                disableSubmitButton(true);
                if (window.flatpickrInstance) {
                    window.flatpickrInstance.clear();
                }
            }
        })
        .catch(error => {
            mensajeMedicoError.textContent = 'Error al verificar el estado del médico.';
            mensajeMedicoError.style.display = 'block';
            fechasInput.disabled = true;
            meridianoSelect.disabled = true;
            disableSubmitButton(true);
            if (window.flatpickrInstance) {
                window.flatpickrInstance.clear();
            }
        });
    }

    function generarBloquesHora() {
        const idPeriodo = meridianoSelect.value;
        contenedorBloquesHora.innerHTML = ''; 
        disableSubmitButton(true);

        if (!idPeriodo || medicoSelect.value === '' || !fechasInput.value) {
            contenedorBloquesHora.innerHTML = '<small class="form-text text-muted">Seleccione médico, fechas y turno para ver los bloques de hora.</small>';
            checkFormCompletion();
            return;
        }
        
        if (mensajeMedicoError.style.display === 'block' && mensajeMedicoError.textContent !== ''){
            checkFormCompletion();
            return; 
        }

        let horasDisponibles = [];
        if (idPeriodo === '1') { 
            horasDisponibles = bloquesHoraBase.am;
        } else if (idPeriodo === '2') { 
            horasDisponibles = bloquesHoraBase.pm;
        }

        if (horasDisponibles.length > 0) {
            const tituloBloques = document.createElement('p');
            tituloBloques.className = 'mb-2 fw-medium';
            tituloBloques.textContent = 'Seleccione los bloques de hora de inicio para trabajar:';
            contenedorBloquesHora.appendChild(tituloBloques);

            const rowDiv = document.createElement('div');
            rowDiv.className = 'row g-2';

            horasDisponibles.forEach(hora => {
                const colDiv = document.createElement('div');
                colDiv.className = 'col-auto';

                const formCheckDiv = document.createElement('div');
                formCheckDiv.className = 'form-check form-check-inline';

                const input = document.createElement('input');
                input.type = 'checkbox';
                input.className = 'form-check-input';
                input.name = 'bloques_hora_seleccionados[]';
                input.value = hora;
                input.id = 'hora_bloque_' + hora;
                input.addEventListener('change', checkFormCompletion);

                const label = document.createElement('label');
                label.className = 'form-check-label';
                label.htmlFor = 'hora_bloque_' + hora;
                label.textContent = (hora < 10 ? '0' + hora : hora) + ':00';

                formCheckDiv.appendChild(input);
                formCheckDiv.appendChild(label);
                colDiv.appendChild(formCheckDiv);
                rowDiv.appendChild(colDiv);
            });
            contenedorBloquesHora.appendChild(rowDiv);
        } else {
            contenedorBloquesHora.innerHTML = '<small class="form-text text-muted">No hay bloques de hora definidos para este turno.</small>';
        }
        checkFormCompletion(); 
    }
    
    function disableSubmitButton(disable) {
        const submitButton = formCrearHorario.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = disable;
        }
    }

    function checkFormCompletion() {
        const medicoOk = medicoSelect.value !== '' && (mensajeMedicoError.style.display === 'none' || mensajeMedicoError.textContent === '');
        const fechasOk = fechasInput.value !== '';
        const meridianoOk = meridianoSelect.value !== '';
        
        let bloquesHoraSeleccionados = false;
        if (meridianoOk && medicoOk && fechasOk) { 
            bloquesHoraSeleccionados = contenedorBloquesHora.querySelectorAll('input[name="bloques_hora_seleccionados[]"]:checked').length > 0;
        }

        if (medicoOk && fechasOk && meridianoOk && bloquesHoraSeleccionados) {
            disableSubmitButton(false);
        } else {
            disableSubmitButton(true);
        }
    }


    if (medicoSelect) {
        medicoSelect.addEventListener('change', () => {
            verificarAsignacionMedico();
            generarBloquesHora(); 
            checkFormCompletion();
        });
    }

    if (meridianoSelect) {
        meridianoSelect.addEventListener('change', () => {
            generarBloquesHora();
            checkFormCompletion();
        });
    }
    
    if (fechasInput) {
         window.flatpickrInstance = flatpickr(fechasInput, {
            mode: "multiple",
            dateFormat: "Y-m-d",
            minDate: "today",
            maxDate: getNextSunday(),
            locale: "es",
            conjunction: ", ",
            disableMobile: "true",
            clickOpens: true,
            static: true, 
            onChange: function(selectedDates, dateStr, instance) {
                generarBloquesHora(); 
                checkFormCompletion();
            },
            onClose: function(selectedDates, dateStr, instance){
                if(selectedDates.length === 0){
                    fechasInput.value = ''; 
                }
                checkFormCompletion();
            }
        });

        if(fechasInput.value) { 
            generarBloquesHora();
        } else {
             if (medicoSelect.value && (mensajeMedicoError.style.display === 'none' || mensajeMedicoError.textContent === '')) {
                fechasInput.disabled = false;
             } else {
                fechasInput.disabled = true;
             }
        }
    }


    if (formCrearHorario) {
        formCrearHorario.addEventListener('submit', function(event) {
            checkFormCompletion(); 
            
            if (!formCrearHorario.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            const bloquesSeleccionados = contenedorBloquesHora.querySelectorAll('input[name="bloques_hora_seleccionados[]"]:checked').length;
            if (meridianoSelect.value !== '' && medicoSelect.value !== '' && fechasInput.value !== '' && bloquesSeleccionados === 0) {
                 event.preventDefault();
                 event.stopPropagation();
                 
                 let errorDiv = document.getElementById('error_bloques_hora');
                 if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.id = 'error_bloques_hora';
                    errorDiv.className = 'text-danger mt-2 small';
                    contenedorBloquesHora.insertAdjacentElement('afterend', errorDiv);
                 }
                 errorDiv.textContent = 'Debe seleccionar al menos un bloque de hora.';
            } else {
                const errorDiv = document.getElementById('error_bloques_hora');
                if (errorDiv) errorDiv.remove();
            }

            formCrearHorario.classList.add('was-validated');
        }, false);
    }
    
    
    disableSubmitButton(true);
    if (medicoSelect && medicoSelect.value) {
        verificarAsignacionMedico(); 
    } else {
        fechasInput.disabled = true;
        meridianoSelect.disabled = true;
    }
    
    if (meridianoSelect && meridianoSelect.value && medicoSelect.value && (mensajeMedicoError.style.display !== 'block' || mensajeMedicoError.textContent === '')) {
        generarBloquesHora();
    }
    
    if(fechasInput.value && medicoSelect.value && meridianoSelect.value){
         checkFormCompletion();
    } else if (!fechasInput.value && medicoSelect.value && (mensajeMedicoError.style.display === 'none' || mensajeMedicoError.textContent === '')) {
        fechasInput.disabled = false;
    }


    if (!medicoSelect.value) {
        fechasInput.disabled = true;
        meridianoSelect.disabled = true;
    }
});