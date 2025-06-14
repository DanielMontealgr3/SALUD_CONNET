<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Acción no válida.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_medicamento = filter_input(INPUT_POST, 'id_medicamento', FILTER_VALIDATE_INT);
    $lote = trim($_POST['lote'] ?? '');
    $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);
    $doc_farmaceuta = $_SESSION['doc_usu'] ?? null;
    $nit_farmacia = $_SESSION['nit_farma'] ?? null;

    if (!$id_medicamento || !$lote || !$cantidad || !$doc_farmaceuta || !$nit_farmacia) {
        $response['message'] = 'Datos insuficientes para procesar la solicitud.';
        echo json_encode($response);
        exit;
    }

    try {
        $db = new database();
        $con = $db->conectar();
        $con->beginTransaction();

        $stmt_vencimiento = $con->prepare("SELECT MIN(fecha_vencimiento) FROM movimientos_inventario WHERE id_medicamento = ? AND lote = ?");
        $stmt_vencimiento->execute([$id_medicamento, $lote]);
        $fecha_vencimiento = $stmt_vencimiento->fetchColumn();

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
            'Salida por vencimiento de lote.'
        ]);
        
        $con->commit();
        $response = ['success' => true, 'message' => 'Lote retirado del inventario correctamente.'];

    } catch (PDOException $e) {
        $con->rollBack();
        $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
    }
    
    echo json_encode($response);
}
?>