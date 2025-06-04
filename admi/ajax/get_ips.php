<?php
require_once '../include/conexion.php';

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}

$ips_list = [];
if ($con) {
    try {
        $stmt = $con->query("SELECT Nit_IPS, nom_IPS FROM ips ORDER BY nom_IPS ASC");
        $ips_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener IPS: " . $e->getMessage());
    }
}
header('Content-Type: application/json');
echo json_encode($ips_list);
?>