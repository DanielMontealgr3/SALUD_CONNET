<?php
// --- RUTA CORREGIDA ---
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// La conexión $con ya está disponible desde config.php
$nit_farmacia = $_SESSION['nit_farma'] ?? null;
$umbral_stock_bajo = 10; // Considerar bajo stock si es <= 10 unidades

if (!$nit_farmacia) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No se pudo identificar la farmacia.']);
    exit;
}

// Consulta SQL con el nombre de la columna corregido
$sql = "SELECT 
            med.nom_medicamento,
            inv.cantidad_actual,
            med.codigo_barras
        FROM inventario_farmacia inv
        JOIN medicamentos med ON inv.id_medicamento = med.id_medicamento
        WHERE inv.nit_farm = :nit_farmacia
        AND inv.cantidad_actual <= :umbral
        ORDER BY inv.cantidad_actual ASC";

$stmt = $con->prepare($sql);
$stmt->bindParam(':nit_farmacia', $nit_farmacia, PDO::PARAM_STR);
$stmt->bindParam(':umbral', $umbral_stock_bajo, PDO::PARAM_INT);
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Asegurarse de que la respuesta siempre sea JSON
header('Content-Type: application/json');
echo json_encode($resultados);
?>