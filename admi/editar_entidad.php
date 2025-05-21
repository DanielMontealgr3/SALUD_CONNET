<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Error de validación CSRF. Intente de nuevo.']); exit;
    }

    $id_entidad_post = trim($_POST['id_entidad_original'] ?? '');
    $tipo_entidad_post = trim($_POST['tipo_entidad_original'] ?? '');

    if (empty($id_entidad_post) || empty($tipo_entidad_post) || !in_array($tipo_entidad_post, ['farmacias', 'eps', 'ips'])) {
        echo json_encode(['success' => false, 'message' => 'Datos de entidad no válidos.']); exit;
    }
    
    $conex_db = new database(); $con = $conex_db->conectar();
    if (!$con) { echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']); exit; }

    $update_data = []; $errores_validacion = []; $config_post = [];

    if ($tipo_entidad_post === 'farmacias') {
        $config_post = ['tabla' => 'farmacias', 'pk' => 'nit_farm'];
        $update_data['nom_farm'] = trim($_POST['nom_farm_modal'] ?? '');
        $update_data['direc_farm'] = trim($_POST['direc_farm_modal'] ?? '');
        $update_data['nom_gerente'] = trim($_POST['nom_gerente_farm_modal'] ?? '');
        $update_data['tel_farm'] = trim($_POST['tel_farm_modal'] ?? '');
        $update_data['correo_farm'] = filter_var(trim($_POST['correo_farm_modal'] ?? ''), FILTER_SANITIZE_EMAIL);
        if(empty($update_data['nom_farm'])) $errores_validacion[] = "El nombre de la farmacia es obligatorio.";
        if(!empty($update_data['correo_farm']) && !filter_var($update_data['correo_farm'], FILTER_VALIDATE_EMAIL)) $errores_validacion[] = "Correo de farmacia inválido.";

    } elseif ($tipo_entidad_post === 'eps') {
        $config_post = ['tabla' => 'eps', 'pk' => 'nit_eps'];
        $update_data['nombre_eps'] = trim($_POST['nombre_eps_modal'] ?? '');
        $update_data['direc_eps'] = trim($_POST['direc_eps_modal'] ?? '');
        $update_data['nom_gerente'] = trim($_POST['nom_gerente_eps_modal'] ?? '');
        $update_data['telefono'] = trim($_POST['telefono_eps_modal'] ?? '');
        $update_data['correo'] = filter_var(trim($_POST['correo_eps_modal'] ?? ''), FILTER_SANITIZE_EMAIL);
        if(empty($update_data['nombre_eps'])) $errores_validacion[] = "El nombre de la EPS es obligatorio.";
        if(!empty($update_data['correo']) && !filter_var($update_data['correo'], FILTER_VALIDATE_EMAIL)) $errores_validacion[] = "Correo de EPS inválido.";

    } elseif ($tipo_entidad_post === 'ips') {
        $config_post = ['tabla' => 'ips', 'pk' => 'Nit_IPS'];
        $update_data['nom_IPS'] = trim($_POST['nom_ips_modal'] ?? '');
        $update_data['direc_IPS'] = trim($_POST['direc_ips_modal'] ?? '');
        $update_data['nom_gerente'] = trim($_POST['nom_gerente_ips_modal'] ?? '');
        $update_data['tel_IPS'] = trim($_POST['tel_ips_modal'] ?? '');
        $update_data['correo_IPS'] = filter_var(trim($_POST['correo_ips_modal'] ?? ''), FILTER_SANITIZE_EMAIL);
        $update_data['ubicacion_mun'] = trim($_POST['ubicacion_mun_ips_modal'] ?? '');
        if(empty($update_data['nom_IPS'])) $errores_validacion[] = "El nombre de la IPS es obligatorio.";
        if(empty($update_data['ubicacion_mun'])) $errores_validacion[] = "El municipio es obligatorio para IPS.";
        elseif(!ctype_digit($update_data['ubicacion_mun'])) $errores_validacion[] = "El municipio seleccionado no es válido.";
        if(!empty($update_data['correo_IPS']) && !filter_var($update_data['correo_IPS'], FILTER_VALIDATE_EMAIL)) $errores_validacion[] = "Correo de IPS inválido.";
    }

    if (!empty($errores_validacion)){
        echo json_encode(['success' => false, 'message' => "Errores de validación: " . implode(", ", $errores_validacion)]);
        $conex_db->desconectar(); exit;
    }

    if (!empty($config_post)) {
        try {
            $sql_update_parts = []; $params_update = [];
            foreach ($update_data as $columna => $valor) {
                if ($columna !== $config_post['pk']) { 
                    $sql_update_parts[] = "$columna = :$columna";
                    $params_update[":$columna"] = ($valor === '' && !in_array($columna, ['nom_farm', 'nombre_eps', 'nom_IPS', 'ubicacion_mun'])) ? null : $valor;
                }
            }
            $params_update[':id_orig'] = $id_entidad_post;

            if (!empty($sql_update_parts)) {
                $sql_update = "UPDATE " . $config_post['tabla'] . " SET " . implode(', ', $sql_update_parts) . " WHERE " . $config_post['pk'] . " = :id_orig";
                $stmt_update = $con->prepare($sql_update);
                
                if ($stmt_update->execute($params_update)) {
                    $_SESSION['mensaje_accion'] = "Entidad '" . htmlspecialchars(reset($update_data)) . "' actualizada correctamente.";
                    $_SESSION['mensaje_accion_tipo'] = "success";
                    echo json_encode(['success' => true, 'message' => 'Entidad actualizada correctamente.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error al ejecutar la actualización en la base de datos.']);
                }
            } else {
                echo json_encode(['success' => true, 'message' => 'No se realizaron cambios (no se proporcionaron datos diferentes).']);
            }
        } catch (PDOException $e) {
            error_log("Error PDO al actualizar entidad: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error PDO al actualizar: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Configuración de entidad no encontrada.']);
    }
    $conex_db->desconectar();
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido o datos insuficientes.']);
    exit;
}
?>