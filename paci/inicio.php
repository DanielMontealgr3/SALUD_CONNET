<?php
// =================================================================
// 1. INCLUIR LA CONFIGURACIÓN CENTRAL
// Esta es la línea MÁS IMPORTANTE. Debe ir primero.
// Define ROOT_PATH y BASE_URL y también inicia la sesión y la conexión a la BD.
// =================================================================
require_once __DIR__ . '/../include/config.php';

// =================================================================
// 2. INCLUIR ARCHIVOS DE LÓGICA USANDO ROOT_PATH
// Ya no se usan rutas relativas como '../'.
// Nota: conexion.php y session_start() ya no son necesarios aquí, 
// porque config.php ya se encarga de ellos.
// =================================================================
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

// =================================================================
// 3. LÓGICA DE LA PÁGINA (verificación de rol, etc.)
// =================================================================
// Esta lógica se mantiene, pero la redirección ahora usa BASE_URL.
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
                    // =================================================================
                    ?>
                    <img src="<?php echo BASE_URL; ?>/img/bodyadmi.png" alt="Imagen Paciente" class="imagen-rol img-fluid rounded" style="max-width: 500px; height: auto;">
                </div>
            </div>
        </div>
    </main>

    <?php
    // Incluyendo el footer con la ruta correcta
    require_once ROOT_PATH . '/include/footer.php';
    ?>

</body>
</html>