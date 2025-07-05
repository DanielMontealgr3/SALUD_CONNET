<?php
require_once __DIR__ . '/../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

if (!in_array($_SESSION['id_rol'], [1, 4])) {
    header('Location: ' . BASE_URL . '/inicio_sesion.php');
    exit;
}

$documento_paciente_url = $_GET['documento'] ?? null;
if (!$documento_paciente_url) {
    header('Location: ' . BASE_URL . '/medi/citas_hoy.php');
    exit;
}

try {
    $queryPaciente = "SELECT nom_usu, foto_usu FROM usuarios WHERE doc_usu = :documento";
    $stmtPaciente = $con->prepare($queryPaciente);
    $stmtPaciente->execute([':documento' => $documento_paciente_url]);
    $paciente = $stmtPaciente->fetch(PDO::FETCH_ASSOC);

    if (!$paciente) {
        header('Location: ' . BASE_URL . '/medi/citas_hoy.php');
        exit;
    }
    $pageTitle = "Historial Completo: " . htmlspecialchars($paciente['nom_usu']);

    // === INICIO DEL BLOQUE CORREGIDO (TU CONSULTA) ===
    $queryHistorias = "
        SELECT 
            hc.*, 
            c.fecha_solici AS fecha_solicitud_cita, 
            hm.fecha_horario AS fecha_atencion, 
            hm.horario AS hora_atencion,
            med.nom_usu AS nombre_medico,
            ips.nom_IPS AS nombre_ips,
            (SELECT e.nombre_eps 
             FROM afiliados af 
             JOIN eps e ON af.id_eps = e.nit_eps 
             WHERE af.doc_afiliado = c.doc_pac 
             ORDER BY af.fecha_afi DESC 
             LIMIT 1) AS nombre_eps
        FROM historia_clinica hc
        JOIN citas c ON hc.id_cita = c.id_cita
        LEFT JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med
        LEFT JOIN usuarios med ON hm.doc_medico = med.doc_usu
        LEFT JOIN ips ON c.nit_IPS = ips.nit_IPS
        WHERE c.doc_pac = :documento
        GROUP BY 
            hc.id_historia, c.fecha_solici, hm.fecha_horario, hm.horario, med.nom_usu, ips.nom_IPS, c.doc_pac
        ORDER BY fecha_atencion DESC, hora_atencion DESC";
    // === FIN DEL BLOQUE CORREGIDO (TU CONSULTA) ===

    $stmtHistorias = $con->prepare($queryHistorias);
    $stmtHistorias->execute([':documento' => $documento_paciente_url]);
    $todasHistoriasClinicas = $stmtHistorias->fetchAll(PDO::FETCH_ASSOC);

    $historialDetallado = [];
    foreach ($todasHistoriasClinicas as $historia) {
        $id_historia_iter = $historia['id_historia'];
        $queryDetalles = "
            SELECT 
                det.*, 
                d.diagnostico AS nombre_diagnostico, 
                enf.nom_enfer AS nombre_enfermedad, 
                med.nom_medicamento AS nombre_medicamento, 
                proc.procedimiento AS nombre_procedimiento 
            FROM detalles_histo_clini det 
            LEFT JOIN diagnostico d ON det.id_diagnostico = d.id_diagnos 
            LEFT JOIN enfermedades enf ON det.id_enferme = enf.id_enferme 
            LEFT JOIN medicamentos med ON det.id_medicam = med.id_medicamento 
            LEFT JOIN procedimientos proc ON det.id_proced = proc.id_proced 
            WHERE det.id_historia = :id_historia_iter 
            ORDER BY det.id_detalle ASC";
        $stmtDetalles = $con->prepare($queryDetalles);
        $stmtDetalles->execute([':id_historia_iter' => $id_historia_iter]);
        $historia['detalles_guardados'] = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
        $historialDetallado[] = $historia;
    }
} catch (PDOException $e) {
    error_log("Error en historial_completo_paciente.php: " . $e->getMessage());
    $_SESSION['mensaje_error'] = "Ocurrió un error al cargar el historial.";
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . '/medi/citas_hoy.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once ROOT_PATH . '/include/menu.php'; ?>
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <style>
        .history-entry { margin-bottom: 1.5rem; border: 1px solid #ddd; border-radius: .5rem; box-shadow: 0 2px 5px rgba(0,0,0,0.05); overflow: hidden; }
        .history-entry-header { background-color: #f8f9fa; padding: 0.75rem 1rem; border-bottom: 1px solid #ddd;}
        .history-entry-body { padding: 1.25rem; }
        .detail-item { padding: 0.5rem; border-bottom: 1px solid #f0f0f0; }
        .detail-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0"><?php echo htmlspecialchars($pageTitle); ?></h2>
            <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>
        
        <?php if (isset($_SESSION['mensaje_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['mensaje_error']; unset($_SESSION['mensaje_error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($historialDetallado)): ?>
            <?php foreach ($historialDetallado as $historiaEntry): ?>
                <div class="history-entry">
                    <div class="history-entry-header">
                        <div class="d-flex justify-content-between flex-wrap">
                            <div>
                                <strong>Fecha Atención:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($historiaEntry['fecha_atencion']))) ?>
                                a las <?= htmlspecialchars(date('h:i A', strtotime($historiaEntry['hora_atencion']))) ?>
                            </div>
                            <div class="text-muted">ID Historia: <?= htmlspecialchars($historiaEntry['id_historia']) ?></div>
                        </div>
                        <div class="d-flex justify-content-between flex-wrap text-muted small mt-1">
                             <div><strong>Atendido por:</strong> Dr/a. <?= htmlspecialchars($historiaEntry['nombre_medico'] ?? 'N/A') ?></div>
                             <div><strong>IPS:</strong> <?= htmlspecialchars($historiaEntry['nombre_ips'] ?? 'N/A') ?></div>
                             <div><strong>EPS (en esa fecha):</strong> <?= htmlspecialchars($historiaEntry['nombre_eps'] ?? 'No registrada') ?></div>
                        </div>
                    </div>
                    <div class="history-entry-body">
                        <h5>Resumen de la Consulta</h5>
                        <p><strong>Motivo:</strong> <?= nl2br(htmlspecialchars($historiaEntry['motivo_de_cons'])) ?></p>
                        <p><strong>Observaciones:</strong><br><?= nl2br(htmlspecialchars($historiaEntry['observaciones'])) ?></p>
                        
                        <?php if (!empty($historiaEntry['detalles_guardados'])): ?>
                            <h6 class="mt-4">Detalles Registrados</h6>
                            <?php foreach($historiaEntry['detalles_guardados'] as $detalle):?>
                                <div class="p-2 rounded bg-light mb-2 detail-item">
                                    <?php if(!empty($detalle['nombre_diagnostico'])):?><p class="mb-1"><strong><i class="bi bi-file-earmark-medical text-primary"></i> Diagnóstico:</strong> <?=htmlspecialchars($detalle['nombre_diagnostico'])?> (<?=htmlspecialchars($detalle['nombre_enfermedad']??'N/E')?>)</p><?php endif;?>
                                    <?php if(!empty($detalle['nombre_medicamento'])):?><p class="mb-1"><strong><i class="bi bi-capsule-pill text-info"></i> Prescripción:</strong> <?=htmlspecialchars($detalle['nombre_medicamento'])?> | Cant: <?=htmlspecialchars($detalle['can_medica']??'N/E')?> | Posología: <?=nl2br(htmlspecialchars($detalle['prescripcion']??'N/E'))?></p><?php endif;?>
                                    <?php if(!empty($detalle['nombre_procedimiento'])):?><p class="mb-0"><strong><i class="bi bi-heart-pulse text-warning"></i> Procedimiento:</strong> <?=htmlspecialchars($detalle['nombre_procedimiento'])?><?php if(isset($detalle['cant_proced'])&&$detalle['cant_proced']>0):?> (Cant: <?=htmlspecialchars($detalle['cant_proced'])?>)<?php endif;?></p><?php endif;?>
                                </div>
                            <?php endforeach;?>
                        <?php endif;?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">Este paciente no tiene entradas en su historial clínico.</div>
        <?php endif; ?>
    </div>
    <?php require_once ROOT_PATH . '/include/footer.php'; ?>
    <script src="<?php echo BASE_URL; ?>/medi/js/historial_completo_paciente.js?v=<?php echo time(); ?>"></script>
</body>
</html>