<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

function generarFilasInventario($con, $nit_farmacia_actual, &$total_registros_ref)
{
    $filtro_tipo = trim($_GET['filtro_tipo'] ?? 'todos');
    $filtro_nombre = trim($_GET['filtro_nombre'] ?? '');
    $filtro_estado_stock = trim($_GET['filtro_stock'] ?? 'todos');
    $filtro_orden = trim($_GET['filtro_orden'] ?? 'asc');
    $filtro_codigo_barras = trim($_GET['filtro_codigo_barras'] ?? '');
    $registros_por_pagina = 4;

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

    $stmt_total = $con->prepare("SELECT COUNT(*) " . $sql_from_join);
    $stmt_total->execute($params);
    $total_registros_ref = (int)$stmt_total->fetchColumn();

    $total_paginas = ceil($total_registros_ref / $registros_por_pagina);
    $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina_actual < 1) $pagina_actual = 1;
    if ($pagina_actual > $total_paginas && $total_paginas > 0) $pagina_actual = $total_paginas;
    $offset = ($pagina_actual - 1) * $registros_por_pagina;

    $order_by = ($filtro_orden === 'desc') ? "m.nom_medicamento DESC" : "m.nom_medicamento ASC";
    $sql_final = "SELECT m.id_medicamento, m.nom_medicamento, tm.nom_tipo_medi, m.codigo_barras, i.cantidad_actual, e.nom_est, e.id_est " . $sql_from_join . " ORDER BY " . $order_by . " LIMIT :limit OFFSET :offset_val";

    $stmt_inventario = $con->prepare($sql_final);
    foreach ($params as $key => &$val) $stmt_inventario->bindParam($key, $val);
    $stmt_inventario->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt_inventario->bindParam(':offset_val', $offset, PDO::PARAM_INT);
    $stmt_inventario->execute();
    $inventario_list = $stmt_inventario->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    if (!empty($inventario_list)):
        foreach ($inventario_list as $item): ?>
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
                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEditarMedicamento" data-id-medicamento="<?php echo $item['id_medicamento']; ?>" title="Editar Medicamento"><i class="bi bi-pencil-fill"></i> Editar</button>
                </td>
            </tr>
        <?php endforeach;
    else: ?>
        <tr><td colspan="6" class="text-center p-4">No se encontraron medicamentos.</td></tr>
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
$registros_por_pagina = 4;
$total_paginas = ceil($total_registros / $registros_por_pagina);
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
if ($pagina_actual > $total_paginas && $total_paginas > 0) $pagina_actual = $total_paginas;

$pageTitle = "Inventario de Farmacia";
$nombre_farmacia_asignada = "";
if ($nit_farmacia_actual) {
    $stmt_nombre = $con->prepare("SELECT nom_farm FROM farmacias WHERE nit_farm = ?");
    $stmt_nombre->execute([$nit_farmacia_actual]);
    $nombre_farmacia_asignada = $stmt_nombre->fetchColumn();
}
$umbral_pocas_unidades = 10;
$stmt_agotados = $con->prepare("SELECT m.nom_medicamento, i.cantidad_actual FROM inventario_farmacia i JOIN medicamentos m ON i.id_medicamento = m.id_medicamento WHERE i.nit_farm = :nit_farma AND i.cantidad_actual <= :umbral ORDER BY i.cantidad_actual ASC, m.nom_medicamento ASC");
$stmt_agotados->execute([':nit_farma' => $nit_farmacia_actual, ':umbral' => $umbral_pocas_unidades]);
$medicamentos_agotados = $stmt_agotados->fetchAll(PDO::FETCH_ASSOC);

$stmt_tipos = $con->prepare("SELECT id_tip_medic, nom_tipo_medi FROM tipo_de_medicamento ORDER BY nom_tipo_medi ASC");
$stmt_tipos->execute();
$tipos_medicamento = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
    <style>
        .barcode-cell svg { height: 30px; width: auto; max-width: 150px; display: inline-block; }
        .filtros-tabla-container .form-label i { margin-right: 0.5rem; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="titulo-lista-tabla m-0">Inventario de: <strong><?php echo htmlspecialchars($nombre_farmacia_asignada); ?></strong></h3>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgotados"><i class="bi bi-exclamation-triangle-fill"></i> Ver Stock Crítico (<?php echo count($medicamentos_agotados); ?>)</button>
                        <button class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel-fill"></i> Generar Reporte</button>
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
                                <th>Cantidad Actual</th>
                                <th>Estado</th>
                                <th class="columna-acciones-fija">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="inventario-tbody">
                            <?php echo $filas_html; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_registros > 0 && $total_paginas > 1): ?>
                    <nav aria-label="Paginación de inventario" class="mt-3 paginacion-tabla-container">
                        <ul class="pagination pagination-sm">
                            <?php $query_params = http_build_query(array_filter(['filtro_tipo' => $_GET['filtro_tipo'] ?? 'todos', 'filtro_stock' => $_GET['filtro_stock'] ?? 'todos','filtro_orden' => $_GET['filtro_orden'] ?? 'asc', 'filtro_nombre' => $_GET['filtro_nombre'] ?? '', 'filtro_codigo_barras' => $_GET['filtro_codigo_barras'] ?? ''])); ?>
                            <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>&<?php echo $query_params; ?>"><i class="bi bi-chevron-left"></i></a>
                            </li>
                            <li class="page-item active" id="page-number-container">
                                <span class="page-link page-number-display" title="Click/Enter para ir a pág."><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></span>
                                <input type="number" class="form-control form-control-sm page-number-input-field" value="<?php echo $pagina_actual; ?>" min="1" max="<?php echo $total_paginas; ?>" style="display: none;" data-total="<?php echo $total_paginas; ?>">
                            </li>
                            <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>&<?php echo $query_params; ?>"><i class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <div class="modal fade" id="modalAgotados" tabindex="-1" aria-labelledby="modalAgotadosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgotadosLabel"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Reporte de Stock Crítico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Lista de medicamentos con <?php echo $umbral_pocas_unidades; ?> o menos unidades.</p>
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Medicamento</th>
                                <th>Cantidad Restante</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($medicamentos_agotados)):
                                foreach($medicamentos_agotados as $med_agotado): ?>
                                    <tr class="<?php echo ($med_agotado['cantidad_actual'] == 0) ? 'table-danger' : 'table-warning'; ?>">
                                        <td><?php echo htmlspecialchars($med_agotado['nom_medicamento']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($med_agotado['cantidad_actual']); ?></strong></td>
                                    </tr>
                                <?php endforeach;
                            else: ?>
                                <tr><td colspan="2" class="text-center p-3">¡Excelente! No hay medicamentos con stock crítico.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalDetallesMedicamento" tabindex="-1" aria-labelledby="modalDetallesLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetallesLabel"><i class="bi bi-capsule-pill me-2"></i>Detalles del Medicamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="contenidoModalDetalles">
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalEditarMedicamento" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="formEditarMedicamento">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditarLabel"><i class="bi bi-pencil-square me-2"></i>Editar Medicamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="cuerpoModalEditar">
                        <div class="text-center p-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" name="accion" value="actualizar_detalles">
                        <input type="hidden" id="edit_id_medicamento" name="id_medicamento">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" disabled>Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../../include/footer.php'; ?>
    <script src="../includes_farm/JsBarcode.all.min.js"></script>
    <script src="../js/gestion_inventario.js?v=<?php echo time(); ?>"></script>
</body>
</html>