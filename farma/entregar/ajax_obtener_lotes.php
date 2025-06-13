<?php
header('Content-Type: application/json');
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$id_medicamento = filter_input(INPUT_GET, 'id_medicamento', FILTER_VALIDATE_INT);
$nit_farmacia = $_SESSION['nit_farmacia_asignada_actual'] ?? null;

if (!$id_medicamento || !$nit_farmacia) {
    echo json_encode(['success' => false, 'message' => 'Datos insuficientes.']);
    exit;
}

try {
    $db = new database();
    $con = $db->conectar();
    
    $sql = "SELECT lote, fecha_vencimiento, SUM(CASE WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad ELSE -cantidad END) AS stock_lote
            FROM movimientos_inventario
            WHERE id_medicamento = :id_medicamento AND nit_farm = :nit_farm
            GROUP BY lote, fecha_vencimiento
            HAVING stock_lote > 0
            ORDER BY fecha_vencimiento ASC, fecha_movimiento ASC";

    $stmt = $con->prepare($sql);
    $stmt->execute([':id_medicamento' => $id_medicamento, ':nit_farm' => $nit_farmacia]);
    $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'lotes' => $lotes]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
}
?>