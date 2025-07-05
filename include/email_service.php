<?php
// Usamos el config.php central para acceder a las constantes y la conexión.
require_once __DIR__ . '/config.php';

// Incluimos la librería PHPMailer usando la ruta absoluta definida en config.php
require_once ROOT_PATH . '/include/PHPMailer/Exception.php';
require_once ROOT_PATH . '/include/PHPMailer/PHPMailer.php';
require_once ROOT_PATH . '/include/PHPMailer/SMTP.php';

// Importamos las clases de PHPMailer al espacio de nombres global para usarlas sin prefijo.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Función centralizada para enviar correos electrónicos.
 * Detecta el entorno (local o producción) y utiliza la configuración SMTP adecuada.
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
        // =================================================================
        // ===        BLOQUE DE CONFIGURACIÓN SMTP INTELIGENTE         ===
        // =================================================================
        
        // El script detecta si está en el servidor de producción o en localhost
        if (BASE_URL === '') { 
            // ----- CONFIGURACIÓN PARA HOSTINGER (PRODUCCIÓN) -----
            $mail->isSMTP();
            $mail->Host       = 'smtp.hostinger.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'soporte@saludconnected.com'; // Tu correo real en Hostinger
            $mail->Password   = 'Saludconnected2025*';       // Tu contraseña real
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->setFrom('soporte@saludconnected.com', 'Soporte Salud Connected');

        } else {
            // ----- CONFIGURACIÓN PARA GMAIL (LOCALHOST) -----
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'saludconneted@gmail.com';     // Tu correo de Gmail
            $mail->Password   = 'czlr pxjh jxeu vzsz';         // Tu contraseña de aplicación de Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->setFrom('saludconneted@gmail.com', 'Soporte Salud Connected');
        }

        // =================================================================
        // ===        CONFIGURACIÓN GENERAL Y ENVÍO DEL CORREO         ===
        // =================================================================
        
        $mail->CharSet = 'UTF-8';
        $mail->addAddress($destinatario_email, $destinatario_nombre);
        $mail->isHTML(true);

        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo_html;
        $mail->AltBody = $cuerpo_texto;
        
        $mail->send();
        return true;

    } catch (Exception $e) {
        // En caso de error, lo registramos en el log del servidor para depuración.
        error_log("Error de PHPMailer al enviar a {$destinatario_email}: " . $mail->ErrorInfo);
        return false;
    }
}


/**
 * Prepara y envía el correo de confirmación de turno de farmacia.
 * Utiliza la función genérica enviarEmail().
 *
 * @param array $datos - Array con ['email_paciente', 'nombre_paciente', 'fecha_turno', 'hora_turno', 'medicamentos_str']
 * @return bool - True si se envió, false si hubo un error.
 */
function enviarCorreoConfirmacionTurno(array $datos): bool {
    $asunto = '✅ Confirmación de tu turno para entrega de medicamentos';

    // Obtenemos la plantilla HTML y reemplazamos los placeholders
    $plantilla_html = file_get_contents(ROOT_PATH . '/include/templates/turno_template.html');
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

    // Creamos la versión en texto plano
    $cuerpo_texto = "Hola, " . $datos['nombre_paciente'] . ".\n\n"
                  . "Tu turno para la entrega de medicamentos ha sido agendado con éxito. Aquí están los detalles:\n"
                  . "Fecha: " . $datos['fecha_turno'] . "\n"
                  . "Hora: " . $datos['hora_turno'] . "\n\n"
                  . "Medicamentos a reclamar:\n" . $datos['medicamentos_str'] . "\n\n"
                  . "Por favor, preséntate puntualmente con tu documento de identidad.\n\n"
                  . "Atentamente,\nEl equipo de Salud Connected";

    // Llamamos a la función principal de envío
    return enviarEmail(
        $datos['email_paciente'],
        $datos['nombre_paciente'],
        $asunto,
        $cuerpo_html,
        $cuerpo_texto
    );
}


/**
 * Prepara y envía el correo de recuperación de contraseña.
 * Utiliza la función genérica enviarEmail().
 *
 * @param string $email El correo del usuario.
 * @param string $token El token de recuperación.
 * @return bool - True si se envió, false si hubo un error.
 */
function enviarCorreoRecuperacion(string $email, string $token): bool {
    $asunto = 'Recuperación de contraseña - Salud Connected';
    $reset_link = (BASE_URL === '' ? 'https://saludconnected.com' : 'http://localhost/SALUDCONNECT') . "/include/change.php?token=" . urlencode($token);

    // Obtenemos la plantilla HTML y reemplazamos los placeholders
    $plantilla_html = file_get_contents(ROOT_PATH . '/include/templates/recuperacion_template.html');
    $cuerpo_html = str_replace(
        ['{{reset_link}}', '{{year}}'],
        [$reset_link, date('Y')],
        $plantilla_html
    );

    // Creamos la versión en texto plano
    $cuerpo_texto = "Hola,\n\nPara restablecer tu contraseña, copia y pega el siguiente enlace en tu navegador:\n" . $reset_link . "\n\nEste enlace es válido por 3 horas.";

    // Llamamos a la función principal de envío. El nombre del destinatario puede ser el propio email si no lo tenemos a mano.
    return enviarEmail($email, $email, $asunto, $cuerpo_html, $cuerpo_texto);
}

?>