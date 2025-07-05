<?php
// ARCHIVO: deta_historia_clini.php (VERSIÓN FINAL CON FECHA CORREGIDA)
require_once ('../include/validar_sesion.php');
require_once ('../include/inactividad.php');
require_once('../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['id_rol'], [1, 4])) {
    header('Location: ../inicio_sesion.php');
    exit;
}
$db = new Database();
$pdo = $db->conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_detalle_directo') {
    if ($_SESSION['id_rol'] != 4) { $_SESSION['mensaje_error'] = "Acción no autorizada."; header("Location: " . $_SERVER['HTTP_REFERER']); exit; }
    $doc_paciente_redirect = trim($_POST['doc_paciente_redirect'] ?? '');
    $id_cita_url_param = isset($_POST['id_cita_actual']) && !empty($_POST['id_cita_actual']) ? '&id_cita=' . (int)$_POST['id_cita_actual'] : '';
    $redirect_url = "deta_historia_clini.php?documento=" . urlencode($doc_paciente_redirect) . $id_cita_url_param;
    $id_historia = filter_input(INPUT_POST, 'id_historia', FILTER_VALIDATE_INT);
    if (!$id_historia) { $_SESSION['mensaje_error'] = "Error: ID de Historia Clínica no válido."; header("Location: " . $redirect_url); exit; }
    
    $datos_a_insertar = ['id_historia' => $id_historia];
    $diag_id_enferme = filter_input(INPUT_POST, 'modal_id_enferme', FILTER_VALIDATE_INT);
    $diag_id_diagnostico = filter_input(INPUT_POST, 'modal_id_diagnostico', FILTER_VALIDATE_INT);
    if ($diag_id_enferme && $diag_id_diagnostico) {
        $datos_a_insertar['id_enferme'] = $diag_id_enferme;
        $datos_a_insertar['id_diagnostico'] = $diag_id_diagnostico;
    }
    $presc_id_medicam = filter_input(INPUT_POST, 'modal_id_medicam', FILTER_VALIDATE_INT);
    $presc_can_medica = trim($_POST['modal_can_medica'] ?? '');
    $presc_prescripcion = trim($_POST['modal_prescripcion_texto'] ?? '');
    if ($presc_id_medicam && !empty($presc_can_medica)) {
        $datos_a_insertar['id_medicam'] = $presc_id_medicam;
        $datos_a_insertar['can_medica'] = $presc_can_medica;
        $datos_a_insertar['prescripcion'] = $presc_prescripcion;
    }
    $proc_id_proced = filter_input(INPUT_POST, 'modal_id_proced', FILTER_VALIDATE_INT);
    if ($proc_id_proced) {
        $proc_cant_proced = filter_input(INPUT_POST, 'modal_cant_proced', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
        $datos_a_insertar['id_proced'] = $proc_id_proced;
        $datos_a_insertar['cant_proced'] = $proc_cant_proced;
    }
    if (count($datos_a_insertar) > 1) {
        try {
            $pdo->beginTransaction();
            $columnas = implode(', ', array_keys($datos_a_insertar));
            $placeholders = implode(', ', array_fill(0, count($datos_a_insertar), '?'));
            $sql = "INSERT INTO detalles_histo_clini ($columnas) VALUES ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($datos_a_insertar));
            $pdo->commit();
            $_SESSION['mensaje_exito'] = "Los detalles de la consulta han sido guardados correctamente.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Error al guardar detalles: " . $e->getMessage());
            $_SESSION['mensaje_error'] = "Ocurrió un error técnico al guardar. Por favor, intente de nuevo.";
        }
    } else { $_SESSION['mensaje_advertencia'] = "No se proporcionaron detalles completos para guardar."; }
    header("Location: " . $redirect_url);
    exit;
}

