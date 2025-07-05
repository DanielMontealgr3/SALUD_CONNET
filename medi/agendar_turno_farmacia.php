<?php
// === LÍNEAS MÁGICAS PARA DEPURACIÓN (ACTIVADAS) ===
// === LÍNEAS MÁGICAS PARA DEPURACIÓN (ACTIVADAS) ===
// Estas líneas hacen que los errores de PHP se muestren en pantalla durante desarrollo.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// === FIN ===

require_once '../include/validar_sesion.php'; 
require_once '../include/inactividad.php'; 
require_once '../include/conexion.php';
require_once '../include/email_service.php'; 

// INICIAR SESIÓN SI NO ESTÁ ACTIVA
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// VERIFICAR SI EL USUARIO ESTÁ LOGUEADO
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) { 
    header('Location: ../inicio_sesion.php'); 
    exit; 
}

// CONECTARSE A LA BASE DE DATOS USANDO LA CLASE PERSONALIZADA
$db = new Database(); 
$pdo = $db->conectar(); 

$mensaje_tipo = ''; 
$mensaje_texto = ''; 
$ordenes_a_mostrar = [];

// 1. VALIDAR DOCUMENTO DEL PACIENTE
// Se toma el documento de la URL o de la sesión activa.
$doc_a_consultar = $_GET['documento'] ?? ($_SESSION['doc_usu'] ?? null);

// SI NO HAY DOCUMENTO, REDIRECCIONA
if (empty($doc_a_consultar)) { 
    $_SESSION['mensaje_error'] = "No se ha especificado un documento de paciente."; 
    header('Location: ../inicio.php'); 
    exit; 
}

// CONSULTAR DATOS DEL PACIENTE
try {
    $stmtPaciente = $pdo->prepare("SELECT nom_usu, foto_usu, correo_usu FROM usuarios WHERE doc_usu = :documento");
    $stmtPaciente->execute([':documento' => $doc_a_consultar]);
    $paciente = $stmtPaciente->fetch(PDO::FETCH_ASSOC);

    if ($paciente === false) { 
        $_SESSION['mensaje_error'] = "Paciente no encontrado."; 
        header('Location: ../inicio.php'); 
        exit; 
    }
} catch (PDOException $e) {
    error_log("Error de BD al buscar paciente: " . $e->getMessage());
    $_SESSION['mensaje_error'] = "Error en la base de datos.";
    header('Location: ../inicio.php');
    exit;
}

$pageTitle = "Agendar Turno para: " . htmlspecialchars($paciente['nom_usu']);

// VOLVER A LA PÁGINA ANTERIOR DEPENDIENDO DE SI LLEGÓ DESDE UN DETALLE O NO
$url_volver = isset($_GET['documento']) ? 'deta_historia_clini.php?documento=' . urlencode($doc_a_consultar) : '../inicio.php';

