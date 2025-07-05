<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php'; // Added this line as it was missing from your snippet but present in previous versions
require_once('../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 4 || !isset($_SESSION['nombre_usuario'])) {
    header('Location: ../inicio_sesion.php');
    exit;
}

// --- STATUS IDs from your 'estado' table (based on screenshot) ---
define('ID_ESTADO_ASIGNADA', 3);      // This is "Activa" for doctor's actions
define('ID_ESTADO_REALIZADA', 5);
define('ID_ESTADO_NO_REALIZADA', 6);  // Explicit "No Realizada" by doctor
define('ID_ESTADO_CANCELADA', 7);     // Cancelled (could be by admin, patient, or doctor)
define('ID_ESTADO_NO_ASISTIO', 8);    // Patient did not show up

$nombre_usuario = $_SESSION['nombre_usuario'];

// !!!!! MOVED DATABASE CONNECTION UP !!!!!
$db = new Database();
$pdo = $db->conectar();
// !!!!! END OF MOVE !!!!!


// --- HANDLE STATE CHANGE ACTION (POST request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cambiar_estado') {
    $id_cita_cambiar = isset($_POST['id_cita_cambiar']) ? (int)$_POST['id_cita_cambiar'] : 0;
    $nuevo_id_est = isset($_POST['nuevo_id_est']) ? (int)$_POST['nuevo_id_est'] : 0;

    // States a doctor can change an "Asignada" appointment to:
    $estados_permitidos_desde_asignada = [
        ID_ESTADO_REALIZADA,
        ID_ESTADO_NO_REALIZADA,
        ID_ESTADO_NO_ASISTIO,
        ID_ESTADO_CANCELADA // Allowing doctor to also mark as Cancelled
    ];

    if ($id_cita_cambiar > 0 && in_array($nuevo_id_est, $estados_permitidos_desde_asignada)) {
        try {
            // $pdo is now available here
            $stmt_check = $pdo->prepare("SELECT id_est FROM citas WHERE id_cita = :id_cita");
            $stmt_check->bindParam(':id_cita', $id_cita_cambiar, PDO::PARAM_INT);
            $stmt_check->execute();
            $current_cita = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($current_cita && $current_cita['id_est'] == ID_ESTADO_ASIGNADA) {
                 $sql_update = "UPDATE citas SET id_est = :nuevo_id_est WHERE id_cita = :id_cita_cambiar";
                 $stmt_update = $pdo->prepare($sql_update);
                 $stmt_update->bindParam(':nuevo_id_est', $nuevo_id_est, PDO::PARAM_INT);
                 $stmt_update->bindParam(':id_cita_cambiar', $id_cita_cambiar, PDO::PARAM_INT);
                 $stmt_update->execute();
                 $_SESSION['mensaje_exito'] = "Estado de la cita #{$id_cita_cambiar} actualizado correctamente.";
            } else {
                 $_SESSION['mensaje_error'] = "No se puede cambiar el estado de esta cita o la cita no está asignada.";
            }
        } catch (PDOException $e) {
            $_SESSION['mensaje_error'] = "Error al actualizar el estado: " . $e->getMessage();
        }
    } else {
        $_SESSION['mensaje_error'] = "Datos inválidos o cambio de estado no permitido desde el estado actual.";
    }

    $redirect_url = "citas.php?";
    $redirect_params = [];
    if (isset($_POST['filtro_estado_actual'])) $redirect_params['filtro_estado'] = $_POST['filtro_estado_actual'];
    if (isset($_POST['pagina_actual_redirect'])) $redirect_params['pagina'] = $_POST['pagina_actual_redirect'];
    if (isset($_POST['busqueda_actual'])) $redirect_params['buscar'] = $_POST['busqueda_actual'];
    $redirect_url .= http_build_query($redirect_params);
    header("Location: " . $redirect_url);
    exit;
}
// --- PAGINATION CONFIGURATION ---
define('REGISTROS_POR_PAGINA', 3);

// --- FILTER CONFIGURATION based on your request ---
$filtros_estado_disponibles = [
    'activas'       => ['label' => 'Activas',       'ids' => [ID_ESTADO_ASIGNADA]],
    'realizadas'    => ['label' => 'Realizadas',    'ids' => [ID_ESTADO_REALIZADA]],
    'no_realizadas' => ['label' => 'No Realizadas', 'ids' => [ID_ESTADO_NO_REALIZADA, ID_ESTADO_NO_ASISTIO, ID_ESTADO_CANCELADA]],
    'todas'         => ['label' => 'Todas',         'ids' => [ID_ESTADO_ASIGNADA, ID_ESTADO_REALIZADA, ID_ESTADO_NO_REALIZADA, ID_ESTADO_NO_ASISTIO, ID_ESTADO_CANCELADA]],
];

