document.addEventListener('DOMContentLoaded', function () {
    // --- BLOQUE 1: INICIALIZACIÓN DE ELEMENTOS DEL MODAL ---
    // Se seleccionan todos los elementos necesarios del DOM para los modales.
    // Si el modal principal no existe en la página, el script se detiene para evitar errores.
    const alertasModalElement = document.getElementById('alertasModal');
    if (!alertasModalElement) return;

    const alertModal = new bootstrap.Modal(alertasModalElement);
    const modalTitle = document.getElementById('alertasModalLabel');
    const modalBody = document.getElementById('alertasModalBody');
    const modalFooter = document.getElementById('alertasModalFooter');
    const modalSecundarioPlaceholder = document.getElementById('modal-secundario-placeholder');

    let currentAlertType = '';

    // --- BLOQUE 2: EVENTO PARA ABRIR EL MODAL PRINCIPAL DE ALERTAS ---
    // Se añade un 'listener' a todas las tarjetas que abren el modal de alertas.
    document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target="#alertasModal"]').forEach(card => {
        card.addEventListener('click', function () {
            currentAlertType = this.dataset.alertType;
            let url = '';
            let title = '';

            // Muestra un spinner de carga mientras se obtienen los datos.
            modalBody.innerHTML = '<div class="d-flex justify-content-center align-items-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
            modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';

            // --- RUTA CORREGIDA Y ÚNICA ---
            // Se construye la URL del endpoint AJAX usando AppConfig.BASE_URL.
            // Esto asegura que la ruta siempre sea correcta, sin importar desde dónde se llame al script.
            const ajaxBasePath = `${AppConfig.BASE_URL}/farma/alertas/`;

            switch (currentAlertType) {
                case 'por-vencer':
                    url = `${ajaxBasePath}productos_por_vencer.php`;
                    title = '<i class="bi bi-hourglass-split me-2"></i>Productos Próximos a Vencer';
                    break;
                case 'vencidos':
                    url = `${ajaxBasePath}productos_vencidos.php`;
                    title = '<i class="bi bi-calendar-x-fill me-2"></i>Productos Vencidos en Inventario';
                    break;
                case 'stock-bajo':
                    url = `${ajaxBasePath}stock_bajo.php`;
                    title = '<i class="bi bi-box-seam me-2"></i>Medicamentos con Stock Bajo';
                    break;
                default: return;
            }

            modalTitle.innerHTML = title;

            // --- BLOQUE 3: PETICIÓN FETCH PARA CARGAR DATOS DE LA ALERTA ---
            // Se realiza la llamada al archivo PHP correspondiente para obtener los datos de la alerta.
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // Si la respuesta contiene un error, se muestra.
                    if (data.error) {
                        modalBody.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                        return;
                    }
                    // Si no hay datos, se muestra un mensaje de éxito.
                    if (data.length === 0) {
                        modalBody.innerHTML = '<div class="alert alert-success text-center"><i class="fas fa-check-circle me-2"></i>No hay elementos para mostrar.</div>';
                        return;
                    }

                    // Se construye la tabla HTML con los datos recibidos.
                    let tableHTML = '<div class="table-responsive"><table class="table table-striped table-hover table-sm"><thead><tr>';
                    
                    // Se definen las cabeceras de la tabla según el tipo de alerta.
                    if (currentAlertType === 'por-vencer') {
                        tableHTML += '<th><i class="bi bi-capsule-pill"></i> Medicamento</th><th><i class="bi bi-tag"></i> Lote</th><th><i class="bi bi-alarm"></i> Vence en</th><th><i class="bi bi-calendar-event"></i> Fecha Venc.</th><th><i class="bi bi-boxes"></i> Stock Lote</th>';
                    } else if (currentAlertType === 'vencidos') {
                        tableHTML += '<th><i class="bi bi-capsule-pill"></i> Medicamento</th><th><i class="bi bi-tag"></i> Lote</th><th><i class="bi bi-calendar-x"></i> Fecha Venc.</th><th><i class="bi bi-boxes"></i> Stock Lote</th>';
                    } else if (currentAlertType === 'stock-bajo') {
                        tableHTML += '<th><i class="bi bi-capsule-pill"></i> Medicamento</th><th><i class="bi bi-upc-scan"></i> Cód. Barras</th><th><i class="bi bi-box-seam"></i> Stock Actual</th>';
                    }
                    tableHTML += '</tr></thead><tbody>';

                    // Se itera sobre los datos para crear las filas de la tabla.
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
                    
                    // --- RUTA CORREGIDA Y ÚNICA ---
                    const basePathRoot = `${AppConfig.BASE_URL}/farma/`;
                    
                    // Se actualiza el pie del modal con botones de acción relevantes.
                    if (currentAlertType === 'vencidos' && data.length > 0) {
                         modalFooter.innerHTML = `<button type="button" class="btn btn-danger" id="btn-abrir-modal-retiro" data-tipo="vencidos"><i class="bi bi-trash me-2"></i>Retirar Vencidos</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>`;
                    } else if (currentAlertType === 'por-vencer' && data.length > 0) {
                        modalFooter.innerHTML = `<button type="button" class="btn btn-warning" id="btn-abrir-modal-retiro" data-tipo="por_vencer"><i class="bi bi-shield-slash me-2"></i>Gestionar Retiro</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>`;
                    } else if (currentAlertType === 'stock-bajo') {
                        modalFooter.innerHTML = `<a href="${basePathRoot}inventario/insertar_inventario.php" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>Ingresar Medicamentos</a><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>`;
                    }
                })
                .catch(error => {
                    modalBody.innerHTML = `<div class="alert alert-danger">Error al cargar los datos: ${error}</div>`;
                });
        });
    });

    // --- BLOQUE 4: LÓGICA PARA ABRIR EL MODAL SECUNDARIO DE RETIRO ---
    modalFooter.addEventListener('click', function(e) {
        if (e.target.id === 'btn-abrir-modal-retiro') {
            const tipo = e.target.dataset.tipo;
            // --- RUTA CORREGIDA Y ÚNICA ---
            const modalRetiroUrl = `${AppConfig.BASE_URL}/farma/alertas/modal_retirar_inventario.php?tipo=${tipo}`;
            
            fetch(modalRetiroUrl)
                .then(response => response.text())
                .then(html => {
                    modalSecundarioPlaceholder.innerHTML = html;
                    const modalRetiroElement = document.getElementById('modalRetiroInventario');
                    const modalRetiro = new bootstrap.Modal(modalRetiroElement);
                    alertModal.hide();
                    modalRetiro.show();

                    // Se añade un listener para recargar la página cuando se cierra el modal de retiro, si se hizo algún cambio.
                    modalRetiroElement.addEventListener('hidden.bs.modal', function () {
                        if (retiroIniciado) {
                           window.location.reload();
                        }
                    }, { once: true });
                });
        }
    });

    // --- BLOQUE 5: LÓGICA DE VALIDACIÓN Y RETIRO DE LOTES EN EL MODAL SECUNDARIO ---
    let retiroIniciado = false;
    modalSecundarioPlaceholder.addEventListener('click', async function(e) {
        const botonClicado = e.target.closest('button');
        if (!botonClicado) return;

        const fila = botonClicado.closest('tr');
        if (!fila) return;

        // Lógica para validar el código de barras
        if (botonClicado.classList.contains('btn-validar-codigo')) {
            const inputCodigo = fila.querySelector('.input-codigo-barras');
            inputCodigo.classList.remove('is-valid', 'is-invalid');

            if (inputCodigo.value.trim() === fila.dataset.codigoBarras) {
                inputCodigo.classList.add('is-valid');
                inputCodigo.disabled = true;
                botonClicado.disabled = true;
                
                const inputLote = fila.querySelector('.input-lote');
                const btnValidarLote = fila.querySelector('.btn-validar-lote');
                inputLote.disabled = false;
                btnValidarLote.disabled = false;
                inputLote.focus();
            } else {
                inputCodigo.classList.add('is-invalid');
                Swal.fire('Error de Validación', 'Incorrecto. El código de barras no pertenece a este medicamento.', 'error');
            }
        }

        // Lógica para validar el lote
        if (botonClicado.classList.contains('btn-validar-lote')) {
            const inputLote = fila.querySelector('.input-lote');
            inputLote.classList.remove('is-valid', 'is-invalid');

            if (inputLote.value.trim().toLowerCase() === fila.dataset.lote.toLowerCase()) {
                inputLote.classList.add('is-valid');
                inputLote.disabled = true;
                botonClicado.disabled = true;
                
                const btnRetirar = fila.querySelector('.btn-retirar-lote');
                btnRetirar.disabled = false;
                const motivo = fila.dataset.motivoRetiro;
                btnRetirar.classList.add(motivo === 'vencido' ? 'btn-danger' : 'btn-warning');

            } else {
                inputLote.classList.add('is-invalid');
                Swal.fire('Error de Validación', 'Incorrecto. El número de lote no corresponde al que se debe retirar.', 'error');
            }
        }

        // Lógica para procesar el retiro del lote
        if (botonClicado.classList.contains('btn-retirar-lote')) {
            await procesarRetiro(fila, botonClicado);
        }
    });
    
    // Función asíncrona que envía la petición para retirar el lote
    async function procesarRetiro(fila, boton) {
        boton.disabled = true;
        boton.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        
        const formData = new FormData();
        formData.append('id_medicamento', fila.dataset.idMedicamento);
        formData.append('lote', fila.dataset.lote);
        formData.append('cantidad', fila.dataset.cantidad);
        formData.append('motivo', fila.dataset.motivoRetiro);
        
        // --- RUTA CORREGIDA Y ÚNICA ---
        const urlRetiro = `${AppConfig.BASE_URL}/farma/alertas/ajax_retirar_inventario.php`;

        try {
            const response = await fetch(urlRetiro, { method: 'POST', body: formData });
            const data = await response.json();
            
            if (data.success) {
                // Marca la fila como retirada exitosamente y deshabilita los controles
                if (!retiroIniciado) {
                    const modalElement = document.getElementById('modalRetiroInventario');
                    modalElement.querySelector('.btn-close').disabled = true;
                    modalElement.querySelector('.btn-cerrar-retiro').textContent = 'Finalizar';
                    retiroIniciado = true;
                }
                const motivo = fila.dataset.motivoRetiro;
                fila.classList.remove('table-danger-light', 'table-warning-light');
                fila.classList.add(motivo === 'vencido' ? 'table-success' : 'table-primary-light');
                fila.querySelectorAll('input, button').forEach(el => el.disabled = true);
                fila.querySelector('td:last-child').innerHTML = `<span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Retirado</span>`;
            } else {
                // Si la API devuelve un error, se muestra y se reactiva el botón
                Swal.fire('Error', data.message, 'error');
                boton.disabled = false;
                boton.innerHTML = '<i class="bi bi-box-arrow-right"></i> Retirar';
            }
        } catch (error) {
             // Si hay un error de red, se notifica y se reactiva el botón
             Swal.fire('Error de Conexión', 'No se pudo procesar la solicitud.', 'error');
             boton.disabled = false;
             boton.innerHTML = '<i class="bi bi-box-arrow-right"></i> Retirar';
        }
    }
});