$documento_paciente_url = $_GET['documento'] ?? null;
$pageTitle = "Historia Clínica"; 
if (!$documento_paciente_url) { $_SESSION['mensaje_error'] = "Documento del paciente no proporcionado."; header('Location: ' . (($_SESSION['id_rol'] == 4) ? 'citas_hoy.php' : '../admin/dashboard.php')); exit; }
$stmtPaciente = $pdo->prepare("SELECT nom_usu, foto_usu FROM usuarios WHERE doc_usu = :documento");
$stmtPaciente->execute([':documento' => $documento_paciente_url]);
$paciente = $stmtPaciente->fetch(PDO::FETCH_ASSOC);
if (!$paciente) { $_SESSION['mensaje_error'] = "Paciente no encontrado: " . htmlspecialchars($documento_paciente_url); header('Location: ' . (($_SESSION['id_rol'] == 4) ? 'citas_hoy.php' : '../admin/dashboard.php')); exit;}
$pageTitle = "Historia Clínica: " . htmlspecialchars($paciente['nom_usu']);
$id_cita_especifica = isset($_GET['id_cita']) ? (int)$_GET['id_cita'] : null;

// ==========================================================
// == CORRECCIÓN PRINCIPAL: SQL AJUSTADO PARA INCLUIR FECHA Y HORA
// ==========================================================
$baseQueryHistoria = "
    SELECT 
        hc.*, 
        hm.fecha_horario AS fecha_atencion, 
        hm.horario AS hora_atencion 
    FROM historia_clinica hc 
    JOIN citas c ON hc.id_cita = c.id_cita 
    LEFT JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med
    WHERE c.doc_pac = :documento";

$params = [':documento' => $documento_paciente_url];
if ($id_cita_especifica) {
    $baseQueryHistoria .= " AND c.id_cita = :id_cita";
    $params[':id_cita'] = $id_cita_especifica;
}
$baseQueryHistoria .= " ORDER BY hc.id_historia DESC LIMIT 1";
$stmt = $pdo->prepare($baseQueryHistoria);
$stmt->execute($params);
$ultimaHistoriaClinica = $stmt->fetch(PDO::FETCH_ASSOC);
$id_historia_actual = $ultimaHistoriaClinica ? $ultimaHistoriaClinica['id_historia'] : null;

// CARGA DE DATOS PARA MODALES Y DETALLES
$tiposEnfermedad = $pdo->query("SELECT id_tipo_enfer, tipo_enfermer FROM tipo_enfermedades ORDER BY tipo_enfermer")->fetchAll(PDO::FETCH_ASSOC);
$enfermedades = $pdo->query("SELECT e.id_enferme, e.nom_enfer, e.id_tipo_enfer FROM enfermedades e ORDER BY e.nom_enfer")->fetchAll(PDO::FETCH_ASSOC);
$diagnosticos = $pdo->query("SELECT id_diagnos, diagnostico FROM diagnostico ORDER BY diagnostico")->fetchAll(PDO::FETCH_ASSOC);
$tiposMedicamento = $pdo->query("SELECT id_tip_medic, nom_tipo_medi FROM tipo_de_medicamento ORDER BY nom_tipo_medi")->fetchAll(PDO::FETCH_ASSOC);
$medicamentos = $pdo->query("SELECT id_medicamento, nom_medicamento, id_tipo_medic FROM medicamentos ORDER BY nom_medicamento")->fetchAll(PDO::FETCH_ASSOC);
$procedimientos = $pdo->query("SELECT id_proced, procedimiento FROM procedimientos ORDER BY procedimiento")->fetchAll(PDO::FETCH_ASSOC);

// ... (código anterior de carga de datos) ...

