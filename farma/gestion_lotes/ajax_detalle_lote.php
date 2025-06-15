<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

header('Content-Type: application/json');

$id_medicamento = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$lote_str = trim($_GET['lote'] ?? '');
$nit_farmacia = $_SESSION['nit_farmacia_asignada_actual'] ?? null;

if (!$id_medicamento || !$lote_str || !$nit_farmacia) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos.']);
    exit;
}

try {
    $db = new database();
    $con = $db->conectar();
    
    $stock_por_lote_sql = "SUM(CASE WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad WHEN id_tipo_mov IN (2, 4) THEN -cantidad ELSE 0 END)";
    $sql = "SELECT lote, fecha_vencimiento, $stock_por_lote_sql AS stock_lote, DATEDIFF(fecha_vencimiento, CURDATE()) AS dias_restantes 
            FROM movimientos_inventario 
            WHERE id_medicamento = :id_medicamento AND lote = :lote AND nit_farm = :nit_farm 
            GROUP BY lote, fecha_vencimiento";
            
    $stmt = $con->prepare($sql);
    $stmt->execute([
        ':id_medicamento' => $id_medicamento,
        ':lote' => $lote_str,
        ':nit_farm' => $nit_farmacia
    ]);
    $lote_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lote_data) {
        echo json_encode(['success' => true, 'data' => $lote_data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró el lote especificado.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
}