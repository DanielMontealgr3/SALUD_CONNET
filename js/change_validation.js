document.addEventListener('DOMContentLoaded', function() {

    const formulario = document.getElementById('formulario-change');
    const passInput = document.getElementById('pass');
    const pass2Input = document.getElementById('pass2');
    const errorPassSpan = document.getElementById('error-pass');
    const errorPass2Span = document.getElementById('error-pass2');
    const requisitosLista = document.getElementById('requisitos-lista');
    const reqItems = {
        length: document.getElementById('req-length'),
        lower: document.getElementById('req-lower'),
        upper: document.getElementById('req-upper'),
        number: document.getElementById('req-number'),
        special: document.getElementById('req-special')
    };
    const submitButton = document.getElementById('submit-btn');

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

    const validarRequisitosPassword = (password) => {
        const requisitos = {
            length: password.length >= 8,
            lower: /[a-z]/.test(password),
            upper: /[A-Z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>_+\-=\[\]\\';\/]/.test(password)
        };
        return requisitos;
    };

    const actualizarVistaRequisitos = (requisitos) => {
        for (const key in reqItems) {
            if (reqItems[key]) {
                if (requisitos[key]) {
                    reqItems[key].classList.remove('invalid');
                    reqItems[key].classList.add('valid');
                } else {
                    reqItems[key].classList.remove('valid');
                    reqItems[key].classList.add('invalid');
                }
            }
        }
    };

    const validarCoincidenciaPasswords = () => {
        const pass1 = passInput.value;
        const pass2 = pass2Input.value;
        let coinciden = true;

        if (pass2 === "") {
            aplicarEstilo(pass2Input, null);
            mostrarMensaje(errorPass2Span, '');
            // No se considera inválido si está vacío aún
        } else if (pass1 !== pass2) {
            aplicarEstilo(pass2Input, 'input-error');
            mostrarMensaje(errorPass2Span, 'Las contraseñas no coinciden.');
            coinciden = false;
        } else {
            aplicarEstilo(pass2Input, 'input-success');
            mostrarMensaje(errorPass2Span, '');
        }
        return coinciden;
    };

     const ocultarMensajesServidor = () => {
        const serverMessages = formulario.querySelectorAll('.mensaje-login-servidor, .mensaje-login-alerta, .mensaje-login-exito');
        serverMessages.forEach(msg => msg.remove());
    };


    const validarFormularioCompleto = () => {
        const password = passInput.value;
        const requisitos = validarRequisitosPassword(password);
        actualizarVistaRequisitos(requisitos);

        const todosRequisitosCumplidos = Object.values(requisitos).every(val => val === true);

        if (password === "") {
             aplicarEstilo(passInput, null);
        } else if (todosRequisitosCumplidos) {
            aplicarEstilo(passInput, 'input-success');
            mostrarMensaje(errorPassSpan, ''); // Ocultar mensaje de error si ahora es válido
        } else {
             aplicarEstilo(passInput, 'input-error');
             // No mostramos mensaje aquí, la lista de requisitos es suficiente
        }


        const coinciden = validarCoincidenciaPasswords();

        // Habilitar botón solo si todos los requisitos se cumplen Y las contraseñas coinciden
        submitButton.disabled = !(todosRequisitosCumplidos && coinciden);
    };


    passInput.addEventListener('input', () => {
        ocultarMensajesServidor();
        validarFormularioCompleto();
    });

    pass2Input.addEventListener('input', () => {
        ocultarMensajesServidor();
        validarFormularioCompleto();
    });

    // Validar al perder foco para asegurar estilos correctos
    passInput.addEventListener('blur', validarFormularioCompleto);
    pass2Input.addEventListener('blur', validarFormularioCompleto);


    formulario.addEventListener('submit', function(evento) {
        validarFormularioCompleto(); // Última validación
        if (submitButton.disabled) {
            evento.preventDefault();
             // Opcional: poner foco en el primer campo inválido
             if (!Object.values(validarRequisitosPassword(passInput.value)).every(val => val === true)) {
                 passInput.focus();
             } else if (passInput.value !== pass2Input.value) {
                 pass2Input.focus();
             }
        }
    });

    // Estado inicial
    validarFormularioCompleto();
});