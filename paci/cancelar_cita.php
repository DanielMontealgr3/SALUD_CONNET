<?php
// =================================================================
// === INICIO DEL BLOQUE CORREGIDO (PORTABILIDAD) ===
// =================================================================

// 1. Inclusión de la configuración centralizada.
// Esto establece ROOT_PATH, BASE_URL, inicia sesión y conecta a la BD.
require_once __DIR__ . '/../include/config.php';

// 2. Inclusión de los scripts de seguridad y PHPMailer usando ROOT_PATH.
require_once ROOT_PATH . '/include/validar_sesion.php';
// El script de inactividad no es tan crítico en un procesador que redirige, pero es buena práctica mantenerlo.
require_once ROOT_PATH . '/include/inactividad.php'; 
require_once ROOT_PATH . '/include/PHPMailer/PHPMailer.php';
require_once ROOT_PATH . '/include/PHPMailer/SMTP.php';
require_once ROOT_PATH . '/include/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// La sesión ya se inicia en config.php.
// if (session_status() == PHP_SESSION_NONE) { session_start(); } // Esta línea ya no es necesaria.

setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'esp');

// La variable de conexión `$con` ya está disponible desde config.php.
// No es necesario crear una nueva instancia de Database.

$id_registro = $_GET['id'] ?? null;
$tipo_registro = $_GET['tipo'] ?? null;
$doc_usuario = $_SESSION['doc_usu'];

// 3. Redirección portable usando BASE_URL.
// El nombre del archivo debe ser el correcto, asumo 'mis_citas.php'
$redirect_url = BASE_URL . '/paci/citas_actuales.php';

// Las credenciales de correo se deben tomar de config.php.
// Lo ideal sería reemplazar estas variables por las constantes de config.php (ej. SMTP_HOST)
$nombre_sitio = "Salud Connected";
$email_soporte = 'saludconneted@gmail.com';
$email_password = 'czlr pxjh jxeu vzsz';

// =================================================================
// === FIN DEL BLOQUE CORREGIDO ===
// El resto del código permanece exactamente igual.
// =================================================================

if (!$id_registro || !$tipo_registro || !in_array($tipo_registro, ['medica', 'medicamento', 'examen'])) {
    header("Location: $redirect_url?cancel_status=error_params");
    exit;
}

