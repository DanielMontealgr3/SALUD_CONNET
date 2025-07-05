<?php
// BLOQUE DE CONFIGURACIÓN INICIAL DE LA PÁGINA
// 1. INCLUYE EL ARCHIVO DE CONFIGURACIÓN GLOBAL, QUE CONECTA A LA BD Y DEFINE LAS RUTAS.
require __DIR__ . '/include/config.php';
// 2. ESTABLECE UN TÍTULO ESPECÍFICO PARA ESTA PÁGINA.
$pageTitle = 'Inicio - Salud Connected';
// 3. INCLUYE EL ARCHIVO DEL MENÚ/ENCABEZADO.
require ROOT_PATH . '/menu_inicio.php';
?>

<!-- INICIO DEL CUERPO DEL DOCUMENTO HTML CON UNA CLASE ESPECÍFICA PARA ESTA PÁGINA. -->
<body class="index">
    
    <!-- CAPA SUPERPUESTA, PROBABLEMENTE USADA POR CSS PARA EFECTOS VISUALES. -->
    <div class="overlay"></div>
   
    <!-- CONTENIDO PRINCIPAL DE LA PÁGINA DE INICIO. -->
    <main> 
        <div id="contenido">
            <h1>Conectando salud y propósitos</h1>
            <p>Facilitamos la gestión de citas, medicamentos y procesos médicos en una sola plataforma.</p>  
            <p>Ahorra tiempo y optimiza tu atención con nuestra solución innovadora.</p>
        </div>

        <div class="botones">
            <!-- BOTÓN QUE LLEVA A LA PÁGINA DE INICIO DE SESIÓN, USANDO 'BASE_URL' PARA LA RUTA. -->
            <a href="<?php echo BASE_URL; ?>/inicio_sesion.php" class="boton">Iniciar sesión</a>
        </div>
    </main> 
    
    <?php
    // BLOQUE FINAL DE LA PÁGINA
    // 1. INCLUYE EL ARCHIVO DEL PIE DE PÁGINA.
    require ROOT_PATH . '/footer_inicio.php'; 
    ?>
    <!-- 2. ENLAZA UN ARCHIVO JAVASCRIPT ESPECÍFICO PARA LA FUNCIONALIDAD DEL MENÚ RESPONSIVO. -->
    <script src="<?php echo BASE_URL; ?>/js/menu_responsivo.js"></script>

</body>

</html>