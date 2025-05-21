<?php
require_once ('../include/validar_sesion.php');
require_once('../include/conexion.php');
require_once( '../include/menu.php');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


$nombre_usuario = $_SESSION['nombre_usuario'];

// Conexión a la base de datos
$db = new Database();
$pdo = $db->conectar();

// Obtener el documento del paciente desde la URL
$documento = $_GET['documento'] ?? null;
if (!$documento) {
    echo "Documento no proporcionado.";
    exit;
}
// echo "Documento($documento)";

// Obtener datos de la cita
$query = "SELECT * FROM citas WHERE doc_pac = :documento";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':documento', $documento, PDO::PARAM_STR);
$stmt->execute();
$cita = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cita) {
    echo "No se encontró cita para el documento proporcionado.";
    exit;
}

$query = "SELECT nom_usu FROM usuarios WHERE doc_usu = :documento";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':documento', $documento, PDO::PARAM_STR);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario) {
    $nombre = $usuario['nom_usu'];
    echo "<h4>Documento ($documento) - $nombre</h4>";
} else {
    echo "<h4>Documento ($documento) - Usuario no encontrado</h4>";
}





// var_dump($cita['doc_pac']); // Ahora sí puedes usarlo

// Obtener tipos de enfermedades
$queryTipos = "SELECT id_tipo_enfer, tipo_enfermer FROM tipo_enfermedades ORDER BY tipo_enfermer";
$stmtTipos = $pdo->prepare($queryTipos);
$stmtTipos->execute();
$tiposEnfermedad = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);

// Obtener todas las enfermedades con su tipo
$queryEnfermedades = "SELECT e.id_enferme, e.nom_enfer, e.id_tipo_enfer, t.tipo_enfermer 
                      FROM enfermedades e 
                      INNER JOIN tipo_enfermedades t ON e.id_tipo_enfer = t.id_tipo_enfer 
                      ORDER BY t.tipo_enfermer, e.nom_enfer";
$stmtEnfermedades = $pdo->prepare($queryEnfermedades);
$stmtEnfermedades->execute();
$enfermedades = $stmtEnfermedades->fetchAll(PDO::FETCH_ASSOC);

// Obtener Diagnósticos
$queryDiagnosticos = "SELECT id_diagnos, diagnostico FROM diagnostico ORDER BY diagnostico";
$stmtDiagnosticos = $pdo->prepare($queryDiagnosticos);
$stmtDiagnosticos->execute();
$diagnosticos = $stmtDiagnosticos->fetchAll(PDO::FETCH_ASSOC);

// Obtener Tipos de Medicamento
$queryTiposMedicamento = "SELECT id_tip_medic, nom_tipo_medi FROM tipo_de_medicamento ORDER BY nom_tipo_medi";
$stmtTiposMedicamento = $pdo->prepare($queryTiposMedicamento);
$stmtTiposMedicamento->execute();
$tiposMedicamento = $stmtTiposMedicamento->fetchAll(PDO::FETCH_ASSOC);


// medicamentos
$queryMedicamento = "SELECT id_medicamento, nom_medicamento, id_tipo_medic, descripcion FROM medicamentos ORDER BY nom_medicamento";
$stmtMedicamento = $pdo->prepare($queryMedicamento);
$stmtMedicamento->execute();
$medicamentos = $stmtMedicamento->fetchAll(PDO::FETCH_ASSOC);

// procedimientos
// Obtener Procedimientos
$queryProcedimientos = "SELECT id_proced, procedimiento FROM procedimientos ORDER BY procedimiento";
$stmtProcedimientos = $pdo->prepare($queryProcedimientos);
$stmtProcedimientos->execute();
$procedimientos = $stmtProcedimientos->fetchAll(PDO::FETCH_ASSOC);


// Obtener Historias Clínicas
$queryHistorias = "SELECT id_historia,id_cita,motivo_de_cons,presion,saturacion,peso,observaciones,estatura FROM historia_clinica ORDER BY id_historia";
$stmtHistorias = $pdo->prepare($queryHistorias);
$stmtHistorias->execute();
$historiasClinicas = $stmtHistorias->fetchAll(PDO::FETCH_ASSOC);

?>

<form action="guarda_detalle_historia_clinica.php" method="POST">


<div class="mb-3">
    <label for="id_historia" class="form-label">Seleccione la Historia Clínica</label>
    <select name="id_historia" id="id_historia" class="form-select" required>
        <option value="">Seleccione una historia</option>
        <?php foreach ($historiasClinicas as $historia) { ?>
            <option value="<?= $historia['id_historia'] ?>"><?= htmlspecialchars($historia['motivo_de_cons']) ?></option>
        <?php } ?>
    </select>
