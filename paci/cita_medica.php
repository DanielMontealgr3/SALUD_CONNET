<?php
// =================================================================================================
// BLOQUE 1: CONFIGURACIÓN Y REQUISITOS PRINCIPALES
// Se incluye el archivo de configuración central que define ROOT_PATH, BASE_URL, inicia
// la sesión de forma segura y establece la conexión a la base de datos ($con).
// Es la primera y más importante inclusión del script.
// =================================================================================================
require_once __DIR__ . '/../include/config.php';

// Se incluyen los scripts para validar que el usuario haya iniciado sesión y para
// manejar el cierre de sesión por inactividad. Se utilizan rutas absolutas con ROOT_PATH.
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

// Se incluyen los archivos necesarios de la librería PHPMailer para el envío de correos.
require_once ROOT_PATH . '/include/PHPMailer/PHPMailer.php';
require_once ROOT_PATH . '/include/PHPMailer/SMTP.php';
require_once ROOT_PATH . '/include/PHPMailer/Exception.php';

// Se importa el namespace de PHPMailer para poder usar sus clases directamente.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// =================================================================================================
// BLOQUE 2: INICIALIZACIÓN DE VARIABLES Y CONFIGURACIÓN DEL ENTORNO
// =================================================================================================
// Establece el idioma para las fechas en español.
setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'esp');

// Inicializa variables que se usarán para mostrar mensajes de notificación al usuario.
$mensaje_tipo = '';
$mensaje_texto = '';

// Credenciales para el envío de correo. NOTA: Por seguridad, estas credenciales
// deberían moverse a un archivo de configuración no versionado (fuera de Git).
$nombresitio = "Salud Connected";
$email_soporte = 'saludconneted@gmail.com';
$email_password = 'czlr pxjh jxeu vzsz';

// =================================================================================================
// BLOQUE 3: CONTROL DE ACCESO Y SEGURIDAD
// Verifica si el usuario ha iniciado sesión, si tiene un rol asignado y si ese rol
// corresponde al de un paciente (ID de rol 2). Si no cumple las condiciones,
// se le redirige a la página de inicio de sesión. La redirección usa BASE_URL.
// =================================================================================================
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2) {
    header('Location: ' . BASE_URL . '/inicio_sesion.php');
    exit;
}

// Se obtiene el documento del usuario que ha iniciado sesión para usarlo en las consultas.
$doc_usuario = $_SESSION['doc_usu'];

