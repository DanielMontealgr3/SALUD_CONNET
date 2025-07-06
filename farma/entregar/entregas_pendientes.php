<?php
// --- BLOQUE 1: CONFIGURACIÓN INICIAL Y SEGURIDAD ---
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

// --- BLOQUE 2: FUNCIÓN PARA GENERAR EL CONTENIDO DE LA TABLA Y PAGINACIÓN ---
function generarFilasPendientes($con, $nit_farmacia_actual, &$pagina_actual_ref, &$total_paginas_ref, &$total_registros_ref)
{
    $registros_por_pagina = 3;
    $pagina_actual_ref = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina_actual_ref < 1) $pagina_actual_ref = 1;

    // Se mantiene toda tu lógica de filtros intacta
    $filtro_radicado = trim($_GET['q_radicado'] ?? '');
    $filtro_documento = trim($_GET['q_documento'] ?? '');
    $filtro_estado = trim($_GET['estado'] ?? '10');
    $filtro_orden = trim($_GET['orden'] ?? 'desc');
    $filtro_fecha_inicio = trim($_GET['fecha_inicio'] ?? '');
    $filtro_fecha_fin = trim($_GET['fecha_fin'] ?? '');
    
    $params = [':nit_farma' => $nit_farmacia_actual];
    $sql_where_conditions = [];

    if ($filtro_estado !== 'todos') {
        $sql_where_conditions[] = "ep.id_estado = :id_estado";
        $params[':id_estado'] = (int)$filtro_estado;
    }
    if (!empty($filtro_radicado)) {
        $sql_where_conditions[] = "ep.radicado_pendiente LIKE :radicado";
        $params[':radicado'] = "%" . $filtro_radicado . "%";
    }
    if (!empty($filtro_documento)) {
        $sql_where_conditions[] = "u.doc_usu LIKE :documento";
        $params[':documento'] = "%" . $filtro_documento . "%";
    }
    if (!empty($filtro_fecha_inicio)) {
        $sql_where_conditions[] = "ep.fecha_generacion >= :fecha_inicio";
        $params[':fecha_inicio'] = $filtro_fecha_inicio;
    }
    if (!empty($filtro_fecha_fin)) {
        $sql_where_conditions[] = "ep.fecha_generacion <= :fecha_fin_ajustada";
        $params[':fecha_fin_ajustada'] = $filtro_fecha_fin . ' 23:59:59';
    }

    $sql_from_join = "
        FROM entrega_pendiente ep
        JOIN detalles_histo_clini dh ON ep.id_detalle_histo = dh.id_detalle
        JOIN historia_clinica hc ON dh.id_historia = hc.id_historia
        JOIN citas c ON hc.id_cita = c.id_cita
        JOIN usuarios u ON c.doc_pac = u.doc_usu
        JOIN medicamentos m ON dh.id_medicam = m.id_medicamento
        JOIN usuarios fg ON ep.id_farmaceuta_genera = fg.doc_usu
        JOIN asignacion_farmaceuta af ON fg.doc_usu = af.doc_farma
    ";
    
    $sql_base_where = " WHERE af.nit_farma = :nit_farma ";
    $sql_final_where = $sql_base_where . (!empty($sql_where_conditions) ? " AND " . implode(" AND ", $sql_where_conditions) : "");
    
    $stmt_total = $con->prepare("SELECT COUNT(DISTINCT ep.id_entrega_pendiente) " . $sql_from_join . $sql_final_where);
    $stmt_total->execute($params);
    $total_registros_ref = (int)$stmt_total->fetchColumn();
    
    $total_paginas_ref = ceil($total_registros_ref / $registros_por_pagina);
    if ($total_paginas_ref == 0) $total_paginas_ref = 1;
    if ($pagina_actual_ref > $total_paginas_ref) $pagina_actual_ref = $total_paginas_ref;
    
    $offset = ($pagina_actual_ref - 1) * $registros_por_pagina;
    $order_by = ($filtro_orden === 'asc') ? "ep.fecha_generacion ASC" : "ep.fecha_generacion DESC";
    
    // Tu consulta original, incluyendo los datos del farmaceuta que genera
    $sql_final = "
        SELECT ep.id_entrega_pendiente, ep.radicado_pendiente, ep.fecha_generacion, ep.id_estado, 
               u.nom_usu AS nombre_paciente, u.doc_usu AS doc_paciente, m.nom_medicamento, 
               dh.id_detalle, ep.cantidad_pendiente, hc.id_historia,
               fg.nom_usu as farmaceuta_genera, fg.doc_usu as doc_farmaceuta_genera
        " . $sql_from_join . $sql_final_where . "
        GROUP BY ep.id_entrega_pendiente
        ORDER BY " . $order_by . "
        LIMIT :limit OFFSET :offset_val
    ";
    
    $stmt = $con->prepare($sql_final);
    foreach ($params as $key => &$val) $stmt->bindParam($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    $stmt->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset_val', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Se mantiene tu generación de HTML original
    ob_start();
    if (!empty($pendientes)):
        foreach ($pendientes as $p): ?>
            <tr>
                <td><span class="badge bg-warning text-dark"><?php echo htmlspecialchars($p['radicado_pendiente']); ?></span></td>
                <td><strong><?php echo htmlspecialchars($p['nombre_paciente']); ?></strong><br><small class="text-muted">Doc: <?php echo htmlspecialchars($p['doc_paciente']); ?></small></td>
                <td><?php echo htmlspecialchars($p['nom_medicamento']); ?></td>
                <td class="text-center"><strong><?php echo htmlspecialchars($p['cantidad_pendiente']); ?></strong></td>
                <td><?php echo htmlspecialchars(date('d/m/Y h:i A', strtotime($p['fecha_generacion']))); ?></td>
                <td class="acciones-tabla">
                    <button type="button" class="btn btn-info btn-sm btn-ver-pendiente" data-id-pendiente="<?php echo $p['id_entrega_pendiente']; ?>"><i class="bi bi-eye-fill"></i> Ver</button>
                    <?php if ($p['id_estado'] == 10): ?>
                        <button type="button" class="btn btn-success btn-sm btn-entregar-pendiente" 
                                data-id-entrega-pendiente="<?php echo $p['id_entrega_pendiente']; ?>"
                                data-id-historia="<?php echo $p['id_historia']; ?>"
                                data-id-detalle="<?php echo $p['id_detalle']; ?>">
                            <i class="bi bi-check-circle-fill"></i> Entregar
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary btn-sm" disabled><i class="bi bi-patch-check-fill"></i> Entregado</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach;
    else: ?>
        <tr><td colspan="6" class="text-center p-4">No se encontraron entregas pendientes con los filtros aplicados.</td></tr>
    <?php endif;
    
    // Se devuelve un array con el HTML, tal como lo espera tu JS
    $html_tabla = ob_get_clean();
    return ['html_tabla' => $html_tabla];
}