$filtro_actual_key = isset($_GET['filtro_estado']) && array_key_exists($_GET['filtro_estado'], $filtros_estado_disponibles)
    ? $_GET['filtro_estado']
    : 'activas'; // Default to 'activas'

$ids_estado_filtrar = $filtros_estado_disponibles[$filtro_actual_key]['ids'];
$pageTitle = $filtros_estado_disponibles[$filtro_actual_key]['label']; // This will be for the main page

$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// --- BUILD WHERE CLAUSE & PARAMS ---
// $pdo is available here as well for the main data fetching
$base_sql_select_count = "SELECT COUNT(c.id_cita) FROM citas c INNER JOIN estado e ON c.id_est = e.id_est LEFT JOIN usuarios up ON c.doc_pac = up.doc_usu";
$base_sql_select_data = "SELECT c.*, e.nom_est, up.nom_usu AS nom_paciente FROM citas c INNER JOIN estado e ON c.id_est = e.id_est LEFT JOIN usuarios up ON c.doc_pac = up.doc_usu";
$where_clauses = [];
$query_params = [];

if (!empty($ids_estado_filtrar)) {
    $status_placeholders = [];
    foreach ($ids_estado_filtrar as $index => $id_est) {
        $ph = ":id_est_filter_" . $index;
        $status_placeholders[] = $ph;
        $query_params[$ph] = $id_est;
    }
    $where_clauses[] = "c.id_est IN (" . implode(", ", $status_placeholders) . ")";
}
if ($busqueda != '') {
    $likeBusqueda = "%$busqueda%";
    $search_conditions_sql = "(c.id_cita LIKE :search_id_cita OR c.doc_pac LIKE :search_doc_pac OR c.fecha_solici LIKE :search_fecha_solici OR c.fecha_cita LIKE :search_fecha_cita OR c.hora_cita LIKE :search_hora_cita OR e.nom_est LIKE :search_nom_est OR up.nom_usu LIKE :search_nom_paciente)";
    $where_clauses[] = $search_conditions_sql;
    $query_params[':search_id_cita'] = $likeBusqueda;
    $query_params[':search_doc_pac'] = $likeBusqueda;
    $query_params[':search_fecha_solici'] = $likeBusqueda;
    $query_params[':search_fecha_cita'] = $likeBusqueda;
    $query_params[':search_hora_cita'] = $likeBusqueda;
    $query_params[':search_nom_est'] = $likeBusqueda;
    $query_params[':search_nom_paciente'] = $likeBusqueda;
}
$final_where_sql = "";
if (!empty($where_clauses)) {
    $final_where_sql = " WHERE " . implode(" AND ", $where_clauses);
}
$count_query_sql = $base_sql_select_count . $final_where_sql;
$stmt_count = $pdo->prepare($count_query_sql);
$stmt_count->execute($query_params);
$total_registros = (int)$stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / REGISTROS_POR_PAGINA);
if ($pagina_actual > $total_paginas && $total_paginas > 0) $pagina_actual = $total_paginas;
if ($pagina_actual < 1 && $total_paginas > 0) $pagina_actual = 1;
if ($total_paginas == 0 && $pagina_actual > 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * REGISTROS_POR_PAGINA;
$query_data_sql = $base_sql_select_data . $final_where_sql . " ORDER BY c.fecha_cita DESC, c.hora_cita DESC LIMIT :offset_param, :limit_param";
$data_query_params = $query_params;
$data_query_params[':offset_param'] = $offset;
$data_query_params[':limit_param'] = REGISTROS_POR_PAGINA;
$stmt_data = $pdo->prepare($query_data_sql);
$stmt_data->execute($data_query_params);
$citas = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

$mensaje_exito = isset($_SESSION['mensaje_exito']) ? $_SESSION['mensaje_exito'] : null;
$mensaje_error = isset($_SESSION['mensaje_error']) ? $_SESSION['mensaje_error'] : null;
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citas: <?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="estilos.css">
    <style>
        /* Custom styles for the modal form if needed */
        #modalConsulta .form-label {
            margin-bottom: 0.3rem; /* Slightly reduce bottom margin for labels */
        }
        #modalConsulta .form-control, 
        #modalConsulta .form-select {
            /* font-size: 0.9rem; /* Optionally, make form elements a bit smaller */
        }
        /* Ensure textarea for motivo_de_cons has a defined height and doesn't resize aggressively by default */
        #motivo_de_cons {
            min-height: 80px; /* Adjust as needed */
            /* /* resize: vertical; Allow only vertical resizing by user, or 'none' */
        }
    </style>
