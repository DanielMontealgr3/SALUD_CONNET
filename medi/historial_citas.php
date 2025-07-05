<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || !in_array($_SESSION['id_rol'], [1, 4])) {
    header('Location: ../inicio_sesion.php');
    exit;
}

// --- CONSTANTES Y CONFIGURACIÓN ---
define('REGISTROS_POR_PAGINA', 3);
$filtros_estado_disponibles = [
    'todas'          => ['label' => 'Todas (Historial)', 'ids' => [3, 5, 6, 7, 8]],
    'activas'        => ['label' => 'Activas/Asignadas', 'ids' => [3]],
    'realizadas'     => ['label' => 'Realizadas',        'ids' => [5]],
    'no_completadas' => ['label' => 'No Completadas',    'ids' => [6, 7, 8]],
];

$db = new Database();
$pdo = $db->conectar();

// --- RECOLECCIÓN DE FILTROS ---
$filtro_actual_key = isset($_GET['filtro_estado']) && array_key_exists($_GET['filtro_estado'], $filtros_estado_disponibles) ? $_GET['filtro_estado'] : 'todas';
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$fecha_desde = isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : '';

$pageTitle = "Historial de Citas: " . $filtros_estado_disponibles[$filtro_actual_key]['label'];
$ids_estado_filtrar = $filtros_estado_disponibles[$filtro_actual_key]['ids'];

// --- CONSTRUCCIÓN DE CONSULTA SQL ---
$sql_base = "FROM citas c 
             JOIN estado e ON c.id_est = e.id_est 
             LEFT JOIN usuarios up ON c.doc_pac = up.doc_usu
             LEFT JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med";

$where_clauses = [];
$query_params = [];

if (!empty($ids_estado_filtrar)) {
    $placeholders = implode(',', array_fill(0, count($ids_estado_filtrar), '?'));
    $where_clauses[] = "c.id_est IN ($placeholders)";
    $query_params = array_merge($query_params, $ids_estado_filtrar);
}
if ($busqueda !== '') {
    $likeBusqueda = "%$busqueda%";
    $where_clauses[] = "(c.id_cita LIKE ? OR c.doc_pac LIKE ? OR up.nom_usu LIKE ?)";
    array_push($query_params, $likeBusqueda, $likeBusqueda, $likeBusqueda);
}
if ($fecha_desde !== '' && $fecha_hasta !== '') {
    $where_clauses[] = "hm.fecha_horario BETWEEN ? AND ?";
    array_push($query_params, $fecha_desde, $fecha_hasta);
} elseif ($fecha_desde !== '') {
    $where_clauses[] = "hm.fecha_horario >= ?";
    $query_params[] = $fecha_desde;
} elseif ($fecha_hasta !== '') {
    $where_clauses[] = "hm.fecha_horario <= ?";
    $query_params[] = $fecha_hasta;
}

$final_where_sql = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- CONTEO PARA PAGINACIÓN ---
$count_sql = "SELECT COUNT(c.id_cita) " . $sql_base . $final_where_sql;
$stmt_count = $pdo->prepare($count_sql);
$stmt_count->execute($query_params);
$total_registros = (int)$stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / REGISTROS_POR_PAGINA);

if ($pagina_actual > $total_paginas && $total_paginas > 0) $pagina_actual = $total_paginas;
$offset = ($pagina_actual - 1) * REGISTROS_POR_PAGINA;

// --- OBTENCIÓN DE DATOS ---
$sql_data = "SELECT c.id_cita, c.doc_pac, e.nom_est, up.nom_usu AS nom_paciente, c.fecha_solici, 
                    hm.fecha_horario AS fecha_cita, hm.horario AS hora_cita " 
            . $sql_base . $final_where_sql 
            . " ORDER BY hm.fecha_horario DESC, hm.horario DESC LIMIT ? OFFSET ?";
$data_query_params = $query_params;
$data_query_params[] = REGISTROS_POR_PAGINA;
$data_query_params[] = $offset;
$stmt_data = $pdo->prepare($sql_data);
$stmt_data->execute($data_query_params);
$citas = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

