<?php
require_once __DIR__ . '/../../include/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id_rol']) || !in_array($_SESSION['id_rol'], [1, 4])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$id_historia = filter_input(INPUT_POST, 'id_historia', FILTER_VALIDATE_INT);
if (!$id_historia) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de Historia no válido.']);
    exit;
}

$id_diagnostico = filter_input(INPUT_POST, 'id_diagnostico', FILTER_VALIDATE_INT) ?: 49;
$id_enferme = filter_input(INPUT_POST, 'id_enferme', FILTER_VALIDATE_INT) ?: 116;
$id_medicam = filter_input(INPUT_POST, 'id_medicam', FILTER_VALIDATE_INT) ?: 40;
$can_medica = trim($_POST['can_medica'] ?? '0');
$prescripcion = trim($_POST['prescripcion'] ?? 'No Aplica');
$id_proced = filter_input(INPUT_POST, 'id_proced', FILTER_VALIDATE_INT) ?: 36;
$cant_proced = filter_input(INPUT_POST, 'cant_proced', FILTER_VALIDATE_INT) ?: 0;

if ($id_medicam == 40) {
    $can_medica = '0';
    $prescripcion = 'No Aplica';
}

$can_medica_int = (int) filter_var($can_medica, FILTER_SANITIZE_NUMBER_INT);

define('ID_ESTADO_REALIZADA', 5);

try {
    $con->beginTransaction();

    $sql_detalle = "INSERT INTO detalles_histo_clini 
                (id_historia, id_diagnostico, id_enferme, id_medicam, can_medica, prescripcion, id_proced, cant_proced) 
            VALUES 
                (:id_historia, :id_diagnostico, :id_enferme, :id_medicam, :can_medica, :prescripcion, :id_proced, :cant_proced)";
    
    $stmt_detalle = $con->prepare($sql_detalle);
    $stmt_detalle->execute([
        ':id_historia' => $id_historia,
        ':id_diagnostico' => $id_diagnostico,
        ':id_enferme' => $id_enferme,
        ':id_medicam' => $id_medicam,
        ':can_medica' => $can_medica_int,
        ':prescripcion' => $prescripcion,
        ':id_proced' => $id_proced,
        ':cant_proced' => $cant_proced
    ]);

    $sql_update_cita = "UPDATE citas SET id_est = :id_est_realizada 
                        WHERE id_cita = (SELECT id_cita FROM historia_clinica WHERE id_historia = :id_historia)";
    $stmt_update = $con->prepare($sql_update_cita);
    $stmt_update->execute([
        ':id_est_realizada' => ID_ESTADO_REALIZADA,
        ':id_historia' => $id_historia
    ]);

    $con->commit();
    
    echo json_encode(['success' => true, 'message' => 'Detalles guardados y cita actualizada.']);

} catch (PDOException $e) {
    $con->rollBack();
    error_log('Error al guardar detalles y actualizar cita: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos.']);
}
?>