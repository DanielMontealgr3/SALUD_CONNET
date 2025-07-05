<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../include/PHPMAILER/PHPMailer.php';
require_once '../include/PHPMAILER/SMTP.php';
require_once '../include/PHPMAILER/Exception.php';

function enviarCorreoNoAsistio($destinatario_correo, $destinatario_nombre, $id_turno, $hora_programada_str, $hora_llamado_str) {
    $base_url_sitio = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/SALUDCONNECT";
    $nombre_empresa_sitio = "Salud Connect";
    $email_remitente_soporte = 'saludconneted@gmail.com';
    $password_remitente_soporte = 'czlr pxjh jxeu vzsz';

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $email_remitente_soporte;
        $mail->Password = $password_remitente_soporte;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($email_remitente_soporte, 'Notificaciones ' . $nombre_empresa_sitio);
        $mail->addAddress($destinatario_correo, $destinatario_nombre);

        $mail->isHTML(true);
        $mail->Subject = 'Información sobre su turno para entrega de medicamentos';
        $mail->Body    = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Turno No Atendido</title></head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#f4f4f4">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border: 1px solid #dddddd; border-radius: 8px;">
                    <tr>
                        <td align="center" style="padding: 30px 20px;">
                            <h1 style="color: #c0392b; margin-top: 0;">Turno No Atendido</h1>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Estimado/a $destinatario_nombre,</p>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Le informamos sobre su turno número <strong>$id_turno</strong>:</p>
                            <ul style="font-size: 16px; color: #333333; line-height: 1.6; text-align: left; display: inline-block; padding-left: 20px;">
                                <li style="margin-bottom: 5px;"><strong>Hora Programada:</strong> $hora_programada_str</li>
                                <li><strong>Hora de Llamado:</strong> $hora_llamado_str</li>
                            </ul>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">A pesar de haber sido llamado, no se presentó en la farmacia dentro del tiempo de espera. Para recibir sus medicamentos, deberá generar un nuevo turno para el próximo día disponible.</p>
                        </td>
                    </tr>
                    <tr><td align="center" style="padding: 20px; background-color: #f0f0f0; border-top: 1px solid #dddddd; font-size: 12px; color: #777777;">© 2024 $nombre_empresa_sitio. Todos los derechos reservados.</td></tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Correo No Asistido - Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>