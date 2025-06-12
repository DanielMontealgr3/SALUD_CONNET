<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$response = ['success' => false, 'message' => 'ID no proporcionado.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_tip_medic'])) {
    $id = filter_var($_POST['id_tip_medic'], FILTER_VALIDATE_INT);

    if (!$id) {
        $response['message'] = 'ID no válido.';
        echo json_encode($response);
        exit;
    }

    try {
        $db = new database();
        $con = $db->conectar();

        $stmt_check = $con->prepare("SELECT COUNT(*) FROM medicamentos WHERE id_tipo_medic = ?");
        $stmt_check->execute([$id]);
        $count = $stmt_check->fetchColumn();

        if ($count > 0) {
            $response['message'] = "No se puede eliminar este tipo porque está asignado a {$count} medicamento(s). Por favor, reasigne esos medicamentos primero.";
        } else {
            $stmt_delete = $con->prepare("DELETE FROM tipo_de_medicamento WHERE id_tip_medic = ?");
            if ($stmt_delete->execute([$id])) {
                $response['success'] = true;
                $response['message'] = 'El tipo de medicamento ha sido eliminado.';
            } else {
                $response['message'] = 'Error al eliminar de la base de datos.';
            }
        }
    } catch (PDOException $e) {
        $response['message'] = 'Error de base de datos. Es posible que el registro esté protegido por otras relaciones.';
        error_log($e->getMessage());
    }
}

echo json_encode($response);
?>