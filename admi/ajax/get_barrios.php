<?php
require_once __DIR__ . '/../../include/conexion.php';
header('Content-Type: application/json');

// --- BLOQUE: OBTENCIÓN Y VALIDACIÓN DE PARÁMETROS ---
$id_mun = trim($_GET['id_mun'] ?? '');
$barrios = []; // Se inicializa para garantizar una respuesta JSON válida.

if (!empty($id_mun)) {
    try {
        // Conexión a la base de datos.
        $pdo = Database::connect();

        // --- CONSULTA SQL UNIFICADA ---
        $sql = "SELECT 
                    id_barrio, 
                    nom_barrio, 
                    id_barrio AS id, 
                    nom_barrio AS nombre 
                FROM barrio 
                WHERE id_mun = :id_mun 
                ORDER BY nom_barrio ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_mun', $id_mun, PDO::PARAM_INT);
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