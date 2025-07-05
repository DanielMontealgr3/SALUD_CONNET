<?php
// =======================================================================================
// BLOQUE 1: CONFIGURACIÓN CENTRAL
// Se incluye el archivo de configuración. Esto establece la conexión a la base de datos
// ($con) y define las constantes necesarias.
// =======================================================================================
require_once __DIR__ . '/../../include/config.php';

// Establece el tipo de contenido que devolverá el script. Es una API que habla en JSON.
header('Content-Type: application/json');

// =======================================================================================
// BLOQUE 2: OBTENCIÓN Y VALIDACIÓN DE PARÁMETROS
// Se recuperan los datos enviados por GET desde la llamada AJAX en JavaScript.
// =======================================================================================
$doc_medico = $_GET['medico'] ?? null;
$start_str = $_GET['start'] ?? ''; // Fecha de inicio del mes visible en el calendario
$end_str = $_GET['end'] ?? '';   // Fecha de fin del mes visible

// Si falta algún parámetro esencial, se devuelve un JSON vacío.
if (!$doc_medico || !$start_str || !$end_str) {
    echo json_encode(['counts' => []]);
    exit;
}

// =======================================================================================
// BLOQUE 3: LÓGICA DE CONSULTA A LA BASE DE DATOS (MODIFICADA)
// Ahora cuenta los horarios disponibles (id_estado = 4) para cada día.
// =======================================================================================
try {
    // La consulta ahora cuenta (COUNT) los horarios disponibles y los agrupa por fecha.
    $sql = "SELECT fecha_horario, COUNT(id_horario_med) as disponibles
            FROM horario_medico 
            WHERE doc_medico = :doc_medico 
              AND id_estado = 4 -- Solo cuenta los que están disponibles
              AND fecha_horario >= CURDATE() -- Desde hoy en adelante
              AND fecha_horario BETWEEN :start AND :end
            GROUP BY fecha_horario
            HAVING disponibles > 0"; // Solo devuelve fechas con al menos 1 horario libre

    $stmt = $con->prepare($sql);
    $stmt->execute([
        ':doc_medico' => $doc_medico,
        ':start' => $start_str,
        ':end' => $end_str
    ]);

    // PDO::FETCH_KEY_PAIR crea un array asociativo [fecha_horario => disponibles]
    $diasConteo = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Se devuelve un objeto JSON con la clave 'counts'. Ej: {"counts": {"2024-10-28": 5, "2024-10-29": 2}}
    echo json_encode(['counts' => $diasConteo]);

} catch (PDOException $e) {
    // Manejo de errores de base de datos.
    error_log("Error en dias_disponibles.php (PDO): " . $e->getMessage());
    echo json_encode(['counts' => []]); // Devuelve un array vacío en caso de error.
}
?>