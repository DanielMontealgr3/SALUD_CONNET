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
    !isset($_POST['doc_usu']) ||
    !isset($_POST['accion']) || 
    !isset($_POST['csrf_token']) ||
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


if (!in_array($accion, ['activar', 'inactivar'])) {
    $response['message'] = 'Acción no válida.';
    echo json_encode($response);
    exit;
}

$nuevo_id_est = ($accion === 'activar') ? 1 : 2;

if (isset($con) && $con instanceof PDO) {
    try {
        $stmt_get_user = $con->prepare("SELECT nom_usu, correo_usu, id_est FROM usuarios WHERE doc_usu = :doc_usu");
        $stmt_get_user->bindParam(':doc_usu', $doc_usu_cambiar, PDO::PARAM_STR);
        $stmt_get_user->execute();
        $usuario_actual = $stmt_get_user->fetch(PDO::FETCH_ASSOC);

        if (!$usuario_actual) {
            $response['message'] = 'Usuario no encontrado.';
            echo json_encode($response);
            exit;
        }
        
        if ($usuario_actual['id_est'] == $nuevo_id_est) {
            $response['success'] = true; 
            $response['message'] = 'El usuario ya se encuentra en el estado solicitado.';
            echo json_encode($response);
            exit;
        }

        $stmt_update = $con->prepare("UPDATE usuarios SET id_est = :nuevo_id_est WHERE doc_usu = :doc_usu");
        $stmt_update->bindParam(':nuevo_id_est', $nuevo_id_est, PDO::PARAM_INT);
        $stmt_update->bindParam(':doc_usu', $doc_usu_cambiar, PDO::PARAM_STR);
        
        if ($stmt_update->execute()) {
            $response['success'] = true;
            $response['nuevo_estado_id'] = $nuevo_id_est;
            $response['nuevo_estado_nombre'] = ($nuevo_id_est == 1) ? 'Activo' : 'Inactivo';
            $response['message'] = 'Estado del usuario actualizado correctamente.';
            $_SESSION['pagina_anterior_estado_change'] = $_SERVER['HTTP_REFERER'] ?? '../admi/lista_farmaceutas.php';


            if ($accion === 'activar' && !empty($correo_usuario_notificar)) {
                $_SESSION['notificar_activacion'] = [
                    'correo' => $correo_usuario_notificar,
                    'nombre' => $usuario_actual['nom_usu'],
                    'documento' => $doc_usu_cambiar
                ];
                 $response['redirect_to_email'] = true; 
            }

        } else {
            $response['message'] = 'Error al actualizar el estado del usuario en la base de datos.';
        }

    } catch (PDOException $e) {
        $response['message'] = 'Error de base de datos: ' . $e->getMessage();
        error_log("PDOException en cambiar_estado_usuario.php: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Error de conexión a la base de datos.';
}

echo json_encode($response);
?>