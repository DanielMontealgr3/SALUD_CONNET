<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$response = ['success' => false, 'message' => 'ID no proporcionado.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_medicamento'])) {
    $id = filter_var($_POST['id_medicamento'], FILTER_VALIDATE_INT);

    if (!$id) {
        $response['message'] = 'ID no válido.';
        echo json_encode($response);
        exit;
    }

    try {
        $db = new database();
        $con = $db->conectar();

        $stmt_check = $con->prepare("SELECT COUNT(*) FROM inventario_farmacia WHERE id_medicamento = ?");
        $stmt_check->execute([$id]);
        $count = $stmt_check->fetchColumn();

        if ($count > 0) {
            $response['message'] = "No se puede eliminar. Este medicamento tiene registros de inventario en {$count} farmacia(s).";
        } else {
            $stmt_delete = $con->prepare("DELETE FROM medicamentos WHERE id_medicamento = ?");
            if ($stmt_delete->execute([$id])) {
                $response['success'] = true;
                $response['message'] = 'El medicamento ha sido eliminado.';
            } else {
                $response['message'] = 'Error al eliminar de la base de datos.';
            }
        }
    } catch (PDOException $e) {
        $response['message'] = 'Error de base de datos. Es posible que el registro esté protegido por otras relaciones.';
    }
}

echo json_encode($response);
?>