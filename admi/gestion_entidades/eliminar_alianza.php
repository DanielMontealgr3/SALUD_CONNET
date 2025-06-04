<?php
require_once '../include/validar_sesion.php';
require_once '../include/conexion.php';

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Error desconocido.'];

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true ||
    !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ||
    !isset($_POST['tabla_origen']) || !in_array($_POST['tabla_origen'], ['detalle_eps_farm', 'detalle_eps_ips']) ||
    !isset($_POST['id_alianza']) ||
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token_alianzas']) ||
    $_POST['csrf_token'] !== $_SESSION['csrf_token_alianzas']
) {
    $response['message'] = 'Acceso no autorizado o datos incompletos para eliminar alianza.';
    echo json_encode($response);
    exit;
}

$tabla_origen = $_POST['tabla_origen'];
$id_alianza = filter_var($_POST['id_alianza'], FILTER_SANITIZE_NUMBER_INT);
$columna_pk = ($tabla_origen === 'detalle_eps_farm') ? 'id_eps_farm' : 'id_eps_ips';


if (isset($con) && $con instanceof PDO) {
    try {
        $sql_delete = "DELETE FROM $tabla_origen WHERE $columna_pk = :id_alianza";
        $stmt_delete = $con->prepare($sql_delete);
        $stmt_delete->bindParam(':id_alianza', $id_alianza, PDO::PARAM_INT);

        if ($stmt_delete->execute()) {
            if ($stmt_delete->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Alianza eliminada correctamente.';
                $_SESSION['mensaje_alianza'] = 'Alianza eliminada exitosamente.';
                $_SESSION['mensaje_alianza_tipo'] = 'success';
            } else {
                $response['message'] = 'No se encontró la alianza para eliminar o ya fue eliminada.';
            }
        } else {
            $response['message'] = 'Error al eliminar la alianza de la base de datos.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Error de base de datos: ' . $e->getMessage();
        error_log("PDOException en eliminar_alianza.php: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Error de conexión a la base de datos.';
}

echo json_encode($response);
?>