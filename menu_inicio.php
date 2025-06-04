<?php
$basePath = $basePath ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/estilo_inicio.css"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title><?php echo $pageTitle ?? 'Salud Connect'; ?></title>
</head>
<body>

    <div id="wrapper">

        <div id="logo-wrapper">
            <a href="<?php echo $basePath; ?>index.php"><img src="<?php echo $basePath; ?>img/Loguito.png" alt="Logo Salud Connected"></a>
        </div>

        <div id="menu-wrapper">
            <nav>
                <ul id="main-menu">
                    <li><a href="<?php echo $basePath; ?>index.php">Inicio</a></li>
                    <li><a href="<?php echo $basePath; ?>sobre_nosotros.php">Sobre nosotros</a></li>
                    <li><a href="<?php echo $basePath; ?>preguntas.php">Preguntas frecuentes</a></li>
                    <li><a href="<?php echo $basePath; ?>inicio_sesion.php">Iniciar Sesión</a></li>
                </ul>
            </nav>
        </div>

        <button class="menu-toggle" aria-label="Alternar menú" aria-expanded="false" aria-controls="main-menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

    </div>
