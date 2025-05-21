<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../include/conexion.php';
require_once __DIR__ . '/../include/validar_sesion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['doc_usu_editar']) || empty($_POST['doc_usu_editar'])) {
        echo json_encode(['success' => false, 'message' => 'Documento de usuario no especificado.']); exit;
    }
    $doc_usuario_a_editar = $_POST['doc_usu_editar'];
    $pdo = Database::connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $nom_usu = trim($_POST['nom_usu_edit'] ?? '');
    $tel_usu = trim($_POST['tel_usu_edit'] ?? '');
    $correo_usu = trim($_POST['correo_usu_edit'] ?? '');
    $direccion_usu = trim($_POST['direccion_usu_edit'] ?? '');
    $fecha_nac = trim($_POST['fecha_nac_edit'] ?? '');
    $id_barrio = trim($_POST['id_barrio_edit'] ?? '');
    $id_gen = trim($_POST['id_gen_edit'] ?? '');
    $id_est = trim($_POST['id_est_edit'] ?? '');
    $id_rol = trim($_POST['id_rol_edit'] ?? '');
    $id_especialidad = trim($_POST['id_especialidad_edit'] ?? '');
    $ID_ESPECIALIDAD_NO_APLICA = 46; 

    $update_fields = []; $params = []; $mensaje_update = ""; $success_update = false;

    if (empty($nom_usu) || empty($correo_usu) || empty($fecha_nac) || empty($id_barrio) || empty($id_gen) || empty($id_est) || empty($id_rol)) {
         echo json_encode(['success' => false, 'message' => "Campos obligatorios (*) faltantes."]); Database::disconnect(); exit;
    }
    if ($id_rol == 4 && (empty($id_especialidad) || $id_especialidad == $ID_ESPECIALIDAD_NO_APLICA) ) {
        echo json_encode(['success' => false, 'message' => "Para el rol Médico, la especialidad es requerida y no puede ser 'No Aplica'."]); Database::disconnect(); exit;
    }

    if (!empty($nom_usu)) { $update_fields[] = "nom_usu = :nom_usu"; $params[':nom_usu'] = $nom_usu; }
    $params[':tel_usu'] = (empty($tel_usu) && $tel_usu !== '0') ? null : $tel_usu; $update_fields[] = "tel_usu = :tel_usu";
    if (!empty($correo_usu)) {
        if (filter_var($correo_usu, FILTER_VALIDATE_EMAIL)) {
            $sql_check_email = "SELECT doc_usu FROM usuarios WHERE correo_usu = :correo_usu AND doc_usu != :doc_usu_a_editar";
            $q_check_email = $pdo->prepare($sql_check_email);
            $q_check_email->execute([':correo_usu' => $correo_usu, ':doc_usu_a_editar' => $doc_usuario_a_editar]);
            if ($q_check_email->fetch()) { echo json_encode(['success' => false, 'message' => "Correo ya en uso por otro usuario."]); Database::disconnect(); exit; }
            else { $update_fields[] = "correo_usu = :correo_usu"; $params[':correo_usu'] = $correo_usu; }
        } else { echo json_encode(['success' => false, 'message' => "Formato de correo inválido."]); Database::disconnect(); exit; }
    }
    $params[':direccion_usu'] = empty($direccion_usu) ? null : $direccion_usu; $update_fields[] = "direccion_usu = :direccion_usu";
    if (!empty($fecha_nac)) { $update_fields[] = "fecha_nac = :fecha_nac"; $params[':fecha_nac'] = $fecha_nac; }
    if (!empty($id_barrio)) { $update_fields[] = "id_barrio = :id_barrio"; $params[':id_barrio'] = $id_barrio; }
    if (!empty($id_gen)) { $update_fields[] = "id_gen = :id_gen"; $params[':id_gen'] = $id_gen; }
    if (!empty($id_est)) { $update_fields[] = "id_est = :id_est"; $params[':id_est'] = $id_est; }
    if (!empty($id_rol)) { $update_fields[] = "id_rol = :id_rol"; $params[':id_rol'] = $id_rol; }
    
    if ($id_rol == 4 && !empty($id_especialidad) && $id_especialidad != $ID_ESPECIALIDAD_NO_APLICA) {
        $update_fields[] = "id_especialidad = :id_especialidad"; $params[':id_especialidad'] = $id_especialidad;
    } else { 
        $update_fields[] = "id_especialidad = :id_especialidad"; $params[':id_especialidad'] = $ID_ESPECIALIDAD_NO_APLICA;
    }

    if (!empty($update_fields)) {
        $sql = "UPDATE usuarios SET " . implode(", ", $update_fields) . " WHERE doc_usu = :doc_usu_a_editar_sql";
        $params[':doc_usu_a_editar_sql'] = $doc_usuario_a_editar;
        try {
            $q = $pdo->prepare($sql); $q->execute($params);
            $mensaje_update = "Usuario actualizado correctamente."; $success_update = true;
        } catch (PDOException $e) { $mensaje_update = "Error al actualizar en la base de datos: " . $e->getMessage(); error_log("PDOException en admin editar_usuario: " . $e->getMessage());}
    } else {
        $mensaje_update = "No se realizaron cambios."; $success_update = true;
    }
    Database::disconnect();
    echo json_encode(['success' => $success_update, 'message' => $mensaje_update, 'new_foto_usu_path_for_modal' => null ]);
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']); exit;
}
?>