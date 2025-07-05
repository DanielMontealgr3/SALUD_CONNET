<?php
// --- BLOQUE 1: CONFIGURACIÓN INICIAL Y SEGURIDAD ---
// Se incluye el archivo de configuración central para acceder a la BD y a las constantes de ruta.
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// --- BLOQUE 2: VALIDACIÓN DEL PARÁMETRO DE ENTRADA ---
// Se verifica que se haya proporcionado un ID de medicamento válido a través de la URL (GET).
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo '<div class="alert alert-danger">Error: ID no válido.</div>';
    exit;
}
$id_medicamento = $_GET['id'];

// La conexión $con ya está disponible desde el archivo config.php.

// --- BLOQUE 3: CONSULTAS A LA BASE DE DATOS ---
// 1. Se obtienen los datos del medicamento específico que se va a editar.
$stmt_med = $con->prepare("SELECT nom_medicamento, id_tipo_medic, descripcion, codigo_barras FROM medicamentos WHERE id_medicamento = ?");
$stmt_med->execute([$id_medicamento]);
$medicamento = $stmt_med->fetch(PDO::FETCH_ASSOC);

// Si no se encuentra el medicamento, se muestra un error y se detiene la ejecución.
if (!$medicamento) {
    echo '<div class="alert alert-danger">No se encontró el medicamento.</div>';
    exit;
}

// 2. Se obtienen todos los tipos de medicamento para poblar el menú desplegable.
$stmt_tipos = $con->query("SELECT id_tip_medic, nom_tipo_medi FROM tipo_de_medicamento ORDER BY nom_tipo_medi ASC");
$tipos_medicamento = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);

// --- BLOQUE 4: RENDERIZADO DEL FORMULARIO HTML ---
// Se genera el contenido HTML del formulario, pre-llenado con los datos del medicamento.
?>
<div class="mb-3">
    <label for="edit_nom_medicamento" class="form-label d-flex align-items-center"><i class="bi bi-capsule-pill me-2"></i>Nombre del Medicamento:</label>
    <input type="text" class="form-control" id="edit_nom_medicamento" name="nom_medicamento" value="<?php echo htmlspecialchars($medicamento['nom_medicamento']); ?>" required>
</div>
<div class="mb-3">
    <label for="edit_id_tipo_medic" class="form-label d-flex align-items-center"><i class="bi bi-tag-fill me-2"></i>Tipo de Medicamento:</label>
    <select class="form-select" id="edit_id_tipo_medic" name="id_tipo_medic" required>
        <option value="">Seleccione...</option>
        <?php foreach ($tipos_medicamento as $tipo): ?>
            <option value="<?php echo $tipo['id_tip_medic']; ?>" <?php echo ($medicamento['id_tipo_medic'] == $tipo['id_tip_medic']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($tipo['nom_tipo_medi']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<div class="mb-3">
    <label for="edit_codigo_barras" class="form-label d-flex align-items-center"><i class="bi bi-upc me-2"></i>Código de Barras (Opcional):</label>
    <input type="text" class="form-control" id="edit_codigo_barras" name="codigo_barras" value="<?php echo htmlspecialchars($medicamento['codigo_barras']); ?>">
</div>
<div class="mb-3">
    <label for="edit_descripcion" class="form-label d-flex align-items-center"><i class="bi bi-card-text me-2"></i>Descripción:</label>
    <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3" required><?php echo htmlspecialchars($medicamento['descripcion']); ?></textarea>
</div>