// --- BLOQUE 3: LÓGICA PRINCIPAL DE LA PÁGINA ---
// Se usa la conexión global $con de config.php
$nit_farmacia_actual = $_SESSION['nit_farma'] ?? $_SESSION['nit_farmacia_asignada_actual'] ?? null;
if (empty($nit_farmacia_actual)) { die("Error de sesión: Farmacia no identificada."); }

// Si la solicitud es por AJAX, se devuelve solo el JSON y se detiene.
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $pagina_actual_ajax = 1; $total_paginas_ajax = 1; $total_registros_ajax = 0;
    // Se llama a la función para obtener el HTML de la tabla
    $contenido_ajax = generarFilasPendientes($con, $nit_farmacia_actual, $pagina_actual_ajax, $total_paginas_ajax, $total_registros_ajax);
    // Se arma el JSON de respuesta que espera el JavaScript
    echo json_encode([
        'html_tabla' => $contenido_ajax['html_tabla'],
        'total_paginas' => $total_paginas_ajax,
        'pagina_actual' => $pagina_actual_ajax,
        'total_registros' => $total_registros_ajax
    ]);
    exit;
}

// Si es una carga inicial, se genera el contenido para la primera vez.
$pageTitle = "Entregas Pendientes";
$pagina_actual = 1; $total_paginas = 1; $total_registros = 0;
$contenido_inicial = generarFilasPendientes($con, $nit_farmacia_actual, $pagina_actual, $total_paginas, $total_registros);
$filas_pendientes_html = $contenido_inicial['html_tabla'];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/loguito.png">
    <?php require_once ROOT_PATH . '/include/menu.php'; ?>
    <style>
        .vista-datos-container { display: flex; flex-direction: column; flex-grow: 1; }
        .table-responsive { flex-grow: 1; }
        .form-control.is-invalid, .form-select.is-invalid {
            border-color: #dc3545; background-color: #f8d7da; background-image: none;
        }
        .form-control.is-invalid:focus, .form-select.is-invalid:focus {
             box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <h3 class="titulo-lista-tabla m-0">Gestión de Entregas Pendientes</h3>
                
                <!-- Tu formulario de filtros original, intacto -->
                <form id="formFiltrosPendientes" class="mb-4 mt-3 filtros-tabla-container" onsubmit="return false;">
                    <div class="row g-2 align-items-end">
                        <div class="col-xl-2 col-lg-4 col-md-6"><label for="q_radicado" class="form-label"><i class="bi bi-search"></i> Por Radicado:</label><input type="search" name="q_radicado" id="q_radicado" class="form-control form-control-sm" placeholder="PEND-..."></div>
                        <div class="col-xl-2 col-lg-4 col-md-6"><label for="q_documento" class="form-label"><i class="bi bi-person-badge"></i> Por Documento:</label><input type="search" name="q_documento" id="q_documento" class="form-control form-control-sm" placeholder="Documento paciente..."></div>
                        <div class="col-xl-2 col-lg-4 col-md-6"><label for="estado" class="form-label"><i class="bi bi-toggles"></i> Estado:</label><select name="estado" id="estado" class="form-select form-select-sm"><option value="10" selected>Pendientes</option><option value="9">Entregados</option><option value="todos">Todos</option></select></div>
                        <div class="col-xl-2 col-lg-4 col-md-6"><label for="orden" class="form-label"><i class="bi bi-sort-down"></i> Ordenar:</label><select name="orden" id="orden" class="form-select form-select-sm"><option value="desc">Más Recientes</option><option value="asc">Más Antiguos</option></select></div>
                        <div class="col-xl-1 col-lg-4 col-md-6"><label for="fecha_inicio" class="form-label"><i class="bi bi-calendar-date"></i> Fecha Inicio:</label><input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control form-control-sm"></div>
                        <div class="col-xl-1 col-lg-4 col-md-6"><label for="fecha_fin" class="form-label"><i class="bi bi-calendar-date-fill"></i> Fecha Fin:</label><input type="date" name="fecha_fin" id="fecha_fin" class="form-control form-control-sm"></div>
                        <div class="col-xl-1 col-lg-6 col-md-6 d-grid"><a href="entregas_pendientes.php" class="btn btn-sm btn-outline-secondary">Limpiar</a></div>
                        <div class="col-xl-1 col-lg-6 col-md-6 d-grid"><button id="btnGenerarReportePendientes" type="button" class="btn btn-sm btn-success" <?php if ($total_registros === 0) echo 'disabled'; ?>><i class="bi bi-file-earmark-excel-fill"></i> Reporte</button></div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="tabla-admin-mejorada">
                        <thead><tr><th>Radicado</th><th>Paciente</th><th>Medicamento</th><th class="text-center">Cantidad</th><th>Fecha Generación</th><th class="columna-acciones-fija">Acciones</th></tr></thead>
                        <tbody id="tabla-pendientes-body"><?php echo $filas_pendientes_html; ?></tbody>
                    </table>
                </div>
                <div id="paginacion-container" class="mt-3"></div>
            </div>
        </div>
    </main>
    
    <!-- Tus modales originales, intactos -->
    <div id="modal-entrega-placeholder"></div>
    <div class="modal fade" id="modalDetallesPendiente" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="bi bi-info-circle-fill me-2"></i>Detalles del Pendiente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="cuerpoModalDetalles"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div></div></div></div>
    <div class="modal fade" id="modalConfirmarReportePendientes" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="bi bi-file-earmark-excel-fill me-2"></i>Generar Reporte de Pendientes</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p>Se generará un reporte en Excel con los filtros aplicados:</p><div id="confirmarReporteTextoPendientes" class="alert alert-light border"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-success" id="btnConfirmarGeneracionPendientes"><i class="bi bi-check-circle-fill"></i> Confirmar y Generar</button></div></div></div></div>
    
    <?php require_once ROOT_PATH . '/include/footer.php'; ?>
    
    <!-- Tus scripts JS originales, intactos -->
    <script src="<?php echo BASE_URL; ?>/farma/js/gestion_pendientes.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo BASE_URL; ?>/farma/js/gestion_entrega.js?v=<?php echo time(); ?>"></script>
</body>
</html>