</div>
  <div class="mb-3">
        <label for="id_tipo_enfer" class="form-label">Seleccionar Tipo de Enfermedad</label>
        <select name="id_tipo_enfer" id="id_tipo_enfer" class="form-select" required onchange="filtrarEnfermedades()">
            <option value="">Seleccione un tipo</option>
            <?php foreach ($tiposEnfermedad as $tipo) { ?>
                <option value="<?= $tipo['id_tipo_enfer'] ?>"><?= htmlspecialchars($tipo['tipo_enfermer']) ?></option>
            <?php } ?>
        </select>
    </div>

    <div class="mb-3">
        <label for="id_enferme" class="form-label">Seleccionar Enfermedad</label>
        <select name="id_enferme" id="id_enferme" class="form-select" required>
            <option value="">Seleccione una enfermedad</option>
            <?php
            foreach ($enfermedades as $enfermedad) {
                echo '<option value="' . $enfermedad['id_enferme'] . '" data-tipo="' . $enfermedad['id_tipo_enfer'] . '">'
                    . htmlspecialchars($enfermedad['nom_enfer']) . '</option>';
            }
            ?>
        </select>
    </div>
     

    <div class="mb-3">
        <label for="id_diagnostico" class="form-label">Seleccionar Diagnóstico</label>
        <select name="id_diagnostico" id="id_diagnostico" class="form-select" required>
            <option value="">Seleccione un diagnóstico</option>
            <?php foreach ($diagnosticos as $diag) { ?>
                <option value="<?= $diag['id_diagnos'] ?>"><?= htmlspecialchars($diag['diagnostico']) ?></option>
            <?php } ?>
        </select>
    </div>

    <div class="mb-3">
        <label for="id_tip_medic" class="form-label">Seleccionar Tipo de Medicamento</label>
        <select name="id_tip_medic" id="id_tip_medic" class="form-select" required>
            <option value="">Seleccione un tipo de medicamento</option>
            <?php foreach ($tiposMedicamento as $tipoMed) { ?>
                <option value="<?= $tipoMed['id_tip_medic'] ?>"><?= htmlspecialchars($tipoMed['nom_tipo_medi']) ?></option>
            <?php } ?>
        </select>
        <div class="mb-3">
    <label for="id_medicam">Medicamento</label>
    <select name="id_medicam" id="id_medicam" class="form-select" required>
        <option value="">Seleccione medicamento</option>
        <?php foreach ($medicamentos as $med) { ?>
            <option value="<?= $med['id_medicamento'] ?>" data-tipo="<?= $med['id_tipo_medic'] ?>" data-descripcion="<?= htmlspecialchars($med['descripcion']) ?>">
                <?= htmlspecialchars($med['nom_medicamento']) ?>
            </option>
        <?php } ?>
    </select>

    <small id="descripcionMedicamento" class="form-text text-muted mt-1"></small>
</div>
<div class="mb-3">
    <label for="can_medica" class="form-label">Cantidad de Medicamento</label>
    <input type="text" name="can_medica" id="can_medica" class="form-control">
    <span class="mensaje-error-cliente" id="error-can_medica"></span>
</div>

<div class="mb-3">
    <label for="id_proced" class="form-label">Seleccionar Procedimiento</label>
    <select name="id_proced" id="id_proced" class="form-select" required>
        <option value="">Seleccione un procedimiento</option>
        <?php foreach ($procedimientos as $proc) { ?>
            <option value="<?= $proc['id_proced'] ?>"><?= htmlspecialchars($proc['procedimiento']) ?></option>
        <?php } ?>
    </select>
</div>

<div class="mb-3">
    <label for="cant_proced" class="form-label">Cantidad de Procedimientos</label>
    <input type="text" name="cant_proced" id="cant_proced" class="form-control">
     <span class="mensaje-error-cliente" id="error-cant_proced"></span>
</div>
<div class="mb-3">
    <label for="prescripcion">Prescripción</label>
    <textarea name="prescripcion" id="prescripcion" class="form-control"></textarea>
</div>  

    <button type="submit" class="btn btn-success">Guardar Diagnóstico</button>

<?php include '../include/footer.php'; ?>
</form>

<script>
function filtrarEnfermedades() {
    const tipoSeleccionado = document.getElementById('id_tipo_enfer').value;
    const opciones = document.getElementById('id_enferme').options;
    for (let i = 0; i < opciones.length; i++) {
        const opcion = opciones[i];
        if (opcion.value === "") {
            opcion.style.display = '';
            continue;
        }
        opcion.style.display = (opcion.getAttribute('data-tipo') === tipoSeleccionado) ? '' : 'none';
    }
    document.getElementById('id_enferme').value = "";
}

function filtrarMedicamentos() {
    const tipoSeleccionado = document.getElementById('id_tip_medic').value;
    const opciones = document.getElementById('id_medicam').options;
    for (let i = 0; i < opciones.length; i++) {
        const opcion = opciones[i];
        if (opcion.value === "") {
            opcion.style.display = '';
            continue;
        }
        opcion.style.display = (opcion.getAttribute('data-tipo') === tipoSeleccionado) ? '' : 'none';
    }
    document.getElementById('id_medicamento').value = "";
    document.getElementById('descripcionMedicamento').innerText = '';
}

document.getElementById('id_medicamento').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const descripcion = selectedOption.getAttribute('data-descripcion') || '';
    document.getElementById('descripcionMedicamento').innerText = descripcion;
});


</script>
<script src="../js/detalle_histo.js"></script>
<style>
    
    .input-error {
    border-color: red;
}
.input-success {
    border-color: green;
}
.mensaje-error-cliente {
    color: red;
    font-size: 0.875em;
}
.visible {
    display: block;
}
</style>

<!-- si el procedimiento es 46 que no lo envie a nada si no que se guarde y ya -->