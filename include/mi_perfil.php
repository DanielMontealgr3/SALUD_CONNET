<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once 'conexion.php'; 

header('Content-Type: application/json');

if (!isset($_SESSION['doc_usu'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no identificado.']); exit;
}
$doc_usuario_actual = $_SESSION['doc_usu'];
$pdo = null; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new database();
        $con = $pdo->conectar();
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $nom_usu = trim($_POST['nom_usu_modal'] ?? '');
        $tel_usu = trim($_POST['tel_usu_modal'] ?? '');
        $correo_usu = trim($_POST['correo_usu_modal'] ?? '');
        $direccion_usu = trim($_POST['direccion_usu_modal'] ?? '');
        $fecha_nac = trim($_POST['fecha_nac_modal'] ?? '');
        $id_barrio = trim($_POST['id_barrio_modal'] ?? '');
        $id_gen = trim($_POST['id_gen_modal'] ?? '');
        $new_pass = $_POST['pass_modal'] ?? '';
        $confirm_pass = $_POST['confirm_pass_modal'] ?? '';

        $update_fields = []; $params = []; $mensaje_update = "";
        $success_update = false; $foto_actualizada_url_json = null;
        
        $default_avatar_db_path = 'img/perfiles/foto_por_defecto.webp';

        if (empty($nom_usu) || empty($correo_usu) || empty($fecha_nac) || empty($id_barrio) || empty($id_gen) ) {
             $mensaje_update = "Campos obligatorios faltantes.";
        }

        if (empty($mensaje_update)) {
            $update_fields[] = "nom_usu = :nom_usu"; $params[':nom_usu'] = $nom_usu;
            $update_fields[] = "tel_usu = :tel_usu"; $params[':tel_usu'] = (empty($tel_usu) && $tel_usu !== '0') ? null : $tel_usu;
            
            if (filter_var($correo_usu, FILTER_VALIDATE_EMAIL)) {
                $sql_check_email = "SELECT doc_usu FROM usuarios WHERE correo_usu = :correo_usu AND doc_usu != :doc_usu_actual";
                $q_check_email = $con->prepare($sql_check_email);
                $q_check_email->execute([':correo_usu' => $correo_usu, ':doc_usu_actual' => $doc_usuario_actual]);
                if ($q_check_email->fetch()) { $mensaje_update = "El correo electrónico ya está en uso por otro usuario."; }
                else { $update_fields[] = "correo_usu = :correo_usu"; $params[':correo_usu'] = $correo_usu; }
            } else { $mensaje_update = "Formato de correo electrónico inválido."; }
            
            if (empty($mensaje_update)) {
                $update_fields[] = "direccion_usu = :direccion_usu"; $params[':direccion_usu'] = empty($direccion_usu) ? null : $direccion_usu;
                $update_fields[] = "fecha_nac = :fecha_nac"; $params[':fecha_nac'] = $fecha_nac;
                $update_fields[] = "id_barrio = :id_barrio"; $params[':id_barrio'] = $id_barrio;
                $update_fields[] = "id_gen = :id_gen"; $params[':id_gen'] = $id_gen;

                if (!empty($new_pass)) {
                    if ($new_pass === $confirm_pass) {
                        if (strlen($new_pass) >= 8 && preg_match('/[a-z]/', $new_pass) && preg_match('/[A-Z]/', $new_pass) && preg_match('/\d/', $new_pass) && preg_match('/[\W_]/', $new_pass) ) {
                            $update_fields[] = "pass = :pass"; $params[':pass'] = password_hash($new_pass, PASSWORD_DEFAULT);
                        } else { $mensaje_update = "La nueva contraseña no cumple con los requisitos de seguridad."; }
                    } else { $mensaje_update = "Las contraseñas no coinciden."; }
                }
            }
        }
        
        if (empty($mensaje_update) && isset($_FILES['foto_usu_modal']) && $_FILES['foto_usu_modal']['error'] == UPLOAD_ERR_OK && $_FILES['foto_usu_modal']['size'] > 0) {
            $server_project_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/SALUDCONNECT/';
            
            $sql_foto_actual = "SELECT foto_usu FROM usuarios WHERE doc_usu = :doc_usu";
            $q_foto_actual = $con->prepare($sql_foto_actual);
            $q_foto_actual->execute([':doc_usu' => $doc_usuario_actual]);
            $foto_actual_db_path = $q_foto_actual->fetchColumn();

            if ($foto_actual_db_path && $foto_actual_db_path !== $default_avatar_db_path) {
                $foto_antigua_server_path = $server_project_root . str_replace('/', DIRECTORY_SEPARATOR, $foto_actual_db_path);
                if (file_exists($foto_antigua_server_path)) {
                    @unlink($foto_antigua_server_path);
                }
            }

            $relative_upload_dir = 'img/perfiles/'; 
            $upload_dir_on_server = $server_project_root . $relative_upload_dir;

            if (!is_dir($upload_dir_on_server)) {
                mkdir($upload_dir_on_server, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['foto_usu_modal']['name'], PATHINFO_EXTENSION));
            $new_file_name = $doc_usuario_actual . '_' . time() . '.' . $file_extension;
            $destination_on_server = $upload_dir_on_server . $new_file_name;
            
            if (move_uploaded_file($_FILES['foto_usu_modal']['tmp_name'], $destination_on_server)) {
                $relative_path_for_db = $relative_upload_dir . $new_file_name;
                $update_fields[] = "foto_usu = :foto_usu";
                $params[':foto_usu'] = $relative_path_for_db; 
                $foto_actualizada_url_json = '/SALUDCONNECT/' . $relative_path_for_db;
            } else { 
                $mensaje_update = "Error al mover el archivo subido.";
            }
        }

        if (empty($mensaje_update)) { 
            if (!empty($update_fields)) {
                $sql = "UPDATE usuarios SET " . implode(", ", $update_fields) . " WHERE doc_usu = :doc_usu_actual";
                $params[':doc_usu_actual'] = $doc_usuario_actual;
                $q = $con->prepare($sql);
                $q->execute($params);
                $mensaje_update = "Perfil actualizado correctamente.";
                $success_update = true;
                if (isset($params[':nom_usu'])) { $_SESSION['nombre_usuario'] = $params[':nom_usu']; }
                if (isset($params[':foto_usu'])) { $_SESSION['foto_usuario'] = $params[':foto_usu']; }
            } else {
                $mensaje_update = "No se realizaron cambios.";
                $success_update = true; 
            }
        }
    } catch (PDOException $e) {
        $success_update = false;
        $mensaje_update = "Error de base de datos.";
        error_log("Error PDO en mi_perfil.php: " . $e->getMessage());
    } finally {
        $con = null;
    }
    
    echo json_encode([
        'success' => $success_update,
        'message' => $mensaje_update,
        'new_nom_usu' => ($success_update && isset($params[':nom_usu'])) ? $params[':nom_usu'] : null,
        'new_foto_usu_path_for_modal' => ($success_update && $foto_actualizada_url_json) ? $foto_actualizada_url_json : null
    ]);
    exit;

} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}
?>