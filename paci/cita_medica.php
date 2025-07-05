<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once '../include/conexion.php';
require_once '../include/PHPMailer/PHPMailer.php';
require_once '../include/PHPMailer/SMTP.php';
require_once '../include/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'esp');

$conex = new Database();
$con = $conex->conectar();
$mensaje_tipo = '';
$mensaje_texto = '';
$nombresitio = "Salud Connected";
$email_soporte = 'saludconneted@gmail.com';
$email_password = 'czlr pxjh jxeu vzsz';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2) {
    header('Location: ../inicio_sesion.php');
    exit;
}

$doc_usuario = $_SESSION['doc_usu'];

if (isset($_POST['enviar'])) {
    $estados_activos = [1, 3, 16];
    $in_placeholders = implode(',', array_fill(0, count($estados_activos), '?'));
    
    $verificar_cita = $con->prepare("SELECT id_cita FROM citas WHERE doc_pac = ? AND id_est IN ($in_placeholders)");
    $params_verif = array_merge([$doc_usuario], $estados_activos);
    $verificar_cita->execute($params_verif);

    if ($verificar_cita->rowCount() > 0) {
        $mensaje_tipo = 'advertencia';
        $mensaje_texto = 'Ya tienes una cita activa o próxima. Debes esperar a que finalice o cancelarla para agendar una nueva.';
    } else {
        $fecha = $_POST['fecha'] ?? '';
        $hora_24h = $_POST['hora'] ?? '';
        $nit_ips = $_POST['ips'] ?? '';
        $doc_medico = $_POST['medico'] ?? '';
        $fecha_solicitud = date('Y-m-d');

        if (empty($fecha) || empty($hora_24h) || empty($nit_ips) || empty($doc_medico)) {
            $mensaje_tipo = 'error';
            $mensaje_texto = 'Existen datos vacíos. Por favor, complete todos los campos.';
        } else {
            $con->beginTransaction();
            try {
                $estado_disponible = 4;
                $sql_horario = "SELECT id_horario_med FROM horario_medico WHERE doc_medico = :doc_medico AND fecha_horario = :fecha AND TIME(horario) = :hora AND id_estado = :estado_disponible FOR UPDATE";
                $stmt_horario = $con->prepare($sql_horario);
                $stmt_horario->execute([':doc_medico' => $doc_medico, ':fecha' => $fecha, ':hora' => $hora_24h, ':estado_disponible' => $estado_disponible]);
                $id_horario_med = $stmt_horario->fetchColumn();

                if (!$id_horario_med) {
                    throw new Exception('La hora seleccionada ya no está disponible. Por favor, elija otra.');
                }
                
                $estado_asignado = 3;
                $con->prepare("UPDATE horario_medico SET id_estado = :estado WHERE id_horario_med = :id_horario")->execute([':estado' => $estado_asignado, ':id_horario' => $id_horario_med]);
                $con->prepare("INSERT INTO citas (doc_pac, doc_med, nit_IPS, fecha_solici, id_horario_med, id_est) VALUES (:doc_pac, :doc_med, :nit_ips, :fecha_solici, :id_horario_med, :id_est)")->execute([':doc_pac' => $doc_usuario, ':doc_med' => $doc_medico, ':nit_ips' => $nit_ips, ':fecha_solici' => $fecha_solicitud, ':id_horario_med' => $id_horario_med, ':id_est' => $estado_asignado]);
                
                $con->commit();
                
                $mensaje_tipo = 'exito';
                $mensaje_texto = '¡Cita médica agendada correctamente! Serás redirigido a "Mis Citas" en unos segundos.';
                
            } catch (Exception $e) {
                $con->rollBack();
                error_log("Error en transacción: " . $e->getMessage());
                $mensaje_tipo = 'error';
                $mensaje_texto = 'Ocurrió un error al solicitar la cita: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agendar Cita Médica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .fc-day-unavailable {
            background-color:rgb(255, 255, 255) !important; /* Gris claro para días no disponibles */
            color:rgb(255, 255, 255);
            cursor: not-allowed;
        }
        .fc-day-available .fc-daygrid-day-frame {
            background-color: #e7f5ff; /* Azul claro para días disponibles */
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .fc-day-available .fc-daygrid-day-frame:hover {
            background-color: #cce5ff;
        }
        #calendar-wrapper.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        #no-dates-overlay {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            text-align: center;
            padding-top: 50px;
            z-index: 10;
        }
        #calendar-wrapper.no-dates #no-dates-overlay {
            display: block;
        }
    </style>
</head>

<?php include '../include/menu.php'; ?>

<body class="d-flex flex-column min-vh-100">
<main class="container py-4">
    <div class="card p-4 shadow-sm">
        <h2 class="mb-4 text-center" style="color: #004a99;">Agendar Cita Médica</h2>
        <form id="form-cita" method="POST">
            <div class="row">
                <div class="col-lg-4">
                    <div class="form-group mb-4">
                        <label for="ips" class="form-label fw-bold">1. Seleccione la IPS:</label>
                        <select name="ips" id="ips" class="form-select" required>
                            <option value="">Cargando...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="medico" class="form-label fw-bold">2. Seleccione el Médico:</label>
                        <select name="medico" id="medico" class="form-select" required disabled>
                            <option value="">Seleccione una IPS</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-8">
                    <label class="form-label fw-bold mt-3 mt-lg-0">3. Seleccione una Fecha:</label>
                    <div id="calendar-wrapper">
                        <div id="calendar" class="mt-2"></div>
                        <div id="no-dates-overlay">No hay días disponibles para este médico</div>
                    </div>
                </div>
            </div>
            
            <input type="hidden" name="fecha" id="selected-date">
            <input type="hidden" name="hora" id="hora">
            <button type="submit" name="enviar" id="submit-hidden" style="display: none;"></button>
        </form>
    </div>

    <!-- Modales -->
    <div class="modal fade" id="hourModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fecha-modal-titulo"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="horas-container"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Cita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="confirm-modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-confirm-agenda">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="notificationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notification-modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="notification-modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Aceptar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="loadingModal" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Procesando solicitud...</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../include/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/form-submission.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const calendarWrapper = document.getElementById('calendar-wrapper');
    const medicoSelect = document.getElementById('medico');
    const hourModal = new bootstrap.Modal(document.getElementById('hourModal'));
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));

    let availableDays = [];
    let diasConHorasDisponibles = {};

    function showNotification(title, message, type = 'advertencia') {
        const modalTitle = document.getElementById('notification-modal-title');
        const modalBody = document.getElementById('notification-modal-body');
        modalTitle.textContent = title;
        modalBody.innerHTML = message;
        if (type === 'exito') {
            modalBody.classList.add('text-success');
            modalBody.classList.remove('text-danger');
        } else {
            modalBody.classList.add('text-danger');
            modalBody.classList.remove('text-success');
        }
        notificationModal.show();
    }

    <?php if (!empty($mensaje_tipo)): ?>
        showNotification('<?php echo ucfirst($mensaje_tipo); ?>', '<?php echo addslashes($mensaje_texto); ?>', '<?php echo $mensaje_tipo; ?>');
        <?php if ($mensaje_tipo === 'exito'): ?>
            setTimeout(() => { window.location.href = 'citas_actuales.php'; }, 3000);
        <?php endif; ?>
    <?php endif; ?>

    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'es',
        initialView: 'dayGridMonth',
        headerToolbar: { left: 'prev', center: 'title', right: 'next' },
        validRange: { start: new Date() },
        height: 'auto',

        dateClick: function(info) {
            const dateStr = info.dateStr;
            if (!availableDays.includes(dateStr)) return;
            $('#selected-date').val(dateStr);
            $('#fecha-modal-titulo').text(info.date.toLocaleDateString('es-ES', { dateStyle: 'full' }));
            loadHours(dateStr);
            hourModal.show();
        },

        dayCellDidMount: function(info) {
            const dateStr = info.date.toISOString().split('T')[0];
            const diaSemana = info.date.getDay();
            const noHoras = !diasConHorasDisponibles[dateStr];

            if (diaSemana === 0 || diaSemana === 6 || noHoras) {
                info.el.classList.add('fc-day-unavailable');
                info.el.style.pointerEvents = 'none';
                info.el.style.opacity = '0.5';
            }
        }
    });
    calendar.render();

    function toggleCalendarState(disabled) {
        if (disabled) {
            calendarWrapper.classList.add('disabled');
            calendarEl.style.opacity = '0.5';
            calendarEl.style.pointerEvents = 'none';
        } else {
            calendarWrapper.classList.remove('disabled');
            calendarEl.style.opacity = '1';
            calendarEl.style.pointerEvents = 'auto';
        }
    }
    toggleCalendarState(true);

    function fetchAvailableDays() {
        const medicoId = medicoSelect.value;
        if (!medicoId) {
            toggleCalendarState(true);
            availableDays = [];
            diasConHorasDisponibles = {};
            calendar.render();
            return;
        }

        toggleCalendarState(false);
        const view = calendar.view;
        const startDate = view.activeStart.toISOString().split('T')[0];
        const endDate = view.activeEnd.toISOString().split('T')[0];

        $.ajax({
            url: 'consultas_citas/dias_disponibles.php',
            type: 'GET',
            data: { medico: medicoId, start: startDate, end: endDate },
            dataType: 'json',
            success: function(data) {
                diasConHorasDisponibles = data || {};
                availableDays = Object.keys(diasConHorasDisponibles).filter(date => diasConHorasDisponibles[date]);
                calendarWrapper.classList.toggle('no-dates', availableDays.length === 0);
                calendar.render();
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar días disponibles:', error, xhr.responseText);
                showNotification('Error', 'No se pudieron cargar los días disponibles.');
                availableDays = [];
                diasConHorasDisponibles = {};
                calendarWrapper.classList.remove('no-dates');
                calendar.render();
            }
        });
    }

    function loadIps() {
        $('#ips').html('<option value="">Cargando...</option>').prop('disabled', true);
        $.ajax({
            url: 'consultas_citas/ips.php',
            type: 'POST',
            success: function(data) {
                $('#ips').html(data).prop('disabled', false);
                if ($('#ips option').length <= 1) {
                    showNotification('Error', 'No se pudieron cargar las IPS.');
                }
            },
            error: function(xhr, status, error) {
                $('#ips').html('<option value="">Error al cargar IPS</option>').prop('disabled', true);
                showNotification('Error', 'No se pudieron cargar las IPS.');
            }
        });
    }

    $(document).ready(function() {
        loadIps();
    });

    $('#ips').change(function() {
        const nit_ips = $(this).val();
        $('#medico').html('<option value="">Cargando...</option>').prop('disabled', true);
        toggleCalendarState(true);
        availableDays = [];
        diasConHorasDisponibles = {};
        calendar.render();

        if (nit_ips) {
            $.post('consultas_citas/medico.php', { nit_ips: nit_ips }, function(data) {
                $('#medico').html(data).prop('disabled', false);
            }).fail(function(xhr) {
                $('#medico').html('<option value="">Error al cargar médicos</option>').prop('disabled', true);
                showNotification('Error', 'No se pudieron cargar los médicos.');
            });
        } else {
            $('#medico').html('<option value="">Seleccione una IPS</option>');
        }
    });

    $('#medico').change(function() {
        if ($(this).val()) {
            fetchAvailableDays();
        } else {
            toggleCalendarState(true);
            availableDays = [];
            diasConHorasDisponibles = {};
            calendarWrapper.classList.remove('no-dates');
            calendar.render();
        }
    });

    function loadHours(fecha) {
        const medico = $('#medico').val();
        $('#horas-container').html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>');
        hourModal.show();
        $.ajax({
            url: 'consultas_citas/horas_disponibles.php',
            type: 'POST',
            dataType: 'json',
            data: { doc_med: medico, fecha: fecha },
            success: function(response) {
                let html = '';
                if (response.error) {
                    html = `<p class="text-danger text-center">${response.error}</p>`;
                } else if (response.hours && response.hours.length > 0) {
                    html = response.hours.map(row => 
                        `<button type="button" class="btn btn-outline-primary hour-btn mb-2" data-time="${row.horario}">${row.hora12}</button>`
                    ).join('');
                } else {
                    html = '<p class="text-muted text-center">No hay horas disponibles para este día.</p>';
                }
                $('#horas-container').html(html);
            },
            error: function(xhr, status, error) {
                $('#horas-container').html('<p class="text-danger text-center">Error al cargar las horas.</p>');
            }
        });
    }

    $(document).on('click', '.hour-btn', function() {
        $('#hora').val($(this).data('time'));
        const selectedDate = new Date($('#selected-date').val() + 'T00:00:00').toLocaleDateString('es-ES', { dateStyle: 'long' });
        
        $('#confirm-modal-body').html(
            `<p>Estás a punto de agendar una cita médica:</p>
             <ul class="list-unstyled">
                <li><strong>Fecha:</strong> ${selectedDate}</li>
                <li><strong>Hora:</strong> ${$(this).text()}</li>
             </ul>
             <p class="mt-3">Por favor, confirma para finalizar el proceso.</p>`
        );
        hourModal.hide();
        confirmModal.show();
    });

    $('#btn-confirm-agenda').on('click', function() {
        confirmModal.hide();
        const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
        loadingModal.show();
        setTimeout(() => { $('#submit-hidden').click(); }, 500);
    });
});
</script>

</body>
</html>