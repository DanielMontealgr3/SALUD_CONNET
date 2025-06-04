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
    $pdo = Database::connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
    $success_update = false; $foto_actualizada_path_json = null;
    
    $default_avatar_db_path = 'img/perfiles/default_avatar.png';

    if (empty($nom_usu) || empty($correo_usu) || empty($fecha_nac) || empty($id_barrio) || empty($id_gen) ) {
         $mensaje_update = "Campos obligatorios faltantes.";
    }

    if (empty($mensaje_update)) {
        if (!empty($nom_usu)) { $update_fields[] = "nom_usu = :nom_usu"; $params[':nom_usu'] = $nom_usu; }
        $params[':tel_usu'] = (empty($tel_usu) && $tel_usu !== '0') ? null : $tel_usu; $update_fields[] = "tel_usu = :tel_usu";
        if (!empty($correo_usu)) {
            if (filter_var($correo_usu, FILTER_VALIDATE_EMAIL)) {
                $sql_check_email = "SELECT doc_usu FROM usuarios WHERE correo_usu = :correo_usu AND doc_usu != :doc_usu_actual";
                $q_check_email = $pdo->prepare($sql_check_email);
                $q_check_email->execute([':correo_usu' => $correo_usu, ':doc_usu_actual' => $doc_usuario_actual]);
                if ($q_check_email->fetch()) { $mensaje_update = "Correo ya en uso."; }
                else { $update_fields[] = "correo_usu = :correo_usu"; $params[':correo_usu'] = $correo_usu; }
            } else { $mensaje_update = "Correo inválido."; }
        }
        if (empty($mensaje_update)) { 
            $params[':direccion_usu'] = empty($direccion_usu) ? null : $direccion_usu; $update_fields[] = "direccion_usu = :direccion_usu";
            if (!empty($fecha_nac)) { $update_fields[] = "fecha_nac = :fecha_nac"; $params[':fecha_nac'] = $fecha_nac; }
            if (!empty($id_barrio)) { $update_fields[] = "id_barrio = :id_barrio"; $params[':id_barrio'] = $id_barrio; }
            if (!empty($id_gen)) { $update_fields[] = "id_gen = :id_gen"; $params[':id_gen'] = $id_gen; }

            if (!empty($new_pass)) {
                if ($new_pass === $confirm_pass) {
                    if (strlen($new_pass) >= 8 && preg_match('/[a-z]/', $new_pass) && preg_match('/[A-Z]/', $new_pass) && preg_match('/\d/', $new_pass) && preg_match('/[\W_]/', $new_pass) ) {
                        $update_fields[] = "pass = :pass"; $params[':pass'] = password_hash($new_pass, PASSWORD_DEFAULT);
                    } else { $mensaje_update = "Contraseña no cumple requisitos."; }
                } else { $mensaje_update = "Contraseñas no coinciden."; }
            }
        }
    }
    
    if (empty($mensaje_update) && isset($_FILES['foto_usu_modal']) && $_FILES['foto_usu_modal']['error'] == UPLOAD_ERR_OK && $_FILES['foto_usu_modal']['size'] > 0) {
        $foto_antigua_path_servidor = null;
        $sql_foto_actual = "SELECT foto_usu FROM usuarios WHERE doc_usu = :doc_usu";
        $q_foto_actual = $pdo->prepare($sql_foto_actual);
        $q_foto_actual->execute([':doc_usu' => $doc_usuario_actual]);
        $foto_actual_db_path_or_url = $q_foto_actual->fetchColumn();

        $server_project_root_filesystem = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'SALUDCONNECT' . DIRECTORY_SEPARATOR;
        
        if ($foto_actual_db_path_or_url && $foto_actual_db_path_or_url !== $default_avatar_db_path && !filter_var($foto_actual_db_path_or_url, FILTER_VALIDATE_URL)) {
            $foto_antigua_path_servidor = $server_project_root_filesystem . str_replace('/', DIRECTORY_SEPARATOR, $foto_actual_db_path_or_url);
        } elseif (filter_var($foto_actual_db_path_or_url, FILTER_VALIDATE_URL)) {
            $parsed_url = parse_url($foto_actual_db_path_or_url);
            if (isset($parsed_url['path'])) {
                 $path_after_host = ltrim($parsed_url['path'], '/');
                 $path_parts = explode('/', $path_after_host);
                 if (count($path_parts) > 1 && $path_parts[0] === 'SALUDCONNECT') {
                    array_shift($path_parts); 
                    $relative_path_for_filesystem = implode(DIRECTORY_SEPARATOR, $path_parts);
                    $foto_antigua_path_servidor = $server_project_root_filesystem . $relative_path_for_filesystem;
                 }
            }
        }

        $relative_upload_subdir = 'img/perfiles/'; 
        $upload_dir_on_server_filesystem = $server_project_root_filesystem . $relative_upload_subdir; 

        if (!is_dir($upload_dir_on_server_filesystem)) {
            if (!mkdir($upload_dir_on_server_filesystem, 0777, true)) {
                 $mensaje_update = "Error al crear directorio de subida físico: " . $upload_dir_on_server_filesystem;
            }
        }

        if (empty($mensaje_update)) { 
            $file_tmp_path = $_FILES['foto_usu_modal']['tmp_name'];
            $file_extension = strtolower(pathinfo($_FILES['foto_usu_modal']['name'], PATHINFO_EXTENSION));
            $new_file_name_only = $doc_usuario_actual . '_' . time() . '.' . $file_extension;
            $destination_on_server_filesystem = $upload_dir_on_server_filesystem . $new_file_name_only;
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_extension, $allowed_extensions) && $_FILES['foto_usu_modal']['size'] < 2000000) { 
                if (move_uploaded_file($file_tmp_path, $destination_on_server_filesystem)) {
                    if ($foto_antigua_path_servidor && file_exists($foto_antigua_path_servidor) && basename($foto_antigua_path_servidor) !== 'default_avatar.png') {
                        @unlink($foto_antigua_path_servidor);
                    }
                    
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                    $host = $_SERVER['HTTP_HOST'];
                    $base_url = $protocol . $host . '/SALUDCONNECT/'; 
                    
                    $path_for_db_and_json = $base_url . $relative_upload_subdir . $new_file_name_only;

                    $update_fields[] = "foto_usu = :foto_usu";
                    $params[':foto_usu'] = $path_for_db_and_json; 
                    $foto_actualizada_path_json = $params[':foto_usu']; 
                } else { $mensaje_update = "Error al mover archivo subido a la ubicación final."; }
            } else { $mensaje_update = "Archivo no permitido o demasiado grande (máx 2MB: JPG, PNG, GIF)."; }
        }
    }

    if (empty($mensaje_update)) { 
        if (!empty($update_fields)) {
            $sql = "UPDATE usuarios SET " . implode(", ", $update_fields) . " WHERE doc_usu = :doc_usu_actual";
            $params[':doc_usu_actual'] = $doc_usuario_actual;
            try {
                $q = $pdo->prepare($sql);
                $q->execute($params);
                $mensaje_update = "Perfil actualizado correctamente.";
                $success_update = true;
                if (isset($params[':nom_usu'])) { $_SESSION['nombre_usuario'] = $params[':nom_usu']; }
                if (isset($params[':foto_usu'])) { $_SESSION['foto_usuario'] = $params[':foto_usu']; }
            } catch (PDOException $e) {
                $mensaje_update = "Error al actualizar en la base de datos: " . $e->getMessage();
                error_log("Error PDO al actualizar perfil: " . $e.getMessage());
                $success_update = false; 
            }
        } else {
            $mensaje_update = "No se realizaron cambios.";
            $success_update = true; 
        }
    } else {
        $success_update = false;
    }

    if ($pdo) { Database::disconnect(); }
    
    echo json_encode([
        'success' => $success_update,
        'message' => $mensaje_update,
        'new_nom_usu' => ($success_update && isset($params[':nom_usu'])) ? $params[':nom_usu'] : null,
        'new_foto_usu_path_for_modal' => ($success_update && $foto_actualizada_path_json) ? $foto_actualizada_path_json : null
    ]);
    exit;

} else {
    if ($pdo) { Database::disconnect(); } 
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}
?>