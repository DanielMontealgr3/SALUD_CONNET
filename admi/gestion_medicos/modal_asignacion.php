<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) { session_start(); }

require_once '../../include/validar_sesion.php';
require_once('../../include/conexion.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) {
  
    echo '<div class="alert alert-danger m-3">Acceso no autorizado.</div>';
    exit;
}

$doc_medico_modal = $_GET['doc_medico'] ?? '';
$nom_medico_modal = $_GET['nom_medico'] ?? 'Médico no especificado';
$csrf_token_modal = $_SESSION['csrf_token'] ?? '';

$conex_db = new database();
$con = $conex_db->conectar();

$lista_ips_modal = [];
$lista_estados_asignacion_modal = [];
$error_carga_modal = ''; 

if ($con) {
    try {
        $stmt_ips = $con->prepare("SELECT Nit_IPS, nom_IPS FROM ips ORDER BY nom_IPS ASC");
        $stmt_ips->execute();
        $lista_ips_modal = $stmt_ips->fetchAll(PDO::FETCH_ASSOC);
        $stmt_estados = $con->prepare("SELECT id_est, nom_est FROM estado WHERE id_est IN (1, 2) ORDER BY FIELD(id_est, 1, 2)");
        $stmt_estados->execute();
        $lista_estados_asignacion_modal = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error_carga_modal = "Error al cargar datos para el modal: " . htmlspecialchars($e->getMessage());
        error_log("Error en modal_asignacion.php al cargar datos: " . $e->getMessage());
    }
} else {
    $error_carga_modal = "Error de conexión a la base de datos para el modal.";
}

?>
<style>
    #modalAsignarIPSContenidoInterno .modal-header { background-color: #005A9C; color: white; border-bottom: 1px solid #0047AB; }
    #modalAsignarIPSContenidoInterno .modal-header .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
    #modalAsignarIPSContenidoInterno .form-label { font-weight: 500; }
    #modalAsignarIPSContenidoInterno .invalid-feedback { display: block; width: 100%; margin-top: .15rem; font-size: .80em; color: #dc3545; }
</style>
<div id="modalAsignarIPSContenidoInterno">
    <div class="modal-header">
        <h5 class="modal-title" id="modalAsignarIPSLabelDinamica">Asignar IPS a Médico</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body p-4">
        <?php if (!empty($error_carga_modal)): ?>
            <div class="alert alert-danger"><?php echo $error_carga_modal; ?></div>
        <?php endif; ?>
        <div id="modalAsignacionGlobalErrorInterno" class="mb-3"></div>
        <div id="modalAsignacionMessageInterno" class="mb-3"></div>

        <form id="formAsignarIPSModalInterno" method="POST" novalidate>
            <input type="hidden" name="doc_medico_asignar" id="doc_medico_asignar_modal" value="<?php echo htmlspecialchars($doc_medico_modal); ?>">
            <input type="hidden" name="nom_medico_original" value="<?php echo htmlspecialchars($nom_medico_modal); ?>"> 
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_modal); ?>">
            <input type="hidden" name="accion_guardar_asignacion" value="1"> 
            
            <div class="alert alert-info bg-light border-info p-3 mb-4 rounded">
                <h6 class="alert-heading">Información del Médico</h6>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Documento:</label>
                        <input type="text" class="form-control form-control-sm bg-white border-0 px-1" value="<?php echo htmlspecialchars($doc_medico_modal); ?>" readonly tabindex="-1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted">Nombre:</label>
                        <input type="text" class="form-control form-control-sm bg-white border-0 px-1" value="<?php echo htmlspecialchars($nom_medico_modal); ?>" readonly tabindex="-1">
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="nit_ips_asignar_modal" class="form-label">Seleccione IPS <span class="text-danger">(*)</span></label>
                    <select id="nit_ips_asignar_modal" name="nit_ips_asignar" class="form-select" required <?php if (!empty($error_carga_modal)) echo 'disabled'; ?>>
                        <option value="">-- Seleccione una IPS --</option>
                        <?php if (!empty($lista_ips_modal)): ?>
                            <?php foreach ($lista_ips_modal as $ips) : ?>
                                <option value="<?php echo htmlspecialchars($ips['Nit_IPS']); ?>">
                                    <?php echo htmlspecialchars($ips['nom_IPS'] . ' (NIT: ' . $ips['Nit_IPS'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>No hay IPS disponibles</option>
                        <?php endif; ?>
                    </select>
                    <div class="invalid-feedback" id="error-nit_ips_asignar_modal">Debe seleccionar una IPS.</div>
                </div>
                <div class="col-md-6">
                    <label for="id_estado_asignacion_modal" class="form-label">Estado de la Asignación <span class="text-danger">(*)</span></label>
                    <select id="id_estado_asignacion_modal" name="id_estado_asignacion" class="form-select" required <?php if (!empty($error_carga_modal)) echo 'disabled'; ?>>
                        <?php if (!empty($lista_estados_asignacion_modal)): ?>
                            <?php foreach ($lista_estados_asignacion_modal as $estado) : ?>
                                <option value="<?php echo htmlspecialchars($estado['id_est']); ?>" <?php echo ($estado['id_est'] == 1) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($estado['nom_est'])); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>No hay estados</option>
                            <option value="1" selected>Activo</option> 
                            <option value="2">Inactivo</option>
                        <?php endif; ?>
                    </select>
                    <div class="invalid-feedback" id="error-id_estado_asignacion_modal">Debe seleccionar un estado.</div>
                </div>
            </div>
             <small class="form-text text-muted mb-3 d-block">
                Nota: Si el médico ya tiene una asignación activa a otra IPS, esta será reemplazada o deberá gestionarla manually.
            </small>
        </form>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="submit" name="guardar_asignacion_modal_submit" class="btn btn-primary" form="formAsignarIPSModalInterno" id="btnGuardarAsignacionModalInterno" <?php if (!empty($error_carga_modal)) echo 'disabled'; ?>>
            <i class="bi bi-building-add me-1"></i>Guardar Asignación
        </button>
    </div>
</div>