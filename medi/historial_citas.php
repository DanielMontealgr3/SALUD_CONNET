<?php
// BLOQUE 1: CONFIGURACIÓN Y SEGURIDAD
// INCLUYE EL ARCHIVO DE CONFIGURACIÓN GLOBAL. ESTA DEBE SER SIEMPRE LA PRIMERA LÍNEA.
require_once __DIR__ . '/../include/config.php';

// INCLUYE SCRIPTS DE VALIDACIÓN DE SESIÓN Y MANEJO DE INACTIVIDAD.
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

// VERIFICACIÓN ESPECÍFICA DEL ROL. SOLO ADMIN (1) Y MÉDICO (4) PUEDEN ACCEDER.
if (!in_array($_SESSION['id_rol'], [1, 4])) {
    header('Location: ' . BASE_URL . '/inicio_sesion.php');
    exit;
}

// BLOQUE 2: CONSTANTES Y FILTROS
// DEFINE LOS FILTROS DE ESTADO DISPONIBLES PARA LAS CITAS.
define('REGISTROS_POR_PAGINA', 5);
$filtros_estado_disponibles = [
    'todas'          => ['label' => 'Todas (Historial)', 'ids' => [3, 5, 6, 7, 8]],
    'activas'        => ['label' => 'Activas/Asignadas', 'ids' => [3]],
    'realizadas'     => ['label' => 'Realizadas',        'ids' => [5]],
    'no_completadas' => ['label' => 'No Completadas',    'ids' => [6, 7, 8]],
];

// RECOGE Y SANEPA LOS PARÁMETROS DE FILTRADO Y PAGINACIÓN DESDE LA URL.
$filtro_actual_key = isset($_GET['filtro_estado']) && array_key_exists($_GET['filtro_estado'], $filtros_estado_disponibles) ? $_GET['filtro_estado'] : 'todas';
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$fecha_desde = isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : '';
$ids_estado_filtrar = $filtros_estado_disponibles[$filtro_actual_key]['ids'];

// BLOQUE 3: CONSTRUCCIÓN DE LA CONSULTA SQL
// ARMA LA PARTE BASE DEL QUERY CON LAS UNIONES DE TABLAS.
$sql_base = "FROM citas c 
             JOIN estado e ON c.id_est = e.id_est 
             LEFT JOIN usuarios up ON c.doc_pac = up.doc_usu
             LEFT JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med";

// CONSTRUYE DINÁMICAMENTE LA CLÁUSULA WHERE BASADO EN LOS FILTROS APLICADOS.
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

// CONTEO DE REGISTROS PARA LA PAGINACIÓN.
$count_sql = "SELECT COUNT(c.id_cita) " . $sql_base . $final_where_sql;
$stmt_count = $con->prepare($count_sql);
$stmt_count->execute($query_params);
$total_registros = (int)$stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / REGISTROS_POR_PAGINA);

// AJUSTE FINAL DE LA PAGINACIÓN Y CÁLCULO DEL OFFSET.
if ($pagina_actual > $total_paginas && $total_paginas > 0) $pagina_actual = $total_paginas;
$offset = ($pagina_actual - 1) * REGISTROS_POR_PAGINA;

// OBTENCIÓN DE LOS DATOS PARA LA PÁGINA ACTUAL.
$sql_data = "SELECT c.id_cita, c.doc_pac, e.nom_est, up.nom_usu AS nom_paciente, c.fecha_solici, 
                    hm.fecha_horario AS fecha_cita, hm.horario AS hora_cita " 
            . $sql_base . $final_where_sql 
            . " ORDER BY hm.fecha_horario DESC, hm.horario DESC LIMIT ? OFFSET ?";
$data_query_params = array_merge($query_params, [$offset, REGISTROS_POR_PAGINA]);
$stmt_data = $con->prepare($sql_data);
$stmt_data->execute($data_query_params);
$citas = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

// GESTIÓN DE MENSAJES FLASH PARA NOTIFICACIONES.
$mensaje_exito = $_SESSION['mensaje_exito'] ?? null;
$mensaje_error = $_SESSION['mensaje_error'] ?? null;
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);

