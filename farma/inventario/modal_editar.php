<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo '<div class="alert alert-danger">Error: ID de medicamento no válido.</div>';
    exit;
}
$id_medicamento = $_GET['id'];
$db = new database();
$con = $db->conectar();

$stmt_med = $con->prepare("SELECT nom_medicamento, id_tipo_medic, descripcion, codigo_barras FROM medicamentos WHERE id_medicamento = ?");
$stmt_med->execute([$id_medicamento]);
$medicamento = $stmt_med->fetch(PDO::FETCH_ASSOC);

$stmt_tipos = $con->query("SELECT id_tip_medic, nom_tipo_medi FROM tipo_de_medicamento ORDER BY nom_tipo_medi ASC");
$tipos_medicamento = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);

if (!$medicamento) {
    echo '<div class="alert alert-danger">No se encontró el medicamento.</div>';
    exit;
}
?>
<div class="mb-3">
    <label for="edit_nom_medicamento" class="form-label d-flex align-items-center"><i class="bi bi-capsule-pill me-2"></i>Nombre del Medicamento:</label>
    <input type="text" class="form-control" id="edit_nom_medicamento" name="nom_medicamento" value="<?php echo htmlspecialchars($medicamento['nom_medicamento']); ?>" required>
</div>
<div class="mb-3">
    <label for="edit_id_tipo_medic" class="form-label d-flex align-items-center"><i class="bi bi-tag-fill me-2"></i>Tipo de Medicamento:</label>
    <select class="form-select" id="edit_id_tipo_medic" name="id_tipo_medic" required>
        <option value="0">Seleccione un tipo...</option>
        <?php foreach ($tipos_medicamento as $tipo): ?>
            <option value="<?php echo $tipo['id_tip_medic']; ?>" <?php echo ($medicamento['id_tipo_medic'] == $tipo['id_tip_medic']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipo['nom_tipo_medi']); ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="mb-3">
    <label for="edit_codigo_barras" class="form-label d-flex align-items-center"><i class="bi bi-upc me-2"></i>Código de Barras (Opcional):</label>
    <input type="text" class="form-control" id="edit_codigo_barras" name="codigo_barras" value="<?php echo htmlspecialchars($medicamento['codigo_barras']); ?>" placeholder="Escanear o ingresar código...">
</div>
<div class="mb-3">
    <label for="edit_descripcion" class="form-label d-flex align-items-center"><i class="bi bi-card-text me-2"></i>Descripción:</label>
    <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="4"><?php echo htmlspecialchars($medicamento['descripcion']); ?></textarea>
</div>