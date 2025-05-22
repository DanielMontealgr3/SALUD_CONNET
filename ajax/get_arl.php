<?php
require_once __DIR__ . '/../include/conexion.php';

$arl_list = [];
$response = ['success' => false, 'data' => [], 'message' => 'Error inicial o no autorizado.'];

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$conex_db = new database();
$con = $conex_db->conectar();

if ($con) {
    try {
        $stmt = $con->query("SELECT id_arl, nom_arl FROM arl ORDER BY nom_arl ASC");
        $arl_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['success'] = true;
        $response['data'] = $arl_list;
        $response['message'] = 'ARL cargadas exitosamente.';
    } catch (PDOException $e) {
        $response['message'] = "Error al cargar ARL: " . $e->getMessage();
        error_log("Error en ajax/get_arl.php: " . $e->getMessage());
    }
} else {
    $response['message'] = "Error de conexión a la base de datos.";
    error_log("Error de conexión BD en ajax/get_arl.php");
}

header('Content-Type: application/json');
echo json_encode($response);
?>