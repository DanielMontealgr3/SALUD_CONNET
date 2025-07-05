<?php
// 1. INCLUIR CONFIGURACIÓN CENTRAL
// Carga las constantes, inicia la sesión y la conexión a la BD.
require_once __DIR__ . '/../include/config.php';

// 2. INCLUIR LÓGICA CON RUTAS ABSOLUTAS (ROOT_PATH)
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

// 3. LÓGICA DE LA PÁGINA
// La verificación se mantiene, pero la redirección usa BASE_URL.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2) {
    // Redirección corregida
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
    <!-- Los enlaces a CDN (externos) no necesitan cambios -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <?php
    // 4. RUTAS PÚBLICAS (CSS) USANDO BASE_URL
    // Suponiendo que 'estilos.css' está en la carpeta 'paci'.
    ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/paci/estilos.css">
</head>

<body class="d-flex flex-column min-vh-100 cuerpo_citas">
    
    <?php
    // 5. INCLUIR PARTES DE PLANTILLA (HTML) CON ROOT_PATH
    require_once ROOT_PATH . '/include/menu.php';
    ?>

    <main id="contenido-principal" class="flex-grow-1 mb-5">
        <div class="container menu_citas">
            <h1 class="titulo_citas">Selecciona el tipo de cita:</h1>
            <div class="row row-cols-1 row-cols-md-3 g-4 mt-4">
                
                <?php
                // =================================================================
                // 6. ENLACES INTERNOS (href) USANDO BASE_URL
                // Esto asegura que los enlaces funcionen desde cualquier URL.
                // =================================================================
                ?>
                <!-- Cita Médica -->
                <div class="col">
                    <div class="card opcion_cita text-center h-100">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-calendar-plus icono-opcion text-primary mb-2" style="font-size: 3.5rem;"></i>
                            <h5 class="card-title">Cita Médica</h5>
                            <a href="<?php echo BASE_URL; ?>/paci/cita_medica.php" class="btn btn-primary mt-2">Ingresar</a>
                        </div>
                    </div>
                </div>

                <!-- Turno Examen -->
                <div class="col">
                    <div class="card opcion_cita text-center h-100">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-clipboard2-pulse icono-opcion text-success mb-2" style="font-size: 3.5rem;"></i>
                            <h5 class="card-title">Turno Examen</h5>
                            <a href="<?php echo BASE_URL; ?>/paci/cita_examen.php" class="btn btn-primary mt-2">Ingresar</a>
                        </div>
                    </div>
                </div>

                <!-- Turno Medicamentos -->
                <div class="col">
                    <div class="card opcion_cita text-center h-100">
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-capsule-pill icono-opcion text-danger mb-2" style="font-size: 3.5rem;"></i>
                            <h5 class="card-title">Turno Medicamentos</h5>
                            <a href="<?php echo BASE_URL; ?>/paci/pendientes.php" class="btn btn-primary mt-2">Ingresar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php
    // Incluyendo el footer con la ruta absoluta correcta
    require_once ROOT_PATH . '/include/footer.php';
    ?>
</body>
</html>