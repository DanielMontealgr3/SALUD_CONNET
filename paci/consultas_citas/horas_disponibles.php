<?php
// ARCHIVO: consultas_citas/horas_disponibles.php (Versión Corregida y Mejorada)

require_once '../../include/conexion.php';

// --- PASO 1: FIJAR LA ZONA HORARIA ---
// Esto es CRUCIAL para que la comparación de "ahora" sea correcta.
date_default_timezone_set('America/Bogota'); 

header('Content-Type: application/json');

$conex = new Database();
$con = $conex->conectar();

$doc_medico = $_POST['doc_med'] ?? '';
$fecha_seleccionada_str = $_POST['fecha'] ?? '';

if (empty($doc_medico) || empty($fecha_seleccionada_str)) {
    echo json_encode(['error' => 'Faltan datos para la consulta.']);
    exit;
}

try {
    // --- PASO 2: CONSULTA A LA BASE DE DATOS (SIN CAMBIOS, ESTABA BIEN) ---
    $estado_disponible = 4;
    // He quitado la columna `meridiano` porque no la usas y es mejor calcular AM/PM con date().
    $sql = "SELECT horario FROM horario_medico 
            WHERE doc_medico = :doc_medico 
            AND fecha_horario = :fecha
            AND id_estado = :id_estado
            ORDER BY horario ASC";

    $stmt = $con->prepare($sql);
    $stmt->execute([
        ':doc_medico' => $doc_medico,
        ':fecha' => $fecha_seleccionada_str,
        ':id_estado' => $estado_disponible
    ]);

    $horas_disponibles_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $horas_formateadas = [];
    
    foreach ($horas_disponibles_db as $row) {
        $time24_str = $row['horario']; // e.g., "14:30:00"
        $time_obj = new DateTime($time24_str);
        
        $horas_formateadas[] = [
            'horario' => $time_obj->format('H:i'), // Formato 24h para el valor (14:30)
            'hora12' => $time_obj->format('h:i A') // Formato 12h para mostrar (02:30 PM)
        ];
    }

    // --- PASO 3: LÓGICA DE FILTRADO PARA EL DÍA DE HOY ---
    $ahora = new DateTime(); // Hora actual de Colombia
    $fecha_seleccionada_obj = new DateTime($fecha_seleccionada_str);
    $response = [];

    // Comparamos si la fecha seleccionada es el día de hoy
    if ($ahora->format('Y-m-d') === $fecha_seleccionada_obj->format('Y-m-d')) {
        $horas_futuras = [];
        $margen_minutos = 30;
        
        // Calculamos la hora límite: la hora actual + 30 minutos
        $hora_limite = (clone $ahora)->add(new DateInterval("PT{$margen_minutos}M"));

        foreach ($horas_formateadas as $hora) {
            // Creamos un objeto DateTime para la hora de la cita
            $hora_cita_obj = new DateTime($fecha_seleccionada_str . ' ' . $hora['horario']);
            
            // Solo incluimos la hora si es posterior a la hora límite
            if ($hora_cita_obj > $hora_limite) {
                $horas_futuras[] = $hora;
            }
        }
        $response['hours'] = $horas_futuras;
    } else {
        // Si no es hoy, se muestran todas las horas disponibles sin filtrar
        $response['hours'] = $horas_formateadas;
    }
    
    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Error en horas_disponibles.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al consultar la disponibilidad.']);
}
?>