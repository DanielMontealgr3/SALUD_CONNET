<?php
require_once '../../include/conexion.php';
require_once '../../include/validar_sesion.php';

header('Content-Type: application/json');
if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 4) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit;
}

$ids_en_pantalla_json = isset($_GET['ids']) ? $_GET['ids'] : '[]';
$ids_en_pantalla = json_decode($ids_en_pantalla_json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $ids_en_pantalla = [];
}

$doc_medico_logueado = $_SESSION['doc_usu'];

try {
    $db = new Database();
    $pdo = $db->conectar();

    // 1. Obtener TODAS las citas que DEBERÍAN estar en pantalla
    $sql_todas_activas = "SELECT c.id_cita, c.doc_pac, c.id_est, e.nom_est, up.nom_usu AS nom_paciente, hm.fecha_horario, hm.horario 
                          FROM citas c 
                          JOIN estado e ON c.id_est = e.id_est 
                          JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med
                          LEFT JOIN usuarios up ON c.doc_pac = up.doc_usu
                          WHERE hm.doc_medico = ? AND hm.fecha_horario = CURDATE() AND c.id_est IN (3, 11)";
    $stmt_todas = $pdo->prepare($sql_todas_activas);
    $stmt_todas->execute([$doc_medico_logueado]);
    $citas_activas_db = $stmt_todas->fetchAll(PDO::FETCH_ASSOC);

    $ids_activos_db = array_map(fn($cita) => $cita['id_cita'], $citas_activas_db);

    // 2. Calcular qué citas remover y qué citas agregar
    $citas_a_remover = array_values(array_diff($ids_en_pantalla, $ids_activos_db));
    $ids_citas_a_agregar = array_values(array_diff($ids_activos_db, $ids_en_pantalla));

    $citas_a_agregar = [];
    if (!empty($ids_citas_a_agregar)) {
        foreach ($citas_activas_db as $cita) {
            if (in_array($cita['id_cita'], $ids_citas_a_agregar)) {
                // Formatear la hora para el cliente
                $cita['hora_formateada'] = date('h:i A', strtotime($cita['horario']));
                $citas_a_agregar[] = $cita;
            }
        }
    }
    
    // 3. Enviar la respuesta
    echo json_encode([
        'success' => true,
        'citas_a_remover' => $citas_a_remover,
        'citas_a_agregar' => $citas_a_agregar
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Error en ajax_gestionar_citas_tiempo_real: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
}