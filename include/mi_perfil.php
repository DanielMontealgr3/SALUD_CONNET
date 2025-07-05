<?php
// Usamos la ruta definida en config.php.
// __DIR__ es '.../SALUDCONNECT/include'
require_once __DIR__ . '/config.php'; 

header('Content-Type: application/json');

if (!isset($_SESSION['doc_usu'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada. Por favor, inicie sesión de nuevo.']);
    exit;
}
$doc_usuario_actual = $_SESSION['doc_usu'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

try {
    // RECOLECCIÓN Y SANEAMIENTO DE DATOS
    $nom_usu = trim($_POST['nom_usu_modal'] ?? '');
    // ... (el resto de tus variables de post) ...
    $tel_usu = trim($_POST['tel_usu_modal'] ?? '');
    $correo_usu = trim($_POST['correo_usu_modal'] ?? '');
    $direccion_usu = trim($_POST['direccion_usu_modal'] ?? '');
    $fecha_nac = trim($_POST['fecha_nac_modal'] ?? '');
    $id_barrio = trim($_POST['id_barrio_modal'] ?? '');
    $id_gen = trim($_POST['id_gen_modal'] ?? '');
    $new_pass = $_POST['pass_modal'] ?? '';
    $confirm_pass = $_POST['confirm_pass_modal'] ?? '';
    
    $mensaje_update = "";
    $default_avatar_db_path = 'img/perfiles/foto_por_defecto.webp';

    // OBTENER DATOS ACTUALES DEL USUARIO PARA COMPARAR
    $stmt_actual = $con->prepare("SELECT * FROM usuarios WHERE doc_usu = ?");
    $stmt_actual->execute([$doc_usuario_actual]);
    $usuario_actual = $stmt_actual->fetch(PDO::FETCH_ASSOC);

    // VALIDACIONES DEL LADO DEL SERVIDOR (capa de seguridad extra)
    // ... (Tu bloque de validaciones PHP, que ya es correcto) ...

    // CONSTRUCCIÓN DINÁMICA DE LA CONSULTA 'UPDATE'
    $update_fields = [];
    $params = [];
    if ($nom_usu !== $usuario_actual['nom_usu']) { $update_fields[] = "nom_usu = :nom_usu"; $params[':nom_usu'] = $nom_usu; }
    // ... (Tu bloque completo de comparaciones, que ya es muy eficiente) ...
    if ($tel_usu != $usuario_actual['tel_usu']) { $update_fields[] = "tel_usu = :tel_usu"; $params[':tel_usu'] = empty($tel_usu) ? null : $tel_usu; }
    if ($correo_usu !== $usuario_actual['correo_usu']) { $update_fields[] = "correo_usu = :correo_usu"; $params[':correo_usu'] = $correo_usu; }
    if ($direccion_usu != $usuario_actual['direccion_usu']) { $update_fields[] = "direccion_usu = :direccion_usu"; $params[':direccion_usu'] = empty($direccion_usu) ? null : $direccion_usu; }
    if ($fecha_nac !== $usuario_actual['fecha_nac']) { $update_fields[] = "fecha_nac = :fecha_nac"; $params[':fecha_nac'] = $fecha_nac; }
    if ($id_barrio != $usuario_actual['id_barrio']) { $update_fields[] = "id_barrio = :id_barrio"; $params[':id_barrio'] = $id_barrio; }
    if ($id_gen != $usuario_actual['id_gen']) { $update_fields[] = "id_gen = :id_gen"; $params[':id_gen'] = $id_gen; }


    // ... resto de tu lógica para contraseña e imagen, que está bien ...
    if (!empty($new_pass)) {
        if ($new_pass === $confirm_pass) {
            if (strlen($new_pass) >= 8 && preg_match('/[a-z]/', $new_pass) && preg_match('/[A-Z]/', $new_pass) && preg_match('/\d/', $new_pass) && preg_match('/[\W_]/', $new_pass)) {
                $update_fields[] = "pass = :pass"; $params[':pass'] = password_hash($new_pass, PASSWORD_DEFAULT);
            } else { $mensaje_update = "La nueva contraseña no cumple los requisitos de seguridad."; }
        } else { $mensaje_update = "Las contraseñas no coinciden."; }
    }
    
    $foto_actualizada_url_json = null;
    if (empty($mensaje_update) && isset($_FILES['foto_usu_modal']) && $_FILES['foto_usu_modal']['error'] == UPLOAD_ERR_OK) {
        $upload_dir_on_server = ROOT_PATH . '/img/perfiles/';
        if (!is_dir($upload_dir_on_server)) @mkdir($upload_dir_on_server, 0775, true);

        // Borra la foto anterior si no es la de por defecto
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
            // Usa BASE_URL para construir la ruta completa para el JSON
            $foto_actualizada_url_json = BASE_URL . '/' . $relative_path_for_db;
        } else { 
            $mensaje_update = "Error al mover el archivo subido.";
        }
    }

    if (!empty($mensaje_update)) {
        // Si hubo un error de validación antes de la DB, se envía.
        echo json_encode(['success' => false, 'message' => $mensaje_update]);
        exit;
    }

    // EJECUCIÓN FINAL DE LA ACTUALIZACIÓN
    if (!empty($update_fields)) {
        $sql = "UPDATE usuarios SET " . implode(", ", $update_fields) . " WHERE doc_usu = :doc_usu_actual";
        $params[':doc_usu_actual'] = $doc_usuario_actual;
        $q = $con->prepare($sql);
        $q->execute($params);
        
        // Actualiza el nombre en la sesión si cambió
        if (isset($params[':nom_usu'])) {
            $_SESSION['nombre_usuario'] = $params[':nom_usu'];
        }

        echo json_encode([
            'success' => true,
            'message' => '¡Perfil actualizado con éxito!',
            'new_nom_usu' => $params[':nom_usu'] ?? null,
            'new_foto_usu_path_for_modal' => $foto_actualizada_url_json
        ]);
    } else {
        // No hubo cambios ni errores
        echo json_encode(['success' => true, 'message' => 'No se realizaron cambios.']);
    }

} catch (PDOException $e) {
    error_log("Error PDO en mi_perfil.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos. No se pudo completar la operación.']);
}
?>