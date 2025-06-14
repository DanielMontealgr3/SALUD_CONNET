document.addEventListener('DOMContentLoaded', function () {
    const cuerpoTabla = document.getElementById('cuerpo-tabla-pacientes');
    const noAsistioModal = new bootstrap.Modal(document.getElementById('modalNoAsistio'));
    const contadorPacientesBadge = document.getElementById('contador-pacientes');
    const intervalos = {};

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
                    El turno de <strong>${paciente.celdas[2]}</strong> ha pasado su hora programada.
                    <br>
                    <small>Turno #${paciente.id_turno_ent}</small>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = document.getElementById(toastId);
        
        toastElement.addEventListener('hidden.bs.toast', function () {
            this.remove();
        });

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

        function actualizarContador() {
            if (tiempo < 0) {
                detenerTimer(idTurno);
                marcarComoNoAsistido(idTurno);
                return;
            }
            const min = Math.floor(tiempo / 60);
            const seg = tiempo % 60;
            contadorSpan.textContent = `${min}:${seg < 10 ? '0' : ''}${seg}`;
            tiempo--;
        }
        
        actualizarContador();
        intervalos[idTurno] = setInterval(actualizarContador, 1000);
    }

    function asignarListeners(elemento) {
        elemento.querySelectorAll('.btn-llamar-paciente').forEach(btn => {
            btn.onclick = function (e) {
                e.preventDefault();
                this.disabled = true;
                llamarPaciente(this.closest('td').dataset.idturno);
            };
        });

        elemento.querySelectorAll('.btn-paciente-llego').forEach(btn => {
            btn.onclick = function (e) {
                e.preventDefault();
                this.disabled = true;
                confirmarLlegada(this.closest('td').dataset.idturno);
            };
        });

        elemento.querySelectorAll('.btn-entregar-medicamentos').forEach(btn => {
            btn.onclick = function(e) {
                 e.preventDefault();
                 const fila = this.closest('tr');
                 const idTurno = this.closest('td').dataset.idturno;
                 const idHistoria = fila.dataset.idhistoria;

                 if (!idHistoria) {
                     Swal.fire('Error', 'No se pudo obtener la información del paciente para la entrega.', 'error');
                     return;
                 }
                 
                 const placeholder = document.getElementById('modal-entrega-placeholder');
                 if (!placeholder) {
                     console.error("El contenedor 'modal-entrega-placeholder' no existe.");
                     return;
                 }

                 placeholder.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';
                 
                 fetch(`entregar/modal_entrega.php?id_historia=${idHistoria}&id_turno=${idTurno}`)
                    .then(response => response.text())
                    .then(html => {
                        placeholder.innerHTML = html;
                        const modalElement = document.getElementById('modalRealizarEntrega');
                        const modal = new bootstrap.Modal(modalElement);
                        modal.show();
                        
                        if (typeof inicializarLogicaEntrega === 'function') {
                            inicializarLogicaEntrega(modalElement);
                        } else {
                            console.error("Error: inicializarLogicaEntrega() no está definida.");
                        }
                    })
                    .catch(error => {
                        placeholder.innerHTML = '';
                        console.error('Error al cargar el modal de entrega:', error);
                        Swal.fire('Error', 'No se pudo cargar la interfaz de entrega.', 'error');
                    });
            };
        });
    }
    
    async function actualizarTablaEnTiempoReal() {
        if (document.hidden) return;

        try {
            const response = await fetch('lista_pacientes.php?json=1');
            if (!response.ok) return;

            const data = await response.json();
            
            if (contadorPacientesBadge) {
                contadorPacientesBadge.textContent = data.total;
            }

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
                    asignarListeners(fila);
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
            console.error("Error actualizando la tabla:", error);
        }
    }

    function llamarPaciente(idTurno) {
        const formData = new FormData();
        formData.append('accion', 'llamar_paciente');
        formData.append('id_turno', idTurno);
        formData.append('csrf_token', csrfTokenListaPacientesGlobal);
        fetch('ajax_gestion_turnos.php', { method: 'POST', body: formData })
            .then(() => actualizarTablaEnTiempoReal());
    }

    function confirmarLlegada(idTurno) {
        detenerTimer(idTurno);
        const formData = new FormData();
        formData.append('accion', 'paciente_llego');
        formData.append('id_turno', idTurno);
        formData.append('csrf_token', csrfTokenListaPacientesGlobal);
        fetch('ajax_gestion_turnos.php', { method: 'POST', body: formData })
            .then(() => actualizarTablaEnTiempoReal());
    }

    function marcarComoNoAsistido(idTurno) {
        const formData = new FormData();
        formData.append('accion', 'marcar_no_asistido');
        formData.append('id_turno', idTurno);
        formData.append('csrf_token', csrfTokenListaPacientesGlobal);
        fetch('ajax_gestion_turnos.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if(noAsistioModal) noAsistioModal.show();
                actualizarTablaEnTiempoReal();
            }
        });
    }

    if (cuerpoTabla) {
        asignarListeners(cuerpoTabla);
        cuerpoTabla.querySelectorAll('tr').forEach(tr => {
            const tiempo = tr.querySelector('td.acciones-tabla')?.dataset.tiempoRestante;
            if (tiempo && parseInt(tiempo, 10) > 0) {
                iniciarTimer(tr, parseInt(tiempo, 10));
            }
        });
        setInterval(actualizarTablaEnTiempoReal, 4000);
        actualizarTablaEnTiempoReal();
    }
});