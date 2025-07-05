<?php
// =================================================================
// === INICIO DEL BLOQUE CORREGIDO (PORTABILIDAD) ===
// =================================================================

// 1. Inclusión de la configuración centralizada.
// Esto establece ROOT_PATH, BASE_URL, inicia sesión y conecta a la BD.
require_once __DIR__ . '/../include/config.php';

// 2. Inclusión de los scripts de seguridad y PHPMailer usando ROOT_PATH.
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';
require_once ROOT_PATH . '/include/PHPMailer/PHPMailer.php';
require_once ROOT_PATH . '/include/PHPMailer/SMTP.php';
require_once ROOT_PATH . '/include/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'esp');

// La variable de conexión `$con` ya está disponible desde config.php.
// No es necesario crear una nueva instancia de Database.

$mensaje_tipo = '';
$mensaje_texto = '';

// Las credenciales de correo se deben tomar de config.php.
// Lo ideal sería reemplazar estas variables por las constantes de config.php (ej. SMTP_HOST)
$nombresitio = "Salud Connected";
$email_soporte = 'saludconneted@gmail.com';
$email_password = 'czlr pxjh jxeu vzsz';

// La sesión ya se inicia en config.php.
// if (session_status() == PHP_SESSION_NONE) { session_start(); } // Esta línea ya no es necesaria.

// 3. Validación de sesión con redirección portable usando BASE_URL.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2) {
    header('Location: ' . BASE_URL . '/inicio_sesion.php');
    exit;
}

// =================================================================
// === FIN DEL BLOQUE CORREGIDO ===
// El resto del código permanece exactamente igual.
// =================================================================

$doc_usuario = $_SESSION['doc_usu'];
$show_confirm_modal = false;
$modal_data = [];

