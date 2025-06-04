document.addEventListener('DOMContentLoaded', function () {
    const modalHorariosMedicoElement = document.getElementById('modalHorariosMedico');
    if (!modalHorariosMedicoElement) {
        return;
    }
    const modalHorariosMedico = new bootstrap.Modal(modalHorariosMedicoElement);
    
    const modalFotoMedicoEl = document.getElementById('modalFotoMedico');
    const modalNombreMedicoEl = document.getElementById('modalNombreMedico');
    const selectFechaHorarioModalEl = document.getElementById('selectFechaHorarioModal');
    const detalleHorarioDiaEl = document.getElementById('detalleHorarioDia');
    let currentDocMedico = null;

    document.querySelectorAll('.btn-ver-horarios').forEach(button => {
        button.addEventListener('click', function () {
            currentDocMedico = this.dataset.docMedico;
            const nombreMedico = this.dataset.nombreMedico;
            const fotoMedico = this.dataset.fotoMedico;

            if (modalNombreMedicoEl) modalNombreMedicoEl.textContent = nombreMedico;
            if (modalFotoMedicoEl) modalFotoMedicoEl.src = fotoMedico || '../img/default_user.png';
            
            if (selectFechaHorarioModalEl) {
                selectFechaHorarioModalEl.innerHTML = '<option value="">Cargando fechas...</option>';
                selectFechaHorarioModalEl.disabled = true;
            }
            if (detalleHorarioDiaEl) {
                detalleHorarioDiaEl.innerHTML = '<p class="text-center text-muted mensaje-seleccione-fecha">Seleccione una fecha para ver los horarios.</p>';
            }
            
            cargarFechasMedico(currentDocMedico);
        });
    });

    function cargarFechasMedico(docMedico) {
        if (!selectFechaHorarioModalEl) return;

        const formData = new FormData();
        formData.append('doc_medico', docMedico);

        fetch('../ajax/get_fechas_horario_medico.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            selectFechaHorarioModalEl.disabled = false;
            if (data.success && data.fechas.length > 0) {
                selectFechaHorarioModalEl.innerHTML = '<option value="">-- Seleccione una fecha --</option>';
                data.fechas.forEach(fechaObj => {
                    const fechaDate = new Date(fechaObj.fecha_horario + 'T00:00:00Z'); 
                    const opcionesFormato = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC' };
                    const fechaFormateada = fechaDate.toLocaleDateString('es-ES', opcionesFormato);

                    const option = document.createElement('option');
                    option.value = fechaObj.fecha_horario;
                    option.textContent = fechaFormateada;
                    selectFechaHorarioModalEl.appendChild(option);
                });
            } else if (data.success && data.fechas.length === 0) {
                selectFechaHorarioModalEl.innerHTML = '<option value="">No hay fechas futuras disponibles</option>';
                if (detalleHorarioDiaEl) detalleHorarioDiaEl.innerHTML = '<p class="text-center text-info mensaje-seleccione-fecha">Este médico no tiene horarios futuros registrados.</p>';
            } else {
                selectFechaHorarioModalEl.innerHTML = '<option value="">Error al cargar fechas</option>';
                if (detalleHorarioDiaEl) detalleHorarioDiaEl.innerHTML = `<p class="text-center text-danger mensaje-seleccione-fecha">${data.message || 'No se pudieron cargar las fechas.'}</p>`;
            }
        })
        .catch(error => {
            if (selectFechaHorarioModalEl) {
                selectFechaHorarioModalEl.innerHTML = '<option value="">Error de red</option>';
                selectFechaHorarioModalEl.disabled = false;
            }
            if (detalleHorarioDiaEl) detalleHorarioDiaEl.innerHTML = '<p class="text-center text-danger mensaje-seleccione-fecha">Error de conexión al intentar cargar las fechas.</p>';
        });
    }

    if (selectFechaHorarioModalEl) {
        selectFechaHorarioModalEl.addEventListener('change', function() {
            const fechaSeleccionada = this.value;
            if (fechaSeleccionada && currentDocMedico) {
                cargarHorasPorFecha(currentDocMedico, fechaSeleccionada);
            } else if (detalleHorarioDiaEl) {
                detalleHorarioDiaEl.innerHTML = '<p class="text-center text-muted mensaje-seleccione-fecha">Seleccione una fecha para ver los horarios.</p>';
            }
        });
    }

    function cargarHorasPorFecha(docMedico, fecha) {
        if (!detalleHorarioDiaEl) return;
        detalleHorarioDiaEl.innerHTML = '<p class="text-center text-muted">Cargando horas...</p>';
        
        const formData = new FormData();
        formData.append('doc_medico', docMedico);
        formData.append('fecha_horario', fecha);

        fetch('../ajax/get_horas_por_fecha_medico.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.horas.length > 0) {
                let htmlHoras = '<ul class="list-group list-group-flush">';
                data.horas.forEach(item => {
                    let horaFormateada = item.horario.substring(0, 5);
                    let estadoTexto = item.nom_est || 'No definido'; 
                    let claseEstado = 'estado-no-definido'; 

                    if (item.nom_est && typeof item.nom_est === 'string' && item.nom_est.trim() !== '') {
                        claseEstado = 'estado-' + item.nom_est.toLowerCase()
                                                        .replace(/\s+/g, '-')
                                                        .replace(/[^a-z0-9-]/g, '');
                    } else if (item.id_est) {
                         claseEstado = 'estado-id-' + item.id_est; 
                    }
                    
                    htmlHoras += `<li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-clock-fill me-2"></i>${horaFormateada} ${item.periodo || ''}</span>
                                    <span class="badge rounded-pill badge-estado ${claseEstado}">${estadoTexto}</span>
                                 </li>`;
                });
                htmlHoras += '</ul>';
                detalleHorarioDiaEl.innerHTML = htmlHoras;
            } else if (data.success && data.horas.length === 0) {
                detalleHorarioDiaEl.innerHTML = '<p class="text-center text-info">No hay bloques de hora registrados para esta fecha.</p>';
            } else {
                detalleHorarioDiaEl.innerHTML = `<p class="text-center text-danger">${data.message || 'No se pudieron cargar las horas.'}</p>`;
            }
        })
        .catch(error => {
            detalleHorarioDiaEl.innerHTML = '<p class="text-center text-danger">Error de conexión o procesamiento al intentar cargar las horas.</p>';
        });
    }
    
    modalHorariosMedicoElement.addEventListener('hidden.bs.modal', function () {
        if (modalNombreMedicoEl) modalNombreMedicoEl.textContent = '';
        if (modalFotoMedicoEl) modalFotoMedicoEl.src = '../img/default_user.png';
        if (selectFechaHorarioModalEl) {
            selectFechaHorarioModalEl.innerHTML = '<option value="">Cargando fechas...</option>';
            selectFechaHorarioModalEl.disabled = true;
        }
        if (detalleHorarioDiaEl) detalleHorarioDiaEl.innerHTML = '<p class="text-center text-muted mensaje-seleccione-fecha">Seleccione una fecha para ver los horarios.</p>';
        currentDocMedico = null;
    });
});