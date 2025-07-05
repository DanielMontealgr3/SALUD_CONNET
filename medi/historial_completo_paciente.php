<?php
require_once ('../include/validar_sesion.php');
require_once ('../include/inactividad.php');
require_once('../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || !in_array($_SESSION['id_rol'], [1, 4])) {
    header('Location: ../inicio_sesion.php');
    exit;
}

$nombre_usuario_sesion = $_SESSION['nombre_usuario'];
$db = new Database();
$pdo = $db->conectar();

$documento_paciente_url = $_GET['documento'] ?? null;
if (!$documento_paciente_url) {
    $_SESSION['mensaje_error'] = "Documento del paciente no proporcionado.";
    $fallback_redirect = ($_SESSION['id_rol'] == 4) ? 'citas.php' : '../admin/dashboard.php';
    header('Location: ' . $fallback_redirect);
    exit;
}

// --- PAGINATION FOR HISTORY ---
define('REGISTROS_POR_PAGINA_HISTORIAL', 1); // MODIFIED: Show 1 history entry per page

// --- FILTERS FOR HISTORY ---
$fecha_desde_historial = isset($_GET['fecha_desde_h']) ? trim($_GET['fecha_desde_h']) : '';
$fecha_hasta_historial = isset($_GET['fecha_hasta_h']) ? trim($_GET['fecha_hasta_h']) : '';

$pagina_historial_actual = isset($_GET['pagina_h']) ? (int)$_GET['pagina_h'] : 1;
if ($pagina_historial_actual < 1) $pagina_historial_actual = 1;


// 1. Get Patient Info
$queryPaciente = "SELECT nom_usu, foto_usu FROM usuarios WHERE doc_usu = :documento";
$stmtPaciente = $pdo->prepare($queryPaciente);
$stmtPaciente->bindParam(':documento', $documento_paciente_url, PDO::PARAM_STR);
$stmtPaciente->execute();
$paciente = $stmtPaciente->fetch(PDO::FETCH_ASSOC);

if (!$paciente) {
    $_SESSION['mensaje_error'] = "Paciente no encontrado con el documento: " . htmlspecialchars($documento_paciente_url);
    $fallback_redirect = ($_SESSION['id_rol'] == 4) ? 'citas.php' : '../admin/dashboard.php';
    header('Location: ' . $fallback_redirect);
    exit;
}
$pageTitle = "Historial Clínico Completo: " . htmlspecialchars($paciente['nom_usu']);

// 2. Build WHERE clause for history queries based on filters
$where_clauses_historial = ["c.doc_pac = :documento_param"]; 
$query_params_historial = [':documento_param' => $documento_paciente_url];

if ($fecha_desde_historial !== '' && $fecha_hasta_historial !== '') {
    $where_clauses_historial[] = "(hm.fecha_horario BETWEEN :fecha_desde_h_param AND :fecha_hasta_h_param)";
    $query_params_historial[':fecha_desde_h_param'] = $fecha_desde_historial;
    $query_params_historial[':fecha_hasta_h_param'] = $fecha_hasta_historial;
} elseif ($fecha_desde_historial !== '') {
    $where_clauses_historial[] = "(hm.fecha_horario >= :fecha_desde_h_param)";
    $query_params_historial[':fecha_desde_h_param'] = $fecha_desde_historial;
} elseif ($fecha_hasta_historial !== '') {
    $where_clauses_historial[] = "(hm.fecha_horario <= :fecha_hasta_h_param)";
    $query_params_historial[':fecha_hasta_h_param'] = $fecha_hasta_historial;
}
$final_where_historial_sql = " WHERE " . implode(" AND ", $where_clauses_historial);

// 3. Count TOTAL Clinical History records for this patient (with filters)
$countHistoriasSql = "SELECT COUNT(hc.id_historia)
                      FROM historia_clinica hc
                      JOIN citas c ON hc.id_cita = c.id_cita
                      LEFT JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med" . $final_where_historial_sql;
$stmtCountHistorias = $pdo->prepare($countHistoriasSql);
$stmtCountHistorias->execute($query_params_historial);
$total_registros_historial = (int)$stmtCountHistorias->fetchColumn();
$total_paginas_historial = ceil($total_registros_historial / REGISTROS_POR_PAGINA_HISTORIAL);

