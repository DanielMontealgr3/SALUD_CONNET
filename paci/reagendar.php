<?php
// =================================================================
// === INICIO DEL BLOQUE CORREGIDO (PORTABILIDAD) ===
// =================================================================

// 1. Inclusión de la configuración centralizada.
// Esto establece ROOT_PATH, BASE_URL, inicia sesión y conecta a la BD.
require_once __DIR__ . '/../include/config.php';

// 2. Inclusión de los scripts de seguridad y PHPMailer usando ROOT_PATH.
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php'; // Se añade por consistencia
require_once ROOT_PATH . '/include/PHPMailer/PHPMailer.php';
require_once ROOT_PATH . '/include/PHPMailer/Exception.php';
require_once ROOT_PATH . '/include/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// La variable de conexión `$con` ya está disponible desde config.php.
// No es necesario crear una nueva instancia de Database.

// =================================================================
// === FIN DEL BLOQUE CORREGIDO ===
// El resto del código permanece exactamente igual.
// =================================================================

$id_registro = $_POST['id_registro'] ?? 0;
$tipo = $_POST['tipo'] ?? '';
$nueva_fecha = $_POST['fecha'] ?? '';
$nueva_hora_24h = $_POST['hora'] ?? '';

// 3. Redirección portable usando BASE_URL.
$redirect_url_base = BASE_URL . '/paci/citas_actuales.php';

if (empty($id_registro) || empty($tipo) || empty($nueva_fecha) || empty($nueva_hora_24h)) {
    header('Location: ' . $redirect_url_base . '?reagenda_status=error_datos');
    exit;
}

$tabla_evento = '';
$col_id_registro = '';
$col_fecha = '';
$col_hora = '';
$nuevo_id_estado = 3; // Asignado
$estado_anterior = 16; // Re-Agendado

$con->beginTransaction();

try {
    switch ($tipo) {
        case 'medica':
            $tabla_evento = 'citas';
            $col_id_registro = 'id_cita';
            // Para citas médicas, el horario es más complejo (se actualiza la referencia a horario_medico)
            $stmt_medico = $con->prepare("SELECT doc_med FROM citas WHERE id_cita = ?");
            $stmt_medico->execute([$id_registro]);
            $doc_medico = $stmt_medico->fetchColumn();
            
            $stmt_horario = $con->prepare("SELECT id_horario_med FROM horario_medico WHERE doc_medico = ? AND fecha_horario = ? AND horario = ? AND id_estado = 4");
            $stmt_horario->execute([$doc_medico, $nueva_fecha, $nueva_hora_24h.':00']);
            $id_horario_med = $stmt_horario->fetchColumn();

            if(!$id_horario_med) throw new Exception("El nuevo horario no está disponible.");

            $update = $con->prepare("UPDATE citas SET id_horario_med = ?, id_est = ? WHERE id_cita = ?");
            $update->execute([$id_horario_med, $nuevo_id_estado, $id_registro]);
            
            $update_horario_viejo = $con->prepare("UPDATE horario_medico SET id_estado=4 WHERE id_horario_med = (SELECT id_horario_med FROM citas WHERE id_cita = ?)");
            // Esta parte es compleja, necesitaríamos guardar el id_horario_med viejo antes de actualizar. Simplificamos por ahora.
            
            $update_horario_nuevo = $con->prepare("UPDATE horario_medico SET id_estado=3 WHERE id_horario_med = ?");
            $update_horario_nuevo->execute([$id_horario_med]);
            
            break;
        
        case 'medicamento':
            // Lógica similar para medicamentos...
            break;
        case 'examen':
            // Lógica similar para exámenes...
            break;
    }

    $con->commit();

    // Lógica para enviar correos de notificación de reagendamiento...

    header('Location: ' . $redirect_url_base . '?reagenda_status=exito');
    exit;

} catch (Exception $e) {
    $con->rollBack();
    error_log("Error al reagendar: " . $e->getMessage());
    header('Location: ' . $redirect_url_base . '?reagenda_status=error_db');
    exit;
}
?>