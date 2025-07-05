<?php
/**
 * @author     Salud-Connected
 * @version    4.7 (FINAL - con lógica de meridiano corregida)
 * @Description Script AJAX que valida y devuelve horarios en tiempo real.
 */
require_once '../include/validar_sesion.php';
require_once '../include/conexion.php';

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['fecha'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Solicitud no válida.']);
    exit;
}

$fecha_seleccionada_str = $_POST['fecha'];
$response = ['hours' => []];

try {
    $db = new Database();
    $pdo = $db->conectar();

    // 1. Establecer la zona horaria correcta
    date_default_timezone_set('America/Bogota');
    
    $es_hoy = ($fecha_seleccionada_str === date('Y-m-d'));
    
    $hora_limite_dt = null;
    if ($es_hoy) {
        $hora_limite_dt = new DateTime("now");
        $hora_limite_dt->add(new DateInterval('PT30M'));
    }

    // 2. Obtener horarios disponibles
    $stmt_horarios = $pdo->prepare("
        SELECT id_horario_farm, horario, meridiano
        FROM horario_farm
        WHERE id_estado = 4
        ORDER BY meridiano ASC, horario ASC
    ");
    $stmt_horarios->execute();
    $horarios_disponibles = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);

    if (empty($horarios_disponibles)) {
        throw new Exception("No hay horarios de farmacia configurados.");
    }

    // 3. Obtener horarios ocupados
    $stmt_ocupados = $pdo->prepare("SELECT hora_entreg FROM turno_ent_medic WHERE fecha_entreg = ?");
    $stmt_ocupados->execute([$fecha_seleccionada_str]);
    $horarios_ocupados = $stmt_ocupados->fetchAll(PDO::FETCH_COLUMN);

    // 4. Construir respuesta final
    foreach ($horarios_disponibles as $hora) {
        $is_past_or_too_soon = false;
        
        if ($es_hoy && $hora_limite_dt) {
            // =======================================================================
            // ==     CORRECCIÓN CLAVE: CONSTRUIR LA HORA CORRECTAMENTE CON AM/PM     ==
            // =======================================================================
            
            // a. Tomar la hora y separarla en partes
            list($h, $m, $s) = explode(':', $hora['horario']);
            $h = (int)$h;

            // b. Ajustar la hora a formato 24h usando la columna 'meridiano'
            if ($hora['meridiano'] == 2 && $h != 12) { // 2 = PM
                $h += 12;
            }
            if ($hora['meridiano'] == 1 && $h == 12) { // 1 = AM, y son las 12 AM
                $h = 0; // 12 AM es la hora 0
            }
            
            // c. Crear un objeto DateTime con la hora correcta en formato 24h
            $hora_del_turno_dt = new DateTime();
            $fecha_parts = date_parse($fecha_seleccionada_str);
            $hora_del_turno_dt->setDate($fecha_parts['year'], $fecha_parts['month'], $fecha_parts['day']);
            $hora_del_turno_dt->setTime($h, (int)$m, (int)$s);

            // d. Ahora la comparación es 100% precisa
            if ($hora_del_turno_dt < $hora_limite_dt) {
                $is_past_or_too_soon = true;
            }
        }
        
        $hora_formateada = date("h:i", strtotime($hora['horario']));
        $meridiano_texto = ($hora['meridiano'] == 1) ? 'AM' : 'PM';
        
        $response['hours'][] = [
            'id_horario_farm' => $hora['id_horario_farm'],
            'hora12'          => $hora_formateada . ' ' . $meridiano_texto,
            'isOccupied'      => in_array($hora['id_horario_farm'], $horarios_ocupados),
            'isPast'          => $is_past_or_too_soon
        ];
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("Error en consultar_horario_farmacia.php: " . $e->getMessage());
    $response = ['error' => "Ocurrió un error al cargar los horarios."];
}

echo json_encode($response);
?>