<?php
// =================================================================
// === INICIO DEL BLOQUE CORREGIDO (PORTABILIDAD) ===
// =================================================================

// 1. Inclusión de la configuración centralizada.
// Esto establece ROOT_PATH, BASE_URL, inicia sesión y conecta a la BD.
// La ruta sube dos niveles porque se asume que este archivo está en un subdirectorio (ej. /ajax/medico/).
require_once __DIR__ . '/../../include/config.php';

// Aunque este script no valida una sesión existente, incluir config.php ya
// prepara el entorno de sesión por si se necesitara en el futuro y provee la conexión.

// La variable de conexión `$con` ya está disponible desde config.php.
// No es necesario crear una nueva instancia de Database.

// =================================================================
// === FIN DEL BLOQUE CORREGIDO ===
// El resto del código permanece exactamente igual.
// =================================================================

$nit_ips = $_POST['nit_ips'] ?? '';

if (empty($nit_ips)) {
    echo '<option value="">Seleccione una IPS primero</option>';
    exit;
}

try {
    // --- CONSULTA SQL CORREGIDA ---
    $sql = "SELECT u.doc_usu, u.nom_usu 
            FROM usuarios u
            JOIN asignacion_medico am ON u.doc_usu = am.doc_medico
            WHERE am.nit_ips = :nit_ips 
              AND u.id_rol = 4 
              AND u.id_est = 1
              AND u.id_especialidad = 45
              AND am.id_estado = 1"; // ===== VALIDACIÓN AÑADIDA AQUÍ =====

    $stmt = $con->prepare($sql);
    $stmt->execute([':nit_ips' => $nit_ips]);

    $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($medicos)) {
        echo '<option value="">No hay médicos de Medicina General activos en esta IPS</option>';
    } else {
        echo '<option value="">Seleccione un médico</option>';
        foreach ($medicos as $medico) {
            echo '<option value="' . htmlspecialchars($medico['doc_usu']) . '">' . htmlspecialchars($medico['nom_usu']) . '</option>';
        }
    }

} catch (PDOException $e) {
    error_log("Error en medico.php: " . $e->getMessage());
    echo '<option value="">Error al cargar médicos</option>';
}
?>