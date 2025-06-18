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
$id_estado_eliminado = 17; // ID del estado "Eliminado"

if (empty($doc_usu) || empty($id_tipo_doc)) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos para la operación.']);
    exit;
}

$db = new database();
$con = $db->conectar();

try {
    $con->beginTransaction();

    // 1. Eliminar afiliaciones (esto se mantiene para limpiar relaciones)
    $con->prepare("DELETE FROM afiliados WHERE doc_afiliado = ?")->execute([$doc_usu]);
    
    // 2. Actualizar el estado del usuario a "Eliminado"
    $stmt = $con->prepare("UPDATE usuarios SET id_est = ? WHERE doc_usu = ? AND id_tipo_doc = ?");
    $stmt->execute([$id_estado_eliminado, $doc_usu, $id_tipo_doc]);

    if ($stmt->rowCount() > 0) {
        $con->commit();
        echo json_encode(['success' => true, 'message' => 'Paciente marcado como eliminado correctamente.']);
    } else {
        $con->rollBack();
        echo json_encode(['success' => false, 'message' => 'No se encontró el paciente para marcar como eliminado.']);
    }
} catch (PDOException $e) {
    if ($con->inTransaction()) { $con->rollBack(); }
    error_log("Error al marcar como eliminado al paciente: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos durante la operación.']);
}
?>