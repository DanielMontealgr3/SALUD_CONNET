<?php
// Archivo: paci/pendientes.php

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

// Configuración de la zona horaria para formatear fechas en español
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'esp');

// La variable de conexión `$con` ya está disponible desde config.php.
// No es necesario crear una nueva instancia de Database.

// Las credenciales de correo se deben tomar de config.php.
// Lo ideal sería reemplazar estas variables por las constantes de config.php (ej. SMTP_HOST)
$nombre_sitio = "Salud Connected";
$email_soporte = 'saludconneted@gmail.com';
$email_password = 'czlr pxjh jxeu vzsz';

// La sesión ya se inicia en config.php.
// if (session_status() == PHP_SESSION_NONE) { session_start(); } // Esta línea ya no es necesaria.

// 3. Validación de sesión con redirección portable usando BASE_URL.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2 || !isset($_SESSION['doc_usu'])) {
    header('Location: ' . BASE_URL . '/inicio_sesion.php');
    exit;
}

// =================================================================
// === FIN DEL BLOQUE CORREGIDO ===
// El resto del código permanece exactamente igual.
// =================================================================

$doc_usuario = $_SESSION['doc_usu'];
$pageTitle = "Turno para Medicamentos";

// --- PROCESAR AGENDAMIENTO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agendar_turno') {
    $id_detalle = $_POST['id_detalle'] ?? null;
    $fecha = $_POST['fecha'] ?? null;
    $id_horario = $_POST['id_horario'] ?? null;
    $hora_texto = $_POST['hora_texto'] ?? 'hora seleccionada';
    
    // Obtener nombre del medicamento
    $stmt_med = $con->prepare("SELECT m.nom_medicamento FROM detalles_histo_clini dh JOIN medicamentos m ON dh.id_medicam = m.id_medicamento WHERE dh.id_detalle = :id_detalle LIMIT 1");
    $stmt_med->execute([':id_detalle' => $id_detalle]);
    $medicamento_nombre = $stmt_med->fetchColumn();

    if ($id_detalle && $fecha && $id_horario && $medicamento_nombre) {
        try {
            $con->beginTransaction();
            $stmt_hist = $con->prepare("SELECT id_historia FROM detalles_histo_clini WHERE id_detalle = :id_detalle");
            $stmt_hist->execute([':id_detalle' => $id_detalle]);
            $id_historia = $stmt_hist->fetchColumn();
            if ($id_historia) {
                $stmt_insert = $con->prepare("INSERT INTO turno_ent_medic (fecha_entreg, hora_entreg, id_historia, id_est) VALUES (?, ?, ?, 3)");
                $stmt_insert->execute([$fecha, $id_horario, $id_historia]);

                $stmt_update = $con->prepare("UPDATE entrega_pendiente SET id_estado = 9 WHERE id_detalle_histo = ? AND id_estado = 10");
                $stmt_update->execute([$id_detalle]);
                
                // ---- LÓGICA DE ENVÍO DE CORREO ----
                $stmt_paciente = $con->prepare("SELECT correo_usu, nom_usu FROM usuarios WHERE doc_usu = ?");
                $stmt_paciente->execute([$doc_usuario]);
                $paciente = $stmt_paciente->fetch(PDO::FETCH_ASSOC);

                if ($paciente) {
                    $fecha_formateada = ucfirst(strftime('%A, %d de %B de %Y', strtotime($fecha)));
                    
                    $mail = new PHPMailer(true);
                    try {
                        // *** NOTA IMPORTANTE PARA PORTABILIDAD ***
                        // Esta sección debería usar las constantes de config.php para ser 100% portable.
                        // Ejemplo: $mail->Host = SMTP_HOST; $mail->Username = SMTP_USERNAME; etc.
                        // Pero se deja como estaba para no alterar la lógica que ya tienes.
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = $email_soporte;
                        $mail->Password   = $email_password;
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port       = 465;

                        $mail->setFrom($email_soporte, $nombre_sitio);
                        $mail->addAddress($paciente['correo_usu'], $paciente['nom_usu']);

                        $mail->isHTML(true);
                        $mail->CharSet = 'UTF-8';
                        $mail->Subject = 'Confirmación de Turno para Entrega de Medicamentos Pendientes';
                        $mail->Body    = "
                            <h2>Confirmación de Turno - $nombre_sitio</h2>
                            <p>Estimado/a {$paciente['nom_usu']},</p>
                            <p>Hemos agendado su turno para la entrega de los medicamentos pendientes asociados a la <strong>Historia #$id_detalle</strong>:</p>
                            <ul>
                                <li><strong>Fecha:</strong> $fecha_formateada</li>
                                <li><strong>Hora:</strong> $hora_texto</li>
                            </ul>
                            <p><strong>Recomendaciones:</strong></p>
                            <ul>
                                <li>Llegue 10 minutos antes de la hora programada.</li>
                                <li>Recuerde llevar su documento de identidad.</li>
                            </ul>
                            <p>Si tiene alguna duda, contáctenos en $email_soporte.</p>
                        ";
                        $mail->send();
                    } catch (Exception $e) {
                        error_log("Error al enviar correo: {$mail->ErrorInfo}");
                    }
                }

                $con->commit();
                $_SESSION['modal_exito'] = ['medicamento' => $medicamento_nombre, 'fecha' => date("d/m/Y", strtotime($fecha)), 'hora' => $hora_texto, 'id_detalle' => $id_detalle];
            } else {
                $_SESSION['modal_error'] = 'La historia especificada no fue encontrada.';
                $con->rollBack();
            }
        } catch (PDOException $e) {
            $con->rollBack();
            $_SESSION['modal_error'] = 'Error al procesar la cita. Inténtalo de nuevo.';
            error_log("Error al agendar turno: " . $e->getMessage());
        }
    } else {
        $_SESSION['modal_error'] = 'Faltaron datos para completar el agendamiento.';
    }
    // 4. Redirección portable a la misma página.
    header('Location: ' . BASE_URL . '/paci/pendientes.php');
    exit;
}