// 2. PROCESAMIENTO DEL FORMULARIO (SI SE ENVIÓ UN POST CON BOTÓN 'enviar')
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])) {

    // OBTENER Y VALIDAR CAMPOS DEL FORMULARIO
    $id_historia = filter_input(INPUT_POST, 'id_historia', FILTER_VALIDATE_INT); 
    $fecha = trim($_POST['fecha'] ?? ''); 
    $id_horario_farm = filter_input(INPUT_POST, 'hora_id', FILTER_VALIDATE_INT);
    
    // SI FALTAN DATOS, MOSTRAR ERROR
    if (empty($id_historia) || empty($fecha) || empty($id_horario_farm)) { 
        $mensaje_tipo = 'error'; 
        $mensaje_texto = 'Datos incompletos desde el formulario.'; 
    } else {
        try {
            date_default_timezone_set('America/Bogota');

            // === VALIDACIÓN ESPECIAL: SI LA FECHA ES HOY, LA HORA DEBE SER AL MENOS 30 MINUTOS MÁS TARDE
            if ($fecha === date('Y-m-d')) {
                $stmtHoraValidar = $pdo->prepare("SELECT horario, meridiano FROM horario_farm WHERE id_horario_farm = ?");
                $stmtHoraValidar->execute([$id_horario_farm]);
                $hora_data = $stmtHoraValidar->fetch(PDO::FETCH_ASSOC);

                if ($hora_data) {
                    $hora_limite_dt = new DateTime("now");
                    $hora_limite_dt->add(new DateInterval('PT30M')); // Agrega 30 minutos a la hora actual
                    
                    // CONVERTIR HORA DESDE LA TABLA (formato 12H + meridiano) a objeto DateTime
                    list($h, $m, $s) = explode(':', $hora_data['horario']);
                    $h = (int)$h;

                    // AJUSTAR HORA SEGÚN MERIDIANO
                    if ($hora_data['meridiano'] == 2 && $h != 12) { // PM
                        $h += 12;
                    }
                    if ($hora_data['meridiano'] == 1 && $h == 12) { // 12 AM
                        $h = 0;
                    }

                    // CREAR OBJETO DE LA HORA DEL TURNO
                    $hora_del_turno_dt = new DateTime();
                    $fecha_parts = date_parse($fecha);
                    $hora_del_turno_dt->setDate($fecha_parts['year'], $fecha_parts['month'], $fecha_parts['day']);
                    $hora_del_turno_dt->setTime($h, (int)$m, (int)$s);

                    // SI LA HORA DEL TURNO ES ANTES DEL LÍMITE, ERROR
                    if ($hora_del_turno_dt < $hora_limite_dt) {
                        throw new Exception("La hora seleccionada ya no está disponible.");
                    }
                }
            }

            // === COMENZAR TRANSACCIÓN PARA GUARDAR EL TURNO ===
            $pdo->beginTransaction();

            // VERIFICAR SI LA HORA YA ESTÁ OCUPADA
            $stmtDisponibilidad = $pdo->prepare("SELECT COUNT(*) FROM turno_ent_medic WHERE fecha_entreg = ? AND hora_entreg = ? FOR UPDATE"); 
            $stmtDisponibilidad->execute([$fecha, $id_horario_farm]);
            if ($stmtDisponibilidad->fetchColumn() > 0) { 
                throw new Exception("La hora que seleccionó acaba de ser ocupada. Por favor, elija otra."); 
            }

            // INSERTAR TURNO
            $insert = $pdo->prepare("INSERT INTO turno_ent_medic (fecha_entreg, hora_entreg, id_historia, id_est) VALUES (?, ?, ?, 3)");
            $insert->execute([$fecha, $id_horario_farm, $id_historia]);

            // === ENVIAR CORREO DE CONFIRMACIÓN ===
            if (!empty($paciente['correo_usu'])) {
                // FORMATEAR HORA LEGIBLE
                $stmtHora = $pdo->prepare("SELECT TIME_FORMAT(meridiano, '%h:%i %p') AS hora_legible FROM horario_farm WHERE id_horario_farm = ?");
                $stmtHora->execute([$id_horario_farm]);
                $hora_turno = $stmtHora->fetchColumn();

                // MEDICAMENTOS RELACIONADOS A LA ORDEN
                $stmtMeds = $pdo->prepare("
                    SELECT m.nom_medicamento 
                    FROM detalles_histo_clini dhc 
                    JOIN medicamentos m ON dhc.id_medicam = m.id_medicamento 
                    WHERE dhc.id_historia = ? 
                        AND dhc.id_medicam IS NOT NULL 
                        AND dhc.id_medicam != 0
                ");
                $stmtMeds->execute([$id_historia]);
                $medicamentos_para_correo = $stmtMeds->fetchAll(PDO::FETCH_COLUMN);

                // DATOS PARA EL EMAIL
                $datosCorreo = [
                    'email_paciente'  => $paciente['correo_usu'], 
                    'nombre_paciente' => $paciente['nom_usu'],
                    'fecha_turno'     => date("d/m/Y", strtotime($fecha)),
                    'hora_turno'      => $hora_turno ?: 'N/D',
                    'medicamentos'    => !empty($medicamentos_para_correo) ? $medicamentos_para_correo : ['Sin medicamentos especificados']
                ];

                // LLAMAR FUNCIÓN PARA ENVIAR CORREO
                if (!enviarCorreoConfirmacionTurno($datosCorreo)) {
                    error_log("Fallo al enviar correo de confirmación a " . $paciente['correo_usu']);
                    $mensaje_texto_adicional = " (pero no se pudo enviar el correo)";
                }
            }

            $pdo->commit(); // TODO OK
            $mensaje_tipo = 'exito'; 
            $mensaje_texto = 'Turno agendado con éxito.' . ($mensaje_texto_adicional ?? '');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); } 
            $mensaje_tipo = 'error'; 
            $mensaje_texto = 'Error: ' . $e->getMessage(); 
        }
    }
}


