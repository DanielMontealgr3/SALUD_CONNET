<?php
// --- BLOQUE 1: INCLUSIÓN DE ARCHIVOS Y LIBRERÍAS ---
// Incluimos el archivo de configuración central. Este nos da acceso a:
// 1. Las constantes de ruta como ROOT_PATH y BASE_URL.
// 2. Las constantes de configuración de SMTP (SMTP_HOST, SMTP_PORT, etc.).
// NOTA: La ruta de inclusión puede variar. Si este archivo está en 'farma/',
// la ruta correcta sería __DIR__ . '/../include/config.php'.
// Asumiré que este archivo estará en 'include' para la siguiente línea:
require_once __DIR__ . '/config.php'; 

// Incluimos los archivos de la librería PHPMailer usando la ruta absoluta ROOT_PATH.
require_once ROOT_PATH . '/include/PHPMailer/Exception.php';
require_once ROOT_PATH . '/include/PHPMailer/PHPMailer.php';
require_once ROOT_PATH . '/include/PHPMailer/SMTP.php';

// Importamos las clases de PHPMailer para usarlas de forma más sencilla.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- BLOQUE 2: FUNCIÓN CENTRALIZADA PARA ENVIAR EL CORREO ---
/**
 * Envía un correo de notificación cuando un paciente no asiste a su turno.
 *
 * @param string $destinatario_correo El email del paciente.
 * @param string $destinatario_nombre El nombre del paciente.
 * @param int $id_turno El ID del turno no atendido.
 * @param string $hora_programada_str La hora en que el turno estaba agendado.
 * @param string $hora_llamado_str La hora en que el paciente fue llamado.
 * @return bool Devuelve true si el correo se envió con éxito, false en caso contrario.
 */
function enviarCorreoNoAsistio($destinatario_correo, $destinatario_nombre, $id_turno, $hora_programada_str, $hora_llamado_str) {
    
    // Creamos una nueva instancia de PHPMailer. El `true` activa las excepciones.
    $mail = new PHPMailer(true);

    try {
        // --- BLOQUE 3: CONFIGURACIÓN DINÁMICA DE SMTP ---
        // Se utilizan las constantes definidas en 'config.php' para configurar el servidor de correo.
        // Esto permite que la configuración cambie automáticamente entre localhost y producción.
        
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Descomentar para depuración detallada.
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;       // Ej: 'smtp.hostinger.com' o 'smtp.gmail.com'
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;   // Tu correo de envío
        $mail->Password   = SMTP_PASSWORD;   // Tu contraseña de correo o de aplicación
        $mail->SMTPSecure = SMTP_SECURE;     // Ej: 'ssl' (para Hostinger) o 'tls' (para Gmail)
        $mail->Port       = SMTP_PORT;       // Ej: 465 (para Hostinger) o 587 (para Gmail)
        $mail->CharSet    = 'UTF-8';

        // --- BLOQUE 4: CONFIGURACIÓN DE REMITENTE Y DESTINATARIO ---
        // Se usan las constantes de 'config.php' para el remitente.
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($destinatario_correo, $destinatario_nombre); // Se añade el destinatario del correo.

        // --- BLOQUE 5: CONTENIDO DEL CORREO ---
        // Se define el asunto y el cuerpo del correo en formato HTML.
        $mail->isHTML(true);
        $mail->Subject = 'Información sobre su turno para entrega de medicamentos';
        
        // Se utiliza la constante BASE_URL para construir la URL del logo de forma dinámica.
        $logo_url = (BASE_URL === '' ? 'https://saludconnected.com' : 'http://localhost/SALUDCONNECT') . '/img/Logo.png';

        // Se usa la sintaxis HEREDOC para un HTML más limpio.
        $mail->Body    = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Turno No Atendido - Salud Connected</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border: 1px solid #dddddd; border-radius: 8px; overflow: hidden;">
                    <!-- Cabecera con logo -->
                    <tr>
                        <td align="center" style="background-color: #0056b3; padding: 20px;">
                            <img src="$logo_url" alt="Logo Salud Connected" style="max-width: 200px;">
                        </td>
                    </tr>
                    <!-- Contenido del mensaje -->
                    <tr>
                        <td style="padding: 30px 20px;">
                            <h1 style="color: #c0392b; margin-top: 0; text-align: center;">Turno No Atendido</h1>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Estimado/a <strong>$destinatario_nombre</strong>,</p>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Le informamos que su turno para la entrega de medicamentos ha sido marcado como "No Asistido".</p>
                            <div style="background-color: #f9f9f9; border-left: 4px solid #c0392b; padding: 15px; margin: 20px 0;">
                                <h3 style="margin-top: 0; color: #333;">Detalles del Turno</h3>
                                <ul style="list-style: none; padding: 0;">
                                    <li style="margin-bottom: 10px;"><strong>Número de Turno:</strong> $id_turno</li>
                                    <li style="margin-bottom: 10px;"><strong>Hora Programada:</strong> $hora_programada_str</li>
                                    <li><strong>Hora de Llamado:</strong> $hora_llamado_str</li>
                                </ul>
                            </div>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">A pesar de haber sido llamado, no se presentó en la farmacia dentro del tiempo de espera. Para recibir sus medicamentos, deberá <strong>generar un nuevo turno</strong> para el próximo día disponible a través de nuestra plataforma.</p>
                        </td>
                    </tr>
                    <!-- Pie de página -->
                    <tr>
                        <td align="center" style="padding: 20px; background-color: #f0f0f0; border-top: 1px solid #dddddd; font-size: 12px; color: #777777;">
                            © 2024 Salud Connected. Todos los derechos reservados.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
        
        // Se envía el correo.
        $mail->send();
        // Si el envío es exitoso, la función devuelve true.
        return true;

    } catch (Exception $e) {
        // Si ocurre un error, se registra en el log del servidor y la función devuelve false.
        error_log("Correo No Asistido - Error al enviar: " . $mail->ErrorInfo);
        return false;
    }
}
?>