if ($pagina_historial_actual > $total_paginas_historial && $total_paginas_historial > 0) $pagina_historial_actual = $total_paginas_historial;
if ($pagina_historial_actual < 1 && $total_paginas_historial > 0) $pagina_historial_actual = 1;
if ($total_paginas_historial == 0 && $pagina_historial_actual > 1) $pagina_historial_actual = 1;

$offset_historial = ($pagina_historial_actual - 1) * REGISTROS_POR_PAGINA_HISTORIAL;

// 4. Get PAGINATED Clinical History records for this patient (with filters)
$queryHistorias = "SELECT hc.*, c.fecha_solici AS fecha_solicitud_cita, hm.fecha_horario AS fecha_atencion, hm.horario AS hora_atencion
                   FROM historia_clinica hc
                   JOIN citas c ON hc.id_cita = c.id_cita
                   LEFT JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med" . $final_where_historial_sql .
                  " ORDER BY hm.fecha_horario DESC, hm.horario DESC, hc.id_historia DESC
                   LIMIT :offset_h, :limit_h";

$query_params_historial_paginated = $query_params_historial;
$query_params_historial_paginated[':offset_h'] = $offset_historial;
$query_params_historial_paginated[':limit_h'] = REGISTROS_POR_PAGINA_HISTORIAL;

$stmtHistorias = $pdo->prepare($queryHistorias);
foreach ($query_params_historial_paginated as $key => &$value) {
    if ($key == ':offset_h' || $key == ':limit_h') {
        $stmtHistorias->bindParam($key, $value, PDO::PARAM_INT);
    } else {
        $stmtHistorias->bindParam($key, $value);
    }
}
unset($value);
$stmtHistorias->execute();
$todasHistoriasClinicas = $stmtHistorias->fetchAll(PDO::FETCH_ASSOC);

