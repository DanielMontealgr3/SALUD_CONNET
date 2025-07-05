document.addEventListener('DOMContentLoaded', function () {
    const cuerpoTabla = document.getElementById('cuerpo-tabla-pacientes');
    const modalNoAsistioElem = document.getElementById('modalNoAsistio');
    const noAsistioModal = modalNoAsistioElem ? new bootstrap.Modal(modalNoAsistioElem) : null;
    const contadorPacientesBadge = document.getElementById('contador-pacientes');

    // CORRECCIÓN: Se obtienen las rutas desde el objeto AppConfig global
    const API_URL = window.AppConfig?.API_URL || '/SALUDCONNECT/farma/';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const intervalos = {};
    const INTERVALO_REFRESCO = 5000;

    if (!cuerpoTabla) {
        return;
    }

    function mostrarNotificacionTurnoVencido(paciente) {
        const toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) return;

        const toastId = `toast-vencido-${paciente.id_turno_ent}`;
        if (document.getElementById(toastId)) return;

        const toastHTML = `
            <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-danger text-white">
                    <i class="bi bi-clock-history me-2"></i>
                    <strong class="me-auto">Turno Vencido</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    El turno de <strong>${paciente.nombre_paciente}</strong> ha pasado su hora programada.
                    <br><small>Turno #${paciente.id_turno_ent}</small>
                </div>
            </div>`;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = document.getElementById(toastId);
        toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
        const toast = new bootstrap.Toast(toastElement, { autohide: false });
        toast.show();
    }

    function detenerTimer(idTurno) {
        if (intervalos[idTurno]) {
            clearInterval(intervalos[idTurno]);
            delete intervalos[idTurno];
        }
    }

    function iniciarTimer(fila, tiempoInicial) {
        const idTurno = fila.id.replace('turno-', '');
        detenerTimer(idTurno);

        const contadorSpan = fila.querySelector('.contador-espera');
        if (!contadorSpan) return;

        let tiempo = tiempoInicial;

        const actualizarContador = () => {
            if (tiempo < 0) {
                detenerTimer(idTurno);
                marcarComoNoAsistido(idTurno);
                return;
            }
            const min = Math.floor(tiempo / 60);
            const seg = tiempo % 60;
            contadorSpan.textContent = `${min}:${seg < 10 ? '0' : ''}${seg}`;
            tiempo--;
        };
        
        actualizarContador();
        intervalos[idTurno] = setInterval(actualizarContador, 1000);
    }
    
    function llamarPaciente(idTurno) {
        const formData = new FormData();
        formData.append('accion', 'llamar_paciente');
        formData.append('id_turno', idTurno);
        formData.append('csrf_token', CSRF_TOKEN);
        
        fetch(`${API_URL}ajax_gestion_turnos.php`, { method: 'POST', body: formData })
            .then(() => actualizarTablaEnTiempoReal());
    }

    function confirmarLlegada(idTurno) {
        detenerTimer(idTurno);
        const formData = new FormData();
        formData.append('accion', 'paciente_llego');
        formData.append('id_turno', idTurno);
        formData.append('csrf_token', CSRF_TOKEN);
        
        fetch(`${API_URL}ajax_gestion_turnos.php`, { method: 'POST', body: formData })
            .then(() => actualizarTablaEnTiempoReal());
    }

    function marcarComoNoAsistido(idTurno) {
        const formData = new FormData();
        formData.append('accion', 'marcar_no_asistido');
        formData.append('id_turno', idTurno);
        formData.append('csrf_token', CSRF_TOKEN);
        
        fetch(`${API_URL}ajax_gestion_turnos.php`, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success && noAsistioModal) {
                    noAsistioModal.show();
                    actualizarTablaEnTiempoReal();
                }
            });
    }

    async function actualizarTablaEnTiempoReal() {
        if (document.hidden) return;

        try {
            const response = await fetch(`${API_URL}lista_pacientes.php?json=1&cache_bust=${Date.now()}`);
            if (!response.ok) {
                return;
            }

            const data = await response.json();
            
            if (contadorPacientesBadge) contadorPacientesBadge.textContent = data.total;

            const filasActuales = new Map([...cuerpoTabla.querySelectorAll('tr')].map(tr => [tr.id, tr]));
            const idsRecibidos = new Set();
            const turnosVencidosValidos = new Set();

            data.pacientes.forEach((paciente, index) => {
                const idFila = `turno-${paciente.id_turno_ent}`;
                idsRecibidos.add(idFila);

                let fila = filasActuales.get(idFila);
                if (!fila) {
                    fila = document.createElement('tr');
                    fila.id = idFila;
                }
                
                const celdasHTML = paciente.celdas.join('') + 
                    `<td class="acciones-tabla" data-idturno="${paciente.id_turno_ent}">${paciente.acciones_html}</td>`;
                
                const idHistoriaActual = paciente.id_historia.toString();

                if (fila.innerHTML !== celdasHTML || fila.className !== paciente.clase_fila || fila.dataset.idhistoria !== idHistoriaActual) {
                    fila.className = paciente.clase_fila;
                    fila.dataset.estado = paciente.estado_llamado;
                    fila.dataset.idhistoria = idHistoriaActual;
                    fila.innerHTML = celdasHTML;
                }
                
                if (cuerpoTabla.children[index] !== fila) {
                    cuerpoTabla.insertBefore(fila, cuerpoTabla.children[index] || null);
                }

                if (paciente.estado_llamado == 1 && paciente.tiempo_restante > 0) {
                    iniciarTimer(fila, paciente.tiempo_restante);
                } else {
                    detenerTimer(paciente.id_turno_ent);
                }
                
                if (paciente.clase_fila === 'table-danger' && paciente.estado_llamado == 0) {
                    turnosVencidosValidos.add(paciente.id_turno_ent.toString());
                    mostrarNotificacionTurnoVencido(paciente);
                }
            });

            filasActuales.forEach((fila, id) => {
                if (!idsRecibidos.has(id)) {
                    detenerTimer(id.replace('turno-', ''));
                    fila.remove();
                }
            });
            
            document.querySelectorAll('.toast-container .toast').forEach(toastEl => {
                const turnId = toastEl.id.replace('toast-vencido-', '');
                if (!turnosVencidosValidos.has(turnId)) {
                    const toastInstance = bootstrap.Toast.getInstance(toastEl);
                    if (toastInstance) toastInstance.hide();
                    else toastEl.remove();
                }
            });

            if (cuerpoTabla.children.length === 0 && !cuerpoTabla.querySelector('.empty-message')) {
                cuerpoTabla.innerHTML = '<tr class="empty-message"><td colspan="7" class="text-center p-4">No hay pacientes pendientes de entrega en este momento.</td></tr>';
            } else if (cuerpoTabla.children.length > 0) {
                const emptyMsg = cuerpoTabla.querySelector('.empty-message');
                if (emptyMsg) emptyMsg.remove();
            }

        } catch (error) {
            console.error(error);
        }
    }
    
    cuerpoTabla.addEventListener('click', function(e) {
        const target = e.target;
        const btnLlamar = target.closest('.btn-llamar-paciente');
        const btnLlego = target.closest('.btn-paciente-llego');
        const btnEntregar = target.closest('.btn-entregar-medicamentos');

        if (btnLlamar) {
            e.preventDefault();
            btnLlamar.disabled = true;
            llamarPaciente(btnLlamar.closest('td').dataset.idturno);
        } else if (btnLlego) {
            e.preventDefault();
            btnLlego.disabled = true;
            confirmarLlegada(btnLlego.closest('td').dataset.idturno);
        } else if (btnEntregar) {
            e.preventDefault();
            const fila = target.closest('tr');
            const idTurno = target.closest('td').dataset.idturno;
            const idHistoria = fila.dataset.idhistoria;
            const placeholder = document.getElementById('modal-entrega-placeholder');

            if (!idHistoria || !placeholder) {
                Swal.fire('Error', 'No se pudo obtener la información necesaria para la entrega.', 'error');
                return;
            }
            
            placeholder.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';
            
            fetch(`${API_URL}entregar/modal_entrega.php?id_historia=${idHistoria}&id_turno=${idTurno}`)
               .then(response => response.text())
               .then(html => {
                   placeholder.innerHTML = html;
                   const modalElement = document.getElementById('modalRealizarEntrega');
                   if (modalElement) {
                       const modal = new bootstrap.Modal(modalElement);
                       modal.show();
                       if (typeof inicializarLogicaEntrega === 'function') {
                           inicializarLogicaEntrega(modalElement);
                       }
                   }
               })
               .catch(error => {
                   placeholder.innerHTML = '';
                   Swal.fire('Error', 'No se pudo cargar la interfaz de entrega.', 'error');
               });
        }
    });

    setInterval(actualizarTablaEnTiempoReal, INTERVALO_REFRESCO);
    actualizarTablaEnTiempoReal();
});