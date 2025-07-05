<?php
require_once __DIR__ . '/../../include/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 4) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

$ids_en_pantalla_json = $_GET['ids'] ?? '[]';
$ids_en_pantalla = json_decode($ids_en_pantalla_json, true);
if (json_last_error() !== JSON_ERROR_NONE) $ids_en_pantalla = [];

$doc_medico_logueado = $_SESSION['doc_usu'];
define('ID_ESTADO_PROGRAMADA', 3);
define('ID_ESTADO_LISTA_PARA_LLAMAR', 10);
define('ID_ESTADO_EN_PROCESO', 11);

try {
    // === INICIO DEL BLOQUE CORREGIDO (SQL) ===
    // Se añade GROUP BY para asegurar que no se devuelvan citas duplicadas.
    $sql_todas_activas = "SELECT 
                              c.id_cita, c.doc_pac, c.id_est, e.nom_est, 
                              up.nom_usu AS nom_paciente, hm.fecha_horario, hm.horario 
                          FROM citas c 
                          JOIN estado e ON c.id_est = e.id_est 
                          JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med
                          LEFT JOIN usuarios up ON c.doc_pac = up.doc_usu
                          WHERE hm.doc_medico = ? AND hm.fecha_horario = CURDATE() AND c.id_est IN (?, ?, ?)
                          GROUP BY c.id_cita, c.doc_pac, c.id_est, e.nom_est, up.nom_usu, hm.fecha_horario, hm.horario";
    // === FIN DEL BLOQUE CORREGIDO (SQL) ===
                          
    $stmt_todas = $con->prepare($sql_todas_activas);
    $stmt_todas->execute([$doc_medico_logueado, ID_ESTADO_PROGRAMADA, ID_ESTADO_LISTA_PARA_LLAMAR, ID_ESTADO_EN_PROCESO]);
    $citas_activas_db = $stmt_todas->fetchAll(PDO::FETCH_ASSOC);

    $ids_activos_db = array_column($citas_activas_db, 'id_cita');

    $citas_a_remover = array_values(array_diff($ids_en_pantalla, $ids_activos_db));
    $ids_citas_a_agregar = array_diff($ids_activos_db, $ids_en_pantalla);
    
    $citas_a_agregar = [];
    if (!empty($ids_citas_a_agregar)) {
        foreach ($citas_activas_db as $cita) {
            if (in_array($cita['id_cita'], $ids_citas_a_agregar)) {
                $cita['hora_formateada'] = date('h:i A', strtotime($cita['horario']));
                $citas_a_agregar[] = $cita;
            }
        }
    }
    
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