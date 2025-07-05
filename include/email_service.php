<?php
// ADAPTACIÓN: Cargamos PHPMailer manualmente, como en tu script funcional.
// Asegúrate de que la ruta a la carpeta PHPMAILER sea correcta.
// Si tu carpeta se llama 'PHPMAILER' y está dentro de 'include', esta ruta es correcta.
require_once __DIR__ . '/PHPMAILER/PHPMailer.php';
require_once __DIR__ . '/PHPMAILER/SMTP.php';
require_once __DIR__ . '/PHPMAILER/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarCorreoConfirmacionTurno($datosTurno) {
    
    // Variables de configuración, como en tu script funcional.
    $nombre_empresa_sitio = "Salud Connected";
    $email_remitente = 'saludconneted@gmail.com'; 
    $password_remitente = 'czlr pxjh jxeu vzsz'; 

    $mail = new PHPMailer(true);

    try {
        // ADAPTACIÓN: Usamos la misma configuración de SMTP (puerto 587 y STARTTLS).
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $email_remitente;
        $mail->Password = $password_remitente;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Corregido a STARTTLS
        $mail->Port = 587; // Corregido al puerto 587
        $mail->CharSet = 'UTF-8';

        // Remitente y Destinatario
        $mail->setFrom($email_remitente, 'Turnos ' . $nombre_empresa_sitio);
        // La clave 'email_paciente' es la que pasamos desde el otro archivo.
        $mail->addAddress($datosTurno['email_paciente'], $datosTurno['nombre_paciente']);

        // Contenido del Correo
        $mail->isHTML(true);
        $mail->Subject = '✅ Confirmación de tu turno para entrega de medicamentos';
        
        // Creamos el cuerpo del correo de forma similar a tu script funcional.
        $destinatario_nombre = htmlspecialchars($datosTurno['nombre_paciente']);
        $fecha_turno = htmlspecialchars($datosTurno['fecha_turno']);
        $hora_turno = htmlspecialchars($datosTurno['hora_turno']);
        
        $lista_medicamentos_html = '';
        foreach ($datosTurno['medicamentos'] as $medicamento) {
            $lista_medicamentos_html .= "<li>" . htmlspecialchars($medicamento) . "</li>";
        }

        $mail->Body = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Confirmación de Turno</title></head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border: 1px solid #dddddd; border-radius: 8px;">
                    <tr>
                        <td align="center" style="padding: 30px 20px; background-color: #007bff; color: #ffffff; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                            <h1 style="margin: 0; color: #ffffff;">Turno Confirmado</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px 20px;">
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">¡Hola, $destinatario_nombre!</p>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Tu turno para la entrega de medicamentos ha sido agendado con éxito. Aquí están los detalles:</p>
                            <div style="background-color: #f9f9f9; border-left: 4px solid #007bff; padding: 15px; margin: 20px 0;">
                                <p style="margin: 0; font-size: 16px;"><strong>Fecha:</strong> $fecha_turno</p>
                                <p style="margin: 5px 0 0 0; font-size: 16px;"><strong>Hora:</strong> $hora_turno</p>
                            </div>
                            <p style="font-size: 16px; color: #333333;"><strong>Medicamentos a reclamar:</strong></p>
                            <ul style="font-size: 16px; color: #333333; line-height: 1.5;">
                                $lista_medicamentos_html
                            </ul>
                            <p style="font-size: 16px; color: #333333; line-height: 1.6;">Por favor, preséntate puntualmente con tu documento de identidad.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 20px; background-color: #f0f0f0; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; font-size: 12px; color: #777777;">
                            © 2024 $nombre_empresa_sitio. Todos los derechos reservados.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        // Versión de texto plano para clientes de correo antiguos
        $mail->AltBody = "Hola, $destinatario_nombre.\nTu turno ha sido confirmado para el $fecha_turno a las $hora_turno.\nPor favor, preséntate puntualmente con tu documento de identidad.";
        
        $mail->send();
        return true;

    } catch (Exception $e) {
        // Guardamos el error en el log para poder revisarlo.
        error_log("Mailer Error para " . $datosTurno['email_paciente'] . ": " . $mail->ErrorInfo);
        return false;
    }
}
?>