<?php
require_once __DIR__ . '/../../include/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id_rol']) || !in_array($_SESSION['id_rol'], [1, 4])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

$id_detalle = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_detalle) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de detalle no válido.']);
    exit;
}

try {
    $query = "
        SELECT det.*, d.diagnostico AS nombre_diagnostico, enf.nom_enfer, 
               med.nom_medicamento, proc.procedimiento AS nombre_procedimiento 
        FROM detalles_histo_clini det 
        LEFT JOIN diagnostico d ON det.id_diagnostico = d.id_diagnos 
        LEFT JOIN enfermedades enf ON det.id_enferme = enf.id_enferme 
        LEFT JOIN medicamentos med ON det.id_medicam = med.id_medicamento 
        LEFT JOIN procedimientos proc ON det.id_proced = proc.id_proced 
        WHERE det.id_detalle = :id_detalle";
    
    $stmt = $con->prepare($query);
    $stmt->execute([':id_detalle' => $id_detalle]);
    $detalle_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($detalle_data) {
        echo json_encode(['success' => true, 'data' => $detalle_data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Detalle no encontrado.']);
    }

} catch (PDOException $e) {
    error_log("Error en ajax_get_detalle: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
}
?>