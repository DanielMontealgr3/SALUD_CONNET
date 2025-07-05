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
    $filtro_orden_fecha = $filtros['orden_fecha'] === 'asc' ? 'ASC' : 'DESC';

    $build_where = function($tipo_entidad, &$params_ref) use ($filtros) {
        $where = [];
        $suffix = "_" . $tipo_entidad . rand(100, 999);

        if (!empty($filtros['nombre_eps'])) {
            $where[] = "eps.nombre_eps LIKE :nombre_eps" . $suffix;
            $params_ref[':nombre_eps' . $suffix] = "%" . $filtros['nombre_eps'] . "%";
        }
        if (!empty($filtros['entidad_aliada'])) {
            $where[] = ($tipo_entidad === 'farmacia' ? "f.nom_farm" : "i.nom_IPS") . " LIKE :entidad_aliada" . $suffix;
            $params_ref[':entidad_aliada' . $suffix] = "%" . $filtros['entidad_aliada'] . "%";
        }
        if ($filtros['estado'] !== '') {
            $where[] = ($tipo_entidad === 'farmacia' ? "def.id_estado" : "dei.id_estado") . " = :estado" . $suffix;
            $params_ref[':estado' . $suffix] = $filtros['estado'];
        }
        
        $fecha_col = $tipo_entidad === 'farmacia' ? "def.fecha" : "dei.fecha";
        if (!empty($filtros['fecha_exacta'])) {
            $where[] = "DATE($fecha_col) = :fecha_exacta" . $suffix;
            $params_ref[':fecha_exacta' . $suffix] = $filtros['fecha_exacta'];
        } else {
            if (!empty($filtros['fecha_inicio'])) {
                $where[] = "$fecha_col >= :fecha_inicio" . $suffix;
                $params_ref[':fecha_inicio' . $suffix] = $filtros['fecha_inicio'];
            }
            if (!empty($filtros['fecha_fin'])) {
                $where[] = "$fecha_col <= :fecha_fin" . $suffix;
                $params_ref[':fecha_fin' . $suffix] = $filtros['fecha_fin'];
            }
        }
        return !empty($where) ? " WHERE " . implode(" AND ", $where) : "";
    };
    
    $full_query_parts = [];
    if ($filtros['tipo'] === 'todas' || $filtros['tipo'] === 'farmacia') {
        $sql_farm_base = "SELECT def.id_eps_farm, eps.nombre_eps, f.nom_farm, 'Farmacia', def.fecha, est.nom_est, def.id_estado, 'detalle_eps_farm' FROM detalle_eps_farm def JOIN eps ON def.nit_eps = eps.nit_eps JOIN farmacias f ON def.nit_farm = f.nit_farm JOIN estado est ON def.id_estado = est.id_est" . $build_where('farmacia', $params);
        $full_query_parts[] = $sql_farm_base;
    }

    if ($filtros['tipo'] === 'todas' || $filtros['tipo'] === 'ips') {
        $sql_ips_base = "SELECT dei.id_eps_ips, eps.nombre_eps, i.nom_IPS, 'IPS', dei.fecha, est.nom_est, dei.id_estado, 'detalle_eps_ips' FROM detalle_eps_ips dei JOIN eps ON dei.nit_eps = eps.nit_eps JOIN ips i ON dei.nit_ips = i.Nit_IPS JOIN estado est ON dei.id_estado = est.id_est" . $build_where('ips', $params);
        $full_query_parts[] = $sql_ips_base;
    }
    
    $total_registros = 0;
    if (!empty($full_query_parts)) {
        $count_sql = "SELECT COUNT(*) FROM (" . implode(" UNION ALL ", $full_query_parts) . ") as subquery";
        $stmt_total = $con->prepare($count_sql);
        $stmt_total->execute($params);
        $total_registros = (int)$stmt_total->fetchColumn();
    }
    
    $total_paginas = $total_registros > 0 ? ceil($total_registros / $registros_por_pagina) : 1;
    $pagina_actual = min($pagina_actual, $total_paginas);
    if ($pagina_actual < 1) $pagina_actual = 1;
    $offset = ($pagina_actual - 1) * $registros_por_pagina;

    $alianzas_list = [];
    if ($total_registros > 0 && !empty($full_query_parts)) {
        $sql_final = "SELECT * FROM (" . implode(" UNION ALL ", $full_query_parts) . ") as final_query ORDER BY 5 $filtro_orden_fecha, 2 ASC LIMIT :limit OFFSET :offset";
        $stmt = $con->prepare($sql_final);
        foreach ($params as $key => &$val) { $stmt->bindParam($key, $val); }
        $stmt->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $alianzas_list = $stmt->fetchAll(PDO::FETCH_NUM);
    }
    
    return ['lista' => $alianzas_list, 'paginacion' => ['actual' => $pagina_actual, 'total' => $total_paginas, 'total_registros' => $total_registros]];
}

