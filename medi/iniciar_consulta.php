<?php
require_once '../include/validar_sesion.php';
require_once('../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 4 || !isset($_SESSION['nombre_usuario'])) {
    header('Location: ../inicio_sesion.php');
    exit;
}

$nombre_usuario = $_SESSION['nombre_usuario'];
$pageTitle = "Iniciar Consulta Médica";

// Conexión a la base de datos
$db = new Database();
$pdo = $db->conectar();

// Obtener el documento del paciente desde el parámetro GET
$documento = $_GET['documento'] ?? null;
if (!$documento) {
    echo "Documento no proporcionado.";
    exit;
}

// Consulta para obtener los datos de la cita y el paciente
$query = "SELECT c.id_cita, c.doc_pac, c.doc_med, c.nit_IPS, c.fecha_solici, c.fecha_cita, c.hora_cita, c.id_est, u.nom_usu, u.doc_usu
          FROM citas c
          INNER JOIN usuarios u ON u.doc_usu = c.doc_pac  -- Unimos citas con usuarios usando el campo doc_usu
          WHERE c.doc_pac = :documento";

$stmt = $pdo->prepare($query);
$stmt->bindParam(':documento', $documento, PDO::PARAM_STR);
$stmt->execute();

$cita = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar si se encontraron resultados
if (!$cita) {
    echo "No se encontraron datos de cita para el paciente con el documento proporcionado.";
    exit;
}

// Consulta para obtener el nombre del estado
$queryEstado = "SELECT nom_est FROM estado WHERE id_est = :id_est";
$stmtEstado = $pdo->prepare($queryEstado);
$stmtEstado->bindParam(':id_est', $cita['id_est'], PDO::PARAM_INT);
$stmtEstado->execute();
$estado = $stmtEstado->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>

<?php include '../include/menu.php'; ?>

<div class="container mt-4">
    
    <h2 class="mb-4">Consulta Médica para <?= htmlspecialchars($cita['nom_usu']); ?> (Documento: <?= htmlspecialchars($cita['doc_usu']); ?>)</h2>
    <form action="guarda_consul.php" method="POST">
        <input type="hidden" name="id_cita" value="<?= htmlspecialchars($cita['id_cita']); ?>">

        <div class="mb-3">
            <label for="motivo_de_cons" class="form-label">Motivo de consulta</label>
            <textarea class="form-control" id="motivo_de_cons" name="motivo_de_cons" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label for="presion" class="form-label">Presión</label>
            <input type="text" class="form-control" id="presion" name="presion">
        </div>

        <div class="mb-3">
            <label for="saturacion" class="form-label">Saturación</label>
            <input type="text" class="form-control" id="saturacion" name="saturacion">
        </div>

        <div class="mb-3">
            <label for="peso" class="form-label">Peso</label>
            <input type="text" class="form-control" id="peso" name="peso">
        </div>

        <div class="mb-3">
            <label for="observaciones" class="form-label">Observaciones</label>
            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
        </div>

        <div class="mb-3">
            <label for="estatura" class="form-label">Estatura</label>
            <input type="text" class="form-control" id="estatura" name="estatura">
        </div>

        <button type="submit" class="btn btn-primary">Guardar Consulta</button>
        <!-- tienviar al diagnosco  -->
        <script src="../js/inicio_consul.js"></script>
    </form>
</div>


<?php include '../include/footer.php'; ?>

</body>
</html>
