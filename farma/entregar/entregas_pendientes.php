<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$pageTitle = "Entregas Pendientes";
$nit_farmacia_actual = $_SESSION['nit_farmacia_asignada_actual'] ?? '';

function generarFilasPendientes($con, $nit_farmacia_actual, &$pagina_actual_ref, &$total_paginas_ref)
{
    $filtro_radicado = trim($_GET['q_radicado'] ?? '');
    $filtro_documento = trim($_GET['q_documento'] ?? '');
    $filtro_orden = trim($_GET['orden'] ?? 'desc');
    $registros_por_pagina = 10;
    
    $pagina_actual_ref = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina_actual_ref < 1) $pagina_actual_ref = 1;

    $params = [':nit_farma' => $nit_farmacia_actual];
    $sql_where_conditions = ["ep.id_estado = 10"];

    if (!empty($filtro_radicado)) {
        $sql_where_conditions[] = "ep.radicado_pendiente LIKE :radicado";
        $params[':radicado'] = "%" . $filtro_radicado . "%";
    }
    if (!empty($filtro_documento)) {
        $sql_where_conditions[] = "u.doc_usu LIKE :documento";
        $params[':documento'] = "%" . $filtro_documento . "%";
    }

    $sql_from_join = "FROM entrega_pendiente ep JOIN detalles_histo_clini dh ON ep.id_detalle_histo = dh.id_detalle JOIN historia_clinica hc ON dh.id_historia = hc.id_historia JOIN citas c ON hc.id_cita = c.id_cita JOIN usuarios u ON c.doc_pac = u.doc_usu JOIN medicamentos m ON dh.id_medicam = m.id_medicamento JOIN usuarios fg ON ep.id_farmaceuta_genera = fg.doc_usu JOIN asignacion_farmaceuta af ON fg.doc_usu = af.doc_farma WHERE af.nit_farma = :nit_farma";
    if (!empty($sql_where_conditions)) {
        $sql_from_join .= " AND " . implode(" AND ", $sql_where_conditions);
    }
    
    $stmt_total = $con->prepare("SELECT COUNT(DISTINCT ep.id_entrega_pendiente) " . $sql_from_join);
    $stmt_total->execute($params);
    $total_registros = (int)$stmt_total->fetchColumn();
    
    $total_paginas_ref = ceil($total_registros / $registros_por_pagina);
    if ($total_paginas_ref == 0) $total_paginas_ref = 1;
    if ($pagina_actual_ref > $total_paginas_ref) $pagina_actual_ref = $total_paginas_ref;
    
    $offset = ($pagina_actual_ref - 1) * $registros_por_pagina;
    $order_by = ($filtro_orden === 'asc') ? "ep.fecha_generacion ASC" : "ep.fecha_generacion DESC";
    
    $sql_final = "SELECT ep.id_entrega_pendiente, ep.radicado_pendiente, ep.fecha_generacion, u.nom_usu AS nombre_paciente, u.doc_usu AS doc_paciente, m.nom_medicamento, dh.id_detalle, ep.cantidad_pendiente, hc.id_historia " . $sql_from_join . " GROUP BY ep.id_entrega_pendiente ORDER BY " . $order_by . " LIMIT :limit OFFSET :offset_val";
    
    $stmt = $con->prepare($sql_final);
    foreach ($params as $key => &$val) $stmt->bindParam($key, $val);
    $stmt->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset_val', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    if (!empty($pendientes)):
        foreach ($pendientes as $p): ?>
            <tr>
                <td><span class="badge bg-warning text-dark"><?php echo htmlspecialchars($p['radicado_pendiente']); ?></span></td>
                <td><?php echo htmlspecialchars($p['nombre_paciente']); ?></td>
                <td><?php echo htmlspecialchars($p['nom_medicamento']); ?></td>
                <td class="text-center"><strong><?php echo htmlspecialchars($p['cantidad_pendiente']); ?></strong></td>
                <td><?php echo htmlspecialchars(date('d/m/Y h:i A', strtotime($p['fecha_generacion']))); ?></td>
                <td class="acciones-tabla">
                    <button type="button" class="btn btn-info btn-sm btn-ver-pendiente" data-id-pendiente="<?php echo $p['id_entrega_pendiente']; ?>"><i class="bi bi-eye-fill"></i> Ver</button>
                    <button type="button" class="btn btn-success btn-sm btn-entregar-pendiente" 
                            data-id-entrega-pendiente="<?php echo $p['id_entrega_pendiente']; ?>"
                            data-id-historia="<?php echo $p['id_historia']; ?>"
                            data-id-detalle="<?php echo $p['id_detalle']; ?>">
                        <i class="bi bi-check-circle-fill"></i> Entregar
                    </button>
                </td>
            </tr>
        <?php endforeach;
    else: ?>
        <tr><td colspan="6" class="text-center p-4">No se encontraron entregas pendientes con los filtros aplicados.</td></tr>
    <?php endif;
    return ob_get_clean();
}

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $pagina_actual_ajax = 1;
    $total_paginas_ajax = 1;
    $html_tabla = generarFilasPendientes($con, $nit_farmacia_actual, $pagina_actual_ajax, $total_paginas_ajax);
    echo json_encode(['html_tabla' => $html_tabla, 'total_paginas' => $total_paginas_ajax, 'pagina_actual' => $pagina_actual_ajax]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="../../img/loguito.png">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <h3 class="titulo-lista-tabla m-0">Gestión de Entregas Pendientes</h3>
                <form id="formFiltrosPendientes" class="mb-4 mt-3 filtros-tabla-container">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4"><label for="q_radicado" class="form-label"><i class="bi bi-search"></i> Por Radicado:</label><input type="search" name="q_radicado" id="q_radicado" class="form-control form-control-sm" placeholder="PEND-..."></div>
                        <div class="col-md-4"><label for="q_documento" class="form-label"><i class="bi bi-person-badge"></i> Por Documento:</label><input type="search" name="q_documento" id="q_documento" class="form-control form-control-sm" placeholder="Documento paciente..."></div>
                        <div class="col-md-2"><label for="orden" class="form-label"><i class="bi bi-sort-down"></i> Ordenar:</label><select name="orden" id="orden" class="form-select form-select-sm"><option value="desc">Más Recientes</option><option value="asc">Más Antiguos</option></select></div>
                        <div class="col-md-2 d-grid"><a href="entregas_pendientes.php" class="btn btn-sm btn-outline-secondary">Limpiar Filtros</a></div>
                    </div>
                </form>
                <div class="table-responsive"><table class="tabla-admin-mejorada"><thead><tr><th>Radicado</th><th>Paciente</th><th>Medicamento</th><th class="text-center">Cantidad</th><th>Fecha Generación</th><th class="columna-acciones-fija">Acciones</th></tr></thead><tbody id="tabla-pendientes-body"></tbody></table></div>
                <div id="paginacion-container" class="d-flex justify-content-center mt-3"></div>
            </div>
        </div>
    </main>
    <div id="modal-entrega-placeholder"></div>
    <div class="modal fade" id="modalDetallesPendiente" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="bi bi-info-circle-fill me-2"></i>Detalles Pendiente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="cuerpoModalDetalles"><div class="text-center p-5"><div class="spinner-border text-primary"></div></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div></div></div></div>
    <?php include '../../include/footer.php'; ?>
    <script src="../js/gestion_pendientes.js?v=<?php echo time(); ?>"></script>
    <script src="../js/gestion_entrega.js?v=<?php echo time(); ?>"></script>
</body>
</html>