document.addEventListener('DOMContentLoaded', function() {
    // --- LECTURA DE DATOS DESDE EL HTML ---
    const mainContainer = document.querySelector('main.container');
    if (!mainContainer) return;

    // Leemos los datos que pasamos desde PHP a través de atributos data-*
    const mensajeTipo = mainContainer.dataset.mensajeTipo || '';
    const mensajeTexto = mainContainer.dataset.mensajeTexto || '';
    const docAConsultar = mainContainer.dataset.docAConsultar || '';
    
    // --- CONFIGURACIÓN DE COMPONENTES BOOTSTRAP ---
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    const hourModal = new bootstrap.Modal(document.getElementById('hourModal')); 
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const toastEl = document.getElementById('liveToast');
    const toast = new bootstrap.Toast(toastEl);

    // --- FUNCIÓN DE NOTIFICACIÓN TOAST ---
    function showNotification(title, message, type = 'info') {
        const toastTitleEl = document.getElementById('toast-title');
        const toastBodyEl = document.getElementById('toast-body');
        toastTitleEl.textContent = title;
        toastBodyEl.textContent = message;

        toastEl.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-warning');
        if (type === 'exito') {
            toastEl.classList.add('text-bg-success');
        } else if (type === 'error') {
            toastEl.classList.add('text-bg-danger');
        } else {
            toastEl.classList.add('text-bg-warning');
        }
        toast.show();
    }

    // --- NOTIFICACIÓN INICIAL (SI HAY MENSAJES DE PHP) ---
    if (mensajeTexto) {
        const tituloNotificacion = mensajeTipo === 'exito' ? 'Proceso Completado' : 'Error en el Proceso';
        showNotification(tituloNotificacion, mensajeTexto, mensajeTipo);
        if (mensajeTipo === 'exito') { 
            setTimeout(() => { 
                window.location.href = `${window.location.pathname}?documento=${encodeURIComponent(docAConsultar)}`; 
            }, 3000);
        }
    }

    // --- LÓGICA DEL CALENDARIO ---
    const todayStr = new Date().toISOString().split('T')[0];

    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'es', 
        initialView: 'dayGridMonth', 
        headerToolbar: { left: 'prev', center: 'title', right: 'next' }, 
        validRange: { start: todayStr },
        height: 'auto',
        dateClick: function(info) {
            if (!document.getElementById('id_historia').value) { 
                showNotification('Atención', 'Por favor, seleccione primero una orden de la lista.', 'warning'); 
                return; 
            }
            if (info.dayEl.classList.contains('fc-day-disabled')) {
                showNotification('Día no disponible', 'No se pueden agendar turnos para fines de semana.', 'warning');
                return;
            }
            document.getElementById('selected-date').value = info.dateStr; 
            document.getElementById('fecha-modal-titulo').textContent = info.date.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            loadHours(info.dateStr);
        },
        dayCellDidMount: function(info) {
            if (info.date.getDay() === 0 || info.date.getDay() === 6) { 
                info.el.classList.add('fc-day-disabled');
            }
        }
    });
    calendar.render();

    // --- FUNCIÓN AJAX PARA CARGAR HORAS ---
    function loadHours(fecha) {
        $('#horas-container').html('<div class="col-12 text-center"><div class="spinner-border text-primary"></div></div>');
        hourModal.show();
        $.ajax({
            url: 'consultar_horario_farmacia.php', 
            type: 'POST', 
            dataType: 'json', 
            data: { fecha: fecha },
            success: function(response) {
                let html = '';
                if (response.error) { 
                    html = `<div class="col-12"><p class="text-danger text-center">${response.error}</p></div>`;
                } else if (response.hours && response.hours.length > 0) {
                    html = response.hours.map(row => {
                        const isDisabled = row.isOccupied || row.isPast;
                        let disabledReason = '';
                        if (row.isOccupied) {
                            disabledReason = 'Hora ya agendada';
                        } else if (row.isPast) {
                            disabledReason = 'Hora no disponible';
                        }
                        return `<div class="col-3 mb-2">
                                    <button type="button" class="btn btn-outline-primary hour-btn" 
                                            data-time-id="${row.id_horario_farm}" data-time-text="${row.hora12}" 
                                            title="${disabledReason}" ${isDisabled ? 'disabled' : ''}>
                                        ${row.hora12}
                                    </button>
                                </div>`;
                    }).join('');
                } else { 
                    html = '<div class="col-12"><p class="text-muted text-center">No hay horas disponibles para este día.</p></div>'; 
                }
                $('#horas-container').html(html);
            },
            error: function() { 
                $('#horas-container').html('<div class="col-12"><p class="text-danger text-center">Error al cargar las horas.</p></div>');
            }
        });
    }

    // --- MANEJADORES DE EVENTOS ---
    $(document).on('click', '.hour-btn', function() {
        if ($(this).is(':disabled')) return;
        $('#selected-hour-id').val($(this).data('time-id'));
        const selectedDate = new Date($('#selected-date').val() + 'T00:00:00').toLocaleDateString('es-ES', { dateStyle: 'long' });
        $('#confirm-modal-body').html(`<p>Confirmar turno para el <strong>${selectedDate}</strong> a las <strong>${$(this).text()}</strong>.</p>`);
        hourModal.hide();
        confirmModal.show();
    });

    $('#btn-confirm-agenda').on('click', function() {
        $('#submit-hidden').click();
    });
});