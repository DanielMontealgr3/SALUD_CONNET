<?php
// =================================================================
// === INICIO DEL BLOQUE CORREGIDO (PORTABILIDAD) ===
// =================================================================

// 1. Inclusión de la configuración centralizada.
// Esto establece ROOT_PATH, BASE_URL, inicia sesión y conecta a la BD.
// La ruta sube dos niveles porque se asume que este archivo está en un subdirectorio.
require_once __DIR__ . '/../../include/config.php';

// 2. Inclusión del script de validación de sesión usando ROOT_PATH.
require_once ROOT_PATH . '/include/validar_sesion.php';

// 3. Validación de sesión.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['id_rol'] != 2) {
    // Es importante enviar un código de estado HTTP adecuado para errores de autorización.
    http_response_code(403); // Forbidden
    echo "<option value=''>Error: Sesión no válida</option>";
    exit;
}

// La variable de conexión `$con` ya está disponible desde config.php.
// No es necesario crear una nueva instancia de Database.

// =================================================================
// === FIN DEL BLOQUE CORREGIDO ===
// El resto del código permanece exactamente igual.
// =================================================================

try {
    $stmtFarmacias = $con->prepare("SELECT nit_farm, nom_farm, direc_farm FROM farmacias ORDER BY nom_farm ASC");
    $stmtFarmacias->execute();
    $farmacias = $stmtFarmacias->fetchAll(PDO::FETCH_ASSOC);
    // error_log("Available pharmacies from farmacias: " . json_encode($farmacias)); // Esto es útil para depurar, se puede dejar o quitar.

    if (empty($farmacias)) {
        echo "<option value=''>No hay farmacias registradas</option>";
    } else {
        // Se añade un option inicial para que el usuario deba seleccionar activamente.
        echo "<option value=''>Seleccione una farmacia</option>";
        foreach ($farmacias as $row) {
            // Es buena práctica usar htmlspecialchars para los valores y el texto.
            $nombre_direccion = htmlspecialchars($row['nom_farm']) . " (" . htmlspecialchars($row['direc_farm']) . ")";
            echo "<option value='" . htmlspecialchars($row['nit_farm']) . "'>$nombre_direccion</option>";
        }
    }
} catch (PDOException $e) {
    // Para errores del servidor, es mejor usar el código de estado 500.
    http_response_code(500); // Internal Server Error
    // Es una mala práctica de seguridad mostrar el mensaje de error de la BD al usuario.
    // Se loguea para el desarrollador y se muestra un mensaje genérico.
    error_log("Error al cargar farmacias: " . $e->getMessage());
    echo "<option value='' disabled>Error al cargar las farmacias</option>";
}
?>