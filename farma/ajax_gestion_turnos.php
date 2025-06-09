<?php
require_once '../include/validar_sesion.php';
require_once '../include/conexion.php';
require_once 'correo_no_asistio.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Acción no válida o datos insuficientes.'];

if (!isset($_POST['accion']) || !isset($_SESSION['doc_usu']) || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token_farma_lista'], $_POST['csrf_token'])) {
    $response['message'] = 'Error de seguridad o sesión inválida.';
    echo json_encode($response);
    exit;
}

$accion = $_POST['accion'];
$doc_farmaceuta = $_SESSION['doc_usu'];
$pdo = (new database())->conectar();

if ($accion === 'llamar_paciente' && isset($_POST['id_turno'])) {
    $id_turno = filter_var($_POST['id_turno'], FILTER_VALIDATE_INT);
    if ($id_turno) {
        try {
            $pdo->beginTransaction();
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM vista_televisor WHERE id_turno = ?");
            $stmt_check->execute([$id_turno]);

            if ($stmt_check->fetchColumn() == 0) {
                $stmt_insert = $pdo->prepare("INSERT INTO vista_televisor (id_turno, id_farmaceuta, id_estado, hora_llamado) VALUES (?, ?, 1, NOW())");
                $stmt_insert->execute([$id_turno, $doc_farmaceuta]);
            } else { 
                $stmt_update = $pdo->prepare("UPDATE vista_televisor SET id_estado = 1, id_farmaceuta = ?, hora_llamado = NOW() WHERE id_turno = ?");
                $stmt_update->execute([$doc_farmaceuta, $id_turno]);
            }
            $pdo->commit();
            $response['success'] = true;
        } catch (PDOException $e) { $pdo->rollBack(); $response['message'] = 'Error de base de datos.'; }
    }
} elseif ($accion === 'paciente_llego' && isset($_POST['id_turno'])) {
    $id_turno = filter_var($_POST['id_turno'], FILTER_VALIDATE_INT);
    if ($id_turno) {
        try {
            $pdo->beginTransaction();
            $stmt_tv = $pdo->prepare("UPDATE vista_televisor SET id_estado = 11 WHERE id_turno = ? AND id_farmaceuta = ?");
            $stmt_tv->execute([$id_turno, $doc_farmaceuta]);
            $stmt_turno = $pdo->prepare("UPDATE turno_ent_medic SET id_est = 11 WHERE id_turno_ent = ?");
            $stmt_turno->execute([$id_turno]);
            $pdo->commit();
            $response['success'] = $stmt_tv->rowCount() > 0;
        } catch (PDOException $e) { $pdo->rollBack(); $response['message'] = 'Error de base de datos.'; }
    }
} elseif ($accion === 'marcar_no_asistido' && isset($_POST['id_turno'])) {
    $id_turno = filter_var($_POST['id_turno'], FILTER_VALIDATE_INT);
    if ($id_turno) {
        try {
            $pdo->beginTransaction();
            $info_query = "SELECT u.correo_usu, u.nom_usu, hf.horario, m.periodo, vt.hora_llamado FROM turno_ent_medic tem JOIN historia_clinica hc ON tem.id_historia = hc.id_historia JOIN citas ci ON hc.id_cita = ci.id_cita JOIN usuarios u ON ci.doc_pac = u.doc_usu JOIN horario_farm hf ON tem.hora_entreg = hf.id_horario_farm LEFT JOIN meridiano m ON hf.meridiano = m.id_periodo LEFT JOIN vista_televisor vt ON tem.id_turno_ent = vt.id_turno WHERE tem.id_turno_ent = ?";
            $stmt_info = $pdo->prepare($info_query);
            $stmt_info->execute([$id_turno]);
            if ($paciente_info = $stmt_info->fetch(PDO::FETCH_ASSOC)) {
                $pdo->prepare("UPDATE turno_ent_medic SET id_est = 8 WHERE id_turno_ent = ?")->execute([$id_turno]);
                $pdo->prepare("DELETE FROM vista_televisor WHERE id_turno = ?")->execute([$id_turno]);
                $hora_programada_str = date("h:i A", strtotime($paciente_info['horario']));
                $hora_llamado_str = date("h:i A", strtotime($paciente_info['hora_llamado']));
                enviarCorreoNoAsistio($paciente_info['correo_usu'], $paciente_info['nom_usu'], $id_turno, $hora_programada_str, $hora_llamado_str);
                $response['success'] = true;
            }
            $pdo->commit();
        } catch (PDOException $e) { $pdo->rollBack(); $response['message'] = 'Error al cancelar turno.'; }
    }
}

echo json_encode($response);
?>