<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../../../include/conexion.php'; 

$data = null; 
$error_message = null; 
$id_registro_editar = null;
$tipo_registro_editar = null; // 'tipo_enfermedad' o 'enfermedad'
$all_tipos_enfermedad_select = []; // Para el select en caso de editar 'enfermedad'

if (isset($_GET['id_registro']) && isset($_GET['tipo_registro'])) {
    $id_registro_editar = trim($_GET['id_registro']);
    $tipo_registro_editar = trim($_GET['tipo_registro']);
}

if (empty($id_registro_editar) || empty($tipo_registro_editar)) {
    $error_message = "Error: ID o tipo de registro a editar no proporcionado.";
} else {
    $conex = new database();
    $pdo = $conex->conectar();

    if ($pdo) {
        try {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($tipo_registro_editar === 'tipo_enfermedad') {
                $sql = "SELECT id_tipo_enfer, tipo_enfermer FROM tipo_enfermedades WHERE id_tipo_enfer = :id_registro";
                $q = $pdo->prepare($sql);
                $q->bindParam(':id_registro', $id_registro_editar);
                $q->execute();
                $data = $q->fetch(PDO::FETCH_ASSOC);
                if($data) $data['nombre_a_editar'] = $data['tipo_enfermer']; // Nombre genérico
            } elseif ($tipo_registro_editar === 'enfermedad') {
                $sql_tipos_sel = "SELECT id_tipo_enfer, tipo_enfermer FROM tipo_enfermedades ORDER BY tipo_enfermer ASC";
                $all_tipos_enfermedad_select = $pdo->query($sql_tipos_sel)->fetchAll(PDO::FETCH_ASSOC);

                $sql = "SELECT id_enferme, nom_enfer, id_tipo_enfer FROM enfermedades WHERE id_enferme = :id_registro";
                $q = $pdo->prepare($sql);
                $q->bindParam(':id_registro', $id_registro_editar);
                $q->execute();
                $data = $q->fetch(PDO::FETCH_ASSOC);
                if($data) $data['nombre_a_editar'] = $data['nom_enfer']; // Nombre genérico
            } else {
                $error_message = "Tipo de registro no válido para edición.";
            }

            if (!$data && empty($error_message)) {
                $error_message = "No se encontraron datos para el registro ID: " . htmlspecialchars($id_registro_editar) . " de tipo: " . htmlspecialchars($tipo_registro_editar);
            }

        } catch (PDOException $e) {
            $error_message = "Error al consultar datos: " . $e->getMessage();
            error_log("PDOException en modal_editar_registro_enfermedad: " . $e->getMessage());
        } finally {
            $pdo = null;
        }
    } else {
        $error_message = "Error: No se pudo conectar a la base de datos.";
    }
}
?>
<style> 
#editRegistroEnfermedadModal .modal-content { background-color: #f0f2f5; border: 2px solid #87CEEB; border-radius: .3rem; } 
#editRegistroEnfermedadModal .modal-header { background-color: #005A9C; color: white; border-bottom: 1px solid #0047AB; } 
#editRegistroEnfermedadModal .modal-header .btn-close { filter: invert(1) grayscale(100%) brightness(200%); } 
#editRegistroEnfermedadModal .modal-dialog { max-width: 600px; } 
.invalid-feedback { display: block; width: 100%; margin-top: .15rem; font-size: .80em; color: #dc3545; }
</style>

<div class="modal fade" id="editRegistroEnfermedadModal" tabindex="-1" aria-labelledby="editRegistroEnfermedadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRegistroEnfermedadModalLabel">Editar <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($tipo_registro_editar ?? 'Registro'))); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error_message); ?></div>
                <?php elseif ($data): ?>
                    <form id="editRegistroEnfermedadForm" method="POST" novalidate>
                        <input type="hidden" name="id_registro_original" value="<?php echo htmlspecialchars($id_registro_editar); ?>">
                        <input type="hidden" name="tipo_registro" value="<?php echo htmlspecialchars($tipo_registro_editar); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                        <div class="mb-3">
                            <label class="form-label">ID:</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($id_registro_editar); ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label for="nombre_edit" class="form-label">Nombre (*):</label>
                            <input type="text" class="form-control" id="nombre_edit" name="nombre_edit" value="<?php echo htmlspecialchars($data['nombre_a_editar'] ?? ''); ?>" required maxlength="150">
                            <div class="invalid-feedback"></div>
                        </div>

                        <?php if ($tipo_registro_editar === 'enfermedad' && !empty($all_tipos_enfermedad_select)): ?>
                        <div class="mb-3">
                            <label for="id_tipo_enfer_fk_edit" class="form-label">Tipo de Enfermedad (*):</label>
                            <select class="form-select" id="id_tipo_enfer_fk_edit" name="id_tipo_enfer_fk_edit" required>
                                <option value="">Seleccione un tipo...</option>
                                <?php foreach ($all_tipos_enfermedad_select as $tipo): ?>
                                    <option value="<?php echo htmlspecialchars($tipo['id_tipo_enfer']); ?>" <?php echo (isset($data['id_tipo_enfer']) && $data['id_tipo_enfer'] == $tipo['id_tipo_enfer']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tipo['tipo_enfermer']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <?php elseif ($tipo_registro_editar === 'enfermedad' && empty($all_tipos_enfermedad_select)): ?>
                            <div class="alert alert-warning">No hay tipos de enfermedad disponibles para seleccionar.</div>
                        <?php endif; ?>
                        
                        <div class="mt-3 modal-update-message-placeholder"></div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning" role="alert">No se pudieron cargar los datos del registro.</div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <?php if ($data && !$error_message && !($tipo_registro_editar === 'enfermedad' && empty($all_tipos_enfermedad_select))): ?>
                    <button type="submit" class="btn btn-primary" form="editRegistroEnfermedadForm" id="saveRegistroEnfermedadChangesButton" disabled>Guardar Cambios</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>