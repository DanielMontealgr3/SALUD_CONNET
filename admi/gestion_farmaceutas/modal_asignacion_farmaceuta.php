<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

require_once '../include/validar_sesion.php';
require_once('../include/conexion.php');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) {
    echo '<div class="alert alert-danger m-3">Acceso no autorizado.</div>';
    exit;
}

$doc_farma_modal = $_GET['doc_farma'] ?? '';
$nom_farma_modal = $_GET['nom_farma'] ?? 'Farmaceuta no especificado';
$csrf_token_modal = $_SESSION['csrf_token'] ?? '';

$conex_db = new database();
$con = $conex_db->conectar();

$lista_farmacias_modal = [];
$lista_estados_asignacion_modal = [];
$error_carga_modal = '';

if ($con) {
    try {
        $stmt_farmacias = $con->prepare("SELECT nit_farm, nom_farm FROM farmacias ORDER BY nom_farm ASC");
        $stmt_farmacias->execute();
        $lista_farmacias_modal = $stmt_farmacias->fetchAll(PDO::FETCH_ASSOC);

        $stmt_estados = $con->prepare("SELECT id_est, nom_est FROM estado WHERE id_est IN (1, 2) ORDER BY FIELD(id_est, 1, 2)");
        $stmt_estados->execute();
        $lista_estados_asignacion_modal = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error_carga_modal = "Error al cargar datos para el modal: " . htmlspecialchars($e->getMessage());
        error_log("Error en modal_asignacion_farmaceuta.php al cargar datos: " . $e->getMessage());
    }
} else {
    $error_carga_modal = "Error de conexión a la base de datos para el modal.";
}
?>
<style>
    #modalAsignarFarmaciaContenidoInterno .modal-header { 
        background-color: #005A9C; 
        color: white; 
        border-bottom: none;
        border-top-left-radius: calc(.5rem - 2px);
        border-top-right-radius: calc(.5rem - 2px);
        padding: 1rem 1.5rem;
    }
    #modalAsignarFarmaciaContenidoInterno .modal-header .btn-close { 
        filter: invert(1) grayscale(100%) brightness(200%);
        padding: 0.75rem;
        margin: -0.75rem -0.75rem -0.75rem auto;
    }
    #modalAsignarFarmaciaContenidoInterno .modal-title {
        font-size: 1.25rem;
        font-weight: 500;
    }
    #modalAsignarFarmaciaContenidoInterno .modal-body { padding: 1.5rem; }
    #modalAsignarFarmaciaContenidoInterno .form-label { font-weight: 600; margin-bottom: .5rem; color: #343a40; }
    #modalAsignarFarmaciaContenidoInterno .form-select,
    #modalAsignarFarmaciaContenidoInterno .form-control { border-radius: .375rem; }
    #modalAsignarFarmaciaContenidoInterno .info-box {
        border: 1px solid #87CEEB;
        background-color: #ffffff;
        padding: 1.25rem;
        border-radius: .375rem;
        margin-bottom: 1.5rem;
    }
    #modalAsignarFarmaciaContenidoInterno .info-box .form-label { color: #005A9C; margin-bottom: .25rem; font-size: 0.875rem; }
    #modalAsignarFarmaciaContenidoInterno .info-box .form-control[readonly] { background-color: #e9ecef; border: 1px solid #ced4da; }
    #modalAsignarFarmaciaContenidoInterno .invalid-feedback { color: #dc3545; font-size: 0.875em; display: block; margin-top: .25rem; }
    #modalAsignarFarmaciaContenidoInterno .text-danger { color: #dc3545 !important; }
    #modalAsignarFarmaciaContenidoInterno .modal-footer {
        background-color: #f0f2f5;
        border-top: 1px solid #dee2e6;
        padding: 1rem 1.5rem;
        border-bottom-left-radius: calc(.5rem - 2px);
        border-bottom-right-radius: calc(.5rem - 2px);
    }
    #modalAsignarFarmaciaContenidoInterno .modal-footer .btn-primary {
        background-color: #005A9C;
        border-color: #005A9C;
        font-weight: 500;
        padding: .5rem 1rem;
    }
    #modalAsignarFarmaciaContenidoInterno .modal-footer .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
        color: white;
        font-weight: 500;
        padding: .5rem 1rem;
    }
</style>
<div id="modalAsignarFarmaciaContenidoInterno">
    <div class="modal-header">
        <h5 class="modal-title" id="modalAsignarFarmaciaLabelDinamica">Asignar Farmacia a Farmaceuta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <?php if (!empty($error_carga_modal)): ?>
            <div class="alert alert-danger"><?php echo $error_carga_modal; ?></div>
        <?php endif; ?>
        <div id="modalAsignacionFarmaceutaGlobalErrorInterno" class="mb-3"></div>
        <div id="modalAsignacionFarmaceutaMessageInterno" class="mb-3"></div>

        <form id="formAsignarFarmaciaModalInterno" method="POST" novalidate>
            <input type="hidden" name="doc_farma_asignar" id="doc_farma_asignar_modal_hidden_interno" value="<?php echo htmlspecialchars($doc_farma_modal); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_modal); ?>">
            <input type="hidden" name="accion_guardar_asignacion_farmaceuta" value="1">
            
            <div class="info-box">
                <h6 class="form-label" style="color: #005A9C; font-weight:bold; font-size: 1.1rem;">Información del Farmaceuta</h6>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Documento:</label>
                        <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($doc_farma_modal); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre:</label>
                        <input type="text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($nom_farma_modal); ?>" readonly>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="nit_farma_asignar_modal_select_interno" class="form-label">Seleccione Farmacia <span class="text-danger">(*)</span></label>
                    <select id="nit_farma_asignar_modal_select_interno" name="nit_farma_asignar_modal_select" class="form-select" required <?php if (!empty($error_carga_modal)) echo 'disabled'; ?>>
                        <option value="">-- Seleccione una Farmacia --</option>
                        <?php if (!empty($lista_farmacias_modal)): ?>
                            <?php foreach ($lista_farmacias_modal as $farmacia) : ?>
                                <option value="<?php echo htmlspecialchars($farmacia['nit_farm']); ?>">
                                    <?php echo htmlspecialchars($farmacia['nom_farm'] . ' (NIT: ' . $farmacia['nit_farm'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>No hay farmacias disponibles</option>
                        <?php endif; ?>
                    </select>
                    <div class="invalid-feedback" id="error-nit_farma_asignar_modal_interno">Debe seleccionar una farmacia.</div>
                </div>
                <div class="col-md-6">
                    <label for="id_estado_asignacion_farmaceuta_modal_select_interno" class="form-label">Estado de la Asignación <span class="text-danger">(*)</span></label>
                    <select id="id_estado_asignacion_farmaceuta_modal_select_interno" name="id_estado_asignacion_farmaceuta_modal_select" class="form-select" required <?php if (!empty($error_carga_modal)) echo 'disabled'; ?>>
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
                    <div class="invalid-feedback" id="error-id_estado_asignacion_farmaceuta_modal_interno">Debe seleccionar un estado.</div>
                </div>
            </div>
             <small class="form-text text-muted mb-3 d-block">
                Nota: Si el farmaceuta ya tiene una asignación activa a otra farmacia, esta será reemplazada.
            </small>
        </form>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="submit" class="btn btn-primary" form="formAsignarFarmaciaModalInterno" id="btnGuardarAsignacionFarmaceutaModalInterno" <?php if (!empty($error_carga_modal)) echo 'disabled'; ?>>
            <i class="bi bi-shop-window me-1"></i>Guardar Asignación
        </button>
    </div>
</div>