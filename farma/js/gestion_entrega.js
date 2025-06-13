function inicializarLogicaEntrega(modalElement, contexto = 'turno') {
    const cuerpoTabla = modalElement.querySelector('#cuerpo-tabla-entrega');
    const btnFinalizar = modalElement.querySelector('#btn-finalizar-entrega-completa');
    const btnCancelar = modalElement.querySelector('#btn-cancelar-entrega');
    const btnCerrarModal = modalElement.querySelector('.modal-header .btn-close');

    if (contexto === 'pendiente') {
        btnFinalizar.textContent = 'Finalizar Entrega Pendiente';
    }

    const deshabilitarCierreModal = () => {
        btnCancelar.disabled = true;
        btnCerrarModal.disabled = true;
        modalElement.setAttribute('data-bs-keyboard', 'false');
        modalElement.setAttribute('data-bs-backdrop', 'static');
    };

    const verificarSiFinalizo = () => {
        const totalFilas = cuerpoTabla.querySelectorAll('tr').length;
        const filasProcesadas = cuerpoTabla.querySelectorAll('tr[data-estado="procesado"]').length;
        if (totalFilas > 0 && totalFilas === filasProcesadas) {
            btnFinalizar.disabled = false;
        }
    };

    const procesarEntregaFinal = async (fila) => {
        deshabilitarCierreModal();
        const celdaAccion = fila.querySelector('.celda-accion');
        celdaAccion.innerHTML = '<div class="spinner-border spinner-border-sm text-primary"></div>';
        
        const accion_a_realizar = (contexto === 'pendiente') ? 'entregar_pendiente' : 'validar_y_entregar';
        const formData = new FormData();
        formData.append('accion', accion_a_realizar);
        formData.append('id_detalle', fila.dataset.idDetalle);

        try {
            const response = await fetch('/SALUDCONNECT/farma/entregar/ajax_procesar_entrega.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                fila.dataset.estado = "procesado";
                fila.classList.add('fila-procesada');
                fila.querySelector('.estado-verificacion i').className = 'bi bi-check-all text-success fs-4';
                
                let resumenHTML = '';
                if (data.entregas && data.entregas.length > 0) {
                    resumenHTML += '<ul class="list-unstyled mb-0 small text-start">';
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
            }
        } catch (error) {
            Swal.fire('Error de Conexión', 'No se pudo comunicar con el servidor.', 'error');
        }
    };
    
    cuerpoTabla.querySelectorAll('tr').forEach(fila => {
        const btnVerificarCodigo = fila.querySelector('.btn-verificar-codigo');
        const btnConfirmarLote = fila.querySelector('.btn-confirmar-lote');
        const inputCodigo = fila.querySelector('input[name="codigo_barras_verif"]');
        const inputLote = fila.querySelector('input[name="lote_manual"]');
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
                    const stockTotal = data.lotes.reduce((acc, lote) => acc + parseInt(lote.stock_lote, 10), 0);
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
                    fila.dataset.stockTotal = stockTotal;

                    let mensajeHTML = '';
                    lotesNecesarios.forEach(l => {
                        mensajeHTML += `Tome <strong>${l.a_tomar}</strong> unidad(es) del Lote: <strong>${l.lote}</strong><br>`;
                    });
                    if (stockTotal < cantidadRequerida && contexto === 'turno') {
                        mensajeHTML += `<hr>Se generará un pendiente por <strong>${cantidadRequerida - stockTotal}</strong> unidad(es).`;
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
                         await Swal.fire({ title: 'Sin Stock', text: 'No hay unidades disponibles. Se debe generar un pendiente.', icon: 'error', confirmButtonText: 'Entendido'});
                         celdaAccion.innerHTML = `<button class="btn btn-warning btn-sm btn-confirmar-accion"><i class="bi bi-file-earmark-plus"></i> Generar Pendiente</button>`;
                    } else {
                        Swal.fire({ title: 'Sin Stock', text: 'No hay unidades disponibles para entregar este pendiente.', icon: 'error'});
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
        const primeraFila = cuerpoTabla.querySelector('tr');
        const idTurno = primeraFila.dataset.idTurno;
        const formData = new FormData();
        formData.append('accion', 'finalizar_turno');
        formData.append('id_turno', idTurno);
        formData.append('contexto', contexto);
        
        try {
            const response = await fetch('/SALUDCONNECT/farma/entregar/ajax_procesar_entrega.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                Swal.fire('¡Proceso Finalizado!', data.message, 'success').then(() => {
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if(modal) modal.hide();
                    location.reload();
                });
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