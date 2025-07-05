<?php
// =================================================================
// 1. INCLUSIÓN DE CONFIGURACIÓN CENTRALIZADA (PORTABLE)
// Esto establece ROOT_PATH, BASE_URL, inicia la sesión y conecta a la BD.
// =================================================================
require_once __DIR__ . '/../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

// =================================================================
// 2. VALIDACIÓN DE ROL Y SESIÓN
// Aseguramos que solo los pacientes (rol ID 2) logueados puedan acceder.
// La redirección ahora usa BASE_URL para ser portable.
// =================================================================
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2) {
    header('Location: ' . BASE_URL . '/inicio_sesion.php');
    exit;
}

$pageTitle = "Seleccionar Cita";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $pageTitle; ?></title>
    <!--
    // =================================================================
    // 3. ENLACES A RECURSOS (CSS, JS) USANDO BASE_URL
    // Aunque uses un CDN para Bootstrap, es una buena práctica usar
    // BASE_URL para tus propios archivos de estilos.
    // =================================================================
    -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Ajustado para usar BASE_URL -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/paci/estilos.css?v=<?php echo time(); ?>">
</head>

<?php
// =================================================================
// 4. INCLUSIÓN DE LA PLANTILLA HTML USANDO RUTAS ABSOLUTAS
// =================================================================
require_once ROOT_PATH . '/include/menu.php';
?>

<body class="d-flex flex-column min-vh-100 cuerpo_citas">
    <main id="contenido-principal" class="flex-grow-1 mb-5">
        <div class="container menu_citas mt-5 pt-5">
            <h1 class="titulo_citas text-center">Selecciona el tipo de cita:</h1>
            <div class="row row-cols-1 row-cols-md-3 g-4 mt-4 justify-content-center">

                <!-- Cita Médica -->
                <div class="col">
                    <div class="card opcion_cita text-center h-100 shadow-sm">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-calendar-plus icono-opcion text-primary mb-2" style="font-size: 3.5rem;"></i>
                            <h5 class="card-title">Cita Médica</h5>
                            <!-- Ajustado para usar BASE_URL -->
                            <a href="<?php echo BASE_URL; ?>/paci/cita_medica.php" class="btn btn-primary mt-2">Ingresar</a>
                        </div>
                    </div>
                </div>

                <!-- Turno Examen -->
                <div class="col">
                    <div class="card opcion_cita text-center h-100 shadow-sm">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-clipboard2-pulse icono-opcion text-success mb-2" style="font-size: 3.5rem;"></i>
                            <h5 class="card-title">Turno Examen</h5>
                            <!-- Ajustado para usar BASE_URL -->
                            <a href="<?php echo BASE_URL; ?>/paci/cita_examen.php" class="btn btn-primary mt-2">Ingresar</a>
                        </div>
                    </div>
                </div>

                <!-- Turno Medicamentos -->
                <div class="col">
                    <div class="card opcion_cita text-center h-100 shadow-sm">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-capsule-pill icono-opcion text-danger mb-2" style="font-size: 3.5rem;"></i>
                            <h5 class="card-title">Turno Medicamentos</h5>
                            <!-- Ajustado para usar BASE_URL -->
                            <a href="<?php echo BASE_URL; ?>/paci/pendientes.php" class="btn btn-primary mt-2">Ingresar</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <?php require_once ROOT_PATH . '/include/footer.php'; ?>
</body>
</html>