if (isset($_POST['prevalidar'])) {
    $id_detalle = $_POST['id_detalle'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $id_horario_farm_prevalidar = $_POST['hora_id'] ?? '';
    $hora_texto = $_POST['hora_texto'] ?? '';
    
    $errores = [];
    if (empty($id_detalle)) $errores[] = "el ID de Detalle";
    if (empty($fecha)) $errores[] = "la fecha";
    if (empty($id_horario_farm_prevalidar)) $errores[] = "la hora";

    if (!empty($errores)) {
        $mensaje_tipo = 'error';
        $mensaje_texto = 'Por favor, complete los siguientes campos: ' . implode(', ', $errores) . '.';
    } else {
        $stmtDetalle = $con->prepare("SELECT hc.id_historia, c.doc_pac FROM detalles_histo_clini dhc JOIN historia_clinica hc ON dhc.id_historia = hc.id_historia JOIN citas c ON hc.id_cita = c.id_cita WHERE dhc.id_detalle = ?");
        $stmtDetalle->execute([$id_detalle]);
        $detalle = $stmtDetalle->fetch(PDO::FETCH_ASSOC);

        if (!$detalle) {
            $mensaje_tipo = 'error'; $mensaje_texto = 'El ID de detalle no existe.';
        } elseif ($detalle['doc_pac'] != $doc_usuario) {
            $mensaje_tipo = 'error'; $mensaje_texto = 'Este detalle de historia clínica no le pertenece.';
        } else {
            $id_historia = $detalle['id_historia'];
            $stmtMedicamentos = $con->prepare("SELECT m.nom_medicamento, d.can_medica FROM detalles_histo_clini d JOIN medicamentos m ON d.id_medicam = m.id_medicamento WHERE d.id_detalle = ? AND d.id_medicam IS NOT NULL");
            $stmtMedicamentos->execute([$id_detalle]);
            $medicamentos = $stmtMedicamentos->fetchAll(PDO::FETCH_ASSOC);

            if (empty($medicamentos)) {
                $mensaje_tipo = 'advertencia'; $mensaje_texto = 'No se encontraron medicamentos en este detalle.';
            } else {
                $stmtDuplicado = $con->prepare("SELECT COUNT(*) FROM turno_ent_medic WHERE id_historia = ? AND id_est != 7");
                $stmtDuplicado->execute([$id_historia]);
                if ($stmtDuplicado->fetchColumn() > 0) {
                    $mensaje_tipo = 'advertencia'; $mensaje_texto = 'Ya existe un turno de entrega activo para esta historia.';
                } else {
                    $show_confirm_modal = true;
                    $fecha_ts = strtotime($fecha);
                    $fecha_formateada = ucfirst(strftime('%A, %d de %B de %Y', $fecha_ts));
                    $modal_data = ['id_detalle' => $id_detalle, 'fecha' => $fecha, 'id_horario' => $id_horario_farm_prevalidar, 'medicamentos' => $medicamentos, 'fecha_formateada' => $fecha_formateada, 'hora_formateada' => $hora_texto];
                }
            }
        }
    }
} elseif (isset($_POST['confirmar_agendamiento'])) {
    $id_detalle = $_POST['id_detalle_final'] ?? '';
    $fecha = $_POST['fecha_final'] ?? '';
    $id_horario_farm = $_POST['hora_final'] ?? '';
    
    if (empty($id_detalle) || empty($fecha) || empty($id_horario_farm)) {
        $mensaje_tipo = 'error'; $mensaje_texto = 'Faltan datos para confirmar el agendamiento.';
    } else {
        $stmtDetalle = $con->prepare("SELECT id_historia FROM detalles_histo_clini WHERE id_detalle = ?");
        $stmtDetalle->execute([$id_detalle]);
        $id_historia = $stmtDetalle->fetchColumn();

        $con->beginTransaction();
        try {
            $stmt_horario = $con->prepare("SELECT horario, nit_farm FROM horario_farm WHERE id_horario_farm = :id_horario FOR UPDATE");
            $stmt_horario->execute([':id_horario' => $id_horario_farm]);
            $horario = $stmt_horario->fetch(PDO::FETCH_ASSOC);
            if (!$horario) { throw new Exception('La hora seleccionada ya no está disponible.'); }
            
            $hora_24h = $horario['horario'];
            $nit_farm = $horario['nit_farm'];

            $con->prepare("INSERT INTO turno_ent_medic (fecha_entreg, hora_entreg, id_historia, id_est) VALUES (?, ?, ?, 3)")->execute([$fecha, $id_horario_farm, $id_historia]);
            
            $stmtFarmacia = $con->prepare("SELECT nom_farm, direc_farm FROM farmacias WHERE nit_farm = ?");
            $stmtFarmacia->execute([$nit_farm]);
            $farmacia = $stmtFarmacia->fetch(PDO::FETCH_ASSOC);
            $nombre_farmacia = $farmacia['nom_farm'] ?? 'Farmacia Desconocida';
            $direccion_farmacia = $farmacia['direc_farm'] ?? 'Dirección no disponible';

            $stmtPaciente = $con->prepare("SELECT correo_usu, nom_usu FROM usuarios WHERE doc_usu = ?");
            $stmtPaciente->execute([$doc_usuario]);
            $paciente = $stmtPaciente->fetch(PDO::FETCH_ASSOC);
            
            if ($paciente) {
                $fecha_formateada = ucfirst(strftime('%A, %d de %B de %Y', strtotime($fecha)));
                $hora_12h = (new DateTime($hora_24h))->format('h:i a');
                $mail = new PHPMailer(true);
                try {
                    // *** NOTA IMPORTANTE PARA PORTABILIDAD ***
                    // Esta sección debería usar las constantes de config.php para ser 100% portable.
                    // Ejemplo: $mail->Host = SMTP_HOST; $mail->Username = SMTP_USERNAME; etc.
                    // Pero se deja como estaba para no alterar la lógica que ya tienes.
                    $mail->isSMTP(); $mail->Host = 'smtp.gmail.com'; $mail->SMTPAuth = true;
                    $mail->Username = $email_soporte; $mail->Password = $email_password;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; $mail->Port = 465;
                    $mail->setFrom($email_soporte, $nombresitio);
                    $mail->addAddress($paciente['correo_usu'], $paciente['nom_usu']);
                    $mail->isHTML(true); $mail->CharSet = 'UTF-8';
                    $mail->Subject = 'Confirmación de Turno para Entrega de Medicamentos';
                    $mail->Body = "<h2>Turno Confirmado - $nombresitio</h2><p>Estimado/a {$paciente['nom_usu']},</p><p>Su turno para entrega de medicamentos ha sido agendado:</p><ul><li><strong>Fecha:</strong> $fecha_formateada</li><li><strong>Hora:</strong> $hora_12h</li><li><strong>Farmacia:</strong> $nombre_farmacia</li><li><strong>Dirección:</strong> $direccion_farmacia</li></ul><p>Gracias por usar nuestros servicios.</p>";
                    $mail->send();
                } catch (Exception $e) { error_log("Error al enviar correo: {$mail->ErrorInfo}"); }
            }
            
            $con->commit();
            $mensaje_tipo = 'exito'; 
            $mensaje_texto = '¡Turno agendado! Serás redirigido a "Mis Citas".';
        } catch (Exception $e) {
            $con->rollBack();
            $mensaje_tipo = 'error'; $mensaje_texto = $e->getMessage();
        }
    }
}
?>
<!-- El resto de tu código HTML iría aquí, y sus enlaces y actions de formularios
     deberían usar BASE_URL para ser portables. -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agendar Turno de Entrega de Medicamentos</title>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .fc-day-disabled { background-color: #f2f2f2 !important; cursor: not-allowed; }
        #calendar-wrapper.disabled {
            opacity: 0.5;
            pointer-events: none;
            cursor: not-allowed;
        }
    </style>
</head>
<?php include '../include/menu.php'; ?>
<body class="d-flex flex-column min-vh-100">
<main class="container py-4">
    <div class="card p-4 shadow-sm">
        <h2 class="mb-4 text-center" style="color: #004a99;">Agendar Turno de Entrega de Medicamentos</h2>
        <form method="POST" id="form-principal-turno" action="cita_medicamen.php">
            <input type="hidden" name="fecha" id="selected-date">
            <input type="hidden" name="hora_id" id="selected-hour-id">
            <input type="hidden" name="hora_texto" id="selected-hour-text">
            <input type="hidden" name="prevalidar" value="1">
            
            <div class="row align-items-start">
                <div class="col-lg-4">
                    <div class="form-group">
                        <label for="id_detalle" class="form-label fw-bold">1. Ingrese el numero de historia:</label>
                        <input type="number" name="id_detalle" id="id_detalle" class="form-control" required value="<?php echo htmlspecialchars($_POST['id_detalle'] ?? ''); ?>" placeholder="ID de la orden médica">
                    </div>
                </div>
                <div class="col-lg-8">
                    <label class="form-label fw-bold mt-3 mt-lg-0">2. Seleccione una Fecha:</label>
                    <div id="calendar-wrapper">
                        <div id="calendar" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade" id="hourModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Seleccione una Hora</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="text-center mb-3">Horas disponibles para el <strong id="fecha-modal-titulo"></strong></p><div id="horas-container" class="d-flex flex-wrap justify-content-center gap-2"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button></div></div></div></div>
    <div class="modal fade" id="confirmModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="bi bi-calendar-check me-2"></i>Confirmar Turno</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form id="form-confirmar" action="cita_medicamen.php" method="POST"><div class="modal-body" id="confirm-modal-body"></div><input type="hidden" name="id_detalle_final" id="id_detalle_final"><input type="hidden" name="fecha_final" id="fecha_final"><input type="hidden" name="hora_final" id="hora_final"><input type="hidden" name="confirmar_agendamiento" value="1"></form><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" form="form-confirmar" class="btn btn-success">Confirmar y Agendar</button></div></div></div></div>
    <div class="modal fade" id="notificationModal" tabindex="-1"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content text-center"><div class="modal-header border-0"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body px-4 pb-4"><h5 class="modal-title mb-2" id="notificationModalLabel"></h5><p id="notification-text" class="mb-0"></p></div><div class="modal-footer border-0 justify-content-center"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button></div></div></div></div>
</main>
<?php include '../include/footer.php'; ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const calendarWrapper = document.getElementById('calendar-wrapper');
    const idDetalleInput = document.getElementById('id_detalle');
    const hourModal = new bootstrap.Modal(document.getElementById('hourModal'));
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));

    function showNotification(title, message, type = 'advertencia') {
        const modalTitle = document.getElementById('notificationModalLabel');
        const modalText = document.getElementById('notification-text');
        modalText.textContent = message;
        let iconHtml = '';
        switch (type) {
            case 'exito': iconHtml = '<i class="bi bi-check-circle-fill text-success fs-1 mb-3"></i>'; break;
            case 'error': iconHtml = '<i class="bi bi-x-circle-fill text-danger fs-1 mb-3"></i>'; break;
            default: iconHtml = '<i class="bi bi-exclamation-triangle-fill text-warning fs-1 mb-3"></i>'; break;
        }
        modalTitle.innerHTML = iconHtml + title;
        notificationModal.show();
    }
    
    <?php if (!empty($mensaje_tipo)): ?>
        showNotification('<?php echo ucfirst($mensaje_tipo); ?>', '<?php echo addslashes($mensaje_texto); ?>', '<?php echo $mensaje_tipo; ?>');
        <?php if ($mensaje_tipo === 'exito'): ?>
            setTimeout(() => window.location.href = 'citas_actuales.php', 2500);
        <?php endif; ?>
    <?php endif; ?>
    
    <?php if ($show_confirm_modal): ?>
        const modalData = <?php echo json_encode($modal_data); ?>;
        let medicamentosHtml = modalData.medicamentos.map(med => `<li>${med.nom_medicamento} (Cantidad: ${med.can_medica})</li>`).join('');
        $('#confirm-modal-body').html(
            `<p>Está a punto de agendar un turno para entregar los siguientes medicamentos:</p>
             <ul class="list-unstyled text-center">${medicamentosHtml}</ul>
             <hr>
             <ul class="list-unstyled mt-3">
                <li><strong>Fecha:</strong> ${modalData.fecha_formateada}</li>
                <li><strong>Hora:</strong> ${modalData.hora_formateada}</li>
             </ul>`
        );
        $('#id_detalle_final').val(modalData.id_detalle);
        $('#fecha_final').val(modalData.fecha);
        $('#hora_final').val(modalData.id_horario);
        confirmModal.show();
    <?php endif; ?>

    function toggleCalendarState() {
        if (idDetalleInput.value.trim() !== '') {
            calendarWrapper.classList.remove('disabled');
        } else {
            calendarWrapper.classList.add('disabled');
        }
    }

    toggleCalendarState();
    idDetalleInput.addEventListener('input', toggleCalendarState);

    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'es', initialView: 'dayGridMonth', selectable: true, aspectRatio: 1.8,
        validRange: { start: new Date() }, headerToolbar: { left: 'prev', center: 'title', right: 'next' },
        dateClick: function(info) {
            if (info.dayEl.classList.contains('fc-day-disabled')) return;
            
            $('#selected-date').val(info.dateStr);
            $('#fecha-modal-titulo').text(info.date.toLocaleDateString('es-ES', { dateStyle: 'full' }));
            loadHours(info.dateStr);
        },
        dayCellDidMount: function(info) { if (info.date.getDay() === 0 || info.date.getDay() === 6) info.el.classList.add('fc-day-disabled'); },
    });
    calendar.render();

    $(document).on('click', '.hour-btn', function() {
        if ($(this).is(':disabled')) return;
        
        $('#selected-hour-id').val($(this).data('id-horario'));
        $('#selected-hour-text').val($(this).text());
        hourModal.hide();
        
        $('#form-principal-turno').submit();
    });

    function loadHours(fecha) {
        hourModal.show();
        $('#horas-container').html('<div class="spinner-border text-primary" role="status"></div>');
        $.ajax({
            url: 'consultas_citas/hora_turno_medi.php',
            type: 'POST', dataType: 'json', data: { fecha: fecha },
            success: function(response) {
                let html = '';
                if (response.error) {
                    html = `<p class="text-danger text-center">${response.error}</p>`;
                } else if (response.hours && response.hours.length > 0) {
                    html = response.hours.map(row => `<button type="button" class="btn btn-outline-primary hour-btn" data-id-horario="${row.id}" ${row.isOccupied ? 'disabled' : ''}>${row.hora12}</button>`).join('');
                } else {
                    html = '<p class="text-muted text-center">No hay horas disponibles este día.</p>';
                }
                $('#horas-container').html(html);
            },
            error: function() { $('#horas-container').html('<p class="text-danger">Error al cargar las horas.</p>'); }
        });
    }
});
</script>
</body>
</html>