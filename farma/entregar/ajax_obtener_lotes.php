<?php
header('Content-Type: application/json');
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$id_medicamento = filter_input(INPUT_GET, 'id_medicamento', FILTER_VALIDATE_INT);
$nit_farmacia = $_SESSION['nit_farmacia_asignada_actual'] ?? null;

if (!$id_medicamento || !$nit_farmacia) {
    echo json_encode(['success' => false, 'message' => 'Datos insuficientes para la consulta.']);
    exit;
}

try {
    $db = new database();
    $con = $db->conectar();
    
    $sql = "SELECT 
                lote, 
                fecha_vencimiento,
                SUM(CASE 
                    WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad 
                    WHEN id_tipo_mov IN (2, 4) THEN -cantidad 
                    ELSE 0 
                END) AS stock_lote,
                DATEDIFF(fecha_vencimiento, CURDATE()) as dias_restantes
            FROM movimientos_inventario
            WHERE 
                id_medicamento = :id_medicamento 
                AND nit_farm = :nit_farm
            GROUP BY lote, fecha_vencimiento
            HAVING stock_lote > 0
            ORDER BY fecha_vencimiento ASC";

    $stmt = $con->prepare($sql);
    $stmt->execute([':id_medicamento' => $id_medicamento, ':nit_farm' => $nit_farmacia]);
    $lotes_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $lotes_procesados = [];
    foreach($lotes_raw as $lote) {
        $estado = 'vigente';
        if ($lote['dias_restantes'] < 0) {
            $estado = 'vencido';
        } elseif ($lote['dias_restantes'] <= 15) {
            $estado = 'proximo_vencer';
        }
        $lote['estado'] = $estado;
        $lotes_procesados[] = $lote;
    }

    echo json_encode(['success' => true, 'lotes' => $lotes_procesados]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>