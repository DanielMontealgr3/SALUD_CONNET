<?php
require_once __DIR__ . '/../../include/conexion.php';
header('Content-Type: application/json');

$id_dep = trim($_GET['id_dep'] ?? '');
$municipios = [];

if (!empty($id_dep)) {
    try {
        $pdo = Database::connect();
        $sql = "SELECT id_mun, nom_mun FROM municipio WHERE id_dep = :id_dep ORDER BY nom_mun ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_dep', $id_dep, PDO::PARAM_STR);
        $stmt->execute();
        $municipios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Database::disconnect();
    } catch (PDOException $e) {
        // En un entorno de producción, registrar el error en lugar de mostrarlo
        error_log("Error en get_municipios.php: " . $e->getMessage());
        // Devolver un array vacío en caso de error
        $municipios = []; 
    }
}

echo json_encode($municipios);
?>