document.addEventListener('DOMContentLoaded', function() {
    // --- 1. SELECCIÓN DE ELEMENTOS Y CONFIGURACIÓN ---
    const formFiltros = document.getElementById('formFiltros');
    const searchInput = document.getElementById('searchInput');
    const tablaContenedor = document.getElementById('contenedor-tabla');
    const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');
    
    const modalDetallesElement = document.getElementById('modalDetallesMedicamento');
    const modalDetalles = modalDetallesElement ? new bootstrap.Modal(modalDetallesElement) : null;
    const modalBodyDetalles = document.getElementById('cuerpoModalDetalles');
    
    const modalEditarElement = document.getElementById('modalEditarMedicamento');
    const modalEditar = modalEditarElement ? new bootstrap.Modal(modalEditarElement) : null;
    const formEditar = document.getElementById('formEditarMedicamento');
    const modalBodyEditar = document.getElementById('cuerpoModalEditar');
    const btnGuardar = document.getElementById('btnGuardarCambios');
    
    // MEJORA: Se obtienen las rutas y el token CSRF del DOM.
    // Asegúrate de que esta ruta base y el nombre del token coincidan con los de tu HTML.
    const API_BASE_URL = document.querySelector('meta[name="api-base-url"]')?.getAttribute('content') || '/SALUDCONNECT/admin/medicamentos/';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    let debounceTimer;
    let scanBuffer = '';
    let lastKeyTime = Date.now();
    let originalData = {};

    // --- 2. FUNCIONES AUXILIARES ---

    function inicializarCodigosDeBarras() {
        try {
            JsBarcode(".barcode").init();
        } catch (e) {
            // Es seguro ignorar este error si no hay códigos de barras para renderizar.
        }
    }

    function cargarContenido(page = 1) {
        if (!formFiltros || !tablaContenedor) return;

        const formData = new FormData(formFiltros);
        const params = new URLSearchParams(formData);
        params.append('ajax', '1');
        params.append('pagina', page);
        
        // MEJORA: La URL de la API es ahora dinámica.
        const url = `${API_BASE_URL}ver_medicamento.php?${params.toString()}`;

        fetch(url)
            .then(response => response.text())
            .then(html => {
                tablaContenedor.innerHTML = html;
                inicializarCodigosDeBarras();
            })
            .catch(error => console.error('Error al cargar la tabla:', error));
    }

    function validarYChequearCambios(inputEspecifico = null) {
        if (!modalBodyEditar?.querySelector('#edit_nom_medicamento')) return;
        
        let formularioCompletoValido = true;
        const campos = {
            nom_medicamento: modalBodyEditar.querySelector('#edit_nom_medicamento'),
            id_tipo_medic: modalBodyEditar.querySelector('#edit_id_tipo_medic'),
            descripcion: modalBodyEditar.querySelector('#edit_descripcion'),
            codigo_barras: modalBodyEditar.querySelector('#edit_codigo_barras')
        };
        
        const validar = (input, nombreCampo) => {
            let esValido = false;
            if (!input) return true;
            const valor = input.value.trim();
            switch (nombreCampo) {
                case 'nom_medicamento': esValido = valor.length > 4; break;
                case 'id_tipo_medic': esValido = valor !== ''; break;
                case 'descripcion': esValido = valor.length > 0; break;
                default: esValido = true;
            }
            if (inputEspecifico && input.id === inputEspecifico.id) {
                 input.classList.toggle('is-valid', esValido);
                 input.classList.toggle('is-invalid', !esValido);
            }
            return esValido;
        };

        for (const key in campos) {
            if (!validar(campos[key], key)) {
                formularioCompletoValido = false;
            }
        }

        let haCambiado = false;
        for (const key in campos) {
            if (campos[key] && campos[key].value !== originalData[key]) {
                haCambiado = true;
                break;
            }
        }
        
        if (btnGuardar) btnGuardar.disabled = !(formularioCompletoValido && haCambiado);
    }
    
    // --- 3. INICIALIZACIÓN DE EVENTOS ---
    
    inicializarCodigosDeBarras();

    formFiltros?.addEventListener('input', function(e) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            cargarContenido(1);
        }, 350);
    });
    
    btnLimpiarFiltros?.addEventListener('click', function() {
        if (formFiltros) formFiltros.reset();
        cargarContenido(1);
    });

    document.addEventListener('keypress', function(e) {
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
            return;
        }
        const currentTime = Date.now();
        if (currentTime - lastKeyTime > 100) { scanBuffer = ''; }
        lastKeyTime = currentTime;
        
        if (e.key === 'Enter') {
            if (scanBuffer.length > 3) {
                e.preventDefault();
                if(searchInput) searchInput.value = scanBuffer;
                cargarContenido(1);
            }
            scanBuffer = '';
        } else {
            scanBuffer += e.key;
        }
    });
    
    tablaContenedor?.addEventListener('click', function(e) {
        const targetElement = e.target.closest('a, button');
        if (!targetElement) return;

        if (targetElement.matches('.page-link')) {
            e.preventDefault();
            const page = targetElement.dataset.page;
            if (page) cargarContenido(page);
        }

        if (targetElement.matches('.btn-ver')) {
            const id = targetElement.dataset.id;
            modalBodyDetalles.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';
            modalDetalles.show();
            
            // MEJORA: URL dinámica
            fetch(`${API_BASE_URL}detalles_medicamento.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const med = data.medicamento;
                        let barcodeHTML = med.codigo_barras ? `<svg class="barcode-detail" jsbarcode-value="${med.codigo_barras}"></svg>` : 'No disponible';
                        const contenidoHTML = `
                            <dl class="row">
                                <dt class="col-sm-4">Nombre</dt>
                                <dd class="col-sm-8">${med.nom_medicamento || 'N/A'}</dd>
                                <dt class="col-sm-4">Tipo</dt>
                                <dd class="col-sm-8">${med.nom_tipo_medi || 'N/A'}</dd>
                                <dt class="col-sm-4">Descripción</dt>
                                <dd class="col-sm-8">${med.descripcion || 'Sin descripción.'}</dd>
                                <dt class="col-sm-4">Código de Barras</dt>
                                <dd class="col-sm-8">${barcodeHTML}</dd>
                            </dl>`;
                        modalBodyDetalles.innerHTML = contenidoHTML;
                        if (med.codigo_barras) { JsBarcode(".barcode-detail").init(); }
                    } else {
                        modalBodyDetalles.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                });
        }
        
        if (targetElement.matches('.btn-editar')) {
            const id = targetElement.dataset.id;
            modalBodyEditar.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';
            if(btnGuardar) btnGuardar.disabled = true;
            modalEditar.show();

            // MEJORA: URL dinámica
            fetch(`${API_BASE_URL}ajax_obtener_form_editar_medi.php?id=${id}`)
                .then(response => response.text())
                .then(html => {
                    modalBodyEditar.innerHTML = html;
                    const idInput = modalBodyEditar.querySelector('#edit-id-medicamento');
                    if (idInput) idInput.value = id; // Asegura que el ID esté en el form

                    originalData = {
                        nom_medicamento: modalBodyEditar.querySelector('#edit_nom_medicamento')?.value || '',
                        id_tipo_medic: modalBodyEditar.querySelector('#edit_id_tipo_medic')?.value || '',
                        descripcion: modalBodyEditar.querySelector('#edit_descripcion')?.value || '',
                        codigo_barras: modalBodyEditar.querySelector('#edit_codigo_barras')?.value || ''
                    };
                    modalBodyEditar.querySelectorAll('input, select, textarea').forEach(input => {
                        input.addEventListener('input', (e) => validarYChequearCambios(e.target));
                    });
                });
        }

        if (targetElement.matches('.btn-eliminar')) {
            const id = targetElement.dataset.id;
            const nombre = targetElement.dataset.nombre;
            Swal.fire({
                title: '¿Está seguro?',
                text: `Realmente desea eliminar "${nombre}"? Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Sí, eliminar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new URLSearchParams();
                    formData.append('id_medicamento', id);
                    formData.append('csrf_token', CSRF_TOKEN); // MEJORA: Añadir token de seguridad
                    
                    // MEJORA: URL dinámica
                    fetch(`${API_BASE_URL}ajax_eliminar_medicamento.php`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Eliminado', data.message, 'success').then(() => cargarContenido());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
                }
            });
        }
    });

    formEditar?.addEventListener('submit', function(e) {
        e.preventDefault();
        validarYChequearCambios();
        if (btnGuardar.disabled) return;

        btnGuardar.disabled = true;
        btnGuardar.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Guardando...`;
        
        const formData = new FormData(formEditar);
        formData.append('csrf_token', CSRF_TOKEN); // MEJORA: Añadir token de seguridad
        
        // MEJORA: URL dinámica
        fetch(`${API_BASE_URL}ajax_editar_medicamento.php`, { 
            method: 'POST', 
            body: formData 
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalEditar.hide();
                Swal.fire({
                    title: '¡Actualizado!',
                    text: data.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    cargarContenido();
                });
            } else {
                Swal.fire('Error', data.message, 'error');
                const nombreInput = modalBodyEditar.querySelector('#edit_nom_medicamento');
                const codigoInput = modalBodyEditar.querySelector('#edit_codigo_barras');
                if(nombreInput) nombreInput.classList.remove('is-invalid', 'is-valid');
                if(codigoInput) codigoInput.classList.remove('is-invalid', 'is-valid');
                if (data.message.toLowerCase().includes('nombre')) {
                    if(nombreInput) {
                        nombreInput.classList.add('is-invalid');
                        nombreInput.focus();
                    }
                } else if (data.message.toLowerCase().includes('código de barras')) {
                    if(codigoInput) {
                        codigoInput.classList.add('is-invalid');
                        codigoInput.focus();
                    }
                }
            }
        })
        .catch(error => {
            Swal.fire('Error de Conexión', 'No se pudo completar la solicitud.', 'error');
        })
        .finally(() => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = 'Guardar Cambios';
        });
    });
});