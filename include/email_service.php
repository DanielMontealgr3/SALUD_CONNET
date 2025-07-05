<?php
// Usamos el config.php central para acceder a las constantes y la conexión.
require_once __DIR__ . '/config.php';

// Incluimos la librería PHPMailer usando la ruta absoluta definida en config.php
require_once ROOT_PATH . '/include/PHPMailer/Exception.php';
require_once ROOT_PATH . '/include/PHPMailer/PHPMailer.php';
require_once ROOT_PATH . '/include/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Función centralizada para enviar correos electrónicos.
 * Utiliza la configuración SMTP definida en config.php.
 *
 * @param string $destinatario_email El correo del destinatario.
 * @param string $destinatario_nombre El nombre del destinatario.
 * @param string $asunto El asunto del correo.
 * @param string $cuerpo_html El contenido HTML del correo.
 * @param string $cuerpo_texto El contenido en texto plano como alternativa.
 * @return bool Devuelve true si el correo se envió con éxito, false en caso de error.
 */
function enviarEmail(string $destinatario_email, string $destinatario_nombre, string $asunto, string $cuerpo_html, string $cuerpo_texto): bool {
    $mail = new PHPMailer(true);

    try {
        // --- CONFIGURACIÓN SMTP DESDE config.php ---
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE; // 'ssl' o 'tls'
        $mail->Port       = SMTP_PORT;

        // --- REMITENTE Y DESTINATARIO ---
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($destinatario_email, $destinatario_nombre);

        // --- CONTENIDO DEL CORREO ---
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo_html;
        $mail->AltBody = $cuerpo_texto;
        
        $mail->send();
        return true;

    } catch (Exception $e) {
        // Registra el error detallado para depuración en el servidor
        error_log("Error de PHPMailer al enviar a {$destinatario_email}: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Prepara y envía el correo de confirmación de turno de farmacia.
 *
 * @param array $datos - Array con ['email_paciente', 'nombre_paciente', 'fecha_turno', 'hora_turno', 'medicamentos_str']
 * @return bool
 */
function enviarCorreoConfirmacionTurno(array $datos): bool {
    $asunto = '✅ Confirmación de tu turno para entrega de medicamentos';
    $plantilla_path = ROOT_PATH . '/include/templates/turno_template.html';

    if (!file_exists($plantilla_path)) {
        error_log("No se encontró la plantilla de correo: " . $plantilla_path);
        return false;
    }

    $plantilla_html = file_get_contents($plantilla_path);
    $cuerpo_html = str_replace(
        ['{{nombre_paciente}}', '{{fecha_turno}}', '{{hora_turno}}', '{{medicamentos}}', '{{year}}'],
        [
            htmlspecialchars($datos['nombre_paciente']),
            htmlspecialchars($datos['fecha_turno']),
            htmlspecialchars($datos['hora_turno']),
            htmlspecialchars($datos['medicamentos_str']),
            date('Y')
        ],
        $plantilla_html
    );

    $cuerpo_texto = "Hola, " . $datos['nombre_paciente'] . ".\n\n"
                  . "Tu turno para la entrega de medicamentos ha sido agendado:\n"
                  . "Fecha: " . $datos['fecha_turno'] . "\n"
                  . "Hora: " . $datos['hora_turno'] . "\n\n"
                  . "Medicamentos: " . $datos['medicamentos_str'] . "\n\n"
                  . "Atentamente,\nEl equipo de Salud Connected";

    return enviarEmail($datos['email_paciente'], $datos['nombre_paciente'], $asunto, $cuerpo_html, $cuerpo_texto);
}

/**
 * Prepara y envía el correo de recuperación de contraseña.
 *
 * @param string $email El correo del usuario.
 * @param string $token El token de recuperación.
 * @return bool
 */
function enviarCorreoRecuperacion(string $email, string $token): bool {
    $asunto = 'Recuperación de contraseña - Salud Connected';
    $reset_link = rtrim(BASE_URL, '/') . "/include/change.php?token=" . urlencode($token);
    $plantilla_path = ROOT_PATH . '/include/templates/recuperacion_template.html';

    if (!file_exists($plantilla_path)) {
        error_log("No se encontró la plantilla de recuperación: " . $plantilla_path);
        return false;
    }
    
    $plantilla_html = file_get_contents($plantilla_path);
    $cuerpo_html = str_replace(
        ['{{reset_link}}', '{{year}}'],
        [$reset_link, date('Y')],
        $plantilla_html
    );

    $cuerpo_texto = "Hola,\n\nPara restablecer tu contraseña, usa el siguiente enlace:\n" . $reset_link . "\n\nEste enlace es válido por 3 horas.";

    return enviarEmail($email, $email, $asunto, $cuerpo_html, $cuerpo_texto);
}
?>