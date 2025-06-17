<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once('../../include/conexion.php');

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

$response = ['success' => false, 'message' => 'Acceso no autorizado o datos inválidos.'];

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1 && $_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $response['message'] = 'Error de validación CSRF. Intente de nuevo.';
        echo json_encode($response);
        exit;
    }

    $id_entidad = trim($_POST['id_registro'] ?? '');
    $tipo_entidad = trim($_POST['tipo_registro'] ?? '');

    if (empty($id_entidad) || empty($tipo_entidad) || !in_array($tipo_entidad, ['farmacias', 'eps', 'ips'])) {
        $response['message'] = 'Datos de entidad incompletos o no válidos.';
        echo json_encode($response);
        exit;
    }

    $conex_db = new database();
    $con = $conex_db->conectar();
    
    if (!$con) {
        $response['message'] = 'Error de conexión a la base de datos.';
        echo json_encode($response);
        exit;
    }

    $config = [];
    switch ($tipo_entidad) {
        case 'farmacias':
            $config = ['tabla' => 'farmacias', 'pk' => 'nit_farm'];
            break;
        case 'eps':
            $config = ['tabla' => 'eps', 'pk' => 'nit_eps'];
            break;
        case 'ips':
            $config = ['tabla' => 'ips', 'pk' => 'Nit_IPS'];
            break;
    }

    try {
        $stmt_delete = $con->prepare("DELETE FROM {$config['tabla']} WHERE {$config['pk']} = :id_entidad");
        $stmt_delete->bindParam(':id_entidad', $id_entidad);

        if ($stmt_delete->execute()) {
            if ($stmt_delete->rowCount() > 0) {
                $response = ['success' => true, 'message' => "La entidad con ID {$id_entidad} ha sido eliminada correctamente."];
            } else {
                $response['message'] = "No se encontró la entidad para eliminar o ya fue borrada.";
            }
        } else {
            $response['message'] = "Error al ejecutar la eliminación en la base de datos.";
        }

    } catch (PDOException $e) {
        error_log("Error PDO al eliminar entidad: " . $e->getMessage());

        if ($e->getCode() == '23000') {
             $response['message'] = "No se puede eliminar. Esta entidad está siendo utilizada por afiliados, asociaciones, médicos u otros registros del sistema.";
        } else {
            $response['message'] = "Error de base de datos al intentar eliminar. Por favor, contacte al administrador.";
        }
    }
    
    $conex_db->desconectar();
    echo json_encode($response);

} else {
    echo json_encode($response);
}
?>