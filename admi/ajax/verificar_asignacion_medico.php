<?php
require_once '../../include/conexion.php';
header('Content-Type: application/json');

if (isset($_POST['doc_medico'])) {
    $doc_medico = $_POST['doc_medico'];
    $response = ['asignado' => false, 'message' => 'Médico no encontrado o no asignado activamente a una IPS.'];

    $conex_db = new database();
    $con = $conex_db->conectar();

    if ($con) {
        try {
            $stmt = $con->prepare("SELECT COUNT(*) FROM asignacion_medico WHERE doc_medico = :doc_medico AND id_estado = 1");
            $stmt->bindParam(':doc_medico', $doc_medico, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                $response['asignado'] = true;
                $response['message'] = ''; 
            }
        } catch (PDOException $e) {
            $response['message'] = 'Error de base de datos: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Error de conexión a la base de datos.';
    }
    echo json_encode($response);
} else {
    echo json_encode(['asignado' => false, 'message' => 'No se proporcionó el documento del médico.']);
}
?>