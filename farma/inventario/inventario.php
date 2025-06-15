<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

// (La función generarFilasInventario no cambia, la omito por brevedad)
function generarFilasInventario($con, $nit_farmacia_actual, &$total_registros_ref)
{
    // ... tu función existente sin cambios ...
    $filtro_tipo = trim($_GET['filtro_tipo'] ?? 'todos');
    $filtro_nombre = trim($_GET['filtro_nombre'] ?? '');
    $filtro_estado_stock = trim($_GET['filtro_stock'] ?? 'todos');
    $filtro_orden = trim($_GET['filtro_orden'] ?? 'asc');
    $filtro_codigo_barras = trim($_GET['filtro_codigo_barras'] ?? '');
    $registros_por_pagina = 15;

    $params = [':nit_farma' => $nit_farmacia_actual];
    $sql_where_conditions = [];
    if ($filtro_tipo !== 'todos') {
        $sql_where_conditions[] = "m.id_tipo_medic = :id_tipo";
        $params[':id_tipo'] = $filtro_tipo;
    }
    if (!empty($filtro_nombre)) {
        $sql_where_conditions[] = "m.nom_medicamento LIKE :nombre_medic";
        $params[':nombre_medic'] = "%" . $filtro_nombre . "%";
    }
    if (!empty($filtro_codigo_barras)) {
        $sql_where_conditions[] = "m.codigo_barras LIKE :codigo_barras";
        $params[':codigo_barras'] = "%" . $filtro_codigo_barras . "%";
    }
    if ($filtro_estado_stock !== 'todos') {
        if ($filtro_estado_stock === 'disponible') $sql_where_conditions[] = "i.id_estado = 13";
        elseif ($filtro_estado_stock === 'pocas_unidades') $sql_where_conditions[] = "i.id_estado = 14";
        elseif ($filtro_estado_stock === 'no_disponible') $sql_where_conditions[] = "i.id_estado = 15";
    }

    $sql_from_join = "FROM inventario_farmacia i JOIN medicamentos m ON i.id_medicamento = m.id_medicamento JOIN tipo_de_medicamento tm ON m.id_tipo_medic = tm.id_tip_medic JOIN estado e ON i.id_estado = e.id_est WHERE i.nit_farm = :nit_farma";
    if (!empty($sql_where_conditions)) {
        $sql_from_join .= " AND " . implode(" AND ", $sql_where_conditions);
    }

    $stmt_total = $con->prepare("SELECT COUNT(DISTINCT m.id_medicamento) " . $sql_from_join);
    $stmt_total->execute($params);
    $total_registros_ref = (int)$stmt_total->fetchColumn();

    $total_paginas = ceil($total_registros_ref / $registros_por_pagina);
    $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina_actual < 1) $pagina_actual = 1;
    if ($pagina_actual > $total_paginas && $total_paginas > 0) $pagina_actual = $total_paginas;
    $offset = ($pagina_actual - 1) * $registros_por_pagina;

    $order_by = ($filtro_orden === 'desc') ? "m.nom_medicamento DESC" : "m.nom_medicamento ASC";
    $sql_final = "SELECT m.id_medicamento, m.nom_medicamento, tm.nom_tipo_medi, m.codigo_barras, i.cantidad_actual, e.nom_est, e.id_est " . $sql_from_join . " GROUP BY m.id_medicamento, tm.nom_tipo_medi, m.codigo_barras, i.cantidad_actual, e.nom_est, e.id_est ORDER BY " . $order_by . " LIMIT :limit OFFSET :offset_val";

    $stmt_inventario = $con->prepare($sql_final);
    foreach ($params as $key => &$val) $stmt_inventario->bindParam($key, $val);
    $stmt_inventario->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt_inventario->bindParam(':offset_val', $offset, PDO::PARAM_INT);
    $stmt_inventario->execute();
    $inventario_list = $stmt_inventario->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    if (!empty($inventario_list)):
        $stock_por_lote_sql = "SUM(CASE WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad WHEN id_tipo_mov IN (2, 4) THEN -cantidad ELSE 0 END)";
        $sql_lotes_base = "SELECT lote FROM movimientos_inventario WHERE id_medicamento = :id_medicamento AND nit_farm = :nit_farm GROUP BY lote, fecha_vencimiento HAVING $stock_por_lote_sql > 0";
        $stmt_lotes = $con->prepare($sql_lotes_base);

        foreach ($inventario_list as $item): 
            $lotes_activos = [];
            if ($item['cantidad_actual'] > 0) {
                $stmt_lotes->execute([':id_medicamento' => $item['id_medicamento'], ':nit_farm' => $nit_farmacia_actual]);
                $lotes_activos = $stmt_lotes->fetchAll(PDO::FETCH_ASSOC);
            }
            $num_lotes = count($lotes_activos);
            ?>
            <tr>
                <td><?php echo htmlspecialchars($item['nom_medicamento']); ?></td>
                <td><?php echo htmlspecialchars($item['nom_tipo_medi']); ?></td>
                <td class="barcode-cell">
                    <?php if (!empty($item['codigo_barras'])): ?>
                        <svg class="barcode" jsbarcode-value="<?php echo htmlspecialchars($item['codigo_barras']); ?>"></svg>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
                <td><strong><?php echo htmlspecialchars($item['cantidad_actual']); ?></strong></td>
                <td class="col-lotes">
                    <?php if ($num_lotes === 0): ?>
                        <span class="badge bg-secondary">Sin Lotes</span>
                    <?php elseif ($num_lotes === 1): ?>
                        <button class="btn btn-outline-dark btn-sm btn-ver-detalle-lote" data-id-medicamento="<?php echo $item['id_medicamento']; ?>" data-lote="<?php echo htmlspecialchars($lotes_activos[0]['lote']); ?>">
                           <i class="bi bi-eye"></i> <?php echo htmlspecialchars($lotes_activos[0]['lote']); ?>
                        </button>
                    <?php else: ?>
                        <button class="btn btn-outline-primary btn-sm btn-ver-lotes" data-id-medicamento="<?php echo $item['id_medicamento']; ?>">
                           <?php echo $num_lotes; ?> Lotes...
                        </button>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    $clase_badge = 'bg-secondary';
                    if ($item['id_est'] == 13) $clase_badge = 'bg-success';
                    if ($item['id_est'] == 14) $clase_badge = 'bg-warning text-dark';
                    if ($item['id_est'] == 15) $clase_badge = 'bg-danger';
                    ?>
                    <span class="badge <?php echo $clase_badge; ?>"><?php echo htmlspecialchars($item['nom_est']); ?></span>
                </td>
                <td class="acciones-tabla">
                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetallesMedicamento" data-id-medicamento="<?php echo $item['id_medicamento']; ?>" title="Ver Detalles Completos"><i class="bi bi-info-circle-fill"></i> Ver</button>
                </td>
            </tr>
        <?php endforeach;
    else: ?>
        <tr><td colspan="7" class="text-center p-4">No se encontraron medicamentos que coincidan con los filtros.</td></tr>
    <?php endif;
    return ob_get_clean();
}


