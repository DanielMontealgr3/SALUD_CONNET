/**
 * Inicializa toda la lógica interactiva para el modal de entrega de medicamentos.
 * Utiliza delegación de eventos para mayor eficiencia y robustez.
 * @param {HTMLElement} modalElement - El elemento del DOM del modal.
 * @param {string} contexto - El contexto de la operación ('turno' o 'entregar_pendiente').
 */
function inicializarLogicaEntrega(modalElement, contexto = 'turno') {
    // --- 1. SELECCIÓN DE ELEMENTOS Y CONFIGURACIÓN INICIAL ---
    const cuerpoTabla = modalElement.querySelector('#cuerpo-tabla-entrega');
    const btnFinalizar = modalElement.querySelector('#btn-finalizar-entrega-completa');
    const btnCancelar = modalElement.querySelector('#btn-cancelar-entrega');
    const btnCerrarModal = modalElement.querySelector('.modal-header .btn-close');
    const idEntregaPendiente = modalElement.dataset.idEntregaPendiente;

    // MEJORA: Se obtienen las rutas y el token CSRF del DOM para no tenerlos fijos en el JS.
    const API_BASE_URL = document.querySelector('meta[name="api-base-url"]')?.getAttribute('content') || '/SALUDCONNECT/farma/';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Verificación de que los elementos esenciales existen.
    if (!cuerpoTabla || !btnFinalizar || !btnCancelar || !btnCerrarModal) {
        console.error("No se encontraron elementos esenciales en el modal de entrega.");
        return;
    }

    // --- 2. FUNCIONES AUXILIARES Y DE LÓGICA DE NEGOCIO ---

    const deshabilitarCierreModal = () => {
        btnCancelar.disabled = true;
        btnCerrarModal.disabled = true;
        modalElement.setAttribute('data-bs-keyboard', 'false');
        modalElement.setAttribute('data-bs-backdrop', 'static');
    };

    const verificarEstadoFinalizacion = () => {
        const totalFilas = cuerpoTabla.querySelectorAll('tr[data-id-detalle]').length;
        const filasProcesadas = cuerpoTabla.querySelectorAll('tr[data-estado="procesado"]').length;
        btnFinalizar.disabled = !(totalFilas > 0 && totalFilas === filasProcesadas);
    };

    const procesarEntregaItem = async (fila) => {
        deshabilitarCierreModal();
        const celdaAccion = fila.querySelector('.celda-accion');
        celdaAccion.innerHTML = '<div class="d-flex justify-content-center align-items-center"><div class="spinner-border spinner-border-sm text-primary"></div></div>';

        const formData = new FormData();
        formData.append('accion', contexto === 'entregar_pendiente' ? 'entregar_pendiente' : 'validar_y_entregar');
        formData.append('id_detalle', fila.dataset.idDetalle);
        formData.append('csrf_token', CSRF_TOKEN); // MEJORA: Añadir token de seguridad.
        if (idEntregaPendiente) {
            formData.append('id_entrega_pendiente', idEntregaPendiente);
        }

        try {
            // MEJORA: La URL de la API es ahora dinámica.
            const response = await fetch(`${API_BASE_URL}entregar/ajax_procesar_entrega.php`, { method: 'POST', body: formData });
            const data = await response.json();

            if (data.success) {
                fila.dataset.estado = "procesado";
                fila.classList.add('fila-procesada');
                fila.querySelector('.estado-verificacion i').className = 'bi bi-check-all text-success fs-4';
                
                let resumenHTML = '';
                if (data.entregas && data.entregas.length > 0) {
                    resumenHTML += '<ul class="list-unstyled mb-0 small">';
                    data.entregas.forEach(e => {
                        resumenHTML += `<li><i class="bi bi-check text-success"></i> ${e.cantidad} de Lote <strong>${e.lote}</strong></li>`;
                    });
                    resumenHTML += '</ul>';
                }
                if (data.pendiente > 0) {
                    resumenHTML += `<div class="mt-1"><span class="badge bg-warning text-dark">Pendiente: ${data.pendiente}</span></div>`;
                }
                fila.querySelector('.celda-lotes').innerHTML = '';
                celdaAccion.innerHTML = resumenHTML;
                
                if (data.radicado) {
                     await Swal.fire({ icon: 'success', title: 'Acción Completada', html: `Se ha generado el pendiente con radicado:<br><strong class="fs-5 text-primary">${data.radicado}</strong>`, confirmButtonText: 'Entendido' });
                } else {
                     await Swal.fire({ icon: 'success', title: 'Entrega Registrada', text: 'El medicamento ha sido registrado correctamente.', timer: 2000, showConfirmButton: false });
                }
                verificarEstadoFinalizacion();
            } else {
                throw new Error(data.message || 'Error desconocido al procesar la entrega.');
            }
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
            celdaAccion.innerHTML = '<button class="btn btn-danger btn-sm btn-reintentar-proceso">Reintentar</button>';
        }
    };

    const finalizarTurno = async function() {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Finalizando...';
        
        const formData = new FormData();
        const primeraFila = cuerpoTabla.querySelector('tr[data-id-turno]');
        const idTurno = primeraFila ? primeraFila.dataset.idTurno : null;
        formData.append('accion', 'finalizar_turno');
        formData.append('id_turno', idTurno);
        formData.append('csrf_token', CSRF_TOKEN);
        
        try {
            const response = await fetch(`${API_BASE_URL}entregar/ajax_procesar_entrega.php`, { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                await Swal.fire('¡Proceso Finalizado!', data.message, 'success');
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) modalInstance.hide();
                if (typeof refrescarTablaEntregas === 'function') refrescarTablaEntregas();
            } else {
                throw new Error(data.message || 'Error desconocido al finalizar el turno.');
            }
        } catch(error) {
            Swal.fire('Error de Conexión', error.message, 'error');
            this.disabled = false; this.innerHTML = `<i class="bi bi-truck me-2"></i> Finalizar`;
        }
    };
    
    const finalizarEntregaPendiente = async function() {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Finalizando...';

        const formData = new FormData();
        formData.append('accion', 'finalizar_entrega_pendiente');
        formData.append('id_entrega_pendiente', idEntregaPendiente);
        formData.append('csrf_token', CSRF_TOKEN);
        
        try {
            const response = await fetch(`${API_BASE_URL}entregar/ajax_procesar_entrega.php`, { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                await Swal.fire({title: '¡Pendiente Finalizado!', text: data.message, icon: 'success', confirmButtonText: 'OK'});
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) modalInstance.hide();
                // Aquí podrías llamar a una función para recargar la lista de pendientes.
                if (typeof refrescarTablaPendientes === 'function') refrescarTablaPendientes();
            } else {
                throw new Error(data.message || 'Error desconocido al finalizar el pendiente.');
            }
        } catch(error) {
            Swal.fire('Error de Conexión', error.message, 'error');
            this.disabled = false; this.innerHTML = 'Finalizar Pendiente';
        }
    };
    
    // Asigna la función correcta al botón de finalizar según el contexto.
    btnFinalizar.addEventListener('click', (contexto === 'entregar_pendiente') ? finalizarEntregaPendiente.bind(btnFinalizar) : finalizarTurno.bind(btnFinalizar));
    
    // --- 3. MANEJO DE EVENTOS (DELEGACIÓN) ---
    cuerpoTabla.addEventListener('click', async (e) => {
        const fila = e.target.closest('tr[data-id-detalle]');
        if (!fila) return;

        // -- Acción: Verificar Código de Barras --
        if (e.target.closest('.btn-verificar-codigo')) {
            const btnVerificarCodigo = e.target.closest('.btn-verificar-codigo');
            const inputCodigo = fila.querySelector('input[name="codigo_barras_verif"]');
            
            if (inputCodigo.value.trim().toLowerCase() !== fila.dataset.codigoBarras.toLowerCase()) {
                Swal.fire({ icon: 'error', title: 'Código Incorrecto', text: 'El código no corresponde al medicamento.' });
                return;
            }
            
            inputCodigo.disabled = true;
            btnVerificarCodigo.disabled = true;
            inputCodigo.classList.add('is-valid');
            fila.querySelector('.estado-verificacion i').className = 'bi bi-check-circle-fill text-success fs-4';
            
            try {
                // MEJORA: La URL de la API es ahora dinámica.
                const response = await fetch(`${API_BASE_URL}entregar/ajax_obtener_lotes.php?id_medicamento=${fila.dataset.idMedicamento}`);
                const data = await response.json();
                const cantidadRequerida = parseInt(fila.dataset.cantidadRequerida, 10);

                const lotesEntregables = data.lotes ? data.lotes.filter(l => l.estado === 'vigente') : [];
                const lotesNoEntregables = data.lotes ? data.lotes.filter(l => l.estado !== 'vigente') : [];
                const stockTotalEntregable = lotesEntregables.reduce((acc, l) => acc + parseInt(l.stock_lote, 10), 0);
                fila.dataset.stockTotal = stockTotalEntregable;

                let swalHTML = '';
                let lotesNecesarios = [];
                
                if (stockTotalEntregable > 0) {
                    swalHTML += '<strong><u>Instrucciones de Entrega:</u></strong><br>';
                    let cantidadTemporal = cantidadRequerida;
                    for (const lote of lotesEntregables) {
                        if (cantidadTemporal <= 0) break;
                        const tomarDeLote = Math.min(cantidadTemporal, parseInt(lote.stock_lote));
                        lotesNecesarios.push({ ...lote, a_tomar: tomarDeLote });
                        swalHTML += `Tome <strong>${tomarDeLote}</strong> de Lote <strong>${lote.lote}</strong>.<br>`;
                        cantidadTemporal -= tomarDeLote;
                    }
                }

                if (lotesNoEntregables.length > 0) {
                    swalHTML += '<hr><strong><u>Alertas de Lotes NO Entregables:</u></strong><ul class="list-unstyled text-start small mt-2">';
                    lotesNoEntregables.forEach(l => {
                        let motivo = l.estado === 'vencido' ? 'VENCIDO' : 'PRÓXIMO A VENCER';
                        swalHTML += `<li><i class="bi bi-x-circle-fill text-danger"></i> Lote <strong>${l.lote}</strong> está <strong>${motivo}</strong>.</li>`;
                    });
                    swalHTML += '</ul>';
                }

                if (contexto === 'turno' && stockTotalEntregable < cantidadRequerida) {
                     swalHTML += `<hr>Se generará un pendiente por <strong>${cantidadRequerida - stockTotalEntregable}</strong> unidad(es).`;
                }
                
                await Swal.fire({ title: 'Verificación de Lotes', html: swalHTML || 'No hay lotes con stock para este producto.', icon: 'info', confirmButtonText: 'Entendido' });
                
                const celdaLotes = fila.querySelector('.celda-lotes');
                const celdaAccion = fila.querySelector('.celda-accion');

                if (stockTotalEntregable > 0) {
                    let inputsHTML = '';
                    lotesNecesarios.forEach((l, index) => {
                        inputsHTML += `<div class="input-group input-group-sm mb-1"><span class="input-group-text">${l.a_tomar} de</span><input type="text" class="form-control" placeholder="Lote ${l.lote}" data-lote-esperado="${l.lote}" name="lote_manual_${index}"></div>`;
                    });
                    celdaLotes.innerHTML = inputsHTML;
                    celdaAccion.innerHTML = `<button class="btn btn-primary btn-sm btn-validar-lotes-final" disabled><i class="bi-key-fill"></i> Validar Lotes</button>`;
                } else if (contexto === 'turno') {
                     celdaAccion.innerHTML = `<button class="btn btn-warning btn-sm btn-confirmar-accion"><i class="bi bi-file-earmark-plus"></i> Generar Pendiente Total</button>`;
                }
            } catch (error) {
                Swal.fire('Error', 'No se pudo verificar el stock.', 'error');
            }
        }

        // -- Acción: Validar Lotes Ingresados --
        if (e.target.closest('.btn-validar-lotes-final')) {
            const celdaAccion = fila.querySelector('.celda-accion');
            const stockTotal = parseInt(fila.dataset.stockTotal, 10);
            const cantidadRequerida = parseInt(fila.dataset.cantidadRequerida, 10);

            if (contexto === 'turno' && stockTotal < cantidadRequerida) {
                celdaAccion.innerHTML = `<button class="btn btn-info btn-sm btn-confirmar-accion"><i class="bi bi-box-arrow-right"></i> Entregar y Generar</button>`;
            } else {
                celdaAccion.innerHTML = `<button class="btn btn-success btn-sm btn-confirmar-accion"><i class="bi bi-check-circle-fill"></i> Confirmar Entrega</button>`;
            }
        }
        
        // -- Acción: Confirmar la acción final (entrega o pendiente) --
        if (e.target.closest('.btn-confirmar-accion')) {
            const stockTotal = parseInt(fila.dataset.stockTotal, 10) || 0;
            const cantidadRequerida = parseInt(fila.dataset.cantidadRequerida, 10);
            let swalConfig = { title: '¿Confirmar Acción?', icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, confirmar' };
            
            if (stockTotal === 0) {
                swalConfig.text = `Se generará un pendiente por ${cantidadRequerida} unidades.`;
            } else if (stockTotal < cantidadRequerida) {
                swalConfig.text = `Se entregarán ${stockTotal} unidades y se generará un pendiente por ${cantidadRequerida - stockTotal}.`;
            } else {
                swalConfig.text = `Se entregarán ${cantidadRequerida} unidades en su totalidad.`;
            }
            
            const result = await Swal.fire(swalConfig);
            if (result.isConfirmed) {
                procesarEntregaItem(fila);
            }
        }

        // -- Acción: Reintentar proceso si falló --
        if (e.target.closest('.btn-reintentar-proceso')) {
            procesarEntregaItem(fila);
        }
    });

    // Evento para validar la entrada manual de lotes.
    cuerpoTabla.addEventListener('input', (e) => {
        if (e.target.matches('input[name^="lote_manual_"]')) {
            const celdaLotes = e.target.closest('.celda-lotes');
            const celdaAccion = e.target.closest('tr').querySelector('.celda-accion');
            const inputs = Array.from(celdaLotes.querySelectorAll('input'));
            const todosValidos = inputs.every(input => input.value.trim().toLowerCase() === input.dataset.loteEsperado.toLowerCase());
            const btnValidar = celdaAccion.querySelector('.btn-validar-lotes-final');
            if (btnValidar) btnValidar.disabled = !todosValidos;
        }
    });
}