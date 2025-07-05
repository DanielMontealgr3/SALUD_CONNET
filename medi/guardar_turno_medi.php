<?php
// ARCHIVO: guardar_turno_medi.php
require_once '../include/validar_sesion.php'; 
require_once '../include/inactividad.php'; 
require_once '../include/conexion.php';
require_once '../include/email_service.php'; 

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

$db = new Database(); 
$pdo = $db->conectar(); 

// 1. Recolección y validación de datos del POST
$id_historia = filter_input(INPUT_POST, 'id_historia', FILTER_VALIDATE_INT); 
$fecha = trim($_POST['fecha'] ?? ''); 
$id_horario_farm = filter_input(INPUT_POST, 'hora_id', FILTER_VALIDATE_INT);
$doc_a_consultar = trim($_POST['doc_paciente'] ?? '');

if (empty($id_historia) || empty($fecha) || empty($id_horario_farm) || empty($doc_a_consultar)) { 
    echo json_encode(['status' => 'error', 'message' => 'Datos incompletos. Faltan parámetros esenciales.']);
    exit;
}

try {
    // 2. Validaciones de negocio (tiempo real y disponibilidad)
    date_default_timezone_set('America/Bogota');
    if ($fecha === date('Y-m-d')) {
        $stmtHoraValidar = $pdo->prepare("SELECT horario FROM horario_farm WHERE id_horario_farm = ?");
        $stmtHoraValidar->execute([$id_horario_farm]);
        $hora_turno_str = $stmtHoraValidar->fetchColumn();
        if ($hora_turno_str) {
            $hora_limite_dt = new DateTime("now");
            $hora_limite_dt->add(new DateInterval('PT30M'));
            $hora_del_turno_dt = new DateTime($fecha . ' ' . $hora_turno_str);
            if ($hora_del_turno_dt < $hora_limite_dt) {
                throw new Exception("La hora seleccionada ya no está disponible.");
            }
        }
    }

    $pdo->beginTransaction();

    $stmtDisponibilidad = $pdo->prepare("SELECT COUNT(*) FROM turno_ent_medic WHERE fecha_entreg = ? AND hora_entreg = ? FOR UPDATE"); 
    $stmtDisponibilidad->execute([$fecha, $id_horario_farm]);
    if ($stmtDisponibilidad->fetchColumn() > 0) { 
        throw new Exception("La hora que seleccionó acaba de ser ocupada. Por favor, elija otra."); 
    }
    
    // 3. Inserción en la base de datos
    $insert = $pdo->prepare("INSERT INTO turno_ent_medic (fecha_entreg, hora_entreg, id_historia, id_est) VALUES (?, ?, ?, 3)");
    $insert->execute([$fecha, $id_horario_farm, $id_historia]);

    // 4. Envío de correo (si aplica)
    $stmtPaciente = $pdo->prepare("SELECT nom_usu, correo_usu FROM usuarios WHERE doc_usu = ?");
    $stmtPaciente->execute([$doc_a_consultar]);
    $paciente = $stmtPaciente->fetch(PDO::FETCH_ASSOC);

    if ($paciente && !empty($paciente['correo_usu'])) {
        $stmtHora = $pdo->prepare("SELECT TIME_FORMAT(meridiano, '%h:%i %p') AS hora_legible FROM horario_farm WHERE id_horario_farm = ?");
        $stmtHora->execute([$id_horario_farm]);
        $hora_turno = $stmtHora->fetchColumn();

        $stmtMeds = $pdo->prepare("SELECT m.nom_medicamento FROM detalles_histo_clini dhc JOIN medicamentos m ON dhc.id_medicam = m.id_medicamento WHERE dhc.id_historia = ? AND dhc.id_medicam IS NOT NULL AND dhc.id_medicam != 0");
        $stmtMeds->execute([$id_historia]);
        $medicamentos_para_correo = $stmtMeds->fetchAll(PDO::FETCH_COLUMN);
        
        $datosCorreo = [
            'email_paciente'  => $paciente['correo_usu'], 
            'nombre_paciente' => $paciente['nom_usu'],
            'fecha_turno'     => date("d/m/Y", strtotime($fecha)),
            'hora_turno'      => $hora_turno ?: 'N/D',
            'medicamentos'    => !empty($medicamentos_para_correo) ? $medicamentos_para_correo : ['Sin medicamentos especificados']
        ];
        enviarCorreoConfirmacionTurno($datosCorreo); // No detenemos el proceso si el correo falla
    }
    
    // 5. Confirmar transacción y enviar respuesta de éxito
    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Turno agendado con éxito. Se ha enviado una confirmación al correo del paciente.']);

} catch (Exception $e) { 
    if ($pdo->inTransaction()) { $pdo->rollBack(); } 
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>