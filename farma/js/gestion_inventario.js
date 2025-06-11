document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('inventario-tbody');
    const formFiltros = document.getElementById('formFiltros');
    const filtroCodigoBarrasInput = document.getElementById('filtro_codigo_barras');
    let scanBuffer = '';
    let lastKeyTime = Date.now();
    let debounceTimer;

    function renderBarcodes() {
        try {
            JsBarcode(".barcode").init();
        } catch (e) { console.error("Error al renderizar códigos de barras en la tabla:", e); }
    }
    
    function searchWithFilters() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const formData = new FormData(formFiltros);
            const params = new URLSearchParams(formData).toString();
            const currentPage = document.querySelector('.page-number-display') ? document.querySelector('.page-number-display').textContent.split(' ')[0] : 1;
            
            fetch(`inventario.php?ajax_search=1&pagina=${currentPage}&${params}`)
                .then(response => response.text())
                .then(html => {
                    tableBody.innerHTML = html;
                    renderBarcodes();
                })
                .catch(error => console.error('Error en la búsqueda:', error));
        }, 300);
    }
    
    renderBarcodes();

    document.addEventListener('keypress', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') { return; }
        const currentTime = Date.now();
        if (currentTime - lastKeyTime > 100) { scanBuffer = ''; }
        lastKeyTime = currentTime;
        if (e.key === 'Enter') {
            if (scanBuffer.length > 3) {
                e.preventDefault();
                filtroCodigoBarrasInput.value = scanBuffer;
                searchWithFilters();
            }
            scanBuffer = '';
        } else {
            scanBuffer += e.key;
        }
    });
    if(formFiltros) {
        formFiltros.addEventListener('input', function(e) { searchWithFilters(); });
        formFiltros.addEventListener('submit', e => e.preventDefault());
    }

    // --- MODAL 'VER DETALLES' CON RUTA CORREGIDA ---
    const modalDetalles = document.getElementById('modalDetallesMedicamento');
    if (modalDetalles) {
        modalDetalles.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const idMedicamento = button.getAttribute('data-id-medicamento');
            const modalBody = modalDetalles.querySelector('#contenidoModalDetalles');

            modalBody.innerHTML = `<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>`;
         
            fetch(`../inventario/detalles_medicamento.php?id=${idMedicamento}`)
                .then(response => {
                    if (!response.ok) { throw new Error(`Error HTTP! estado: ${response.status}`); }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const med = data.medicamento;
                        let clase_badge = 'bg-secondary';
                        if (med.id_est == 13) clase_badge = 'bg-success';
                        if (med.id_est == 14) clase_badge = 'bg-warning text-dark';
                        if (med.id_est == 15) clase_badge = 'bg-danger';
                        let barcodeHTML = med.codigo_barras ? `<svg class="barcode-detail" jsbarcode-value="${med.codigo_barras}"></svg>` : 'No disponible';
                        const contenidoHTML = `<div class="row"><div class="col-md-8"><dl class="row"><dt class="col-sm-4">Nombre</dt><dd class="col-sm-8">${med.nom_medicamento||'N/A'}</dd><dt class="col-sm-4">Tipo</dt><dd class="col-sm-8">${med.nom_tipo_medi||'N/A'}</dd><dt class="col-sm-4">Descripción</dt><dd class="col-sm-8">${med.descripcion||'Sin descripción.'}</dd><dt class="col-sm-4">Cantidad</dt><dd class="col-sm-8"><strong>${med.cantidad_actual!==null?med.cantidad_actual:'N/A'}</strong></dd><dt class="col-sm-4">Estado</dt><dd class="col-sm-8"><span class="badge ${clase_badge}">${med.nom_est||'N/A'}</span></dd></dl></div><div class="col-md-4 text-center"><strong>Código Barras</strong><div class="mt-2 p-2 border rounded bg-light" style="min-height: 80px; display: flex; align-items: center; justify-content: center;">${barcodeHTML}</div></div></div>`;
                        modalBody.innerHTML = contenidoHTML;
                        if (med.codigo_barras) { JsBarcode(".barcode-detail").init(); }
                    } else {
                        modalBody.innerHTML = `<div class="alert alert-danger">${data.message || 'No se pudo cargar la información.'}</div>`;
                    }
                })
                .catch(error => {
                    modalBody.innerHTML = `<div class="alert alert-danger">Error de conexión. Revise la consola.</div>`;
                    console.error('Error en fetch:', error);
                });
        });
    }

    // --- MODAL 'EDITAR' CON RUTAS CORREGIDAS ---
    const modalEditarElement = document.getElementById('modalEditarMedicamento');
    let originalEditData = {};
    let modalEditar;
    let currentEditingId = null;
    if (modalEditarElement) {
        modalEditar = new bootstrap.Modal(modalEditarElement);
    }
    
    function validateField(inputElement) {
        let isValid = false;
        if (inputElement.id === 'edit_nom_medicamento') isValid = inputElement.value.trim().length > 4;
        else if (inputElement.id === 'edit_id_tipo_medic') isValid = inputElement.value !== '' && inputElement.value !== '0';
        else if (inputElement.id === 'edit_descripcion') isValid = inputElement.value.trim() === '' || inputElement.value.trim().length >= 10;
        else isValid = true;
        inputElement.classList.remove('is-valid', 'is-invalid');
        inputElement.classList.add(isValid ? 'is-valid' : 'is-invalid');
        checkFormState();
    }
    function checkFormState() {
        const form = document.getElementById('formEditarMedicamento');
        if (!form) return;
        const nombreInput = form.querySelector('#edit_nom_medicamento');
        const tipoSelect = form.querySelector('#edit_id_tipo_medic');
        const descripcionInput = form.querySelector('#edit_descripcion');
        const submitButton = form.querySelector('button[type="submit"]');
        let isNameValid = nombreInput && nombreInput.value.trim().length > 4;
        let isTypeValid = tipoSelect && tipoSelect.value !== '' && tipoSelect.value !== '0';
        let isDescValid = descripcionInput && (descripcionInput.value.trim() === '' || descripcionInput.value.trim().length >= 10);
        const isFormValid = isNameValid && isTypeValid && isDescValid;
        const isDataChanged = ['nom_medicamento', 'id_tipo_medic', 'descripcion', 'codigo_barras'].some(key => {
            const el = form.querySelector(`#edit_${key}`);
            return el && el.value !== originalEditData[key];
        });
        if(submitButton) submitButton.disabled = !(isFormValid && isDataChanged);
    }

    if (modalEditarElement) {
        const cuerpoModal = document.getElementById('cuerpoModalEditar');
        const form = document.getElementById('formEditarMedicamento');
        modalEditarElement.addEventListener('show.bs.modal', function(event) {
            if (event.relatedTarget) { currentEditingId = event.relatedTarget.getAttribute('data-id-medicamento'); }
            form.querySelector('button[type="submit"]').disabled = true;
            cuerpoModal.innerHTML = `<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>`;
            document.getElementById('edit_id_medicamento').value = currentEditingId;
            
            fetch(`../inventario/modal_editar.php?id=${currentEditingId}`).then(r => r.text()).then(html => {
                cuerpoModal.innerHTML = html;
                originalEditData = {
                    nom_medicamento: cuerpoModal.querySelector('#edit_nom_medicamento').value,
                    id_tipo_medic: cuerpoModal.querySelector('#edit_id_tipo_medic').value,
                    descripcion: cuerpoModal.querySelector('#edit_descripcion').value,
                    codigo_barras: cuerpoModal.querySelector('#edit_codigo_barras').value
                };
                ['edit_nom_medicamento', 'edit_id_tipo_medic', 'edit_descripcion', 'edit_codigo_barras'].forEach(id => {
                    const el = document.getElementById(id);
                    if(el) el.addEventListener('input', (e) => validateField(e.target));
                });
                checkFormState();
            });
        });
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            const formData = new FormData(this);
            const nombreMedicamento = formData.get('nom_medicamento');
             
            fetch('../inventario/ajax_actualizar_medicamento.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    modalEditar.hide();
                    if(data.success) {
                        Swal.fire({ title: '¡Actualizado!', text: data.message, icon: 'success', timer: 2000, showConfirmButton: false }).then(() => { searchWithFilters(); });
                    } else { 
                        Swal.fire({ title: 'Error al Guardar', text: data.message, icon: 'error' }).then(() => {
                            modalEditar.show();
                            if(data.message.includes('código de barras')){
                                const codigoInput = form.querySelector('#edit_codigo_barras');
                                if(codigoInput) { codigoInput.classList.add('is-invalid'); codigoInput.focus(); }
                            } else if(data.message.includes('nombre')) {
                                const nombreInput = form.querySelector('#edit_nom_medicamento');
                                 if(nombreInput) { nombreInput.classList.add('is-invalid'); nombreInput.focus(); }
                            }
                        });
                        submitButton.disabled = false;
                    }
                });
        });
    }
});