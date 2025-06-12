document.addEventListener('DOMContentLoaded', function() {
    const formFiltros = document.getElementById('formFiltros');
    const searchInput = document.getElementById('searchInput');
    const tablaContenedor = document.getElementById('contenedor-tabla');
    let debounceTimer;
    let scanBuffer = '';
    let lastKeyTime = Date.now();

    function inicializarCodigosDeBarras() {
        try {
            JsBarcode(".barcode").init();
        } catch (e) {
            console.error("Error al inicializar JsBarcode:", e);
        }
    }

    function cargarContenido(page = 1) {
        const formData = new FormData(formFiltros);
        const params = new URLSearchParams(formData);
        params.append('ajax', '1');
        params.append('pagina', page);
        
        const url = `ver_medicamento.php?${params.toString()}`;

        fetch(url)
            .then(response => response.text())
            .then(html => {
                tablaContenedor.innerHTML = html;
                inicializarCodigosDeBarras();
            })
            .catch(error => console.error('Error al cargar la tabla:', error));
    }

    inicializarCodigosDeBarras();

    formFiltros.addEventListener('input', function(e) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            cargarContenido(1);
        }, 350);
    });

    document.addEventListener('keypress', function(e) {
        if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA' || document.activeElement.tagName === 'SELECT') {
            return;
        }
        const currentTime = Date.now();
        if (currentTime - lastKeyTime > 100) { scanBuffer = ''; }
        lastKeyTime = currentTime;
        
        if (e.key === 'Enter') {
            if (scanBuffer.length > 3) {
                e.preventDefault();
                searchInput.value = scanBuffer;
                cargarContenido(1);
            }
            scanBuffer = '';
        } else {
            scanBuffer += e.key;
        }
    });

    const modalDetallesElement = document.getElementById('modalDetallesMedicamento');
    const modalDetalles = new bootstrap.Modal(modalDetallesElement);
    const modalBodyDetalles = document.getElementById('cuerpoModalDetalles');

    const modalEditarElement = document.getElementById('modalEditarMedicamento');
    const modalEditar = new bootstrap.Modal(modalEditarElement);
    const formEditar = document.getElementById('formEditarMedicamento');
    const modalBodyEditar = document.getElementById('cuerpoModalEditar');
    const inputId = document.getElementById('edit-id-medicamento');
    const btnGuardar = document.getElementById('btnGuardarCambios');
    let originalData = {};

    function validarYChequearCambios(inputEspecifico = null) {
        if (!modalBodyEditar.querySelector('#edit_nom_medicamento')) return;
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
        
        btnGuardar.disabled = !(formularioCompletoValido && haCambiado);
    }
    
    tablaContenedor.addEventListener('click', function(e) {
        const targetElement = e.target;
        
        if (targetElement.closest('.page-link')) {
            e.preventDefault();
            const page = targetElement.closest('.page-link').dataset.page;
            if (page) {
                cargarContenido(page);
            }
        }

        if (targetElement.closest('.btn-ver')) {
            const btnVer = targetElement.closest('.btn-ver');
            const id = btnVer.dataset.id;
            modalBodyDetalles.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';
            modalDetalles.show();
            
            fetch(`detalles_medicamento.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const med = data.medicamento;
                        let barcodeHTML = med.codigo_barras ? `<svg class="barcode-detail" jsbarcode-value="${med.codigo_barras}"></svg>` : 'No disponible';
                        const contenidoHTML = `
                            <dl class="row">
                                <dt class="col-sm-4">Nombre del Medicamento</dt>
                                <dd class="col-sm-8">${med.nom_medicamento || 'N/A'}</dd>
                                <dt class="col-sm-4">Tipo de Medicamento</dt>
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
        
        const btnEditar = targetElement.closest('.btn-editar');
        if (btnEditar) {
            const id = btnEditar.dataset.id;
            inputId.value = id;
            modalBodyEditar.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>';
            btnGuardar.disabled = true;
            modalEditar.show();

            fetch(`ajax_obtener_form_editar_medi.php?id=${id}`)
                .then(response => response.text())
                .then(html => {
                    modalBodyEditar.innerHTML = html;
                    originalData = {
                        nom_medicamento: modalBodyEditar.querySelector('#edit_nom_medicamento').value,
                        id_tipo_medic: modalBodyEditar.querySelector('#edit_id_tipo_medic').value,
                        descripcion: modalBodyEditar.querySelector('#edit_descripcion').value,
                        codigo_barras: modalBodyEditar.querySelector('#edit_codigo_barras').value
                    };
                    modalBodyEditar.querySelectorAll('input, select, textarea').forEach(input => {
                        input.addEventListener('input', (e) => validarYChequearCambios(e.target));
                    });
                });
        }

        const btnEliminar = targetElement.closest('.btn-eliminar');
        if (btnEliminar) {
            const id = btnEliminar.dataset.id;
            const nombre = btnEliminar.dataset.nombre;
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
                    fetch('ajax_eliminar_medicamento.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id_medicamento=${id}`
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

    formEditar.addEventListener('submit', function(e) {
        e.preventDefault();
        validarYChequearCambios();
        if (btnGuardar.disabled) return;

        btnGuardar.disabled = true;
        btnGuardar.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Guardando...`;
        
        const formData = new FormData(formEditar);
        
        fetch('ajax_editar_medicamento.php', { 
            method: 'POST', 
            body: formData 
        })
        .then(response => {
            if (!response.ok) throw new Error('Error en la respuesta del servidor.');
            return response.json();
        })
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
                nombreInput.classList.remove('is-invalid', 'is-valid');
                codigoInput.classList.remove('is-invalid', 'is-valid');
                if (data.message.toLowerCase().includes('nombre')) {
                    nombreInput.classList.add('is-invalid');
                    nombreInput.focus();
                } else if (data.message.toLowerCase().includes('código de barras')) {
                    codigoInput.classList.add('is-invalid');
                    codigoInput.focus();
                }
            }
        })
        .catch(error => {
            console.error('Error en fetch:', error);
            Swal.fire('Error de Conexión', 'No se pudo completar la solicitud.', 'error');
        })
        .finally(() => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = 'Guardar Cambios';
        });
    });
});