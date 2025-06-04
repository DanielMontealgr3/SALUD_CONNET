<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Error desconocido.'];

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) {
    $response['message'] = 'Acceso no autorizado.';
    echo json_encode($response);
    exit;
}
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token_alianzas']) || !hash_equals($_SESSION['csrf_token_alianzas'], $_POST['csrf_token'])) {
    $response['message'] = 'Error de validación (CSRF).';
    echo json_encode($response);
    exit;
}

if (isset($_POST['id_alianza'], $_POST['tabla_origen'])) {
    $id_alianza = $_POST['id_alianza'];
    $tabla_origen = $_POST['tabla_origen'];
    $nombre_eps_aliada = '';
    $nombre_entidad_aliada = '';

    $db = new database();
    $con = $db->conectar();

    if ($con) {
        try {
            $campo_id_tabla_para_delete = '';
            $sql_select_nombres = '';
            $params_select = [':id_alianza_param_select' => $id_alianza];

            if ($tabla_origen === 'detalle_eps_farm') {
                $campo_id_tabla_para_delete = 'id_eps_farm'; // Columna directa en la tabla detalle_eps_farm
                $sql_select_nombres = "SELECT e.nombre_eps, f.nom_farm as nombre_entidad 
                                       FROM detalle_eps_farm def 
                                       JOIN eps e ON def.nit_eps = e.nit_eps 
                                       JOIN farmacias f ON def.nit_farm = f.nit_farm 
                                       WHERE def.id_eps_farm = :id_alianza_param_select";
            } elseif ($tabla_origen === 'detalle_eps_ips') {
                $campo_id_tabla_para_delete = 'id_eps_ips'; // Columna directa en la tabla detalle_eps_ips
                $sql_select_nombres = "SELECT e.nombre_eps, i.nom_IPS as nombre_entidad 
                                       FROM detalle_eps_ips dei 
                                       JOIN eps e ON dei.nit_eps = e.nit_eps 
                                       JOIN ips i ON dei.nit_ips = i.Nit_IPS 
                                       WHERE dei.id_eps_ips = :id_alianza_param_select";
            } else {
                throw new Exception("Tabla de origen no válida para eliminación.");
            }
            
            $stmt_nombres = $con->prepare($sql_select_nombres);
            $stmt_nombres->execute($params_select);
            $nombres = $stmt_nombres->fetch(PDO::FETCH_ASSOC);

            if ($nombres) {
                $nombre_eps_aliada = $nombres['nombre_eps'];
                $nombre_entidad_aliada = $nombres['nombre_entidad'];
            }


            $sql_delete = "DELETE FROM {$tabla_origen} WHERE {$campo_id_tabla_para_delete} = :id_alianza_param_delete";
            $stmt_delete = $con->prepare($sql_delete);
            $stmt_delete->bindParam(':id_alianza_param_delete', $id_alianza, PDO::PARAM_INT);

            if ($stmt_delete->execute()) {
                if ($stmt_delete->rowCount() > 0) {
                    $response['success'] = true;
                    $response['nombre_eps'] = $nombre_eps_aliada;
                    $response['nombre_entidad_aliada'] = $nombre_entidad_aliada;
                    $mensaje_exito = "Alianza eliminada correctamente.";
                    if (!empty($nombre_eps_aliada) && !empty($nombre_entidad_aliada)) {
                        $mensaje_exito = "La alianza entre '" . htmlspecialchars($nombre_eps_aliada) . "' y '" . htmlspecialchars($nombre_entidad_aliada) . "' fue eliminada.";
                    }
                    $response['message'] = $mensaje_exito;
                    $_SESSION['mensaje_alianza'] = $mensaje_exito;
                    $_SESSION['mensaje_alianza_tipo'] = 'success';
                } else {
                    $response['message'] = 'No se encontró la alianza para eliminar (ID: ' . htmlspecialchars($id_alianza) . ').';
                    $_SESSION['mensaje_alianza'] = $response['message'];
                    $_SESSION['mensaje_alianza_tipo'] = 'warning';
                }
            } else {
                $response['message'] = 'Error al ejecutar la eliminación de la alianza.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Error de base de datos al eliminar: ' . $e->getMessage();
            error_log("Error PDO eliminando alianza: " . $e->getMessage());
        } catch (Exception $e) {
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Error de conexión a la base de datos.';
    }
} else {
    $response['message'] = 'Datos incompletos para la eliminación de la alianza.';
}

echo json_encode($response);
?>