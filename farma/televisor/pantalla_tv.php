<?php
// --- BLOQUE 1: CONFIGURACIÓN INICIAL Y SEGURIDAD ---
// Se incluye el archivo de configuración central. La ruta __DIR__ . '/../../' sube dos niveles
// desde 'farma/televisor/' para encontrar la carpeta 'include/'.
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

// --- BLOQUE 2: VERIFICACIÓN DE ROL Y ACCESO ---
// Se verifica que el usuario haya iniciado sesión y que tenga el rol de Administrador (1) o Farmaceuta (3).
// Si no cumple, se le redirige a la página de inicio de sesión.
if (!isset($_SESSION['id_rol']) || !in_array($_SESSION['id_rol'], [1, 3])) {
    header('Location: ' . BASE_URL . '/inicio_sesion.php?error=rol_invalido');
    exit;
}

// --- BLOQUE 3: OBTENCIÓN DE DATOS DE LA FARMACIA ASIGNADA ---
// Se obtiene el NIT de la farmacia desde la sesión. Es crucial para saber qué turnos mostrar.
$nit_farmacia_actual = $_SESSION['nit_farma'] ?? null;
$nombre_farmacia = 'Farmacia';

if ($nit_farmacia_actual) {
    try {
        // La conexión $con ya está disponible desde config.php
        $stmt = $con->prepare("SELECT nom_farm FROM farmacias WHERE nit_farm = ?");
        $stmt->execute([$nit_farmacia_actual]);
        $nombre_farmacia = $stmt->fetchColumn() ?: 'Farmacia';
    } catch (Exception $e) { 
        // En caso de error, se usa un nombre por defecto y se registra el error.
        error_log("Error al obtener nombre de farmacia para TV: " . $e->getMessage());
        $nombre_farmacia = 'Farmacia'; 
    }
} else {
    // Si no se puede determinar la farmacia, se detiene la ejecución.
    die("Error: No se pudo determinar la farmacia asignada. Por favor, inicie sesión como farmaceuta.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- --- BLOQUE 4: METADATOS Y ENLACES CSS/JS DEL HEAD --- -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantalla de Turnos - <?php echo htmlspecialchars($nombre_farmacia); ?></title>
    <!-- Rutas a recursos corregidas con BASE_URL para que el navegador las encuentre. -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/farma/televisor/estilos_tv.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/loguito.png">
</head>
<body>
    <!-- --- BLOQUE 5: CONTENIDO HTML PRINCIPAL --- -->
    <div id="inicio-overlay">
        <button id="btn-iniciar-pantalla">▶ Iniciar Pantalla</button>
    </div>

    <div class="pantalla-tv">
        <header class="header-tv">
            <div class="header-center">
                <!-- Ruta a la imagen del logo corregida con BASE_URL -->
                <img src="<?php echo BASE_URL; ?>/img/Loguito.png" alt="Logo" class="logo-header">
                <h1 class="nombre-farmacia-header"><?php echo htmlspecialchars($nombre_farmacia); ?></h1>
            </div>
            <div id="reloj" class="reloj-container"></div>
        </header>
        <main class="contenido-tv">
            <div class="columna-llamando">
                <div class="columna-header">Llamando a</div>
                <div id="lista-llamando" class="lista-turnos"></div>
            </div>
            <div class="columna-atencion">
                <div class="columna-header">En Atención</div>
                <div id="lista-atencion" class="lista-turnos"></div>
            </div>
        </main>
    </div>

    <!-- Modal de notificación que se activa al llamar un nuevo turno. -->
    <div id="modal-notificacion" class="modal-notificacion-overlay">
        <div class="modal-notificacion-contenido">
            <div class="modal-turno" id="modal-notificacion-turno"></div>
            <div class="modal-paciente" id="modal-notificacion-paciente"></div>
            <div class="modal-destino" id="modal-notificacion-destino"></div>
        </div>
    </div>
    
    <!-- --- BLOQUE 6: SCRIPTS FINALES --- -->
    <!-- Se enlaza el script JS usando BASE_URL para que la ruta sea correcta desde el navegador. -->
    <script src="<?php echo BASE_URL; ?>/farma/js/controlador_tv.js?v=<?php echo time(); ?>"></script>
</body>
</html>