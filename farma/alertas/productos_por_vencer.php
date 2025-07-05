<?php
// --- RUTA CORREGIDA ---
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// La conexión $con ya está disponible desde config.php
$nit_farmacia = $_SESSION['nit_farma'] ?? null;
$dias_aviso = 30;

if (!$nit_farmacia) {
    echo json_encode(['error' => 'No se pudo identificar la farmacia.']);
    exit;
}

$stock_por_lote_sql = "(SELECT SUM(CASE WHEN id_tipo_mov = 1 THEN cantidad ELSE -cantidad END) FROM movimientos_inventario WHERE lote = mi.lote AND id_medicamento = mi.id_medicamento AND nit_farm = mi.nit_farm)";

$sql = "SELECT 
            med.nom_medicamento,
            mi.lote, 
            mi.fecha_vencimiento,
            DATEDIFF(mi.fecha_vencimiento, CURDATE()) AS dias_restantes,
            $stock_por_lote_sql AS stock_lote
        FROM movimientos_inventario mi
        JOIN medicamentos med ON mi.id_medicamento = med.id_medicamento
        WHERE mi.nit_farm = :nit_farmacia
        AND mi.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :dias_aviso DAY)
        GROUP BY mi.id_medicamento, mi.lote, mi.fecha_vencimiento
        HAVING stock_lote > 0
        ORDER BY dias_restantes ASC";

$stmt = $con->prepare($sql);
$stmt->bindParam(':nit_farmacia', $nit_farmacia, PDO::PARAM_STR);
$stmt->bindParam(':dias_aviso', $dias_aviso, PDO::PARAM_INT);
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($resultados);
?>