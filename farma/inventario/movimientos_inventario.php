<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

function generarContenidoMovimientos($con, $nit_farmacia_actual, &$total_registros_ref) {
    $registros_por_pagina = 3; 
    $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina_actual < 1) $pagina_actual = 1;

    $filtro_doc_resp = trim($_GET['filtro_doc_resp'] ?? '');
    $filtro_medicamento = trim($_GET['filtro_medicamento'] ?? '');
    $filtro_tipo_mov = trim($_GET['filtro_tipo_mov'] ?? 'todos');
    $filtro_fecha_inicio = trim($_GET['filtro_fecha_inicio'] ?? '');
    $filtro_fecha_fin = trim($_GET['filtro_fecha_fin'] ?? '');
    $filtro_lote = trim($_GET['filtro_lote'] ?? '');
    $filtro_vencimiento = trim($_GET['filtro_vencimiento'] ?? '');

    $sql_base_from = "
        FROM movimientos_inventario mi
        JOIN medicamentos med ON mi.id_medicamento = med.id_medicamento
        JOIN tipo_movimiento tm ON mi.id_tipo_mov = tm.id_tipo_mov
        LEFT JOIN usuarios u ON mi.id_usuario_responsable = u.doc_usu
    ";

    $sql_where_conditions = ["mi.nit_farm = :nit_farma_actual"];
    $params = [':nit_farma_actual' => $nit_farmacia_actual];

    if (!empty($filtro_doc_resp)) { $sql_where_conditions[] = "u.doc_usu LIKE :doc_resp"; $params[':doc_resp'] = "%" . $filtro_doc_resp . "%"; }
    if (!empty($filtro_medicamento)) { $sql_where_conditions[] = "med.nom_medicamento LIKE :medicamento"; $params[':medicamento'] = "%" . $filtro_medicamento . "%"; }
    if ($filtro_tipo_mov !== 'todos') { $sql_where_conditions[] = "mi.id_tipo_mov = :tipo_mov"; $params[':tipo_mov'] = $filtro_tipo_mov; }
    if (!empty($filtro_fecha_inicio)) { $sql_where_conditions[] = "DATE(mi.fecha_movimiento) >= :fecha_inicio"; $params[':fecha_inicio'] = $filtro_fecha_inicio; }
    if (!empty($filtro_fecha_fin)) { $sql_where_conditions[] = "DATE(mi.fecha_movimiento) <= :fecha_fin"; $params[':fecha_fin'] = $filtro_fecha_fin; }
    if (!empty($filtro_lote)) { $sql_where_conditions[] = "mi.lote LIKE :lote"; $params[':lote'] = "%" . $filtro_lote . "%"; }
    if (!empty($filtro_vencimiento)) { $sql_where_conditions[] = "mi.fecha_vencimiento = :vencimiento"; $params[':vencimiento'] = $filtro_vencimiento; }

    $sql_where = " WHERE " . implode(" AND ", $sql_where_conditions);
    
    $stmt_total = $con->prepare("SELECT COUNT(*) " . $sql_base_from . $sql_where);
    $stmt_total->execute($params);
    $total_registros_ref = (int)$stmt_total->fetchColumn();
    
    $total_paginas = ceil($total_registros_ref / $registros_por_pagina);
    if ($total_paginas == 0) $total_paginas = 1;
    if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;
    
    $offset = ($pagina_actual - 1) * $registros_por_pagina;
    
    $sql_final = "
        SELECT 
            mi.id_movimiento, med.nom_medicamento, tm.nom_mov, mi.id_tipo_mov, mi.cantidad, mi.lote, 
            mi.fecha_vencimiento, u.nom_usu, mi.fecha_movimiento, mi.notas
        " . $sql_base_from . $sql_where . "
        ORDER BY mi.fecha_movimiento DESC, mi.id_movimiento DESC
        LIMIT :limit OFFSET :offset_val
    ";

    $stmt_movimientos = $con->prepare($sql_final);
    foreach ($params as $key => &$val) $stmt_movimientos->bindParam($key, $val);
    $stmt_movimientos->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt_movimientos->bindParam(':offset_val', $offset, PDO::PARAM_INT);
    $stmt_movimientos->execute();
    $lista_movimientos = $stmt_movimientos->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    if (!empty($lista_movimientos)):
        foreach ($lista_movimientos as $mov):
            $clase_badge = 'bg-secondary';
            if (in_array($mov['id_tipo_mov'], [1, 3, 5])) $clase_badge = 'bg-success';
            if (in_array($mov['id_tipo_mov'], [2, 4])) $clase_badge = 'bg-danger';
            ?>
            <tr>
                <td><?php echo htmlspecialchars($mov['id_movimiento']); ?></td>
                <td><strong><?php echo htmlspecialchars($mov['nom_medicamento']); ?></strong><br><small class="text-muted">Lote: <?php echo htmlspecialchars($mov['lote'] ?? 'N/A'); ?></small></td>
                <td><span class="badge <?php echo $clase_badge; ?>"><?php echo htmlspecialchars($mov['nom_mov']); ?></span></td>
                <td><strong><?php echo htmlspecialchars($mov['cantidad']); ?></strong></td>
                <td><?php echo $mov['fecha_vencimiento'] ? htmlspecialchars(date('d/m/Y', strtotime($mov['fecha_vencimiento']))) : 'N/A'; ?></td>
                <td><?php echo htmlspecialchars($mov['nom_usu'] ?? 'Sistema'); ?></td>
                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($mov['fecha_movimiento']))); ?></td>
                <td class="acciones-tabla">
                    <button type="button" class="btn btn-primary btn-sm btn-ver-detalles" data-id-movimiento="<?php echo $mov['id_movimiento']; ?>" title="Ver Detalles"><i class="bi bi-eye-fill"></i> Ver</button>
                </td>
            </tr>
        <?php endforeach;
    else: ?>
        <tr><td colspan="8" class="text-center p-4">No se encontraron movimientos que coincidan con los filtros.</td></tr>
    <?php endif;
    $filas_html = ob_get_clean();

    ob_start();
    if ($total_registros_ref > 0): ?>
        <nav aria-label="Paginación de movimientos">
            <ul class="pagination pagination-sm justify-content-center">
                <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="#" data-pagina="<?php echo $pagina_actual - 1; ?>"><</a></li>
                <li class="page-item active" aria-current="page"><span class="page-link" style="background-color: #0d6efd; border-color: #0d6efd;"><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></span></li>
                <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>"><a class="page-link" href="#" data-pagina="<?php echo $pagina_actual + 1; ?>">></a></li>
            </ul>
        </nav>
    <?php endif;
    $paginacion_html = ob_get_clean();

    return ['filas' => $filas_html, 'paginacion' => $paginacion_html, 'total_registros' => $total_registros_ref];
}

