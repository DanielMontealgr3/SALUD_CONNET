document.addEventListener('DOMContentLoaded', function() {

    const formulario = document.getElementById('formulario-login');
    const tipoDocumentoSelect = document.getElementById('id_tipo_doc');
    const documentoInput = document.getElementById('doc_usu');
    const contrasenaInput = document.getElementById('pass');

    const errorTipoDocSpan = document.getElementById('error-tipo-doc');
    const errorDocUsuSpan = document.getElementById('error-doc-usu');
    const errorPassSpan = document.getElementById('error-pass');

    const aplicarEstilo = (elemento, clase) => {
        elemento.classList.remove('input-error', 'input-success');
        if (clase) {
            elemento.classList.add(clase);
        }
    };

    const mostrarMensaje = (elementoSpan, mensaje) => {
        elementoSpan.textContent = mensaje;
        if (mensaje) {
            elementoSpan.classList.add('visible'); 
        } else {
            elementoSpan.classList.remove('visible');
        }
    };

    // --- Validación en tiempo real para TIPO DE DOCUMENTO ---
    tipoDocumentoSelect.addEventListener('change', function() {
        if (this.value === "") {
            aplicarEstilo(this, 'input-error');
            mostrarMensaje(errorTipoDocSpan, 'Seleccione un tipo de documento.');
        } else {
            aplicarEstilo(this, 'input-success');
            mostrarMensaje(errorTipoDocSpan, '');
        }
    });

    // --- Validación en tiempo real para DOCUMENTO ---
    documentoInput.addEventListener('input', function() {
        const valor = this.value.trim();
<<<<<<< HEAD
        if (valor === "") {
            aplicarEstilo(this, null); 
            mostrarMensaje(errorDocUsuSpan, '');
        } else if (!/^\d+$/.test(valor)) {
            aplicarEstilo(this, 'input-error');
            mostrarMensaje(errorDocUsuSpan, 'Solo números permitidos.');
        } else if (valor.length < 7 || valor.length > 11) {
            aplicarEstilo(this, 'input-error');
            mostrarMensaje(errorDocUsuSpan, 'Debe tener 7-11 dígitos.');
        } else {
            aplicarEstilo(this, 'input-success');
            mostrarMensaje(errorDocUsuSpan, '');
=======
        const soloNumeros = /^\d+$/.test(valor);

        if (valor === "") {
            aplicarEstilo(this, null); 
            mostrarMensaje(errorDocUsuSpan, '');
        } else if (soloNumeros) {
            aplicarEstilo(this, 'input-success');
            mostrarMensaje(errorDocUsuSpan, '');
        } else {
            aplicarEstilo(this, 'input-error');
            mostrarMensaje(errorDocUsuSpan, 'El documento solo debe contener números.');
>>>>>>> main
        }
    });

    // --- Validación en tiempo real para CONTRASEÑA ---
    contrasenaInput.addEventListener('input', function() {
        const valor = this.value;
        if (valor === "") {
            aplicarEstilo(this, null); 
            mostrarMensaje(errorPassSpan, '');
<<<<<<< HEAD
        } else if (!(valor.length >= 8 && /[a-z]/.test(valor) && /[A-Z]/.test(valor) && /\d/.test(valor) && /[\W_]/.test(valor))) {
            aplicarEstilo(this, 'input-error');
            mostrarMensaje(errorPassSpan, 'Mín 8: Mayús, minús, núm, símb.');
        } else {
            aplicarEstilo(this, 'input-success');
            mostrarMensaje(errorPassSpan, '');
=======
        } else if (valor.length >= 6) {
            aplicarEstilo(this, 'input-success');
            mostrarMensaje(errorPassSpan, '');
        } else {
            aplicarEstilo(this, 'input-error');
            mostrarMensaje(errorPassSpan, 'La contraseña debe tener al menos 6 caracteres.');
>>>>>>> main
        }
    });


    formulario.addEventListener('submit', function(evento) {
        let esValido = true;

        // Validar Tipo Documento
        if (tipoDocumentoSelect.value === "") {
            aplicarEstilo(tipoDocumentoSelect, 'input-error');
            mostrarMensaje(errorTipoDocSpan, 'Seleccione un tipo de documento.');
            esValido = false;
        } else {
             if (!tipoDocumentoSelect.classList.contains('input-error')) {
                 aplicarEstilo(tipoDocumentoSelect, 'input-success');
             }
             if (tipoDocumentoSelect.value !== "") {
                mostrarMensaje(errorTipoDocSpan, '');
             }
        }

        // Validar Documento
        const docValor = documentoInput.value.trim();
        if (docValor === "") {
            aplicarEstilo(documentoInput, 'input-error');
<<<<<<< HEAD
            mostrarMensaje(errorDocUsuSpan, 'Documento es requerido.');
            esValido = false;
        } else if (!/^\d+$/.test(docValor)) {
            aplicarEstilo(documentoInput, 'input-error');
            mostrarMensaje(errorDocUsuSpan, 'Solo números permitidos.');
            esValido = false;
        } else if (docValor.length < 7 || docValor.length > 11) {
            aplicarEstilo(documentoInput, 'input-error');
            mostrarMensaje(errorDocUsuSpan, 'Debe tener 7-11 dígitos.');
=======
            mostrarMensaje(errorDocUsuSpan, 'Ingrese su número de documento.');
            esValido = false;
        } else if (!/^\d+$/.test(docValor)) {
            aplicarEstilo(documentoInput, 'input-error');
            mostrarMensaje(errorDocUsuSpan, 'El documento solo debe contener números.');
>>>>>>> main
            esValido = false;
        } else {
             if (!documentoInput.classList.contains('input-error')) {
                 aplicarEstilo(documentoInput, 'input-success');
             }
<<<<<<< HEAD
              mostrarMensaje(errorDocUsuSpan, '');
=======
             if (/^\d+$/.test(docValor)) {
                 mostrarMensaje(errorDocUsuSpan, '');
             }
>>>>>>> main
        }

        // Validar Contraseña
        const passValor = contrasenaInput.value;
        if (passValor === "") {
            aplicarEstilo(contrasenaInput, 'input-error');
<<<<<<< HEAD
            mostrarMensaje(errorPassSpan, 'Contraseña es requerida.');
            esValido = false;
        } else if (!(passValor.length >= 8 && /[a-z]/.test(passValor) && /[A-Z]/.test(passValor) && /\d/.test(passValor) && /[\W_]/.test(passValor))) {
            aplicarEstilo(contrasenaInput, 'input-error');
            mostrarMensaje(errorPassSpan, 'Mín 8: Mayús, minús, núm, símb.');
=======
            mostrarMensaje(errorPassSpan, 'Ingrese su contraseña.');
            esValido = false;
        } else if (passValor.length < 6) {
            aplicarEstilo(contrasenaInput, 'input-error');
            mostrarMensaje(errorPassSpan, 'La contraseña debe tener al menos 6 caracteres.');
>>>>>>> main
            esValido = false;
        } else {
             if (!contrasenaInput.classList.contains('input-error')) {
                aplicarEstilo(contrasenaInput, 'input-success');
             }
<<<<<<< HEAD
             mostrarMensaje(errorPassSpan, '');
        }

        if (!esValido) {
            evento.preventDefault();
=======
             if (passValor.length >= 6) {
                mostrarMensaje(errorPassSpan, '');
             }
        }

        // Si algo no es válido, previene el envío y enfoca el primer error
        if (!esValido) {
            evento.preventDefault();
            console.log('Validación final fallida. Envío detenido.');
>>>>>>> main
            const primerError = formulario.querySelector('.input-error');
            if(primerError) {
                primerError.focus();
            }
<<<<<<< HEAD
=======
        } else {
            console.log('Validación final exitosa. Enviando formulario...');
>>>>>>> main
        }
    });

});