<?php
require_once __DIR__ . '/../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

if ($_SESSION['id_rol'] != 4) {
    header('Location: ' . BASE_URL . '/inicio_sesion.php');
    exit;
}

$doc_medico_actual = $_SESSION['doc_usu'];

define('REGISTROS_POR_PAGINA_DETALLES', 5);
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

$sql_base = "FROM detalles_histo_clini dh
             INNER JOIN historia_clinica hc ON dh.id_historia = hc.id_historia
             INNER JOIN citas ci ON hc.id_cita = ci.id_cita
             INNER JOIN horario_medico hm ON ci.id_horario_med = hm.id_horario_med
             INNER JOIN usuarios u_pac ON ci.doc_pac = u_pac.doc_usu 
             LEFT JOIN diagnostico d ON dh.id_diagnostico = d.id_diagnos
             LEFT JOIN enfermedades e ON dh.id_enferme = e.id_enferme
             LEFT JOIN medicamentos m ON dh.id_medicam = m.id_medicamento
             LEFT JOIN procedimientos p ON dh.id_proced = p.id_proced";

$where_clauses = [];
$query_params = [];

$where_clauses[] = "hm.doc_medico = ?";
$query_params[] = $doc_medico_actual;

if ($busqueda != '') {
    $likeBusqueda = "%$busqueda%";
    $where_clauses[] = "(
        u_pac.nom_usu LIKE ? OR u_pac.doc_usu LIKE ? OR d.diagnostico LIKE ? OR
        e.nom_enfer LIKE ? OR m.nom_medicamento LIKE ? OR p.procedimiento LIKE ? OR
        dh.prescripcion LIKE ? OR dh.id_detalle LIKE ? OR hc.id_historia LIKE ?
    )";
    for ($i = 0; $i < 9; $i++) {
        $query_params[] = $likeBusqueda;
    }
}

$final_where_sql = " WHERE " . implode(" AND ", $where_clauses);

$count_sql = "SELECT COUNT(dh.id_detalle) " . $sql_base . $final_where_sql;
$stmt_count = $con->prepare($count_sql);
$stmt_count->execute($query_params);
$total_registros = (int)$stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / REGISTROS_POR_PAGINA_DETALLES);

if ($pagina_actual > $total_paginas && $total_paginas > 0) $pagina_actual = $total_paginas;
$offset = ($pagina_actual - 1) * REGISTROS_POR_PAGINA_DETALLES;

$sql_data = "SELECT dh.id_detalle, hc.id_historia, u_pac.nom_usu AS nombre_paciente, u_pac.doc_usu AS documento_paciente, d.diagnostico, e.nom_enfer, m.nom_medicamento, dh.can_medica, p.procedimiento, dh.cant_proced, dh.prescripcion " 
            . $sql_base . $final_where_sql 
            . " ORDER BY dh.id_detalle DESC LIMIT ? OFFSET ?";

$data_query_params = $query_params;
$data_query_params[] = REGISTROS_POR_PAGINA_DETALLES;
$data_query_params[] = $offset;

$stmt_data = $con->prepare($sql_data);

$param_index = 1;
foreach ($data_query_params as $param_value) {
    $param_type = is_int($param_value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt_data->bindValue($param_index++, $param_value, $param_type);
}

$stmt_data->execute();
$detalles = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Mis Órdenes Clínicas Generadas";
?>
<!DOCTYPE html>
<html lang="es">
<?php 
require_once ROOT_PATH . '/include/menu.php'; 
?>
<div class="container mt-4 container-ver-ordenes"> 
    <div class="page-container-table"> 
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="<?php echo BASE_URL; ?>/medi/citas_hoy.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
            <h2 class="mb-0 text-center flex-grow-1 fs-4"><?php echo htmlspecialchars($pageTitle); ?></h2>
            <div style="width:90px;"></div> 
        </div>
        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="mb-3"> 
            <div class="input-group input-group-sm">
                <input type="text" name="buscar" class="form-control" placeholder="Buscar en mis órdenes..." value="<?php echo htmlspecialchars($busqueda); ?>">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                <?php if ($busqueda != ''): ?>
                    <a href="<?php echo BASE_URL; ?>/medi/ver_ordenes.php" class="btn btn-outline-secondary">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
        
        <?php if (empty($detalles)): ?>
            <div class="alert alert-info text-center">No ha generado órdenes <?php echo ($busqueda != '') ? 'con el criterio "' . htmlspecialchars($busqueda) . '"' : ''; ?>.</div>
        <?php else: ?>
            <div class="table-container"> 
                <table class="table table-bordered table-hover table-detalles table-responsive-cards"> 
                    <thead> 
                        <tr>
                            <th>ID Det.</th><th>ID Hist.</th><th>Paciente</th><th>Documento</th><th>Diagnóstico</th>
                            <th>Enfermedad</th><th>Medicamento</th><th>Cant. Med.</th><th>Procedimiento</th>
                            <th>Cant. Proc.</th><th>Prescripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $fila): ?>
                            <tr>
                                <td data-label="ID Detalle:" class="text-center"><?php echo htmlspecialchars($fila['id_detalle']); ?></td>
                                <td data-label="ID Historia:" class="text-center"><?php echo htmlspecialchars($fila['id_historia']); ?></td>
                                <td data-label="Paciente:"><?php echo htmlspecialchars($fila['nombre_paciente']); ?></td>
                                <td data-label="Documento:" class="text-center"><?php echo htmlspecialchars($fila['documento_paciente']); ?></td>
                                <td data-label="Diagnóstico:"><?php echo htmlspecialchars($fila['diagnostico'] ?? 'N/A'); ?></td>
                                <td data-label="Enfermedad:"><?php echo htmlspecialchars($fila['nom_enfer'] ?? 'N/A'); ?></td>
                                <td data-label="Medicamento:"><?php echo htmlspecialchars($fila['nom_medicamento'] ?? 'N/A'); ?></td>
                                <td data-label="Cant. Med.:" class="text-center"><?php echo htmlspecialchars($fila['can_medica'] ?? 'N/A'); ?></td>
                                <td data-label="Procedimiento:"><?php echo htmlspecialchars($fila['procedimiento'] ?? 'N/A'); ?></td>
                                <td data-label="Cant. Proc.:" class="text-center"><?php echo htmlspecialchars($fila['cant_proced'] ?? 'N/A'); ?></td>
                                <td data-label="Prescripción:"><?php echo nl2br(htmlspecialchars($fila['prescripcion'] ?? 'N/A')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
                <div class="pagination-compact d-flex justify-content-center mt-4">
                    <ul class="pagination mb-0">
                        <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>&buscar=<?php echo urlencode($busqueda); ?>" aria-label="Anterior"><</a>
                        </li>
                    </ul>
                    <span class="page-counter mx-3">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
                    <ul class="pagination mb-0">
                        <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>&buscar=<?php echo urlencode($busqueda); ?>" aria-label="Siguiente">></a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php 
require_once ROOT_PATH . '/include/footer.php'; 
?>