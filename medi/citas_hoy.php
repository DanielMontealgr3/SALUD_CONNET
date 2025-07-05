<?php
// BLOQUE 1: CONFIGURACIÓN Y SEGURIDAD
require_once __DIR__ . '/../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

// Verificación de rol específico para esta página.
if ($_SESSION['id_rol'] != 4) {
    header('Location: ' . BASE_URL . '/inicio_sesion.php');
    exit;
}

// BLOQUE 2: LÓGICA DE NEGOCIO
define('ID_ESTADO_PROGRAMADA', 3);
define('ID_ESTADO_LISTA_PARA_LLAMAR', 10);
define('ID_ESTADO_EN_PROCESO', 11);

$doc_medico_logueado = $_SESSION['doc_usu'];

// Consulta corregida para evitar duplicados y centrar las tarjetas
$sql = "SELECT 
            c.id_cita, c.doc_pac, c.id_est, e.nom_est, up.nom_usu AS nom_paciente, 
            hm.fecha_horario, hm.horario, 
            MAX(hc.id_historia) AS id_historia
        FROM citas c 
        JOIN estado e ON c.id_est = e.id_est 
        JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med
        LEFT JOIN usuarios up ON c.doc_pac = up.doc_usu
        LEFT JOIN historia_clinica hc ON c.id_cita = hc.id_cita
        WHERE hm.doc_medico = ? AND hm.fecha_horario = CURDATE() AND c.id_est IN (?, ?, ?)
        GROUP BY c.id_cita, c.doc_pac, c.id_est, e.nom_est, up.nom_usu, hm.fecha_horario, hm.horario
        ORDER BY hm.horario ASC";
        