$detallesExistentes = [];
if ($id_historia_actual) {
    // ====================================================================
    // == CORRECCIÓN DEFINITIVA: USAR LA CONSULTA QUE SELECCIONA TODO    ==
    // ====================================================================
    // Esta consulta trae todas las columnas de la tabla de detalles (det.*)
    // y usa LEFT JOIN para obtener los nombres correspondientes de las otras tablas.
    $queryDetalles = "
        SELECT 
            det.*, 
            d.diagnostico AS nombre_diagnostico, 
            enf.nom_enfer, 
            med.nom_medicamento, 
            proc.procedimiento AS nombre_procedimiento 
        FROM detalles_histo_clini det 
        LEFT JOIN diagnostico d ON det.id_diagnostico = d.id_diagnos 
        LEFT JOIN enfermedades enf ON det.id_enferme = enf.id_enferme 
        LEFT JOIN medicamentos med ON det.id_medicam = med.id_medicamento 
        LEFT JOIN procedimientos proc ON det.id_proced = proc.id_proced 
        WHERE det.id_historia = :id_historia 
        ORDER BY det.id_detalle ASC
    ";
    $stmtDetalles = $pdo->prepare($queryDetalles);
    $stmtDetalles->execute([':id_historia' => $id_historia_actual]);
    $detallesExistentes = $stmtDetalles->fetchAll(PDO::FETCH_ASSOC);
}

