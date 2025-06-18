<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['id_rol'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token_alianzas'], $_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Error de validación de seguridad.']);
    exit;
}

$id_alianza = $_POST['id_alianza'] ?? 0;
$tabla_origen = $_POST['tabla_origen'] ?? '';
$accion = $_POST['accion'] ?? '';

$tablas_permitidas = ['detalle_eps_farm', 'detalle_eps_ips'];
if (!in_array($tabla_origen, $tablas_permitidas)) {
    echo json_encode(['success' => false, 'message' => 'Tabla de origen no válida.']);
    exit;
}

$db = new database();
$con = $db->conectar();
$pk_columna = ($tabla_origen == 'detalle_eps_farm') ? 'id_eps_farm' : 'id_eps_ips';

try {
    if ($accion === 'activar' || $accion === 'inactivar') {
        $nuevo_estado = ($accion === 'activar') ? 1 : 2;
        $sql = "UPDATE $tabla_origen SET id_estado = :estado, fecha = NOW() WHERE $pk_columna = :id";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':estado', $nuevo_estado, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id_alianza, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $mensaje = 'Alianza ' . ($accion === 'activar' ? 'activada' : 'inactivada') . ' correctamente.';
            echo json_encode(['success' => true, 'message' => $mensaje]);
        } else {
            throw new Exception('No se pudo actualizar el estado de la alianza.');
        }

    } elseif ($accion === 'eliminar') {
        $sql = "DELETE FROM $tabla_origen WHERE $pk_columna = :id";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':id', $id_alianza, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Alianza eliminada correctamente.']);
            } else {
                throw new Exception('No se encontró la alianza para eliminar o ya fue eliminada.');
            }
        } else {
            throw new Exception('No se pudo eliminar la alianza.');
        }

    } else {
        throw new Exception('Acción no reconocida.');
    }

} catch (PDOException $e) {
    if ($e->getCode() == '23000') {
        echo json_encode(['success' => false, 'message' => 'No se puede eliminar. La alianza está en uso por otros registros del sistema.']);
    } else {
        error_log("Error en ajax_gestionar_alianza.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    error_log("Error en ajax_gestionar_alianza.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>