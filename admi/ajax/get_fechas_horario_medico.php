<?php
require_once '../../include/conexion.php';
header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

$response = ['success' => false, 'fechas' => [], 'message' => ''];

if (isset($_POST['doc_medico'])) {
    $doc_medico = $_POST['doc_medico'];
    $conex_db = new database();
    $con = $conex_db->conectar();

    if ($con) {
        try {
            $stmt = $con->prepare("
                SELECT DISTINCT fecha_horario 
                FROM horario_medico 
                WHERE doc_medico = :doc_medico AND fecha_horario >= CURDATE()
                ORDER BY fecha_horario ASC
            ");
            $stmt->bindParam(':doc_medico', $doc_medico, PDO::PARAM_STR);
            $stmt->execute();
            $fechas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['fechas'] = $fechas;
        } catch (PDOException $e) {
            $response['message'] = 'Error de base de datos: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Error de conexión a la base de datos.';
    }
} else {
    $response['message'] = 'No se proporcionó el documento del médico.';
}

echo json_encode($response);
?>