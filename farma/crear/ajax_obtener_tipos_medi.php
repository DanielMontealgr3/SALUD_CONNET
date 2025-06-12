<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../include/conexion.php';

$response = ['success' => false, 'tipos' => []];

try {
    $db = new database();
    $con = $db->conectar();
    
    $stmt = $con->query("SELECT id_tip_medic, nom_tipo_medi FROM tipo_de_medicamento ORDER BY nom_tipo_medi ASC");
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($tipos) {
        $response['success'] = true;
        $response['tipos'] = $tipos;
    }
} catch (PDOException $e) {
    // No se envía mensaje de error al cliente, solo se registra
    error_log($e->getMessage());
}

echo json_encode($response);
?>