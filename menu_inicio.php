<!DOCTYPE html>
<html lang="es">
<head>
    <!-- CONFIGURACIÓN BÁSICA DEL DOCUMENTO HTML -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ICONO DE LA PESTAÑA DEL NAVEGADOR (FAVICON). USA 'BASE_URL' PARA QUE LA RUTA SEA UNIVERSAL. -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/Loguito.png">

    <!-- ENLACE A LA HOJA DE ESTILOS CSS. USA 'BASE_URL' PARA QUE LA RUTA FUNCIONE TANTO EN LOCAL COMO EN EL HOSTING. -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/estilo_inicio.css">
    
    <!-- ENLACE A UNA LIBRERÍA EXTERNA DE ICONOS (BOOTSTRAP ICONS). -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- TÍTULO DE LA PÁGINA. MUESTRA LA VARIABLE '$pageTitle' SI EXISTE, O 'Salud Connect' POR DEFECTO. -->
    <title><?php echo $pageTitle ?? 'Salud Connect'; ?></title>
</head>

    <!-- ESTRUCTURA PRINCIPAL DEL ENCABEZADO -->
    <div id="wrapper">

        <!-- SECCIÓN DEL LOGOTIPO. USA 'BASE_URL' PARA ASEGURAR QUE EL ENLACE Y LA IMAGEN APUNTEN A LA RAÍZ. -->
        <div id="logo-wrapper">
            <a href="<?php echo BASE_URL; ?>/index.php"><img src="<?php echo BASE_URL; ?>/img/Loguito.png" alt="Logo Salud Connected"></a>
        </div>

        <!-- SECCIÓN DEL MENÚ DE NAVEGACIÓN. USA 'BASE_URL' EN TODOS LOS ENLACES. -->
        <div id="menu-wrapper">
            <nav>
                <ul id="main-menu">
                    <li><a href="<?php echo BASE_URL; ?>/index.php">Inicio</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/sobre_nosotros.php">Sobre nosotros</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/preguntas.php">Preguntas frecuentes</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/inicio_sesion.php">Iniciar Sesión</a></li>
                </ul>
            </nav>
        </div>

        <!-- BOTÓN PARA EL MENÚ RESPONSIVO (HAMBURGUESA), VISIBLE EN DISPOSITIVOS MÓVILES. -->
        <button class="menu-toggle" aria-label="Alternar menú" aria-expanded="false" aria-controls="main-menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

    </div>