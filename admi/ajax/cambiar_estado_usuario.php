<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Error desconocido.'];

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true ||
    !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 || 
    !isset($_POST['doc_usu'], $_POST['accion'], $_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    $_POST['csrf_token'] !== $_SESSION['csrf_token']
) {
    $response['message'] = 'Acceso no autorizado o datos incompletos.';
    echo json_encode($response);
    exit;
}

$doc_usu_cambiar = trim($_POST['doc_usu']);
$accion = trim($_POST['accion']);
$correo_usuario_notificar = filter_var(trim($_POST['correo_usu'] ?? ''), FILTER_SANITIZE_EMAIL);

if (!in_array($accion, ['activar', 'inactivar', 'revertir'])) {
    $response['message'] = 'Acción no válida.';
    echo json_encode($response);
    exit;
}

$db = new database();
$con = $db->conectar();
$nuevo_id_est = ($accion === 'inactivar') ? 2 : 1; // 'activar' y 'revertir' ambos resultan en estado 1 (Activo)

try {
    $stmt_get_user = $con->prepare("SELECT nom_usu, id_est FROM usuarios WHERE doc_usu = :doc_usu");
    $stmt_get_user->bindParam(':doc_usu', $doc_usu_cambiar, PDO::PARAM_STR);
    $stmt_get_user->execute();
    $usuario_actual = $stmt_get_user->fetch(PDO::FETCH_ASSOC);

    if (!$usuario_actual) {
        $response['message'] = 'Usuario no encontrado.';
        echo json_encode($response);
        exit;
    }
    
    $stmt_update = $con->prepare("UPDATE usuarios SET id_est = :nuevo_id_est WHERE doc_usu = :doc_usu");
    $stmt_update->bindParam(':nuevo_id_est', $nuevo_id_est, PDO::PARAM_INT);
    $stmt_update->bindParam(':doc_usu', $doc_usu_cambiar, PDO::PARAM_STR);
    
    if ($stmt_update->execute()) {
        $response['success'] = true;
        $response['message'] = 'Estado del usuario actualizado correctamente.';
        
       
        if ($accion === 'activar' && !empty($correo_usuario_notificar) && $usuario_actual['id_est'] != 1) {
            $response['send_email'] = true;
            $response['email_data'] = [
                'correo' => $correo_usuario_notificar,
                'nombre' => $usuario_actual['nom_usu'],
                'documento' => $doc_usu_cambiar
            ];
        } else {
             $response['send_email'] = false;
        }
    } else {
        $response['message'] = 'Error al actualizar el estado del usuario en la base de datos.';
    }

} catch (PDOException $e) {
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    error_log("PDOException en cambiar_estado_usuario.php: " . $e->getMessage());
}

echo json_encode($response);
?>