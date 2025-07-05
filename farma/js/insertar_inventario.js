document.addEventListener('DOMContentLoaded', function () {
    // --- 1. SELECCIÓN DE ELEMENTOS Y CONFIGURACIÓN ---
    const formBusqueda = document.getElementById('formBusquedaInicial');
    const inputCodigo = document.getElementById('codigo_barras_scan');
    const errorBusqueda = document.getElementById('busqueda-error');
    const areaRegistro = document.getElementById('area-registro');
    const areaPlaceholder = document.getElementById('area-placeholder');
    const formRegistro = document.getElementById('formRegistrarEntrada');
    const btnGuardar = document.getElementById('btnGuardarEntrada');
    const infoNombre = document.getElementById('nombre-medicamento');
    const infoStock = document.getElementById('stock-actual');
    const inputIdMedicamento = document.getElementById('id_medicamento_encontrado');

    const camposAValidar = {
        cantidad_entrada: document.getElementById('cantidad_entrada'),
        lote: document.getElementById('lote'),
        fecha_vencimiento: document.getElementById('fecha_vencimiento')
    };

    // MEJORA: Se obtienen las rutas y el token CSRF del DOM.
    const API_BASE_URL = document.querySelector('meta[name="api-base-url"]')?.getAttribute('content') || '/SALUDCONNECT/farma/';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // --- 2. FUNCIONES AUXILIARES ---

    function mostrarAreaRegistro(mostrar) {
        if(areaRegistro) areaRegistro.classList.toggle('d-none', !mostrar);
        if(areaPlaceholder) areaPlaceholder.classList.toggle('d-none', mostrar);
    }

    function validarCampo(input) {
        if (!input) return false;
        let esValido = false;
        const valor = input.value.trim();
        
        switch(input.id) {
            case 'cantidad_entrada':
                if (/^[1-9]\d*$/.test(valor)) {
                    esValido = true;
                } else {
                    esValido = false;
                    input.value = valor.replace(/[^0-9]/g, ''); 
                }
                break;
            case 'lote':
                esValido = valor.length >= 4; // Umbral común para un lote
                break;
            case 'fecha_vencimiento':
                if (valor) {
                    const fechaSeleccionada = new Date(valor + "T00:00:00");
                    const fechaMinima = new Date();
                    fechaMinima.setHours(0, 0, 0, 0);
                    fechaMinima.setMonth(fechaMinima.getMonth() + 3); // Mínimo 3 meses de vigencia
                    esValido = fechaSeleccionada >= fechaMinima;
                }
                break;
        }
        input.classList.toggle('is-valid', esValido);
        input.classList.toggle('is-invalid', !esValido);
        return esValido;
    }

    function comprobarEstadoFormulario() {
        const todosValidos = Object.values(camposAValidar).every(input => validarCampo(input));
        if (btnGuardar) btnGuardar.disabled = !todosValidos;
    }

    function buscarMedicamento() {
        const codigo = inputCodigo.value.trim();
        if (codigo === '') {
            errorBusqueda.textContent = 'Por favor, ingrese un código de barras.';
            mostrarAreaRegistro(false);
            return;
        }
        errorBusqueda.textContent = '';

        // MEJORA: URL dinámica
        fetch(`${API_BASE_URL}inventario/ajax_buscar_medicamento.php?codigo_barras=${encodeURIComponent(codigo)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const med = data.data;
                    infoNombre.textContent = med.nombre;
                    infoStock.textContent = med.stock_actual;
                    inputIdMedicamento.value = med.id_medicamento;
                    
                    if(formRegistro) formRegistro.reset();
                    mostrarAreaRegistro(true);

                    document.getElementById('cantidad_entrada')?.focus();
                    Object.values(camposAValidar).forEach(input => {
                        input?.classList.remove('is-valid', 'is-invalid');
                    });
                    if (btnGuardar) btnGuardar.disabled = true;
                } else {
                    errorBusqueda.textContent = data.message;
                    mostrarAreaRegistro(false);
                    inputCodigo.select();
                }
            })
            .catch(error => {
                console.error("Error buscando medicamento:", error);
                errorBusqueda.textContent = 'Error de conexión al buscar el medicamento.';
                mostrarAreaRegistro(false);
            });
    }

    // --- 3. INICIALIZACIÓN DE EVENTOS ---

    inputCodigo?.addEventListener('input', function() {
        if (inputCodigo.value.trim() === '') {
            mostrarAreaRegistro(false);
            if(errorBusqueda) errorBusqueda.textContent = '';
        }
    });

    Object.values(camposAValidar).forEach(input => {
        input?.addEventListener('input', () => {
            validarCampo(input);
            comprobarEstadoFormulario();
        });
    });

    formBusqueda?.addEventListener('submit', function (e) {
        e.preventDefault();
        buscarMedicamento();
    });

    formRegistro?.addEventListener('submit', function (e) {
        e.preventDefault();
        comprobarEstadoFormulario();
        if (btnGuardar.disabled) return;

        btnGuardar.disabled = true;
        btnGuardar.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Guardando...`;

        const formData = new FormData(formRegistro);
        formData.append('csrf_token', CSRF_TOKEN); // MEJORA: Añadir token de seguridad

        // MEJORA: URL dinámica
        fetch(`${API_BASE_URL}inventario/ajax_registrar_entrada.php`, { 
            method: 'POST', 
            body: formData 
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: '¡Éxito!',
                    text: data.message,
                    icon: 'success',
                    timer: 2000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                }).then(() => {
                    if (data.pendientes_cubiertos && data.pendientes_cubiertos.length > 0) {
                        let listaHtml = '<ul class="list-group text-start">';
                        data.pendientes_cubiertos.forEach(p => {
                            listaHtml += `<li class="list-group-item">Paciente <strong>${p.nom_usu}</strong> necesita <strong>${p.can_medica}</strong> unidades.</li>`;
                        });
                        listaHtml += '</ul>';
                        
                        // MEJORA: La URL de redirección es ahora dinámica.
                        const urlPendientes = `${API_BASE_URL.replace(/\/farma\/?$/, '')}/farma/entregar/entregas_pendientes.php`;

                        Swal.fire({
                            icon: 'info',
                            title: '¡Pendientes por Entregar!',
                            html: `<p>Se ha detectado que ya hay stock para cubrir los siguientes pendientes:</p>${listaHtml}`,
                            confirmButtonText: '<i class="bi bi-arrow-right-circle-fill me-2"></i>Ir a Entregas Pendientes',
                            confirmButtonColor: '#28a745'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = urlPendientes;
                            }
                        });
                    }
                });

                formRegistro.reset();
                inputCodigo.value = '';
                mostrarAreaRegistro(false);
                inputCodigo.focus();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error("Error registrando entrada:", error);
            Swal.fire('Error de Conexión', 'No se pudo completar la solicitud.', 'error');
        })
        .finally(() => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i>Registrar Entrada`;
        });
    });

    // Estado inicial
    mostrarAreaRegistro(false);
});