<?php
require_once '../../include/conexion.php';
require_once '../../include/validar_sesion.php';

header('Content-Type: application/json');
if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit;
}

$cita_ids_json = isset($_GET['ids']) ? $_GET['ids'] : '[]';
$cita_ids = json_decode($cita_ids_json, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($cita_ids) || empty($cita_ids)) {
    echo json_encode(['citas_a_remover' => []]);
    exit;
}

$cita_ids_sanitizadas = array_filter(array_map('intval', $cita_ids), fn($id) => $id > 0);
if (empty($cita_ids_sanitizadas)) {
    echo json_encode(['citas_a_remover' => []]);
    exit;
}

try {
    $db = new Database();
    $pdo = $db->conectar();
    
    $estados_activos = [3, 11];
    $placeholders_estados = implode(',', array_fill(0, count($estados_activos), '?'));
    $placeholders_citas = implode(',', array_fill(0, count($cita_ids_sanitizadas), '?'));
    
    $sql = "SELECT id_cita FROM citas WHERE id_cita IN ($placeholders_citas) AND id_est IN ($placeholders_estados)";
    $stmt = $pdo->prepare($sql);
    $params = array_merge($cita_ids_sanitizadas, $estados_activos);
    $stmt->execute($params);
    $citas_aun_activas_db = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    $citas_a_remover = array_diff($cita_ids_sanitizadas, $citas_aun_activas_db);

    echo json_encode(['citas_a_remover' => array_values($citas_a_remover)]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error en ajax_verificar_estado_citas: " . $e->getMessage());
    echo json_encode(['error' => 'Error de base de datos.']);
}