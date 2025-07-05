<?php

// Validaciones y conexiones necesarias
require_once '../include/validar_sesion.php';
require_once '../include/conexion.php';
require_once '../include/PHPMailer/PHPMailer.php';
require_once '../include/PHPMailer/SMTP.php';
require_once '../include/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// Configurar localización para mostrar fechas en español
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'esp');

// Conexión a la base de datos
$conex = new Database();
$con = $conex->conectar();
$doc_usuario = $_SESSION['doc_usu'];

// Datos para envío de correo
$nombre_sitio = "Salud Connected";
$email_soporte = 'saludconneted@gmail.com';
$email_password = 'czlr pxjh jxeu vzsz';

// Verificar si se envió el formulario de reagendamiento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reagendar_submit'])) {
    $id_registro = $_POST['id_registro'] ?? 0;
    $tipo_slug = $_POST['tipo'] ?? '';
    $id_horario_nuevo = $_POST['id_horario'] ?? 0;
    $tipo_evento_post = $_POST['tipo_evento'] ?? 'su turno';

    if (empty($id_registro) || empty($tipo_slug) || empty($id_horario_nuevo)) {
        $_SESSION['reagendamiento_status'] = ['tipo' => 'error', 'mensaje' => 'Faltan datos para reagendar.'];
    } else {
        $con->beginTransaction(); // Iniciar transacción para asegurar consistencia de datos
        try {
            $lugar_atencion = ['nombre' => 'No especificado', 'direccion' => 'No especificada'];
            $nueva_fecha = '';
            $nueva_hora_24h = '';

            // Reagendamiento de cita médica
            if ($tipo_slug === 'medica') {
                $stmt_nuevo_horario = $con->prepare("SELECT fecha_horario, horario FROM horario_medico WHERE id_horario_med = ? AND id_estado = 4");
                $stmt_nuevo_horario->execute([$id_horario_nuevo]);
                $nuevo_horario_info = $stmt_nuevo_horario->fetch(PDO::FETCH_ASSOC);
                if (!$nuevo_horario_info) throw new Exception("El nuevo horario ya no está disponible.");
                
                $nueva_fecha = $nuevo_horario_info['fecha_horario'];
                $nueva_hora_24h = $nuevo_horario_info['horario'];

                $stmt_cita = $con->prepare("SELECT id_horario_med, nit_ips FROM citas WHERE id_cita = ? AND doc_pac = ?");
                $stmt_cita->execute([$id_registro, $doc_usuario]);
                $cita_anterior = $stmt_cita->fetch(PDO::FETCH_ASSOC);
                if (!$cita_anterior) throw new Exception("La cita no fue encontrada.");

                // Liberar horario anterior y ocupar el nuevo
                $con->prepare("UPDATE horario_medico SET id_estado = 4 WHERE id_horario_med = ?")->execute([$cita_anterior['id_horario_med']]);
                $con->prepare("UPDATE horario_medico SET id_estado = 3 WHERE id_horario_med = ?")->execute([$id_horario_nuevo]);
                $con->prepare("UPDATE citas SET id_horario_med = ? WHERE id_cita = ?")->execute([$id_horario_nuevo, $id_registro]);
                
                // Obtener nombre y dirección del lugar
                $stmt_info = $con->prepare("SELECT nom_ips, direc_ips FROM ips WHERE nit_ips = ?");
                $stmt_info->execute([$cita_anterior['nit_ips']]);
                if ($lugar_temp = $stmt_info->fetch(PDO::FETCH_ASSOC)) $lugar_atencion = ['nombre' => $lugar_temp['nom_ips'], 'direccion' => $lugar_temp['direc_ips']];

            // Reagendamiento de entrega de medicamentos
            } elseif ($tipo_slug === 'medicamento') {
                $nueva_fecha = $_POST['fecha'];
                $stmt_nuevo_horario = $con->prepare("SELECT horario, nit_farm FROM horario_farm WHERE id_horario_farm = ? AND id_estado = 4");
                $stmt_nuevo_horario->execute([$id_horario_nuevo]);
                $nuevo_horario_info = $stmt_nuevo_horario->fetch(PDO::FETCH_ASSOC);
                if (!$nuevo_horario_info) throw new Exception("El nuevo horario no está disponible.");
                
                $nueva_hora_24h = $nuevo_horario_info['horario'];
                $con->prepare("UPDATE turno_ent_medic SET fecha_entreg = ?, hora_entreg = ? WHERE id_turno_ent = ?")->execute([$nueva_fecha, $id_horario_nuevo, $id_registro]);

                $stmt_info = $con->prepare("SELECT nom_farm, direc_farm FROM farmacias WHERE nit_farm = ?");
                $stmt_info->execute([$nuevo_horario_info['nit_farm']]);
                if ($lugar_temp = $stmt_info->fetch(PDO::FETCH_ASSOC)) $lugar_atencion = ['nombre' => $lugar_temp['nom_farm'], 'direccion' => $lugar_temp['direc_farm']];

            // Reagendamiento de examen médico
            } elseif ($tipo_slug === 'examen') {
                $stmt_turno_ant = $con->prepare("SELECT hora_exam FROM turno_examen WHERE id_turno_exa = ?");
                $stmt_turno_ant->execute([$id_registro]);
                $id_horario_anterior = $stmt_turno_ant->fetchColumn();

                $stmt_nuevo_horario = $con->prepare("SELECT horario FROM horario_examen WHERE id_horario_exan = ? AND id_estado = 4");
                $stmt_nuevo_horario->execute([$id_horario_nuevo]);
                $nuevo_horario_info = $stmt_nuevo_horario->fetch(PDO::FETCH_ASSOC);
                if (!$nuevo_horario_info) throw new Exception("El nuevo horario no está disponible.");

                $nueva_fecha = $_POST['fecha'];
                $nueva_hora_24h = $nuevo_horario_info['horario'];

                $con->prepare("UPDATE horario_examen SET id_estado = 4 WHERE id_horario_exan = ?")->execute([$id_horario_anterior]);
                $con->prepare("UPDATE horario_examen SET id_estado = 3 WHERE id_horario_exan = ?")->execute([$id_horario_nuevo]);
                $con->prepare("UPDATE turno_examen SET fech_exam = ?, hora_exam = ? WHERE id_turno_exa = ?")->execute([$nueva_fecha, $id_horario_nuevo, $id_registro]);

                $lugar_atencion = ['nombre' => 'Laboratorio Clínico Central', 'direccion' => 'Consultar dirección en la orden de examen.'];
            }

            // Obtener correo del paciente
            $stmt_paciente = $con->prepare("SELECT correo_usu, nom_usu FROM usuarios WHERE doc_usu = ?");
            $stmt_paciente->execute([$doc_usuario]);
            $paciente = $stmt_paciente->fetch(PDO::FETCH_ASSOC);

            // Enviar notificación por correo
            if ($paciente) {
                $fecha_formateada = ucfirst(strftime('%A, %d de %B de %Y', strtotime($nueva_fecha)));
                $hora_12h = (new DateTime($nueva_hora_24h))->format('h:i a');
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = $email_soporte;
                    $mail->Password = $email_password;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = 465;
                    $mail->setFrom($email_soporte, $nombre_sitio);
                    $mail->addAddress($paciente['correo_usu'], $paciente['nom_usu']);
                    $mail->isHTML(true);
                    $mail->CharSet = 'UTF-8';
                    $mail->Subject = 'Confirmación de Reagendamiento';
                    $mail->Body = "<h2>Cita Reagendada Exitosamente - $nombre_sitio</h2>
                        <p>Estimado/a {$paciente['nom_usu']},</p>
                        <p>Le informamos que su <strong>{$tipo_evento_post}</strong> ha sido reagendada con los siguientes detalles:</p>
                        <ul style='list-style-type: none; padding: 0;'>
                            <li><strong>Lugar de Atención:</strong> {$lugar_atencion['nombre']}</li>
                            <li><strong>Dirección:</strong> {$lugar_atencion['direccion']}</li>
                            <li><strong>Nueva Fecha:</strong> {$fecha_formateada}</li>
                            <li><strong>Nueva Hora:</strong> {$hora_12h}</li>
                        </ul><p>Gracias por utilizar nuestros servicios.</p>";
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Error al enviar correo de reagendamiento: {$mail->ErrorInfo}");
                }
            }

            // Confirmar transacción
            $con->commit();
            $_SESSION['reagendamiento_status'] = ['tipo' => 'exito', 'mensaje' => 'Cita reagendada y notificada con éxito.'];
        } catch (Exception $e) {
            $con->rollBack();
            $_SESSION['reagendamiento_status'] = ['tipo' => 'error', 'mensaje' => 'Error al reagendar: ' . $e->getMessage()];
        }
    }
}

// Redirigir de nuevo a la vista de citas
header('Location: citas_actuales.php');
exit;
?>