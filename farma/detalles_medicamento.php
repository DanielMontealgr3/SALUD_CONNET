<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../include/conexion.php';
require_once '../include/validar_sesion.php';

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'message' => 'ID de medicamento no válido.']);
    exit;
}
if (!isset($_SESSION['nit_farmacia_asignada_actual'])) {
    echo json_encode(['success' => false, 'message' => 'No se pudo identificar la farmacia.']);
    exit;
}

$id_medicamento = $_GET['id'];
$nit_farmacia = $_SESSION['nit_farmacia_asignada_actual'];

try {
    $db = new database();
    $con = $db->conectar();

    $sql = "SELECT 
                m.nom_medicamento,
                m.descripcion,
                m.codigo_barras,
                tm.nom_tipo_medi,
                i.cantidad_actual,
                e.nom_est,
                e.id_est
            FROM 
                medicamentos m
            JOIN 
                tipo_de_medicamento tm ON m.id_tipo_medic = tm.id_tip_medic
            LEFT JOIN
                inventario_farmacia i ON m.id_medicamento = i.id_medicamento AND i.nit_farm = :nit_farma
            LEFT JOIN
                estado e ON i.id_estado = e.id_est
            WHERE 
                m.id_medicamento = :id_medicamento";
    
    $stmt = $con->prepare($sql);
    $stmt->execute([
        ':id_medicamento' => $id_medicamento,
        ':nit_farma' => $nit_farmacia
    ]);
    $medicamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($medicamento) {
        echo json_encode(['success' => true, 'medicamento' => $medicamento]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró el medicamento.']);
    }

} catch (PDOException $e) {
    error_log("Error al obtener detalles de medicamento: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos.']);
}
?>