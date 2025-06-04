<!DOCTYPE html>
<html lang="es">

<head>
    <title>Preguntas Frecuentes</title>
</head>

<body class="index"> 
    
    <?php include 'menu_inicio.php'; ?>

    <div class="overlay_blanco"></div>
    
    <main> 
        <div class="preguntas-f">

            <div class="pre-f">
                <div class="pregunta-f">¿Qué es Salud Connected?</div>
                <div class="respuesta-f">Salud Connected es un software innovador diseñado para optimizar los procesos en el área médica.</div>
            </div>

            <div class="pre-f">
                <div class="pregunta-f">¿Cómo puedo registrarme?</div>
                <div class="respuesta-f">Puedes registrarte desde la opción "Registrarse" proporcionando tu información personal.</div>
            </div>

            <div class="pre-f">
                <div class="pregunta-f">¿Es segura la información almacenada?</div>
                <div class="respuesta-f">Sí, nuestra plataforma cumple con altos estándares de seguridad para proteger tus datos.</div>
            </div>

            <div class="pre-f">
                <div class="pregunta-f">¿Como puedo solicitar una cita medica?</div>
                <div class="respuesta-f"> Puedes solicitar tus citas médicas desde Oficina Virtual con tu usuario y clave. Alli­ podras solicitar, cancelar citas, imprimir tu certificado de afiliación, tu carné y realizar otros trámites.</div>
            </div>

            <div class="pre-f">
                <div class="pregunta-f">¿Como puedo modificar o cancelar una cita medica?</div>
                <div class="respuesta-f">Puedes cambiar tu cita directamente en Oficina Virtual o en nuestra APP en el menú citas médicas.</div>
            </div>

            <div class="pre-f">
                <div class="pregunta-f">¿Cómo descargar o consultar los resultados de laboratorio?</div>
                <div class="respuesta-f">Para ver los resultados de laboratorios debes ingresar a nuestra Oficina Virtual o la APP con tu usuario y clave, dirígete al menú «Resultados.</div>
            </div>

        </div>
    </main> 

    <script>
        const preguntas = document.querySelectorAll('.pregunta-f');
        preguntas.forEach(pregunta => {
            pregunta.addEventListener('click', () => {
                const respuesta = pregunta.nextElementSibling;
                const isVisible = respuesta.style.display === 'block';
                
                preguntas.forEach(otraPregunta => {
                    otraPregunta.nextElementSibling.style.display = 'none';
                });

                respuesta.style.display = isVisible ? 'none' : 'block';
            });
        });
    </script>

    <?php include 'footer_inicio.php'; ?>
    <script src="js/menu_responsivo.js"></script>

</body>

</html>