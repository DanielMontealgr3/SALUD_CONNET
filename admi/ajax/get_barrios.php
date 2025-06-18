<?php
require_once __DIR__ . '/../../include/conexion.php';
header('Content-Type: application/json');

$id_mun = trim($_GET['id_mun'] ?? '');
$barrios = [];

if (!empty($id_mun)) {
    try {
        $pdo = Database::connect();
        $sql = "SELECT id_barrio, nom_barrio FROM barrio WHERE id_mun = :id_mun ORDER BY nom_barrio ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_mun', $id_mun, PDO::PARAM_STR);
        $stmt->execute();
        $barrios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Database::disconnect();
    } catch (PDOException $e) {
        error_log("Error en get_barrios.php: " . $e->getMessage());
        $barrios = [];
    }
}

echo json_encode($barrios);
?>