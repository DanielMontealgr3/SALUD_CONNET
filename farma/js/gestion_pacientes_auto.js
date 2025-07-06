document.addEventListener('DOMContentLoaded', function () {
    // ================== CAMBIO CLAVE #1: LEER EL TOKEN CSRF CORRECTAMENTE ==================
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    // =======================================================================================

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
                    El turno de <strong>${paciente.nombre_paciente}</strong> ha pasado su hora programada.
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
                 
                 // ================== CAMBIO CLAVE #2: USAR AppConfig.API_URL ==================
                 const url = `${AppConfig.API_URL}entregar/modal_entrega.php?id_historia=${idHistoria}&id_turno=${idTurno}`;
                 fetch(url)
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
            // Se usa la URL completa para evitar problemas de rutas relativas
            const response = await fetch(`${AppConfig.API_URL}lista_pacientes.php?json=1`);
            if (!response.ok) return;

            const data = await response.json();
            
            if (contadorPacientesBadge) {
                contadorPacientesBadge.textContent = data.total;
            }

            const filasActuales = new Map([...cuerpoTabla.querySelectorAll('tr')].map(tr => [tr.id, tr]));
            const idsRecibidos = new Set();
            const turnosVencidosValidos = new Set();
            let pacientesConNombre = [];

            data.pacientes.forEach((paciente, index) => {
                const idFila = `turno-${paciente.id_turno_ent}`;
                idsRecibidos.add(idFila);
                
                // Extraer el nombre del paciente del HTML de las celdas para la notificación
                const nombreMatch = /<td>(.*?)<\/td>/.exec(paciente.celdas[2]);
                paciente.nombre_paciente = nombreMatch ? nombreMatch[1] : 'Paciente';

                let fila = filasActuales.get(idFila);
                if (!fila) {
                    fila = document.createElement('tr');
                    fila.id = idFila;
                }
                
                const celdasHTML = paciente.celdas.join('') + 
                    `<td class="acciones-tabla" data-idturno="${paciente.id_turno_ent}" data-tiempo-restante="${paciente.tiempo_restante}">${paciente.acciones_html}</td>`;
                
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

    function enviarPeticion(accion, idTurno) {
        const formData = new FormData();
        formData.append('accion', accion);
        formData.append('id_turno', idTurno);
        // ================== CAMBIO CLAVE #3: ENVIAR EL TOKEN CSRF CORRECTO ==================
        formData.append('csrf_token', csrfToken); 
        // ====================================================================================

        // ================== CAMBIO CLAVE #4: USAR AppConfig.API_URL PARA LA PETICIÓN ==================
        const url = `${AppConfig.API_URL}ajax_gestion_turnos.php`;
        // ============================================================================================

        return fetch(url, {
            method: 'POST',
            body: formData
        });
    }

    function llamarPaciente(idTurno) {
        enviarPeticion('llamar_paciente', idTurno)
            .then(async response => { // La hacemos async para poder leer el texto si falla
                if (!response.ok) {
                    // Si la respuesta no es OK (ej. error 500), leemos el cuerpo como texto
                    const errorText = await response.text();
                    // Mostramos el texto del error del servidor para depuración
                    console.error("Error del servidor:", errorText);
                    // Lanzamos un error para que lo atrape el .catch()
                    throw new Error(`Error en el servidor: ${response.status} ${response.statusText}`);
                }
                // Si todo está OK, procesamos el JSON
                return response.json();
            })
            .then(data => {
                // Si la petición fue exitosa (success: true), actualizamos la tabla
                if(data.success) {
                    actualizarTablaEnTiempoReal();
                } else {
                    // Si el servidor devolvió success: false, mostramos el mensaje
                    console.error("Error en la operación:", data.message);
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                // Este catch ahora atrapará tanto errores de red como los errores que lanzamos manualmente
                console.error("Fetch error:", err);
                // Habilitamos de nuevo el botón si falló para que el usuario pueda reintentar
                const btn = document.querySelector(`.acciones-tabla[data-idturno="${idTurno}"] .btn-llamar-paciente`);
                if (btn) btn.disabled = false;
            });
    }


    function confirmarLlegada(idTurno) {
        detenerTimer(idTurno);
        enviarPeticion('paciente_llego', idTurno)
            .then(() => actualizarTablaEnTiempoReal());
    }

    function marcarComoNoAsistido(idTurno) {
        enviarPeticion('marcar_no_asistido', idTurno)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (noAsistioModal) noAsistioModal.show();
                    actualizarTablaEnTiempoReal();
                }
            });
    }

    if (cuerpoTabla) {
        asignarListeners(cuerpoTabla);
        cuerpoTabla.querySelectorAll('tr').forEach(tr => {
            const tdAcciones = tr.querySelector('td.acciones-tabla');
            if (tdAcciones) {
                const tiempo = tdAcciones.dataset.tiempoRestante;
                const estado = tr.dataset.estado;
                if (estado == 1 && tiempo && parseInt(tiempo, 10) > 0) {
                    iniciarTimer(tr, parseInt(tiempo, 10));
                }
            }
        });
        setInterval(actualizarTablaEnTiempoReal, 4000);
        actualizarTablaEnTiempoReal();
    }
});