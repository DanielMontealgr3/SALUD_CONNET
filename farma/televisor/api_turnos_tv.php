<?php
ob_start();
require_once '../../include/conexion.php';
require_once '../../include/validar_sesion.php';

function send_json_error($message) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message]);
    exit;
}

date_default_timezone_set('America/Bogota');

if (!isset($_SESSION['id_rol']) || !in_array($_SESSION['id_rol'], [1, 3])) {
    send_json_error('Acceso no autorizado.');
}

$nit_farmacia_sesion = $_SESSION['nit_farmacia_asignada_actual'] ?? null;
if (!$nit_farmacia_sesion) {
    send_json_error('No se pudo determinar la farmacia.');
}

try {
    $db = new database();
    $con = $db->conectar();
} catch (Exception $e) {
    send_json_error('Error de conexión a la base de datos.');
}

$sql_base = "
    SELECT
        vt.id_turno,
        u_paciente.nom_usu AS nombre_paciente,
        u_farmaceuta.nom_usu AS nombre_farmaceuta,
        vt.id_estado,
        'Módulo de Entrega' AS modulo_atencion,
        vt.hora_llamado
    FROM vista_televisor vt
    JOIN asignacion_farmaceuta af ON vt.id_farmaceuta = af.doc_farma AND af.id_estado = 1 AND af.nit_farma = :nit_farma
    JOIN usuarios u_farmaceuta ON vt.id_farmaceuta = u_farmaceuta.doc_usu
    JOIN turno_ent_medic tem ON vt.id_turno = tem.id_turno_ent
    JOIN historia_clinica hc ON tem.id_historia = hc.id_historia
    JOIN citas c ON hc.id_cita = c.id_cita
    JOIN usuarios u_paciente ON c.doc_pac = u_paciente.doc_usu
    WHERE vt.id_estado IN (1, 11)
    ORDER BY vt.hora_llamado DESC
";

try {
    $stmt = $con->prepare($sql_base);
    $stmt->execute([':nit_farma' => $nit_farmacia_sesion]);
    $todos_los_turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    send_json_error('Error al consultar los turnos.');
}

$turnos_notificacion = [];
$turnos_llamando = [];
$turnos_atencion = [];
$ahora = new DateTime();

foreach ($todos_los_turnos as $turno) {
    if ($turno['id_estado'] == 1) { 
        $turnos_llamando[] = $turno;
        $hora_llamado = new DateTime($turno['hora_llamado']);
        $diferencia = $ahora->getTimestamp() - $hora_llamado->getTimestamp();
        
        if ($diferencia <= 5) {
            $turnos_notificacion[] = $turno;
        }
    }
    if ($turno['id_estado'] == 11) { 
        $turnos_atencion[] = $turno; 
    }
}

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'notificacion' => array_slice($turnos_notificacion, 0, 1),
    'llamando' => array_slice($turnos_llamando, 0, 5),
    'en_atencion' => array_slice($turnos_atencion, 0, 10)
]);
?>