<?php
// --- BLOQUE 1: PREPARACIÓN Y SEGURIDAD ---
// Se inicia el buffer de salida para poder limpiar cualquier contenido no deseado antes de enviar la respuesta JSON.
ob_start();

// Se incluye el archivo de configuración central. La ruta __DIR__ . '/../../' sube dos niveles
// desde 'farma/televisor/' para encontrar la carpeta 'include/'.
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// --- BLOQUE 2: FUNCIÓN DE MANEJO DE ERRORES ---
// Una función centralizada para enviar respuestas de error en formato JSON y detener la ejecución.
function send_json_error($message) {
    ob_end_clean(); // Limpia el buffer de salida para evitar contenido extra.
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message]);
    exit;
}

// Se establece la zona horaria para consistencia en las operaciones de fecha y hora.
date_default_timezone_set('America/Bogota');

// Se verifica que el rol del usuario sea Administrador (1) o Farmaceuta (3).
if (!isset($_SESSION['id_rol']) || !in_array($_SESSION['id_rol'], [1, 3])) {
    send_json_error('Acceso no autorizado.');
}

// Se obtiene el NIT de la farmacia desde la sesión, que es crucial para filtrar los turnos.
$nit_farmacia_sesion = $_SESSION['nit_farma'] ?? null;
if (!$nit_farmacia_sesion) {
    send_json_error('No se pudo determinar la farmacia.');
}

// La conexión a la base de datos ($con) ya está disponible desde config.php, no es necesario crearla de nuevo.

// --- BLOQUE 3: CONSULTA A LA BASE DE DATOS ---
// Consulta SQL para obtener los turnos que están "Llamando" (id_estado = 1) o "En Atención" (id_estado = 11).
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

// --- BLOQUE 4: PROCESAMIENTO DE LOS DATOS ---
// Se inicializan los arrays para clasificar los turnos.
$turnos_notificacion = [];
$turnos_llamando = [];
$turnos_atencion = [];
$ahora = new DateTime();

// Se itera sobre los resultados de la consulta para clasificarlos.
foreach ($todos_los_turnos as $turno) {
    if ($turno['id_estado'] == 1) { // Estado 'Llamando'
        $turnos_llamando[] = $turno;
        $hora_llamado = new DateTime($turno['hora_llamado']);
        $diferencia = $ahora->getTimestamp() - $hora_llamado->getTimestamp();
        
        // Si el llamado se hizo hace 5 segundos o menos, se añade a la lista de notificaciones.
        if ($diferencia <= 5) {
            $turnos_notificacion[] = $turno;
        }
    }
    if ($turno['id_estado'] == 11) { // Estado 'En Atención'
        $turnos_atencion[] = $turno; 
    }
}

// --- BLOQUE 5: ENVÍO DE LA RESPUESTA JSON ---
// Se limpia cualquier posible salida del buffer.
ob_end_clean();
// Se establece la cabecera para la respuesta JSON.
header('Content-Type: application/json; charset=utf-8');
// Se envía la respuesta final con los turnos clasificados y limitados en cantidad.
echo json_encode([
    'notificacion' => array_slice($turnos_notificacion, 0, 1), // Solo la notificación más reciente
    'llamando' => array_slice($turnos_llamando, 0, 5),      // Máximo 5 turnos en la lista de 'Llamando'
    'en_atencion' => array_slice($turnos_atencion, 0, 10)   // Máximo 10 turnos en la lista de 'En Atención'
]);
?>