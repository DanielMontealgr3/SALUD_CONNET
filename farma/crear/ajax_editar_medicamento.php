<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$response = ['success' => false, 'message' => 'Datos inválidos.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_medicamento'])) {
    $id = filter_var($_POST['id_medicamento'], FILTER_VALIDATE_INT);
    $nombre = trim($_POST['nom_medicamento']);
    $id_tipo = filter_var($_POST['id_tipo_medic'], FILTER_VALIDATE_INT);
    $descripcion = trim($_POST['descripcion']);
    $codigo_barras = trim($_POST['codigo_barras']);
    $codigo_barras = empty($codigo_barras) ? null : $codigo_barras;

    if (!$id || empty($nombre) || !$id_tipo || empty($descripcion)) {
        $response['message'] = 'Todos los campos obligatorios deben ser completados.';
        echo json_encode($response);
        exit;
    }
    
    try {
        $db = new database();
        $con = $db->conectar();
        
        $stmt_check_name = $con->prepare("SELECT id_medicamento FROM medicamentos WHERE nom_medicamento = ? AND id_medicamento != ?");
        $stmt_check_name->execute([$nombre, $id]);
        if ($stmt_check_name->fetch()) {
            $response['message'] = 'Ese nombre ya pertenece a otro medicamento.';
            echo json_encode($response);
            exit;
        }

        if ($codigo_barras !== null) {
            $stmt_check_code = $con->prepare("SELECT id_medicamento FROM medicamentos WHERE codigo_barras = ? AND id_medicamento != ?");
            $stmt_check_code->execute([$codigo_barras, $id]);
            if ($stmt_check_code->fetch()) {
                $response['message'] = 'Ese código de barras ya pertenece a otro medicamento.';
                echo json_encode($response);
                exit;
            }
        }

        $sql = "UPDATE medicamentos SET nom_medicamento = ?, id_tipo_medic = ?, descripcion = ?, codigo_barras = ? WHERE id_medicamento = ?";
        $stmt_update = $con->prepare($sql);
        if($stmt_update->execute([$nombre, $id_tipo, $descripcion, $codigo_barras, $id])){
            $response['success'] = true;
            $response['message'] = 'Medicamento actualizado correctamente.';
        } else {
            $response['message'] = 'Error al actualizar el medicamento.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Error de base de datos.';
    }
}
echo json_encode($response);
?>