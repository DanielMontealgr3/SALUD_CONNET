<?php

require_once __DIR__ . '/../include/conexion.php';

$response = ['isAvailable' => false, 'message' => 'Error en la solicitud.'];
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['correo'])) {
    $correo = trim($_POST['correo']); 
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Formato de correo inválido.';
        echo json_encode($response);
        exit;
    }

    $conex_db = new database();
    $con = $conex_db->conectar();

    if ($con) {
        try {
            $sql = "SELECT COUNT(*) FROM usuarios WHERE correo_usu = :correo_usu";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':correo_usu', $correo, PDO::PARAM_STR);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count == 0) {
                $response['isAvailable'] = true;
                $response['message'] = 'Correo disponible.';
            } else {
                $response['isAvailable'] = false;
                $response['message'] = 'Este correo electrónico ya está registrado.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Error al verificar el correo: ' . $e->getMessage();
            error_log("Error en ajax/verificar_correo.php: " . $e->getMessage());
        }
    } else {
        $response['message'] = 'Error de conexión a la base de datos.';
    }
    database::disconnect();
} else {
    $response['message'] = 'Solicitud no válida.';
}

echo json_encode($response);
?>