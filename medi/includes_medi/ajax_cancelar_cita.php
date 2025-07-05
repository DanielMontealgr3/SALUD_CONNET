<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

// Asegurar que la salida sea siempre JSON
header('Content-Type: application/json');
if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 4) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

$id_cita = isset($_POST['id_cita']) ? (int)$_POST['id_cita'] : 0;

if ($id_cita <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de cita inválido.']);
    exit;
}

try {
    $db = new Database();
    $pdo = $db->conectar();
    $pdo->beginTransaction();

    // Estado 8 = No Asistió
    $sql_cita = "UPDATE citas SET id_est = 8 WHERE id_cita = ?";
    $stmt_cita = $pdo->prepare($sql_cita);
    $stmt_cita->execute([$id_cita]);

    // Estado 4 = Disponible/No asignada
    $sql_horario = "UPDATE horario_medico SET id_estado = 4 WHERE id_horario_med = (SELECT id_horario_med FROM citas WHERE id_cita = ?)";
    $stmt_horario = $pdo->prepare($sql_horario);
    $stmt_horario->execute([$id_cita]);
    
    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("Error en ajax_cancelar_cita: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
}