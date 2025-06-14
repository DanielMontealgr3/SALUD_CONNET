<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$response = ['success' => false, 'message' => 'Acción no válida.'];
$accion = $_POST['accion'] ?? '';
$db = new database();
$con = $db->conectar();
$doc_farmaceuta = $_SESSION['doc_usu'] ?? null;
$nit_farmacia = $_SESSION['nit_farmacia_asignada_actual'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($accion) || !$doc_farmaceuta || !$nit_farmacia) {
    echo json_encode(['success' => false, 'message' => 'Petición o sesión inválida.']);
    exit;
}

if ($accion === 'verificar_stock_pendiente') {
    $id_detalle = filter_input(INPUT_POST, 'id_detalle', FILTER_VALIDATE_INT);
    if (!$id_detalle) {
        echo json_encode(['success' => false, 'message' => 'ID de detalle no válido.']);
        exit;
    }
    
    try {
        $stmt_pendiente_info = $con->prepare("SELECT ep.cantidad_pendiente, i.cantidad_actual, d.id_medicam FROM entrega_pendiente ep JOIN detalles_histo_clini d ON ep.id_detalle_histo = d.id_detalle LEFT JOIN inventario_farmacia i ON d.id_medicam = i.id_medicamento AND i.nit_farm = :nit_farm WHERE ep.id_detalle_histo = :id_detalle AND ep.id_estado = 10");
        $stmt_pendiente_info->execute([':nit_farm' => $nit_farmacia, ':id_detalle' => $id_detalle]);
        $data = $stmt_pendiente_info->fetch(PDO::FETCH_ASSOC);

        if (!$data) { throw new Exception("No se encontró información del pendiente."); }

        $cantidad_requerida_pendiente = (int)$data['cantidad_pendiente'];
        $stock_actual = (int)$data['cantidad_actual'];

        if ($stock_actual < $cantidad_requerida_pendiente) {
            throw new Exception("Stock insuficiente. Requeridas: {$cantidad_requerida_pendiente}, Disponible: {$stock_actual}.");
        }
        
        $response = ['success' => true, 'message' => 'Stock suficiente.'];

    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }

} elseif ($accion === 'validar_y_entregar' || $accion === 'entregar_pendiente') {
    $id_detalle = filter_input(INPUT_POST, 'id_detalle', FILTER_VALIDATE_INT);
    $id_entrega_pendiente = filter_input(INPUT_POST, 'id_entrega_pendiente', FILTER_VALIDATE_INT);

    if (!$id_detalle) {
        echo json_encode(['success' => false, 'message' => 'ID de detalle no válido.']);
        exit;
    }

    try {
        $con->beginTransaction();
        
        $stmt_detalle = $con->prepare("SELECT id_medicam, can_medica FROM detalles_histo_clini WHERE id_detalle = :id_detalle");
        $stmt_detalle->execute([':id_detalle' => $id_detalle]);
        $detalle_medicamento = $stmt_detalle->fetch(PDO::FETCH_ASSOC);
        if (!$detalle_medicamento) { throw new Exception("Detalle de medicamento no encontrado."); }
        
        $id_medicamento = $detalle_medicamento['id_medicam'];
        $cantidad_necesaria = 0;

        if ($accion === 'entregar_pendiente') {
            $stmt_cant = $con->prepare("SELECT cantidad_pendiente FROM entrega_pendiente WHERE id_detalle_histo = :id_detalle AND id_estado = 10");
            $stmt_cant->execute([':id_detalle' => $id_detalle]);
            $cantidad_necesaria = (int)$stmt_cant->fetchColumn();
        } else { // 'validar_y_entregar'
            $cantidad_necesaria = (int)$detalle_medicamento['can_medica'];
        }

        if ($cantidad_necesaria <= 0) { throw new Exception("La cantidad requerida no es válida."); }

        $stmt_stock_total = $con->prepare("SELECT cantidad_actual FROM inventario_farmacia WHERE id_medicamento = :id_medicamento AND nit_farm = :nit_farm");
        $stmt_stock_total->execute([':id_medicamento' => $id_medicamento, ':nit_farm' => $nit_farmacia]);
        $stock_total_actual = (int)$stmt_stock_total->fetchColumn();

        if ($accion === 'entregar_pendiente' && $stock_total_actual < $cantidad_necesaria) {
            throw new Exception("Stock insuficiente para completar el pendiente. Stock actual: $stock_total_actual. Unidades requeridas: $cantidad_necesaria");
        }

        $cantidad_a_entregar_real = min($cantidad_necesaria, $stock_total_actual);
        $cantidad_a_dejar_pendiente = $cantidad_necesaria - $cantidad_a_entregar_real;
        
        $entregas_realizadas = [];
        $radicado_generado = null;

        if ($cantidad_a_entregar_real > 0) {
            $sql_lotes = "SELECT lote, SUM(CASE WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad ELSE -cantidad END) AS stock_lote FROM movimientos_inventario WHERE id_medicamento = :id_medicamento AND nit_farm = :nit_farm GROUP BY lote HAVING stock_lote > 0 ORDER BY fecha_vencimiento ASC";
            $stmt_lotes = $con->prepare($sql_lotes);
            $stmt_lotes->execute([':id_medicamento' => $id_medicamento, ':nit_farm' => $nit_farmacia]);
            $lotes_disponibles = $stmt_lotes->fetchAll(PDO::FETCH_ASSOC);
            $cantidad_restante_por_entregar = $cantidad_a_entregar_real;

            foreach ($lotes_disponibles as $lote) {
                if ($cantidad_restante_por_entregar <= 0) break;
                $cantidad_a_sacar_de_lote = min($cantidad_restante_por_entregar, (int)$lote['stock_lote']);
                $sql_insert = "INSERT INTO entrega_medicamentos (id_detalle_histo, doc_farmaceuta, id_estado, lote, cantidad_entregada, Observaciones) VALUES (:id_detalle, :doc_farma, 9, :lote, :cantidad, 'Entrega.')";
                $stmt_insert = $con->prepare($sql_insert);
                $stmt_insert->execute([':id_detalle' => $id_detalle, ':doc_farma' => $doc_farmaceuta, ':lote' => $lote['lote'], ':cantidad' => $cantidad_a_sacar_de_lote]);
                $entregas_realizadas[] = ['lote' => $lote['lote'], 'cantidad' => $cantidad_a_sacar_de_lote];
                $cantidad_restante_por_entregar -= $cantidad_a_sacar_de_lote;
            }
        }
        
        if ($accion !== 'entregar_pendiente' && $cantidad_a_dejar_pendiente > 0) {
            $radicado_generado = 'PEND-' . strtoupper(substr(uniqid(), -8));
            $sql_pendiente = "INSERT INTO entrega_pendiente (id_detalle_histo, radicado_pendiente, id_farmaceuta_genera, cantidad_pendiente, id_estado) VALUES (:id_detalle_histo, :radicado, :doc_farma, :cantidad_pendiente, 10)";
            $stmt_pendiente = $con->prepare($sql_pendiente);
            $stmt_pendiente->execute([
                ':id_detalle_histo' => $id_detalle,
                ':radicado' => $radicado_generado,
                ':doc_farma' => $doc_farmaceuta,
                ':cantidad_pendiente' => $cantidad_a_dejar_pendiente
            ]);
        }
        
        $con->commit();
        $response = ['success' => true, 'message' => 'Proceso completado.', 'entregas' => $entregas_realizadas, 'pendiente' => $cantidad_a_dejar_pendiente, 'radicado' => $radicado_generado];
    } catch (Exception $e) {
        $con->rollBack();
        $response['message'] = 'Error al procesar: ' . $e->getMessage();
    }

} elseif ($accion === 'finalizar_entrega_pendiente') {
    $id_entrega_pendiente = filter_input(INPUT_POST, 'id_entrega_pendiente', FILTER_VALIDATE_INT);
    if ($id_entrega_pendiente) {
        try {
            $stmt = $con->prepare("UPDATE entrega_pendiente SET id_estado = 9 WHERE id_entrega_pendiente = :id_entrega_pendiente AND id_estado = 10");
            $stmt->execute([':id_entrega_pendiente' => $id_entrega_pendiente]);
            $response = ['success' => true, 'message' => 'Entrega de pendiente finalizada y estado actualizado.'];
        } catch (PDOException $e) {
            $response['message'] = 'Error al actualizar el estado del pendiente: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'ID de pendiente para actualizar no fue proporcionado.';
    }

} elseif ($accion === 'finalizar_turno') {
    $id_turno = filter_input(INPUT_POST, 'id_turno', FILTER_VALIDATE_INT);
    if ($id_turno) {
        try {
            $con->beginTransaction();
            $stmt_get_horario = $con->prepare("SELECT hora_entreg FROM turno_ent_medic WHERE id_turno_ent = :id_turno");
            $stmt_get_horario->execute([':id_turno' => $id_turno]);
            $id_horario = $stmt_get_horario->fetchColumn();
            $stmt_turno = $con->prepare("UPDATE turno_ent_medic SET id_est = 9 WHERE id_turno_ent = :id_turno");
            $stmt_turno->execute([':id_turno' => $id_turno]);
            if ($id_horario) {
                $stmt_horario = $con->prepare("UPDATE horario_farm SET id_estado = 4 WHERE id_horario_farm = :id_horario");
                $stmt_horario->execute([':id_horario' => $id_horario]);
            }
            $stmt_vista = $con->prepare("DELETE FROM vista_televisor WHERE id_turno = :id_turno");
            $stmt_vista->execute([':id_turno' => $id_turno]);
            $con->commit();
            $response = ['success' => true, 'message' => 'Turno finalizado y recursos liberados.'];
        } catch (PDOException $e) {
            $con->rollBack();
            $response['message'] = 'Error al finalizar el turno: ' . $e->getMessage();
        }
    } else {
        $response = ['success' => false, 'message' => 'ID de turno no proporcionado.'];
    }
}

echo json_encode($response);
?>