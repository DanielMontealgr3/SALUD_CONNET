<?php
// --- BLOQUE 1: CONFIGURACIÓN Y SEGURIDAD ---
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// Se define la respuesta como JSON.
header('Content-Type: application/json; charset=utf-8');

// --- BLOQUE 2: VALIDACIÓN DE ENTRADA ---
$id_medicamento = filter_input(INPUT_GET, 'id_medicamento', FILTER_VALIDATE_INT);
$nit_farmacia = $_SESSION['nit_farma'] ?? null; // Variable de sesión estandarizada.

if (!$id_medicamento || !$nit_farmacia) {
    echo json_encode(['success' => false, 'message' => 'Datos insuficientes para la consulta.']);
    exit;
}

// --- BLOQUE 3: CONSULTA A BASE DE DATOS ---
try {
    // Se usa la conexión global $con de config.php.
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

    // --- BLOQUE 4: PROCESAMIENTO Y RESPUESTA JSON ---
    $lotes_procesados = [];
    foreach($lotes_raw as $lote) {
        $estado = 'vigente';
        if ($lote['dias_restantes'] < 0) {
            $estado = 'vencido';
        } elseif ($lote['dias_restantes'] <= 30) { // Umbral común de 30 días para "próximo a vencer"
            $estado = 'proximo_vencer';
        }
        $lote['estado'] = $estado;
        $lotes_procesados[] = $lote;
    }

    echo json_encode(['success' => true, 'lotes' => $lotes_procesados]);

} catch (PDOException $e) {
    error_log("Error en ajax_obtener_lotes.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al consultar la base de datos.']);
}
?>