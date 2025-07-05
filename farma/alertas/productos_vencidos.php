<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$db = new database();
$con = $db->conectar();

$nit_farmacia = $_SESSION['nit_farma'] ?? null;

if (!$nit_farmacia) {
    echo json_encode(['error' => 'No se pudo identificar la farmacia.']);
    exit;
}

$stock_por_lote_sql = "(SELECT SUM(CASE WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad ELSE -cantidad END) FROM movimientos_inventario WHERE lote = mi.lote AND id_medicamento = mi.id_medicamento AND nit_farm = mi.nit_farm)";

$sql = "SELECT 
            mi.id_medicamento,
            med.nom_medicamento,
            med.codigo_barras,
            mi.lote, 
            mi.fecha_vencimiento,
            $stock_por_lote_sql AS stock_lote
        FROM movimientos_inventario mi
        JOIN medicamentos med ON mi.id_medicamento = med.id_medicamento
        WHERE mi.nit_farm = :nit_farmacia
        AND mi.fecha_vencimiento < CURDATE()
        GROUP BY mi.id_medicamento, mi.lote, mi.fecha_vencimiento
        HAVING stock_lote > 0
        ORDER BY mi.fecha_vencimiento DESC";

$stmt = $con->prepare($sql);
$stmt->bindParam(':nit_farmacia', $nit_farmacia, PDO::PARAM_STR);
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($resultados);
?>