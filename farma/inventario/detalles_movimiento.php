<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Error inesperado.'];

if (!isset($_GET['id_movimiento']) || !is_numeric($_GET['id_movimiento'])) {
    $response['message'] = 'ID de movimiento no válido.';
    echo json_encode($response);
    exit;
}

$id_movimiento = intval($_GET['id_movimiento']);
$nit_farmacia_actual = $_SESSION['nit_farmacia_asignada_actual'];

$db = new database();
$con = $db->conectar();

try {
    $sql = "SELECT 
                mi.id_movimiento, 
                mi.cantidad, 
                mi.lote, 
                mi.fecha_vencimiento, 
                mi.fecha_movimiento, 
                mi.notas,
                med.nom_medicamento, 
                med.codigo_barras,
                tm.nom_mov, 
                mi.id_tipo_mov,
                u.nom_usu AS nombre_responsable, 
                u.doc_usu AS doc_responsable,
                f.nom_farm
            FROM movimientos_inventario mi
            JOIN medicamentos med ON mi.id_medicamento = med.id_medicamento
            JOIN tipo_movimiento tm ON mi.id_tipo_mov = tm.id_tipo_mov
            JOIN farmacias f ON mi.nit_farm = f.nit_farm
            LEFT JOIN usuarios u ON mi.id_usuario_responsable = u.doc_usu
            WHERE mi.id_movimiento = :id_movimiento AND mi.nit_farm = :nit_farm";

    $stmt = $con->prepare($sql);
    $stmt->execute([':id_movimiento' => $id_movimiento, ':nit_farm' => $nit_farmacia_actual]);
    $movimiento = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($movimiento) {
        $response['success'] = true;
        $response['data'] = $movimiento;
    } else {
        $response['message'] = 'No se encontró el movimiento o no tiene permiso para verlo.';
    }

} catch (PDOException $e) {
    $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
}

echo json_encode($response);