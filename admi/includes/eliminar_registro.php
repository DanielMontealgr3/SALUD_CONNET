<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Error de validación (CSRF).']);
    exit;
}

$doc_usu = $_POST['doc_usu'] ?? '';
$id_tipo_doc = $_POST['id_tipo_doc'] ?? '';
$id_estado_eliminado = 17;
$id_estado_asignacion_inactiva = 2;

if (empty($doc_usu) || empty($id_tipo_doc)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos para la operación.']);
    exit;
}

$db = new database();
$con = $db->conectar();

try {
    $con->beginTransaction();

    $stmt_role = $con->prepare("SELECT id_rol FROM usuarios WHERE doc_usu = ?");
    $stmt_role->execute([$doc_usu]);
    $usuario = $stmt_role->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $con->rollBack();
        echo json_encode(['success' => false, 'message' => 'El usuario a eliminar no existe.']);
        exit;
    }
    $id_rol_usuario = $usuario['id_rol'];

    if ($id_rol_usuario == 2) {
        $con->prepare("DELETE FROM afiliados WHERE doc_afiliado = ?")->execute([$doc_usu]);
    } elseif ($id_rol_usuario == 3) {
        $stmt_asig_farma = $con->prepare("UPDATE asignacion_farmaceuta SET id_estado = ? WHERE doc_farma = ? AND id_estado = 1");
        $stmt_asig_farma->execute([$id_estado_asignacion_inactiva, $doc_usu]);
    } elseif ($id_rol_usuario == 4) {
        $stmt_asig_medico = $con->prepare("UPDATE asignacion_medico SET id_estado = ? WHERE doc_medico = ? AND id_estado = 1");
        $stmt_asig_medico->execute([$id_estado_asignacion_inactiva, $doc_usu]);
    }

    $stmt_update_user = $con->prepare("UPDATE usuarios SET id_est = ? WHERE doc_usu = ? AND id_tipo_doc = ?");
    $stmt_update_user->execute([$id_estado_eliminado, $doc_usu, $id_tipo_doc]);

    if ($stmt_update_user->rowCount() > 0) {
        $con->commit();
        echo json_encode(['success' => true, 'message' => 'Usuario marcado como eliminado correctamente.']);
    } else {
        $con->rollBack();
        echo json_encode(['success' => false, 'message' => 'No se encontró el usuario para marcar como eliminado.']);
    }

} catch (PDOException $e) {
    if ($con->inTransaction()) { $con->rollBack(); }
    error_log("Error al marcar como eliminado al usuario " . $doc_usu . ": " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos durante la operación.']);
}
?>