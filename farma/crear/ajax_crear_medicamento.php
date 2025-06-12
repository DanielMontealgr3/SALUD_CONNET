<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$response = ['success' => false, 'message' => 'Datos inválidos o faltantes.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nom_medicamento'] ?? '');
    $id_tipo = filter_var($_POST['id_tipo_medic'], FILTER_VALIDATE_INT);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $codigo_barras = trim($_POST['codigo_barras'] ?? '');
    $codigo_barras = empty($codigo_barras) ? null : $codigo_barras;

    if (strlen($nombre) < 5 || !$id_tipo || strlen($descripcion) < 10) {
        $response['message'] = 'Por favor, complete todos los campos requeridos correctamente.';
        echo json_encode($response);
        exit;
    }

    try {
        $db = new database();
        $con = $db->conectar();

        // Verificar si el nombre ya existe
        $stmt_check = $con->prepare("SELECT id_medicamento FROM medicamentos WHERE nom_medicamento = ?");
        $stmt_check->execute([$nombre]);
        if ($stmt_check->fetch()) {
            $response['message'] = 'Ya existe un medicamento con este nombre.';
            echo json_encode($response);
            exit;
        }

        // Verificar si el código de barras ya existe (si se proporcionó)
        if ($codigo_barras !== null) {
            $stmt_check_code = $con->prepare("SELECT id_medicamento FROM medicamentos WHERE codigo_barras = ?");
            $stmt_check_code->execute([$codigo_barras]);
            if ($stmt_check_code->fetch()) {
                $response['message'] = 'Este código de barras ya está asignado a otro medicamento.';
                echo json_encode($response);
                exit;
            }
        }
        
        $sql = "INSERT INTO medicamentos (nom_medicamento, id_tipo_medic, descripcion, codigo_barras) VALUES (?, ?, ?, ?)";
        $stmt_insert = $con->prepare($sql);

        if ($stmt_insert->execute([$nombre, $id_tipo, $descripcion, $codigo_barras])) {
            $response['success'] = true;
            $response['message'] = 'Medicamento creado con éxito.';
        } else {
            $response['message'] = 'Error al guardar el medicamento en la base de datos.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Error de base de datos.';
        error_log($e->getMessage());
    }
}

echo json_encode($response);
?>