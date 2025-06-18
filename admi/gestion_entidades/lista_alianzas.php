<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once('../../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) {
    header('Location: ../inicio_sesion.php'); exit;
}
if (empty($_SESSION['csrf_token_alianzas'])) { $_SESSION['csrf_token_alianzas'] = bin2hex(random_bytes(32)); }
$csrf_token_alianzas = $_SESSION['csrf_token_alianzas'];

function obtener_datos_alianzas($con, $pagina_actual, $registros_por_pagina, $filtros) {
    $params = [];
    $query_parts = [];
    $count_parts = [];

    $filtro_tipo = $filtros['tipo'];
    $filtro_orden_fecha = $filtros['orden_fecha'] === 'asc' ? 'ASC' : 'DESC';

    if ($filtro_tipo === 'todas' || $filtro_tipo === 'farmacia') {
        $where_farm = [];
        $sql_farm_base = "SELECT def.id_eps_farm AS id_alianza, eps_farm.nombre_eps, f.nom_farm AS nombre_entidad_aliada, 'Farmacia' AS tipo_entidad_aliada, def.fecha, est_farm.nom_est AS estado_alianza, def.id_estado AS id_estado_alianza_val, 'detalle_eps_farm' as tabla_origen FROM detalle_eps_farm def JOIN eps eps_farm ON def.nit_eps = eps_farm.nit_eps JOIN farmacias f ON def.nit_farm = f.nit_farm JOIN estado est_farm ON def.id_estado = est_farm.id_est";
        
        if (!empty($filtros['nombre_eps'])) {
            $where_farm[] = "eps_farm.nombre_eps LIKE :nombre_eps_farm";
            $params[':nombre_eps_farm'] = "%" . $filtros['nombre_eps'] . "%";
        }
        if (!empty($filtros['entidad_aliada'])) {
            $where_farm[] = "f.nom_farm LIKE :entidad_aliada_farm";
            $params[':entidad_aliada_farm'] = "%" . $filtros['entidad_aliada'] . "%";
        }
        if ($filtros['estado'] !== '') {
            $where_farm[] = "def.id_estado = :estado_farm";
            $params[':estado_farm'] = $filtros['estado'];
        }
        if (!empty($where_farm)) { $sql_farm_base .= " WHERE " . implode(" AND ", $where_farm); }
        $query_parts[] = "($sql_farm_base)";
    }

    if ($filtro_tipo === 'todas' || $filtro_tipo === 'ips') {
        $where_ips = [];
        $sql_ips_base = "SELECT dei.id_eps_ips AS id_alianza, eps_ips.nombre_eps, i.nom_IPS AS nombre_entidad_aliada, 'IPS' AS tipo_entidad_aliada, dei.fecha, est_ips.nom_est AS estado_alianza, dei.id_estado AS id_estado_alianza_val, 'detalle_eps_ips' as tabla_origen FROM detalle_eps_ips dei JOIN eps eps_ips ON dei.nit_eps = eps_ips.nit_eps JOIN ips i ON dei.nit_ips = i.Nit_IPS JOIN estado est_ips ON dei.id_estado = est_ips.id_est";

        if (!empty($filtros['nombre_eps'])) {
            $where_ips[] = "eps_ips.nombre_eps LIKE :nombre_eps_ips";
            $params[':nombre_eps_ips'] = "%" . $filtros['nombre_eps'] . "%";
        }
        if (!empty($filtros['entidad_aliada'])) {
            $where_ips[] = "i.nom_IPS LIKE :entidad_aliada_ips";
            $params[':entidad_aliada_ips'] = "%" . $filtros['entidad_aliada'] . "%";
        }
        if ($filtros['estado'] !== '') {
            $where_ips[] = "dei.id_estado = :estado_ips";
            $params[':estado_ips'] = $filtros['estado'];
        }
        if (!empty($where_ips)) { $sql_ips_base .= " WHERE " . implode(" AND ", $where_ips); }
        $query_parts[] = "($sql_ips_base)";
    }
    
    // Total records calculation
    $count_sql_parts = [];
    if($filtro_tipo === 'todas' || $filtro_tipo === 'farmacia') {
        $count_farm_sql = "SELECT count(*) FROM detalle_eps_farm def JOIN eps eps_farm ON def.nit_eps = eps_farm.nit_eps JOIN farmacias f ON def.nit_farm = f.nit_farm" . (!empty($where_farm) ? " WHERE " . implode(" AND ", $where_farm) : "");
        $count_sql_parts[] = "($count_farm_sql)";
    }
    if($filtro_tipo === 'todas' || $filtro_tipo === 'ips') {
        $count_ips_sql = "SELECT count(*) FROM detalle_eps_ips dei JOIN eps eps_ips ON dei.nit_eps = eps_ips.nit_eps JOIN ips i ON dei.nit_ips = i.Nit_IPS" . (!empty($where_ips) ? " WHERE " . implode(" AND ", $where_ips) : "");
        $count_sql_parts[] = "($count_ips_sql)";
    }

    $total_registros = 0;
    if(!empty($count_sql_parts)) {
        $count_sql = "SELECT SUM(c) FROM (" . implode(" UNION ALL ", str_replace("count(*)", "count(*) as c", $count_sql_parts)) . ") as sub";
        $stmt_total = $con->prepare($count_sql);
        $stmt_total->execute($params);
        $total_registros = (int)$stmt_total->fetchColumn();
    }
    
    $total_paginas = $total_registros > 0 ? ceil($total_registros / $registros_por_pagina) : 1;
    if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;
    if ($pagina_actual < 1) $pagina_actual = 1;
    $offset = ($pagina_actual - 1) * $registros_por_pagina;

    $alianzas_list = [];
    if ($total_registros > 0 && !empty($query_parts)) {
        $sql_union = implode(" UNION ALL ", $query_parts);
        $sql_final = $sql_union . " ORDER BY fecha $filtro_orden_fecha, nombre_eps ASC LIMIT :limit OFFSET :offset";
        $stmt = $con->prepare($sql_final);
        foreach ($params as $key => &$val) { $stmt->bindParam($key, $val); }
        $stmt->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $alianzas_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return ['lista' => $alianzas_list, 'paginacion' => ['actual' => $pagina_actual, 'total' => $total_paginas]];
}

function render_alianzas_body($alianzas) {
    ob_start();
    if (!empty($alianzas)) {
        foreach ($alianzas as $alianza) { ?>
            <tr>
                <td><?php echo htmlspecialchars($alianza['id_alianza']); ?></td>
                <td><span class="truncate-text" style="max-width: 250px;" title="<?php echo htmlspecialchars($alianza['nombre_eps']); ?>"><?php echo htmlspecialchars($alianza['nombre_eps']); ?></span></td>
                <td><span class="truncate-text" style="max-width: 250px;" title="<?php echo htmlspecialchars($alianza['nombre_entidad_aliada']); ?>"><?php echo htmlspecialchars($alianza['nombre_entidad_aliada']); ?></span></td>
                <td><span class="tipo-alianza-texto">EPS / <?php echo htmlspecialchars($alianza['tipo_entidad_aliada']); ?></span></td>
                <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($alianza['fecha']))); ?></td>
                <td><span class="badge <?php echo $alianza['id_estado_alianza_val'] == 1 ? 'bg-success' : 'bg-danger'; ?>"><?php echo htmlspecialchars($alianza['estado_alianza']); ?></span></td>
                <td class="acciones-tabla celda-acciones-fija">
                    <button class="btn btn-info btn-sm btn-ver-detalles-alianza" data-id="<?php echo htmlspecialchars($alianza['id_alianza']); ?>" data-tabla="<?php echo htmlspecialchars($alianza['tabla_origen']); ?>" title="Ver Detalles"><i class="bi bi-eye-fill"></i><span>Ver</span></button>
                    <?php if ($alianza['id_estado_alianza_val'] == 1): ?>
                        <button class="btn btn-warning btn-sm btn-cambiar-estado" data-id="<?php echo htmlspecialchars($alianza['id_alianza']); ?>" data-tabla="<?php echo htmlspecialchars($alianza['tabla_origen']); ?>" data-accion="inactivar" title="Inactivar Alianza"><i class="bi bi-toggle-off"></i><span>Inactivar</span></button>
                    <?php else: ?>
                        <button class="btn btn-success btn-sm btn-cambiar-estado" data-id="<?php echo htmlspecialchars($alianza['id_alianza']); ?>" data-tabla="<?php echo htmlspecialchars($alianza['tabla_origen']); ?>" data-accion="activar" title="Activar Alianza"><i class="bi bi-toggle-on"></i><span>Activar</span></button>
                    <?php endif; ?>
                    <button class="btn btn-danger btn-sm btn-eliminar-alianza" data-id="<?php echo htmlspecialchars($alianza['id_alianza']); ?>" data-tabla="<?php echo htmlspecialchars($alianza['tabla_origen']); ?>" data-info="la alianza entre <?php echo htmlspecialchars($alianza['nombre_eps']); ?> y <?php echo htmlspecialchars($alianza['nombre_entidad_aliada']); ?>" title="Eliminar Alianza"><i class="bi bi-trash3"></i> <span>Eliminar</span></button>
                </td>
            </tr>
        <?php }
    } else { ?>
        <tr><td colspan="7" class="text-center">No se encontraron alianzas que coincidan con los filtros.</td></tr>
    <?php }
    return ob_get_clean();
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    $db = new database();
    $con = $db->conectar();
    $filtros_ajax = [
        'tipo' => $_GET['filtro_tipo'] ?? 'todas', 'estado' => $_GET['filtro_estado'] ?? '',
        'nombre_eps' => $_GET['filtro_nombre_eps'] ?? '', 'entidad_aliada' => $_GET['filtro_nombre_entidad_aliada'] ?? '',
        'orden_fecha' => $_GET['filtro_orden_fecha'] ?? 'desc',
    ];
    $pagina_ajax = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $resultado = obtener_datos_alianzas($con, $pagina_ajax, 4, $filtros_ajax);
    echo json_encode(['html_body' => render_alianzas_body($resultado['lista']), 'paginacion' => $resultado['paginacion']]);
    exit;
}

