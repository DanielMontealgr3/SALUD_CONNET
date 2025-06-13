document.addEventListener('DOMContentLoaded', function () {
    const alertModal = new bootstrap.Modal(document.getElementById('alertasModal'));
    const modalTitle = document.getElementById('alertasModalLabel');
    const modalBody = document.getElementById('alertasModalBody');
    const modalFooter = document.getElementById('alertasModalFooter');

    document.querySelectorAll('.alert-card[data-bs-toggle="modal"]').forEach(card => {
        card.addEventListener('click', function () {
            const alertType = this.dataset.alertType;
            let url = '';
            let title = '';

            modalBody.innerHTML = '<div class="d-flex justify-content-center align-items-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
            modalFooter.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';

            switch (alertType) {
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

            modalTitle.innerHTML = title; // Usamos .innerHTML para que se renderice el icono

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
                    
                    // --- SECCIÓN CON ICONOS AÑADIDOS ---
                    if (alertType === 'por-vencer') {
                        tableHTML += '<th><i class="bi bi-capsule-pill me-1"></i>Medicamento</th><th><i class="bi bi-tag me-1"></i>Lote</th><th><i class="bi bi-alarm me-1"></i>Vence en</th><th><i class="bi bi-calendar-event me-1"></i>Fecha Venc.</th><th><i class="bi bi-boxes me-1"></i>Stock Lote</th>';
                    } else if (alertType === 'vencidos') {
                        tableHTML += '<th><i class="bi bi-capsule-pill me-1"></i>Medicamento</th><th><i class="bi bi-tag me-1"></i>Lote</th><th><i class="bi bi-calendar-x me-1"></i>Fecha Venc.</th><th><i class="bi bi-boxes me-1"></i>Stock Lote</th>';
                    } else if (alertType === 'stock-bajo') {
                        tableHTML += '<th><i class="bi bi-capsule-pill me-1"></i>Medicamento</th><th><i class="bi bi-upc-scan me-1"></i>Cód. Barras</th><th><i class="bi bi-box-seam me-1"></i>Stock Actual</th>';
                    }
                    tableHTML += '</tr></thead><tbody>';

                    data.forEach(item => {
                        tableHTML += '<tr>';
                        // --- SECCIÓN CON CORRECCIÓN DEL NOMBRE DE LA PROPIEDAD ---
                        if (alertType === 'por-vencer') {
                            tableHTML += `<td>${item.nom_medicamento}</td><td>${item.lote}</td><td><span class="badge bg-warning text-dark">${item.dias_restantes} días</span></td><td>${item.fecha_vencimiento}</td><td>${item.stock_lote}</td>`;
                        } else if (alertType === 'vencidos') {
                            tableHTML += `<td>${item.nom_medicamento}</td><td>${item.lote}</td><td><span class="badge bg-danger">${item.fecha_vencimiento}</span></td><td>${item.stock_lote}</td>`;
                        } else if (alertType === 'stock-bajo') {
                            // CORREGIDO: de 'nombre_medicamento' a 'nom_medicamento'
                            tableHTML += `<td>${item.nom_medicamento}</td><td>${item.codigo_barras || 'N/A'}</td><td><span class="badge bg-danger">${item.cantidad_actual}</span></td>`;
                        }
                        tableHTML += '</tr>';
                    });

                    tableHTML += '</tbody></table></div>';
                    modalBody.innerHTML = tableHTML;
                    
                    if (alertType === 'vencidos') {
                         modalFooter.innerHTML = '<button type="button" class="btn btn-danger"><i class="bi bi-trash me-2"></i>Retirar Vencidos</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';
                    }
                     if (alertType === 'stock-bajo') {
                        modalFooter.innerHTML = '<a href="inventario/insertar_inventario.php" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>Ingresar Medicamentos</a><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>';
                    }

                })
                .catch(error => {
                    modalBody.innerHTML = `<div class="alert alert-danger">Error al cargar los datos: ${error}</div>`;
                });
        });
    });
});