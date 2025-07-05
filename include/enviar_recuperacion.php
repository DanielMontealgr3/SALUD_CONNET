<?php
// INCLUYE EL ARCHIVO DE CONFIGURACIÓN GLOBAL. NOS DA ACCESO A LAS RUTAS, LA SESIÓN Y LA CONEXIÓN A LA BD.
require_once __DIR__ . '/config.php';

// INCLUYE LOS ARCHIVOS NECESARIOS DE LA LIBRERÍA PHPMailer USANDO LA RUTA ABSOLUTA Y CORRECTA.
require_once ROOT_PATH . '/include/PHPMailer/Exception.php';
require_once ROOT_PATH . '/include/PHPMailer/PHPMailer.php';
require_once ROOT_PATH . '/include/PHPMailer/SMTP.php';

// IMPORTA LAS CLASES DE PHPMailer AL ESPACIO DE NOMBRES GLOBAL.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// VERIFICA QUE LA SOLICITUD SEA MEDIANTE EL MÉTODO POST.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // RECOGE Y LIMPIA EL CORREO ELECTRÓNICO ENVIADO DESDE EL FORMULARIO.
    $email = trim($_POST["correo_usu"]);

    // VALIDACIÓN BÁSICA PARA ASEGURAR QUE EL CAMPO NO ESTÉ VACÍO.
    if (empty($email)) {
        $_SESSION['flash_message'] = 'El correo no puede estar vacío';
        $_SESSION['flash_type'] = 'error';
        header('Location: ' . BASE_URL . '/include/olvide_contra.php');
        exit;
    }

    // CONSULTA A LA BASE DE DATOS PARA VERIFICAR QUE EL USUARIO EXISTE.
    $stmt = $con->prepare("SELECT doc_usu FROM usuarios WHERE correo_usu = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // SI NO SE ENCUENTRA EL USUARIO, DEVUELVE UN MENSAJE DE ERROR.
    if (!$user) {
        $_SESSION['flash_message'] = 'Correo incorrecto';
        $_SESSION['flash_type'] = 'error';
        header('Location: ' . BASE_URL . '/include/olvide_contra.php');
        exit;
    }

    // GENERACIÓN DE UN TOKEN DE RECUPERACIÓN SEGURO Y ÚNICO, Y DEFINICIÓN DE SU TIEMPO DE VALIDEZ.
    $id_usuario = $user['doc_usu'];
    $token = bin2hex(random_bytes(50));
    $creacion_t = date("Y-m-d H:i:s");
    $expiracion_t = date("Y-m-d H:i:s", strtotime("+3 hour"));

    // INSERTA O ACTUALIZA EL TOKEN EN LA BASE DE DATOS PARA ESTE USUARIO.
    try {
        $stmt = $con->prepare("SELECT id_recu FROM recu_contra WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $stmt = $con->prepare("UPDATE recu_contra SET token = ?, creacion_t = ?, expiracion_t = ? WHERE id_usuario = ?");
            $stmt->execute([$token, $creacion_t, $expiracion_t, $id_usuario]);
        } else {
            $stmt = $con->prepare("INSERT INTO recu_contra (id_usuario, token, creacion_t, expiracion_t) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id_usuario, $token, $creacion_t, $expiracion_t]);
        }
    } catch (PDOException $e) {
        // MANEJO DE ERRORES SI FALLA LA OPERACIÓN EN LA BASE DE DATOS.
        error_log("Error BD al guardar token: " . $e->getMessage());
        $_SESSION['flash_message'] = 'Ocurrió un error interno. Por favor, inténtelo más tarde.';
        $_SESSION['flash_type'] = 'error';
        header('Location: ' . BASE_URL . '/include/olvide_contra.php');
        exit;
    }

    // CREA UNA NUEVA INSTANCIA DE PHPMailer.
    $mail = new PHPMailer(true);
    try {
        // ***** CAMBIO 1: SE ACTIVA EL MODO DEBUG PARA VER LOS ERRORES DETALLADOS *****
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;

        // ***** INICIO DEL BLOQUE DE CONFIGURACIÓN SMTP INTELIGENTE *****

        // DETECTA SI ESTAMOS EN EL SERVIDOR DE PRODUCCIÓN.
        if (BASE_URL === '') {
            // CONFIGURACIÓN PARA HOSTINGER (PRODUCCIÓN)
            $mail->isSMTP();
            $mail->Host       = 'smtp.hostinger.com';
            $mail->SMTPAuth   = true;
            // ¡¡IMPORTANTE!! REEMPLAZA ESTOS DATOS CON TU CUENTA DE CORREO CREADA EN HOSTINGER.
            $mail->Username   = 'no-responder@saludconnected.com'; // EJEMPLO: no-responder@saludconnected.com
            $mail->Password   = 'LA_CONTRASEÑA_DE_TU_EMAIL_DE_HOSTINGER'; // LA CONTRASEÑA DE ESE CORREO
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            // LA DIRECCIÓN DEL REMITENTE DEBE SER LA MISMA QUE EL USERNAME.
            $mail->setFrom($mail->Username, 'Soporte Salud Connected');
        } else {
            // CONFIGURACIÓN PARA GMAIL (LOCALHOST)
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'saludconneted@gmail.com'; // Tu email de Gmail
            $mail->Password = 'czlr pxjh jxeu vzsz'; // Tu contraseña de aplicación de Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom($mail->Username, 'Soporte Salud Connected');
        }

        // ***** FIN DEL BLOQUE DE CONFIGURACIÓN SMTP INTELIGENTE *****

        $mail->CharSet = 'UTF-8';

        // AÑADE LA DIRECCIÓN DEL DESTINATARIO.
        $mail->addAddress($email);

        // CONFIGURA EL ASUNTO Y EL CUERPO DEL MENSAJE.
        $mail->Subject = 'Recuperación de contraseña - Salud Connected';
        $reset_link = (BASE_URL === '' ? 'https://saludconnected.com' : 'http://localhost/SALUDCONNECT') . "/include/change.php?token=" . urlencode($token);

        $mail->isHTML(true);
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperación de Contraseña</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333333; margin: 0; padding: 0;">
    <div style="max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #dddddd; border-radius: 8px; background-color: #ffffff;">
        <div style="margin-bottom: 20px;">
            <h2 style="color: #0056b3; text-align: center;">Recuperación de contraseña</h2>
            <p>Hola,</p>
            <p>Has solicitado restablecer tu contraseña para tu cuenta en Salud Connected.</p>
            <p>Para continuar, por favor haz clic en el siguiente botón:</p>
            <p style="text-align: center; margin: 25px 0;">
                <a href="$reset_link" style="display: inline-block; background-color: #007bff; color: #ffffff !important; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;">Restablecer contraseña</a>
            </p>
            <p>Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
            <p style="word-break: break-all;"><a href="$reset_link">$reset_link</a></p>
            <p><strong>Importante:</strong> Este enlace es válido únicamente por las próximas 3 horas.</p>
            <p>Si no solicitaste este cambio, puedes ignorar este correo electrónico de forma segura. Tu contraseña actual no ha sido modificada.</p>
        </div>
        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eeeeee; font-size: 0.9em; color: #555555; text-align: center;">
            <p>Atentamente,<br>Administradores de Salud Connected</p>
            <p>© {date('Y')} Salud Connected. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
HTML;

        // VERSIÓN EN TEXTO PLANO DEL CORREO PARA CLIENTES ANTIGUOS.
        $mail->AltBody = "Hola,\n\nHas solicitado restablecer tu contraseña para Salud Connected.\n\n"
                       . "Copia y pega el siguiente enlace en tu navegador para continuar:\n$reset_link\n\n"
                       . "Este enlace expira en 3 horas.\n\n"
                       . "Si no solicitaste este cambio, ignora este mensaje.\n\n"
                       . "Atentamente,\nAdministradores de Salud Connected";

        // ENVÍA EL CORREO.
        $mail->send();

        // SI EL ENVÍO ES EXITOSO, CREA UN MENSAJE FLASH Y REDIRIGE.
        $_SESSION['flash_message'] = 'Se ha enviado un correo con instrucciones. Revisa tu bandeja de entrada (y spam).';
        $_SESSION['flash_type'] = 'success';
        header('Location: ' . BASE_URL . '/include/olvide_contra.php');
        exit;

    } catch (Exception $e) {
        // MANEJO DE ERRORES SI PHPMailer FALLA AL ENVIAR EL CORREO.
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
        $_SESSION['flash_message'] = 'Error al enviar el correo. Inténtalo más tarde o contacta a soporte.';
        $_SESSION['flash_type'] = 'error';

        // ***** CAMBIO 2: SE DESACTIVA LA REDIRECCIÓN PARA VER EL MENSAJE DE ERROR EN PANTALLA *****
        // header('Location: ' . BASE_URL . '/include/olvide_contra.php');
        // exit;
    }

} else {
    // SI ALGUIEN INTENTA ACCEDER A ESTE ARCHIVO DIRECTAMENTE SIN ENVIAR DATOS, SE LE REDIRIGE.
    header('Location: ' . BASE_URL . '/include/olvide_contra.php');
    exit;
}
?>