$db = new database(); $con = $db->conectar();
$pageTitle = "Lista de Alianzas EPS";
$filtros_iniciales = [
    'tipo' => $_GET['filtro_tipo'] ?? 'todas', 'estado' => $_GET['filtro_estado'] ?? '',
    'nombre_eps' => $_GET['filtro_nombre_eps'] ?? '', 'entidad_aliada' => $_GET['filtro_nombre_entidad_aliada'] ?? '',
    'orden_fecha' => $_GET['filtro_orden_fecha'] ?? 'desc',
];
$pagina_inicial = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$datos_iniciales = obtener_datos_alianzas($con, $pagina_inicial, 4, $filtros_iniciales);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="../img/loguito.png">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="titulo-lista-tabla m-0 border-0 p-0"><?php echo htmlspecialchars($pageTitle); ?></h3>
                    <a href="crear_alianza.php" class="btn btn-success btn-sm flex-shrink-0">
                        <i class="bi bi-plus-circle-fill"></i> Nueva Alianza
                    </a>
                </div>
                <div class="filtros-tabla-container">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-2 col-md-4">
                            <label for="filtro_tipo" class="form-label">Tipo Alianza:</label>
                            <select id="filtro_tipo" name="filtro_tipo" class="form-select form-select-sm">
                                <option value="todas">Todas</option>
                                <option value="farmacia">EPS / Farmacia</option>
                                <option value="ips">EPS / IPS</option>
                            </select>
                        </div>
                         <div class="col-lg-2 col-md-4">
                            <label for="filtro_estado" class="form-label">Estado Alianza:</label>
                            <select name="filtro_estado" id="filtro_estado" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="1">Activo</option>
                                <option value="2">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <label for="filtro_orden_fecha" class="form-label">Ordenar por Fecha:</label>
                            <select name="filtro_orden_fecha" id="filtro_orden_fecha" class="form-select form-select-sm">
                                <option value="desc">Más Recientes</option>
                                <option value="asc">Más Antiguas</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label for="filtro_nombre_eps" class="form-label">Buscar EPS:</label>
                            <input type="text" name="filtro_nombre_eps" id="filtro_nombre_eps" class="form-control form-control-sm" placeholder="Nombre de EPS...">
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label for="filtro_nombre_entidad_aliada" class="form-label">Buscar Aliada:</label>
                            <input type="text" name="filtro_nombre_entidad_aliada" id="filtro_nombre_entidad_aliada" class="form-control form-control-sm" placeholder="Nombre de Farmacia/IPS...">
                        </div>
                        <div class="col-lg-2 col-md-12 mt-md-0 mt-3 d-grid">
                            <button type="button" id="btn_limpiar_filtros" class="btn btn-sm btn-outline-secondary">Limpiar</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="tabla-admin-mejorada">
                        <thead>
                            <tr>
                                <th>ID</th><th>EPS</th><th>Entidad Aliada</th><th>Tipo</th><th>Fecha</th><th>Estado</th>
                                <th class="columna-acciones-fija">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla_alianzas_body">
                            <?php echo render_alianzas_body($datos_iniciales['lista']); ?>
                        </tbody>
                    </table>
                </div>
                <nav aria-label="Paginación de alianzas" class="mt-3 paginacion-tabla-container">
                    <ul class="pagination pagination-sm" id="paginacion_lista"></ul>
                </nav>
            </div>
        </div>
    </main>
    <?php include '../../include/footer.php'; ?>
    <div class="modal fade" id="modalVerAlianza" tabindex="-1" aria-labelledby="modalVerAlianzaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content"><div class="modal-header modal-header-alianza"><h5 class="modal-title" id="modalVerAlianzaLabel"><i class="bi bi-info-circle-fill"></i> Detalles de la Alianza</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body" id="modalVerAlianzaBody"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div></div>
        </div>
    </div>
    <div class="modal fade" id="responseModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-custom"><div class="modal-content modal-content-custom"><div class="modal-body text-center p-4"><div class="modal-icon-container"><div id="modalIcon"></div></div><h4 class="mt-3 fw-bold" id="modalTitle"></h4><p id="modalMessage" class="mt-2 text-muted"></p></div><div class="modal-footer-custom"><button type="button" class="btn btn-primary-custom" data-bs-dismiss="modal">OK</button></div></div></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script> const csrfTokenAlianzas = '<?php echo $csrf_token_alianzas; ?>'; </script>
    <script src="../js/lista_alianzas_admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>