function render_alianzas_body($alianzas) {
    ob_start();
    if (!empty($alianzas)) {
        foreach ($alianzas as $alianza) { ?>
            <tr>
                <td><?php echo htmlspecialchars($alianza[0]); ?></td>
                <td><span class="truncate-text" title="<?php echo htmlspecialchars($alianza[1]); ?>"><?php echo htmlspecialchars($alianza[1]); ?></span></td>
                <td><span class="truncate-text" title="<?php echo htmlspecialchars($alianza[2]); ?>"><?php echo htmlspecialchars($alianza[2]); ?></span></td>
                <td><span class="tipo-alianza-texto">EPS / <?php echo htmlspecialchars($alianza[3]); ?></span></td>
                <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($alianza[4]))); ?></td>
                <td><span class="badge <?php echo $alianza[6] == 1 ? 'bg-success' : 'bg-danger'; ?>"><?php echo htmlspecialchars($alianza[5]); ?></span></td>
                <td class="acciones-tabla celda-acciones-fija">
                    <button class="btn btn-info btn-sm btn-ver-detalles-alianza" data-id="<?php echo htmlspecialchars($alianza[0]); ?>" data-tabla="<?php echo htmlspecialchars($alianza[7]); ?>" title="Ver Detalles"><i class="bi bi-eye-fill"></i><span>Ver</span></button>
                    <?php if ($alianza[6] == 1): ?>
                        <button class="btn btn-warning btn-sm btn-cambiar-estado" data-id="<?php echo htmlspecialchars($alianza[0]); ?>" data-tabla="<?php echo htmlspecialchars($alianza[7]); ?>" data-accion="inactivar" title="Inactivar Alianza"><i class="bi bi-toggle-off"></i><span>Inactivar</span></button>
                    <?php else: ?>
                        <button class="btn btn-success btn-sm btn-cambiar-estado" data-id="<?php echo htmlspecialchars($alianza[0]); ?>" data-tabla="<?php echo htmlspecialchars($alianza[7]); ?>" data-accion="activar" title="Activar Alianza"><i class="bi bi-toggle-on"></i><span>Activar</span></button>
                    <?php endif; ?>
                    <button class="btn btn-danger btn-sm btn-eliminar-alianza" data-id="<?php echo htmlspecialchars($alianza[0]); ?>" data-tabla="<?php echo htmlspecialchars($alianza[7]); ?>" data-info="la alianza entre <?php echo htmlspecialchars($alianza[1]); ?> y <?php echo htmlspecialchars($alianza[2]); ?>" title="Eliminar Alianza"><i class="bi bi-trash3"></i> <span>Eliminar</span></button>
                </td>
            </tr>
        <?php }
    } else { ?>
        <tr><td colspan="7" class="text-center p-4">No se encontraron alianzas que coincidan con los filtros.</td></tr>
    <?php }
    return ob_get_clean();
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    $db = new database(); $con = $db->conectar();
    $filtros_ajax = [
        'tipo' => $_GET['filtro_tipo'] ?? 'todas', 'estado' => $_GET['filtro_estado'] ?? '',
        'nombre_eps' => $_GET['filtro_nombre_eps'] ?? '', 'entidad_aliada' => $_GET['filtro_nombre_entidad_aliada'] ?? '',
        'orden_fecha' => $_GET['filtro_orden_fecha'] ?? 'desc', 'fecha_inicio' => $_GET['filtro_fecha_inicio'] ?? '',
        'fecha_fin' => $_GET['filtro_fecha_fin'] ?? '', 'fecha_exacta' => $_GET['filtro_fecha_exacta'] ?? '',
    ];
    $pagina_ajax = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $resultado = obtener_datos_alianzas($con, $pagina_ajax, 4, $filtros_ajax);
    echo json_encode(['html_body' => render_alianzas_body($resultado['lista']), 'paginacion' => $resultado['paginacion']]);
    exit;
}

