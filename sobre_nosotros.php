<?php
// INCLUYE EL ARCHIVO DE CONFIGURACIÓN GLOBAL PARA ESTABLECER RUTAS Y CONECTAR A LA BASE DE DATOS.
require __DIR__ . '/include/config.php';
$pageTitle = 'Sobre Nosotros'; // Añadido para consistencia
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <!-- CONFIGURACIÓN DEL HEAD DEL DOCUMENTO HTML. -->
    <title>Sobre Nosotros</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ENLACE A LA HOJA DE ESTILOS PRINCIPAL, USANDO BASE_URL PARA UNA RUTA CORRECTA. -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/estilo_inicio.css">
    <!-- ENLACE A LIBRERÍA EXTERNA DE ICONOS. -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/loguito.png">
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
        <!-- CONTENEDOR PARA LA INFORMACIÓN DE "SOBRE NOSOTROS". -->
        <div id="info-nosotros">

            <!-- BLOQUE PARA LA VISIÓN DE LA EMPRESA. -->
            <div class="recuadro">
                <img src="<?php echo BASE_URL; ?>/img/recuadros/vision.jpg" alt="Vision">
                <h3>Vision</h3>
                <p>Mejorar la eficiencia y la atención al cliente en la farmacia a través de un software innovador y fácil de usar.</p>
            </div>

            <!-- BLOQUE PARA LA MISIÓN DE LA EMPRESA. -->
            <div class="recuadro">
                <img src="<?php echo BASE_URL; ?>/img/recuadros/mision.jpg" alt="Mision">
                <h3>Misión</h3>
                <p>Proporcionar herramientas tecnológicas para agilizar operaciones diarias y mejorar el servicio farmacéutico.</p>
            </div>

            <!-- BLOQUE PARA PRESENTAR AL EQUIPO DE DESARROLLO. -->
            <div class="recuadro">
                <img src="<?php echo BASE_URL; ?>/img/recuadros/quienes.jpg" alt="Quienes somos">
                <h3>¿Quiénes somos?</h3>
                <p><strong>Product Owner:</strong> Daniel Montealegre</p>
                <p><strong>Scrum Master:</strong> Brian Rocha</p>
                <p><strong>Dev Team:</strong> Daniel Montealegre, Asly Murillo, Brian Rocha</p>
            </div>

        </div>
    </main>

    <?php 
    // INCLUYE EL PIE DE PÁGINA USANDO LA RUTA ABSOLUTA.
    require ROOT_PATH . '/footer_inicio.php'; 
    ?>
    <!-- ENLACE AL SCRIPT DEL MENÚ RESPONSIVO. -->
    <script src="<?php echo BASE_URL; ?>/js/menu_responsivo.js"></script>

</body>

</html>