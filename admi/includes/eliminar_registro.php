<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['id_rol'] != 1 ) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Error de validación (CSRF).']);
    exit;
}

$doc_usu = $_POST['doc_usu'] ?? '';
$id_tipo_doc = $_POST['id_tipo_doc'] ?? '';

if (empty($doc_usu) || empty($id_tipo_doc)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos para la eliminación.']);
    exit;
}

$db = new database();
$con = $db->conectar();

try {
    $con->beginTransaction();
    $con->prepare("DELETE FROM afiliados WHERE doc_afiliado = ?")->execute([$doc_usu]);
    
    $stmt = $con->prepare("DELETE FROM usuarios WHERE doc_usu = ? AND id_tipo_doc = ?");
    $stmt->execute([$doc_usu, $id_tipo_doc]);

    if ($stmt->rowCount() > 0) {
        $con->commit();
        echo json_encode(['success' => true, 'message' => 'Paciente eliminado correctamente.']);
    } else {
        $con->rollBack();
        echo json_encode(['success' => false, 'message' => 'No se encontró el paciente para eliminar.']);
    }
} catch (PDOException $e) {
    if ($con->inTransaction()) { $con->rollBack(); }
    if ($e->getCode() == '23000') {
         echo json_encode(['success' => false, 'message' => 'No se puede eliminar. El paciente tiene citas u otros registros asociados.']);
    } else {
        error_log("Error al eliminar paciente: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error de base de datos al eliminar.']);
    }
}
?>