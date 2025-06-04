document.addEventListener('DOMContentLoaded', function() {

    const formulario = document.getElementById('formulario-recuperacion');
    const correoInput = document.getElementById('correo_usu');
    const errorCorreoSpan = document.getElementById('error-correo-usu');
    const botonEnviar = formulario.querySelector('input[type="submit"].boton_reg');

    const aplicarEstilo = (elemento, clase) => {
        elemento.classList.remove('input-error', 'input-success');
        if (clase) {
            elemento.classList.add(clase);
        }
    };

    const mostrarMensaje = (elementoSpan, mensaje) => {
        if (elementoSpan) {
            elementoSpan.textContent = mensaje;
            elementoSpan.classList.toggle('visible', !!mensaje);
        }
    };

    const validarEmail = (email) => {
        const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return re.test(String(email).toLowerCase());
    };

    const validarCampoCorreo = (esBlur = false) => {
        const valor = correoInput.value.trim();
        let esValido = false;

        if (valor === "") {
            if (esBlur) {
               aplicarEstilo(correoInput, 'input-error');
               mostrarMensaje(errorCorreoSpan, 'Ingrese su correo electrónico.');
            } else {
               aplicarEstilo(correoInput, null);
            }
        } else if (validarEmail(valor)) {
            aplicarEstilo(correoInput, 'input-success');
            esValido = true;
        } else {
            aplicarEstilo(correoInput, 'input-error');
            mostrarMensaje(errorCorreoSpan, 'Ingrese un formato de correo válido (ej: usuario@dominio.com).');
        }
        botonEnviar.disabled = !esValido;
    };

    correoInput.addEventListener('input', function() {
        mostrarMensaje(errorCorreoSpan, '');

        const serverMessage = formulario.querySelector('.mensaje-login-servidor, .mensaje-login-alerta');
        if (serverMessage) {
            serverMessage.remove();
        }

        validarCampoCorreo(false);
    });

    correoInput.addEventListener('blur', function() {
         validarCampoCorreo(true);
    });


    formulario.addEventListener('submit', function(evento) {
        validarCampoCorreo(true);

        if (botonEnviar.disabled) {
            evento.preventDefault();
            if (document.activeElement !== correoInput) {
                correoInput.focus();
            }
        }
    });

    validarCampoCorreo(false);
});