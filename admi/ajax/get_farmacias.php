<?php
require_once '../../include/conexion.php';

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}

$farmacias_list = [];
if ($con) {
    try {
        $stmt = $con->query("SELECT nit_farm, nom_farm FROM farmacias ORDER BY nom_farm ASC");
        $farmacias_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener Farmacias: " . $e->getMessage());
    }
}
header('Content-Type: application/json');
echo json_encode($farmacias_list);
?>