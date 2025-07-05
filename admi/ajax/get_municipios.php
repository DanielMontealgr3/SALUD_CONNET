<?php
require_once __DIR__ . '/../../include/conexion.php';
header('Content-Type: application/json');

// --- BLOQUE: OBTENCIÓN Y VALIDACIÓN DE PARÁMETROS ---
$id_dep = trim($_GET['id_dep'] ?? '');
$municipios = []; 

if (!empty($id_dep)) {
    try {
        $pdo = Database::connect();

        $sql = "SELECT 
                    id_mun, 
                    nom_mun, 
                    id_mun AS id, 
                    nom_mun AS nombre 
                FROM municipio 
                WHERE id_dep = :id_dep 
                ORDER BY nom_mun ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_dep', $id_dep, PDO::PARAM_INT);
        $stmt->execute();

        $municipios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Se cierra la conexión.
        Database::disconnect();

    } catch (PDOException $e) {
        // --- BLOQUE: MANEJO DE ERRORES ---
        error_log("Error en get_municipios.php: " . $e->getMessage());
        $municipios = []; 
    }
}

echo json_encode($municipios);
?>