function inicializarLogicaEntrega(modalElement, contexto = 'turno') {
    const cuerpoTabla = modalElement.querySelector('#cuerpo-tabla-entrega');
    const btnFinalizar = modalElement.querySelector('#btn-finalizar-entrega-completa');
    const btnCancelar = modalElement.querySelector('#btn-cancelar-entrega');
    const btnCerrarModal = modalElement.querySelector('.modal-header .btn-close');
    const btnGenerarPendientesLote = modalElement.querySelector('#btn-generar-pendientes-lote');
    const idEntregaPendiente = modalElement.dataset.idEntregaPendiente;

    if (contexto === 'entregar_pendiente') {
        btnFinalizar.textContent = 'Finalizar Entrega Pendiente';
    }

    const deshabilitarCierreModal = () => {
        btnCancelar.disabled = true;
        btnCerrarModal.disabled = true;
        modalElement.setAttribute('data-bs-keyboard', 'false');
        modalElement.setAttribute('data-bs-backdrop', 'static');
    };
    
    const actualizarBotonPendientesLote = () => {
        const filasParaPendiente = cuerpoTabla.querySelectorAll('tr[data-accion-pendiente="true"]').length;
        if (filasParaPendiente > 1) {
            btnGenerarPendientesLote.style.display = 'inline-block';
        } else {
            btnGenerarPendientesLote.style.display = 'none';
        }
    };

    const verificarSiFinalizo = () => {
        const totalFilas = cuerpoTabla.querySelectorAll('tr').length;
        const filasProcesadas = cuerpoTabla.querySelectorAll('tr[data-estado="procesado"]').length;
        if (totalFilas > 0 && totalFilas === filasProcesadas) {
            btnFinalizar.disabled = false;
            btnGenerarPendientesLote.style.display = 'none';
        }
    };

    const procesarEntregaFinal = async (fila) => {
        deshabilitarCierreModal();
        const celdaAccion = fila.querySelector('.celda-accion');
        celdaAccion.innerHTML = '<div class="d-flex justify-content-center align-items-center"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
        
        const accion_a_realizar = (contexto === 'entregar_pendiente') ? 'entregar_pendiente' : 'validar_y_entregar';
        const formData = new FormData();
        formData.append('accion', accion_a_realizar);
        formData.append('id_detalle', fila.dataset.idDetalle);
        if (idEntregaPendiente) {
            formData.append('id_entrega_pendiente', idEntregaPendiente);
        }

        try {
            const response = await fetch('/SALUDCONNECT/farma/entregar/ajax_procesar_entrega.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                fila.dataset.estado = "procesado";
                fila.classList.remove('fila-pendiente-lista');
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
                     await Swal.fire({
                        icon: 'success', title: 'Acción Completada',
                        html: `Se ha generado el pendiente con radicado:<br><strong class="fs-5 text-primary">${data.radicado}</strong>`,
                        confirmButtonText: 'Entendido'
                    });
                } else {
                     await Swal.fire({
                        icon: 'success', title: 'Entrega Registrada',
                        text: 'El medicamento ha sido registrado correctamente.',
                        timer: 2000, showConfirmButton: false
                    });
                }
                verificarSiFinalizo();
            } else {
                Swal.fire('Error', data.message, 'error');
                celdaAccion.innerHTML = '<button class="btn btn-danger btn-sm">Reintentar</button>';
            }
        } catch (error) {
            Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor.', 'error');
            celdaAccion.innerHTML = '<button class="btn btn-danger btn-sm">Reintentar</button>';
        }
    };
    
    cuerpoTabla.querySelectorAll('tr').forEach(fila => {
        const btnVerificarCodigo = fila.querySelector('.btn-verificar-codigo');
        const inputCodigo = fila.querySelector('input[name="codigo_barras_verif"]');
        const celdaLotes = fila.querySelector('.celda-lotes');
        const celdaAccion = fila.querySelector('.celda-accion');
        
        btnVerificarCodigo.addEventListener('click', async () => {
            if (inputCodigo.value.trim() !== fila.dataset.codigoBarras) {
                Swal.fire({ icon: 'error', title: 'Código Incorrecto', text: 'El código no corresponde al medicamento.'}); return;
            }
            inputCodigo.disabled = true;
            btnVerificarCodigo.disabled = true;
            inputCodigo.classList.add('is-valid');
            fila.querySelector('.estado-verificacion i').className = 'bi bi-check-circle-fill text-success fs-4';
            
            try {
                const response = await fetch(`/SALUDCONNECT/farma/entregar/ajax_obtener_lotes.php?id_medicamento=${fila.dataset.idMedicamento}`);
                const data = await response.json();
                
                if (data.success && data.lotes.length > 0) {
                    const stockTotalEnLotes = data.lotes.reduce((acc, lote) => acc + parseInt(lote.stock_lote, 10), 0);
                    const cantidadRequerida = parseInt(fila.dataset.cantidadRequerida, 10);
                    let lotesNecesarios = [];
                    let cantidadTemporal = cantidadRequerida;

                    for(const lote of data.lotes) {
                        if(cantidadTemporal <= 0) break;
                        const tomarDeLote = Math.min(cantidadTemporal, parseInt(lote.stock_lote));
                        lotesNecesarios.push({ ...lote, a_tomar: tomarDeLote });
                        cantidadTemporal -= tomarDeLote;
                    }
                    
                    fila.dataset.lotesNecesarios = JSON.stringify(lotesNecesarios);
                    fila.dataset.stockTotal = stockTotalEnLotes;

                    let mensajeHTML = '';
                    lotesNecesarios.forEach(l => {
                        mensajeHTML += `Tome <strong>${l.a_tomar}</strong> unidad(es) del Lote: <strong>${l.lote}</strong><br>`;
                    });

                    if (stockTotalEnLotes < cantidadRequerida && contexto === 'turno') {
                        mensajeHTML += `<hr>Se generará un pendiente por <strong>${cantidadRequerida - stockTotalEnLotes}</strong> unidad(es).`;
                    } else if (stockTotalEnLotes < cantidadRequerida && contexto === 'entregar_pendiente') {
                         await Swal.fire({ title: 'Stock Insuficiente', html: `No hay suficiente stock para completar el pendiente.<br>Requeridas: <strong>${cantidadRequerida}</strong><br>Disponibles: <strong>${stockTotalEnLotes}</strong>`, icon: 'error', confirmButtonText: 'Entendido' });
                         return;
                    }
                    
                    await Swal.fire({ title: 'Instrucciones de Entrega', html: mensajeHTML, icon: 'info', confirmButtonText: 'Entendido' });
                    
                    let inputsHTML = '';
                    lotesNecesarios.forEach((l, index) => {
                        inputsHTML += `<div class="input-group input-group-sm mb-1"><span class="input-group-text">${l.a_tomar} de</span><input type="text" class="form-control" placeholder="Lote ${l.lote}" data-lote-esperado="${l.lote}" name="lote_manual_${index}"></div>`;
                    });
                    celdaLotes.innerHTML = inputsHTML;
                    celdaAccion.innerHTML = `<button class="btn btn-primary btn-sm btn-validar-lotes-final" disabled><i class="bi-key-fill"></i> Validar Lotes</button>`;

                } else {
                    fila.dataset.stockTotal = 0;
                    if (contexto === 'turno') {
                        await Swal.fire({ 
                            title: 'Sin Stock Válido', 
                            html: 'No hay unidades disponibles para la entrega.<br><small>Esto puede deberse a falta de inventario o a que los lotes existentes están próximos a vencer.</small>', 
                            icon: 'warning', 
                            confirmButtonText: 'Entendido'
                        });
                        
                        fila.dataset.accionPendiente = 'true';
                        fila.classList.add('fila-pendiente-lista');
                        celdaAccion.innerHTML = `<button class="btn btn-warning btn-sm btn-confirmar-accion"><i class="bi bi-file-earmark-plus"></i> Generar Pendiente</button>`;
                        celdaLotes.innerHTML = `<div class="text-center text-muted small">Sin stock válido</div>`;
                        actualizarBotonPendientesLote();
                    } else {
                        Swal.fire({ title: 'Sin Stock Válido', text: 'No hay unidades válidas disponibles para entregar este pendiente.', icon: 'error'});
                    }
                }
            } catch (error) {
                Swal.fire('Error', 'No se pudo verificar el stock.', 'error');
            }
        });

        celdaLotes.addEventListener('input', () => {
            const inputs = Array.from(celdaLotes.querySelectorAll('input'));
            if(inputs.length === 0) return;
            const todosValidos = inputs.every(input => input.value.trim().toLowerCase() === input.dataset.loteEsperado.toLowerCase());
            const btnValidar = celdaAccion.querySelector('.btn-validar-lotes-final');
            if(btnValidar) btnValidar.disabled = !todosValidos;
        });

        celdaAccion.addEventListener('click', async (e) => {
            const boton = e.target.closest('button');
            if (boton) {
                let swalConfig = { title: '¿Confirmar Acción?', icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, confirmar' };
                const stockTotal = parseInt(fila.dataset.stockTotal, 10);
                const cantidadRequerida = parseInt(fila.dataset.cantidadRequerida, 10);

                if(boton.matches('.btn-validar-lotes-final')){
                    if (contexto === 'turno' && stockTotal < cantidadRequerida) {
                        boton.parentElement.innerHTML = `<button class="btn btn-info btn-sm btn-confirmar-accion"><i class="bi bi-box-arrow-right"></i> Entregar y Generar</button>`;
                    } else {
                        boton.parentElement.innerHTML = `<button class="btn btn-success btn-sm btn-confirmar-accion"><i class="bi bi-check-circle-fill"></i> Confirmar Entrega</button>`;
                    }
                    return;
                } 
                
                if (boton.matches('.btn-confirmar-accion')) {
                    if (stockTotal === 0) {
                        swalConfig.text = `Se generará un pendiente por ${cantidadRequerida} unidades.`;
                    } else if (stockTotal < cantidadRequerida) {
                        swalConfig.text = `Se entregarán ${stockTotal} unidades y se generará un pendiente por ${cantidadRequerida - stockTotal}.`;
                    } else {
                        swalConfig.text = `Se entregarán ${cantidadRequerida} unidades en su totalidad.`;
                    }
                }
                
                const result = await Swal.fire(swalConfig);
                if (result.isConfirmed) {
                    procesarEntregaFinal(fila);
                }
            }
        });
    });

    btnFinalizar.addEventListener('click', async function(){
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Finalizando...';
        
        const formData = new FormData();
        let accionFinalizar = '';
        
        if (contexto === 'entregar_pendiente') {
            accionFinalizar = 'finalizar_entrega_pendiente';
            formData.append('accion', accionFinalizar);
            formData.append('id_entrega_pendiente', idEntreгаPendiente);
        } else {
            const primeraFila = cuerpoTabla.querySelector('tr');
            const idTurno = primeraFila ? primeraFila.dataset.idTurno : null;
            accionFinalizar = 'finalizar_turno';
            formData.append('accion', accionFinalizar);
            formData.append('id_turno', idTurno);
        }
        
        try {
            const response = await fetch('/SALUDCONNECT/farma/entregar/ajax_procesar_entrega.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                await Swal.fire('¡Proceso Finalizado!', data.message, 'success');
                const modal = bootstrap.Modal.getInstance(modalElement);
                if(modal) modal.hide();
            } else {
                Swal.fire('Error', data.message, 'error');
                this.disabled = false;
                this.innerHTML = `<i class="bi bi-truck me-2"></i> Finalizar`;
            }
        } catch(error) {
            Swal.fire('Error de Conexión', 'No se pudo finalizar el proceso.', 'error');
            this.disabled = false;
            this.innerHTML = `<i class="bi bi-truck me-2"></i> Finalizar`;
        }
    });
}