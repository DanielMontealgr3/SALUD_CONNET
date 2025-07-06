<?php
// --- BLOQUE 1: CONFIGURACIÓN Y SEGURIDAD ---
require_once __DIR__ . '/../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';
// Se ha ELIMINADO la línea: require_once __DIR__ . '/correo_no_asistio.php';

header('Content-Type: application/json; charset=utf-8');

// --- BLOQUE 2: VALIDACIÓN DE PETICIÓN Y TOKEN CSRF ---
$response = ['success' => false, 'message' => 'Acción no válida o datos insuficientes.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['accion']) || !isset($_SESSION['doc_usu']) || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token_farma_lista'] ?? '', $_POST['csrf_token'])) {
    http_response_code(403);
    $response['message'] = 'Error de seguridad o sesión inválida.';
    echo json_encode($response);
    exit;
}

// --- BLOQUE 3: PROCESAMIENTO DE ACCIONES ---
$accion = $_POST['accion'];
$doc_farmaceuta = $_SESSION['doc_usu'];

try {
    global $con;
    if (!$con) {
        throw new Exception("La conexión a la base de datos no está disponible.");
    }
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    switch ($accion) {
        
        case 'llamar_paciente':
            $id_turno = filter_input(INPUT_POST, 'id_turno', FILTER_VALIDATE_INT);
            if (!$id_turno) throw new Exception("ID de turno no válido.");

            $con->beginTransaction();
            
            $stmt_check = $con->prepare("SELECT COUNT(*) FROM vista_televisor WHERE id_turno = ?");
            $stmt_check->execute([$id_turno]);
            $existe = $stmt_check->fetchColumn() > 0;
            
            if ($existe) {
                $stmt = $con->prepare("UPDATE vista_televisor SET id_estado = 1, id_farmaceuta = ?, hora_llamado = NOW() WHERE id_turno = ?");
                $stmt->execute([$doc_farmaceuta, $id_turno]);
            } else {
                $stmt = $con->prepare("INSERT INTO vista_televisor (id_turno, id_farmaceuta, id_estado, hora_llamado) VALUES (?, ?, 1, NOW())");
                $stmt->execute([$id_turno, $doc_farmaceuta]);
            }
            
            $con->commit();
            $response = ['success' => true, 'message' => 'Paciente llamado correctamente.'];
            break;

        case 'paciente_llego':
            $id_turno = filter_input(INPUT_POST, 'id_turno', FILTER_VALIDATE_INT);
            if (!$id_turno) throw new Exception("ID de turno no válido.");

            $con->beginTransaction();
            $stmt_tv = $con->prepare("UPDATE vista_televisor SET id_estado = 11 WHERE id_turno = ? AND id_farmaceuta = ?");
            $stmt_tv->execute([$id_turno, $doc_farmaceuta]);
            
            $stmt_turno = $con->prepare("UPDATE turno_ent_medic SET id_est = 11 WHERE id_turno_ent = ?");
            $stmt_turno->execute([$id_turno]);
            
            $con->commit();
            $response = ['success' => $stmt_tv->rowCount() > 0, 'message' => 'Estado del paciente actualizado.'];
            break;

        case 'marcar_no_asistido':
            $id_turno = filter_input(INPUT_POST, 'id_turno', FILTER_VALIDATE_INT);
            if (!$id_turno) throw new Exception("ID de turno no válido.");

            $con->beginTransaction();
            
            // Solo necesitamos el id del horario para liberarlo.
            $info_query = "SELECT hora_entreg FROM turno_ent_medic WHERE id_turno_ent = ?";
            $stmt_info = $con->prepare($info_query);
            $stmt_info->execute([$id_turno]);
            $paciente_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

            if ($paciente_info) {
                $con->prepare("UPDATE turno_ent_medic SET id_est = 8 WHERE id_turno_ent = ?")->execute([$id_turno]);
                $con->prepare("DELETE FROM vista_televisor WHERE id_turno = ?")->execute([$id_turno]);
                
                if ($paciente_info['hora_entreg']) {
                    $con->prepare("UPDATE horario_farm SET id_estado = 4 WHERE id_horario_farm = ?")->execute([$paciente_info['hora_entreg']]);
                }
                
                // Se ha ELIMINADO la llamada a la función: enviarCorreoNoAsistio(...)
                
                $response = ['success' => true, 'message' => 'Turno marcado como "No Asistido".'];
            } else {
                throw new Exception("No se encontró información del turno para cancelar.");
            }
            
            $con->commit();
            break;

        default:
            throw new Exception("Acción no reconocida.");
            break;
    }
} catch (Exception $e) {
    if (isset($con) && $con->inTransaction()) {
        $con->rollBack();
    }
    http_response_code(500); 
    $response['message'] = $e->getMessage();
    error_log("Error en ajax_gestion_turnos.php: " . $e->getMessage());
}

echo json_encode($response);