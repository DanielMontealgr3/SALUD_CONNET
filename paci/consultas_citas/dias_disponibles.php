<?php
// =================================================================
// === INICIO DEL BLOQUE CORREGIDO (PORTABILIDAD) ===
// =================================================================

// 1. Inclusión de la configuración centralizada.
// Esto establece ROOT_PATH, BASE_URL, inicia sesión y conecta a la BD.
// La ruta sube dos niveles porque se asume que este archivo está en un subdirectorio.
require_once __DIR__ . '/../../include/config.php';

header('Content-Type: application/json');

// La variable de conexión `$con` ya está disponible desde config.php.
// No es necesario crear una nueva instancia de Database.

// NOTA: Este script no valida la sesión. Si es necesario, se deberían añadir
// las líneas de validación de sesión aquí. Por ahora, se omite para no alterar la lógica.

// =================================================================
// === FIN DEL BLOQUE CORREGIDO ===
// El resto del código permanece exactamente igual.
// =================================================================

$doc_medico = $_GET['medico'] ?? null;
$start_str = $_GET['start'] ?? '';
$end_str = $_GET['end'] ?? '';

if (!$doc_medico || !$start_str || !$end_str) {
    echo json_encode([]);
    exit;
}

try {
    // Consulta: trae todas las fechas en rango para ese médico
    $sql = "SELECT fecha_horario, COUNT(*) as total 
            FROM horario_medico 
            WHERE doc_medico = :doc_medico 
              AND fecha_horario >= CURDATE()
              AND fecha_horario BETWEEN :start AND :end
            GROUP BY fecha_horario";

    $stmt = $con->prepare($sql);
    $stmt->execute([
        ':doc_medico' => $doc_medico,
        ':start' => $start_str,
        ':end' => $end_str
    ]);

    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $diasConHoras = [];

    // Por cada fecha, verificamos si tiene al menos una hora disponible (id_estado = 4)
    foreach ($resultado as $row) {
        $fecha = $row['fecha_horario'];

        $sqlHoras = "SELECT COUNT(*) 
                     FROM horario_medico 
                     WHERE doc_medico = :doc_medico 
                       AND fecha_horario = :fecha 
                       AND id_estado = 4";

        $stmtHoras = $con->prepare($sqlHoras);
        $stmtHoras->execute([
            ':doc_medico' => $doc_medico,
            ':fecha' => $fecha
        ]);

        $totalHorasDisponibles = $stmtHoras->fetchColumn();

        // Guardamos la fecha en el array con su disponibilidad
        $diasConHoras[$fecha] = $totalHorasDisponibles > 0;
    }

    echo json_encode($diasConHoras); // Devuelve un objeto { fecha: true/false }

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    error_log("Error en dias_disponibles.php (PDO): " . $e->getMessage());
    echo json_encode([]);
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log("Error general en dias_disponibles.php: " . $e->getMessage());
    echo json_encode([]);
}
?>