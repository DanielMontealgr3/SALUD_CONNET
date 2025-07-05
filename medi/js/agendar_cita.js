document.addEventListener('DOMContentLoaded', function() {
    const sedeSelectFiltro = document.getElementById('id_sede_seleccionada_filtro');
    const medicoSelectFiltro = document.getElementById('id_medico_seleccionado_filtro'); // Get the new doctor dropdown

    // Sede change handler
    if (sedeSelectFiltro) {
        sedeSelectFiltro.addEventListener('change', function() {
            const selectedSedeId = this.value;
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('id_sede', selectedSedeId);
            currentUrl.searchParams.delete('doc_medico'); // Reset doctor when sede changes
            currentUrl.searchParams.delete('mes'); 
            currentUrl.searchParams.delete('anio');
            window.location.href = currentUrl.toString().split('#')[0] + '#calendario-wrapper';
        });
    }

    // NEW: Doctor change handler
    if (medicoSelectFiltro) {
        medicoSelectFiltro.addEventListener('change', function() {
            const selectedMedicoId = this.value;
            const currentUrl = new URL(window.location.href);
            if (selectedMedicoId) {
                currentUrl.searchParams.set('doc_medico', selectedMedicoId);
            } else {
                currentUrl.searchParams.delete('doc_medico'); // If "Todos los médicos" is selected
            }
            // Optional: Reset month/year or keep them
            // currentUrl.searchParams.delete('mes'); 
            // currentUrl.searchParams.delete('anio');
            window.location.href = currentUrl.toString().split('#')[0] + '#calendario-wrapper';
        });
    }

    // ... (Scroll to hash logic as before) ...
    if(window.location.hash) {
        const targetId = window.location.hash;
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            const behavior = targetId === "#calendario-wrapper" ? 'auto' : 'smooth'; 
            const block = targetId === "#calendario-wrapper" ? 'start' : 'center'; 
            setTimeout(() => targetElement.scrollIntoView({ behavior: behavior, block: block }), 150);
        }
    }

    var modalHorariosElement = document.getElementById('modalHorarios');
    // ... (Modal elements as before) ...
    var modalHorarios = modalHorariosElement ? new bootstrap.Modal(modalHorariosElement) : null;
    const listaHorariosDiv = document.getElementById('listaHorarios');
    const cargandoHorariosDiv = document.getElementById('cargandoHorarios');
    const sinHorariosDiv = document.getElementById('sinHorarios');
    const fechaSeleccionadaModalSpan = document.getElementById('fechaSeleccionadaModal');

    document.body.addEventListener('click', function(event) {
        if (event.target.matches('.abrir-modal-horarios') || event.target.closest('.abrir-modal-horarios')) {
            const button = event.target.closest('.abrir-modal-horarios');
            const fecha = button.dataset.date;
            const idSedeActual = sedeSelectFiltro ? sedeSelectFiltro.value : null;
            const idMedicoActual = medicoSelectFiltro ? medicoSelectFiltro.value : null; // Get selected doctor

            if (!idSedeActual) {
                alert('Por favor, seleccione una sede primero.');
                return;
            }

            if (fechaSeleccionadaModalSpan) fechaSeleccionadaModalSpan.textContent = fecha;
            // ... (rest of modal setup: clear listaHorarios, show loading, etc.) ...
            if (listaHorariosDiv) {
                listaHorariosDiv.innerHTML = '';
                listaHorariosDiv.dataset.fechaSeleccionada = fecha;
            }
            if (sinHorariosDiv) sinHorariosDiv.style.display = 'none';
            if (cargandoHorariosDiv) cargandoHorariosDiv.style.display = 'block';
            if (modalHorarios) modalHorarios.show();

            // AJAX call using jQuery
            $.ajax({
                url: 'get_horarios_disponibles.php',
                type: 'POST',
                data: { 
                    fecha: fecha, 
                    id_sede: idSedeActual,
                    doc_medico: idMedicoActual // NEW: Send selected doctor ID
                },
                dataType: 'json',
                success: function(response) {
                    // ... (AJAX success callback as before, generating buttons) ...
                    if (cargandoHorariosDiv) cargandoHorariosDiv.style.display = 'none';
                    if (response.error) {
                        if (listaHorariosDiv) listaHorariosDiv.innerHTML = '<p class="text-danger text-center">' + response.error + '</p>';
                         console.error("Server error from get_horarios_disponibles.php:", response.error);
                    } else if (response.horarios && response.horarios.length > 0) {
                        let htmlHorarios = response.horarios.map(h => 
                            `<button type="button" 
                                     class="btn btn-primary btn-horario-viva m-1 seleccionar-horario-final" 
                                     data-id-horario="${h.id}">
                                 <i class="fa fa-clock-o" aria-hidden="true"></i> ${h.hora_formateada}
                             </button>`
                        ).join('');
                        if (listaHorariosDiv) listaHorariosDiv.innerHTML = htmlHorarios;
                    } else {
                        if (sinHorariosDiv) sinHorariosDiv.style.display = 'block';
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // ... (AJAX error callback as before) ...
                    if (cargandoHorariosDiv) cargandoHorariosDiv.style.display = 'none';
                    if (listaHorariosDiv) listaHorariosDiv.innerHTML = '<p class="text-danger text-center">Error de red o servidor al cargar horarios. Revise la consola.</p>';
                    console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                }
            });
        }
    });
            
    // ... (event listener for selecting a time slot - no change here) ...
    if (listaHorariosDiv) {
        listaHorariosDiv.addEventListener('click', function(event) {
            const targetButton = event.target.closest('.seleccionar-horario-final');
            if (targetButton) {
                const idSedeForm = sedeSelectFiltro ? sedeSelectFiltro.value : null;
                const fechaSeleccionada = listaHorariosDiv.dataset.fechaSeleccionada;
                const idHorarioSeleccionado = targetButton.dataset.idHorario;

                if (!idSedeForm || !fechaSeleccionada || !idHorarioSeleccionado) {
                     alert('Error: datos incompletos para agendar. Intente nuevamente.'); 
                     return; 
                }
                $('#modal_id_sede_seleccionada_form').val(idSedeForm);
                $('#modal_fecha_para_horario').val(fechaSeleccionada);
                $('#modal_id_horario_seleccionado').val(idHorarioSeleccionado);
                $('#formAgendarCitaModal').submit();
            }
        });
    }
});