// ESTABLECE EL TÍTULO DE LA PÁGINA ANTES DE INCLUIR EL MENÚ.
$pageTitle = "Historial de Citas: " . $filtros_estado_disponibles[$filtro_actual_key]['label'];
?>
<!DOCTYPE html>
<html lang="es">
<?php 
// BLOQUE 4: INCLUSIÓN DE LA VISTA (ENCABEZADO Y MENÚ)
require_once ROOT_PATH . '/include/menu.php'; 
?>
<div class="container mt-4">
    <div class="page-container">
        <!-- TÍTULO DE LA PÁGINA Y NOTIFICACIONES. -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="text-center mb-0 flex-grow-1"><?php echo htmlspecialchars($pageTitle); ?></h2>
        </div>
        <?php if ($mensaje_exito): ?><div class="alert alert-success"><?php echo htmlspecialchars($mensaje_exito); ?></div><?php endif; ?>
        <?php if ($mensaje_error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($mensaje_error); ?></div><?php endif; ?>
        
        <!-- FORMULARIO DE FILTROS -->
        <form method="GET" action="<?php echo BASE_URL; ?>/medi/historial_citas.php" class="form-filters">
            <div class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6"><label for="filtro_estado" class="form-label">Estado:</label><select name="filtro_estado" id="filtro_estado" class="form-select form-select-sm"><?php foreach ($filtros_estado_disponibles as $key => $filter): ?><option value="<?php echo $key; ?>" <?php echo ($filtro_actual_key == $key) ? 'selected' : ''; ?>><?php echo htmlspecialchars($filter['label']); ?></option><?php endforeach; ?></select></div>
                <div class="col-lg-2 col-md-6"><label for="fecha_desde" class="form-label">Desde:</label><input type="date" name="fecha_desde" id="fecha_desde" class="form-control form-control-sm" value="<?php echo htmlspecialchars($fecha_desde); ?>"></div>
                <div class="col-lg-2 col-md-6"><label for="fecha_hasta" class="form-label">Hasta:</label><input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control form-control-sm" value="<?php echo htmlspecialchars($fecha_hasta); ?>"></div>
                <div class="col-lg-3 col-md-6"><label for="buscar" class="form-label">Buscar:</label><input type="text" name="buscar" id="buscar" class="form-control form-control-sm" placeholder="ID, Doc, Nombre..." value="<?php echo htmlspecialchars($busqueda); ?>"></div>
                <div class="col-lg-2 col-md-12 d-flex justify-content-end gap-2"><button type="submit" class="btn btn-primary btn-sm">Filtrar</button><a href="<?php echo BASE_URL; ?>/medi/historial_citas.php" class="btn btn-secondary btn-sm">Limpiar</a></div>
                <div><a href="<?php echo BASE_URL; ?>/medi/descargar_citas_pdf.php?<?php echo http_build_query($_GET); ?>" class="btn btn-danger btn-sm" target="_blank">PDF</a></div>
            </div>
        </form>

        <!-- TABLA DE RESULTADOS -->
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center table-sm">
                <thead>
                    <tr><th>ID Cita</th><th>Paciente</th><th>Documento</th><th>F. Solicitud</th><th>F. Cita</th><th>H. Cita</th><th>Estado</th></tr>
                </thead>
                <tbody>
                    <?php if (count($citas) > 0): ?>
                        <?php foreach ($citas as $cita): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cita['id_cita']); ?></td>
                                <td class="td-paciente"><?php echo htmlspecialchars($cita['nom_paciente'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cita['doc_pac']); ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($cita['fecha_solici']))); ?></td>
                                <td><?php echo htmlspecialchars($cita['fecha_cita'] ? date('d/m/Y', strtotime($cita['fecha_cita'])) : 'N/D'); ?></td>
                                <td><?php echo htmlspecialchars($cita['hora_cita'] ? date('h:i A', strtotime($cita['hora_cita'])) : 'N/D'); ?></td>
                                <td><?php echo htmlspecialchars($cita['nom_est']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No se encontraron citas con los filtros aplicados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- BLOQUE DE PAGINACIÓN -->
        <?php if ($total_paginas > 1): ?>
            <div class="pagination-compact">
                <ul class="pagination mb-0">
                    <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])); ?>" aria-label="Anterior"><span><</span></a>
                    </li>
                </ul>
                <span class="page-counter"><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></span>
                <ul class="pagination mb-0">
                    <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])); ?>" aria-label="Siguiente"><span>></span></a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php 
// BLOQUE 5: INCLUSIÓN DEL PIE DE PÁGINA
require_once ROOT_PATH . '/include/footer.php'; 
?>
</html>