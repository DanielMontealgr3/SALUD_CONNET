<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../include/conexion.php';
require_once __DIR__ . '/../../include/validar_sesion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_usuario_a_editar = $_POST['doc_usu_editar'] ?? '';
    if (empty($doc_usuario_a_editar)) {
        echo json_encode(['success' => false, 'message' => 'Documento de usuario no especificado.']); exit;
    }

    $pdo = Database::connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $nom_usu = trim($_POST['nom_usu_edit'] ?? '');
    $tel_usu = trim($_POST['tel_usu_edit'] ?? '');
    $correo_usu = trim($_POST['correo_usu_edit'] ?? '');
    $direccion_usu = trim($_POST['direccion_usu_edit'] ?? '');
    $id_barrio = trim($_POST['id_barrio_edit'] ?? '');
    $id_gen = trim($_POST['id_gen_edit'] ?? '');
    $id_est = trim($_POST['id_est_edit'] ?? '');
    $id_rol = trim($_POST['id_rol_edit'] ?? '');
    $id_especialidad = trim($_POST['id_especialidad_edit'] ?? '');
    $ID_ESPECIALIDAD_NO_APLICA = 46; // Asumo que 46 es el ID para "No Aplica"

    if (empty($nom_usu) || empty($correo_usu) || empty($id_barrio) || empty($id_gen) || empty($id_est) || empty($id_rol) || empty($tel_usu) || empty($direccion_usu)) {
        echo json_encode(['success' => false, 'message' => "Todos los campos con (*) son obligatorios."]); Database::disconnect(); exit;
    }
    if ($id_rol == 4 && (empty($id_especialidad) || $id_especialidad == $ID_ESPECIALIDAD_NO_APLICA)) {
        echo json_encode(['success' => false, 'message' => "Para el rol Médico, la especialidad es requerida."]); Database::disconnect(); exit;
    }

    try {
        $update_fields = [];
        $params = [':doc_usu_a_editar' => $doc_usuario_a_editar];
        
        $update_fields[] = "nom_usu = :nom_usu"; $params[':nom_usu'] = $nom_usu;
        $update_fields[] = "tel_usu = :tel_usu"; $params[':tel_usu'] = $tel_usu;
        $update_fields[] = "correo_usu = :correo_usu"; $params[':correo_usu'] = $correo_usu;
        $update_fields[] = "direccion_usu = :direccion_usu"; $params[':direccion_usu'] = $direccion_usu;
        $update_fields[] = "id_barrio = :id_barrio"; $params[':id_barrio'] = $id_barrio;
        $update_fields[] = "id_gen = :id_gen"; $params[':id_gen'] = $id_gen;
        $update_fields[] = "id_est = :id_est"; $params[':id_est'] = $id_est;
        $update_fields[] = "id_rol = :id_rol"; $params[':id_rol'] = $id_rol;
        
        // Asignar especialidad solo si el rol es médico, de lo contrario asignar 'No aplica'
        $params[':id_especialidad'] = ($id_rol == 4 && !empty($id_especialidad)) ? $id_especialidad : $ID_ESPECIALIDAD_NO_APLICA;
        $update_fields[] = "id_especialidad = :id_especialidad";

        $sql = "UPDATE usuarios SET " . implode(", ", $update_fields) . " WHERE doc_usu = :doc_usu_a_editar";
        $q = $pdo->prepare($sql);
        $q->execute($params);
        
        echo json_encode(['success' => true, 'message' => "Usuario actualizado correctamente."]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar en la base de datos: ' . $e->getMessage()]);
    } finally {
        Database::disconnect();
    }
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']); exit;
}
?>