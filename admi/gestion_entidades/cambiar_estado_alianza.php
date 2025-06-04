<?php
date_default_timezone_set('America/Bogota'); 

require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

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
$nit_eps_post = trim($_POST['nit_eps']);
$nit_entidad_post = trim($_POST['nit_entidad']);
$accion_alianza = $_POST['accion_alianza'];
$nuevo_id_estado = ($accion_alianza === 'activar') ? 1 : 2;
$fecha_actual_db = date('Y-m-d');

$columna_entidad_aliada_en_detalle = ($tabla_origen === 'detalle_eps_farm') ? 'nit_farm' : 'nit_ips';
$nombre_eps_aliada = '';
$nombre_entidad_aliada_relacionada = '';


if (isset($con) && $con instanceof PDO) {
    try {
        $sql_select_nombres = '';
        $params_select_nombres = [':nit_eps' => $nit_eps_post, ':nit_entidad' => $nit_entidad_post];

        if ($tabla_origen === 'detalle_eps_farm') {
            $sql_select_nombres = "SELECT e.nombre_eps, f.nom_farm as nombre_entidad 
                                   FROM detalle_eps_farm def 
                                   JOIN eps e ON def.nit_eps = e.nit_eps 
                                   JOIN farmacias f ON def.nit_farm = f.nit_farm 
                                   WHERE def.nit_eps = :nit_eps AND def.nit_farm = :nit_entidad";
        } elseif ($tabla_origen === 'detalle_eps_ips') {
            $sql_select_nombres = "SELECT e.nombre_eps, i.nom_IPS as nombre_entidad 
                                   FROM detalle_eps_ips dei 
                                   JOIN eps e ON dei.nit_eps = e.nit_eps 
                                   JOIN ips i ON dei.nit_ips = i.Nit_IPS 
                                   WHERE dei.nit_eps = :nit_eps AND dei.nit_ips = :nit_entidad";
        }

        if (!empty($sql_select_nombres)) {
            $stmt_nombres = $con->prepare($sql_select_nombres);
            $stmt_nombres->execute($params_select_nombres);
            $nombres = $stmt_nombres->fetch(PDO::FETCH_ASSOC);
            if ($nombres) {
                $nombre_eps_aliada = $nombres['nombre_eps'];
                $nombre_entidad_aliada_relacionada = $nombres['nombre_entidad'];
            }
        }

        $sql_update = "UPDATE $tabla_origen SET id_estado = :nuevo_id_estado, fecha = :fecha 
                       WHERE nit_eps = :nit_eps AND $columna_entidad_aliada_en_detalle = :nit_entidad";
        $stmt_update = $con->prepare($sql_update);
        $stmt_update->bindParam(':nuevo_id_estado', $nuevo_id_estado, PDO::PARAM_INT);
        $stmt_update->bindParam(':fecha', $fecha_actual_db, PDO::PARAM_STR);
        $stmt_update->bindParam(':nit_eps', $nit_eps_post, PDO::PARAM_STR);
        $stmt_update->bindParam(':nit_entidad', $nit_entidad_post, PDO::PARAM_STR);

        if ($stmt_update->execute()) {
            if ($stmt_update->rowCount() > 0) {
                $response['success'] = true;
                $accion_texto_pasado = ($accion_alianza === 'activar') ? 'activada' : 'desactivada';
                $mensaje_exito = "Estado de la alianza actualizado correctamente.";
                if (!empty($nombre_eps_aliada) && !empty($nombre_entidad_aliada_relacionada)) {
                     $mensaje_exito = "La alianza entre '" . htmlspecialchars($nombre_eps_aliada) . "' y '" . htmlspecialchars($nombre_entidad_aliada_relacionada) . "' fue " . $accion_texto_pasado . ".";
                }
                $response['message'] = $mensaje_exito;
                $_SESSION['mensaje_alianza'] = $mensaje_exito;
                $_SESSION['mensaje_alianza_tipo'] = 'success';
            } else {
                 $response['message'] = 'No se actualizó el estado. La alianza no existe con esos NITs o ya tiene el estado solicitado.';
                 $_SESSION['mensaje_alianza'] = $response['message'];
                 $_SESSION['mensaje_alianza_tipo'] = 'warning';
            }
        } else {
            $response['message'] = 'Error al ejecutar la actualización del estado de la alianza.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Error de base de datos al cambiar estado: ' . $e->getMessage();
        error_log("PDOException en cambiar_estado_alianza.php: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Error de conexión a la base de datos.';
}

echo json_encode($response);
?>