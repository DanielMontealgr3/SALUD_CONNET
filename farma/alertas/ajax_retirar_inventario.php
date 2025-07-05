<?php
// --- RUTA CORREGIDA ---
// Sube dos niveles desde 'farma/alertas/' para encontrar la carpeta 'include/'.
require_once __DIR__ . '/../../include/config.php';

// El resto de los require_once ya no son necesarios porque config.php los maneja.

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Acción no válida.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_medicamento = filter_input(INPUT_POST, 'id_medicamento', FILTER_VALIDATE_INT);
    $lote = trim($_POST['lote'] ?? '');
    $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);
    $motivo = trim($_POST['motivo'] ?? '');
    $doc_farmaceuta = $_SESSION['doc_usu'] ?? null;
    $nit_farmacia = $_SESSION['nit_farma'] ?? null;

    if (!$id_medicamento || !$lote || !$cantidad || !$motivo || !$doc_farmaceuta || !$nit_farmacia) {
        $response['message'] = 'Datos insuficientes para procesar la solicitud.';
        echo json_encode($response);
        exit;
    }
    
    $notas = 'Salida de inventario no especificada.';
    if ($motivo === 'vencido') {
        $notas = 'Salida por vencimiento de lote.';
    } elseif ($motivo === 'proximo_vencer') {
        $notas = 'Retiro por producto próximo a vencer.';
    }

    try {
        // La conexión $con ya está disponible desde config.php
        $con->beginTransaction();

        $stmt_vencimiento = $con->prepare("SELECT MIN(fecha_vencimiento) FROM movimientos_inventario WHERE id_medicamento = ? AND lote = ? AND nit_farm = ?");
        $stmt_vencimiento->execute([$id_medicamento, $lote, $nit_farmacia]);
        $fecha_vencimiento = $stmt_vencimiento->fetchColumn();

        if(!$fecha_vencimiento) {
             throw new Exception("No se pudo encontrar la fecha de vencimiento para el lote especificado.");
        }

        $sql_insert = "INSERT INTO movimientos_inventario (id_medicamento, nit_farm, id_usuario_responsable, id_tipo_mov, cantidad, lote, fecha_vencimiento, notas) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $con->prepare($sql_insert);
        $stmt_insert->execute([
            $id_medicamento,
            $nit_farmacia,
            $doc_farmaceuta,
            4, 
            $cantidad,
            $lote,
            $fecha_vencimiento,
            $notas
        ]);
        
        $con->commit();
        $response = ['success' => true, 'message' => 'Lote retirado del inventario correctamente.'];

    } catch (PDOException | Exception $e) {
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
    }
    
    echo json_encode($response);
}
?>