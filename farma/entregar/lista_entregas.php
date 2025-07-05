<?php
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

function generarContenidoEntregas($con, $nit_farmacia_actual, &$total_registros_ref) {
    $registros_por_pagina = 3;
    $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina_actual < 1) $pagina_actual = 1;

    $filtro_doc = trim($_GET['filtro_doc'] ?? '');
    $filtro_id = trim($_GET['filtro_id'] ?? '');
    $filtro_orden_fecha = trim($_GET['filtro_orden_fecha'] ?? 'desc');
    $filtro_fecha_inicio = trim($_GET['filtro_fecha_inicio'] ?? '');
    $filtro_fecha_fin = trim($_GET['filtro_fecha_fin'] ?? '');

    $sql_base_from = "
        FROM entrega_medicamentos em
        JOIN usuarios far ON em.doc_farmaceuta = far.doc_usu
        JOIN estado est ON em.id_estado = est.id_est
        JOIN detalles_histo_clini dhc ON em.id_detalle_histo = dhc.id_detalle
        JOIN medicamentos med ON dhc.id_medicam = med.id_medicamento
        JOIN historia_clinica hc ON dhc.id_historia = hc.id_historia
        JOIN citas c ON hc.id_cita = c.id_cita
        JOIN usuarios pac ON c.doc_pac = pac.doc_usu
        JOIN turno_ent_medic tem ON dhc.id_historia = tem.id_historia AND tem.id_est = 9
        JOIN asignacion_farmaceuta af ON em.doc_farmaceuta = af.doc_farma
    ";

    $sql_where_conditions = ["af.nit_farma = :nit_farma_actual"];
    $params = [':nit_farma_actual' => $nit_farmacia_actual];

    if (!empty($filtro_doc)) { $sql_where_conditions[] = "pac.doc_usu LIKE :doc_pac"; $params[':doc_pac'] = "%" . $filtro_doc . "%"; }
    if (!empty($filtro_id)) { $sql_where_conditions[] = "em.id_entrega LIKE :id_entrega"; $params[':id_entrega'] = "%" . $filtro_id . "%"; }
    if (!empty($filtro_fecha_inicio)) { $sql_where_conditions[] = "tem.fecha_entreg >= :fecha_inicio"; $params[':fecha_inicio'] = $filtro_fecha_inicio; }
    if (!empty($filtro_fecha_fin)) { $sql_where_conditions[] = "tem.fecha_entreg <= :fecha_fin"; $params[':fecha_fin'] = $filtro_fecha_fin; }

    $sql_where = " WHERE " . implode(" AND ", $sql_where_conditions);
    
    $stmt_total = $con->prepare("SELECT COUNT(DISTINCT em.id_entrega) " . $sql_base_from . $sql_where);
    $stmt_total->execute($params);
    $total_registros_ref = (int)$stmt_total->fetchColumn();
    
    $total_paginas = ceil($total_registros_ref / $registros_por_pagina);
    if ($total_paginas == 0) $total_paginas = 1;
    if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;
    
    $offset = ($pagina_actual - 1) * $registros_por_pagina;
    $orden_sql = ($filtro_orden_fecha === 'asc') ? 'ASC' : 'DESC';
    
    $sql_final = "
        SELECT 
            em.id_entrega, MAX(tem.fecha_entreg) AS fecha_entrega, pac.nom_usu AS nombre_paciente,
            pac.doc_usu AS doc_paciente, far.nom_usu AS nombre_farmaceuta, far.doc_usu AS doc_farmaceuta,
            med.nom_medicamento, em.cantidad_entregada, em.lote, est.nom_est
        " . $sql_base_from . $sql_where . "
        GROUP BY em.id_entrega
        ORDER BY fecha_entrega " . $orden_sql . ", em.id_entrega DESC
        LIMIT :limit OFFSET :offset_val
    ";

    $stmt_entregas = $con->prepare($sql_final);
    foreach ($params as $key => &$val) $stmt_entregas->bindParam($key, $val);
    $stmt_entregas->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt_entregas->bindParam(':offset_val', $offset, PDO::PARAM_INT);
    $stmt_entregas->execute();
    $lista_entregas = $stmt_entregas->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    if (!empty($lista_entregas)):
        foreach ($lista_entregas as $entrega): ?>
            <tr>
                <td><?php echo htmlspecialchars($entrega['id_entrega']); ?></td>
                <td><strong><?php echo htmlspecialchars($entrega['nombre_paciente']); ?></strong><br><small class="text-muted">Doc: <?php echo htmlspecialchars($entrega['doc_paciente']); ?></small></td>
                <td><strong><?php echo htmlspecialchars($entrega['nom_medicamento']); ?></strong><br><small class="text-muted">Cant: <?php echo htmlspecialchars($entrega['cantidad_entregada']); ?> | Lote: <?php echo htmlspecialchars($entrega['lote']); ?></small></td>
                <td><strong><?php echo htmlspecialchars($entrega['nombre_farmaceuta']); ?></strong><br><small class="text-muted">Doc: <?php echo htmlspecialchars($entrega['doc_farmaceuta']); ?></small></td>
                <td><?php echo htmlspecialchars($entrega['fecha_entrega']); ?></td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($entrega['nom_est']); ?></span></td>
                <td class="acciones-tabla"><button type="button" class="btn btn-primary btn-sm btn-ver-detalles" data-id-entrega="<?php echo $entrega['id_entrega']; ?>" title="Ver Detalles"><i class="bi bi-eye-fill"></i> Ver</button></td>
            </tr>
        <?php endforeach;
    else: ?>
        <tr><td colspan="7" class="text-center p-4">No se encontraron entregas que coincidan con los filtros.</td></tr>
    <?php endif;
    $filas_html = ob_get_clean();

    ob_start();
    if ($total_registros_ref > 0): ?>
        <nav aria-label="Paginación compacta de entregas">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="#" data-pagina="<?php echo $pagina_actual - 1; ?>" aria-label="Anterior"><</a></li>
                <li class="page-item active" aria-current="page"><span class="page-link" style="background-color: #0d6efd; border-color: #0d6efd;"><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></span></li>
                <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>"><a class="page-link" href="#" data-pagina="<?php echo $pagina_actual + 1; ?>" aria-label="Siguiente">></a></li>
            </ul>
        </nav>
    <?php endif;
    $paginacion_html = ob_get_clean();

    return ['filas' => $filas_html, 'paginacion' => $paginacion_html, 'total_registros' => $total_registros_ref];
}