if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['doc_usu']) || $_SESSION['id_rol'] != 3) {
    header('Location: ../../inicio_sesion.php?error=no_permiso');
    exit;
}
$nit_farmacia_actual = $_SESSION['nit_farmacia_asignada_actual'] ?? '';

if (isset($_GET['ajax_search'])) {
    $total_registros_ajax = 0;
    echo generarFilasInventario($con, $nit_farmacia_actual, $total_registros_ajax);
    exit;
}

$total_registros = 0;
$filas_html = generarFilasInventario($con, $nit_farmacia_actual, $total_registros);

$pageTitle = "Inventario de Farmacia";
$nombre_farmacia_asignada = "";
$count_stock_bajo = 0;
$count_por_vencer = 0;
$count_vencidos = 0;

if ($nit_farmacia_actual) {
    $stmt_nombre = $con->prepare("SELECT nom_farm FROM farmacias WHERE nit_farm = ?");
    $stmt_nombre->execute([$nit_farmacia_actual]);
    $nombre_farmacia_asignada = $stmt_nombre->fetchColumn();

    $sql_stock = "SELECT COUNT(*) FROM inventario_farmacia WHERE nit_farm = :nit AND cantidad_actual <= 10";
    $stmt_stock = $con->prepare($sql_stock);
    $stmt_stock->execute(['nit' => $nit_farmacia_actual]);
    $count_stock_bajo = $stmt_stock->fetchColumn();

    $stock_por_lote_sql = "(SELECT SUM(CASE WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad WHEN id_tipo_mov IN (2, 4) THEN -cantidad ELSE 0 END) FROM movimientos_inventario WHERE lote = mi.lote AND id_medicamento = mi.id_medicamento AND nit_farm = mi.nit_farm)";

    $sql_por_vencer = "SELECT COUNT(DISTINCT mi.lote, mi.id_medicamento) FROM movimientos_inventario mi WHERE mi.nit_farm = :nit AND mi.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND $stock_por_lote_sql > 0";
    $stmt_por_vencer = $con->prepare($sql_por_vencer);
    $stmt_por_vencer->execute(['nit' => $nit_farmacia_actual]);
    $count_por_vencer = $stmt_por_vencer->fetchColumn();
    
    $sql_vencidos = "SELECT COUNT(DISTINCT mi.lote, mi.id_medicamento) FROM movimientos_inventario mi WHERE mi.nit_farm = :nit AND mi.fecha_vencimiento < CURDATE() AND $stock_por_lote_sql > 0";
    $stmt_vencidos = $con->prepare($sql_vencidos);
    $stmt_vencidos->execute(['nit' => $nit_farmacia_actual]);
    $count_vencidos = $stmt_vencidos->fetchColumn();
}

