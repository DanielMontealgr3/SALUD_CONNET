<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

// Asegurar que la salida sea siempre JSON
header('Content-Type: application/json');
if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['id_rol'] != 4) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

$id_cita = isset($_POST['id_cita']) ? (int)$_POST['id_cita'] : 0;
if ($id_cita <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de cita inválido.']);
    exit;
}

define('ID_ESTADO_EN_PROCESO', 11);

try {
    $db = new Database();
    $pdo = $db->conectar();
    
    // Solo actualiza si está en estado 'Asignada' (3)
    $sql = "UPDATE citas SET id_est = ? WHERE id_cita = ? AND id_est = 3";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([ID_ESTADO_EN_PROCESO, $id_cita]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        // Puede que ya se haya procesado o el estado inicial no era 'Asignada'
        $check_sql = "SELECT id_est FROM citas WHERE id_cita = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$id_cita]);
        $current_state = $check_stmt->fetchColumn();

        if($current_state == ID_ESTADO_EN_PROCESO){
            // Si ya está en proceso, lo consideramos un éxito para el JS.
            echo json_encode(['success' => true]);
        } else {
             echo json_encode(['success' => false, 'message' => 'La cita no se pudo actualizar. Es posible que ya haya sido procesada o cancelada.']);
        }
    }
} catch (PDOException $e) {
    error_log("Error en ajax_cambiar_estado_cita.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
}