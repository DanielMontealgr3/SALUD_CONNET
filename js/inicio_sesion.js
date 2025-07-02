document.addEventListener('DOMContentLoaded', function() {
    const formulario = document.getElementById('formulario-login');
    const tipoDocumentoSelect = document.getElementById('id_tipo_doc');
    const documentoInput = document.getElementById('doc_usu');
    const contrasenaInput = document.getElementById('pass');
    const aceptoTerminosCheckbox = document.getElementById('acepto_terminos');
    const botonEnviar = document.getElementById('boton-enviar');

    const errorTipoDocSpan = document.getElementById('error-tipo-doc');
    const errorDocUsuSpan = document.getElementById('error-doc-usu');
    const errorPassSpan = document.getElementById('error-pass');

    const modalTerminos = document.getElementById('modalTerminos');
    const enlaceTerminos = document.getElementById('enlace_terminos');
    const cerrarModalTerminos = document.getElementById('cerrarModalTerminos');

    const estadoValidacion = {
        tipoDoc: false,
        docUsu: false,
        pass: false
    };

    const aplicarEstilo = (elemento, esValido) => {
        if (!elemento.dataset.interacted) {
            elemento.classList.remove('input-error', 'input-success');
            return;
        }
        elemento.classList.toggle('input-error', !esValido);
        elemento.classList.toggle('input-success', esValido);
    };
    
    const mostrarMensaje = (span, mensaje) => {
        span.textContent = mensaje;
        span.classList.toggle('visible', !!mensaje);
    };

    const actualizarBoton = () => {
        const todoValido = Object.values(estadoValidacion).every(v => v);
        botonEnviar.disabled = !(todoValido && aceptoTerminosCheckbox.checked);
    };

    const validarTipoDoc = () => {
        const esValido = tipoDocumentoSelect.value !== "";
        estadoValidacion.tipoDoc = esValido;
        if (tipoDocumentoSelect.dataset.interacted) {
            aplicarEstilo(tipoDocumentoSelect, esValido);
            mostrarMensaje(errorTipoDocSpan, esValido ? '' : 'Seleccione un tipo de documento.');
        }
        actualizarBoton();
        return esValido;
    };

    const validarDocumento = () => {
        const valor = documentoInput.value.trim();
        let esValido = false;
        let mensaje = '';
        if (valor === "") {
            mensaje = 'El documento es requerido.';
        } else if (!/^\d+$/.test(valor)) {
            mensaje = 'Solo se permiten números.';
        } else if (valor.length < 7 || valor.length > 11) {
            mensaje = 'El documento debe tener entre 7 y 11 dígitos.';
        } else {
            esValido = true;
        }
        estadoValidacion.docUsu = esValido;
        if (documentoInput.dataset.interacted) {
            aplicarEstilo(documentoInput, esValido);
            mostrarMensaje(errorDocUsuSpan, mensaje);
        }
        actualizarBoton();
        return esValido;
    };
    
    const validarContrasena = () => {
        const valor = contrasenaInput.value;
        let esValido = false;
        let mensaje = '';
        
        if (valor === "") {
            mensaje = 'La contraseña es requerida.';
        } else if (valor.length < 8) {
            mensaje = 'Debe tener al menos 8 caracteres.';
        } else if (!/[A-Z]/.test(valor)) {
            mensaje = 'Debe incluir al menos una mayúscula.';
        } else if (!/[a-z]/.test(valor)) {
            mensaje = 'Debe incluir al menos una minúscula.';
        } else if (!/\d/.test(valor)) {
            mensaje = 'Debe incluir al menos un número.';
        } else if (!/[\W_]/.test(valor)) {
            mensaje = 'Debe incluir al menos un símbolo.';
        } else {
            esValido = true;
        }
        
        estadoValidacion.pass = esValido;
        if(contrasenaInput.dataset.interacted) {
            aplicarEstilo(contrasenaInput, esValido);
            mostrarMensaje(errorPassSpan, mensaje);
        }
        actualizarBoton();
        return esValido;
    };

    const campos = [
        { el: tipoDocumentoSelect, fn: validarTipoDoc },
        { el: documentoInput, fn: validarDocumento },
        { el: contrasenaInput, fn: validarContrasena }
    ];

    campos.forEach(({ el, fn }) => {
        const evento = el.tagName === 'SELECT' ? 'change' : 'input';
        el.addEventListener(evento, () => {
            el.dataset.interacted = true;
            fn();
        });
        el.addEventListener('focus', () => { // Marcar como interactuado al entrar
            el.dataset.interacted = true;
        });
    });

    aceptoTerminosCheckbox.addEventListener('change', actualizarBoton);

    formulario.addEventListener('submit', function(e) {
        campos.forEach(c => c.el.dataset.interacted = true);
        const formValido = campos.every(c => c.fn());

        if (!formValido || !aceptoTerminosCheckbox.checked) {
            e.preventDefault();
            if (!aceptoTerminosCheckbox.checked && formValido) {
                alert('Debe aceptar los Términos y Condiciones para continuar.');
            }
            const primerError = formulario.querySelector('.input-error');
            if (primerError) {
                primerError.focus();
            }
        }
    });

    if (enlaceTerminos && modalTerminos && cerrarModalTerminos) {
        enlaceTerminos.addEventListener('click', (e) => {
            e.preventDefault();
            modalTerminos.classList.add('visible');
            document.body.style.overflow = 'hidden';
        });

        const cerrarModal = () => {
            modalTerminos.classList.remove('visible');
            document.body.style.overflow = '';
        };

        cerrarModalTerminos.addEventListener('click', cerrarModal);

        window.addEventListener('click', (e) => {
            if (e.target === modalTerminos) {
                cerrarModal();
            }
        });

        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modalTerminos.classList.contains('visible')) {
                cerrarModal();
            }
        });
    }
});