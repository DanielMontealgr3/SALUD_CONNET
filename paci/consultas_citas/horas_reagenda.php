<?php
// Archivo: paci/consultas_citas/horas_reagenda.php

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
$tipo = $_POST['tipo'] ?? ''; 

if (empty($fecha_seleccionada_str) || empty($tipo)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Faltan datos para la consulta.']);
    exit;
}

try {
    $todos_los_horarios = [];
    $ids_horarios_ocupados = [];

    if ($tipo === 'medica') {
        $sql_base = "SELECT id_horario_med AS id, horario, meridiano, id_estado FROM horario_medico WHERE fecha_horario = :fecha ORDER BY meridiano, horario";
        $stmt_base = $con->prepare($sql_base);
        $stmt_base->execute([':fecha' => $fecha_seleccionada_str]);
        $todos_los_horarios = $stmt_base->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $tabla_horario = ($tipo === 'medicamento') ? 'horario_farm' : 'horario_examen';
        $col_horario_id = ($tipo === 'medicamento') ? 'id_horario_farm' : 'id_horario_exan';
        $tabla_turno = ($tipo === 'medicamento') ? 'turno_ent_medic' : 'turno_examen';
        $col_turno_id = ($tipo === 'medicamento') ? 'hora_entreg' : 'hora_exam';
        $col_fecha_turno = ($tipo === 'medicamento') ? 'fecha_entreg' : 'fech_exam';

        $sql_base = "SELECT {$col_horario_id} AS id, horario, meridiano FROM {$tabla_horario} WHERE id_estado = 4 ORDER BY meridiano, horario";
        $stmt_base = $con->query($sql_base);
        $todos_los_horarios = $stmt_base->fetchAll(PDO::FETCH_ASSOC);

        $sql_ocupados = "SELECT {$col_turno_id} FROM {$tabla_turno} WHERE {$col_fecha_turno} = :fecha AND id_est IN (1, 3, 16)";
        $stmt_ocupados = $con->prepare($sql_ocupados);
        $stmt_ocupados->execute([':fecha' => $fecha_seleccionada_str]);
        $ids_horarios_ocupados = $stmt_ocupados->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    $horas_finales = [];
    $ahora = new DateTime("now", new DateTimeZone('America/Bogota'));
    $es_hoy = ($ahora->format('Y-m-d') === $fecha_seleccionada_str);

    foreach ($todos_los_horarios as $horario) {
        $esta_ocupado = ($tipo === 'medica') ? ($horario['id_estado'] != 4) : in_array($horario['id'], $ids_horarios_ocupados);
        if ($esta_ocupado) continue;

        $hora_obj = new DateTime($horario['horario']);
        if ($horario['meridiano'] == 2 && $hora_obj->format('H') < 12) {
            $hora_obj->add(new DateInterval('PT12H'));
        }
        
        if ($es_hoy && $hora_obj < $ahora) {
            continue;
        }

        $horas_finales[] = [
            'id'       => $horario['id'],
            'horario'  => $hora_obj->format('H:i:s'),
            'hora12'   => $hora_obj->format('h:i A')
        ];
    }

    echo json_encode(['hours' => $horas_finales]);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log("Error en horas_reagenda.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al consultar la disponibilidad horaria.']);
}
?>