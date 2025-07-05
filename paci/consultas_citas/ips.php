<?php
// =================================================================
// === INICIO DEL BLOQUE CORREGIDO (PORTABILIDAD) ===
// =================================================================

// 1. Inclusión de la configuración centralizada.
// Esto establece ROOT_PATH, BASE_URL, inicia sesión y conecta a la BD.
// La ruta sube dos niveles porque se asume que este archivo está en un subdirectorio (ej. /ajax/ips/).
require_once __DIR__ . '/../../include/config.php';

// 2. Inclusión del script de validación de sesión usando ROOT_PATH.
require_once ROOT_PATH . '/include/validar_sesion.php';

// 3. Validación de sesión.
// La redirección no es posible en AJAX, por lo que se devuelve un error.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['id_rol'] != 2) {
    // Es importante enviar un código de estado HTTP adecuado para errores de autorización.
    http_response_code(403); // Forbidden
    echo '<option value="">Error: Sesión no válida</option>';
    exit;
}

$doc_usuario = $_SESSION['doc_usu'];

// La variable de conexión `$con` ya está disponible desde config.php.
// No es necesario crear una nueva instancia de Database.

// =================================================================
// === FIN DEL BLOQUE CORREGIDO ===
// El resto del código permanece exactamente igual.
// =================================================================

try {
    $stmt = $con->prepare("SELECT i.Nit_IPS, i.nom_ips 
                           FROM usuarios u 
                           INNER JOIN barrio b ON u.id_barrio = b.id_barrio 
                           INNER JOIN afiliados a ON u.doc_usu = a.doc_afiliado 
                           INNER JOIN detalle_eps_ips dei ON a.id_eps = dei.nit_eps 
                           INNER JOIN ips i ON dei.nit_ips = i.Nit_IPS 
                           WHERE u.doc_usu = ? AND i.ubicacion_mun = b.id_mun");
    $stmt->execute([$doc_usuario]);
    $ips_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<option value=''>Seleccione una IPS</option>";
    if (empty($ips_list)) {
        echo "<option value='' disabled>No hay IPS disponibles para su municipio y EPS</option>";
    } else {
        foreach ($ips_list as $row) {
            // Es buena práctica usar htmlspecialchars para evitar problemas si los nombres contienen caracteres especiales.
            echo "<option value='" . htmlspecialchars($row['Nit_IPS']) . "'>" . htmlspecialchars($row['nom_ips']) . "</option>";
        }
    }
} catch (PDOException $e) {
    // Para errores del servidor, es mejor usar el código de estado 500.
    http_response_code(500); // Internal Server Error
    // Es una mala práctica de seguridad mostrar el mensaje de error de la BD al usuario.
    // Se loguea para el desarrollador y se muestra un mensaje genérico.
    error_log("Error al cargar IPS: " . $e->getMessage());
    echo "<option value='' disabled>Error al cargar las IPS</option>";
}
?>