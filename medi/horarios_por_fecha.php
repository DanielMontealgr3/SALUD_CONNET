<?php
require_once('../include/conexion.php');
$db = new Database();
$pdo = $db->conectar();

$fecha = $_GET['fecha'] ?? null;

if ($fecha) {
    $stmt = $pdo->prepare("
        SELECT 
            id_horario_med, 
            fecha_horario, 
            CONCAT(horario, ' ', IF(meridiano = 1, 'AM', 'PM')) AS horario_completo 
        FROM horario_medico 
        WHERE id_estado = 4 AND fecha_horario = :fecha
    ");
    $stmt->execute(['fecha' => $fecha]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($resultados);
} else {
    echo json_encode([]);
}
?>
