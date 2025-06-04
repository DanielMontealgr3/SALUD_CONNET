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
    !isset($_POST['nit_eps']) ||
    !isset($_POST['nit_entidad']) ||
    !isset($_POST['accion_alianza']) || !in_array($_POST['accion_alianza'], ['activar', 'inactivar']) ||
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token_alianzas']) ||
    $_POST['csrf_token'] !== $_SESSION['csrf_token_alianzas']
) {
    $response['message'] = 'Acceso no autorizado o datos incompletos para cambiar estado de alianza.';
    echo json_encode($response);
    exit;
}

$tabla_origen = $_POST['tabla_origen'];
$nit_eps = trim($_POST['nit_eps']);
$nit_entidad = trim($_POST['nit_entidad']);
$accion_alianza = $_POST['accion_alianza'];
$nuevo_id_estado = ($accion_alianza === 'activar') ? 1 : 2;
$fecha_actual_db = date('Y-m-d');

$columna_entidad_aliada = ($tabla_origen === 'detalle_eps_farm') ? 'nit_farm' : 'nit_ips';


if (isset($con) && $con instanceof PDO) {
    try {
        $sql_update = "UPDATE $tabla_origen SET id_estado = :nuevo_id_estado, fecha = :fecha 
                       WHERE nit_eps = :nit_eps AND $columna_entidad_aliada = :nit_entidad";
        $stmt_update = $con->prepare($sql_update);
        $stmt_update->bindParam(':nuevo_id_estado', $nuevo_id_estado, PDO::PARAM_INT);
        $stmt_update->bindParam(':fecha', $fecha_actual_db, PDO::PARAM_STR);
        $stmt_update->bindParam(':nit_eps', $nit_eps, PDO::PARAM_STR);
        $stmt_update->bindParam(':nit_entidad', $nit_entidad, PDO::PARAM_STR);

        if ($stmt_update->execute()) {
            $response['success'] = true;
            $response['message'] = 'Estado de la alianza actualizado correctamente.';
             $_SESSION['mensaje_alianza'] = 'Estado de la alianza actualizado.';
             $_SESSION['mensaje_alianza_tipo'] = 'success';
        } else {
            $response['message'] = 'Error al actualizar el estado de la alianza.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Error de base de datos: ' . $e->getMessage();
        error_log("PDOException en cambiar_estado_alianza.php: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Error de conexión a la base de datos.';
}

echo json_encode($response);
?>