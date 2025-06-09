document.addEventListener('DOMContentLoaded', function () {
    const cuerpoTabla = document.getElementById('cuerpo-tabla-pacientes');
    const noAsistioModal = new bootstrap.Modal(document.getElementById('modalNoAsistio'));
    const contadorPacientesBadge = document.getElementById('contador-pacientes');
    const intervalos = {};

    function iniciarTimer(fila, tiempoInicial) {
        const idTurno = fila.id.replace('turno-', '');
        if (intervalos[idTurno]) {
            clearInterval(intervalos[idTurno]);
        }

        const contadorSpan = fila.querySelector('.contador-espera');
        if (!contadorSpan) return;

        let tiempo = tiempoInicial;

        function actualizarContador() {
            if (tiempo < 0) {
                clearInterval(intervalos[idTurno]);
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
                 const idTurno = this.closest('td').dataset.idturno;
                 window.location.href = `detalles_medicamento.php?id_turno=${idTurno}`;
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

            const idsRecibidos = new Set(data.pacientes.map(p => `turno-${p.id_turno_ent}`));
            const filasActuales = new Map([...cuerpoTabla.querySelectorAll('tr')].map(tr => [tr.id, tr]));

            data.pacientes.forEach(paciente => {
                const idFila = `turno-${paciente.id_turno_ent}`;
                let fila = filasActuales.get(idFila);

                if (!fila) {
                    fila = document.createElement('tr');
                    fila.id = idFila;
                    cuerpoTabla.appendChild(fila);
                }
                
                fila.className = paciente.clase_fila;
                fila.dataset.estado = paciente.estado_llamado;

                const celdasHTML = paciente.celdas.join('') + 
                    `<td class="acciones-tabla" data-idturno="${paciente.id_turno_ent}">${paciente.acciones_html}</td>`;

                if (fila.innerHTML !== celdasHTML) {
                    fila.innerHTML = celdasHTML;
                    asignarListeners(fila);
                    if (paciente.estado_llamado == 1 && paciente.tiempo_restante > 0) {
                        iniciarTimer(fila, paciente.tiempo_restante);
                    }
                }
            });

            filasActuales.forEach((fila, id) => {
                if (!idsRecibidos.has(id)) {
                    if (intervalos[id.replace('turno-','')]) clearInterval(intervalos[id.replace('turno-','')]);
                    fila.remove();
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
    }
});