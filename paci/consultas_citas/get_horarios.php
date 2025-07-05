<?php
// Archivo: paci/consultas_citas/get_horarios.php
header('Content-Type: application/json');
require_once '../../include/conexion.php'; 

$response = ['success' => false, 'horarios' => []];

if (isset($_POST['fecha'])) {
    $fecha_seleccionada = $_POST['fecha'];
    $conex = new Database();
    $con = $conex->conectar();
    
    try {
        // 1. Obtener TODOS los horarios disponibles para el día
        $sql_horarios_base = "
            SELECT id_horario_farm, horario, meridiano
            FROM horario_farm 
            WHERE id_estado = 4
            ORDER BY meridiano ASC, horario ASC
        ";
        $stmt_horarios = $con->prepare($sql_horarios_base);
        $stmt_horarios->execute();
        $todos_los_horarios = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);

        // 2. Obtener los ID de los horarios YA OCUPADOS para esa fecha
        $sql_ocupados = "
            SELECT hora_entreg 
            FROM turno_ent_medic 
            WHERE fecha_entreg = :fecha AND id_est IN (1, 3, 16)
        ";
        $stmt_ocupados = $con->prepare($sql_ocupados);
        $stmt_ocupados->execute([':fecha' => $fecha_seleccionada]);
        $ids_horarios_ocupados = $stmt_ocupados->fetchAll(PDO::FETCH_COLUMN);

        // 3. Procesar en PHP para filtrar y formatear
        $ahora = new DateTime("now", new DateTimeZone('America/Bogota'));
        $es_hoy = ($ahora->format('Y-m-d') === $fecha_seleccionada);
        
        foreach ($todos_los_horarios as $horario) {
            // Verificar si el horario está en la lista de ocupados
            if (in_array($horario['id_horario_farm'], $ids_horarios_ocupados)) {
                continue; // Si está ocupado, saltar al siguiente
            }

            // --- LÓGICA DE FILTRADO DE HORA CORREGIDA ---
            if ($es_hoy) {
                // Convertimos la hora de la BD (ej. 02:30:00) a un objeto DateTime del día de hoy
                $hora_cita_obj = new DateTime($horario['horario']);
                
                // Ajustamos el AM/PM si es necesario.
                // Si el meridiano es PM (2) y la hora es menor a 12 (ej. 01:00:00), le sumamos 12 horas.
                if ($horario['meridiano'] == 2 && $hora_cita_obj->format('H') < 12) {
                    $hora_cita_obj->add(new DateInterval('PT12H'));
                }
                
                // Obtenemos la hora actual con un margen de 30 minutos
                $hora_limite = (clone $ahora)->add(new DateInterval("PT30M"));

                // Comparamos la hora de la cita con la hora límite
                if ($hora_cita_obj->format('H:i') < $hora_limite->format('H:i')) {
                    continue; // Omitir esta hora porque ya pasó o está muy cerca
                }
            }

            // Formatear la hora para mostrarla al usuario
            $hora_base_12h = date('h:i', strtotime($horario['horario']));
            $meridiano_texto = ($horario['meridiano'] == 1) ? 'AM' : 'PM';
            $hora_formateada_12h = $hora_base_12h . ' ' . $meridiano_texto;

            // Añadir el horario válido a la respuesta final
            $response['horarios'][] = [
                'id_horario_farm' => $horario['id_horario_farm'],
                'hora_formato'    => $hora_formateada_12h
            ];
        }

        $response['success'] = true;
        
    } catch (Exception $e) { // Usamos Exception genérico para capturar errores de DateTime también
        $response['message'] = 'Error interno del servidor: ' . $e->getMessage();
        error_log("Error en get_horarios.php: " . $e->getMessage());
    }
} else {
    $response['message'] = 'No se proporcionó una fecha.';
}

echo json_encode($response);
?>