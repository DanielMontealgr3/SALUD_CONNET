<?php
// INCLUYE EL ARCHIVO DE CONFIGURACIÓN GLOBAL PARA ESTABLECER RUTAS Y CONECTAR A LA BASE DE DATOS.
require __DIR__ . '/include/config.php';
$pageTitle = 'Preguntas Frecuentes'; // Añadido para consistencia
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <!-- CONFIGURACIÓN DEL HEAD DEL DOCUMENTO HTML. -->
    <title>Preguntas Frecuentes</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ENLACE A LA HOJA DE ESTILOS PRINCIPAL, USANDO BASE_URL PARA UNA RUTA CORRECTA. -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/estilo_inicio.css">
    <!-- ENLACE A LIBRERÍA EXTERNA DE ICONOS. -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/Loguito.png">
</head>

<body class="index"> 
    
    <?php 
    // INCLUYE EL MENÚ DE NAVEGACIÓN USANDO LA RUTA ABSOLUTA DEFINIDA EN ROOT_PATH.
    require ROOT_PATH . '/menu_inicio.php'; 
    ?>

    <!-- CAPA SUPERPUESTA PARA EFECTOS VISUALES. -->
    <div class="overlay_blanco"></div>
    
    <!-- CONTENIDO PRINCIPAL DE LA PÁGINA. -->
    <main> 
        <!-- CONTENEDOR PARA LA SECCIÓN DE PREGUNTAS Y RESPUESTAS. -->
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

    <!-- BLOQUE DE JAVASCRIPT PARA LA FUNCIONALIDAD DEL ACORDEÓN DE PREGUNTAS. -->
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

    <?php 
    // INCLUYE EL PIE DE PÁGINA USANDO LA RUTA ABSOLUTA.
    require ROOT_PATH . '/footer_inicio.php'; 
    ?>
    <!-- ENLACE AL SCRIPT DEL MENÚ RESPONSIVO. -->
    <script src="<?php echo BASE_URL; ?>/js/menu_responsivo.js"></script>

</body>

</html>