document.addEventListener('DOMContentLoaded', function() {
    const botonesLlamar = document.querySelectorAll('.btn-llamar-paciente');

    botonesLlamar.forEach(boton => {
        boton.addEventListener('click', function() {
            const idTurno = this.dataset.idturno;
            const docPaciente = this.dataset.docpaciente;
            const csrfToken = typeof csrfTokenListaPacientesGlobal !== 'undefined' ? csrfTokenListaPacientesGlobal : '';

            if (!idTurno || !docPaciente) {
                mostrarAlerta('Error: Faltan datos para llamar al paciente.', 'danger');
                return;
            }
            
            const formData = new FormData();
            formData.append('id_turno', idTurno);
            formData.append('doc_paciente', docPaciente);
            formData.append('csrf_token', csrfToken);

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Llamando...';

            fetch('../ajax/llamar_paciente.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlerta(data.message, 'success');
                    this.classList.remove('btn-primary', 'btn-llamar-paciente');
                    this.classList.add('btn-success', 'btn-en-atencion');
                    this.innerHTML = '<i class="bi bi-person-check-fill"></i> En Atención';
                    setTimeout(() => { window.location.reload(); }, 1500);

                } else {
                    mostrarAlerta(data.message || 'Error al llamar al paciente.', 'danger');
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-megaphone-fill"></i> Llamar';
                    if (data.already_called) {
                         this.classList.remove('btn-primary', 'btn-llamar-paciente');
                         this.classList.add('btn-success', 'btn-en-atencion');
                         this.innerHTML = '<i class="bi bi-person-check-fill"></i> En Atención';
                         this.disabled = true;
                    }
                }
            })
            .catch(error => {
                console.error('Error en fetch:', error);
                mostrarAlerta('Error de red o comunicación con el servidor.', 'danger');
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-megaphone-fill"></i> Llamar';
            });
        });
    });

    function mostrarAlerta(mensaje, tipo) {
        let alertaDiv = document.getElementById('alerta-accion-js');
        if (!alertaDiv) {
            alertaDiv = document.createElement('div');
            alertaDiv.id = 'alerta-accion-js';
            alertaDiv.className = 'alert alert-dismissible fade show';
            alertaDiv.setAttribute('role', 'alert');
            
            const textoMensaje = document.createTextNode(mensaje); // Crear nodo de texto
            const botonCerrar = document.createElement('button');
            botonCerrar.type = 'button';
            botonCerrar.className = 'btn-close';
            botonCerrar.setAttribute('data-bs-dismiss', 'alert');
            botonCerrar.setAttribute('aria-label', 'Close');
            
            alertaDiv.appendChild(textoMensaje); // Añadir texto primero
            alertaDiv.appendChild(botonCerrar);  // Luego el botón
            
            const formFiltros = document.querySelector('.filtros-tabla-container');
            if (formFiltros && formFiltros.parentNode) {
                formFiltros.parentNode.insertBefore(alertaDiv, formFiltros);
            } else { 
                const tituloTabla = document.querySelector('.titulo-lista-tabla');
                if(tituloTabla && tituloTabla.parentNode) tituloTabla.parentNode.insertBefore(alertaDiv, tituloTabla.nextSibling);
            }
        } else {
            // Si ya existe, solo actualiza el texto y la clase
            alertaDiv.childNodes[0].nodeValue = mensaje; // Asumiendo que el texto es el primer hijo
        }
        
        alertaDiv.className = `alert alert-${tipo} alert-dismissible fade show fixed-top mx-auto mt-3`; 
        alertaDiv.style.maxWidth = '600px';
        alertaDiv.style.left = '50%';
        alertaDiv.style.transform = 'translateX(-50%)';
        alertaDiv.style.zIndex = '1056';
        alertaDiv.style.display = 'block';
                                                
        if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
            var alertInstance = bootstrap.Alert.getOrCreateInstance(alertaDiv);
        }

        setTimeout(() => {
            if (alertaDiv && alertaDiv.classList.contains('show')) {
                 if (typeof bootstrap !== 'undefined' && bootstrap.Alert && bootstrap.Alert.getInstance(alertaDiv)) {
                    bootstrap.Alert.getInstance(alertaDiv).close();
                } else {
                    alertaDiv.style.display = 'none';
                }
            }
        }, 5000);
    }
});