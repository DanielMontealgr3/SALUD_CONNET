<?php
session_start();

require 'PHPMAILER/PHPMailer.php';
require 'PHPMAILER/SMTP.php';
require 'PHPMailer/Exception.php';
require 'conexion.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$base_url = "http://localhost/SALUDCONNECT";
$nombre_sitio = "Salud Connect";
$email_soporte = 'saludconneted@gmail.com';
$email_password = 'czlr pxjh jxeu vzsz';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["correo_usu"]);

    if (empty($email)) {
        $_SESSION['flash_message'] = 'El correo no puede estar vacío';
        $_SESSION['flash_type'] = 'error';
        header('Location: olvide_contra.php');
        exit;
    }

    $conex = new database();
    $con = $conex->conectar();

    $stmt = $con->prepare("SELECT doc_usu FROM usuarios WHERE correo_usu = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['flash_message'] = 'Correo incorrecto';
        $_SESSION['flash_type'] = 'error';
        header('Location: olvide_contra.php');
        exit;
    }

    $id_usuario = $user['doc_usu'];
    $token = bin2hex(random_bytes(50));
    $creacion_t = date("Y-m-d H:i:s");
    $expiracion_t = date("Y-m-d H:i:s", strtotime("+3 hour"));

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
        error_log("Error BD al guardar token: " . $e->getMessage());
        $_SESSION['flash_message'] = 'Ocurrió un error interno. Por favor, inténtelo más tarde.';
        $_SESSION['flash_type'] = 'error';
        header('Location: olvide_contra.php');
        exit;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $email_soporte;
        $mail->Password = $email_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($email_soporte, 'Soporte ' . $nombre_sitio);
        $mail->addAddress($email);

        $mail->Subject = 'Recuperación de contraseña - ' . $nombre_sitio;

        $reset_link = $base_url . "/include/change.php?token=" . urlencode($token);

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
            <p>Has solicitado restablecer tu contraseña para tu cuenta en $nombre_sitio.</p>
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
            <p>Atentamente,<br>Administradores de $nombre_sitio</p>
            <p>© {date('Y')} $nombre_sitio. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
HTML;

        $mail->AltBody = "Hola,\n\nHas solicitado restablecer tu contraseña para $nombre_sitio.\n\n"
                       . "Copia y pega el siguiente enlace en tu navegador para continuar:\n$reset_link\n\n"
                       . "Este enlace expira en 3 horas.\n\n"
                       . "Si no solicitaste este cambio, ignora este mensaje.\n\n"
                       . "Atentamente,\nAdministradores de $nombre_sitio";

        $mail->send();
        $_SESSION['flash_message'] = 'Se ha enviado un correo con instrucciones. Revisa tu bandeja de entrada (y spam).';
        $_SESSION['flash_type'] = 'success';
        header('Location: olvide_contra.php');
        exit;

    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
        $_SESSION['flash_message'] = 'Error al enviar el correo. Inténtalo más tarde o contacta a soporte.';
        $_SESSION['flash_type'] = 'error';
        header('Location: olvide_contra.php');
        exit;
    }

} else {
    header("Location: olvide_contra.php");
    exit;
}
?>