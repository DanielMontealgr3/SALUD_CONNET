<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../include/PHPMAILER/PHPMailer.php';
require_once '../include/PHPMAILER/SMTP.php';
require_once '../include/PHPMAILER/Exception.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarCorreoActivacion($destinatario_correo, $destinatario_nombre, $destinatario_documento) {
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

        $mail->setFrom($email_remitente_soporte, 'Soporte ' . $nombre_empresa_sitio);
        $mail->addAddress($destinatario_correo, $destinatario_nombre);

        $mail->isHTML(true);
        $mail->Subject = 'Activación de cuenta - ' . $nombre_empresa_sitio;
        $mail->Body    = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Activación de Cuenta</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#f4f4f4">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border: 1px solid #dddddd; border-radius: 8px;">
                    <tr>
                        <td align="center" style="padding: 30px 20px;">
                            <h1 style="color: #0056b3; margin-top: 0;">¡Cuenta Activada!</h1>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Estimado/a $destinatario_nombre,</p>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Le informamos que su cuenta en $nombre_empresa_sitio con el documento <strong>$destinatario_documento</strong> ha sido activada por un administrador.</p>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Ya puede ingresar al sistema utilizando sus credenciales habituales.</p>
                            <p style="margin-top: 25px; text-align: center;">
                                <a href="$base_url_sitio/inicio_sesion.php" style="background-color: #007bff; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Iniciar Sesión</a>
                            </p>
                            <p style="font-size: 14px; color: #555555; line-height: 1.6;">Si no esperaba esta activación o tiene alguna pregunta, por favor, póngase en contacto con nuestro equipo de soporte.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 20px; background-color: #f0f0f0; border-top: 1px solid #dddddd; font-size: 12px; color: #777777;">
                            © {date('Y')} $nombre_empresa_sitio. Todos los derechos reservados.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
        $mail->AltBody = "Estimado/a $destinatario_nombre,\n\nLe informamos que su cuenta en $nombre_empresa_sitio con el documento $destinatario_documento ha sido activada.\n\nPuede ingresar al sistema en: $base_url_sitio/inicio_sesion.php\n\nAtentamente,\nEl equipo de $nombre_empresa_sitio";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo de activación a $destinatario_correo: " . $mail->ErrorInfo);
        return false;
    }
}

$redirect_url = $_SESSION['pagina_anterior_estado_change'] ?? 'lista_farmaceutas.php'; 
unset($_SESSION['pagina_anterior_estado_change']);

if (isset($_SESSION['notificar_activacion'])) {
    $datos_notificacion = $_SESSION['notificar_activacion'];
    unset($_SESSION['notificar_activacion']); 

    $correo_enviado = enviarCorreoActivacion(
        $datos_notificacion['correo'],
        $datos_notificacion['nombre'],
        $datos_notificacion['documento']
    );

    $mensaje_get = $correo_enviado ? "Estado actualizado. Correo de activación enviado a " . htmlspecialchars($datos_notificacion['correo']) . "." : "Estado actualizado, pero hubo un error al enviar el correo de activación.";
    $tipo_mensaje_get = $correo_enviado ? "success" : "warning";
    
    header("Location: $redirect_url?msg_estado=" . urlencode($mensaje_get) . "&tipo_msg_estado=" . $tipo_mensaje_get);
    exit;
}

header("Location: $redirect_url");
exit;
?>