$nit_farmacia_asignada_actual = $_SESSION['nit_farma'] ?? null;
$nombre_farmacia_asignada = 'Farmacia';
if (!$nit_farmacia_asignada_actual) { die("Error de sesión: No se ha identificado la farmacia. Por favor, vuelva a iniciar sesión."); }

if (isset($con)) {
    try {
        $stmt_nombre = $con->prepare("SELECT nom_farm FROM farmacias WHERE nit_farm = ?");
        $stmt_nombre->execute([$nit_farmacia_asignada_actual]);
        $nombre_farma = $stmt_nombre->fetchColumn();
        if ($nombre_farma) {
            $nombre_farmacia_asignada = $nombre_farma;
            $_SESSION['nombre_farmacia_actual'] = $nombre_farma;
        }
    } catch (PDOException $e) {}
}

if (isset($_GET['ajax_search'])) {
    header('Content-Type: application/json');
    $total_registros = 0;
    $contenido = generarContenidoEntregas($con, $nit_farmacia_asignada_actual, $total_registros);
    echo json_encode($contenido);
    exit;
}

$total_registros = 0;
$contenido_inicial = generarContenidoEntregas($con, $nit_farmacia_asignada_actual, $total_registros);
$filas_html = $contenido_inicial['filas'];
$paginacion_html = $contenido_inicial['paginacion'];
$total_registros = $contenido_inicial['total_registros'];
$pageTitle = "Historial de Entregas";
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
        .form-row-actions { display: flex; align-items: flex-end; gap: 0.5rem; }
        .modal-body .alert { background-color: #e9ecef; border-color: #ced4da; }
        .is-invalid-date {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <h3 class="titulo-lista-tabla mb-3">Historial de Entregas en: <strong><?php echo htmlspecialchars($nombre_farmacia_asignada); ?></strong></h3>
                
                <form id="formFiltros" class="mb-4 filtros-tabla-container" onsubmit="return false;">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-2 col-md-6"><label for="filtro_doc" class="form-label"><i class="bi bi-person-badge"></i> Doc. Paciente:</label><input type="text" id="filtro_doc" class="form-control form-control-sm" placeholder="Buscar..."></div>
                        <div class="col-lg-2 col-md-6"><label for="filtro_id" class="form-label"><i class="bi bi-hash"></i> ID Entrega:</label><input type="text" id="filtro_id" class="form-control form-control-sm" placeholder="Buscar..."></div>
                        <div class="col-lg-2 col-md-6"><label for="filtro_orden_fecha" class="form-label"><i class="bi bi-sort-down"></i> Ordenar Fecha:</label><select id="filtro_orden_fecha" class="form-select form-select-sm"><option value="desc">Más Recientes</option><option value="asc">Más Antiguos</option></select></div>
                        <div class="col-lg-2 col-md-6"><label for="filtro_fecha_inicio" class="form-label"><i class="bi bi-calendar-range"></i> Fecha Inicio:</label><input type="date" id="filtro_fecha_inicio" class="form-control form-control-sm" max="<?php echo date('Y-m-d'); ?>"></div>
                        <div class="col-lg-2 col-md-6"><label for="filtro_fecha_fin" class="form-label"><i class="bi bi-calendar-range-fill"></i> Fecha Fin:</label><input type="date" id="filtro_fecha_fin" class="form-control form-control-sm" max="<?php echo date('Y-m-d'); ?>"></div>
                        <div class="col-lg-2 col-md-12 form-row-actions">
                            <button id="btnLimpiar" type="button" class="btn btn-sm btn-outline-secondary w-100">Limpiar</button>
                            <button id="btnGenerarReporte" type="button" class="btn btn-sm btn-success w-100" <?php if ($total_registros === 0) echo 'disabled'; ?>><i class="bi bi-file-earmark-excel-fill"></i> Reporte</button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="tabla-admin-mejorada">
                        <thead><tr><th>ID Entrega</th><th>Paciente</th><th>Medicamento</th><th>Farmaceuta</th><th>Fecha Entrega</th><th>Estado</th><th class="columna-acciones-fija">Acciones</th></tr></thead>
                        <tbody id="entregas-tbody"><?php echo $filas_html; ?></tbody>
                    </table>
                </div>
                <div id="paginacion-container" class="mt-3"><?php echo $paginacion_html; ?></div>
            </div>
        </div>
    </main>
    
    <div class="modal fade" id="modalVerDetalles" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="modalVerDetallesLabel"><i class="bi bi-file-text-fill"></i> Detalles de la Entrega</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
                <div class="modal-body" id="contenidoModalDetalles"><div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="modalConfirmarReporte" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-file-earmark-excel-fill me-2"></i>Generar Reporte de Entregas</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p>Se generará un reporte en Excel con los siguientes filtros aplicados:</p>
                    <div id="confirmarReporteTexto" class="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnConfirmarGeneracion"><i class="bi bi-check-circle-fill"></i> Confirmar y Generar</button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once ROOT_PATH . '/include/footer.php'; ?>
    <script src="<?php echo BASE_URL; ?>/farma/js/lista_entregas.js?v=<?php echo time(); ?>"></script>
</body>
</html>