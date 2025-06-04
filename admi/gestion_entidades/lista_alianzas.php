<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php');

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) {
    header('Location: ../inicio_sesion.php'); exit;
}
if (empty($_SESSION['csrf_token_alianzas'])) { $_SESSION['csrf_token_alianzas'] = bin2hex(random_bytes(32)); }
$csrf_token_alianzas = $_SESSION['csrf_token_alianzas'];

$alianzas_list = [];
$error_db = '';
$pageTitle = "Lista de Alianzas EPS";

$filtro_tipo_alianza = trim($_GET['filtro_tipo'] ?? 'todas');
$filtro_estado_alianza = trim($_GET['filtro_estado'] ?? '');
$filtro_nombre_eps = trim($_GET['filtro_nombre_eps'] ?? '');
$filtro_nombre_entidad_aliada = trim($_GET['filtro_nombre_entidad_aliada'] ?? '');
$filtro_orden_fecha = trim($_GET['filtro_orden_fecha'] ?? 'desc'); // Nuevo filtro de orden


$registros_por_pagina = 4;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$total_registros = 0; $total_paginas = 1;

if ($con) {
    try {
        $params_union = [];
        $where_farm_conditions = []; 
        $where_ips_conditions = [];

        if (!empty($filtro_nombre_eps)) {
            $where_farm_conditions[] = "eps_farm.nombre_eps LIKE :nombre_eps_farm";
            $where_ips_conditions[] = "eps_ips.nombre_eps LIKE :nombre_eps_ips";
            $params_union[':nombre_eps_farm'] = "%" . $filtro_nombre_eps . "%";
            $params_union[':nombre_eps_ips'] = "%" . $filtro_nombre_eps . "%";
        }
        if (!empty($filtro_nombre_entidad_aliada)) {
            $where_farm_conditions[] = "f.nom_farm LIKE :nombre_entidad_farm";
            $where_ips_conditions[] = "i.nom_IPS LIKE :nombre_entidad_ips";
            $params_union[':nombre_entidad_farm'] = "%" . $filtro_nombre_entidad_aliada . "%";
            $params_union[':nombre_entidad_ips'] = "%" . $filtro_nombre_entidad_aliada . "%";
        }
         if ($filtro_estado_alianza !== '') {
            $where_farm_conditions[] = "def.id_estado = :estado_alianza_farm";
            $where_ips_conditions[] = "dei.id_estado = :estado_alianza_ips";
            $params_union[':estado_alianza_farm'] = $filtro_estado_alianza;
            $params_union[':estado_alianza_ips'] = $filtro_estado_alianza;
        }

        $where_farm_sql = "";
        if(!empty($where_farm_conditions)){
            $where_farm_sql = "WHERE " . implode(" AND ", $where_farm_conditions);
        }
        $where_ips_sql = "";
        if(!empty($where_ips_conditions)){
            $where_ips_sql = "WHERE " . implode(" AND ", $where_ips_conditions);
        }
        
        $sql_farm_from_definition = "FROM detalle_eps_farm def JOIN eps eps_farm ON def.nit_eps = eps_farm.nit_eps JOIN farmacias f ON def.nit_farm = f.nit_farm JOIN estado est_farm ON def.id_estado = est_farm.id_est $where_farm_sql";
        $sql_ips_from_definition = "FROM detalle_eps_ips dei JOIN eps eps_ips ON dei.nit_eps = eps_ips.nit_eps JOIN ips i ON dei.nit_ips = i.Nit_IPS JOIN estado est_ips ON dei.id_estado = est_ips.id_est $where_ips_sql";

        $sql_farm_select_base = "SELECT def.id_eps_farm AS id_alianza, eps_farm.nombre_eps AS nombre_eps, f.nom_farm AS nombre_entidad_aliada, 'Farmacia' AS tipo_entidad_aliada, def.fecha, est_farm.nom_est AS estado_alianza, def.id_estado AS id_estado_alianza_val, 'detalle_eps_farm' as tabla_origen, def.nit_eps as nit_eps_val, def.nit_farm as nit_entidad_val";
        $sql_ips_select_base = "SELECT dei.id_eps_ips AS id_alianza, eps_ips.nombre_eps AS nombre_eps, i.nom_IPS AS nombre_entidad_aliada, 'IPS' AS tipo_entidad_aliada, dei.fecha, est_ips.nom_est AS estado_alianza, dei.id_estado AS id_estado_alianza_val, 'detalle_eps_ips' as tabla_origen, dei.nit_eps as nit_eps_val, dei.nit_ips as nit_entidad_val";

        $sql_farm_select_from = "$sql_farm_select_base $sql_farm_from_definition";
        $sql_ips_select_from = "$sql_ips_select_base $sql_ips_from_definition";

        $sub_queries_for_sum = [];
        if ($filtro_tipo_alianza === 'todas' || $filtro_tipo_alianza === 'farmacia') {
            $sub_queries_for_sum[] = "SELECT COUNT(*) AS c $sql_farm_from_definition";
        }
        if ($filtro_tipo_alianza === 'todas' || $filtro_tipo_alianza === 'ips') {
             $sub_queries_for_sum[] = "SELECT COUNT(*) AS c $sql_ips_from_definition";
        }

        $total_registros = 0;
        if (!empty($sub_queries_for_sum)) {
            $count_sql_final = "SELECT SUM(c) FROM (" . implode(" UNION ALL ", $sub_queries_for_sum) . ") AS sub_counts";
            $stmt_total = $con->prepare($count_sql_final);
            $stmt_total->execute($params_union);
            $total_registros = (int)$stmt_total->fetchColumn();
        }

        if ($total_registros > 0) {
            $total_paginas = ceil($total_registros / $registros_por_pagina);
            if ($total_paginas == 0) $total_paginas = 1;
            if ($pagina_actual > $total_paginas && $total_paginas > 0) $pagina_actual = $total_paginas;
            $offset = ($pagina_actual - 1) * $registros_por_pagina;

            $sql_union_parts_select = [];
            if ($filtro_tipo_alianza === 'todas' || $filtro_tipo_alianza === 'farmacia') {
                $sql_union_parts_select[] = "($sql_farm_select_from)";
            }
            if ($filtro_tipo_alianza === 'todas' || $filtro_tipo_alianza === 'ips') {
                $sql_union_parts_select[] = "($sql_ips_select_from)";
            }
            
            if(!empty($sql_union_parts_select)){
                $order_by_clause = "ORDER BY ";
                if ($filtro_orden_fecha === 'asc') {
                    $order_by_clause .= "fecha ASC, nombre_eps ASC, tipo_entidad_aliada ASC, nombre_entidad_aliada ASC";
                } else { // 'desc' o por defecto
                    $order_by_clause .= "fecha DESC, nombre_eps ASC, tipo_entidad_aliada ASC, nombre_entidad_aliada ASC";
                }

                $sql_union = implode(" UNION ALL ", $sql_union_parts_select) . " $order_by_clause LIMIT :limit OFFSET :offset_val";
                $stmt = $con->prepare($sql_union);
                foreach ($params_union as $key => $value) {
                    $stmt->bindValue($key, $value);
                }
                $stmt->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
                $stmt->bindParam(':offset_val', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $alianzas_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                 $alianzas_list = []; $total_registros = 0; $total_paginas = 1;
            }
        } else { $alianzas_list = []; $total_paginas = 1; $pagina_actual = 1;}
    } catch (PDOException $e) { $error_db = "Error al consultar BD: " . $e->getMessage(); error_log("PDO Ver Alianzas: ".$e->getMessage()); $alianzas_list = []; }
} else { $error_db = "Error de conexión."; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="../img/loguito.png">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <h3 class="titulo-lista-tabla"><?php echo htmlspecialchars($pageTitle); ?></h3>
                <?php if (isset($_SESSION['mensaje_alianza'])): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['mensaje_alianza_tipo']); ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['mensaje_alianza']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['mensaje_alianza']); unset($_SESSION['mensaje_alianza_tipo']); ?>
                <?php endif; ?>
                <?php if (!empty($error_db)) : ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_db); ?></div><?php endif; ?>

                <form method="GET" action="lista_alianzas.php" class="mb-4 filtros-tabla-container">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-2 col-md-4">
                            <label for="filtro_tipo" class="form-label">Tipo Alianza:</label>
                            <select id="filtro_tipo" name="filtro_tipo" class="form-select form-select-sm">
                                <option value="todas" <?php echo ($filtro_tipo_alianza == 'todas') ? 'selected' : ''; ?>>Todas</option>
                                <option value="farmacia" <?php echo ($filtro_tipo_alianza == 'farmacia') ? 'selected' : ''; ?>>EPS-Farmacia</option>
                                <option value="ips" <?php echo ($filtro_tipo_alianza == 'ips') ? 'selected' : ''; ?>>EPS-IPS</option>
                            </select>
                        </div>
                         <div class="col-lg-2 col-md-4">
                            <label for="filtro_estado" class="form-label">Estado Alianza:</label>
                            <select name="filtro_estado" id="filtro_estado" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="1" <?php echo ($filtro_estado_alianza === '1') ? 'selected' : ''; ?>>Activa</option>
                                <option value="2" <?php echo ($filtro_estado_alianza === '2') ? 'selected' : ''; ?>>Inactiva</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <label for="filtro_orden_fecha" class="form-label">Ordenar por Fecha:</label>
                            <select name="filtro_orden_fecha" id="filtro_orden_fecha" class="form-select form-select-sm">
                                <option value="desc" <?php echo ($filtro_orden_fecha === 'desc') ? 'selected' : ''; ?>>Más Recientes Primero</option>
                                <option value="asc" <?php echo ($filtro_orden_fecha === 'asc') ? 'selected' : ''; ?>>Más Antiguas Primero</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label for="filtro_nombre_eps" class="form-label">Nombre EPS:</label>
                            <input type="text" name="filtro_nombre_eps" id="filtro_nombre_eps" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtro_nombre_eps); ?>" placeholder="Nombre EPS...">
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label for="filtro_nombre_entidad_aliada" class="form-label">Entidad Aliada:</label>
                            <input type="text" name="filtro_nombre_entidad_aliada" id="filtro_nombre_entidad_aliada" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtro_nombre_entidad_aliada); ?>" placeholder="Farmacia/IPS...">
                        </div>
                        <div class="col-lg-1 col-md-6 mt-md-0 mt-3">
                             <button type="submit" class="btn btn-sm btn-filtrar-tabla w-100">Filtrar</button>
                        </div>
                        <div class="col-lg-1 col-md-6 mt-md-0 mt-3">
                            <a href="lista_alianzas.php" class="btn btn-sm btn-outline-secondary w-100">Limpiar</a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="tabla-admin-mejorada">
                        <thead>
                            <tr>
                                <th>ID Alianza</th>
                                <th>EPS</th>
                                <th>Entidad Aliada</th>
                                <th>Tipo Alianza</th>
                                <th>Fecha Creación/Mod.</th>
                                <th>Estado</th>
                                <th class="columna-acciones-tabla">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($alianzas_list)) : ?>
                                <?php foreach ($alianzas_list as $alianza) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($alianza['id_alianza']); ?></td>
                                        <td><?php echo htmlspecialchars($alianza['nombre_eps']); ?></td>
                                        <td><?php echo htmlspecialchars($alianza['nombre_entidad_aliada']); ?></td>
                                        <td>
                                            <?php if ($alianza['tipo_entidad_aliada'] === 'Farmacia'): ?>
                                                <span class="badge badge-alianza-farmacia"><?php echo htmlspecialchars($alianza['tipo_entidad_aliada']); ?></span>
                                            <?php elseif ($alianza['tipo_entidad_aliada'] === 'IPS'): ?>
                                                 <span class="badge badge-alianza-ips"><?php echo htmlspecialchars($alianza['tipo_entidad_aliada']); ?></span>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($alianza['tipo_entidad_aliada']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($alianza['fecha']))); ?></td>
                                        <td>
                                            <?php if ($alianza['id_estado_alianza_val'] == 1): ?>
                                                <span class="badge bg-success">Activa</span>
                                            <?php elseif ($alianza['id_estado_alianza_val'] == 2): ?>
                                                <span class="badge bg-danger">Inactiva</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($alianza['estado_alianza']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="acciones-tabla">
                                             <?php if ($alianza['id_estado_alianza_val'] == 1): ?>
                                                <button class="btn btn-warning btn-sm btn-cambiar-estado-alianza"
                                                        data-id-alianza="<?php echo htmlspecialchars($alianza['id_alianza']); ?>"
                                                        data-tabla-origen="<?php echo htmlspecialchars($alianza['tabla_origen']); ?>"
                                                        data-nit-eps="<?php echo htmlspecialchars($alianza['nit_eps_val']); ?>"
                                                        data-nit-entidad="<?php echo htmlspecialchars($alianza['nit_entidad_val']); ?>"
                                                        data-accion="inactivar"
                                                        title="Inactivar Alianza">
                                                    <i class="bi bi-toggle-off"></i><span>Inactivar</span>
                                                </button>
                                            <?php elseif ($alianza['id_estado_alianza_val'] == 2): ?>
                                                <button class="btn btn-success btn-sm btn-cambiar-estado-alianza"
                                                        data-id-alianza="<?php echo htmlspecialchars($alianza['id_alianza']); ?>"
                                                        data-tabla-origen="<?php echo htmlspecialchars($alianza['tabla_origen']); ?>"
                                                        data-nit-eps="<?php echo htmlspecialchars($alianza['nit_eps_val']); ?>"
                                                        data-nit-entidad="<?php echo htmlspecialchars($alianza['nit_entidad_val']); ?>"
                                                        data-accion="activar"
                                                        title="Activar Alianza">
                                                    <i class="bi bi-toggle-on"></i><span>Activar</span>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-danger btn-sm btn-eliminar-alianza" 
                                                    data-id-alianza="<?php echo htmlspecialchars($alianza['id_alianza']); ?>"
                                                    data-tabla-origen="<?php echo htmlspecialchars($alianza['tabla_origen']); ?>"
                                                    data-nombre-eps="<?php echo htmlspecialchars($alianza['nombre_eps']); ?>"
                                                    data-nombre-entidad="<?php echo htmlspecialchars($alianza['nombre_entidad_aliada']); ?>"
                                                    title="Eliminar Alianza">
                                                <i class="bi bi-trash3"></i> <span>Eliminar</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                 <tr><td colspan="7" class="text-center">
                                    No hay alianzas que coincidan con los filtros aplicados.
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_registros > 0 && $total_paginas > 1): ?>
                <nav aria-label="Paginación de alianzas" class="mt-3 paginacion-tabla-container">
                    <ul class="pagination pagination-sm">
                         <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>&filtro_tipo=<?php echo urlencode($filtro_tipo_alianza); ?>&filtro_estado=<?php echo urlencode($filtro_estado_alianza); ?>&filtro_orden_fecha=<?php echo urlencode($filtro_orden_fecha); ?>&filtro_nombre_eps=<?php echo urlencode($filtro_nombre_eps); ?>&filtro_nombre_entidad_aliada=<?php echo urlencode($filtro_nombre_entidad_aliada); ?>"><i class="bi bi-chevron-left"></i></a>
                        </li>
                         <li class="page-item active" id="page-number-container">
                           <span class="page-link page-number-display" title="Click/Enter para ir a pág."><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></span>
                           <input type="number" class="form-control form-control-sm page-number-input-field" value="<?php echo $pagina_actual; ?>" min="1" max="<?php echo $total_paginas; ?>" style="display: none;" data-total="<?php echo $total_paginas; ?>">
                         </li>
                        <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>&filtro_tipo=<?php echo urlencode($filtro_tipo_alianza); ?>&filtro_estado=<?php echo urlencode($filtro_estado_alianza); ?>&filtro_orden_fecha=<?php echo urlencode($filtro_orden_fecha); ?>&filtro_nombre_eps=<?php echo urlencode($filtro_nombre_eps); ?>&filtro_nombre_entidad_aliada=<?php echo urlencode($filtro_nombre_entidad_aliada); ?>"><i class="bi bi-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include '../include/footer.php'; ?>

    <div class="modal-confirmacion" id="modalConfirmacionAlianza" style="display:none;">
        <div class="modal-contenido">
            <h4 id="tituloConfirmacionAlianza">Confirmar Acción</h4>
            <p id="mensajeModalConfirmacionAlianza">¿Está seguro?</p>
            <div class="modal-botones">
                <button id="btnConfirmarAccionAlianza" class="btn btn-danger">Confirmar</button>
                <button id="btnCancelarAccionAlianza" class="btn btn-secondary">Cancelar</button>
            </div>
        </div>
    </div>
    <script> const csrfTokenAlianzas = '<?php echo $csrf_token_alianzas; ?>'; </script>
    <script src="../js/lista_alianzas_admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>