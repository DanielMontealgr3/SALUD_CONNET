<?php
require_once __DIR__ . '/../../include/config.php';

header('Content-Type: application/json');

// Validar sesión y rol
if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 4) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

$id_cita = filter_input(INPUT_POST, 'id_cita', FILTER_VALIDATE_INT);
if (!$id_cita) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de cita inválido.']);
    exit;
}

// Definimos los estados relevantes
define('ID_ESTADO_PROGRAMADA', 3);
define('ID_ESTADO_LISTA_PARA_LLAMAR', 10); // Este es el estado al que cambiaremos la cita

try {
    // Primero, verificamos que la cita esté en el estado correcto para ser actualizada
    $check_stmt = $con->prepare("SELECT id_est FROM citas WHERE id_cita = ?");
    $check_stmt->execute([$id_cita]);
    $current_state = $check_stmt->fetchColumn();
    
    if ($current_state == ID_ESTADO_PROGRAMADA) {
        $sql = "UPDATE citas SET id_est = ? WHERE id_cita = ?";
        $stmt = $con->prepare($sql);
        $stmt->execute([ID_ESTADO_LISTA_PARA_LLAMAR, $id_cita]);

        if ($stmt->rowCount() > 0) {
            // Obtenemos el nombre del nuevo estado para devolverlo
            $stmt_estado = $con->prepare("SELECT nom_est FROM estado WHERE id_est = ?");
            $stmt_estado->execute([ID_ESTADO_LISTA_PARA_LLAMAR]);
            $nuevo_estado = $stmt_estado->fetchColumn();
            echo json_encode(['success' => true, 'nuevo_estado' => $nuevo_estado]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la cita.']);
        }
    } elseif ($current_state == ID_ESTADO_LISTA_PARA_LLAMAR) {
         echo json_encode(['success' => true, 'message' => 'La cita ya estaba lista para llamar.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'La cita no se puede actualizar desde su estado actual.']);
    }

} catch (PDOException $e) {
    error_log("Error en ajax_cambiar_estado_cita.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
}