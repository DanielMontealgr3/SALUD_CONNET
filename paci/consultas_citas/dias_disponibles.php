<?php


require_once '../../include/conexion.php';

header('Content-Type: application/json');

$conex = new Database();
$con = $conex->conectar();

$doc_medico = $_GET['medico'] ?? null;
$start_str = $_GET['start'] ?? '';
$end_str = $_GET['end'] ?? '';

if (!$doc_medico || !$start_str || !$end_str) {
    echo json_encode([]);
    exit;
}

try {
    // Consulta: trae todas las fechas en rango para ese médico
    $sql = "SELECT fecha_horario, COUNT(*) as total 
            FROM horario_medico 
            WHERE doc_medico = :doc_medico 
              AND fecha_horario >= CURDATE()
              AND fecha_horario BETWEEN :start AND :end
            GROUP BY fecha_horario";

    $stmt = $con->prepare($sql);
    $stmt->execute([
        ':doc_medico' => $doc_medico,
        ':start' => $start_str,
        ':end' => $end_str
    ]);

    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $diasConHoras = [];

    // Por cada fecha, verificamos si tiene al menos una hora disponible (id_estado = 4)
    foreach ($resultado as $row) {
        $fecha = $row['fecha_horario'];

        $sqlHoras = "SELECT COUNT(*) 
                     FROM horario_medico 
                     WHERE doc_medico = :doc_medico 
                       AND fecha_horario = :fecha 
                       AND id_estado = 4";

        $stmtHoras = $con->prepare($sqlHoras);
        $stmtHoras->execute([
            ':doc_medico' => $doc_medico,
            ':fecha' => $fecha
        ]);

        $totalHorasDisponibles = $stmtHoras->fetchColumn();

        // Guardamos la fecha en el array con su disponibilidad
        $diasConHoras[$fecha] = $totalHorasDisponibles > 0;
    }

    echo json_encode($diasConHoras); // Devuelve un objeto { fecha: true/false }

} catch (PDOException $e) {
    error_log("Error en dias_disponibles.php (PDO): " . $e->getMessage());
    echo json_encode([]);
} catch (Exception $e) {
    error_log("Error general en dias_disponibles.php: " . $e->getMessage());
    echo json_encode([]);
}
?>
