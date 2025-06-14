document.addEventListener('DOMContentLoaded', function () {
    const alertModal = new bootstrap.Modal(document.getElementById('alertasModal'));
    const modalTitle = document.getElementById('alertasModalLabel');
    const modalBody = document.getElementById('alertasModalBody');
    const modalFooter = document.getElementById('alertasModalFooter');
    const modalSecundarioPlaceholder = document.getElementById('modal-secundario-placeholder');

    let currentAlertType = '';

    document.querySelectorAll('.alert-card[data-bs-toggle="modal"]').forEach(card => {
        card.addEventListener('click', function () {
            currentAlertType = this.dataset.alertType;
            let url = '';
            let title = '';

            modalBody.innerHTML = '<div class="d-flex justify-content-center align-items-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
            modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';

            switch (currentAlertType) {
                case 'por-vencer':
                    url = 'alertas/productos_por_vencer.php';
                    title = '<i class="bi bi-hourglass-split me-2"></i>Productos Próximos a Vencer';
                    break;
                case 'vencidos':
                    url = 'alertas/productos_vencidos.php';
                    title = '<i class="bi bi-calendar-x-fill me-2"></i>Productos Vencidos en Inventario';
                    break;
                case 'stock-bajo':
                    url = 'alertas/stock_bajo.php';
                    title = '<i class="bi bi-box-seam me-2"></i>Medicamentos con Stock Bajo';
                    break;
                default:
                    return;
            }

            modalTitle.innerHTML = title;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        modalBody.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                        return;
                    }
                    if (data.length === 0) {
                        modalBody.innerHTML = '<div class="alert alert-success text-center"><i class="fas fa-check-circle me-2"></i>No hay elementos para mostrar.</div>';
                        return;
                    }

                    let tableHTML = '<div class="table-responsive"><table class="table table-striped table-hover table-sm"><thead><tr>';
                    
                    if (currentAlertType === 'por-vencer') {
                        tableHTML += '<th><i class="bi bi-capsule-pill me-1"></i>Medicamento</th><th><i class="bi bi-tag me-1"></i>Lote</th><th><i class="bi bi-alarm me-1"></i>Vence en</th><th><i class="bi bi-calendar-event me-1"></i>Fecha Venc.</th><th><i class="bi bi-boxes me-1"></i>Stock Lote</th>';
                    } else if (currentAlertType === 'vencidos') {
                        tableHTML += '<th><i class="bi bi-capsule-pill me-1"></i>Medicamento</th><th><i class="bi bi-tag me-1"></i>Lote</th><th><i class="bi bi-calendar-x me-1"></i>Fecha Venc.</th><th><i class="bi bi-boxes me-1"></i>Stock Lote</th>';
                    } else if (currentAlertType === 'stock-bajo') {
                        tableHTML += '<th><i class="bi bi-capsule-pill me-1"></i>Medicamento</th><th><i class="bi bi-upc-scan me-1"></i>Cód. Barras</th><th><i class="bi bi-box-seam me-1"></i>Stock Actual</th>';
                    }
                    tableHTML += '</tr></thead><tbody>';

                    data.forEach(item => {
                        tableHTML += '<tr>';
                        if (currentAlertType === 'por-vencer') {
                            tableHTML += `<td>${item.nom_medicamento}</td><td>${item.lote}</td><td><span class="badge bg-warning text-dark">${item.dias_restantes} días</span></td><td>${item.fecha_vencimiento}</td><td>${item.stock_lote}</td>`;
                        } else if (currentAlertType === 'vencidos') {
                            tableHTML += `<td>${item.nom_medicamento}</td><td>${item.lote}</td><td><span class="badge bg-danger">${item.fecha_vencimiento}</span></td><td>${item.stock_lote}</td>`;
                        } else if (currentAlertType === 'stock-bajo') {
                            tableHTML += `<td>${item.nom_medicamento}</td><td>${item.codigo_barras || 'N/A'}</td><td><span class="badge bg-danger">${item.cantidad_actual}</span></td>`;
                        }
                        tableHTML += '</tr>';
                    });

                    tableHTML += '</tbody></table></div>';
                    modalBody.innerHTML = tableHTML;
                    
                    if (currentAlertType === 'vencidos' && data.length > 0) {
                         modalFooter.innerHTML = '<button type="button" class="btn btn-danger" id="btn-abrir-modal-retiro"><i class="bi bi-trash me-2"></i>Retirar Vencidos</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';
                    }
                     if (currentAlertType === 'stock-bajo') {
                        modalFooter.innerHTML = '<a href="inventario/insertar_inventario.php" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>Ingresar Medicamentos</a><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';
                    }
                })
                .catch(error => {
                    modalBody.innerHTML = `<div class="alert alert-danger">Error al cargar los datos: ${error}</div>`;
                });
        });
    });

    modalFooter.addEventListener('click', function(e) {
        if (e.target.id === 'btn-abrir-modal-retiro') {
            fetch('alertas/modal_retirar_vencidos.php')
                .then(response => response.text())
                .then(html => {
                    modalSecundarioPlaceholder.innerHTML = html;
                    const modalRetiroElement = document.getElementById('modalRetiroVencidos');
                    const modalRetiro = new bootstrap.Modal(modalRetiroElement);
                    alertModal.hide();
                    modalRetiro.show();
                });
        }
    });

    let retiroIniciado = false;
    modalSecundarioPlaceholder.addEventListener('click', async function(e) {
        const botonClicado = e.target.closest('button');
        if (!botonClicado) return;

        const fila = botonClicado.closest('tr');
        if (!fila) return;

        if (botonClicado.classList.contains('btn-validar-codigo')) {
            const inputCodigo = fila.querySelector('.input-codigo-barras');
            if (inputCodigo.value.trim() === fila.dataset.codigoBarras) {
                inputCodigo.disabled = true;
                botonClicado.disabled = true;
                inputCodigo.classList.remove('is-invalid');
                inputCodigo.classList.add('is-valid');
                botonClicado.classList.add('btn-success');
                const inputLote = fila.querySelector('.input-lote');
                const btnValidarLote = fila.querySelector('.btn-validar-lote');
                inputLote.disabled = false;
                btnValidarLote.disabled = false;
                inputLote.focus();
            } else {
                Swal.fire('Error', 'El código de barras no corresponde al medicamento.', 'error');
                inputCodigo.classList.add('is-invalid');
            }
        }

        if (botonClicado.classList.contains('btn-validar-lote')) {
            const inputLote = fila.querySelector('.input-lote');
            if (inputLote.value.trim().toLowerCase() === fila.dataset.lote.toLowerCase()) {
                inputLote.disabled = true;
                botonClicado.disabled = true;
                inputLote.classList.remove('is-invalid');
                inputLote.classList.add('is-valid');
                botonClicado.classList.add('btn-success');
                fila.querySelector('.btn-retirar-lote').disabled = false;
                await verificarEstadoGlobalRetiro();
            } else {
                Swal.fire('Error', 'El número de lote no coincide.', 'error');
                inputLote.classList.add('is-invalid');
            }
        }

        if (botonClicado.classList.contains('btn-retirar-lote')) {
            await procesarRetiro(fila, botonClicado);
            await verificarEstadoGlobalRetiro();
        }
    });

    async function verificarEstadoGlobalRetiro() {
        const modalRetiro = document.getElementById('modalRetiroVencidos');
        if (!modalRetiro) return;
        
        const filas = modalRetiro.querySelectorAll('#tabla-retiro-vencidos tr');
        const todasRetiradas = [...filas].every(f => f.classList.contains('fila-retirada'));
        
        if (retiroIniciado && todasRetiradas) {
            let resumenHtml = '<p>Se han retirado los siguientes lotes del inventario:</p>';
            resumenHtml += '<table class="table table-bordered table-sm text-start"><thead><tr><th>Medicamento</th><th>Lote</th><th>Cant.</th></tr></thead><tbody>';

            filas.forEach(fila => {
                const medicamento = fila.querySelector('td:first-child strong').textContent;
                const lote = fila.dataset.lote;
                const cantidad = fila.dataset.cantidad;
                resumenHtml += `<tr><td>${medicamento}</td><td>${lote}</td><td>${cantidad}</td></tr>`;
            });
            
            resumenHtml += '</tbody></table><p class="mt-3">Recuerda gestionar activamente los productos para darles salida antes de su fecha de vencimiento. ¡Más cuidado a la próxima!</p>';
            
            const modalRetiroBootstrap = bootstrap.Modal.getInstance(modalRetiro);
            if (modalRetiroBootstrap) {
                 modalRetiroBootstrap.hide();
            }

            await Swal.fire({
                icon: 'success',
                title: '¡Proceso Completado!',
                html: resumenHtml,
                confirmButtonText: 'OK'
            });

            window.location.reload();
        }
    }

    async function procesarRetiro(fila, boton) {
        if (boton.disabled && !fila.classList.contains('fila-retirada')) {
            return;
        }
        
        boton.disabled = true;
        boton.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        
        const formData = new FormData();
        formData.append('id_medicamento', fila.dataset.idMedicamento);
        formData.append('lote', fila.dataset.lote);
        formData.append('cantidad', fila.dataset.cantidad);

        try {
            const response = await fetch('alertas/ajax_retirar_vencido.php', { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success) {
                if (!retiroIniciado) {
                    const modalElement = document.getElementById('modalRetiroVencidos');
                    modalElement.querySelector('.btn-close').disabled = true;
                    retiroIniciado = true;
                }
                fila.classList.add('fila-retirada', 'table-success');
                fila.querySelectorAll('input, button').forEach(el => el.disabled = true);
                boton.innerHTML = '<i class="bi bi-check-circle-fill"></i> Retirado';
            } else {
                Swal.fire('Error', data.message, 'error');
                boton.disabled = false;
                boton.innerHTML = '<i class="bi bi-box-arrow-right"></i> Retirar';
            }
        } catch (error) {
             Swal.fire('Error de Conexión', 'No se pudo procesar la solicitud.', 'error');
             boton.disabled = false;
             boton.innerHTML = '<i class="bi bi-box-arrow-right"></i> Retirar';
        }
    }
});