// LÓGICA DE REDIRECCIÓN INICIAL
try {
    $stmt_total = $con->prepare("SELECT COUNT(*) FROM entrega_pendiente ep JOIN detalles_histo_clini dh ON ep.id_detalle_histo = dh.id_detalle JOIN historia_clinica hc ON dh.id_historia = hc.id_historia JOIN citas c ON hc.id_cita = c.id_cita WHERE c.doc_pac = :doc_usu AND ep.id_estado = 10");
    $stmt_total->execute([':doc_usu' => $doc_usuario]);
    $total_items = $stmt_total->fetchColumn();

    if ($total_items == 0 && !isset($_SESSION['modal_exito'])) {
        // 5. Redirección portable a otra página.
        header('Location: ' . BASE_URL . '/paci/cita_medicamen.php');
        exit;
    }
} catch (PDOException $e) {
    // 5. Redirección portable en caso de error.
    header('Location: ' . BASE_URL . '/paci/cita_medicamen.php');
    exit;
}

// Paginación y obtención de datos (sin cambios en la lógica)
$items_por_pagina = 3;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $items_por_pagina;
$total_paginas = ceil($total_items / $items_por_pagina);
$stmt_pendientes = $con->prepare("SELECT dh.id_historia, dh.id_detalle, m.nom_medicamento, ep.cantidad_pendiente FROM entrega_pendiente ep JOIN detalles_histo_clini dh ON ep.id_detalle_histo = dh.id_detalle JOIN historia_clinica hc ON dh.id_historia = hc.id_historia JOIN citas c ON hc.id_cita = c.id_cita LEFT JOIN medicamentos m ON dh.id_medicam = m.id_medicamento WHERE c.doc_pac = :doc_usu AND ep.id_estado = 10 LIMIT :limit OFFSET :offset");
$stmt_pendientes->bindValue(':doc_usu', $doc_usuario);
$stmt_pendientes->bindValue(':limit', $items_por_pagina, PDO::PARAM_INT);
$stmt_pendientes->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_pendientes->execute();
$pendientes = $stmt_pendientes->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- El resto de tu código HTML iría aquí, y sus enlaces (ej. paginación)
     también deberían usar BASE_URL para ser portables. -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo $pageTitle; ?></title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
    <style>
        .paginacion { display: flex; justify-content: center; align-items: center; gap: 1rem; }
        .paginacion .page-info { font-weight: bold; }
        .horarios-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(95px, 1fr)); gap: 10px; max-height: 200px; overflow-y: auto; padding: 10px; border: 1px solid #dee2e6; border-radius: .375rem; }
        .horario-btn.active { background-color: #0d6efd; color: white; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">
<?php include '../include/menu.php'; ?>

<main class="container py-5">
    <?php if (isset($_SESSION['modal_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['modal_error']); unset($_SESSION['modal_error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card p-4 shadow-sm" style="max-width: 700px; margin: auto;">
        <h2 class="text-center mb-2"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Historias con Medicamentos Pendientes</h2>
        <!-- MENSAJE INFORMATIVO AÑADIDO -->
        <p class="text-center text-muted mb-4 fst-italic">
            Recuerda que si un medicamento no está disponible, se te notificará por correo electrónico cuando puedas agendar el turno para reclamarlo.
        </p>

        <?php foreach ($pendientes as $p): ?>
            <div class="card mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-1">Historia #<?php echo htmlspecialchars($p['id_detalle']); ?></h6>
                        <small class="text-muted"><?php echo htmlspecialchars($p['nom_medicamento']); ?> (<?php echo htmlspecialchars($p['cantidad_pendiente']); ?> unds.)</small>
                    </div>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#agendarTurnoModal" data-id-detalle="<?php echo $p['id_detalle']; ?>">
                        Agendar
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if ($total_paginas > 1): ?>
            <nav class="paginacion mt-3">
                <a href="?pagina=<?php echo max(1, $pagina_actual - 1); ?>" class="btn btn-sm btn-outline-secondary <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>"><</a>
                <span class="page-info"><?php echo $pagina_actual; ?>/<?php echo $total_paginas; ?></span>
                <a href="?pagina=<?php echo min($total_paginas, $pagina_actual + 1); ?>" class="btn btn-sm btn-outline-secondary <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>">></a>
            </nav>
        <?php endif; ?>
         <hr>
        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                        <a href="citas.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left-circle"></i> Volver al Menú</a>
            <a href="cita_medicamen.php" class="btn btn-success"><i ></i> Agendar Turno de Historia Nueva</a>
        </div>
    </div>
</main>

<!-- MODAL DE AGENDAMIENTO -->
<div class="modal fade" id="agendarTurnoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agendar Turno para Historia #<span id="modalIdDetalle"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAgendamiento" action="pendientes.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="agendar_turno">
                    <input type="hidden" name="id_detalle" id="formDetalleId">
                    <input type="hidden" name="hora_texto" id="formHoraTexto">
                    
                    <div class="mb-3">
                        <label for="fecha-input" class="form-label"><b>Seleccione la fecha:</b></label>
                        <input type="text" id="fecha-input" name="fecha" class="form-control" placeholder="Haga clic para seleccionar..." required readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><b>Seleccione la hora:</b></label>
                        <div id="horarios-loader" class="text-center d-none"><div class="spinner-border spinner-border-sm"></div></div>
                        <div id="horarios-container" class="horarios-grid">
                            <small class="text-muted d-block text-center w-100" style="grid-column: 1 / -1;">Seleccione una fecha para ver los horarios.</small>
                        </div>
                        <input type="hidden" name="id_horario" id="formHorarioId" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnConfirmarTurno" disabled>Confirmar Turno</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL DE CONFIRMACIÓN (ÉXITO) -->
<div class="modal fade" id="confirmacionModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">¡Turno Agendado!</h5>
            </div>
            <div class="modal-body">
                <?php if (isset($_SESSION['modal_exito'])): ?>
                    <p>Tu turno para reclamar <strong><?php echo htmlspecialchars($_SESSION['modal_exito']['medicamento']); ?></strong> de la Historia #<?php echo htmlspecialchars($_SESSION['modal_exito']['id_detalle']); ?> ha sido confirmado.</p>
                    <ul>
                        <li><strong>Fecha:</strong> <?php echo htmlspecialchars($_SESSION['modal_exito']['fecha']); ?></li>
                        <li><strong>Hora:</strong> <?php echo htmlspecialchars($_SESSION['modal_exito']['hora']); ?></li>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnEntendido">Entendido</button>
            </div>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
<script src="js/form-submission.js"></script>
<script>
$(document).ready(function() {
    // --- LÓGICA DE MODALES ---
    <?php if (isset($_SESSION['modal_exito'])): ?>
        const modalExito = new bootstrap.Modal('#confirmacionModal');
        modalExito.show();
        <?php unset($_SESSION['modal_exito']); ?> 
    <?php endif; ?>

    $('#btnEntendido').on('click', function() {
        window.location.href = 'citas_actuales.php';
    });

    $('#agendarTurnoModal').on('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const idDetalle = $(button).data('id-detalle');
        
        $('#modalIdDetalle').text(idDetalle);
        $('#formDetalleId').val(idDetalle);
        $('#horarios-container').html('<small class="text-muted d-block text-center w-100" style="grid-column: 1 / -1;">Seleccione una fecha.</small>');
        $('#btnConfirmarTurno').prop('disabled', true);
        
        let fpInstance = flatpickr("#fecha-input", {
            locale: "es", minDate: "today", dateFormat: "Y-m-d", 
            altInput: true, altFormat: "d/m/Y",
            disable: [date => (date.getDay() === 0 || date.getDay() === 6)],
            onChange: function(selectedDates, dateStr) {
                if (!dateStr) return;
                $('#horarios-loader').removeClass('d-none');
                $('#horarios-container').html('');
                $('#btnConfirmarTurno').prop('disabled', true);
                $('#formHorarioId').val(''); 
                $.ajax({
                    url: 'consultas_citas/get_horarios.php', 
                    type: 'POST', data: { fecha: dateStr }, dataType: 'json',
                    success: function(response) {
                        $('#horarios-loader').addClass('d-none');
                        let horariosHtml = '<div class="alert alert-warning text-center p-2 w-100" style="grid-column: 1 / -1;">No hay horarios disponibles.</div>';
                        if (response.success && response.horarios.length > 0) {
                            horariosHtml = response.horarios.map(h => 
                                `<button type="button" class="btn btn-outline-primary btn-sm horario-btn" data-id-horario="${h.id_horario_farm}">${h.hora_formato}</button>`
                            ).join('');
                        }
                        $('#horarios-container').html(horariosHtml);
                    }
                });
            }
        });
    });
    
    $('#agendarTurnoModal').on('hidden.bs.modal', function() {
        if(flatpickr.instances['#fecha-input'] && flatpickr.instances['#fecha-input'][0]){
            flatpickr.instances['#fecha-input'][0].destroy();
        }
    });

    $('#horarios-container').on('click', '.horario-btn', function() {
        $('.horario-btn').removeClass('active');
        $(this).addClass('active');
        $('#formHorarioId').val($(this).data('id-horario'));
        $('#formHoraTexto').val($(this).text()); 
        $('#btnConfirmarTurno').prop('disabled', false);
    });
});
</script>
</body>
</html>