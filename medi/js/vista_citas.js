document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('citas-table-body');
    const modalNoAsistioEl = document.getElementById('modalNoAsistio');
    const clockElement = document.getElementById('real-time-clock');

    if (!tableBody || !modalNoAsistioEl || !clockElement) return;
    
    const noAsistioModal = new bootstrap.Modal(modalNoAsistioEl);
    const timers = {};
    const COUNTDOWN_DURATION = 300;
    const POLLING_INTERVAL = 4000;

    function crearFilaCita(cita) {
        const nomPaciente = cita.nom_paciente || 'N/A';
        const docPaciente = cita.doc_pac || '';
        const idCita = cita.id_cita;
        const dateTimeStr = `${cita.fecha_horario}T${cita.horario}`;
        
        const badgeClass = cita.id_est == 3 ? 'info text-dark' : 'warning text-dark';
        let actionButtons;

        if (cita.id_est == 3) {
            actionButtons = `
                <button type="button" class="btn btn-sm llamar-paciente-btn" disabled><i class="fas fa-clock"></i> Esperando...</button>
                <button type="button" class="btn btn-sm btn-success paciente-llego-btn" style="display:none;"><i class="fas fa-user-check"></i> Paciente Llegó</button>
            `;
        } else { // id_est == 11
            actionButtons = `
                <button type="button" class="btn btn-sm btn-primary iniciar-consulta-btn" 
                    data-bs-toggle="modal" 
                    data-bs-target="#modalConsulta" 
                    data-id-cita="${idCita}" 
                    data-doc-paciente="${docPaciente}" 
                    data-nom-paciente="${nomPaciente}">
                    <i class="fas fa-play"></i> Iniciar Consulta
                </button>`;
        }

        const newRow = document.createElement('tr');
        newRow.id = `cita-row-${idCita}`;
        newRow.dataset.citaId = idCita;
        newRow.dataset.datetime = dateTimeStr;
        newRow.dataset.docPaciente = docPaciente;
        newRow.dataset.nomPaciente = nomPaciente;

        newRow.innerHTML = `
            <td class="text-start">${nomPaciente}</td>
            <td>${docPaciente}</td>
            <td><strong>${cita.hora_formateada}</strong></td>
            <td id="estado-cita-${idCita}"><span class="badge bg-${badgeClass}">${cita.nom_est}</span></td>
            <td class="actions-container" id="actions-cita-${idCita}">${actionButtons}</td>
        `;

        // Lógica de inserción ordenada
        const allRows = Array.from(tableBody.querySelectorAll('tr[data-datetime]'));
        const newRowTime = new Date(dateTimeStr).getTime();
        let inserted = false;
        for (const row of allRows) {
            const existingRowTime = new Date(row.dataset.datetime).getTime();
            if (newRowTime < existingRowTime) {
                tableBody.insertBefore(newRow, row);
                inserted = true;
                break;
            }
        }
        if (!inserted) {
            tableBody.appendChild(newRow);
        }
        
        inicializarCita(newRow);
    }

    async function pollForChanges() {
        const rows = tableBody.querySelectorAll('tr[data-cita-id]');
        const idsEnPantalla = Array.from(rows).map(row => row.dataset.citaId);

        try {
            const response = await fetch(`includes_medi/ajax_gestionar_citas_tiempo_real.php?ids=${JSON.stringify(idsEnPantalla)}`);
            if (!response.ok) return;

            const data = await response.json();
            if (!data.success) return;

            if (data.citas_a_remover.length > 0) {
                data.citas_a_remover.forEach(id => {
                    const rowToRemove = document.getElementById(`cita-row-${id}`);
                    if (rowToRemove) {
                        rowToRemove.classList.add('row-removing');
                        setTimeout(() => rowToRemove.remove(), 500);
                    }
                });
            }

            if (data.citas_a_agregar.length > 0) {
                const noCitasRow = document.getElementById('no-citas-row');
                if (noCitasRow) noCitasRow.remove();

                data.citas_a_agregar.forEach(crearFilaCita);
            }

            if (tableBody.querySelectorAll('tr[data-cita-id]').length === 0 && !document.getElementById('no-citas-row')) {
                tableBody.innerHTML = '<tr id="no-citas-row"><td colspan="5" class="text-center p-4">No tiene citas activas para hoy.</td></tr>';
            }

        } catch (error) {
            console.error('Error en el polling de actualizaciones:', error);
        }
    }
    
    // ... (El resto del código de vista_citas.js no necesita cambios)
    
    async function safeFetch(url, options) { try { const response = await fetch(url, options); if (!response.ok) { console.error("Respuesta de red no fue OK:", response.status, response.statusText); return { success: false, message: `Error del servidor: ${response.status}` }; } const data = await response.json(); return data; } catch (error) { console.error("Error de Fetch o JSON.parse:", error); return { success: false, message: "Error de conexión o respuesta inválida del servidor." }; } }
    function inicializarCita(row) { const dateTimeStr = row.dataset.datetime; const llamarBtn = row.querySelector('.llamar-paciente-btn'); if (!llamarBtn || !dateTimeStr) return; const horaCita = new Date(dateTimeStr); const cincoMinutosAntes = new Date(horaCita.getTime() - 5 * 60000); const checkTime = () => { const ahora = new Date(); if (ahora >= cincoMinutosAntes) { llamarBtn.disabled = false; if (ahora > horaCita) { llamarBtn.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Llamar (Vencida)`; llamarBtn.className = 'btn btn-sm btn-danger btn-vencida llamar-paciente-btn'; if (!row.classList.contains('table-danger-custom')) row.classList.add('table-danger-custom'); } else { llamarBtn.innerHTML = `<i class="fas fa-bullhorn"></i> Llamar Paciente`; llamarBtn.className = 'btn btn-sm btn-warning llamar-paciente-btn'; } if (timers[row.dataset.citaId]?.timeout) clearTimeout(timers[row.dataset.citaId].timeout); } else { const tiempoParaHabilitar = cincoMinutosAntes - ahora; const timeoutId = setTimeout(checkTime, tiempoParaHabilitar); timers[row.dataset.citaId] = { ...timers[row.dataset.citaId], timeout: timeoutId }; } }; checkTime(); }
    function handleLlamarPaciente(button) { const row = button.closest('tr'); const citaId = row.dataset.citaId; const actionsContainer = row.querySelector('.actions-container'); const llegoBtn = actionsContainer.querySelector('.paciente-llego-btn'); button.style.display = 'none'; const wrapper = document.createElement('div'); wrapper.className = 'timer-action-wrapper'; const timerDisplay = document.createElement('div'); timerDisplay.className = 'countdown-timer'; wrapper.appendChild(timerDisplay); wrapper.appendChild(llegoBtn); actionsContainer.appendChild(wrapper); llegoBtn.style.display = 'inline-block'; startCountdown(citaId, COUNTDOWN_DURATION, timerDisplay, wrapper); }
    async function handlePacienteLlego(button) { const row = button.closest('tr'); const citaId = row.dataset.citaId; if (timers[citaId]?.interval) { clearInterval(timers[citaId].interval); delete timers[citaId]; } button.disabled = true; button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; const formData = new FormData(); formData.append('id_cita', citaId); const result = await safeFetch('includes_medi/ajax_cambiar_estado_cita.php', { method: 'POST', body: formData }); if (result.success) { actualizarFilaAEnProceso(row); } else { alert('Error: ' + (result.message || 'Intente de nuevo.')); button.disabled = false; button.innerHTML = '<i class="fas fa-user-check"></i> Paciente Llegó'; } }
    function actualizarFilaAEnProceso(row) { const estadoTd = row.querySelector(`#estado-cita-${row.dataset.citaId}`); const actionsContainer = row.querySelector('.actions-container'); estadoTd.innerHTML = `<span class="badge bg-warning text-dark">En Proceso</span>`; const newButton = `<button type="button" class="btn btn-sm btn-primary iniciar-consulta-btn" data-bs-toggle="modal" data-bs-target="#modalConsulta" data-id-cita="${row.dataset.citaId}" data-doc-paciente="${row.dataset.docPaciente}" data-nom-paciente="${row.dataset.nomPaciente}"><i class="fas fa-play"></i> Iniciar Consulta</button>`; actionsContainer.innerHTML = newButton; }
    function startCountdown(citaId, duration, display, wrapper) { let timer = duration; const intervalId = setInterval(() => { const minutes = String(Math.floor(timer / 60)).padStart(2, '0'); const seconds = String(timer % 60).padStart(2, '0'); display.textContent = `${minutes}:${seconds}`; if (--timer < 0) { clearInterval(intervalId); wrapper.remove(); autoCancelarCita(citaId); } }, 1000); timers[citaId] = { ...timers[citaId], interval: intervalId }; }
    async function autoCancelarCita(citaId) { const row = document.getElementById(`cita-row-${citaId}`); if (!row) return; const formData = new FormData(); formData.append('id_cita', citaId); const result = await safeFetch('includes_medi/ajax_cancelar_cita.php', { method: 'POST', body: formData }); if (result.success) { noAsistioModal.show(); modalNoAsistioEl.addEventListener('hidden.bs.modal', () => { pollForChanges(); }, { once: true }); } else { alert("Error al cancelar la cita: " + (result.message || "Error desconocido.")); } }
    function updateClock() { const now = new Date(); const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }; const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }; clockElement.innerHTML = `<strong>${now.toLocaleTimeString('es-CO', timeOptions)}</strong><small>${now.toLocaleDateString('es-CO', dateOptions)}</small>`; }
    tableBody.addEventListener('click', (e) => { const button = e.target.closest('button'); if (!button || button.disabled) return; if (button.classList.contains('llamar-paciente-btn')) { handleLlamarPaciente(button); } else if (button.classList.contains('paciente-llego-btn')) { handlePacienteLlego(button); } });
    document.querySelectorAll('#citas-table-body tr[data-cita-id]').forEach(inicializarCita);
    setInterval(updateClock, 1000);
    setInterval(pollForChanges, POLLING_INTERVAL);
});