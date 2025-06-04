<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once '../include/conexion.php';

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$doc_farmaceuta_logueado = $_SESSION['doc_usu'] ?? null;

if (
    !isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true ||
    !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 3 ||
    !$doc_farmaceuta_logueado
) {
    header('Location: ../inicio_sesion.php?error=nosession');
    exit;
}

$asignacion_activa_farma = false;
$nit_farmacia_asignada_actual = null;
if (isset($con) && $con instanceof PDO) {
    $stmt_check_farma = $con->prepare("SELECT nit_farma FROM asignacion_farmaceuta WHERE doc_farma = :doc_farma AND id_estado = 1 LIMIT 1");
    $stmt_check_farma->bindParam(':doc_farma', $doc_farmaceuta_logueado, PDO::PARAM_STR);
    $stmt_check_farma->execute();
    $farma_asignada = $stmt_check_farma->fetch(PDO::FETCH_ASSOC);
    if ($farma_asignada) {
        $asignacion_activa_farma = true;
        $nit_farmacia_asignada_actual = $farma_asignada['nit_farma'];
    }
}

if (!$asignacion_activa_farma || !$nit_farmacia_asignada_actual) {
    header('Location: inicio.php');
    exit;
}

if (empty($_SESSION['csrf_token_farma_lista'])) {
    $_SESSION['csrf_token_farma_lista'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_farma_lista'];

$pageTitle = "Lista de Pacientes para Entrega";
$error_db = '';
$pacientes_pendientes = [];

$filtro_hora_id = $_GET['filtro_hora'] ?? '';
$filtro_paciente = trim($_GET['filtro_paciente'] ?? '');

$horarios_disponibles = [];
if (isset($con) && $con instanceof PDO) {
    try {
        $stmt_horarios = $con->prepare("SELECT hf.id_horario_farm, hf.horario, m.periodo AS meridiano_nombre 
                                        FROM horario_farm hf 
                                        LEFT JOIN meridiano m ON hf.meridiano = m.id_periodo
                                        WHERE hf.id_estado = 4 ORDER BY hf.horario ASC");
        $stmt_horarios->execute();
        $horarios_disponibles = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obteniendo horarios para filtro: " . $e->getMessage());
    }
}

if (isset($con) && $con instanceof PDO) {
    try {
        $sql_base = "SELECT
                        tem.id_turno_ent,
                        tem.fecha_entreg,
                        tem.id_historia,
                        u.doc_usu AS documento_paciente,
                        u.nom_usu AS nombre_paciente,
                        med.nom_medicamento,
                        dhc.can_medica,
                        hf.horario AS hora_programada_time,
                        mer.periodo AS meridiano_str,
                        vt_info.id_farmaceuta AS farmaceuta_atendiendo, /* <-- Columna clave añadida */
                        CASE WHEN vt_info.id_turno IS NOT NULL THEN 1 ELSE 0 END AS en_vista_televisor /* <-- Indicador si está en TV */
                    FROM
                        turno_ent_medic tem
                    JOIN
                        historia_clinica hc ON tem.id_historia = hc.id_historia
                    JOIN
                        citas ci ON hc.id_cita = ci.id_cita
                    JOIN
                        usuarios u ON ci.doc_pac = u.doc_usu
                    JOIN
                        afiliados afi ON u.doc_usu = afi.doc_afiliado
                    JOIN 
                        detalle_eps_farm def ON afi.id_eps = def.nit_eps
                    JOIN
                        detalles_histo_clini dhc ON tem.id_historia = dhc.id_historia 
                    JOIN
                        medicamentos med ON dhc.id_medicam = med.id_medicamento
                    JOIN
                        horario_farm hf ON tem.hora_entreg = hf.id_horario_farm
                    LEFT JOIN
                        meridiano mer ON hf.meridiano = mer.id_periodo
                    LEFT JOIN 
                        vista_televisor vt_info ON tem.id_turno_ent = vt_info.id_turno AND vt_info.nit_farma = :nit_farma_for_vt_join
                    WHERE
                        tem.id_est = 3
                        AND tem.fecha_entreg = CURDATE()
                        AND def.nit_farm= :nit_farma_for_def_join
                        AND def.id_estado = 1 ";

        $params_sql = [
            ':nit_farma_for_vt_join' => $nit_farmacia_asignada_actual,
            ':nit_farma_for_def_join' => $nit_farmacia_asignada_actual
        ];
        
        $sql_conditions_array = [];
        if (!empty($filtro_hora_id)) {
            $sql_conditions_array[] = "hf.id_horario_farm = :id_horario_filtro";
            $params_sql[':id_horario_filtro'] = $filtro_hora_id;
        }
        if (!empty($filtro_paciente)) {
            $sql_conditions_array[] = "(u.doc_usu LIKE :filtro_paciente OR u.nom_usu LIKE :filtro_paciente)";
            $params_sql[':filtro_paciente'] = "%" . $filtro_paciente . "%";
        }

        if (!empty($sql_conditions_array)) {
            $sql_base .= " AND " . implode(" AND ", $sql_conditions_array);
        }

        $sql_base .= " GROUP BY tem.id_turno_ent, tem.fecha_entreg, tem.id_historia, u.doc_usu, u.nom_usu, med.nom_medicamento, dhc.can_medica, hf.horario, mer.periodo, vt_info.id_farmaceuta, vt_info.id_turno
                       ORDER BY hf.horario ASC, u.nom_usu ASC";

        $stmt_pacientes = $con->prepare($sql_base);
        $stmt_pacientes->execute($params_sql);
        $pacientes_pendientes = $stmt_pacientes->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error_db = "Error al consultar pacientes: " . $e->getMessage();
        error_log("PDO Lista Pacientes Farma: " . $e->getMessage());
    }
} else {
    $error_db = "Error de conexión a la base de datos.";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php include '../include/menu.php'; ?>

    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <h3 class="titulo-lista-tabla"><?php echo htmlspecialchars($pageTitle); ?></h3>

                <?php if (isset($_SESSION['mensaje_accion_farma'])): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['mensaje_accion_farma_tipo']); ?> alert-dismissible fade show" role="alert" id="alerta-accion">
                        <?php echo htmlspecialchars($_SESSION['mensaje_accion_farma']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['mensaje_accion_farma']); unset($_SESSION['mensaje_accion_farma_tipo']); ?>
                <?php endif; ?>
                <?php if (!empty($error_db)) : ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_db); ?></div><?php endif; ?>

                <form method="GET" action="lista_pacientes.php" class="mb-4 filtros-tabla-container">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="filtro_hora" class="form-label">Filtrar por Hora Programada:</label>
                            <select id="filtro_hora" name="filtro_hora" class="form-select form-select-sm">
                                <option value="">-- Todas las Horas --</option>
                                <?php foreach ($horarios_disponibles as $h): ?>
                                    <?php
                                        $hora_formateada_opcion = date("h:i", strtotime($h['horario'])) . " " . ($h['meridiano_nombre'] ?? '');
                                    ?>
                                    <option value="<?php echo htmlspecialchars($h['id_horario_farm']); ?>" <?php echo ($filtro_hora_id == $h['id_horario_farm']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($hora_formateada_opcion); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="filtro_paciente_input" class="form-label">Buscar por Paciente (Doc/Nombre):</label>
                            <input type="text" name="filtro_paciente" id="filtro_paciente_input" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtro_paciente); ?>" placeholder="Documento o nombre...">
                        </div>
                        <div class="col-md-2">
                             <button type="submit" class="btn btn-sm btn-filtrar-tabla w-100">Filtrar <i class="bi bi-search"></i></button>
                        </div>
                         <div class="col-md-2">
                            <a href="lista_pacientes.php" class="btn btn-sm btn-outline-secondary w-100">Limpiar Filtros</a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="tabla-admin-mejorada tabla-lista-pacientes">
                        <thead>
                            <tr>
                                <th>Documento Pac.</th>
                                <th>Nombre Pac.</th>
                                <th>Medicamento</th>
                                <th>Cantidad</th>
                                <th>Hora Programada</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pacientes_pendientes)) : ?>
                                <?php foreach ($pacientes_pendientes as $paciente) : ?>
                                    <?php
                                        $hora_formateada = date("h:i", strtotime($paciente['hora_programada_time'])) . " " . ($paciente['meridiano_str'] ?? '');
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($paciente['documento_paciente']); ?></td>
                                        <td><?php echo htmlspecialchars($paciente['nombre_paciente']); ?></td>
                                        <td><?php echo htmlspecialchars($paciente['nom_medicamento']); ?></td>
                                        <td><?php echo htmlspecialchars($paciente['can_medica']); ?></td>
                                        <td><?php echo htmlspecialchars($hora_formateada); ?></td>
                                        <td class="acciones-tabla">
                                            <?php if ($paciente['en_vista_televisor'] == 1) : ?>
                                                <?php if ($paciente['farmaceuta_atendiendo'] == $doc_farmaceuta_logueado) : ?>
                                                    <button class="btn btn-info btn-sm btn-entregar-medicamentos"
                                                            data-idturno="<?php echo htmlspecialchars($paciente['id_turno_ent']); ?>"
                                                            data-docpaciente="<?php echo htmlspecialchars($paciente['documento_paciente']); ?>"
                                                            title="Entregar Medicamentos al Paciente">
                                                        <i class="bi bi-box-seam-fill"></i> Entregar
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-success btn-sm" disabled>
                                                        <i class="bi bi-person-gear"></i> En Proceso
                                                    </button>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <button class="btn btn-primary btn-sm btn-llamar-paciente"
                                                        data-idturno="<?php echo htmlspecialchars($paciente['id_turno_ent']); ?>"
                                                        data-docpaciente="<?php echo htmlspecialchars($paciente['documento_paciente']); ?>"
                                                        title="Llamar Paciente">
                                                    <i class="bi bi-megaphone-fill"></i> Llamar
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                 <tr><td colspan="6" class="text-center">
                                    No hay pacientes pendientes de entrega que coincidan con los filtros para esta farmacia.
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <?php include '../include/footer.php'; ?>
    <script> const csrfTokenListaPacientesGlobal = '<?php echo $csrf_token; ?>'; </script>
    <script src="../js/farma_lista_pacientes.js?v=<?php echo time(); ?>"></script>
</body>
</html>