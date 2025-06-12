<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$response = ['success' => false, 'message' => 'Datos inválidos.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_tip_medic'], $_POST['nom_tipo_medi'])) {
    $id = filter_var($_POST['id_tip_medic'], FILTER_VALIDATE_INT);
    $nombre = trim($_POST['nom_tipo_medi']);

    if (!$id || strlen($nombre) < 5 || !preg_match('/^[a-zA-Z\s()]+$/', $nombre)) {
        $response['message'] = 'El nombre proporcionado no es válido.';
        echo json_encode($response);
        exit;
    }

    try {
        $db = new database();
        $con = $db->conectar();

        $stmt_check = $con->prepare("SELECT id_tip_medic FROM tipo_de_medicamento WHERE nom_tipo_medi = ? AND id_tip_medic != ?");
        $stmt_check->execute([$nombre, $id]);
        if ($stmt_check->fetch()) {
            $response['message'] = 'Ese nombre ya está en uso por otro tipo de medicamento.';
        } else {
            $stmt_update = $con->prepare("UPDATE tipo_de_medicamento SET nom_tipo_medi = ? WHERE id_tip_medic = ?");
            if ($stmt_update->execute([$nombre, $id])) {
                $response['success'] = true;
                $response['message'] = 'El tipo de medicamento ha sido actualizado.';
            } else {
                $response['message'] = 'Error al actualizar en la base de datos.';
            }
        }
    } catch (PDOException $e) {
        $response['message'] = 'Error de base de datos.';
        error_log($e->getMessage());
    }
}

echo json_encode($response);
?>