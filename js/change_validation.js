// ESPERA A QUE TODO EL CONTENIDO HTML DE LA PÁGINA HAYA CARGADO ANTES DE EJECUTAR EL SCRIPT.
document.addEventListener('DOMContentLoaded', function() {

    // SELECCIÓN DE TODOS LOS ELEMENTOS DEL DOM (FORMULARIO, INPUTS, ETC.) CON LOS QUE SE VA A INTERACTUAR.
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

    // FUNCIÓN PARA APLICAR ESTILOS CSS (BORDE ROJO O VERDE) A UN CAMPO.
    const aplicarEstilo = (elemento, clase) => {
        elemento.classList.remove('input-error', 'input-success');
        if (clase) {
            elemento.classList.add(clase);
        }
    };

    // FUNCIÓN PARA MOSTRAR U OCULTAR UN MENSAJE DE ERROR.
    const mostrarMensaje = (elementoSpan, mensaje) => {
        if (elementoSpan) {
            elementoSpan.textContent = mensaje;
            elementoSpan.classList.toggle('visible', !!mensaje);
        }
    };

    // FUNCIÓN QUE VERIFICA SI UNA CONTRASEÑA CUMPLE CON TODOS LOS REQUISITOS DE SEGURIDAD.
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

    // FUNCIÓN QUE ACTUALIZA VISUALMENTE LA LISTA DE REQUISITOS (TACHANDO LOS QUE SE CUMPLEN).
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

    // FUNCIÓN QUE VERIFICA SI LOS DOS CAMPOS DE CONTRASEÑA COINCIDEN.
    const validarCoincidenciaPasswords = () => {
        const pass1 = passInput.value;
        const pass2 = pass2Input.value;
        let coinciden = true;

        if (pass2 === "") {
            aplicarEstilo(pass2Input, null);
            mostrarMensaje(errorPass2Span, '');
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

    // FUNCIÓN PARA OCULTAR LOS MENSAJES DE ERROR QUE VIENEN DEL SERVIDOR CUANDO EL USUARIO EMPIEZA A CORREGIR.
     const ocultarMensajesServidor = () => {
        const serverMessages = formulario.querySelectorAll('.mensaje-login-servidor, .mensaje-login-alerta, .mensaje-login-exito');
        serverMessages.forEach(msg => msg.remove());
    };

    // FUNCIÓN PRINCIPAL DE VALIDACIÓN QUE ORQUESTA TODAS LAS DEMÁS Y ACTUALIZA EL BOTÓN DE ENVÍO.
    const validarFormularioCompleto = () => {
        const password = passInput.value;
        const requisitos = validarRequisitosPassword(password);
        actualizarVistaRequisitos(requisitos);
        const todosRequisitosCumplidos = Object.values(requisitos).every(val => val === true);

        if (password === "") {
             aplicarEstilo(passInput, null);
        } else if (todosRequisitosCumplidos) {
            aplicarEstilo(passInput, 'input-success');
            mostrarMensaje(errorPassSpan, '');
        } else {
             aplicarEstilo(passInput, 'input-error');
        }

        const coinciden = validarCoincidenciaPasswords();
        submitButton.disabled = !(todosRequisitosCumplidos && coinciden);
    };

    // ASIGNACIÓN DE EVENTOS A LOS INPUTS PARA VALIDAR EN TIEMPO REAL.
    passInput.addEventListener('input', () => {
        ocultarMensajesServidor();
        validarFormularioCompleto();
    });
    pass2Input.addEventListener('input', () => {
        ocultarMensajesServidor();
        validarFormularioCompleto();
    });
    passInput.addEventListener('blur', validarFormularioCompleto);
    pass2Input.addEventListener('blur', validarFormularioCompleto);

    // GESTIONA EL ENVÍO DEL FORMULARIO PARA UNA ÚLTIMA VALIDACIÓN ANTES DE ENVIAR.
    formulario.addEventListener('submit', function(evento) {
        validarFormularioCompleto(); 
        if (submitButton.disabled) {
            evento.preventDefault();
             if (!Object.values(validarRequisitosPassword(passInput.value)).every(val => val === true)) {
                 passInput.focus();
             } else if (passInput.value !== pass2Input.value) {
                 pass2Input.focus();
             }
        }
    });

    // EJECUTA LA VALIDACIÓN UNA VEZ AL CARGAR LA PÁGINA PARA ESTABLECER EL ESTADO INICIAL DEL BOTÓN.
    validarFormularioCompleto();
});