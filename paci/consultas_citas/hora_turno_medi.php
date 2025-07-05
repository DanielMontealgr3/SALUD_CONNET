<?php
// =======================================================================================
// BLOQUE 1: CONFIGURACIÓN CENTRAL
// Incluye el config.php para tener acceso a la conexión ($con) y otras configuraciones.
// =======================================================================================
require_once __DIR__ . '/../../include/config.php';

// Configuración de la zona horaria para manejar correctamente la hora actual.
date_default_timezone_set('America/Bogota');
// Se especifica que la respuesta será en formato JSON.
header('Content-Type: application/json');

// =======================================================================================
// BLOQUE 2: OBTENCIÓN Y VALIDACIÓN DE PARÁMETROS
// Se recupera la fecha enviada por POST desde la llamada AJAX.
// =======================================================================================
$fecha_seleccionada_str = $_POST['fecha'] ?? '';

// Si no se proporcionó una fecha, se devuelve un error en formato JSON.
if (empty($fecha_seleccionada_str)) {
    echo json_encode(['error' => 'No se proporcionó una fecha.']);
    exit;
}

// =======================================================================================
// BLOQUE 3: LÓGICA DE CONSULTA Y PROCESAMIENTO
// Se obtienen los horarios de farmacia y se filtran los que ya están ocupados o pasados.
// =======================================================================================
try {
    // 1. Obtener TODOS los horarios base disponibles para farmacia (estado 4).
    $sql_base = "SELECT id_horario_farm AS id, horario, meridiano FROM horario_farm WHERE id_estado = 4 ORDER BY meridiano, horario";
    $stmt_base = $con->query($sql_base);
    $todos_los_horarios = $stmt_base->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener los ID de los horarios YA OCUPADOS para la fecha seleccionada.
    $sql_ocupados = "SELECT hora_entreg FROM turno_ent_medic WHERE fecha_entreg = :fecha AND id_est IN (1, 3, 16)";
    $stmt_ocupados = $con->prepare($sql_ocupados);
    $stmt_ocupados->execute([':fecha' => $fecha_seleccionada_str]);
    $ids_horarios_ocupados = $stmt_ocupados->fetchAll(PDO::FETCH_COLUMN, 0);

    // 3. Procesar en PHP para filtrar y formatear los horarios.
    $horas_finales = [];
    $ahora = new DateTime(); // Zona horaria ya definida al inicio.
    $es_hoy = ($ahora->format('Y-m-d') === $fecha_seleccionada_str);

    foreach ($todos_los_horarios as $horario) {
        $isOccupied = in_array($horario['id'], $ids_horarios_ocupados);
        
        $hora_obj = new DateTime($horario['horario']);
        if ($horario['meridiano'] == 2 && $hora_obj->format('H') < 12) {
            $hora_obj->add(new DateInterval('PT12H'));
        }
        
        // Si es hoy, se omiten las horas que ya pasaron.
        if ($es_hoy && $hora_obj < $ahora) {
            continue;
        }

        // Se construye el array con los datos formateados para el frontend.
        $horas_finales[] = [
            'id'         => $horario['id'],
            'hora12'     => $hora_obj->format('h:i A'),
            'isOccupied' => $isOccupied
        ];
    }

    // Se devuelve el resultado final en formato JSON.
    echo json_encode(['hours' => $horas_finales]);

} catch (PDOException $e) {
    error_log("Error en hora_turno_medi.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al consultar la disponibilidad horaria.']);
}
?>