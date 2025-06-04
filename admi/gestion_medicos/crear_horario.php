<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once('../../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) {
    header('Location: ../inicio_sesion.php');
    exit;
}

$conex_db = new database();
$con = $conex_db->conectar();

$medicos = [];
$meridianos = [];
$php_error_message = '';
$php_success_message = '';

$selected_medico_val = $_POST['doc_medico'] ?? '';
$selected_dates_str_val = $_POST['fechas_horario'] ?? '';
$selected_meridiano_val = $_POST['id_meridiano'] ?? '';
$selected_bloques_hora_val = $_POST['bloques_hora_seleccionados'] ?? [];


if ($con) {
    try {
        $stmt_medicos = $con->prepare("SELECT doc_usu, nom_usu FROM usuarios WHERE id_rol = 4 ORDER BY nom_usu ASC");
        $stmt_medicos->execute();
        $medicos = $stmt_medicos->fetchAll(PDO::FETCH_ASSOC);

        $stmt_meridianos = $con->prepare("SELECT id_periodo, periodo FROM meridiano ORDER BY id_periodo ASC");
        $stmt_meridianos->execute();
        $meridianos = $stmt_meridianos->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $php_error_message = "<div class='alert alert-danger'>Error al cargar datos iniciales: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    $php_error_message = "<div class='alert alert-danger'>Error de conexión inicial a la base de datos.</div>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_horario'])) {
    if (!$con) {
        $conex_db_temp = new database();
        $con = $conex_db_temp->conectar();
        if (!$con) {
            $php_error_message = "<div class='alert alert-danger'>Error crítico: No se pudo reconectar a la base de datos para guardar.</div>";
        }
    }

    if ($con) {
        $doc_medico_post = $_POST['doc_medico'] ?? '';
        $fechas_horario_str_post = $_POST['fechas_horario'] ?? '';
        $id_meridiano_post = $_POST['id_meridiano'] ?? '';
        $bloques_hora_seleccionados_post = $_POST['bloques_hora_seleccionados'] ?? [];
        $id_estado_horario = 4; 

        $errores_validacion = [];

        if (empty($doc_medico_post)) { $errores_validacion[] = "Debe seleccionar un médico."; }
        else {
            $stmt_check_asignacion = $con->prepare("SELECT COUNT(*) FROM asignacion_medico WHERE doc_medico = :doc_medico AND id_estado = 1");
            $stmt_check_asignacion->bindParam(':doc_medico', $doc_medico_post, PDO::PARAM_STR);
            $stmt_check_asignacion->execute();
            if ($stmt_check_asignacion->fetchColumn() == 0) {
                $errores_validacion[] = "El médico seleccionado no está asignado a una IPS o su asignación no está activa.";
            }
        }

        if (empty($fechas_horario_str_post)) { $errores_validacion[] = "Debe seleccionar al menos una fecha."; }
        if (empty($id_meridiano_post)) { $errores_validacion[] = "Debe seleccionar un turno (AM/PM)."; }
        if (empty($bloques_hora_seleccionados_post)) { $errores_validacion[] = "Debe seleccionar al menos un bloque de hora principal."; }


        if (empty($errores_validacion)) {
            $fechas_array = explode(', ', $fechas_horario_str_post);
            
            try {
                $con->beginTransaction();
                $sql_insert = "INSERT INTO horario_medico (doc_medico, fecha_horario, horario, meridiano, id_estado) VALUES (:doc_medico, :fecha_horario, :horario, :meridiano, :id_estado)";
                $stmt_insert = $con->prepare($sql_insert);

                $horarios_creados_count = 0;
                $sub_intervalos_generados = 0;

                foreach ($fechas_array as $fecha_str) {
                    $fecha_dt = DateTime::createFromFormat('Y-m-d', trim($fecha_str));
                    if ($fecha_dt && $fecha_dt->format('Y-m-d') === trim($fecha_str)) {
                        $fecha_db_format = $fecha_dt->format('Y-m-d');

                        foreach ($bloques_hora_seleccionados_post as $hora_principal_str) {
                            $hora_principal = intval($hora_principal_str);
                            
                            for ($i = 0; $i < 3; $i++) { 
                                $minutos_adicionales = $i * 20;
                                $hora_actual_obj = new DateTime();
                                $hora_actual_obj->setTime($hora_principal, 0, 0);
                                $hora_actual_obj->add(new DateInterval('PT' . $minutos_adicionales . 'M'));
                                $hora_formateada_db = $hora_actual_obj->format('H:i:s');

                                $stmt_check_duplicado = $con->prepare("SELECT COUNT(*) FROM horario_medico WHERE doc_medico = :doc_medico AND fecha_horario = :fecha_horario AND horario = :horario AND meridiano = :meridiano");
                                $stmt_check_duplicado->execute([
                                    ':doc_medico' => $doc_medico_post,
                                    ':fecha_horario' => $fecha_db_format,
                                    ':horario' => $hora_formateada_db,
                                    ':meridiano' => $id_meridiano_post
                                ]);

                                if ($stmt_check_duplicado->fetchColumn() == 0) {
                                    $stmt_insert->execute([
                                        ':doc_medico' => $doc_medico_post,
                                        ':fecha_horario' => $fecha_db_format,
                                        ':horario' => $hora_formateada_db,
                                        ':meridiano' => $id_meridiano_post,
                                        ':id_estado' => $id_estado_horario
                                    ]);
                                    $sub_intervalos_generados++;
                                }
                            }
                        }
                        if ($sub_intervalos_generados > 0 && $horarios_creados_count == 0) $horarios_creados_count =1;
                    } else {
                        $errores_validacion[] = "Formato de fecha inválido: " . htmlspecialchars($fecha_str);
                    }
                }

                if (!empty($errores_validacion) && $sub_intervalos_generados == 0) { 
                     if ($con->inTransaction()) { $con->rollBack(); }
                } else {
                    if ($sub_intervalos_generados > 0) {
                         $con->commit();
                         $php_success_message = "<div class='alert alert-success'>Se han creado " . $sub_intervalos_generados . " intervalo(s) de horario exitosamente.</div>";
                         $selected_medico_val = ''; $selected_dates_str_val = ''; $selected_meridiano_val = ''; $selected_bloques_hora_val = [];
                    } else {
                         if ($con->inTransaction()) { $con->rollBack(); }
                         $php_error_message = "<div class='alert alert-warning'>No se crearon nuevos horarios. Es posible que todos los seleccionados ya existieran o hubo un problema con los datos.</div>";
                         if(!empty($errores_validacion)){ 
                             $existing_error = $php_error_message;
                             $php_error_message = "<div class='alert alert-danger'><strong>Errores de validación:</strong><ul>";
                             foreach ($errores_validacion as $error) { $php_error_message .= "<li>" . htmlspecialchars($error) . "</li>"; }
                             $php_error_message .= "</ul></div>" . $existing_error;
                         }
                    }
                }

            } catch (PDOException $e) {
                if ($con->inTransaction()) { $con->rollBack(); }
                $php_error_message = "<div class='alert alert-danger'>Error PDO al guardar horario: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }

        if (!empty($errores_validacion) && $sub_intervalos_generados == 0) { 
            $existing_error_msg = $php_error_message; 
            $php_error_message = "<div class='alert alert-danger'><strong>Por favor corrija los siguientes errores:</strong><ul>";
            foreach ($errores_validacion as $error) {
                $php_error_message .= "<li>" . htmlspecialchars($error) . "</li>";
            }
            $php_error_message .= "</ul></div>";
            if (strpos($existing_error_msg, 'alert-warning') !== false) $php_error_message .= $existing_error_msg;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="../img/loguito.png">
    <title>Crear Horario Médico - Administración</title>
    <style>
        #mensaje_medico_error { display: none; color: red; font-size: 0.875em; margin-top: .25rem; }
        #contenedor_bloques_hora .form-check-inline { margin-right: .8rem; margin-bottom: .5rem;}

        .flatpickr-day.selected,
        .flatpickr-day.startRange,
        .flatpickr-day.endRange,
        .flatpickr-day.selected.inRange,
        .flatpickr-day.startRange.inRange,
        .flatpickr-day.endRange.inRange,
        .flatpickr-day.selected:focus,
        .flatpickr-day.startRange:focus,
        .flatpickr-day.endRange:focus,
        .flatpickr-day.selected:hover,
        .flatpickr-day.startRange:hover,
        .flatpickr-day.endRange:hover,
        .flatpickr-day.selected.prevMonthDay,
        .flatpickr-day.startRange.prevMonthDay,
        .flatpickr-day.endRange.prevMonthDay,
        .flatpickr-day.selected.nextMonthDay,
        .flatpickr-day.startRange.nextMonthDay,
        .flatpickr-day.endRange.nextMonthDay {
            background: #0d6efd; 
            border-color: #0d6efd;
            color: #fff; 
            border-radius: 50%; 
            box-shadow: none; 
        }

        .flatpickr-day {
            max-width: 2.4rem; 
            line-height: 2.4rem; 
            height: 2.4rem;
            padding: 0; 
            display: inline-flex; 
            align-items: center;
            justify-content: center;
            font-weight: normal;
        }

        .flatpickr-day.today {
            border-color: #0d6efd; 
            color: #0d6efd;
        }
        .flatpickr-day.today:hover:not(.selected) {
            background: #e9ecef;
        }

        .flatpickr-day.today.selected {
            background: #0b5ed7; 
            border-color: #0a58ca;
            color: #fff;
        }
        .flatpickr-day.disabled, .flatpickr-day.disabled:hover {
            color: #adb5bd;
            background: transparent !important;
            border-color: #dee2e6 !important;
            cursor: default;
        }
        .flatpickr-calendar {
            width: auto !important; 
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
        }
        .dayContainer{
            padding: 0.25rem;
            justify-content: space-around;
        }

    </style>
</head>
<body>
    <?php include '../include/menu.php'; ?>
    <main id="contenido-principal" class="d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="form-container mx-auto d-flex flex-column flex-grow-1 p-4 rounded shadow-sm bg-light">
                <h3 class="text-center mb-4">Crear Horario Médico</h3>
                <?php
                    if (!empty($php_error_message)) { echo $php_error_message; }
                    if (!empty($php_success_message)) { echo $php_success_message; }
                ?>
                <form id="formCrearHorario" action="crear_horario.php" method="POST" novalidate class="d-flex flex-column flex-grow-1">
                    
                    <div class="mb-3">
                        <label for="doc_medico" class="form-label">Seleccione Médico <span class="text-danger">(*)</span>:</label>
                        <select id="doc_medico" name="doc_medico" class="form-select" required>
                            <option value="">-- Seleccione un médico --</option>
                            <?php foreach ($medicos as $medico) : ?>
                                <option value="<?php echo htmlspecialchars($medico['doc_usu']); ?>" <?php echo ($selected_medico_val == $medico['doc_usu']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($medico['nom_usu']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="mensaje_medico_error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="fechas_horario_input" class="form-label">Seleccione las fechas para el horario <span class="text-danger">(*)</span>:</label>
                        <input type="text" id="fechas_horario_input" name="fechas_horario" class="form-control" placeholder="Haga clic para seleccionar fechas" required value="<?php echo htmlspecialchars($selected_dates_str_val); ?>" disabled readonly>
                        <small class="form-text text-muted">Puede seleccionar múltiples fechas de la semana actual. Habilite después de seleccionar un médico asignado.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="id_meridiano" class="form-label">Seleccione Turno (AM/PM) <span class="text-danger">(*)</span>:</label>
                        <select id="id_meridiano" name="id_meridiano" class="form-select" required disabled>
                            <option value="">-- Seleccione Turno --</option>
                            <?php foreach ($meridianos as $meridiano) : ?>
                                <option value="<?php echo htmlspecialchars($meridiano['id_periodo']); ?>" <?php echo ($selected_meridiano_val == $meridiano['id_periodo']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($meridiano['periodo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                         <small class="form-text text-muted">Habilite después de seleccionar un médico asignado.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Bloques de Hora a Trabajar <span class="text-danger">(*)</span>:</label>
                        <div id="contenedor_bloques_hora" class="p-2 border rounded bg-white" style="min-height: 50px;">
                            <small class="form-text text-muted">Seleccione un médico, fechas y un turno para ver los bloques de hora.</small>
                            <?php
                            if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($selected_meridiano_val) && !empty($selected_bloques_hora_val)) {
                                $horas_disponibles_repop = [];
                                if ($selected_meridiano_val == '1') $horas_disponibles_repop = [7, 8, 9, 10, 11];
                                else if ($selected_meridiano_val == '2') $horas_disponibles_repop = [12, 13, 14, 15, 16, 17];

                                if (!empty($horas_disponibles_repop)) {
                                    echo '<p class="mb-2 fw-medium">Seleccione los bloques de hora de inicio para trabajar:</p>';
                                    echo '<div class="row g-2">';
                                    foreach ($horas_disponibles_repop as $hora) {
                                        $checked_attr = in_array((string)$hora, $selected_bloques_hora_val, true) ? 'checked' : '';
                                        echo '<div class="col-auto"><div class="form-check form-check-inline">';
                                        echo '<input type="checkbox" class="form-check-input" name="bloques_hora_seleccionados[]" value="' . $hora . '" id="hora_bloque_' . $hora . '" ' . $checked_attr . '>';
                                        echo '<label class="form-check-label" for="hora_bloque_' . $hora . '">' . str_pad($hora, 2, "0", STR_PAD_LEFT) . ':00</label>';
                                        echo '</div></div>';
                                    }
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <div class="mt-auto pt-3">
                        <button type="submit" name="guardar_horario" class="btn btn-success w-100" disabled>
                            Guardar Horario <i class="bi bi-calendar-plus"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include '../../include/footer.php'; ?>
    <script src="../js/crear_horario_admin.js"></script> 
</body>
</html>