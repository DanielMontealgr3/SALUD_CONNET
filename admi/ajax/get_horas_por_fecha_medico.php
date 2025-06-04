<?php
require_once '../../include/conexion.php';
header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

$response = ['success' => false, 'horas' => [], 'message' => ''];

if (isset($_POST['doc_medico']) && isset($_POST['fecha_horario'])) {
    $doc_medico = $_POST['doc_medico'];
    $fecha_horario = $_POST['fecha_horario'];
    
    $conex_db = new database();
    $con = $conex_db->conectar();

    if ($con) {
        try {
            $stmt = $con->prepare("
                SELECT 
                    hm.horario, 
                    m.periodo,
                    e.id_est,
                    e.nom_est
                FROM horario_medico hm
                LEFT JOIN meridiano m ON hm.meridiano = m.id_periodo
                LEFT JOIN estado e ON hm.id_estado = e.id_est
                WHERE hm.doc_medico = :doc_medico AND hm.fecha_horario = :fecha_horario
                ORDER BY hm.horario ASC
            ");
            $stmt->bindParam(':doc_medico', $doc_medico, PDO::PARAM_STR);
            $stmt->bindParam(':fecha_horario', $fecha_horario, PDO::PARAM_STR);
            $stmt->execute();
            $horas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['horas'] = $horas;
        } catch (PDOException $e) {
            $response['message'] = 'Error de base de datos: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Error de conexión a la base de datos.';
    }
} else {
    $response['message'] = 'Faltan parámetros (médico o fecha).';
}

echo json_encode($response);
?>