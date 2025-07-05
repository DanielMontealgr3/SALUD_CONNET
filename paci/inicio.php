<?php
// =================================================================
// 1. INCLUIR LA CONFIGURACIÓN CENTRAL
// Esta es la línea MÁS IMPORTANTE. Debe ir primero.
// Se corrige _DIR_ por __DIR__ (con dos guiones bajos).
// =================================================================
require_once __DIR__ . '/../include/config.php';

// =================================================================
// 2. INCLUIR ARCHIVOS DE LÓGICA USANDO ROOT_PATH
// Esta parte ya estaba correcta. config.php se encarga de iniciar
// la sesión y la conexión a la BD, por lo que no se necesitan aquí.
// =================================================================
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

// =================================================================
// 3. LÓGICA DE LA PÁGINA (verificación de rol, etc.)
// El uso de BASE_URL para la redirección es perfecto para que funcione
// tanto en local como en producción.
// =================================================================
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2 || !isset($_SESSION['nombre_usuario'])) {
    // Redirección corregida con BASE_URL
    header('Location: ' . BASE_URL . '/inicio_sesion.php');
    exit;
}

$nombre_usuario = $_SESSION['nombre_usuario'];
$pageTitle = "Inicio Paciente";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php
    // =================================================================
    // 4. INCLUIR PARTES DE LA PLANTILLA (HTML) USANDO ROOT_PATH
    // Esto es correcto para inclusiones del lado del servidor.
    // =================================================================
    require_once ROOT_PATH . '/include/menu.php';
    ?>
</head>
<body class="d-flex flex-column min-vh-100">

    <main id="contenido-principal" class="flex-grow-1 mb-5">
        <div class="container mt-5 pt-5">
            <div class="contenedor-bienvenida text-center">
                <h1 class="mensaje-bienvenida display-5 mb-4">
                    Bienvenido Paciente, <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>
                </h1>
                <div class="contenedor-imagen-rol">
                    <?php
                    // =================================================================
                    // 5. RUTAS DE RECURSOS PÚBLICOS (imágenes, CSS, JS) USANDO BASE_URL
                    // Esto es perfecto para recursos que el navegador necesita cargar.
                    // =================================================================
                    ?>
                    <img src="<?php echo BASE_URL; ?>/img/bodyadmi.png" alt="Imagen Paciente" class="imagen-rol img-fluid rounded" style="max-width: 500px; height: auto;">
                </div>
            </div>
        </div>
    </main>

    <?php
    // Incluyendo el footer con la ruta correcta del servidor.
    require_once ROOT_PATH . '/include/footer.php';
    ?>

</body>
</html>