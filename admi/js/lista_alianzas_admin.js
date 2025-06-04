document.addEventListener('DOMContentLoaded', function() {
    const modalConfirm = document.getElementById('modalConfirmacionAlianza');
    const tituloModal = document.getElementById('tituloConfirmacionAlianza');
    const mensajeModal = document.getElementById('mensajeModalConfirmacionAlianza');
    const btnConfirmarAccion = document.getElementById('btnConfirmarAccionAlianza');
    const btnCancelarAccion = document.getElementById('btnCancelarAccionAlianza');
    let datosAccionActual = null;

    document.querySelectorAll('.btn-cambiar-estado-alianza').forEach(button => {
        button.addEventListener('click', function() {
            datosAccionActual = {
                id_alianza: this.dataset.idAlianza,
                tabla_origen: this.dataset.tablaOrigen,
                nit_eps: this.dataset.nitEps,
                nit_entidad: this.dataset.nitEntidad,
                accion_alianza: this.dataset.accion, 
                tipoOperacion: 'cambiarEstado'
            };
            const accionTexto = datosAccionActual.accion_alianza === 'activar' ? 'Activar' : 'Inactivar';
            tituloModal.textContent = `${accionTexto} Alianza`;
            mensajeModal.textContent = `¿Está seguro que desea ${datosAccionActual.accion_alianza.toLowerCase()} esta alianza?`;
            btnConfirmarAccion.className = datosAccionActual.accion_alianza === 'activar' ? 'btn btn-success' : 'btn btn-warning';
            btnConfirmarAccion.textContent = accionTexto;
            modalConfirm.style.display = 'flex';
        });
    });

    document.querySelectorAll('.btn-eliminar-alianza').forEach(button => {
        button.addEventListener('click', function() {
            datosAccionActual = {
                id_alianza: this.dataset.idAlianza,
                tabla_origen: this.dataset.tablaOrigen,
                nombre_eps: this.dataset.nombreEps,
                nombre_entidad: this.dataset.nombreEntidad,
                tipoOperacion: 'eliminar'
            };
            tituloModal.textContent = 'Confirmar Eliminación';
            mensajeModal.innerHTML = `¿Está seguro que desea eliminar la alianza entre <strong>${datosAccionActual.nombre_eps}</strong> y <strong>${datosAccionActual.nombre_entidad}</strong> (ID: ${datosAccionActual.id_alianza})? Esta acción es irreversible.`;
            btnConfirmarAccion.className = 'btn btn-danger';
            btnConfirmarAccion.textContent = 'Eliminar';
            modalConfirm.style.display = 'flex';
        });
    });

    btnConfirmarAccion.addEventListener('click', function() {
        if (!datosAccionActual) return;

        const formData = new FormData();
        formData.append('csrf_token', typeof csrfTokenAlianzas !== 'undefined' ? csrfTokenAlianzas : '');
        formData.append('tabla_origen', datosAccionActual.tabla_origen);
        
        let urlFetch = '';

        if (datosAccionActual.tipoOperacion === 'cambiarEstado') {
            formData.append('nit_eps', datosAccionActual.nit_eps);
            formData.append('nit_entidad', datosAccionActual.nit_entidad);
            formData.append('accion_alianza', datosAccionActual.accion_alianza);
            urlFetch = 'cambiar_estado_alianza.php'; 
        } else if (datosAccionActual.tipoOperacion === 'eliminar') {
            formData.append('id_alianza', datosAccionActual.id_alianza); 
            urlFetch = 'eliminar_alianza.php'; 
        }

        if (urlFetch === '') {
            modalConfirm.style.display = 'none';
            datosAccionActual = null;
            return;
        }

        fetch(urlFetch, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.message || 'No se pudo completar la acción.'));
            }
        })
        .catch(error => {
            console.error('Error en fetch:', error);
            alert('Error de comunicación con el servidor.');
        })
        .finally(() => {
            modalConfirm.style.display = 'none';
            datosAccionActual = null;
        });
    });

    btnCancelarAccion.addEventListener('click', () => {
        modalConfirm.style.display = 'none';
        datosAccionActual = null;
    });
    window.addEventListener('click', (event) => {
        if (event.target === modalConfirm) {
            modalConfirm.style.display = 'none';
            datosAccionActual = null;
        }
    });

    const pageContainer = document.getElementById('page-number-container'); 
    if(pageContainer) {
        const pageIS = pageContainer.querySelector('.page-number-display'); 
        const pageIF = pageContainer.querySelector('.page-number-input-field'); 
        if(pageIS && pageIF){ 
            pageIS.addEventListener('click', () => { pageIS.style.display = 'none'; pageIF.style.display = 'inline-block'; pageIF.focus(); pageIF.select(); }); 
            const goPg = () => { const tp = parseInt(pageIF.dataset.total, 10) || 1; let tgPg = parseInt(pageIF.value, 10); if (isNaN(tgPg) || tgPg < 1) tgPg = 1; else if (tgPg > tp) tgPg = tp; const curl = new URL(window.location.href); curl.searchParams.set('pagina', tgPg); window.location.href = curl.toString(); }; 
            const hideInput = () => { const totalPgs = parseInt(pageIF.dataset.total, 10) || 1; const currentPageVal = parseInt(pageIF.value, 10); let displayPage; if (isNaN(currentPageVal) || currentPageVal < 1) { displayPage = pageIS.textContent.split(' / ')[0].trim(); pageIF.value = displayPage; } else if (currentPageVal > totalPgs) { displayPage = totalPgs; pageIF.value = totalPgs; } else { displayPage = pageIF.value; } pageIS.textContent = displayPage + ' / ' + totalPgs; pageIS.style.display = 'inline-block'; pageIF.style.display = 'none'; }; 
            pageIF.addEventListener('blur', () => { setTimeout(hideInput, 150); }); 
            pageIF.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); goPg(); } else if (e.key === 'Escape'){ pageIF.value = pageIS.textContent.split(' / ')[0].trim(); hideInput(); } }); 
        }
    }
});