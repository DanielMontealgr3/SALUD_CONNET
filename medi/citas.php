<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php'); 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 4 || !isset($_SESSION['nombre_usuario'])) {
    header('Location: ../inicio_sesion.php'); 
    exit;
}

$nombre_usuario = $_SESSION['nombre_usuario'];
$pageTitle = "Citas Programadas";

$db = new Database();
$pdo = $db->conectar();

// Verificar si se aplicó búsqueda
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Consulta de citas con JOIN a estado según búsqueda
if ($busqueda != '') {
    $query = "SELECT c.*, e.nom_est 
              FROM citas c
              INNER JOIN estado e ON c.id_est = e.id_est
              WHERE c.id_cita LIKE :busqueda 
              OR c.doc_pac LIKE :busqueda 
              OR c.doc_med LIKE :busqueda 
              OR c.nit_IPS LIKE :busqueda 
              OR c.fecha_solici LIKE :busqueda 
              OR c.fecha_cita LIKE :busqueda 
              OR c.hora_cita LIKE :busqueda 
              OR e.nom_est LIKE :busqueda";
    $stmt = $pdo->prepare($query);
    $likeBusqueda = "%$busqueda%";
    $stmt->bindParam(':busqueda', $likeBusqueda);
} else {
    $query = "SELECT c.*, e.nom_est 
              FROM citas c
              INNER JOIN estado e ON c.id_est = e.id_est";
    $stmt = $pdo->prepare($query);
}
$stmt->execute();
$citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<head>
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<?php include '../include/menu.php'; ?>

<body>

<div class="container mt-4">
    <h2 class="mb-4">Lista de Citas Programadas</h2>

    <form method="GET" class="mb-4 d-flex align-items-center gap-2">
        <input type="text" name="buscar" class="form-control w-25" placeholder="Buscar en todo..." value="<?= htmlspecialchars($busqueda); ?>">
        <button type="submit" class="btn btn-primary">Buscar</button>
        <a href="citas.php" class="btn btn-secondary">Limpiar</a>
    </form>
    

    <table class="table table-bordered table-hover text-center">
        <thead class="table-dark">
            <tr>
                <th>ID Cita</th>
                <th>Doc. Paciente</th>
                <th>Doc. Médico</th>
                <th>NIT IPS</th>
                <th>Fecha Solicitud</th>
                <th>Fecha Cita</th>
                <th>Hora Cita</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        
        <tbody>
            <?php if (count($citas) > 0) {
                foreach ($citas as $cita) { ?>
                    <tr>
                        <td><?= $cita['id_cita']; ?></td>
                        <td><?= $cita['doc_pac']; ?></td>
                        <td><?= $cita['doc_med']; ?></td>
                        <td><?= $cita['nit_IPS']; ?></td>
                        <td><?= $cita['fecha_solici']; ?></td>
                        <td><?= $cita['fecha_cita']; ?></td>
                        <td><?= $cita['hora_cita']; ?></td>
                        <td><?= $cita['nom_est']; ?></td>
                        <td><a href="iniciar_consulta.php?documento=<?= $cita['doc_pac'] ?>" class="btn btn-sm btn-warning">Iniciar consulta</a></td>
                    </tr>
            <?php }
            } else { ?>
                <tr><td colspan="9">No se encontraron citas con ese criterio.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php include '../include/footer.php'; ?>

</body>
