<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../include/conexion.php';
require_once '../../include/validar_sesion.php';

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'message' => 'ID de medicamento no válido.']);
    exit;
}

$id_medicamento = $_GET['id'];

try {
    $db = new database();
    $con = $db->conectar();
    
    $sql = "SELECT 
                m.nom_medicamento, 
                m.descripcion, 
                m.codigo_barras, 
                tm.nom_tipo_medi 
            FROM medicamentos m 
            JOIN tipo_de_medicamento tm ON m.id_tipo_medic = tm.id_tip_medic 
            WHERE m.id_medicamento = :id_medicamento";
            
    $stmt = $con->prepare($sql);
    $stmt->execute([':id_medicamento' => $id_medicamento]);
    $medicamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($medicamento) {
        echo json_encode(['success' => true, 'medicamento' => $medicamento]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró el medicamento.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos.']);
}
?>