// La lógica para validar si hay medicamentos reales se mantiene igual,
// ya que ahora $detallesExistentes vuelve a tener toda la información.
$hayMedicamentosReales = false;
if (!empty($detallesExistentes)) {
    foreach ($detallesExistentes as $detalle) {
        // Comprueba si existe la clave, no es nula y el ID es diferente de 40 (No Aplica)
        if (isset($detalle['id_medicam']) && $detalle['id_medicam'] !== null && $detalle['id_medicam'] != 40) {
            $hayMedicamentosReales = true;
            break; // No es necesario seguir buscando, rompemos el bucle
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Historia Clínica'); ?></title>
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"> -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> -->
    <style>.patient-info-header { background-color: #f8f9fa; padding: 1rem; border-radius: 0.3rem; margin-bottom: 1.5rem; border: 1px solid #dee2e6; } .modal-body { max-height: calc(100vh - 220px); overflow-y: auto; }</style>
</head>
<body>
  
    <div class="container mt-4">
        <div class="patient-info-header">
             <div class="d-flex align-items-center">
                <?php if (!empty($paciente['foto_usu'])): ?>
                    <img src="../fotos_usuarios/<?php echo htmlspecialchars($paciente['foto_usu']); ?>" alt="Foto Paciente" class="img-thumbnail me-3" style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <div class="img-thumbnail me-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background-color: #e9ecef; border-radius: 50%;"><i class="fas fa-user fa-2x text-secondary"></i></div>
                <?php endif; ?>
                <div><h3><?= htmlspecialchars($paciente['nom_usu']); ?></h3><p class="mb-0 text-muted">Documento: <?= htmlspecialchars($documento_paciente_url); ?></p></div>
                <div class="ms-auto"><a href="historial_completo_paciente.php?documento=<?= htmlspecialchars($documento_paciente_url) ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-history"></i> Ver Historial</a><a href="citas_hoy.php" class="btn btn-outline-secondary ms-2 btn-sm"><i class="fas fa-arrow-left"></i> Volver</a></div>
            </div>
        </div>
        
        <?php if (isset($_SESSION['mensaje_exito'])): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= $_SESSION['mensaje_exito']; unset($_SESSION['mensaje_exito']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
        <?php if (isset($_SESSION['mensaje_error'])): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $_SESSION['mensaje_error']; unset($_SESSION['mensaje_error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
        <?php if (isset($_SESSION['mensaje_advertencia'])): ?><div class="alert alert-warning alert-dismissible fade show" role="alert"><?= $_SESSION['mensaje_advertencia']; unset($_SESSION['mensaje_advertencia']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

        <?php if ($ultimaHistoriaClinica): ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <span>Resumen de Consulta (ID Historia: <?= htmlspecialchars($id_historia_actual) ?>)</span>
                    <span class="text-muted">Fecha: <?= isset($ultimaHistoriaClinica['fecha_atencion']) ? htmlspecialchars(date('d/m/Y h:i A', strtotime($ultimaHistoriaClinica['fecha_atencion'] . ' ' . $ultimaHistoriaClinica['hora_atencion']))) : 'N/D' ?></span>
                </div>
                <div class="card-body">
                    <p><strong>Motivo:</strong> <?= nl2br(htmlspecialchars($ultimaHistoriaClinica['motivo_de_cons'] ?? 'N/R')) ?></p>
                    <p><strong>Signos Vitales:</strong> 
                        P: <?= htmlspecialchars($ultimaHistoriaClinica['presion'] ?: 'N/R') ?> | 
                        Sat: <?= htmlspecialchars($ultimaHistoriaClinica['saturacion'] ?: 'N/R') ?> | 
                        Peso: <?= htmlspecialchars($ultimaHistoriaClinica['peso'] ? number_format((float)$ultimaHistoriaClinica['peso'], 2) . ' kg' : 'N/R') ?> | 
                        Est: <?= htmlspecialchars($ultimaHistoriaClinica['estatura'] ? number_format((float)$ultimaHistoriaClinica['estatura'], 2) . ' cm' : 'N/R') ?>
                    </p>
                    <p><strong>Obs. Generales:</strong> <?= nl2br(htmlspecialchars($ultimaHistoriaClinica['observaciones'] ?? 'N/R')) ?></p>
                </div>
                <div class="card-footer text-center">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAgregarDetallesConsulta"><i class="fas fa-notes-medical"></i> Iniciar Detalle De Consulta</button>
                    <?php if ($hayMedicamentosReales): ?>
        <a href="agendar_turno_farmacia.php?documento=<?= htmlspecialchars($documento_paciente_url) ?>" class="btn btn-info">
            <i class="fas fa-pills"></i> Dispensar Medicamentos
        </a>
    <?php else: ?>
        <!-- Opcional: Mostrar un botón deshabilitado para que el usuario sepa que existe la opción -->
        <button type="button" class="btn btn-info" disabled title="No hay medicamentos recetados para dispensar">
            <i class="fas fa-pills"></i> Dispensar Medicamentos
        </button>
        <a href="citas_hoy.php" style="display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px;">Finalizar</a>
    <?php endif; ?>
</div>

                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No se encontró historia clínica activa para esta cita.</div>
        <?php endif; ?>
        
        <div id="detallesHistoriaClinicaExistente">
            <?php include '../include/menu.php'; ?>
            <h4>Detalles Registrados en esta Consulta</h4>
            <?php if ($id_historia_actual && !empty($detallesExistentes)): ?>
                <div class="list-group">
                    <?php foreach ($detallesExistentes as $detalle): ?>
                    <div class="list-group-item list-group-item-action mb-2 shadow-sm">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">
                                <?php 
                                    $details_summary = [];
                                    if (!empty($detalle['nombre_diagnostico'])) $details_summary[] = '<i class="fas fa-stethoscope text-primary"></i> Diagnóstico';
                                    if (!empty($detalle['nom_medicamento'])) $details_summary[] = '<i class="fas fa-pills text-info"></i> Prescripción';
                                    if (!empty($detalle['nombre_procedimiento'])) $details_summary[] = '<i class="fas fa-procedures text-warning"></i> Procedimiento';
                                    echo implode(' / ', $details_summary);
                                ?>
                            </h5>
                            <small>Registro #<?= htmlspecialchars($detalle['id_detalle']) ?></small>
                        </div>
                        <?php if (!empty($detalle['nombre_diagnostico'])): ?><p class="mb-1"><strong>Diagnóstico:</strong> <?= htmlspecialchars($detalle['nombre_diagnostico']) ?> (<?= htmlspecialchars($detalle['nom_enfer']) ?>)</p><?php endif; ?>
                        <?php if (!empty($detalle['nom_medicamento'])): ?><p class="mb-1"><strong>Prescripción:</strong> <?= htmlspecialchars($detalle['nom_medicamento']) ?> - Cant: <?= htmlspecialchars($detalle['can_medica']) ?>. Indicaciones: <?= nl2br(htmlspecialchars($detalle['prescripcion'])) ?></p><?php endif; ?>
                        <?php if (!empty($detalle['nombre_procedimiento'])): ?><p class="mb-0"><strong>Procedimiento:</strong> <?= htmlspecialchars($detalle['nombre_procedimiento']) ?><?= isset($detalle['cant_proced']) ? ' (Cantidad: '.htmlspecialchars($detalle['cant_proced']).')' : '' ?></p><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif($id_historia_actual): ?>
                <p><em>Aún no hay detalles específicos registrados para esta consulta.</em></p>
            <?php endif; ?>
            
        </div>
    </div>
    <?php include '../include/footer.php'; ?>

    <!-- MODAL PRINCIPAL: AGREGAR DETALLES -->
    <div class="modal fade" id="modalAgregarDetallesConsulta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>?documento=<?= htmlspecialchars($documento_paciente_url ?? '') ?>" method="POST" id="formModalDetalles" novalidate>
                    <div class="modal-header"><h5 class="modal-title">Agregar Detalles a la Consulta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="guardar_detalle_directo"><input type="hidden" name="id_historia" value="<?= htmlspecialchars($id_historia_actual ?? '') ?>"><input type="hidden" name="doc_paciente_redirect" value="<?= htmlspecialchars($documento_paciente_url ?? '') ?>"><input type="hidden" name="id_cita_actual" value="<?= htmlspecialchars($id_cita_especifica ?? '') ?>">
                        
                        <ul class="nav nav-tabs" id="detalleTabs" role="tablist">
                            <li class="nav-item" role="presentation"><button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#diagnostico-tab-pane">Diagnóstico</button></li>
                            <li class="nav-item" role="presentation"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#prescripcion-tab-pane">Prescripción</button></li>
                            <li class="nav-item" role="presentation"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#procedimiento-tab-pane">Procedimiento</button></li>
                        </ul>
                        <div class="tab-content pt-3">
   <!-- PESTAÑA DIAGNÓSTICO -->
<div class="tab-pane fade show active" id="diagnostico-tab-pane">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="modal_id_tipo_enfer" class="form-label">Tipo Enfermedad</label>
            <select id="modal_id_tipo_enfer" class="form-select">
                <?php 
                // Se recorren todos los tipos de enfermedad y se pre-selecciona
                // el que tenga el ID 22, que corresponde a "No aplica".
                foreach ($tiposEnfermedad as $t): ?>
                    <option value="<?= $t['id_tipo_enfer'] ?>" <?= ($t['id_tipo_enfer'] == 22) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['tipo_enfermer']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="modal_id_enferme" class="form-label">Enfermedad</label>
            <!-- Este select se llena con JS, pero podemos ponerle el "No Aplica" por defecto desde PHP -->
            <select id="modal_id_enferme" name="modal_id_enferme" class="form-select">
                <?php 
                // Buscamos la enfermedad "No Aplica" (ID 116 según tu captura) para ponerla por defecto.
                foreach ($enfermedades as $e) {
                    if ($e['id_enferme'] == 116) {
                        echo "<option value='116' selected>" . htmlspecialchars($e['nom_enfer']) . "</option>";
                        break;
                    }
                }
                ?>
            </select>
        </div>
    </div>
    <div class="mb-3">
        <label for="modal_id_diagnostico" class="form-label">Diagnóstico CIE-10</label>
        <select id="modal_id_diagnostico" name="modal_id_diagnostico" class="form-select">
            <?php 
            // Se recorren todos los diagnósticos y se pre-selecciona el que tenga el ID 49.
            foreach ($diagnosticos as $d): ?>
                <option value="<?= $d['id_diagnos'] ?>" <?= ($d['id_diagnos'] == 49) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['diagnostico']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>


    <!-- PESTAÑA PRESCRIPCIÓN (VERSIÓN DINÁMICA FINAL) -->
<div class="tab-pane fade" id="prescripcion-tab-pane">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="modal_id_tip_medic" class="form-label">Tipo Medicamento</label>
            <select id="modal_id_tip_medic" class="form-select">
                <?php
                // Se recorren todos los tipos y se pre-selecciona el que tenga el ID 26 ("No aplica").
                foreach ($tiposMedicamento as $tm): ?>
                    <option value="<?= $tm['id_tip_medic'] ?>" <?= ($tm['id_tip_medic'] == 26) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tm['nom_tipo_medi']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="modal_id_medicam">Medicamento</label>
            <select id="modal_id_medicam" name="modal_id_medicam" class="form-select">
                <?php
                // Se recorren todos los medicamentos y se pre-selecciona el que tenga el ID 40 ("No aplica").
                // El JavaScript se encargará de filtrar esto cuando se cambie el tipo.
                foreach ($medicamentos as $m): ?>
                    <option value="<?= $m['id_medicamento'] ?>" <?= ($m['id_medicamento'] == 40) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['nom_medicamento']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="modal_can_medica" class="form-label">Cantidad</label>
            <!-- Se establece el valor por defecto a "0 Unidades" -->
            <input type="text" id="modal_can_medica" name="modal_can_medica" class="form-control" placeholder="Ej: 10 tabletas" data-validate="alfanumerico" value="0 Unidades">
            <div class="invalid-feedback"></div>
        </div>
        <div class="col-md-8 mb-3">
            <label for="modal_prescripcion_texto">Posología/Indicaciones</label>
            <!-- Se establece el valor por defecto a "No Aplica" -->
            <textarea id="modal_prescripcion_texto" name="modal_prescripcion_texto" class="form-control" rows="3" placeholder="Ej: Tomar 1 tableta cada 8 horas" data-validate="alfanumerico">No Aplica</textarea>
            <div class="invalid-feedback"></div>
        </div>
    </div>
</div>

   <!-- PESTAÑA PROCEDIMIENTO (VERSIÓN DINÁMICA FINAL) -->
<div class="tab-pane fade" id="procedimiento-tab-pane">
    <div class="row">
        <div class="col-md-8 mb-3">
            <label for="modal_id_proced" class="form-label">Procedimiento</label>
            <select id="modal_id_proced" name="modal_id_proced" class="form-select">
                <?php
                // Se recorren todos los procedimientos y se pre-selecciona
                // el que tenga el ID 36, que corresponde a "No aplica".
                foreach ($procedimientos as $p): ?>
                    <option value="<?= $p['id_proced'] ?>" <?= ($p['id_proced'] == 36) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['procedimiento']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label for="modal_cant_proced" class="form-label">Cantidad (Opcional)</label>
            <!-- Se establece el valor por defecto a 0 y el tipo a 'number' -->
            <input type="number" id="modal_cant_proced" name="modal_cant_proced" class="form-control" min="0" placeholder="0" data-validate="numerico" value="0">
            <div class="invalid-feedback"></div>
        </div>
    </div>
</div>
</div><!-- Cierre de .modal-body -->
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary me-auto" id="btnPrevTab" style="display: none;">Anterior</button>
    <button type="button" class="btn btn-outline-primary" id="btnNextTab">Siguiente</button>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-success" id="btnGuardarDirecto" style="display: none;"><i class="fas fa-save"></i> Guardar y Cerrar</button>
</div>
</form> <!-- Cierre del form -->
</div>
</div>
</div>

<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
    <script>
        window.enfermedadesData = <?php echo json_encode($enfermedades ?? []); ?>;
        window.medicamentosData = <?php echo json_encode($medicamentos ?? []); ?>;
        </script>
    <script src="js/deta_historia_clini.js"></script>
    
</body>

</html>