$mensaje_exito = $_SESSION['mensaje_exito'] ?? null;
$mensaje_error = $_SESSION['mensaje_error'] ?? null;
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; }
        .page-container { background-color: #fff; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-filters { background-color: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #dee2e6; }
        .table thead th { 
            background-color: #6c757d; 
            color: white; 
            border-bottom-width: 2px; 
            text-align: center; 
            vertical-align: middle;
            white-space: nowrap; 
        }
        .table td { 
            vertical-align: middle; 
            text-align: center;
            white-space: nowrap; 
        }
        .table .td-paciente { text-align: left; }
        .badge { font-size: 0.8rem; padding: 0.4em 0.7em; }
        .pagination-compact { display: flex; justify-content: center; align-items: center; margin-top: 1.5rem; }
        .pagination-compact .page-link { border-radius: 50% !important; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; margin: 0 5px; }
        .pagination-compact .page-counter { margin: 0 1rem; font-weight: 500; color: #495057; }
    </style>
</head>
<body>
<?php include '../include/menu.php'; ?>
<div class="container mt-4">
    <div class="page-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="text-center mb-0 flex-grow-1"><?php echo htmlspecialchars($pageTitle); ?></h2>
        </div>

        <?php if ($mensaje_exito): ?><div class="alert alert-success"><?php echo htmlspecialchars($mensaje_exito); ?></div><?php endif; ?>
        <?php if ($mensaje_error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($mensaje_error); ?></div><?php endif; ?>
        
        <form method="GET" action="" class="form-filters">
            <div class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6"><label for="filtro_estado" class="form-label">Estado:</label><select name="filtro_estado" id="filtro_estado" class="form-select form-select-sm"><?php foreach ($filtros_estado_disponibles as $key => $filter): ?><option value="<?= $key ?>" <?= ($filtro_actual_key == $key) ? 'selected' : '' ?>><?= htmlspecialchars($filter['label']) ?></option><?php endforeach; ?></select></div>
                <div class="col-lg-2 col-md-6"><label for="fecha_desde" class="form-label">Desde:</label><input type="date" name="fecha_desde" id="fecha_desde" class="form-control form-control-sm" value="<?= htmlspecialchars($fecha_desde) ?>"></div>
                <div class="col-lg-2 col-md-6"><label for="fecha_hasta" class="form-label">Hasta:</label><input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control form-control-sm" value="<?= htmlspecialchars($fecha_hasta) ?>"></div>
                <div class="col-lg-3 col-md-6"><label for="buscar" class="form-label">Buscar:</label><input type="text" name="buscar" id="buscar" class="form-control form-control-sm" placeholder="ID, Doc, Nombre..." value="<?= htmlspecialchars($busqueda); ?>"></div>
                <div class="col-lg-2 col-md-12 d-flex justify-content-end gap-2"><button type="submit" class="btn btn-primary btn-sm">Filtrar</button><a href="historial_citas.php" class="btn btn-secondary btn-sm">Limpiar</a></div>
                <div><a href="descargar_citas_pdf.php?filtro_estado=<?= urlencode($filtro_actual_key) ?>&fecha_desde=<?= urlencode($fecha_desde) ?>&fecha_hasta=<?= urlencode($fecha_hasta) ?>&buscar=<?= urlencode($busqueda) ?>"
           class="btn btn-danger btn-sm" target="_blank">PDF</a></div>
                
                
                
            </div>
        </form>

        <!-- DIV CON CLASE table-responsive RESTAURADO -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center table-sm">
                <thead>
                    <tr><th>ID Cita</th><th>Paciente</th><th>Documento</th><th>F. Solicitud</th><th>F. Cita</th><th>H. Cita</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <?php if (count($citas) > 0): ?>
                        <?php foreach ($citas as $cita): ?>
                            <tr>
                                <td><?= htmlspecialchars($cita['id_cita']); ?></td>
                                <td class="td-paciente"><?= htmlspecialchars($cita['nom_paciente'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($cita['doc_pac']); ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($cita['fecha_solici']))); ?></td>
                                <td><?= htmlspecialchars($cita['fecha_cita'] ? date('d/m/Y', strtotime($cita['fecha_cita'])) : 'N/D'); ?></td>
                                <td><?= htmlspecialchars($cita['hora_cita'] ? date('h:i A', strtotime($cita['hora_cita'])) : 'N/D'); ?></td>
                                <td><?= htmlspecialchars($cita['nom_est']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No se encontraron citas con los filtros aplicados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_paginas > 0): ?>
            <div class="pagination-compact">
                <ul class="pagination mb-0">
                    <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?>&<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])) ?>" aria-label="Anterior"><span><</span></a>
                    </li>
                </ul>
                <span class="page-counter"><?= $pagina_actual ?> / <?= $total_paginas ?></span>
                <ul class="pagination mb-0">
                    <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?>&<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])) ?>" aria-label="Siguiente"><span>></span></a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
    // No se necesita JS para la tabla responsiva.
</script>
<?php include '../include/footer.php'; ?>
</body>
</html>