<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$response = ['success' => false, 'message' => 'Acceso denegado o método no permitido.', 'pendientes_cubiertos' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_medicamento'])) {
    $id_medicamento = filter_var($_POST['id_medicamento'], FILTER_VALIDATE_INT);
    $cantidad = filter_var($_POST['cantidad'], FILTER_VALIDATE_INT);
    $lote = trim($_POST['lote'] ?? '');
    $fecha_vencimiento = trim($_POST['fecha_vencimiento'] ?? '');
    $notas = trim($_POST['notas'] ?? null);
    
    $nit_farm = $_SESSION['nit_farmacia_asignada_actual'] ?? null;
    $documento_responsable = $_SESSION['doc_usu'] ?? null;
    
    if (!$id_medicamento || !$cantidad || $cantidad <= 0 || empty($lote) || empty($fecha_vencimiento) || empty($nit_farm) || !$documento_responsable) {
        $response['message'] = 'Todos los campos son obligatorios o falta información de sesión del responsable.';
        echo json_encode($response);
        exit;
    }

    $db = new database();
    $con = $db->conectar();

    try {
        $con->beginTransaction();

        $id_tipo_movimiento_entrada = 1; 
        $sql = "INSERT INTO movimientos_inventario (id_medicamento, nit_farm, id_usuario_responsable, id_tipo_mov, cantidad, lote, fecha_vencimiento, notas) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        
        if ($stmt->execute([$id_medicamento, $nit_farm, $documento_responsable, $id_tipo_movimiento_entrada, $cantidad, $lote, $fecha_vencimiento, $notas])) {
            
            $stmt_stock = $con->prepare("SELECT cantidad_actual FROM inventario_farmacia WHERE id_medicamento = ? AND nit_farm = ?");
            $stmt_stock->execute([$id_medicamento, $nit_farm]);
            $stock_actual = (int)$stmt_stock->fetchColumn();

            $sql_pendientes = "SELECT u.nom_usu, dh.can_medica FROM entrega_pendiente ep JOIN detalles_histo_clini dh ON ep.id_detalle_histo = dh.id_detalle JOIN historia_clinica hc ON dh.id_historia = hc.id_historia JOIN citas c ON hc.id_cita = c.id_cita JOIN usuarios u ON c.doc_pac = u.doc_usu WHERE dh.id_medicam = ? AND ep.id_estado = 10 AND dh.can_medica <= ?";
            $stmt_pendientes = $con->prepare($sql_pendientes);
            $stmt_pendientes->execute([$id_medicamento, $stock_actual]);
            $pendientes_cubiertos = $stmt_pendientes->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['message'] = 'Entrada de inventario registrada con éxito.';
            $response['pendientes_cubiertos'] = $pendientes_cubiertos;
            
            $con->commit();
        } else {
            $con->rollBack();
            $response['message'] = 'No se pudo ejecutar la inserción en la base de datos.';
        }

    } catch (PDOException $e) {
        $con->rollBack();
        $response['message'] = 'Se produjo un error en la base de datos.';
        error_log("Error en ajax_registrar_entrada: " . $e->getMessage());
    }
}

echo json_encode($response);
?>