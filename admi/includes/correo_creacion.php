<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../include/PHPMAILER/PHPMailer.php';
require_once '../include/PHPMAILER/SMTP.php';
require_once '../include/PHPMAILER/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarCorreoBienvenida($destinatario_correo, $destinatario_nombre, $destinatario_documento, $contrasena_temporal, $estado_inicial_usuario, $nombre_rol_creado) {
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
        $mail->Subject = '¡Bienvenido/a a ' . $nombre_empresa_sitio . '! - Cuenta Creada';
        
        $mensaje_activacion = "";
        $boton_iniciar_sesion = "";

        if ($estado_inicial_usuario == 1) { 
            $mensaje_activacion = "<p style='font-size: 16px; color: #333333; line-height: 1.6;'>Su cuenta con el rol de <strong>".htmlspecialchars($nombre_rol_creado)."</strong> ha sido creada y activada. Ya puede ingresar al sistema.</p>";
            $boton_iniciar_sesion = "<p style='margin-top: 25px; text-align: center;'>
                                <a href=\"$base_url_sitio/inicio_sesion.php\" style=\"background-color: #007bff; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;\">Iniciar Sesión</a>
                            </p>";
        } else { 
            $mensaje_activacion = "<p style='font-size: 16px; color: #333333; line-height: 1.6;'>Su cuenta con el rol de <strong>".htmlspecialchars($nombre_rol_creado)."</strong> ha sido creada como inactiva. Por favor, espere un correo de activación por parte de un administrador para poder acceder al sistema.</p>";
        }

        $mail->Body    = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Bienvenido/a a $nombre_empresa_sitio</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#f4f4f4">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border: 1px solid #dddddd; border-radius: 8px;">
                    <tr>
                        <td align="center" style="padding: 30px 20px;">
                            <h1 style="color: #0056b3; margin-top: 0;">¡Bienvenido/a a $nombre_empresa_sitio!</h1>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Estimado/a $destinatario_nombre,</p>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Su cuenta en $nombre_empresa_sitio ha sido creada exitosamente.</p>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Sus datos de acceso iniciales son:</p>
                            <ul style="font-size: 16px; color: #333333; line-height: 1.6; list-style-type: none; padding-left: 0;">
                                <li><strong>Documento:</strong> $destinatario_documento</li>
                                <li><strong>Contraseña Temporal:</strong> <strong style="color: #d9534f;">$contrasena_temporal</strong></li>
                            </ul>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;"><strong>Importante:</strong> Por su seguridad, le recomendamos cambiar esta contraseña temporal después de su primer inicio de sesión.</p>
                            $mensaje_activacion
                            $boton_iniciar_sesion
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
        $mail->AltBody = "Estimado/a $destinatario_nombre,\n\nSu cuenta en $nombre_empresa_sitio ha sido creada.\n\nRol: ".htmlspecialchars($nombre_rol_creado)."\nDocumento: $destinatario_documento\nContraseña Temporal: $contrasena_temporal\n\nPor favor, cambie su contraseña después de su primer inicio de sesión.\nPuede ingresar al sistema en: $base_url_sitio/inicio_sesion.php\n\nAtentamente,\nEl equipo de $nombre_empresa_sitio";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo de bienvenida a $destinatario_correo: " . $mail->ErrorInfo);
        return false;
    }
}

$redirect_url = 'crear_usu.php'; 

if (isset($_SESSION['correo_nuevo_usuario_info'])) {
    $datos_correo = $_SESSION['correo_nuevo_usuario_info'];
    unset($_SESSION['correo_nuevo_usuario_info']); 

    $correo_enviado_bienvenida = enviarCorreoBienvenida(
        $datos_correo['correo'],
        $datos_correo['nombre'],
        $datos_correo['documento'],
        $datos_correo['contrasena_plain'],
        $datos_correo['estado_inicial'],
        $datos_correo['nombre_rol'] ?? 'Usuario'
    );
    
    $param_status = $datos_correo['estado_inicial'] == 1 ? 'enviado_activo' : 'enviado_inactivo';
    $doc_creado_param = urlencode($datos_correo['documento']);
    $nom_creado_param = urlencode($datos_correo['nombre']);
    $tipo_doc_param = urlencode($_SESSION['ultimo_tipo_doc_creado'] ?? '');
    unset($_SESSION['ultimo_tipo_doc_creado']);


    $query_params = "correo_status=".$param_status."&email=" . urlencode($datos_correo['correo']) . "&nombre=" . $nom_creado_param . "&doc_creado=" . $doc_creado_param . "&tipo_doc_creado=" . $tipo_doc_param;

    if ($correo_enviado_bienvenida) {
        header("Location: $redirect_url?$query_params");
    } else {
        header("Location: $redirect_url?correo_status=error&doc_creado=" . $doc_creado_param . "&nom_creado=" . $nom_creado_param . "&tipo_doc_creado=" . $tipo_doc_param);
    }
    exit;
}

header("Location: $redirect_url");
exit;
?>