// 3. CARGA DE ÓRDENES PENDIENTES
try {
    $sql_ordenes_pendientes = "SELECT DISTINCT hc.id_historia, hm.fecha_horario, c.nit_ips FROM historia_clinica hc JOIN citas c ON hc.id_cita = c.id_cita JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med WHERE c.doc_pac = :doc_paciente AND hc.id_historia NOT IN (SELECT id_historia FROM turno_ent_medic) AND EXISTS (SELECT 1 FROM detalles_histo_clini dhc WHERE dhc.id_historia = hc.id_historia AND dhc.id_medicam IS NOT NULL AND dhc.id_medicam != 0) ORDER BY hm.fecha_horario DESC";
    $stmt_ordenes = $pdo->prepare($sql_ordenes_pendientes); $stmt_ordenes->execute(['doc_paciente' => $doc_a_consultar]); $historias_pendientes = $stmt_ordenes->fetchAll(PDO::FETCH_ASSOC);
    foreach ($historias_pendientes as $historia) {
        $id_historia_loop = $historia['id_historia']; $nit_ips_cita = $historia['nit_ips']; $convenio_para_esta_orden = false;
        if ($nit_ips_cita) { $stmtConvenio = $pdo->prepare("SELECT COUNT(def.id_eps_farm) FROM detalle_eps_ips dei JOIN detalle_eps_farm def ON dei.nit_eps = def.nit_eps WHERE dei.nit_ips = ? AND def.id_estado = 1"); $stmtConvenio->execute([$nit_ips_cita]); if ($stmtConvenio->fetchColumn() > 0) { $convenio_para_esta_orden = true; } }
        $sql_medicamentos = "SELECT m.nom_medicamento, dhc.can_medica FROM detalles_histo_clini dhc JOIN medicamentos m ON dhc.id_medicam = m.id_medicamento WHERE dhc.id_historia = :id_historia AND dhc.id_medicam IS NOT NULL AND dhc.id_medicam != 0";
        $stmt_medicamentos = $pdo->prepare($sql_medicamentos); $stmt_medicamentos->execute(['id_historia' => $id_historia_loop]); $lista_medicamentos = $stmt_medicamentos->fetchAll(PDO::FETCH_ASSOC);
        $medicamentos_formateados = [];
        foreach ($lista_medicamentos as $med) { $medicamentos_formateados[] = htmlspecialchars($med['nom_medicamento']) . ' (Cant: ' . htmlspecialchars($med['can_medica']) . ')'; }
        $medicamentos_str = implode('; ', $medicamentos_formateados);
        $ordenes_a_mostrar[] = ['id_historia' => $id_historia_loop, 'fecha_horario' => $historia['fecha_horario'], 'medicamentos_str' => $medicamentos_str, 'tiene_convenio' => $convenio_para_esta_orden];
    }
} catch (PDOException $e) { 
    $mensaje_tipo = 'error'; 
    $mensaje_texto = "Error al cargar las órdenes pendientes: " . $e->getMessage(); 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .patient-info-header { background-color: #f8f9fa; padding: 1rem; border-radius: 0.3rem; margin-bottom: 1.5rem; border: 1px solid #dee2e6; }
        .fc-day-disabled { background-color: #f5f5f5 !important; cursor: not-allowed; } 
        .hour-btn { width: 100%; } .hour-btn:disabled { text-decoration: line-through; color: #6c757d; }
      <!-- ========================================================== -->
    <!-- ==      NUEVO BLOQUE DE CSS RESPONSIVO Y DE ESTILO      == -->
    <!-- ========================================================== -->
   
        .patient-info-header { background-color: #f8f9fa; padding: 1rem; border-radius: 0.3rem; margin-bottom: 1.5rem; border: 1px solid #dee2e6; }
        .fc-day-disabled { background-color: #f5f5f5 !important; cursor: not-allowed; } 
        .hour-btn { width: 100%; } .hour-btn:disabled { text-decoration: line-through; color: #6c757d; }

        /* Estilos para compactar el calendario en todas las pantallas */
        .fc .fc-toolbar-title { font-size: 1.25em; }
        .fc .fc-button { font-size: 0.8em; padding: 0.4em 0.6em; }
        .fc-daygrid-day-number { font-size: 0.8em; }
        .fc-col-header-cell-cushion { font-size: 0.9em; }

        /* Media  para pantallas pequeñas (móviles y tablets en vertical) */
        @media screen and (max-width: 768px) {
            .patient-info-header .d-flex {
                flex-direction: column; /* Apila los elementos verticalmente */
                align-items: center !important; /* Centra todo */
                text-align: center;
                gap: 0.5rem; /* Espacio entre elementos */
            }
            .patient-info-header .ms-auto {
                margin-left: 0 !important; /* Resetea el margen */
                margin-top: 1rem; /* Añade espacio arriba */
            }
            main.container {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
            .card.p-4 {
                padding: 1.5rem !important; /* Reduce el padding en móviles */
            }
            /* Reduce el tamaño del texto en móviles para que quepa mejor */
            .form-label, .form-select, .form-text {
                font-size: 0.9rem;
            }
        }
    
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../include/menu.php'; ?>
    <main class="container py-4">
        <div class="patient-info-header">
            <div class="d-flex align-items-center">
                <?php if (!empty($paciente['foto_usu'])): ?>
                    <img src="../fotos_usuarios/<?= htmlspecialchars($paciente['foto_usu']); ?>" alt="Foto Paciente" class="img-thumbnail me-3" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <div class="img-thumbnail me-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background-color: #e9ecef; border-radius: 50%;"><i class="fas fa-user fa-2x text-secondary"></i></div>
                <?php endif; ?>
                <div>
                    <h3><?= htmlspecialchars($paciente['nom_usu']); ?></h3>
                    <p class="mb-0 text-muted">Documento: <?= htmlspecialchars($doc_a_consultar); ?></p>
                </div>
                <div class="ms-auto">
                    <a href="<?= $url_volver ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver a Órdenes</a>
                </div>
            </div>
        </div>
        <div class="card p-4 shadow-sm">
            <h2 class="mb-4 text-center">Agendar Turno de Entrega de Medicamentos</h2>
            
            <?php if (!empty($mensaje_texto) && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($mensaje_texto) ?></div>
            <?php endif; ?>
            
            <?php if (empty($ordenes_a_mostrar) && empty($mensaje_texto)): ?>
                <div class="alert alert-info text-center">
                    <h4 class="alert-heading"><i class="bi bi-info-circle-fill"></i> Información</h4>
                    <p>No tiene órdenes de medicamentos pendientes por agendar.</p>
                </div>
            <?php else: ?>
                <form method="POST" id="form-turno" action="agendar_turno_farmacia.php?documento=<?= htmlspecialchars($doc_a_consultar) ?>">
                    <div class="row align-items-start">
                        <div class="col-lg-5 mb-3">
                            <label for="id_historia" class="form-label fw-bold">1. Seleccione la Orden a Reclamar:</label>
                            <select name="id_historia" id="id_historia" class="form-select" required>
                                <option value="" disabled selected>-- Elija una orden --</option>
                                <?php foreach ($ordenes_a_mostrar as $orden): ?>
                                    <option value="<?= $orden['id_historia'] ?>" <?= !$orden['tiene_convenio'] ? 'disabled' : '' ?>>
                                        Cita del <?= htmlspecialchars(date("d/m/Y", strtotime($orden['fecha_horario']))) ?>: <?= $orden['medicamentos_str'] ?> <?= !$orden['tiene_convenio'] ? ' (Sin convenio)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Solo se habilitan las órdenes con convenio activo.</div>
                        </div>
                        <div class="col-lg-7">
                            <label class="form-label fw-bold">2. Seleccione una Fecha:</label>
                            <div id="calendar" class="mt-2"></div>
                        </div>
                    </div>
                    <input type="hidden" name="fecha" id="selected-date">
                    <input type="hidden" name="hora_id" id="selected-hour-id">
                    <button type="submit" name="enviar" id="submit-hidden" style="display: none;"></button>
                </form>
            <?php endif; ?>
        </div>

        <div class="modal fade" id="hourModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Seleccionar Hora</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="text-center mb-3">Horas disponibles para el <strong id="fecha-modal-titulo"></strong></p><div id="horas-container" class="row g-2"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button></div></div></div></div>
        <div class="modal fade" id="confirmModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="bi bi-calendar-check me-2"></i>Confirmar Turno</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="confirm-modal-body"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button><button type="button" class="btn btn-success" id="btn-confirm-agenda">Confirmar y Agendar</button></div></div></div></div>
    </main>

    <?php include '../include/footer.php'; ?>

    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
      <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
          <strong class="me-auto" id="toast-title"></strong>
          <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toast-body">
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js'></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        const hourModal = new bootstrap.Modal(document.getElementById('hourModal')); 
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        
        const toastEl = document.getElementById('liveToast');
        const toast = new bootstrap.Toast(toastEl);
        const toastTitleEl = document.getElementById('toast-title');
        const toastBodyEl = document.getElementById('toast-body');

        function showNotification(title, message, type = 'info') {
            toastTitleEl.textContent = title;
            toastBodyEl.textContent = message;

            toastEl.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-warning');
            if (type === 'exito') {
                toastEl.classList.add('text-bg-success');
            } else if (type === 'error') {
                toastEl.classList.add('text-bg-danger');
            } else {
                toastEl.classList.add('text-bg-warning');
            }
            toast.show();
        }

        <?php if (!empty($mensaje_texto) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            showNotification(
                '<?= $mensaje_tipo === 'exito' ? 'Proceso Completado' : 'Error en el Proceso' ?>', 
                '<?= addslashes($mensaje_texto) ?>',
                '<?= $mensaje_tipo ?>'
            );
            <?php if ($mensaje_tipo === 'exito'): ?> 
                setTimeout(() => { window.location.href = window.location.pathname + '?documento=<?= urlencode($doc_a_consultar) ?>'; }, 3000); 
            <?php endif; ?>
        <?php endif; ?>

        const calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'es', 
            initialView: 'dayGridMonth', 
            headerToolbar: { left: 'prev', center: 'title', right: 'next' }, 
            validRange: { start: new Date().toISOString().split("T")[0] }, 
            height: 'auto',
            dateClick: function(info) {
                if (!document.getElementById('id_historia').value) { 
                    showNotification('Atención', 'Por favor, seleccione primero una orden de la lista.', 'warning'); 
                    return; 
                }
                if (info.dayEl.classList.contains('fc-day-past') || info.date.getDay() === 0 || info.date.getDay() === 6) return;
                document.getElementById('selected-date').value = info.dateStr; 
                document.getElementById('fecha-modal-titulo').textContent = info.date.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                loadHours(info.dateStr);
            },
            dayCellDidMount: function(info) { if (info.date.getDay() === 0 || info.date.getDay() === 6) { info.el.classList.add('fc-day-disabled'); } }
        });
        calendar.render();

              function loadHours(fecha) {
            $('#horas-container').html('<div class="col-12 text-center"><div class="spinner-border text-primary"></div></div>'); 
            hourModal.show();
            
            $.ajax({
                url: 'consultar_horario_farmacia.php', 
                type: 'POST', 
                dataType: 'json', 
                data: { fecha: fecha },
                success: function(response) {
                    let html = '';
                    if (response.error) { 
                        html = `<div class="col-12"><p class="text-danger text-center">${response.error}</p></div>`;
                    } else if (response.hours && response.hours.length > 0) {
                        
                        // --- LÓGICA DE TIEMPO REAL AÑADIDA AQUÍ ---
                        const esHoy = (new Date().toISOString().split('T')[0] === fecha);
                        const ahora = new Date();
                        
                        html = response.hours.map(row => {
                            let isDisabled = row.isOccupied;
                            let disabledReason = row.isOccupied ? 'Hora ya agendada' : '';

                            // Si es hoy, hacemos la validación de tiempo
                            if (esHoy && !isDisabled) {
                                // Convertimos la hora del botón (ej. "07:30 AM") a un objeto Date de hoy
                                const [time, period] = row.hora12.split(' ');
                                let [hours, minutes] = time.split(':');
                                hours = parseInt(hours, 10);

                                if (period.toUpperCase() === 'PM' && hours !== 12) {
                                    hours += 12;
                                }
                                if (period.toUpperCase() === 'AM' && hours === 12) {
                                    hours = 0; // Medianoche
                                }

                                const horaDelTurno = new Date();
                                horaDelTurno.setHours(hours, parseInt(minutes, 10), 0, 0);

                                // Creamos la hora límite (ahora + 30 minutos)
                                const horaLimite = new Date();
                                horaLimite.setMinutes(horaLimite.getMinutes() + 30);
                                
                                // Comparamos
                                if (horaDelTurno < horaLimite) {
                                    isDisabled = true;
                                    disabledReason = 'Hora no disponible';
                                }
                            }
                            
                            return `<div class="col-3 mb-2">
                                        <button type="button" 
                                                class="btn btn-outline-primary hour-btn" 
                                                data-time-id="${row.id_horario_farm}" 
                                                data-time-text="${row.hora12}" 
                                                title="${disabledReason}"
                                                ${isDisabled ? 'disabled' : ''}>
                                            ${row.hora12}
                                        </button>
                                    </div>`;
                        }).join('');
                    } else { 
                        html = '<div class="col-12"><p class="text-muted text-center">No hay horas disponibles para este día.</p></div>'; 
                    }
                    $('#horas-container').html(html);
                },
                error: function() { 
                    $('#horas-container').html('<div class="col-12"><p class="text-danger text-center">Error al cargar las horas.</p></div>'); 
                }
            });
        }

        $(document).on('click', '.hour-btn', function() {
            if ($(this).is(':disabled')) return;
            $('#selected-hour-id').val($(this).data('time-id'));
            const selectedDate = new Date($('#selected-date').val() + 'T00:00:00').toLocaleDateString('es-ES', { dateStyle: 'long' });
            $('#confirm-modal-body').html(`<p>Confirmar turno para el <strong>${selectedDate}</strong> a las <strong>${$(this).text()}</strong>.</p>`);
            hourModal.hide(); confirmModal.show();
        });

        $('#btn-confirm-agenda').on('click', function() { $('#submit-hidden').click(); });
    });
    </script>
</body>
</html>