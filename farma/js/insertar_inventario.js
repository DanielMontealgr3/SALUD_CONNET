document.addEventListener('DOMContentLoaded', function () {
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

    function mostrarAreaRegistro(mostrar) {
        areaRegistro.classList.toggle('d-none', !mostrar);
        areaPlaceholder.classList.toggle('d-none', mostrar);
    }

    inputCodigo.addEventListener('input', function() {
        if (inputCodigo.value.trim() === '') {
            mostrarAreaRegistro(false);
            errorBusqueda.textContent = '';
        }
    });

    function validarCampo(input) {
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
                esValido = valor.length >= 5;
                break;
            case 'fecha_vencimiento':
                if (valor) {
                    const fechaSeleccionada = new Date(valor + "T00:00:00");
                    const fechaMinima = new Date();
                    fechaMinima.setHours(0, 0, 0, 0);
                    fechaMinima.setMonth(fechaMinima.getMonth() + 3);
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
        btnGuardar.disabled = !todosValidos;
    }

    Object.values(camposAValidar).forEach(input => {
        input.addEventListener('input', () => {
            validarCampo(input);
            comprobarEstadoFormulario();
        });
    });

    function buscarMedicamento() {
        const codigo = inputCodigo.value.trim();
        if (codigo === '') {
            errorBusqueda.textContent = 'Por favor, ingrese un código de barras.';
            mostrarAreaRegistro(false);
            return;
        }
        errorBusqueda.textContent = '';

        fetch(`../inventario/ajax_buscar_medicamento.php?codigo_barras=${codigo}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const med = data.data;
                    infoNombre.textContent = med.nombre;
                    infoStock.textContent = med.stock_actual;
                    inputIdMedicamento.value = med.id_medicamento;
                    
                    formRegistro.reset();
                    mostrarAreaRegistro(true);

                    document.getElementById('cantidad_entrada').focus();
                    Object.values(camposAValidar).forEach(input => {
                        input.classList.remove('is-valid', 'is-invalid');
                    });
                    btnGuardar.disabled = true;
                } else {
                    errorBusqueda.textContent = data.message;
                    mostrarAreaRegistro(false);
                    inputCodigo.select();
                }
            })
            .catch(error => {
                errorBusqueda.textContent = 'Error de conexión al buscar el medicamento.';
                mostrarAreaRegistro(false);
            });
    }

    formBusqueda.addEventListener('submit', function (e) {
        e.preventDefault();
        buscarMedicamento();
    });

    formRegistro.addEventListener('submit', function (e) {
        e.preventDefault();
        comprobarEstadoFormulario();
        if (btnGuardar.disabled) return;

        btnGuardar.disabled = true;
        btnGuardar.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Guardando...`;

        const formData = new FormData(formRegistro);

        fetch('../inventario/ajax_registrar_entrada.php', { 
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
                        let listaHtml = '<ul class="list-group">';
                        data.pendientes_cubiertos.forEach(p => {
                            listaHtml += `<li class="list-group-item">Paciente <strong>${p.nom_usu}</strong> necesita <strong>${p.can_medica}</strong> unidades.</li>`;
                        });
                        listaHtml += '</ul>';

                        Swal.fire({
                            icon: 'info',
                            title: '¡Pendientes por Entregar!',
                            html: `<p>Se ha detectado que ya hay stock para cubrir los siguientes pendientes:</p>${listaHtml}`,
                            confirmButtonText: '<i class="bi bi-arrow-right-circle-fill me-2"></i>Ir a Entregas Pendientes',
                            confirmButtonColor: '#28a745'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '../entregar/entregas_pendientes.php';
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
            Swal.fire('Error de Conexión', 'No se pudo completar la solicitud.', 'error');
        })
        .finally(() => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i>Registrar Entrada`;
        });
    });

    mostrarAreaRegistro(false);
});