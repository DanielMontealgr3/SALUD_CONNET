<?php
// Archivo: paci/consultas_citas/hora_turno_medi.php

// =================================================================
// === INICIO DEL BLOQUE CORREGIDO (PORTABILIDAD) ===
// =================================================================

// 1. Inclusión de la configuración centralizada.
// Esto establece ROOT_PATH, BASE_URL, inicia sesión y conecta a la BD.
// La ruta sube dos niveles porque se asume que este archivo está en un subdirectorio.
require_once __DIR__ . '/../../include/config.php';

date_default_timezone_set('America/Bogota');
header('Content-Type: application/json');

// La variable de conexión `$con` ya está disponible desde config.php.
// No es necesario crear una nueva instancia de Database.

// =================================================================
// === FIN DEL BLOQUE CORREGIDO ===
// El resto del código permanece exactamente igual.
// =================================================================

$fecha_seleccionada_str = $_POST['fecha'] ?? '';

if (empty($fecha_seleccionada_str)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'No se proporcionó una fecha.']);
    exit;
}

try {
    // 1. Obtener TODOS los horarios base disponibles para farmacia
    $sql_base = "SELECT id_horario_farm AS id, horario, meridiano FROM horario_farm WHERE id_estado = 4 ORDER BY meridiano, horario";
    $stmt_base = $con->query($sql_base);
    $todos_los_horarios = $stmt_base->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener los ID de los horarios YA OCUPADOS para esa fecha
    $sql_ocupados = "SELECT hora_entreg FROM turno_ent_medic WHERE fecha_entreg = :fecha AND id_est IN (1, 3, 16)";
    $stmt_ocupados = $con->prepare($sql_ocupados);
    $stmt_ocupados->execute([':fecha' => $fecha_seleccionada_str]);
    $ids_horarios_ocupados = $stmt_ocupados->fetchAll(PDO::FETCH_COLUMN, 0);

    // 3. Procesar en PHP para filtrar y formatear
    $horas_finales = [];
    $ahora = new DateTime("now", new DateTimeZone('America/Bogota'));
    $es_hoy = ($ahora->format('Y-m-d') === $fecha_seleccionada_str);

    foreach ($todos_los_horarios as $horario) {
        // Verificar si el horario está en la lista de ocupados
        $isOccupied = in_array($horario['id'], $ids_horarios_ocupados);
        
        // Formatear la hora correctamente
        $hora_obj = new DateTime($horario['horario']);
        if ($horario['meridiano'] == 2 && $hora_obj->format('H') < 12) {
            $hora_obj->add(new DateInterval('PT12H'));
        }
        
        // Omitir horas que ya pasaron en el día de hoy
        if ($es_hoy && $hora_obj < $ahora) {
            continue;
        }

        $horas_finales[] = [
            'id'         => $horario['id'],
            'hora12'     => $hora_obj->format('h:i A'),
            'isOccupied' => $isOccupied
        ];
    }

    echo json_encode(['hours' => $horas_finales]);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    error_log("Error en hora_turno_medi.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al consultar la disponibilidad horaria.']);
}
?>