$stmt = $con->prepare($sql);
$stmt->execute([$doc_medico_logueado, ID_ESTADO_PROGRAMADA, ID_ESTADO_LISTA_PARA_LLAMAR, ID_ESTADO_EN_PROCESO]);
$citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Gestión de Citas Diarias';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require_once ROOT_PATH . '/include/menu.php'; ?>
    <style>
        .page-header { background-color: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .real-time-clock { text-align: right; }
        .real-time-clock strong { display: block; font-size: 1.5rem; color: #005A9C; }
        .real-time-clock small { font-size: 0.9rem; color: #6c757d; }
        .appointments-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 1.5rem; }
        .appointment-card { background-color: #fff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.08); border-left: 5px solid; padding: 1rem; transition: all 0.3s ease; opacity: 1; width: 330px; flex-shrink: 0; }
        .status-programada { border-color: #0dcaf0; }
        .status-lista-para-llamar { border-color: #0d6efd; }
        .status-retrasada { border-color: #dc3545; }
        .status-en-proceso { border-color: #ffc107; }
        .row-adding { animation: fadeIn 0.5s ease-out; }
        .row-removing { animation: fadeOut 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeOut { from { opacity: 1; } to { opacity: 0; transform: scale(0.95); } }
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
        .appointment-time { font-size: 1.2rem; font-weight: bold; color: #343a40; }
        .appointment-status { font-size: 0.9rem; font-weight: 500; }
        .patient-info strong { display: block; font-size: 1.1rem; }
        .actions-container { margin-top: 1rem; text-align: right; }
        .legend { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; justify-content: center; }
        .legend-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; }
        .legend-color { width: 15px; height: 15px; border-radius: 3px; }
    </style>
</head>
<body>
    <main class="container-fluid mt-4">
        <div class="page-header">
            <div>
                <h2 class="mb-0"><?php echo $pageTitle; ?></h2>
                <p class="text-muted mb-0">Vista en tiempo real de sus citas para hoy.</p>
            </div>
            <div id="real-time-clock" class="real-time-clock"></div>
        </div>

        <div class="legend">
            <div class="legend-item"><div class="legend-color" style="background-color: #0dcaf0;"></div>Programada</div>
            <div class="legend-item"><div class="legend-color" style="background-color: #0d6efd;"></div>Lista para llamar</div>
            <div class="legend-item"><div class="legend-color" style="background-color: #dc3545;"></div>Retrasada</div>
            <div class="legend-item"><div class="legend-color" style="background-color: #ffc107;"></div>En Consulta</div>
        </div>

        <div id="appointments-grid" class="appointments-grid">
            <?php if (empty($citas)): ?>
                <div id="no-citas-message" class="alert alert-info w-100 text-center">No tiene citas activas para hoy.</div>
            <?php else: ?>
                <?php foreach ($citas as $cita): 
                    $fecha_hora_cita_str = $cita['fecha_horario'] . 'T' . $cita['horario'];
                ?>
                    <div id="cita-row-<?php echo $cita['id_cita']; ?>" class="appointment-card"
                         data-cita-id="<?php echo $cita['id_cita']; ?>"
                         data-datetime="<?php echo $fecha_hora_cita_str; ?>"
                         data-doc-paciente="<?php echo htmlspecialchars($cita['doc_pac']); ?>"
                         data-nom-paciente="<?php echo htmlspecialchars($cita['nom_paciente'] ?? 'N/A'); ?>">
                        
                        <div class="card-header-flex">
                            <div class="appointment-time"><?php echo date('h:i A', strtotime($cita['horario'])); ?></div>
                            <div class="appointment-status" id="estado-cita-<?php echo $cita['id_cita']; ?>">
                                <span class="badge rounded-pill bg-info text-dark"><?php echo htmlspecialchars($cita['nom_est']); ?></span>
                            </div>
                        </div>
                        <div class="patient-info">
                            <strong><?php echo htmlspecialchars($cita['nom_paciente'] ?? 'Paciente no especificado'); ?></strong>
                            <small class="text-muted">DOC: <?php echo htmlspecialchars($cita['doc_pac']); ?></small>
                        </div>
                        <div class="actions-container" id="actions-cita-<?php echo $cita['id_cita']; ?>">
                            <?php if ($cita['id_est'] == ID_ESTADO_PROGRAMADA): ?>
                                <button type="button" class="btn btn-sm btn-secondary llamar-paciente-btn" disabled><i class="bi bi-hourglass-split"></i> Esperando hora</button>
                                <button type="button" class="btn btn-sm btn-success paciente-llego-btn" style="display:none;"><i class="bi bi-check-circle"></i> Paciente Llegó</button>
                            <?php elseif ($cita['id_est'] == ID_ESTADO_LISTA_PARA_LLAMAR): ?>
                                <button type="button" class="btn btn-primary iniciar-consulta-btn" data-bs-toggle="modal" data-bs-target="#modalConsulta" data-id-cita="<?php echo $cita['id_cita']; ?>" data-doc-paciente="<?php echo htmlspecialchars($cita['doc_pac']); ?>" data-nom-paciente="<?php echo htmlspecialchars($cita['nom_paciente'] ?? 'N/A'); ?>">
                                    <i class="bi bi-play-circle"></i> Iniciar Consulta
                                </button>
                            <?php elseif ($cita['id_est'] == ID_ESTADO_EN_PROCESO && !empty($cita['id_historia'])): ?>
                                <!-- === INICIO DEL BLOQUE CORREGIDO (HTML) === -->
                                <!-- Se añade la clase 'continuar-consulta-link' para identificarlo en JS -->
                                <a href="<?php echo BASE_URL; ?>/medi/deta_historia_clini.php?id=<?php echo $cita['id_historia']; ?>" class="btn btn-sm btn-warning continuar-consulta-link">
                                    <i class="bi bi-pencil-square"></i> Continuar Consulta
                                </a>
                                <!-- === FIN DEL BLOQUE CORREGIDO (HTML) === -->
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php
    require_once __DIR__ . '/includes_medi/modal_inicio_cita.php'; 
    require_once ROOT_PATH . '/include/footer.php'; 
    ?>
    
    <script>
        if (typeof window.AppConfig === 'undefined') {
            window.AppConfig = { BASE_URL: '<?php echo BASE_URL; ?>' };
        }
    </script>
   
    <script src="<?php echo BASE_URL; ?>/medi/js/vista_citas.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo BASE_URL; ?>/medi/js/inicio_consul.js?v=<?php echo time(); ?>"></script>
</body>
</html>