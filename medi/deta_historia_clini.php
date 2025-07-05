<?php
require_once __DIR__ . '/../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

if (!in_array($_SESSION['id_rol'] ?? null, [1, 4])) {
    header('Location: ' . BASE_URL . '/inicio_sesion.php');
    exit;
}

$id_historia_actual = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id_historia_actual) {
    $_SESSION['mensaje_error'] = "No se especificó un ID de historia clínica válido.";
    header('Location: ' . BASE_URL . '/medi/citas_hoy.php');
    exit;
}

try {
    $sql_principal = "
        SELECT 
            hc.*, c.id_cita, u.doc_usu, u.nom_usu, u.correo_usu, u.foto_usu, 
            hm.fecha_horario, hm.horario
        FROM historia_clinica hc
        JOIN citas c ON hc.id_cita = c.id_cita
        LEFT JOIN usuarios u ON c.doc_pac = u.doc_usu
        LEFT JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med
        WHERE hc.id_historia = :id_historia";
    
    $stmtPrincipal = $con->prepare($sql_principal);
    $stmtPrincipal->execute([':id_historia' => $id_historia_actual]);
    $datosConsulta = $stmtPrincipal->fetch(PDO::FETCH_ASSOC);

    if (!$datosConsulta) {
        $_SESSION['mensaje_error'] = "No se encontró la historia clínica solicitada.";
        header('Location: ' . BASE_URL . '/medi/citas_hoy.php');
        exit;
    }
    
    $paciente = [ 'nom_usu' => $datosConsulta['nom_usu'], 'correo_usu' => $datosConsulta['correo_usu'], 'foto_usu' => $datosConsulta['foto_usu'] ];
    $documento_paciente_url = $datosConsulta['doc_usu'];
    $ultimaHistoriaClinica = $datosConsulta;
    $pageTitle = "Historia Clínica: " . htmlspecialchars($paciente['nom_usu']);

    $tiposEnfermedad = $con->query("SELECT id_tipo_enfer, tipo_enfermer FROM tipo_enfermedades ORDER BY tipo_enfermer ASC")->fetchAll(PDO::FETCH_ASSOC);
    $enfermedades = $con->query("SELECT id_enferme, nom_enfer, id_tipo_enfer FROM enfermedades ORDER BY nom_enfer ASC")->fetchAll(PDO::FETCH_ASSOC);
    $diagnosticos = $con->query("SELECT id_diagnos, diagnostico FROM diagnostico ORDER BY diagnostico ASC")->fetchAll(PDO::FETCH_ASSOC);
    $tiposMedicamento = $con->query("SELECT id_tip_medic, nom_tipo_medi FROM tipo_de_medicamento ORDER BY nom_tipo_medi ASC")->fetchAll(PDO::FETCH_ASSOC);
    $medicamentos = $con->query("SELECT id_medicamento, nom_medicamento, id_tipo_medic FROM medicamentos ORDER BY nom_medicamento ASC")->fetchAll(PDO::FETCH_ASSOC);
    $procedimientos = $con->query("SELECT id_proced, procedimiento FROM procedimientos ORDER BY procedimiento ASC")->fetchAll(PDO::FETCH_ASSOC);

    $queryDetalles = "
        SELECT det.*, d.diagnostico AS nombre_diagnostico, enf.nom_enfer, med.nom_medicamento, proc.procedimiento AS nombre_procedimiento 
        FROM detalles_histo_clini det 
        LEFT JOIN diagnostico d ON det.id_diagnostico = d.id_diagnos 
        LEFT JOIN enfermedades enf ON det.id_enferme = enf.id_enferme 
        LEFT JOIN medicamentos med ON det.id_medicam = med.id_medicamento 
        LEFT JOIN procedimientos proc ON det.id_proced = proc.id_proced 
        WHERE det.id_historia = :id_historia ORDER BY det.id_detalle ASC";
    $stmtDetalles = $con->prepare($queryDetalles);
    $stmtDetalles->execute([':id_historia' => $id_historia_actual]);
    $detallesExistentes = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
    
    $hayDetallesGuardados = !empty($detallesExistentes);
    $hayMedicamentosReales = false;
    foreach ($detallesExistentes as $detalle) {
        if (!empty($detalle['id_medicam']) && $detalle['id_medicam'] != 40) {
            $hayMedicamentosReales = true;
            break;
        }
    }
} catch (PDOException $e) {
    error_log("Error en deta_historia_clini.php: " . $e->getMessage());
    $_SESSION['mensaje_error'] = "Error de base de datos.";
    header('Location: ' . BASE_URL . '/medi/citas_hoy.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once ROOT_PATH . '/include/menu.php'; ?>
    <title><?php echo $pageTitle; ?></title>
    <style>
        .patient-info-header { background-color: #f8f9fa; padding: 1.5rem; border-radius: .5rem; margin-bottom: 1.5rem; border: 1px solid #dee2e6; }
        .modal-body { max-height: calc(100vh - 210px); overflow-y: auto; }
        .card-footer .btn { margin: 0 5px; }
        .nav-tabs .nav-link.active { color: #005A9C; border-color: #dee2e6 #dee2e6 #fff; font-weight: bold; }
        .detail-section h5 { color: #005A9C; border-bottom: 2px solid #005A9C; padding-bottom: 5px; margin-top: 1rem; }
    </style>
</head>
<body>
    <main class="container my-4">
        <div class="patient-info-header d-flex align-items-center flex-wrap">
            <?php if (!empty($paciente['foto_usu'])): ?>
                <img src="<?php echo BASE_URL . '/' . htmlspecialchars($paciente['foto_usu']); ?>" alt="Foto Paciente" class="img-thumbnail me-3" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%;">
            <?php else: ?>
                <div class="img-thumbnail me-3 d-flex align-items-center justify-content-center bg-light" style="width: 80px; height: 80px; border-radius: 50%;"><i class="bi bi-person-fill" style="font-size: 2.5rem; color: #6c757d;"></i></div>
            <?php endif; ?>
            <div>
                <h3><?php echo htmlspecialchars($paciente['nom_usu'] ?? 'Paciente no registrado'); ?></h3>
                <p class="mb-0 text-muted">Documento: <?php echo htmlspecialchars($documento_paciente_url); ?></p>
            </div>
            <div class="ms-auto mt-2 mt-md-0 d-flex gap-2">
                <a href="<?php echo BASE_URL; ?>/medi/historial_completo_paciente.php?documento=<?php echo htmlspecialchars($documento_paciente_url); ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-clock-history"></i> Ver Historial Clínico</a>
                <a href="<?php echo BASE_URL; ?>/medi/citas_hoy.php" class="btn btn-outline-secondary ms-2 btn-sm"><i class="bi bi-arrow-left"></i> Volver a Citas</a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['mensaje_exito'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert"><?= $_SESSION['mensaje_exito']; unset($_SESSION['mensaje_exito']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['mensaje_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $_SESSION['mensaje_error']; unset($_SESSION['mensaje_error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if ($ultimaHistoriaClinica): ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong class="text-primary">Resumen de Consulta (ID Historia: <?php echo $id_historia_actual; ?>)</strong>
                    <span class="text-muted"><i class="bi bi-calendar-event me-1"></i> <?php echo date('d/m/Y h:i A', strtotime($ultimaHistoriaClinica['fecha_horario'] . ' ' . $ultimaHistoriaClinica['horario'])); ?></span>
                </div>
                <div class="card-body">
                    <p><strong>Motivo:</strong> <?php echo nl2br(htmlspecialchars($ultimaHistoriaClinica['motivo_de_cons'] ?? 'N/R')); ?></p>
                    <p><strong>Signos Vitales:</strong> 
                        P: <strong><?php echo htmlspecialchars($ultimaHistoriaClinica['presion'] ?: 'N/R'); ?></strong> | 
                        Sat: <strong><?php echo htmlspecialchars($ultimaHistoriaClinica['saturacion'] ?: 'N/R'); ?>%</strong> | 
                        Peso: <strong><?php echo htmlspecialchars($ultimaHistoriaClinica['peso'] ? number_format((float)$ultimaHistoriaClinica['peso'], 2) . ' kg' : 'N/R'); ?></strong> | 
                        Est: <strong><?php echo htmlspecialchars($ultimaHistoriaClinica['estatura'] ? number_format((float)$ultimaHistoriaClinica['estatura'], 2) . ' m' : 'N/R'); ?></strong>
                    </p>
                    <p><strong>Observaciones:</strong> <?php echo nl2br(htmlspecialchars($ultimaHistoriaClinica['observaciones'] ?? 'N/R')); ?></p>
                </div>
                <div class="card-footer text-center bg-light">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarDetalles"><i class="bi bi-plus-circle-fill me-1"></i> Agregar Detalles</button>
                    <a href="<?php echo BASE_URL; ?>/medi/citas_hoy.php" id="btnFinalizarConsulta" class="btn btn-success <?php if (!$hayDetallesGuardados) echo 'disabled'; ?>"><i class="bi bi-check-all me-1"></i> Finalizar Consulta</a>
                </div>
            </div>
        <?php endif; ?>
        
        <h4>Detalles Registrados</h4>
        <div id="listaDetallesContainer" class="list-group">
            <?php if (empty($detallesExistentes)): ?>
                <p id="no-detalles-msg" class="text-muted fst-italic">Aún no hay detalles específicos registrados.</p>
            <?php else: ?>
                <?php foreach ($detallesExistentes as $detalle): ?>
                    <div class="list-group-item list-group-item-action flex-column align-items-start mb-2 shadow-sm">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">
                                <?php 
                                    $summary = [];
                                    if (!empty($detalle['nombre_diagnostico'])) $summary[] = '<i class="bi bi-file-earmark-medical text-primary"></i> Diagnóstico';
                                    if (!empty($detalle['nom_medicamento'])) $summary[] = '<i class="bi bi-capsule-pill text-info"></i> Prescripción';
                                    if (!empty($detalle['nombre_procedimiento'])) $summary[] = '<i class="bi bi-heart-pulse text-warning"></i> Procedimiento';
                                    echo empty($summary) ? 'Registro de Detalle' : implode(' / ', $summary);
                                ?>
                            </h5>
                            <div>
                                <button type="button" class="btn btn-outline-primary btn-sm ver-detalle-btn" data-id-detalle="<?php echo htmlspecialchars($detalle['id_detalle']); ?>" data-bs-toggle="modal" data-bs-target="#modalVerDetalle">
                                    <i class="bi bi-eye-fill"></i> Ver
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <div class="modal fade" id="modalAgregarDetalles" tabindex="-1" aria-labelledby="modalDetallesLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalDetallesLabel"><i class="bi bi-journal-plus me-2"></i>Agregar Detalles a la Consulta</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formModalDetalles" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="id_historia" value="<?php echo htmlspecialchars($id_historia_actual ?? ''); ?>">
                        <ul class="nav nav-tabs" id="detalleTabs" role="tablist">
                            <li class="nav-item" role="presentation"><button class="nav-link active" id="tab-btn-diag" data-bs-toggle="tab" data-bs-target="#diagnostico-tab-pane" type="button" role="tab">1. Diagnóstico</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-btn-presc" data-bs-toggle="tab" data-bs-target="#prescripcion-tab-pane" type="button" role="tab">2. Prescripción</button></li>
                            <li class="nav-item" role="presentation"><button class="nav-link" id="tab-btn-proc" data-bs-toggle="tab" data-bs-target="#procedimiento-tab-pane" type="button" role="tab">3. Procedimiento</button></li>
                        </ul>
                        <div class="tab-content p-3 border border-top-0 rounded-bottom">
                            <div class="tab-pane fade show active" id="diagnostico-tab-pane" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="modal_id_tipo_enfer" class="form-label">Tipo Enfermedad <span class="text-danger">*</span></label>
                                        <select id="modal_id_tipo_enfer" class="form-select" required>
                                            <option value="" disabled selected>Seleccione un tipo...</option>
                                            <option value="22">No aplica</option>
                                            <?php foreach ($tiposEnfermedad as $t) { if ($t['id_tipo_enfer'] != 22) echo "<option value='{$t['id_tipo_enfer']}'>".htmlspecialchars($t['tipo_enfermer'])."</option>"; } ?>
                                        </select>
                                        <div class="invalid-feedback">Este campo es obligatorio.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="modal_id_enferme" class="form-label">Enfermedad <span class="text-danger">*</span></label>
                                        <select id="modal_id_enferme" name="id_enferme" class="form-select" required>
                                            <option value="" disabled selected>Seleccione un tipo primero...</option>
                                        </select>
                                        <div class="invalid-feedback">Este campo es obligatorio.</div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="modal_id_diagnostico" class="form-label">Diagnóstico (CIE-10) <span class="text-danger">*</span></label>
                                    <select id="modal_id_diagnostico" name="id_diagnostico" class="form-select" required>
                                        <option value="" disabled selected>Seleccione un diagnóstico...</option>
                                        <option value="49">No aplica</option>
                                        <?php foreach ($diagnosticos as $d) { if ($d['id_diagnos'] != 49) echo "<option value='{$d['id_diagnos']}'>".htmlspecialchars($d['diagnostico'])."</option>"; } ?>
                                    </select>
                                    <div class="invalid-feedback">Este campo es obligatorio.</div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="prescripcion-tab-pane" role="tabpanel">
                               <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="modal_id_tip_medic" class="form-label">Tipo Medicamento <span class="text-danger">*</span></label>
                                        <select id="modal_id_tip_medic" class="form-select" required>
                                            <option value="" disabled selected>Seleccione un tipo...</option>
                                            <option value="26">No aplica</option>
                                            <?php foreach ($tiposMedicamento as $tm) { if ($tm['id_tip_medic'] != 26) echo "<option value='{$tm['id_tip_medic']}'>".htmlspecialchars($tm['nom_tipo_medi'])."</option>"; } ?>
                                        </select>
                                        <div class="invalid-feedback">Este campo es obligatorio.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="modal_id_medicam" class="form-label">Medicamento <span class="text-danger">*</span></label>
                                        <select id="modal_id_medicam" name="id_medicam" class="form-select" required>
                                            <option value="" disabled selected>Seleccione un tipo primero...</option>
                                        </select>
                                        <div class="invalid-feedback">Este campo es obligatorio.</div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="modal_can_medica" class="form-label">Cantidad</label>
                                        <input type="text" id="modal_can_medica" name="can_medica" class="form-control" placeholder="Ej: 10 tabletas">
                                        <div class="invalid-feedback">La cantidad es obligatoria si receta un medicamento.</div>
                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <label for="modal_prescripcion_texto" class="form-label">Posología/Indicaciones</label>
                                        <textarea id="modal_prescripcion_texto" name="prescripcion" class="form-control" rows="2" placeholder="Ej: Tomar 1 tableta cada 8 horas"></textarea>
                                        <div class="invalid-feedback">La posología es obligatoria si receta un medicamento.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="procedimiento-tab-pane" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="modal_id_proced" class="form-label">Procedimiento <span class="text-danger">*</span></label>
                                        <select id="modal_id_proced" name="id_proced" class="form-select" required>
                                            <option value="" disabled selected>Seleccione un procedimiento...</option>
                                            <option value="36">No aplica</option>
                                            <?php foreach ($procedimientos as $p) { if ($p['id_proced'] != 36) echo "<option value='{$p['id_proced']}'>".htmlspecialchars($p['procedimiento'])."</option>"; } ?>
                                        </select>
                                        <div class="invalid-feedback">Este campo es obligatorio.</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="modal_cant_proced" class="form-label">Cantidad</label>
                                        <input type="number" id="modal_cant_proced" name="cant_proced" class="form-control" min="1" placeholder="Ej: 1">
                                        <div class="invalid-feedback">La cantidad es obligatoria si solicita un procedimiento.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary me-auto" id="btnPrevTab" style="display: none;">Anterior</button>
                        <button type="button" class="btn btn-outline-primary" id="btnNextTab" disabled>Siguiente</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" id="btnGuardarDetallesAjax" style="display: none;" disabled><i class="bi bi-save-fill me-1"></i> Guardar y Cerrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalVerDetalle" tabindex="-1" aria-labelledby="modalVerDetalleLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="modalVerDetalleLabel"><i class="bi bi-card-list me-2"></i>Detalle Completo del Registro</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="modalVerDetalleBody">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
                                            
    <?php require_once ROOT_PATH . '/include/footer.php'; ?>
    <script>
        if (typeof window.AppConfig === 'undefined') { window.AppConfig = { BASE_URL: '<?php echo BASE_URL; ?>' }; }
        window.phpData = {
            enfermedades: <?php echo json_encode($enfermedades ?? []); ?>,
            medicamentos: <?php echo json_encode($medicamentos ?? []); ?>,
        };
    </script>
    <script src="<?php echo BASE_URL; ?>/medi/js/deta_historia_clini.js?v=<?php echo time(); ?>"></script>
</body>
</html>