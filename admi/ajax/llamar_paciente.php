<?php
require_once '../include/validar_sesion.php';
require_once '../include/conexion.php';

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Error desconocido.'];

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true ||
    !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 3 ||
    !isset($_SESSION['doc_usu']) ||
    !isset($_POST['id_turno']) ||
    !isset($_POST['doc_paciente']) ||
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token_farma_lista']) ||
    $_POST['csrf_token'] !== $_SESSION['csrf_token_farma_lista']
) {
    $response['message'] = 'Acceso no autorizado o datos incompletos.';
    echo json_encode($response);
    exit;
}

$id_turno_ent = filter_var($_POST['id_turno'], FILTER_SANITIZE_NUMBER_INT);
$doc_paciente_llamado = filter_var($_POST['doc_paciente'], FILTER_SANITIZE_STRING);
$doc_farmaceuta_actual = $_SESSION['doc_usu'];

$nit_farmacia_actual = null;
if (isset($con) && $con instanceof PDO) {
    $stmt_farma_nit = $con->prepare("SELECT nit_farma FROM asignacion_farmaceuta WHERE doc_farma = :doc_farma AND id_estado = 1 LIMIT 1");
    $stmt_farma_nit->bindParam(':doc_farma', $doc_farmaceuta_actual, PDO::PARAM_STR);
    $stmt_farma_nit->execute();
    $farma_data = $stmt_farma_nit->fetch(PDO::FETCH_ASSOC);
    if ($farma_data) {
        $nit_farmacia_actual = $farma_data['nit_farma'];
    }
}

if (!$nit_farmacia_actual) {
    $response['message'] = 'No se pudo determinar la farmacia del usuario.';
    echo json_encode($response);
    exit;
}

if (isset($con) && $con instanceof PDO) {
    try {
        $con->beginTransaction();

        $sql_check = "SELECT COUNT(*) FROM vista_televisor WHERE id_turno = :id_turno AND nit_farma = :nit_farma";
        $stmt_check = $con->prepare($sql_check);
        $stmt_check->bindParam(':id_turno', $id_turno_ent, PDO::PARAM_INT);
        $stmt_check->bindParam(':nit_farma', $nit_farmacia_actual, PDO::PARAM_STR);
        $stmt_check->execute();
        if ($stmt_check->fetchColumn() > 0) {
            $response['message'] = 'Este paciente ya está siendo atendido o llamado en esta farmacia.';
            $response['already_called'] = true;
            $con->rollBack();
            echo json_encode($response);
            exit;
        }

        $sql_insert = "INSERT INTO vista_televisor (id_turno, id_farmaceuta, id_paciente, hora_llamado, id_estado, nit_farma) 
                       VALUES (:id_turno, :id_farmaceuta, :id_paciente, CURTIME(), 1, :nit_farma)";
        $stmt_insert = $con->prepare($sql_insert);
        $stmt_insert->bindParam(':id_turno', $id_turno_ent, PDO::PARAM_INT);
        $stmt_insert->bindParam(':id_farmaceuta', $doc_farmaceuta_actual, PDO::PARAM_STR);
        $stmt_insert->bindParam(':id_paciente', $doc_paciente_llamado, PDO::PARAM_STR);
        $stmt_insert->bindParam(':nit_farma', $nit_farmacia_actual, PDO::PARAM_STR);
        
        if ($stmt_insert->execute()) {
            $con->commit();
            $response['success'] = true;
            $response['message'] = 'Paciente llamado correctamente.';
             $_SESSION['mensaje_accion_farma'] = 'Paciente llamado y registrado en el televisor.';
             $_SESSION['mensaje_accion_farma_tipo'] = 'success';
        } else {
            $con->rollBack();
            $response['message'] = 'Error al registrar el llamado del paciente.';
            error_log("Error al insertar en vista_televisor para turno $id_turno_ent por farmaceuta $doc_farmaceuta_actual");
        }

    } catch (PDOException $e) {
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        $response['message'] = 'Error de base de datos: ' . $e->getMessage();
        error_log("PDOException en llamar_paciente.php: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Error de conexión a la base de datos.';
}

echo json_encode($response);
?>