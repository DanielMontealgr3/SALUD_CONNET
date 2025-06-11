<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$response = ['success' => false, 'message' => 'Acceso denegado o método no permitido.'];

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

    try {
        $db = new database();
        $con = $db->conectar();
        
        $id_tipo_movimiento_entrada = 1; 

        $sql = "INSERT INTO movimientos_inventario 
                    (id_medicamento, nit_farm, id_usuario_responsable, id_tipo_mov, cantidad, lote, fecha_vencimiento, notas) 
                VALUES 
                    (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $con->prepare($sql);
        
        if ($stmt->execute([$id_medicamento, $nit_farm, $documento_responsable, $id_tipo_movimiento_entrada, $cantidad, $lote, $fecha_vencimiento, $notas])) {
            $response['success'] = true;
            $response['message'] = 'Entrada de inventario registrada con éxito.';
        } else {
            $response['message'] = 'No se pudo ejecutar la inserción en la base de datos.';
        }

    } catch (PDOException $e) {
        $response['message'] = 'Se produjo un error en la base de datos.';
        error_log("Error en ajax_registrar_entrada: " . $e->getMessage());
    }
}

echo json_encode($response);
?>  