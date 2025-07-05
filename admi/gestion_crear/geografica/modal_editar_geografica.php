<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../../../include/conexion.php'; // Ajusta la ruta si es necesario

$data = null; 
$error_message = null; 
$all_departamentos = [];
$municipios_del_depto_actual = [];

$id_registro_editar = null;
$tipo_registro_editar = null;

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

            if ($tipo_registro_editar === 'departamento') {
                $sql = "SELECT id_dep, nom_dep FROM departamento WHERE id_dep = :id_registro";
                $q = $pdo->prepare($sql);
                $q->bindParam(':id_registro', $id_registro_editar);
                $q->execute();
                $data = $q->fetch(PDO::FETCH_ASSOC);
            } elseif ($tipo_registro_editar === 'municipio') {
                $sql = "SELECT id_mun, nom_mun, id_dep FROM municipio WHERE id_mun = :id_registro";
                $q = $pdo->prepare($sql);
                $q->bindParam(':id_registro', $id_registro_editar);
                $q->execute();
                $data = $q->fetch(PDO::FETCH_ASSOC);
                if ($data) {
                    $sql_deps = "SELECT id_dep, nom_dep FROM departamento ORDER BY nom_dep";
                    $all_departamentos = $pdo->query($sql_deps)->fetchAll(PDO::FETCH_ASSOC);
                }
            } elseif ($tipo_registro_editar === 'barrio') {
                $sql = "SELECT b.id_barrio, b.nom_barrio, b.id_mun, m.id_dep 
                        FROM barrio b 
                        JOIN municipio m ON b.id_mun = m.id_mun 
                        WHERE b.id_barrio = :id_registro";
                $q = $pdo->prepare($sql);
                $q->bindParam(':id_registro', $id_registro_editar);
                $q->execute();
                $data = $q->fetch(PDO::FETCH_ASSOC);
                if ($data) {
                    $sql_deps = "SELECT id_dep, nom_dep FROM departamento ORDER BY nom_dep";
                    $all_departamentos = $pdo->query($sql_deps)->fetchAll(PDO::FETCH_ASSOC);
                    if (!empty($data['id_dep'])) {
                        $sql_muns = "SELECT id_mun, nom_mun FROM municipio WHERE id_dep = :id_dep ORDER BY nom_mun";
                        $q_muns = $pdo->prepare($sql_muns);
                        $q_muns->bindParam(':id_dep', $data['id_dep']);
                        $q_muns->execute();
                        $municipios_del_depto_actual = $q_muns->fetchAll(PDO::FETCH_ASSOC);
                    }
                }
            } else {
                $error_message = "Tipo de registro no válido.";
            }

            if (!$data && empty($error_message)) {
                $error_message = "No se encontraron datos para el registro: " . htmlspecialchars($id_registro_editar);
            }

        } catch (PDOException $e) {
            $error_message = "Error al consultar datos: " . $e->getMessage();
            error_log("PDOException en modal_editar_geografica: " . $e->getMessage());
        } finally {
            $pdo = null;
        }
    } else {
        $error_message = "Error: No se pudo conectar a la base de datos.";
    }
}
?>
<style> /* Estilos copiados de tu ejemplo de modal de usuario, ajusta si es necesario */
#editGeograficaModal .modal-content { background-color: #f0f2f5; border: 2px solid #87CEEB; border-radius: .3rem; } 
#editGeograficaModal .modal-header { background-color: #005A9C; color: white; border-bottom: 1px solid #0047AB; } 
#editGeograficaModal .modal-header .btn-close { filter: invert(1) grayscale(100%) brightness(200%); } 
#editGeograficaModal .modal-dialog { max-width: 600px; } 
.invalid-feedback { display: block; width: 100%; margin-top: .15rem; font-size: .80em; color: #dc3545; }
</style>

<div class="modal fade" id="editGeograficaModal" tabindex="-1" aria-labelledby="editGeograficaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editGeograficaModalLabel">Editar <?php echo ucfirst(htmlspecialchars($tipo_registro_editar ?? 'Registro')); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error_message); ?></div>
                <?php elseif ($data): ?>
                    <form id="editGeograficaForm" method="POST" novalidate>
                        <input type="hidden" name="id_registro_original" value="<?php echo htmlspecialchars($id_registro_editar); ?>">
                        <input type="hidden" name="tipo_registro" value="<?php echo htmlspecialchars($tipo_registro_editar); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                        <?php if ($tipo_registro_editar === 'departamento'): ?>
                            <div class="mb-3">
                                <label class="form-label">ID Departamento:</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($data['id_dep']); ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label for="nom_dep_edit" class="form-label">Nombre Departamento (*):</label>
                                <input type="text" class="form-control" id="nom_dep_edit" name="nom_dep_edit" value="<?php echo htmlspecialchars($data['nom_dep']); ?>" required maxlength="100">
                                <div class="invalid-feedback"></div>
                            </div>
                        <?php elseif ($tipo_registro_editar === 'municipio'): ?>
                            <div class="mb-3">
                                <label class="form-label">ID Municipio:</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($data['id_mun']); ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label for="nom_mun_edit" class="form-label">Nombre Municipio (*):</label>
                                <input type="text" class="form-control" id="nom_mun_edit" name="nom_mun_edit" value="<?php echo htmlspecialchars($data['nom_mun']); ?>" required maxlength="100">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="id_dep_edit_mun" class="form-label">Departamento (*):</label>
                                <select class="form-select" id="id_dep_edit_mun" name="id_dep_edit_mun" required>
                                    <option value="">Seleccione un departamento...</option>
                                    <?php foreach ($all_departamentos as $depto): ?>
                                        <option value="<?php echo htmlspecialchars($depto['id_dep']); ?>" <?php echo ($data['id_dep'] == $depto['id_dep']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($depto['nom_dep']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        <?php elseif ($tipo_registro_editar === 'barrio'): ?>
                             <div class="mb-3">
                                <label class="form-label">ID Barrio:</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($data['id_barrio']); ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label for="nom_barrio_edit" class="form-label">Nombre Barrio (*):</label>
                                <input type="text" class="form-control" id="nom_barrio_edit" name="nom_barrio_edit" value="<?php echo htmlspecialchars($data['nom_barrio']); ?>" required maxlength="150">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="id_dep_edit_barrio" class="form-label">Departamento (*):</label>
                                <select class="form-select" id="id_dep_edit_barrio" name="id_dep_edit_barrio" required>
                                    <option value="">Seleccione un departamento...</option>
                                    <?php foreach ($all_departamentos as $depto): ?>
                                        <option value="<?php echo htmlspecialchars($depto['id_dep']); ?>" <?php echo ($data['id_dep'] == $depto['id_dep']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($depto['nom_dep']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="mb-3">
                                <label for="id_mun_edit_barrio" class="form-label">Municipio (*):</label>
                                <select class="form-select" id="id_mun_edit_barrio" name="id_mun_edit_barrio" required <?php echo empty($municipios_del_depto_actual) ? 'disabled' : '';?>>
                                    <option value="">Seleccione un municipio...</option>
                                    <?php foreach ($municipios_del_depto_actual as $mun): ?>
                                        <option value="<?php echo htmlspecialchars($mun['id_mun']); ?>" <?php echo ($data['id_mun'] == $mun['id_mun']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($mun['nom_mun']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        <?php endif; ?>
                        <div id="modalEditGeograficaUpdateMessage" class="mt-3"></div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning" role="alert">No se pudieron cargar los datos del registro.</div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <?php if ($data && !$error_message): ?>
                    <button type="submit" class="btn btn-primary" form="editGeograficaForm" id="saveGeograficaChangesButton" disabled>Guardar Cambios</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>