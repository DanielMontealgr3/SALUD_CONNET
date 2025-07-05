<?php
require '../include/fpdf/fpdf.php';
require '../include/PHPMailer/PHPMailer.php';
require '../include/PHPMailer/SMTP.php';
require '../include/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Datos del formulario
$correo_destino = $_POST['correo_usu'] ?? '';
$mensaje = $_POST['mensaje'] ?? '';

// Crear PDF con FPDF
$pdf = new FPDF();  
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(40, 10, 'Mensaje del formulario:');
$pdf->Ln(10);
$pdf->SetFont('Arial', '', 12);
$pdf->MultiCell(0, 10, $mensaje);

// Guardar PDF temporalmente
$pdf_path = 'mensaje.pdf';
$pdf->Output('F', $pdf_path);

// Enviar correo con PHPMailer
$mail = new PHPMailer(true);
 try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'saludconneted@gmail.com';
        $mail->Password = 'czlr pxjh jxeu vzsz'; // ⚠️ Mejor usa variables de entorno en lugar de exponer contraseñas.
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
    
        $mail->setFrom('saludconneted@gmail.com', 'Formulario');
        $mail->addAddress($correo_destino);
        $mail->Subject = 'Formulario en PDF';
        $mail->Body = 'Adjunto el PDF del mensaje enviado.';
        $mail->addAttachment($pdf_path);

        // Enlace de recuperación   
        $reset_link = "http://localhost/SALUDCONNECT/include/change.php?token=" . urlencode($token);

        



    $mail->send();
    echo 'Mensaje enviado correctamente';
} catch (Exception $e) {
    echo "No se pudo enviar. Error: {$mail->ErrorInfo}";
}