</head>

<?php include '../include/menu.php'; ?>

<body>
<div class="container main-content-area-table">
    <div class="page-container-table">

        <div class="d-flex justify-content-between align-items-center mb-4">
             <a href="inicio.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <h2 class="text-center mb-0 flex-grow-1">Citas: <?php echo htmlspecialchars($pageTitle); ?></h2>
            <div style="width: 80px;"></div> <!-- Spacer -->
        </div>

        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($mensaje_exito); ?></div>
        <?php endif; ?>
        <?php if ($mensaje_error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($mensaje_error); ?></div>
        <?php endif; ?>

        <form method="GET" action="citas.php" class="mb-2 row g-3 align-items-center">
            <div class="col-md-4">
                <label for="filtro_estado_select" class="form-label">Filtrar por estado:</label>
                <select name="filtro_estado" id="filtro_estado_select" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($filtros_estado_disponibles as $key => $filter): ?>
                        <option value="<?= $key ?>" <?= ($filtro_actual_key == $key) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($filter['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="buscar_input" class="form-label">Buscar en <?= strtolower(htmlspecialchars($pageTitle)) ?>:</label>
                <input type="text" name="buscar" id="buscar_input" class="form-control" placeholder="Escriba para buscar..." value="<?= htmlspecialchars($busqueda); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 me-2">Buscar</button>
                <a href="citas.php?filtro_estado=<?= htmlspecialchars($filtro_actual_key) ?>" class="btn btn-secondary w-100" title="Limpiar búsqueda actual">Limpiar</a>
            </div>
        </form>

        <div class="mb-4 text-end">
            <a href="descargar_citas_excel.php?filtro_estado=<?= htmlspecialchars($filtro_actual_key) ?>&buscar=<?= urlencode($busqueda) ?>" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Descargar Excel
            </a>
            <a href="descargar_citas_pdf.php?filtro_estado=<?= htmlspecialchars($filtro_actual_key) ?>&buscar=<?= urlencode($busqueda) ?>" class="btn btn-danger btn-sm">
                <i class="fas fa-file-pdf"></i> Descargar PDF
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center table-project-styled">
                <thead>
                    <tr>
                        <th>ID Cita</th>
                        <th>Nombre Paciente</th>
                        <th>Doc. Paciente</th>
                        <th>Fecha Solicitud</th>
                        <th>Fecha Cita</th>
                        <th>Hora Cita</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($citas) > 0) {
                        foreach ($citas as $cita) { ?>
                            <tr>
                                <td><?= htmlspecialchars($cita['id_cita']); ?></td>
                                <td><?= htmlspecialchars($cita['nom_paciente'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($cita['doc_pac']); ?></td>
                                <td><?= htmlspecialchars($cita['fecha_solici']); ?></td>
                                <td><?= htmlspecialchars($cita['fecha_cita']); ?></td>
                                <td><?= htmlspecialchars($cita['hora_cita']); ?></td>
                                <td><?= htmlspecialchars($cita['nom_est']); ?></td>
                                <td>
                                    <?php if ($cita['id_est'] == ID_ESTADO_ASIGNADA): ?>
                                        <button type="button" class="btn btn-sm btn-info mb-1 d-block iniciar-consulta-btn"
                                                data-bs-toggle="modal" data-bs-target="#modalConsulta"
                                                data-id-cita="<?= htmlspecialchars($cita['id_cita']); ?>"
                                                data-doc-paciente="<?= htmlspecialchars($cita['doc_pac']); ?>"
                                                data-nom-paciente="<?= htmlspecialchars($cita['nom_paciente'] ?? 'N/A'); ?>">
                                            Iniciar consulta
                                        </button>
                                        
                                        <form method="POST" action="citas.php" class="mt-1">
                                            <input type="hidden" name="action" value="cambiar_estado">
                                            <input type="hidden" name="id_cita_cambiar" value="<?= $cita['id_cita'] ?>">
                                            <input type="hidden" name="filtro_estado_actual" value="<?= htmlspecialchars($filtro_actual_key) ?>">
                                            <input type="hidden" name="pagina_actual_redirect" value="<?= $pagina_actual ?>">
                                            <input type="hidden" name="busqueda_actual" value="<?= htmlspecialchars($busqueda) ?>">
                                            
                                            <select name="nuevo_id_est" class="form-select form-select-sm" onchange="if(this.value !== '') { this.form.submit(); }">
                                                <option value="">Cambiar estado a...</option>
                                                <option value="<?= ID_ESTADO_REALIZADA ?>">Realizada</option>
                                                <option value="<?= ID_ESTADO_NO_REALIZADA ?>">No Realizada</option>
                                                <option value="<?= ID_ESTADO_NO_ASISTIO ?>">No Asistió</option>
                                                <option value="<?= ID_ESTADO_CANCELADA ?>">Cancelar Cita</option>
                                            </select>
                                        </form>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php }
                    } else { /* ... no results ... */ } ?>
                </tbody>
            </table>
        </div>
        <!-- ... (Pagination) ... -->
         <?php if ($total_paginas > 1) : ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>&filtro_estado=<?= htmlspecialchars($filtro_actual_key) ?>&buscar=<?php echo urlencode($busqueda); ?>">Anterior</a>
                </li>
                <?php
                $rango_paginas = 2;
                $inicio_rango = max(1, $pagina_actual - $rango_paginas);
                $fin_rango = min($total_paginas, $pagina_actual + $rango_paginas);

                if ($inicio_rango > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?pagina=1&filtro_estado=' . htmlspecialchars($filtro_actual_key) . '&buscar=' . urlencode($busqueda) . '">1</a></li>';
                    if ($inicio_rango > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                for ($i = $inicio_rango; $i <= $fin_rango; $i++) : ?>
                    <li class="page-item <?php echo ($pagina_actual == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $i; ?>&filtro_estado=<?= htmlspecialchars($filtro_actual_key) ?>&buscar=<?php echo urlencode($busqueda); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor;
                if ($fin_rango < $total_paginas) {
                    if ($fin_rango < $total_paginas - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?pagina=' . $total_paginas . '&filtro_estado=' . htmlspecialchars($filtro_actual_key) . '&buscar=' . urlencode($busqueda) . '">' . $total_paginas . '</a></li>';
                }
                ?>
                <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>&filtro_estado=<?= htmlspecialchars($filtro_actual_key) ?>&buscar=<?php echo urlencode($busqueda); ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Consulta -->
<div class="modal fade" id="modalConsulta" tabindex="-1" aria-labelledby="modalConsultaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl"> 
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConsultaLabelDinamica">Consulta Médica</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong id="modalPacienteNombre">Paciente: </strong> <br>
                    <small id="modalPacienteDocumento" class="text-muted">Documento: </small>
                </div>
                <hr>
                
                <form id="formConsultaMedica" action="guarda_consul.php" method="POST">
                    <input type="hidden" name="id_cita" id="modalIdCita" value="">
                    <input type="hidden" name="doc_pac_hidden" id="modalDocPacHidden" value=""> 
                    <input type="hidden" name="filtro_estado_actual_hidden" value="<?= htmlspecialchars($filtro_actual_key) ?>">
                    <input type="hidden" name="pagina_actual_hidden" value="<?= $pagina_actual ?>">
                    <input type="hidden" name="busqueda_actual_hidden" value="<?= htmlspecialchars($busqueda) ?>">

                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="motivo_de_cons" class="form-label">Diagnostico de la consulta <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="motivo_de_cons" name="motivo_de_cons" rows="3" required></textarea>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label for="presion" class="form-label">Presión Arterial</label>
                            <input type="text" class="form-control" id="presion" name="presion" placeholder="ej: 120/80">
                        </div>
                        <div class="col-md-3">
                            <label for="saturacion" class="form-label">Saturación O<sub>2</sub></label>
                            <input type="text" class="form-control" id="saturacion" name="saturacion" placeholder="ej: 98%">
                        </div>
                        <div class="col-md-3">
                            <label for="peso" class="form-label">Peso</label>
                            <input type="text" class="form-control" id="peso" name="peso" placeholder="ej: 70.5 kg">
                        </div>
                        <div class="col-md-3">
                            <label for="estatura" class="form-label">Estatura</label>
                            <input type="text" class="form-control" id="estatura" name="estatura" placeholder="ej: 175 cm">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="observaciones" class="form-label">Observaciones / Anamnesis</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="4"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" form="formConsultaMedica" class="btn btn-primary">Guardar Consulta</button>
            </div>
        </div>
    </div>
</div>

<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
<script src="js/inicio_consul.js"></script>


<?php include '../include/footer.php'; ?>
</body>
</html>