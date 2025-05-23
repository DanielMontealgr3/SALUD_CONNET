<?php
require_once __DIR__ . '/../include/conexion.php';

$eps_list = [];
$response = ['success' => false, 'data' => [], 'message' => 'Error inicial o no autorizado.'];

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$conex_db = new database();
$con = $conex_db->conectar();

if ($con) {
    try {
        $stmt = $con->query("SELECT nit_eps, nombre_eps FROM eps ORDER BY nombre_eps ASC");
        $eps_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['success'] = true;
        $response['data'] = array_map(function($item) {
            return ['id' => $item['nit_eps'], 'nombre' => $item['nombre_eps']];
        }, $eps_list);
        $response['message'] = 'EPS cargadas exitosamente.';

    } catch (PDOException $e) {
        $response['message'] = "Error al cargar EPS: " . $e->getMessage();
        error_log("Error en ajax/get_eps.php: " . $e->getMessage());
    }
} else {
    $response['message'] = "Error de conexión a la base de datos.";
    error_log("Error de conexión BD en ajax/get_eps.php");
}

header('Content-Type: application/json');
echo json_encode($response);
?>