<?php
require_once __DIR__ . '/../../include/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 4) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

$id_cita = filter_input(INPUT_POST, 'id_cita', FILTER_VALIDATE_INT);
if (!$id_cita) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de cita inválido.']);
    exit;
}

define('ID_ESTADO_NO_ASISTIO', 8);
define('ID_ESTADO_HORARIO_DISPONIBLE', 4);

try {
    $con->beginTransaction();

    $sql_cita = "UPDATE citas SET id_est = ? WHERE id_cita = ?";
    $stmt_cita = $con->prepare($sql_cita);
    $stmt_cita->execute([ID_ESTADO_NO_ASISTIO, $id_cita]);

    $sql_horario = "UPDATE horario_medico SET id_estado = ? WHERE id_horario_med = (SELECT id_horario_med FROM citas WHERE id_cita = ?)";
    $stmt_horario = $con->prepare($sql_horario);
    $stmt_horario->execute([ID_ESTADO_HORARIO_DISPONIBLE, $id_cita]);
    
    $con->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    http_response_code(500);
    error_log("Error en ajax_cancelar_cita: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos al cancelar la cita.']);
}