$db = new database();
$con = $db->conectar();
$total_registros = 0;
$nit_farmacia_actual = $_SESSION['nit_farmacia_asignada_actual'] ?? null;
$nombre_farmacia_asignada = $_SESSION['nombre_farmacia_actual'] ?? 'Farmacia';

if (!$nit_farmacia_actual) { die("Error: No se ha identificado la farmacia."); }

$stmt_tipos = $con->query("SELECT id_tipo_mov, nom_mov FROM tipo_movimiento ORDER BY nom_mov");
$tipos_movimiento = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['ajax_search'])) {
    header('Content-Type: application/json');
    $contenido = generarContenidoMovimientos($con, $nit_farmacia_actual, $total_registros);
    echo json_encode($contenido);
    exit;
}

$contenido_inicial = generarContenidoMovimientos($con, $nit_farmacia_actual, $total_registros);
$filas_html = $contenido_inicial['filas'];
$paginacion_html = $contenido_inicial['paginacion'];
$total_registros = $contenido_inicial['total_registros'];
$pageTitle = "Historial de Movimientos de Inventario";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
    <link rel="icon" type="image/png" href="../../img/loguito.png">
    <style>
        .vista-datos-container { display: flex; flex-direction: column; flex-grow: 1; }
        .table-responsive { flex-grow: 1; }
        .form-row-actions { display: flex; align-items: flex-end; gap: 0.5rem; }
        .modal-body .alert { background-color: #e9ecef; border-color: #ced4da; }
        .is-invalid-date {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
        .filtros-tabla-container .form-label { font-size: 0.8rem; margin-bottom: 0.2rem; display: block;}
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <h3 class="titulo-lista-tabla mb-3">Historial de Movimientos en: <strong><?php echo htmlspecialchars($nombre_farmacia_asignada); ?></strong></h3>
                
                <form id="formFiltros" class="mb-4 filtros-tabla-container" onsubmit="return false;">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg"><label for="filtro_medicamento" class="form-label"><i class="bi bi-capsule"></i> Medicamento:</label><input type="text" id="filtro_medicamento" name="filtro_medicamento" class="form-control form-control-sm" placeholder="Buscar..."></div>
                        <div class="col-lg-2"><label for="filtro_tipo_mov" class="form-label"><i class="bi bi-arrows-move"></i> Tipo Movimiento:</label><select id="filtro_tipo_mov" name="filtro_tipo_mov" class="form-select form-select-sm"><option value="todos">Todos</option><?php foreach ($tipos_movimiento as $tipo) echo "<option value='{$tipo['id_tipo_mov']}'>".htmlspecialchars($tipo['nom_mov'])."</option>"; ?></select></div>
                        <div class="col-lg"><label for="filtro_doc_resp" class="form-label"><i class="bi bi-person-badge"></i> Doc. Responsable:</label><input type="text" id="filtro_doc_resp" name="filtro_doc_resp" class="form-control form-control-sm" placeholder="Buscar..."></div>
                        <div class="col-lg"><label for="filtro_lote" class="form-label"><i class="bi bi-box-seam"></i> Lote:</label><input type="text" id="filtro_lote" name="filtro_lote" class="form-control form-control-sm" placeholder="Buscar..."></div>
                        <div class="col-lg-auto form-row-actions">
                            <button id="btnMasFiltros" class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#modalFiltrosAvanzados">
                                Más Filtros <span id="badge-filtros-avanzados" class="badge bg-danger ms-1 d-none">!</span>
                            </button>
                            <button id="btnLimpiar" type="button" class="btn btn-sm btn-outline-secondary">Limpiar</button>
                            <button id="btnGenerarReporte" type="button" class="btn btn-sm btn-success" <?php if ($total_registros === 0) echo 'disabled'; ?>><i class="bi bi-file-earmark-excel-fill"></i> Reporte</button>
                        </div>
                    </div>

                    <input type="date" id="filtro_fecha_inicio" name="filtro_fecha_inicio" class="d-none">
                    <input type="date" id="filtro_fecha_fin" name="filtro_fecha_fin" class="d-none">
                    <input type="date" id="filtro_vencimiento" name="filtro_vencimiento" class="d-none">
                </form>

                <div class="table-responsive">
                    <table class="tabla-admin-mejorada">
                        <thead><tr><th>ID</th><th>Medicamento / Lote</th><th>Tipo</th><th>Cant.</th><th>F. Vencimiento</th><th>Responsable</th><th>F. Movimiento</th><th class="columna-acciones-fija">Acciones</th></tr></thead>
                        <tbody id="movimientos-tbody"><?php echo $filas_html; ?></tbody>
                    </table>
                </div>
                <div id="paginacion-container" class="mt-auto pt-3"><?php echo $paginacion_html; ?></div>
            </div>
        </div>
    </main>
    
    <div class="modal fade" id="modalFiltrosAvanzados" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Filtros Avanzados por Fecha</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label for="modal_filtro_fecha_inicio" class="form-label"><i class="bi bi-calendar-range"></i> F. Mov. Inicio:</label><input type="date" id="modal_filtro_fecha_inicio" class="form-control form-control-sm" max="<?php echo date('Y-m-d'); ?>"></div>
                    <div class="mb-3"><label for="modal_filtro_fecha_fin" class="form-label"><i class="bi bi-calendar-range-fill"></i> F. Mov. Fin:</label><input type="date" id="modal_filtro_fecha_fin" class="form-control form-control-sm" max="<?php echo date('Y-m-d'); ?>"></div>
                    <div class="mb-3"><label for="modal_filtro_vencimiento" class="form-label"><i class="bi bi-calendar-x"></i> F. Vencimiento:</label><input type="date" id="modal_filtro_vencimiento" class="form-control form-control-sm"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="btnLimpiarFiltrosModal">Limpiar Fechas</button>
                    <button type="button" class="btn btn-primary" id="btnAplicarFiltrosModal">Aplicar Filtros</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="modalVerDetalles" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="modalVerDetallesLabel"><i class="bi bi-file-text-fill"></i> Detalles del Movimiento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body" id="contenidoModalDetalles"><div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalConfirmarReporte" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-file-earmark-excel-fill me-2"></i>Generar Reporte de Movimientos</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><p>Se generará un reporte en Excel con los siguientes filtros aplicados:</p><div id="confirmarReporteTexto" class="alert"></div></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-success" id="btnConfirmarGeneracion"><i class="bi bi-check-circle-fill"></i> Confirmar y Generar</button></div>
            </div>
        </div>
    </div>

    <?php include '../../include/footer.php'; ?>
    <script src="../js/gestion_movi_inven.js?v=<?php echo time(); ?>"></script>
</body>
</html>