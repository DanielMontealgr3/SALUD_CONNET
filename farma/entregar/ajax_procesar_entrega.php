<?php
// --- BLOQUE 1: CONFIGURACIÓN Y SEGURIDAD ---
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// Se define la respuesta como JSON desde el principio.
header('Content-Type: application/json; charset=utf-8');

// --- BLOQUE 2: VALIDACIÓN INICIAL Y PREPARACIÓN ---
$response = ['success' => false, 'message' => 'Petición o sesión inválida.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido.';
    http_response_code(405);
    echo json_encode($response);
    exit;
}

$accion = $_POST['accion'] ?? '';
$doc_farmaceuta = $_SESSION['doc_usu'] ?? null;
// Corregido para usar la variable de sesión consistente
$nit_farmacia = $_SESSION['nit_farma'] ?? $_SESSION['nit_farmacia_asignada_actual'] ?? null;

// Se usa la conexión global $con de config.php.
if (empty($accion) || !$doc_farmaceuta || !$nit_farmacia) {
    http_response_code(403);
    echo json_encode($response);
    exit;
}

// --- BLOQUE 3: CONTROLADOR DE ACCIONES (switch) ---
try {
    // Se usa la conexión global y se establece el modo de error
    global $con;
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    switch ($accion) {

        case 'verificar_stock_pendiente':
            $id_detalle = filter_input(INPUT_POST, 'id_detalle', FILTER_VALIDATE_INT);
            if (!$id_detalle) throw new Exception("ID de detalle no válido.");
            
            $stmt_info = $con->prepare("SELECT d.id_medicam, ep.cantidad_pendiente FROM entrega_pendiente ep JOIN detalles_histo_clini d ON ep.id_detalle_histo = d.id_detalle WHERE ep.id_detalle_histo = :id_detalle AND ep.id_estado = 10");
            $stmt_info->execute([':id_detalle' => $id_detalle]);
            $info = $stmt_info->fetch(PDO::FETCH_ASSOC);

            if (!$info) throw new Exception("No se encontró información del pendiente.");

            $id_medicamento = $info['id_medicam'];
            $cantidad_requerida = (int)$info['cantidad_pendiente'];

            $sql_stock = "SELECT SUM(stock_lote) FROM (SELECT SUM(CASE WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad WHEN id_tipo_mov IN (2, 4) THEN -cantidad ELSE 0 END) as stock_lote FROM movimientos_inventario WHERE id_medicamento = :id_m AND nit_farm = :nit_f AND fecha_vencimiento > DATE_ADD(CURDATE(), INTERVAL 15 DAY) GROUP BY lote, fecha_vencimiento HAVING stock_lote > 0) as lotes_vigentes";
            $stmt_stock = $con->prepare($sql_stock);
            $stmt_stock->execute([':id_m' => $id_medicamento, ':nit_f' => $nit_farmacia]);
            $stock_valido = (int)$stmt_stock->fetchColumn();

            if ($stock_valido < $cantidad_requerida) {
                throw new Exception("Stock insuficiente. Requeridas: {$cantidad_requerida}, Disponibles (lotes vigentes): {$stock_valido}.");
            }
            
            $response = ['success' => true, 'message' => 'Stock suficiente.'];
            break;

        case 'validar_y_entregar':
        case 'entregar_pendiente':
            $id_detalle = filter_input(INPUT_POST, 'id_detalle', FILTER_VALIDATE_INT);
            $id_entrega_pendiente = filter_input(INPUT_POST, 'id_entrega_pendiente', FILTER_VALIDATE_INT);
            if (!$id_detalle) throw new Exception("ID de detalle no válido.");

            $con->beginTransaction();
            
            $stmt_detalle = $con->prepare("SELECT id_medicam, can_medica FROM detalles_histo_clini WHERE id_detalle = :id_detalle");
            $stmt_detalle->execute([':id_detalle' => $id_detalle]);
            $detalle_medicamento = $stmt_detalle->fetch(PDO::FETCH_ASSOC);
            if (!$detalle_medicamento) throw new Exception("Detalle de medicamento no encontrado.");
            
            $id_medicamento = $detalle_medicamento['id_medicam'];
            
            if ($accion === 'entregar_pendiente' && $id_entrega_pendiente) {
                $stmt_cant = $con->prepare("SELECT cantidad_pendiente FROM entrega_pendiente WHERE id_entrega_pendiente = :id_p AND id_estado = 10");
                $stmt_cant->execute([':id_p' => $id_entrega_pendiente]);
                $cantidad_necesaria = (int)$stmt_cant->fetchColumn();
            } else {
                $cantidad_necesaria = (int)$detalle_medicamento['can_medica'];
            }

            if ($cantidad_necesaria <= 0) throw new Exception("La cantidad requerida no es válida.");
            
            $sql_lotes = "SELECT lote, fecha_vencimiento, SUM(CASE WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad WHEN id_tipo_mov IN (2, 4) THEN -cantidad ELSE 0 END) as stock_lote FROM movimientos_inventario WHERE id_medicamento = :id_m AND nit_farm = :nit_f AND fecha_vencimiento > CURDATE() GROUP BY lote, fecha_vencimiento HAVING stock_lote > 0 ORDER BY fecha_vencimiento ASC";
            $stmt_lotes = $con->prepare($sql_lotes);
            $stmt_lotes->execute([':id_m' => $id_medicamento, ':nit_f' => $nit_farmacia]);
            $lotes_disponibles = $stmt_lotes->fetchAll(PDO::FETCH_ASSOC);
            
            $stock_total_disponible = array_sum(array_column($lotes_disponibles, 'stock_lote'));
            $cantidad_a_entregar = min($cantidad_necesaria, $stock_total_disponible);
            $cantidad_pendiente = $cantidad_necesaria - $cantidad_a_entregar;
            
            $entregas_realizadas = [];
            $radicado_generado = null;

            if ($cantidad_a_entregar > 0) {
                $cantidad_restante_por_entregar = $cantidad_a_entregar;
                foreach ($lotes_disponibles as $lote) {
                    if ($cantidad_restante_por_entregar <= 0) break;
                    
                    $cantidad_a_sacar_de_lote = min($cantidad_restante_por_entregar, (int)$lote['stock_lote']);
                    
                    $sql_insert = "INSERT INTO entrega_medicamentos (id_detalle_histo, doc_farmaceuta, id_estado, lote, cantidad_entregada, Observaciones) VALUES (:id_detalle, :doc_farma, 9, :lote, :cantidad, 'Entrega completa de lote.')";
                    $stmt_insert = $con->prepare($sql_insert);
                    $stmt_insert->execute([':id_detalle' => $id_detalle, ':doc_farma' => $doc_farmaceuta, ':lote' => $lote['lote'], ':cantidad' => $cantidad_a_sacar_de_lote]);
                    
                    $entregas_realizadas[] = ['lote' => $lote['lote'], 'cantidad' => $cantidad_a_sacar_de_lote];
                    $cantidad_restante_por_entregar -= $cantidad_a_sacar_de_lote;
                }
            }
            
            if ($accion !== 'entregar_pendiente' && $cantidad_pendiente > 0) {
                $radicado_generado = 'PEND-' . strtoupper(substr(uniqid(), -8));
                $sql_pendiente = "INSERT INTO entrega_pendiente (id_detalle_histo, radicado_pendiente, id_farmaceuta_genera, cantidad_pendiente, id_estado) VALUES (:id_detalle, :radicado, :doc_farma, :cantidad_p, 10)";
                $stmt_pendiente = $con->prepare($sql_pendiente);
                $stmt_pendiente->execute([':id_detalle' => $id_detalle, ':radicado' => $radicado_generado, ':doc_farma' => $doc_farmaceuta, ':cantidad_p' => $cantidad_pendiente]);
            }
            
            $con->commit();
            $response = ['success' => true, 'message' => 'Proceso de entrega completado.', 'entregas' => $entregas_realizadas, 'pendiente' => $cantidad_pendiente, 'radicado' => $radicado_generado];
            break;

        case 'finalizar_entrega_pendiente':
            $id_entrega_pendiente = filter_input(INPUT_POST, 'id_entrega_pendiente', FILTER_VALIDATE_INT);
            if (!$id_entrega_pendiente) throw new Exception("ID de pendiente no válido.");

            $stmt = $con->prepare("UPDATE entrega_pendiente SET id_estado = 9 WHERE id_entrega_pendiente = :id AND id_estado = 10");
            $stmt->execute([':id' => $id_entrega_pendiente]);
            $response = ['success' => true, 'message' => 'Pendiente finalizado correctamente.'];
            break;

        case 'finalizar_turno':
            $id_turno = filter_input(INPUT_POST, 'id_turno', FILTER_VALIDATE_INT);
            if (!$id_turno) throw new Exception("ID de turno no proporcionado.");
            
            $con->beginTransaction();
            $stmt_turno = $con->prepare("UPDATE turno_ent_medic SET id_est = 9 WHERE id_turno_ent = :id_turno");
            $stmt_turno->execute([':id_turno' => $id_turno]);
            $stmt_vista = $con->prepare("DELETE FROM vista_televisor WHERE id_turno = :id_turno");
            $stmt_vista->execute([':id_turno' => $id_turno]);
            $con->commit();
            $response = ['success' => true, 'message' => 'Turno finalizado y recursos liberados.'];
            break;

        default:
            throw new Exception('La acción solicitada no es válida.');
            break;
    }
} catch (Exception $e) {
    if ($con->inTransaction()) {
        $con->rollBack();
    }
    http_response_code(500);
    $response['message'] = $e->getMessage();
    error_log("Error en ajax_procesar_entrega.php: " . $e->getMessage());
}

// --- BLOQUE 4: RESPUESTA FINAL ---
echo json_encode($response);
?>