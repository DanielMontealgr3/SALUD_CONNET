<?php
// =======================================================================================
// BLOQUE 1: CONFIGURACIÓN CENTRAL
// Incluye el config.php para la conexión a la BD ($con).
// =======================================================================================
require_once __DIR__ . '/../../include/config.php';

// Configuración de la zona horaria y el tipo de contenido de la respuesta.
date_default_timezone_set('America/Bogota');
header('Content-Type: application/json');

// =======================================================================================
// BLOQUE 2: OBTENCIÓN Y VALIDACIÓN DE PARÁMETROS
// Se recuperan los datos enviados por POST desde la llamada AJAX.
// =======================================================================================
$doc_medico = $_POST['doc_med'] ?? '';
$fecha_seleccionada_str = $_POST['fecha'] ?? '';

// =======================================================================================
// ¡¡NUEVO BLOQUE DE DEPURACIÓN!!
// Esto guardará en tu archivo de log de errores (error_log) los datos que recibe el script.
// Así sabremos con certeza qué está llegando desde el JavaScript.
// =======================================================================================
error_log("horas_disponibles.php recibió: Medico -> {$doc_medico}, Fecha -> {$fecha_seleccionada_str}");

// Validación de los datos recibidos
if (empty($doc_medico) || empty($fecha_seleccionada_str)) {
    error_log("Error: Datos incompletos. Medico o Fecha vacíos.");
    echo json_encode(['error' => 'Faltan datos para la consulta.']);
    exit;
}

// =======================================================================================
// BLOQUE 3: LÓGICA DE CONSULTA Y FILTRADO (CORREGIDA)
// Se obtienen las horas disponibles y se filtran si la cita es para el día actual.
// =======================================================================================
try {
    // La consulta SQL está bien, el problema es cómo se le pasan los datos.
    // Usar placeholders como :fecha es la forma correcta y segura.
    $sql = "SELECT horario, meridiano FROM horario_medico 
            WHERE doc_medico = :doc_medico 
            AND fecha_horario = :fecha
            AND id_estado = 4
            ORDER BY meridiano, horario ASC"; // Ordenamos por meridiano y luego por hora
            
    $stmt = $con->prepare($sql);

    // El método execute se encarga de poner las comillas y escapar los valores.
    // Aquí nos aseguramos de que todo se pase como debe ser.
    $stmt->execute([
        ':doc_medico' => $doc_medico,
        ':fecha' => $fecha_seleccionada_str, // PDO se encarga de tratarlo como string con comillas.
    ]);
    
    $horas_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log para saber qué devolvió la base de datos
    error_log("La consulta a la BD devolvió: " . count($horas_db) . " filas.");

    $horas_formateadas = [];
    
    foreach ($horas_db as $row) {
        $time_obj = new DateTime($row['horario']);
        
        // La corrección clave para el meridiano:
        if ($row['meridiano'] == 2 && $time_obj->format('H') < 12) {
            $time_obj->add(new DateInterval('PT12H'));
        }

        $horas_formateadas[] = [
            'horario' => $time_obj->format('H:i'), 
            'hora12'  => $time_obj->format('h:i A')
        ];
    }

    // Lógica de filtrado para el día de hoy.
    $ahora = new DateTime();
    $fecha_seleccionada_obj = new DateTime($fecha_seleccionada_str);
    $response = [];

    if ($ahora->format('Y-m-d') === $fecha_seleccionada_obj->format('Y-m-d')) {
        $horas_futuras = [];
        $margen_minutos = 30;
        $hora_limite = (clone $ahora)->add(new DateInterval("PT{$margen_minutos}M"));

        foreach ($horas_formateadas as $hora) {
            $hora_cita_obj = new DateTime($fecha_seleccionada_str . ' ' . $hora['horario']);
            if ($hora_cita_obj > $hora_limite) {
                $horas_futuras[] = $hora;
            }
        }
        $response['hours'] = $horas_futuras;
    } else {
        $response['hours'] = $horas_formateadas;
    }
    
    // Se devuelve el resultado final en formato JSON.
    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Error de PDO en horas_disponibles.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al consultar la disponibilidad.']);
}
?>