// =================================================================================================
// BLOQUE 4: PROCESAMIENTO DEL FORMULARIO (LÓGICA DE NEGOCIO)
// Este bloque se ejecuta solo si se ha enviado el formulario (método POST).
// =================================================================================================
if (isset($_POST['enviar'])) {
    // Primero, verifica si el paciente ya tiene una cita activa o pendiente para evitar duplicados.
    $estados_activos = [1, 3, 16]; // Estados de cita que impiden agendar una nueva.
    $in_placeholders = implode(',', array_fill(0, count($estados_activos), '?'));
    
    $verificar_cita = $con->prepare("SELECT id_cita FROM citas WHERE doc_pac = ? AND id_est IN ($in_placeholders)");
    $params_verif = array_merge([$doc_usuario], $estados_activos);
    $verificar_cita->execute($params_verif);

    if ($verificar_cita->rowCount() > 0) {
        // Si ya tiene una cita, se prepara un mensaje de advertencia.
        $mensaje_tipo = 'advertencia';
        $mensaje_texto = 'Ya tienes una cita activa o próxima. Debes esperar a que finalice o cancelarla para agendar una nueva.';
    } else {
        // Si no tiene citas activas, se procesan los datos del formulario.
        $fecha = $_POST['fecha'] ?? '';
        $hora_24h = $_POST['hora'] ?? '';
        $nit_ips = $_POST['ips'] ?? '';
        $doc_medico = $_POST['medico'] ?? '';
        $fecha_solicitud = date('Y-m-d');

        if (empty($fecha) || empty($hora_24h) || empty($nit_ips) || empty($doc_medico)) {
            // Valida que no haya campos vacíos.
            $mensaje_tipo = 'error';
            $mensaje_texto = 'Existen datos vacíos. Por favor, complete todos los campos.';
        } else {
            // Se inicia una transacción para asegurar la integridad de los datos.
            // O se guarda todo, o no se guarda nada.
            $con->beginTransaction();
            try {
                // Busca el horario específico seleccionado para asegurarse de que sigue disponible (estado 4).
                $estado_disponible = 4;
                $sql_horario = "SELECT id_horario_med FROM horario_medico WHERE doc_medico = :doc_medico AND fecha_horario = :fecha AND TIME(horario) = :hora AND id_estado = :estado_disponible FOR UPDATE";
                $stmt_horario = $con->prepare($sql_horario);
                $stmt_horario->execute([':doc_medico' => $doc_medico, ':fecha' => $fecha, ':hora' => $hora_24h, ':estado_disponible' => $estado_disponible]);
                $id_horario_med = $stmt_horario->fetchColumn();

                if (!$id_horario_med) {
                    // Si el horario ya fue tomado por otro usuario, se lanza una excepción.
                    throw new Exception('La hora seleccionada ya no está disponible. Por favor, elija otra.');
                }
                
                // Si el horario está disponible, se actualiza su estado a 'Asignado' (id_est = 3).
                $estado_asignado = 3;
                $con->prepare("UPDATE horario_medico SET id_estado = :estado WHERE id_horario_med = :id_horario")->execute([':estado' => $estado_asignado, ':id_horario' => $id_horario_med]);
                
                // Se inserta la nueva cita en la tabla 'citas', también con estado 'Programada' (id_est = 3).
                $con->prepare("INSERT INTO citas (doc_pac, doc_med, nit_IPS, fecha_solici, id_horario_med, id_est) VALUES (:doc_pac, :doc_med, :nit_ips, :fecha_solici, :id_horario_med, :id_est)")->execute([':doc_pac' => $doc_usuario, ':doc_med' => $doc_medico, ':nit_ips' => $nit_ips, ':fecha_solici' => $fecha_solicitud, ':id_horario_med' => $id_horario_med, ':id_est' => $estado_asignado]);
                
                // Si todas las operaciones fueron exitosas, se confirma la transacción.
                $con->commit();
                
                // Se prepara un mensaje de éxito.
                $mensaje_tipo = 'exito';
                $mensaje_texto = '¡Cita médica agendada correctamente! Serás redirigido a "Mis Citas" en unos segundos.';
                
            } catch (Exception $e) {
                // Si ocurre cualquier error, se revierte la transacción.
                $con->rollBack();
                error_log("Error en transacción de cita_medica.php: " . $e->getMessage()); // Guarda el error real para depuración.
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
    <!-- =================================================================================================
    BLOQUE 5: CABECERA HTML (METADATOS, TÍTULO Y HOJAS DE ESTILO)
    ================================================================================================= -->
    <meta charset="UTF-8">
    <title>Agendar Cita Médica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/paci/styles.css"> 
    
    <style>
        /* Estilos CSS específicos para esta página, como la apariencia del calendario. */
        .fc-day-unavailable { 
            background-color: #f5f5f5 !important; 
            cursor: not-allowed;
        }
        .fc-day-available .fc-daygrid-day-frame {
            background-color: #e7f5ff; /* Azul claro para días disponibles */
            cursor: pointer;
            transition: background-color 0.2s;
            position: relative; /* Necesario para posicionar el contador */
        }
        .fc-day-available .fc-daygrid-day-frame:hover {
            background-color: #cce5ff;
        }
        /* NUEVO ESTILO PARA EL CONTADOR DE DISPONIBILIDAD */
        .availability-count {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background-color: #0d6efd;
            color: white;
            font-size: 0.75em;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 8px;
            line-height: 1;
        }

        #calendar-wrapper.disabled { opacity: 0.5; pointer-events: none; }
        #no-dates-overlay { display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); color: white; display: flex; align-items: center; justify-content: center; z-index: 10; font-size: 1.2rem; }
        #calendar-wrapper.no-dates #no-dates-overlay { display: flex; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    
    <?php
    // =================================================================================================
    // BLOQUE 6: INCLUSIÓN DE LA PLANTILLA DEL MENÚ
    // =================================================================================================
    require_once ROOT_PATH . '/include/menu.php';
    ?>

    <main class="container py-4">
        <!-- =================================================================================================
        BLOQUE 7: CONTENIDO PRINCIPAL Y FORMULARIO
        ================================================================================================= -->
        <div class="card p-4 shadow-sm">
            <h2 class="mb-4 text-center" style="color: #004a99;">Agendar Cita Médica</h2>
            <form id="form-cita" method="POST" action="">
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
                        <div id="calendar-wrapper" class="position-relative">
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

        <!-- =================================================================================================
        BLOQUE 8: VENTANAS MODALES
        ================================================================================================= -->
        <div class="modal fade" id="hourModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title" id="fecha-modal-titulo"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body"><div id="horas-container" class="d-flex flex-wrap gap-2 justify-content-center"></div></div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="confirmModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Confirmar Cita</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body" id="confirm-modal-body"></div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="btn-confirm-agenda">Confirmar</button></div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="notificationModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title" id="notification-modal-title"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body" id="notification-modal-body"></div>
                    <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Aceptar</button></div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div><p class="mt-2">Procesando solicitud...</p></div>
                </div>
            </div>
        </div>
    </main>

    <?php
    // =================================================================================================
    // BLOQUE 9: INCLUSIÓN DE LA PLANTILLA DEL PIE DE PÁGINA
    // =================================================================================================
    require_once ROOT_PATH . '/include/footer.php';
    ?>

    <!-- =================================================================================================
    BLOQUE 10: SCRIPTS DE JAVASCRIPT
    ================================================================================================= -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js'></script>
    <script src="<?php echo BASE_URL; ?>/paci/js/form-submission.js"></script>

    <script>
    // =================================================================================================
    // BLOQUE 11: LÓGICA DE JAVASCRIPT PERSONALIZADA (MODIFICADA)
    // =================================================================================================
    
    const BASE_URL = '<?php echo BASE_URL; ?>';

    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        const calendarWrapper = document.getElementById('calendar-wrapper');
        const medicoSelect = document.getElementById('medico');
        const hourModal = new bootstrap.Modal(document.getElementById('hourModal'));
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        const notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
        
        let diasConteoDisponibles = {}; // Almacenará { fecha: conteo }

        function showNotification(title, message, type = 'advertencia') {
            const modalTitle = document.getElementById('notification-modal-title');
            const modalBody = document.getElementById('notification-modal-body');
            modalTitle.textContent = title;
            modalBody.innerHTML = message;
            modalBody.classList.toggle('text-success', type === 'exito');
            modalBody.classList.toggle('text-danger', type !== 'exito');
            notificationModal.show();
        }

        <?php if (!empty($mensaje_tipo)): ?>
            showNotification('<?php echo ucfirst($mensaje_tipo); ?>', '<?php echo addslashes($mensaje_texto); ?>', '<?php echo $mensaje_tipo; ?>');
            <?php if ($mensaje_tipo === 'exito'): ?>
                setTimeout(() => { window.location.href = `${BASE_URL}/paci/citas_actuales.php`; }, 3000);
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
                // Solo permite clic si el día tiene citas disponibles
                if (diasConteoDisponibles[dateStr] && diasConteoDisponibles[dateStr] > 0) {
                    $('#selected-date').val(dateStr);
                    $('#fecha-modal-titulo').text(info.date.toLocaleDateString('es-ES', { dateStyle: 'full' }));
                    loadHours(dateStr);
                }
            },

            // --- FUNCIÓN MODIFICADA PARA MOSTRAR LA DISPONIBILIDAD ---
            dayCellDidMount: function(info) {
                const dateStr = info.date.toISOString().split('T')[0];
                const dayEl = info.el;
                const frameEl = dayEl.querySelector('.fc-daygrid-day-frame');

                // Limpiar clases y contenido previo
                dayEl.classList.remove('fc-day-available', 'fc-day-unavailable');
                const existingCount = frameEl.querySelector('.availability-count');
                if (existingCount) existingCount.remove();

                const conteo = diasConteoDisponibles[dateStr] || 0;

                if (conteo > 0) {
                    dayEl.classList.add('fc-day-available');
                    // Crear el elemento para el contador
                    const countEl = document.createElement('div');
                    countEl.classList.add('availability-count');
                    countEl.innerText = conteo;
                    frameEl.appendChild(countEl);
                } else {
                    dayEl.classList.add('fc-day-unavailable');
                }
            },
            
            // Recargar los datos cuando cambia el mes en el calendario
            datesSet: function() {
                if (medicoSelect.value) {
                    fetchAvailableDays();
                }
            }
        });
        calendar.render();

        function toggleCalendarState(disabled) {
            calendarWrapper.classList.toggle('disabled', disabled);
        }
        toggleCalendarState(true);

        function fetchAvailableDays() {
            const medicoId = medicoSelect.value;
            if (!medicoId) {
                toggleCalendarState(true);
                diasConteoDisponibles = {}; // Limpiar datos si no hay médico
                calendar.render(); // Re-renderizar para limpiar el calendario
                return;
            }
            toggleCalendarState(false);
            const view = calendar.view;
            const startDate = view.activeStart.toISOString().split('T')[0];
            const endDate = view.activeEnd.toISOString().split('T')[0];

            $.ajax({
                url: `${BASE_URL}/paci/consultas_citas/dias_disponibles.php`,
                type: 'GET',
                data: { medico: medicoId, start: startDate, end: endDate },
                dataType: 'json',
                success: function(data) {
                    // Ahora esperamos un objeto { counts: {...} }
                    diasConteoDisponibles = data.counts || {};
                    const hasDates = Object.keys(diasConteoDisponibles).length > 0;
                    calendarWrapper.classList.toggle('no-dates', !hasDates);
                    calendar.render(); // Re-renderizar el calendario para aplicar los nuevos datos
                },
                error: function() {
                    showNotification('Error', 'No se pudo cargar la disponibilidad del médico.');
                    diasConteoDisponibles = {};
                    calendarWrapper.classList.add('no-dates');
                    calendar.render();
                }
            });
        }

        function loadIps() {
            $('#ips').html('<option value="">Cargando...</option>').prop('disabled', true);
            $.ajax({
                url: `${BASE_URL}/paci/consultas_citas/ips.php`,
                type: 'POST',
                success: function(data) { $('#ips').html(data).prop('disabled', false); }
            });
        }

        $(document).ready(function() { loadIps(); });

        $('#ips').change(function() {
            const nit_ips = $(this).val();
            $('#medico').html('<option value="">Cargando...</option>').prop('disabled', true);
            toggleCalendarState(true);
            diasConteoDisponibles = {}; // Limpiar datos al cambiar de IPS
            calendar.render(); // Limpiar visualmente el calendario
            if (nit_ips) {
                $.post(`${BASE_URL}/paci/consultas_citas/medico.php`, { nit_ips: nit_ips }, function(data) {
                    $('#medico').html(data).prop('disabled', false);
                });
            } else {
                $('#medico').html('<option value="">Seleccione una IPS</option>');
            }
        });

        $('#medico').change(function() {
            fetchAvailableDays(); // Carga los días cuando se selecciona un médico
        });

        function loadHours(fecha) {
            const medico = $('#medico').val();
            $('#horas-container').html('<div class="spinner-border text-primary" role="status"></div>');
            hourModal.show();
            $.ajax({
                url: `${BASE_URL}/paci/consultas_citas/horas_disponibles.php`,
                type: 'POST', // Asegurarse de que el método coincida con el backend
                data: { doc_med: medico, fecha: fecha },
                dataType: 'json',
                success: function(response) {
                    let html = '';
                    if (response.error) {
                        html = `<p class="text-danger">${response.error}</p>`;
                    } else if (response.hours && response.hours.length > 0) {
                        html = response.hours.map(row => `<button type="button" class="btn btn-outline-primary hour-btn" data-time="${row.horario}">${row.hora12}</button>`).join('');
                    } else {
                        html = '<p class="text-muted">No hay horas disponibles para este día.</p>';
                    }
                    $('#horas-container').html(html);
                }
            });
        }

        $(document).on('click', '.hour-btn', function() {
            $('#hora').val($(this).data('time'));
            const selectedDate = new Date($('#selected-date').val() + 'T00:00:00Z').toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            $('#confirm-modal-body').html(
                `<p>Estás a punto de agendar una cita para el:</p>
                 <ul class="list-unstyled">
                    <li><strong>Día:</strong> ${selectedDate}</li>
                    <li><strong>Hora:</strong> ${$(this).text()}</li>
                 </ul>
                 <p class="mt-3">¿Deseas confirmar?</p>`
            );
            hourModal.hide();
            confirmModal.show();
        });

        $('#btn-confirm-agenda').on('click', function() {
            confirmModal.hide();
            const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
            loadingModal.show();
            setTimeout(() => { $('#form-cita').submit(); }, 500);
        });
    });
    </script>
</body>
</html>