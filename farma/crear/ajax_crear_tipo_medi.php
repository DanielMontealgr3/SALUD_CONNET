<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$response = ['success' => false, 'message' => 'Acceso no permitido o datos incorrectos.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom_tipo_medi'])) {
    
    $nombre_tipo = trim($_POST['nom_tipo_medi']);

    if (strlen($nombre_tipo) < 5 || !preg_match('/^[a-zA-Z\s]+$/', $nombre_tipo)) {
        $response['message'] = 'El nombre no es válido. Debe tener más de 4 caracteres y contener solo letras y espacios.';
        echo json_encode($response);
        exit;
    }

    try {
        $db = new database();
        $con = $db->conectar();

        $stmt_check = $con->prepare("SELECT id_tip_medic FROM tipo_de_medicamento WHERE nom_tipo_medi = ?");
        $stmt_check->execute([$nombre_tipo]);

        if ($stmt_check->fetch()) {
            $response['message'] = 'Este tipo de medicamento ya existe.';
        } else {
            $stmt_insert = $con->prepare("INSERT INTO tipo_de_medicamento (nom_tipo_medi) VALUES (?)");
            if ($stmt_insert->execute([$nombre_tipo])) {
                $response['success'] = true;
                $response['message'] = 'Tipo de medicamento creado con éxito.';
            } else {
                $response['message'] = 'Error al guardar en la base de datos.';
            }
        }
    } catch (PDOException $e) {
        $response['message'] = 'Error de base de datos.';
        error_log($e->getMessage());
    }
}

echo json_encode($response);
?>