$stmt_tipos = $con->prepare("SELECT id_tip_medic, nom_tipo_medi FROM tipo_de_medicamento ORDER BY nom_tipo_medi ASC");
$stmt_tipos->execute();
$tipos_medicamento = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="../../img/loguito.png">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
    <style>
        .barcode-cell svg { height: 30px; width: auto; max-width: 150px; display: inline-block; }
        .btn .badge { margin-left: 8px; }
        .has-alerts { animation: pulse 1.5s infinite; }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
        .btn-warning.has-alerts { animation-name: pulse-warning; }
        @keyframes pulse-warning {
            0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <div class="d-flex justify-content-between align-items-center mb-3 header-form-responsive">
                    <h3 class="titulo-lista-tabla m-0">Inventario de: <strong><?php echo htmlspecialchars($nombre_farmacia_asignada); ?></strong></h3>
                    <div class="d-flex align-items-center gap-2 header-alert-buttons">
                        <button type="button" class="btn btn-secondary btn-sm <?php echo ($count_stock_bajo > 0) ? 'has-alerts' : ''; ?>" data-bs-toggle="modal" data-bs-target="#alertasModal" data-alert-type="stock-bajo">
                            <i class="bi bi-battery-charging"></i><span class="btn-text"> Stock Bajo</span><span class="badge bg-dark rounded-pill"><?php echo $count_stock_bajo; ?></span>
                        </button>
                        <button type="button" class="btn btn-warning btn-sm <?php echo ($count_por_vencer > 0) ? 'has-alerts' : ''; ?>" data-bs-toggle="modal" data-bs-target="#alertasModal" data-alert-type="por-vencer">
                            <i class="bi bi-hourglass-split"></i><span class="btn-text"> Próximos a Vencer</span><span class="badge bg-dark rounded-pill"><?php echo $count_por_vencer; ?></span>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm <?php echo ($count_vencidos > 0) ? 'has-alerts' : ''; ?>" data-bs-toggle="modal" data-bs-target="#alertasModal" data-alert-type="vencidos">
                            <i class="bi bi-calendar-x-fill"></i><span class="btn-text"> Vencidos</span><span class="badge bg-light text-dark rounded-pill"><?php echo $count_vencidos; ?></span>
                        </button>
                    </div>
                </div>
                
                <form id="formFiltros" class="mb-4 filtros-tabla-container">
                     <div class="row g-2 align-items-end">
                        <div class="col-lg-2 col-md-6">
                            <label for="filtro_tipo" class="form-label"><i class="bi bi-tag"></i>Tipo Medicamento:</label>
                            <select id="filtro_tipo" name="filtro_tipo" class="form-select form-select-sm">
                                <option value="todos">Todos</option>
                                <?php foreach ($tipos_medicamento as $tipo): ?>
                                    <option value="<?php echo $tipo['id_tip_medic']; ?>"><?php echo htmlspecialchars($tipo['nom_tipo_medi']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label for="filtro_stock" class="form-label"><i class="bi bi-check-circle-fill"></i>Estado Stock:</label>
                            <select name="filtro_stock" id="filtro_stock" class="form-select form-select-sm">
                                <option value="todos">Todos</option>
                                <option value="disponible">Disponible</option>
                                <option value="pocas_unidades">Pocas Unidades</option>
                                <option value="no_disponible">No Disponible</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label for="filtro_orden" class="form-label"><i class="bi bi-sort-alpha-down"></i>Ordenar por Nombre:</label>
                            <select name="filtro_orden" id="filtro_orden" class="form-select form-select-sm">
                                <option value="asc">A - Z</option>
                                <option value="desc">Z - A</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label for="filtro_nombre" class="form-label"><i class="bi bi-search"></i>Buscar por Nombre:</label>
                            <input type="text" name="filtro_nombre" id="filtro_nombre" class="form-control form-control-sm" placeholder="Nombre..." autocomplete="off">
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="filtro_codigo_barras" class="form-label"><i class="bi bi-upc-scan"></i>Buscar por Código:</label>
                            <input type="text" name="filtro_codigo_barras" id="filtro_codigo_barras" class="form-control form-control-sm" placeholder="Escanear o escribir código..." autocomplete="off">
                        </div>
                        <div class="col-lg-1 col-md-12">
                            <a href="inventario.php" class="btn btn-sm btn-outline-secondary w-100">Limpiar</a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="tabla-admin-mejorada">
                        <thead>
                            <tr>
                                <th>Medicamento</th>
                                <th>Tipo</th>
                                <th>Código de Barras</th>
                                <th>Cantidad Total</th>
                                <th>Lotes</th>
                                <th>Estado</th>
                                <th class="columna-acciones-fija">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="inventario-tbody">
                            <?php echo $filas_html; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div id="modal-lotes-placeholder"></div>
    <div id="modal-secundario-placeholder"></div>
    
    <div class="modal fade" id="alertasModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="alertasModalLabel">Detalles de la Alerta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body" id="alertasModalBody"></div>
                <div class="modal-footer" id="alertasModalFooter"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalDetallesMedicamento" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-capsule-pill me-2"></i>Detalles del Medicamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body" id="contenidoModalDetalles"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
            </div>
        </div>
    </div>
    
    <?php include '../../include/footer.php'; ?>
    <script src="../js/gestion_inventario.js?v=<?php echo time(); ?>"></script>
    <script src="../js/alertas_dashboard.js?v=<?php echo time(); ?>"></script>
    <script src="../includes_farm/JsBarcode.all.min.js"></script>
</body>
</html>