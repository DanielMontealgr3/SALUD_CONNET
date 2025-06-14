<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$id_pendiente = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_pendiente) {
    echo json_encode(['success' => false, 'message' => 'ID no válido.']);
    exit;
}

try {
    $db = new database();
    $con = $db->conectar();

    $sql = "SELECT 
                ep.radicado_pendiente, ep.fecha_generacion, ep.cantidad_pendiente,
                u.nom_usu, u.doc_usu, u.tel_usu, u.correo_usu, u.direccion_usu,
                m.nom_medicamento,
                ug.nom_usu AS farmaceuta_genera
            FROM entrega_pendiente ep
            JOIN detalles_histo_clini dh ON ep.id_detalle_histo = dh.id_detalle
            JOIN historia_clinica hc ON dh.id_historia = hc.id_historia
            JOIN citas c ON hc.id_cita = c.id_cita
            JOIN usuarios u ON c.doc_pac = u.doc_usu
            JOIN medicamentos m ON dh.id_medicam = m.id_medicamento
            JOIN usuarios ug ON ep.id_farmaceuta_genera = ug.doc_usu
            WHERE ep.id_entrega_pendiente = :id_pendiente";

    $stmt = $con->prepare($sql);
    $stmt->execute([':id_pendiente' => $id_pendiente]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron detalles para este pendiente.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
}
?>