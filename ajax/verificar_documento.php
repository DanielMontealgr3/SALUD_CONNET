<?php
require_once __DIR__ . '/../include/conexion.php';

$response = ['isAvailable' => false, 'message' => 'Error en la solicitud.'];
header('Content-Type: application/json'); 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doc_usu']) && isset($_POST['id_tipo_doc'])) {
    $doc_usu = trim($_POST['doc_usu']); 
    $id_tipo_doc = filter_var(trim($_POST['id_tipo_doc']), FILTER_VALIDATE_INT); 

    if (empty($doc_usu) || empty($id_tipo_doc)) {
        $response['message'] = 'Documento o tipo de documento no proporcionado.';
        echo json_encode($response);
        exit;
    }

    $conex_db = new database();
    $con = $conex_db->conectar();

    if ($con) {
        try {
            $sql = "SELECT COUNT(*) FROM usuarios WHERE doc_usu = :doc_usu AND id_tipo_doc = :id_tipo_doc";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':doc_usu', $doc_usu, PDO::PARAM_STR);
            $stmt->bindParam(':id_tipo_doc', $id_tipo_doc, PDO::PARAM_INT);
            $stmt->execute(); 
            $count = $stmt->fetchColumn(); 

            if ($count == 0) { 
                $response['isAvailable'] = true;
                $response['message'] = 'Documento disponible.';
            } else {
                $response['isAvailable'] = false;
                $response['message'] = 'Este documento con el tipo seleccionado ya está registrado.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Error al verificar el documento: ' . $e->getMessage();
            error_log("Error en ajax/verificar_documento.php: " . $e->getMessage()); 
        }
    } else {
        $response['message'] = 'Error de conexión a la base de datos.';
    }
    database::disconnect(); 
} else {
    $response['message'] = 'Solicitud no válida o datos incompletos.';
}

echo json_encode($response);
?>