// 5. For each history record on the current page, get its details
$historialDetallado = [];
foreach ($todasHistoriasClinicas as $historia) {
    $id_historia_iter = $historia['id_historia'];
    $queryDetalles = "SELECT det.*, d.diagnostico AS nombre_diagnostico, enf.nom_enfer AS nombre_enfermedad, te.tipo_enfermer AS nombre_tipo_enfermedad, med.nom_medicamento AS nombre_medicamento, tm.nom_tipo_medi AS nombre_tipo_medicamento, proc.procedimiento AS nombre_procedimiento FROM detalles_histo_clini det LEFT JOIN diagnostico d ON det.id_diagnostico = d.id_diagnos LEFT JOIN enfermedades enf ON det.id_enferme = enf.id_enferme LEFT JOIN tipo_enfermedades te ON enf.id_tipo_enfer = te.id_tipo_enfer LEFT JOIN medicamentos med ON det.id_medicam = med.id_medicamento LEFT JOIN tipo_de_medicamento tm ON med.id_tipo_medic = tm.id_tip_medic LEFT JOIN procedimientos proc ON det.id_proced = proc.id_proced WHERE det.id_historia = :id_historia_iter ORDER BY det.id_detalle ASC";
    $stmtDetalles = $pdo->prepare($queryDetalles);
    $stmtDetalles->bindParam(':id_historia_iter', $id_historia_iter, PDO::PARAM_INT);
    $stmtDetalles->execute();
    $historia['detalles_guardados'] = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
    $historialDetallado[] = $historia;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="estilos.css">
    <style>
        .patient-info-header { background-color: #f8f9fa; padding: 1rem; border-radius: 0.3rem; margin-bottom: 1.5rem; }
        .history-entry { margin-bottom: 2rem; border: 1px solid #ddd; border-radius: .25rem; }
        .history-entry-header { background-color: #e9ecef; padding: 0.75rem 1rem; border-bottom: 1px solid #ddd;}
        .history-entry-body { padding: 1rem; }
        .detail-list { list-style-type: none; padding-left: 0; }
        .detail-list li { padding: 0.25rem 0; border-bottom: 1px dashed #eee; }
        .detail-list li:last-child { border-bottom: none; }
        .badge { font-size: 0.9em; }
    </style>
</head>
<body>
    <?php include '../include/menu.php'; ?>

    <div class="container mt-4">
        <div class="patient-info-header">
            <div class="d-flex align-items-center">
                <?php if (!empty($paciente['foto_usu'])): ?><img src="../fotos_usuarios/<?php echo htmlspecialchars($paciente['foto_usu']); ?>" alt="Foto Paciente" class="img-thumbnail me-3" style="width: 80px; height: 80px; object-fit: cover;"><?php else: ?><div class="img-thumbnail me-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background-color: #e9ecef;"><i class="fas fa-user fa-2x text-secondary"></i></div><?php endif; ?>
                <div class="flex-grow-1"><h3><?php echo htmlspecialchars($paciente['nom_usu']); ?></h3><p class="mb-0 text-muted">Documento: <?php echo htmlspecialchars($documento_paciente_url); ?></p></div>
                <a href="deta_historia_clini.php?documento=<?= htmlspecialchars($documento_paciente_url) ?>" class="btn btn-outline-secondary ms-auto"><i class="fas fa-arrow-left"></i> Volver a Consulta Actual</a>
            </div>
        </div>

        <h2 class="mb-3">Historial Clínico Completo</h2>
        
        <form method="GET" action="historial_completo_paciente.php" class="mb-4 border p-3 rounded">
            <input type="hidden" name="documento" value="<?= htmlspecialchars($documento_paciente_url) ?>">
            <div class="row g-2 align-items-end">
                <div class="col-md-4"><label for="fecha_desde_h" class="form-label form-label-sm">Desde Fecha Atención:</label><input type="date" name="fecha_desde_h" id="fecha_desde_h" class="form-control form-control-sm" value="<?= htmlspecialchars($fecha_desde_historial) ?>"></div>
                <div class="col-md-4"><label for="fecha_hasta_h" class="form-label form-label-sm">Hasta Fecha Atención:</label><input type="date" name="fecha_hasta_h" id="fecha_hasta_h" class="form-control form-control-sm" value="<?= htmlspecialchars($fecha_hasta_historial) ?>"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary btn-sm w-100">Filtrar</button></div>
                <div class="col-md-2"><a href="historial_completo_paciente.php?documento=<?= htmlspecialchars($documento_paciente_url) ?>" class="btn btn-secondary btn-sm w-100">Limpiar</a></div>
            </div>
        </form>

        <?php if (isset($_SESSION['mensaje_error_historial'])): ?><div class="alert alert-danger"><?php echo $_SESSION['mensaje_error_historial']; unset($_SESSION['mensaje_error_historial']); ?></div><?php endif; ?>

        <?php if (!empty($historialDetallado)): ?>
            <?php foreach ($historialDetallado as $historiaEntry): ?>
                <div class="history-entry">
                    <div class="history-entry-header">
                        <strong>Fecha Atención:</strong> <?= htmlspecialchars($historiaEntry['fecha_atencion'] ? date('d/m/Y', strtotime($historiaEntry['fecha_atencion'])) : 'N/D') ?>
                        <strong>Hora:</strong> <?= htmlspecialchars($historiaEntry['hora_atencion'] ? date('h:i A', strtotime($historiaEntry['hora_atencion'])) : 'N/D') ?>
                        <span class="float-end text-muted">ID Historia: <?= htmlspecialchars($historiaEntry['id_historia']) ?> (Cita ID: <?= htmlspecialchars($historiaEntry['id_cita']) ?>)</span>
                    </div>
                    <div class="history-entry-body">
                        <p><strong>Motivo:</strong><br><?= nl2br(htmlspecialchars($historiaEntry['motivo_de_cons'])) ?></p>
                        <p><strong>Signos Vitales:</strong> P: <?= htmlspecialchars($historiaEntry['presion']?:'N/R') ?> | Sat: <?= htmlspecialchars($historiaEntry['saturacion']?:'N/R') ?> | Peso: <?= htmlspecialchars($historiaEntry['peso']?:'N/R') ?><?= $historiaEntry['peso']?' kg':'' ?> | Est: <?= htmlspecialchars($historiaEntry['estatura']?:'N/R') ?><?= $historiaEntry['estatura']?' cm':'' ?></p>
                        <p><strong>Obs. Generales:</strong><br><?= nl2br(htmlspecialchars($historiaEntry['observaciones'])) ?></p>
                        <?php if (!empty($historiaEntry['detalles_guardados'])): ?><h6>Detalles Adicionales:</h6><ul class="detail-list"><?php foreach($historiaEntry['detalles_guardados'] as $detalle):?><li><?php if(!empty($detalle['id_diagnostico'])||!empty($detalle['id_enferme'])):?><span class="badge bg-primary me-1">Diagnóstico</span><strong><?=htmlspecialchars($detalle['nombre_diagnostico']??'N/E')?></strong> (Enf: <?=htmlspecialchars($detalle['nombre_enfermedad']??'N/E')?>)<?php elseif(!empty($detalle['id_medicam'])):?><span class="badge bg-info me-1">Prescripción</span><strong><?=htmlspecialchars($detalle['nombre_medicamento']??'N/E')?></strong> - Cant: <?=htmlspecialchars($detalle['can_medica']??'N/E')?> - Posología: <?=nl2br(htmlspecialchars($detalle['prescripcion']??'N/E'))?><?php elseif(!empty($detalle['id_proced'])):?><span class="badge bg-warning me-1">Procedimiento</span><strong><?=htmlspecialchars($detalle['nombre_procedimiento']??'N/E')?></strong><?php if(isset($detalle['cant_proced'])&&$detalle['cant_proced']!==null):?> (Cant: <?=htmlspecialchars($detalle['cant_proced'])?>)<?php endif;?><?php endif;?></li><?php endforeach;?></ul><?php else:?><p><em><small>No hay detalles adicionales.</small></em></p><?php endif;?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- PAGINATION FOR HISTORY -->
            <?php if ($total_paginas_historial > 1) : ?>
            <nav aria-label="Paginación del historial">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($pagina_historial_actual <= 1) ? 'disabled':''; ?>"><a class="page-link" href="?documento=<?=htmlspecialchars($documento_paciente_url)?>&pagina_h=<?php echo $pagina_historial_actual-1; ?>&fecha_desde_h=<?=urlencode($fecha_desde_historial)?>&fecha_hasta_h=<?=urlencode($fecha_hasta_historial)?>">Anterior</a></li>
                    <?php $rango_p = 2; $inicio_r = max(1, $pagina_historial_actual - $rango_p); $fin_r = min($total_paginas_historial, $pagina_historial_actual + $rango_p);
                    if($inicio_r > 1){ echo '<li class="page-item"><a class="page-link" href="?documento='.htmlspecialchars($documento_paciente_url).'&pagina_h=1&fecha_desde_h='.urlencode($fecha_desde_historial).'&fecha_hasta_h='.urlencode($fecha_hasta_historial).'">1</a></li>'; if($inicio_r > 2){ echo '<li class="page-item disabled"><span class="page-link">...</span></li>';} }
                    for($i=$inicio_r;$i<=$fin_r;$i++):?><li class="page-item <?php echo ($pagina_historial_actual==$i)?'active':'';?>"><a class="page-link" href="?documento=<?=htmlspecialchars($documento_paciente_url)?>&pagina_h=<?php echo $i;?>&fecha_desde_h=<?=urlencode($fecha_desde_historial)?>&fecha_hasta_h=<?=urlencode($fecha_hasta_historial)?>"><?php echo $i;?></a></li><?php endfor;
                    if($fin_r < $total_paginas_historial){ if($fin_r < $total_paginas_historial-1){ echo '<li class="page-item disabled"><span class="page-link">...</span></li>';} echo '<li class="page-item"><a class="page-link" href="?documento='.htmlspecialchars($documento_paciente_url).'&pagina_h='.$total_paginas_historial.'&fecha_desde_h='.urlencode($fecha_desde_historial).'&fecha_hasta_h='.urlencode($fecha_hasta_historial).'">'.$total_paginas_historial.'</a></li>';}?>
                    <li class="page-item <?php echo ($pagina_historial_actual >= $total_paginas_historial) ? 'disabled':''; ?>"><a class="page-link" href="?documento=<?=htmlspecialchars($documento_paciente_url)?>&pagina_h=<?php echo $pagina_historial_actual+1; ?>&fecha_desde_h=<?=urlencode($fecha_desde_historial)?>&fecha_hasta_h=<?=urlencode($fecha_hasta_historial)?>">Siguiente</a></li>
                </ul>
            </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert alert-info">Este paciente no tiene entradas en su historial clínico según los filtros aplicados.</div>
        <?php endif; ?>
    </div>

    <?php include '../include/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>