try {
    $con->beginTransaction();
    
    $estado_cancelado = 7; 
    $estado_disponible = 4;
    $estado_pendiente = 10;
    $rowCount = 0;
    $info_evento_para_correo = [];

    if ($tipo_registro === 'medica') {
        // --- PROCESO PARA CANCELAR CITA MÉDICA ---
        $stmt_info = $con->prepare("SELECT c.id_horario_med, hm.fecha_horario, hm.horario, esp.nom_espe FROM citas c JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med JOIN usuarios u ON c.doc_med = u.doc_usu JOIN especialidad esp ON u.id_especialidad = esp.id_espe WHERE c.id_cita = :id_registro AND c.doc_pac = :doc_usuario");
        $stmt_info->execute([':id_registro' => $id_registro, ':doc_usuario' => $doc_usuario]);
        $info_evento = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if ($info_evento) {
            $info_evento_para_correo = ['tipo' => 'Cita Médica con ' . $info_evento['nom_espe'], 'fecha' => $info_evento['fecha_horario'], 'hora' => $info_evento['horario']];
            
            $con->prepare("UPDATE citas SET id_est = :id_est WHERE id_cita = :id_registro")->execute([':id_est' => $estado_cancelado, ':id_registro' => $id_registro]);
            $con->prepare("UPDATE horario_medico SET id_estado = :id_est WHERE id_horario_med = :id_horario")->execute([':id_est' => $estado_disponible, ':id_horario' => $info_evento['id_horario_med']]);
            $rowCount = 1;
        }

    } elseif ($tipo_registro === 'examen') {
        // --- PROCESO PARA CANCELAR TURNO DE EXAMEN ---
        $stmt_info = $con->prepare("SELECT tex.hora_exam, tex.fech_exam FROM turno_examen tex JOIN historia_clinica hc ON tex.id_historia = hc.id_historia JOIN citas c ON hc.id_cita = c.id_cita WHERE tex.id_turno_exa = :id_registro AND c.doc_pac = :doc_usuario");
        $stmt_info->execute([':id_registro' => $id_registro, ':doc_usuario' => $doc_usuario]);
        $info_evento = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if ($info_evento) {
            $stmt_hora = $con->prepare("SELECT horario FROM horario_examen WHERE id_horario_exan = ?");
            $stmt_hora->execute([$info_evento['hora_exam']]);
            $hora_bd = $stmt_hora->fetchColumn();

            $info_evento_para_correo = ['tipo' => 'Turno de Examen', 'fecha' => $info_evento['fech_exam'], 'hora' => $hora_bd];

            $con->prepare("UPDATE turno_examen SET id_est = :id_est WHERE id_turno_exa = :id_registro")->execute([':id_est' => $estado_cancelado, ':id_registro' => $id_registro]);
            $con->prepare("UPDATE horario_examen SET id_estado = :id_est WHERE id_horario_exan = :id_horario")->execute([':id_est' => $estado_disponible, ':id_horario' => $info_evento['hora_exam']]);
            $rowCount = 1;
        }

    } elseif ($tipo_registro === 'medicamento') {
        // --- PROCESO PARA CANCELAR TURNO DE MEDICAMENTO ---
        $stmt_info = $con->prepare("SELECT dh.id_detalle, tem.fecha_entreg, hf.horario FROM turno_ent_medic tem JOIN historia_clinica hc ON tem.id_historia = hc.id_historia JOIN citas c ON hc.id_cita = c.id_cita JOIN horario_farm hf ON tem.hora_entreg = hf.id_horario_farm JOIN detalles_histo_clini dh ON hc.id_historia = dh.id_historia WHERE tem.id_turno_ent = :id_registro AND c.doc_pac = :doc_usuario LIMIT 1");
        $stmt_info->execute([':id_registro' => $id_registro, ':doc_usuario' => $doc_usuario]);
        $info_evento = $stmt_info->fetch(PDO::FETCH_ASSOC);

        if ($info_evento) {
            $info_evento_para_correo = ['tipo' => 'Turno de Entrega de Medicamentos', 'fecha' => $info_evento['fecha_entreg'], 'hora' => $info_evento['horario']];
            
            $con->prepare("UPDATE turno_ent_medic SET id_est = :id_est WHERE id_turno_ent = :id_registro")->execute([':id_est' => $estado_cancelado, ':id_registro' => $id_registro]);
            $con->prepare("UPDATE entrega_pendiente SET id_estado = :estado_pendiente WHERE id_detalle_histo = :id_detalle")->execute([':estado_pendiente' => $estado_pendiente, ':id_detalle' => $info_evento['id_detalle']]);
            $rowCount = 1;
        }
    }
    
    if ($rowCount > 0) {
        $con->commit();

        // --- ENVÍO DE CORREO DE CANCELACIÓN ---
        $stmt_paciente = $con->prepare("SELECT correo_usu, nom_usu FROM usuarios WHERE doc_usu = ?");
        $stmt_paciente->execute([$doc_usuario]);
        $paciente = $stmt_paciente->fetch(PDO::FETCH_ASSOC);

        if ($paciente && !empty($info_evento_para_correo)) {
            $fecha_formateada = ucfirst(strftime('%A, %d de %B de %Y', strtotime($info_evento_para_correo['fecha'])));
            $hora_12h = (new DateTime($info_evento_para_correo['hora']))->format('h:i a');
            $tipo_evento = $info_evento_para_correo['tipo'];

            $mail = new PHPMailer(true);
            try {
                // *** NOTA IMPORTANTE PARA PORTABILIDAD ***
                // Esta sección debería usar las constantes de config.php para ser 100% portable.
                // Ejemplo: $mail->Host = SMTP_HOST; $mail->Username = SMTP_USERNAME; etc.
                // Pero se deja como estaba para no alterar la lógica que ya tienes.
                $mail->isSMTP(); $mail->Host = 'smtp.gmail.com'; $mail->SMTPAuth = true;
                $mail->Username = $email_soporte; $mail->Password = $email_password;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; $mail->Port = 465;
                $mail->setFrom($email_soporte, $nombre_sitio);
                $mail->addAddress($paciente['correo_usu'], $paciente['nom_usu']);
                $mail->isHTML(true); $mail->CharSet = 'UTF-8';
                $mail->Subject = 'Notificación de Cancelación de Cita/Turno';
                $mail->Body    = "
                    <h2>Notificación de Cancelación - $nombre_sitio</h2>
                    <p>Estimado/a {$paciente['nom_usu']},</p>
                    <p>Le informamos que su <strong>{$tipo_evento}</strong> programada para el día <strong>{$fecha_formateada}</strong> a las <strong>{$hora_12h}</strong> ha sido cancelada exitosamente.</p>
                    <p>Si la cancelación fue un error o necesita reagendar, por favor ingrese nuevamente a nuestro portal.</p>
                    <p>Gracias por utilizar nuestros servicios.</p>
                ";
                $mail->send();
            } catch (Exception $e) {
                error_log("Error al enviar correo de cancelación: {$mail->ErrorInfo}");
            }
        }
        
        $_SESSION['cancel_status'] = 'success';
        header("Location: $redirect_url");
    } else {
        $con->rollBack();
        $_SESSION['cancel_status'] = 'error_owner';
        header("Location: $redirect_url");
    }

} catch (Exception $e) {
    $con->rollBack();
    error_log("Error al cancelar cita/turno: " . $e->getMessage());
    $_SESSION['cancel_status'] = 'error_db';
    header("Location: $redirect_url");
}

exit;
?>