$db = new database(); $con = $db->conectar();
$pageTitle = "Lista de Alianzas EPS";
$datos_iniciales = obtener_datos_alianzas($con, 1, 4, [
    'tipo' => 'todas', 'estado' => '', 'nombre_eps' => '', 'entidad_aliada' => '',
    'orden_fecha' => 'desc', 'fecha_inicio' => '', 'fecha_fin' => '', 'fecha_exacta' => ''
]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../img/loguito.png">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
    <style>.filtro-avanzado-indicator{position:absolute;top:-5px;right:-5px;width:15px;height:15px;background-color:red;color:white;border-radius:50%;font-size:10px;display:flex;align-items:center;justify-content:center;font-weight:bold;}</style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="titulo-lista-tabla m-0 border-0 p-0"><?php echo htmlspecialchars($pageTitle); ?></h3>
                    <a href="crear_alianza.php" class="btn btn-success btn-sm flex-shrink-0"><i class="bi bi-plus-circle-fill"></i> Nueva Alianza</a>
                </div>
                <div class="filtros-tabla-container mb-4">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg col-md-4"><label for="filtro_tipo" class="form-label">Tipo Alianza:</label><select id="filtro_tipo" class="form-select form-select-sm"><option value="todas">Todas</option><option value="farmacia">EPS / Farmacia</option><option value="ips">EPS / IPS</option></select></div>
                        <div class="col-lg col-md-4"><label for="filtro_estado" class="form-label">Estado:</label><select name="filtro_estado" id="filtro_estado" class="form-select form-select-sm"><option value="">Todos</option><option value="1">Activo</option><option value="2">Inactivo</option></select></div>
                        <div class="col-lg col-md-4"><label for="filtro_orden_fecha" class="form-label">Ordenar Fecha:</label><select name="filtro_orden_fecha" id="filtro_orden_fecha" class="form-select form-select-sm"><option value="desc">Más Recientes</option><option value="asc">Más Antiguas</option></select></div>
                        <div class="col-lg col-md-6"><label for="filtro_nombre_eps" class="form-label">Buscar EPS:</label><input type="text" id="filtro_nombre_eps" class="form-control form-control-sm" placeholder="Nombre de EPS..."></div>
                        <div class="col-lg col-md-6"><label for="filtro_nombre_entidad_aliada" class="form-label">Buscar Aliada:</label><input type="text" id="filtro_nombre_entidad_aliada" class="form-control form-control-sm" placeholder="Nombre de Farmacia/IPS..."></div>
                        <div class="col-lg-auto col-md-12">
                            <div class="d-flex gap-2">
                                <button type="button" id="btn_mas_filtros" class="btn btn-sm btn-secondary position-relative" data-bs-toggle="modal" data-bs-target="#modalMasFiltros">Más Filtros<span id="filtro_avanzado_indicator" class="filtro-avanzado-indicator" style="display: none;">!</span></button>
                                <button type="button" id="btn_limpiar_filtros" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eraser"></i> Limpiar</button>
                                <button type="button" id="btn_generar_reporte" class="btn btn-sm btn-success" <?php if ($datos_iniciales['paginacion']['total_registros'] === 0) echo 'disabled'; ?>><i class="bi bi-file-earmark-excel-fill"></i> Reporte</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="tabla-admin-mejorada">
                        <thead><tr><th>ID</th><th>EPS</th><th>Entidad Aliada</th><th>Tipo</th><th>Fecha</th><th>Estado</th><th class="columna-acciones-fija">Acciones</th></tr></thead>
                        <tbody id="tabla_alianzas_body"><?php echo render_alianzas_body($datos_iniciales['lista']); ?></tbody>
                    </table>
                </div>
                <nav aria-label="Paginación de alianzas" class="mt-3 paginacion-tabla-container"><ul class="pagination pagination-sm" id="paginacion_lista"></ul></nav>
            </div>
        </div>
    </main>
    <?php include '../../include/footer.php'; ?>
    <div class="modal fade" id="modalMasFiltros" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Filtros Avanzados por Fecha</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="filtro_fecha_inicio" class="form-label"><i class="bi bi-calendar-range"></i> Fecha Inicio Reporte:</label>
                        <input type="date" id="filtro_fecha_inicio" class="form-control form-control-sm" max="<?php echo date('Y-m-d'); ?>">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="filtro_fecha_fin" class="form-label"><i class="bi bi-calendar-range-fill"></i> Fecha Fin Reporte:</label>
                        <input type="date" id="filtro_fecha_fin" class="form-control form-control-sm" max="<?php echo date('Y-m-d'); ?>">
                        <div class="invalid-feedback"></div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label for="filtro_fecha_exacta" class="form-label"><i class="bi bi-calendar-day"></i> Fecha Alianza (Día exacto):</label>
                        <input type="date" id="filtro_fecha_exacta" class="form-control form-control-sm" max="<?php echo date('Y-m-d'); ?>">
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="btn_limpiar_fechas">Limpiar Fechas</button>
                    <button type="button" class="btn btn-primary" id="btn_aplicar_filtros_fecha">Aplicar Filtros</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalVerAlianza" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><div class="modal-header modal-header-alianza"><h5 class="modal-title"><i class="bi bi-info-circle-fill"></i> Detalles de la Alianza</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="modalVerAlianzaBody"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div></div></div></div>
    <div class="modal fade" id="modalConfirmarReporteAlianzas" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="bi bi-file-earmark-excel-fill me-2"></i>Generar Reporte de Alianzas</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p>Se generará un reporte en Excel con los siguientes filtros aplicados:</p><div id="confirmarReporteTextoAlianzas" class="alert alert-light border"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-success" id="btnConfirmarGeneracionAlianzas"><i class="bi bi-check-circle-fill"></i> Confirmar y Generar</button></div></div></div></div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script> const csrfTokenAlianzas = '<?php echo $csrf_token_alianzas; ?>'; </script>
    <script src="../js/lista_alianzas_admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>