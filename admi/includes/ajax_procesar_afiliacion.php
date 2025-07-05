<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

$response = ['success' => false, 'message' => 'Error desconocido.'];
$conex_db = new database();
$con = $conex_db->conectar();

$doc_afiliado_post = trim($_POST['doc_afiliado_modal_hidden'] ?? '');
$tipo_entidad_sel_post = trim($_POST['tipo_entidad_afiliacion_modal'] ?? '');
$id_eps_valor = ($tipo_entidad_sel_post === 'eps') ? trim($_POST['entidad_especifica_eps_modal'] ?? null) : null;
$id_arl_valor = ($tipo_entidad_sel_post === 'arl') ? filter_input(INPUT_POST, 'entidad_especifica_arl_modal', FILTER_VALIDATE_INT) : null;
$id_regimen_sel_post = filter_input(INPUT_POST, 'id_regimen_modal', FILTER_VALIDATE_INT);
$id_estado_sel_post = filter_input(INPUT_POST, 'id_estado_modal', FILTER_VALIDATE_INT);
$fecha_actual_db = date('Y-m-d');

if (empty($doc_afiliado_post) || empty($tipo_entidad_sel_post) || ($tipo_entidad_sel_post === 'eps' && empty($id_eps_valor)) || ($tipo_entidad_sel_post === 'arl' && empty($id_arl_valor)) || empty($id_regimen_sel_post) || empty($id_estado_sel_post)) {
    $response['message'] = "Todos los campos son obligatorios.";
    echo json_encode($response);
    exit;
}

if ($con) {
    try {
        $con->beginTransaction();
        $accion_realizada = "";
        
        if ($tipo_entidad_sel_post === 'eps') {
            if ($id_estado_sel_post == 1) { 
                $con->prepare("UPDATE afiliados SET id_estado = 2 WHERE doc_afiliado = ? AND id_eps IS NOT NULL AND id_estado = 1")->execute([$doc_afiliado_post]);
            }
            $stmt_check_eps = $con->prepare("SELECT id_afiliacion FROM afiliados WHERE doc_afiliado = ? AND id_eps = ?");
            $stmt_check_eps->execute([$doc_afiliado_post, $id_eps_valor]);
            $id_afiliacion_existente = $stmt_check_eps->fetchColumn();

            if ($id_afiliacion_existente) {
                $sql = "UPDATE afiliados SET fecha_afi = ?, id_regimen = ?, id_estado = ? WHERE id_afiliacion = ?";
                $params = [$fecha_actual_db, $id_regimen_sel_post, $id_estado_sel_post, $id_afiliacion_existente];
                $accion_realizada = "actualizada";
            } else {
                $sql = "INSERT INTO afiliados (doc_afiliado, fecha_afi, id_eps, id_regimen, id_estado) VALUES (?, ?, ?, ?, ?)";
                $params = [$doc_afiliado_post, $fecha_actual_db, $id_eps_valor, $id_regimen_sel_post, $id_estado_sel_post];
                $accion_realizada = "registrada";
            }
            $con->prepare($sql)->execute($params);
            $response['message'] = "Afiliación EPS " . $accion_realizada . " exitosamente.";
        
        } elseif ($tipo_entidad_sel_post === 'arl') {
             if ($id_estado_sel_post == 1) { 
                $con->prepare("UPDATE afiliados SET id_estado = 2 WHERE doc_afiliado = ? AND id_arl IS NOT NULL AND id_estado = 1")->execute([$doc_afiliado_post]);
            }
            $stmt_check_arl = $con->prepare("SELECT id_afiliacion FROM afiliados WHERE doc_afiliado = ? AND id_arl = ?");
            $stmt_check_arl->execute([$doc_afiliado_post, $id_arl_valor]);
            $id_afiliacion_existente = $stmt_check_arl->fetchColumn();

            if ($id_afiliacion_existente) {
                $sql = "UPDATE afiliados SET fecha_afi = ?, id_regimen = ?, id_estado = ? WHERE id_afiliacion = ?";
                $params = [$fecha_actual_db, $id_regimen_sel_post, $id_estado_sel_post, $id_afiliacion_existente];
                $accion_realizada = "actualizada";
            } else {
                $sql = "INSERT INTO afiliados (doc_afiliado, fecha_afi, id_arl, id_regimen, id_estado) VALUES (?, ?, ?, ?, ?)";
                $params = [$doc_afiliado_post, $fecha_actual_db, $id_arl_valor, $id_regimen_sel_post, $id_estado_sel_post];
                $accion_realizada = "registrada";
            }
            $con->prepare($sql)->execute($params);
            $response['message'] = "Afiliación ARL " . $accion_realizada . " exitosamente.";
        }
        
        $con->commit();
        $response['success'] = true;

    } catch (PDOException $e) {
        if ($con->inTransaction()) $con->rollBack();
        $response['message'] = "Error de base de datos: " . $e->getMessage();
    }
} else {
    $response['message'] = "Error de conexión a la base de datos.";
}
echo json_encode($response);
?>