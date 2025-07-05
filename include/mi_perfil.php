<?php
// BLOQUE 1: CONFIGURACIÓN INICIAL
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['doc_usu'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no identificado.']); exit;
}
$doc_usuario_actual = $_SESSION['doc_usu'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // BLOQUE 2: RECOLECCIÓN Y VALIDACIÓN DE DATOS
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
        $default_avatar_db_path = 'img/perfiles/foto_por_defecto.webp';

        // OBTENER DATOS ACTUALES PARA COMPARAR QUÉ CAMBIÓ
        $stmt_actual = $con->prepare("SELECT * FROM usuarios WHERE doc_usu = ?");
        $stmt_actual->execute([$doc_usuario_actual]);
        $usuario_actual = $stmt_actual->fetch(PDO::FETCH_ASSOC);

        // VALIDACIONES EN EL SERVIDOR
        if (empty($nom_usu) || empty($correo_usu) || empty($fecha_nac) || empty($id_barrio) || empty($id_gen)) {
             $mensaje_update = "Campos obligatorios faltantes.";
        } elseif (!preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]{5,100}$/', $nom_usu)) {
            $mensaje_update = "Nombre inválido. Solo letras, entre 5 y 100 caracteres.";
        } elseif (!filter_var($correo_usu, FILTER_VALIDATE_EMAIL)) {
            $mensaje_update = "Formato de correo electrónico inválido.";
        }
        
        if (empty($mensaje_update)) {
            $sql_check_email = "SELECT doc_usu FROM usuarios WHERE correo_usu = :correo_usu AND doc_usu != :doc_usu_actual";
            $q_check_email = $con->prepare($sql_check_email);
            $q_check_email->execute([':correo_usu' => $correo_usu, ':doc_usu_actual' => $doc_usuario_actual]);
            if ($q_check_email->fetch()) { $mensaje_update = "El correo electrónico ya está en uso por otro usuario."; }
        }

        // BLOQUE 3: CONSTRUCCIÓN DINÁMICA DE LA CONSULTA 'UPDATE' (SOLO CAMPOS CAMBIADOS)
        if (empty($mensaje_update)) {
            if ($nom_usu !== $usuario_actual['nom_usu']) { $update_fields[] = "nom_usu = :nom_usu"; $params[':nom_usu'] = $nom_usu; }
            if ($tel_usu != $usuario_actual['tel_usu']) { $update_fields[] = "tel_usu = :tel_usu"; $params[':tel_usu'] = empty($tel_usu) ? null : $tel_usu; }
            if ($correo_usu !== $usuario_actual['correo_usu']) { $update_fields[] = "correo_usu = :correo_usu"; $params[':correo_usu'] = $correo_usu; }
            if ($direccion_usu != $usuario_actual['direccion_usu']) { $update_fields[] = "direccion_usu = :direccion_usu"; $params[':direccion_usu'] = empty($direccion_usu) ? null : $direccion_usu; }
            if ($fecha_nac !== $usuario_actual['fecha_nac']) { $update_fields[] = "fecha_nac = :fecha_nac"; $params[':fecha_nac'] = $fecha_nac; }
            if ($id_barrio != $usuario_actual['id_barrio']) { $update_fields[] = "id_barrio = :id_barrio"; $params[':id_barrio'] = $id_barrio; }
            if ($id_gen != $usuario_actual['id_gen']) { $update_fields[] = "id_gen = :id_gen"; $params[':id_gen'] = $id_gen; }

            if (!empty($new_pass)) {
                if ($new_pass === $confirm_pass) {
                    if (strlen($new_pass) >= 8 && preg_match('/[a-z]/', $new_pass) && preg_match('/[A-Z]/', $new_pass) && preg_match('/\d/', $new_pass) && preg_match('/[\W_]/', $new_pass)) {
                        $update_fields[] = "pass = :pass"; $params[':pass'] = password_hash($new_pass, PASSWORD_DEFAULT);
                    } else { $mensaje_update = "La nueva contraseña no cumple los requisitos."; }
                } else { $mensaje_update = "Las contraseñas no coinciden."; }
            }
        }
        
        // BLOQUE 4: PROCESAMIENTO DE LA SUBIDA DE IMAGEN
        $foto_actualizada_url_json = null;
        if (empty($mensaje_update) && isset($_FILES['foto_usu_modal']) && $_FILES['foto_usu_modal']['error'] == UPLOAD_ERR_OK) {
            $upload_dir_on_server = ROOT_PATH . '/img/perfiles/';
            if (!is_dir($upload_dir_on_server)) mkdir($upload_dir_on_server, 0777, true);

            $foto_actual_db_path = $usuario_actual['foto_usu'];
            if ($foto_actual_db_path && $foto_actual_db_path !== $default_avatar_db_path && file_exists(ROOT_PATH . '/' . $foto_actual_db_path)) {
                @unlink(ROOT_PATH . '/' . $foto_actual_db_path);
            }

            $file_extension = strtolower(pathinfo($_FILES['foto_usu_modal']['name'], PATHINFO_EXTENSION));
            $new_file_name = $doc_usuario_actual . '_' . time() . '.' . $file_extension;
            $destination_on_server = $upload_dir_on_server . $new_file_name;
            
            if (move_uploaded_file($_FILES['foto_usu_modal']['tmp_name'], $destination_on_server)) {
                $relative_path_for_db = 'img/perfiles/' . $new_file_name;
                $update_fields[] = "foto_usu = :foto_usu";
                $params[':foto_usu'] = $relative_path_for_db; 
                $foto_actualizada_url_json = BASE_URL . '/' . $relative_path_for_db;
            } else { 
                $mensaje_update = "Error al mover el archivo subido.";
            }
        }

        // BLOQUE 5: EJECUCIÓN FINAL DE LA ACTUALIZACIÓN
        $success_update = false;
        if (empty($mensaje_update)) { 
            if (!empty($update_fields)) {
                $sql = "UPDATE usuarios SET " . implode(", ", $update_fields) . " WHERE doc_usu = :doc_usu_actual";
                $params[':doc_usu_actual'] = $doc_usuario_actual;
                $q = $con->prepare($sql);
                $q->execute($params);
                $mensaje_update = "Perfil actualizado correctamente.";
                $success_update = true;
                if (isset($params[':nom_usu'])) { $_SESSION['nombre_usuario'] = $params[':nom_usu']; }
            } else {
                $mensaje_update = "No se realizaron cambios.";
                $success_update = true; 
            }
        }
    } catch (PDOException $e) {
        $mensaje_update = "Error de base de datos.";
        error_log("Error PDO en mi_perfil.php: " . $e->getMessage());
    }
    
    // BLOQUE 6: RESPUESTA JSON AL CLIENTE
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