<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { exit('Método no permitido.'); }
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['id_rol'] != 1) { exit('Acceso no autorizado.'); }

$id_alianza = $_GET['id'] ?? 0;
$tabla_origen = $_GET['tabla'] ?? '';

$tablas_permitidas = ['detalle_eps_farm', 'detalle_eps_ips'];
if (!in_array($tabla_origen, $tablas_permitidas)) { exit('Parámetro no válido.'); }

$db = new database();
$con = $db->conectar();
$pk_columna = ($tabla_origen == 'detalle_eps_farm') ? 'id_eps_farm' : 'id_eps_ips';
$nit_aliado_col = ($tabla_origen == 'detalle_eps_farm') ? 'nit_farm' : 'nit_ips';

$sql = "";
if ($tabla_origen == 'detalle_eps_farm') {
    $sql = "SELECT d.*, e.nombre_eps, e.direc_eps, e.telefono as tel_eps, e.correo as correo_eps, e.nom_gerente as gerente_eps, f.nom_farm as nombre_aliado, f.nit_farm as nit_aliado, f.direc_farm as direc_aliado, f.tel_farm as tel_aliado, f.correo_farm as correo_aliado, f.nom_gerente as gerente_aliado
            FROM detalle_eps_farm d
            JOIN eps e ON d.nit_eps = e.nit_eps
            JOIN farmacias f ON d.nit_farm = f.nit_farm
            WHERE d.id_eps_farm = :id";
} else {
    $sql = "SELECT d.*, e.nombre_eps, e.direc_eps, e.telefono as tel_eps, e.correo as correo_eps, e.nom_gerente as gerente_eps, i.nom_IPS as nombre_aliado, i.Nit_IPS as nit_aliado, i.direc_IPS as direc_aliado, i.tel_IPS as tel_aliado, i.correo_IPS as correo_aliado, i.nom_gerente as gerente_aliado
            FROM detalle_eps_ips d
            JOIN eps e ON d.nit_eps = e.nit_eps
            JOIN ips i ON d.nit_ips = i.Nit_IPS
            WHERE d.id_eps_ips = :id";
}

try {
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':id', $id_alianza, PDO::PARAM_INT);
    $stmt->execute();
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($detalle) {
    ?>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="detalle-seccion">
                <h6><i class="bi bi-building"></i> Datos EPS</h6>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Nombre:</strong> <?php echo htmlspecialchars($detalle['nombre_eps']); ?></li>
                    <li class="list-group-item"><strong>NIT:</strong> <?php echo htmlspecialchars($detalle['nit_eps']); ?></li>
                    <li class="list-group-item"><strong>Gerente / Representante:</strong> <?php echo htmlspecialchars($detalle['gerente_eps'] ?? 'N/A'); ?></li>
                    <li class="list-group-item"><strong>Dirección:</strong> <?php echo htmlspecialchars($detalle['direc_eps'] ?? 'N/A'); ?></li>
                    <li class="list-group-item"><strong>Teléfono:</strong> <?php echo htmlspecialchars($detalle['tel_eps'] ?? 'N/A'); ?></li>
                    <li class="list-group-item"><strong>Correo Electrónico:</strong> <?php echo htmlspecialchars($detalle['correo_eps'] ?? 'N/A'); ?></li>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="detalle-seccion">
                <h6><i class="bi bi-hospital"></i> Datos Entidad Aliada</h6>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Nombre:</strong> <?php echo htmlspecialchars($detalle['nombre_aliado']); ?></li>
                    <li class="list-group-item"><strong>NIT:</strong> <?php echo htmlspecialchars($detalle['nit_aliado']); ?></li>
                    <li class="list-group-item"><strong>Gerente / Representante:</strong> <?php echo htmlspecialchars($detalle['gerente_aliado'] ?? 'N/A'); ?></li>
                    <li class="list-group-item"><strong>Dirección:</strong> <?php echo htmlspecialchars($detalle['direc_aliado'] ?? 'N/A'); ?></li>
                    <li class="list-group-item"><strong>Teléfono:</strong> <?php echo htmlspecialchars($detalle['tel_aliado'] ?? 'N/A'); ?></li>
                    <li class="list-group-item"><strong>Correo Electrónico:</strong> <?php echo htmlspecialchars($detalle['correo_aliado'] ?? 'N/A'); ?></li>
                </ul>
            </div>
        </div>
    </div>
    <?php
    } else {
        echo '<div class="alert alert-warning">No se encontraron detalles para esta alianza.</div>';
    }
} catch (PDOException $e) {
    error_log("Error en ajax_detalle_alianza.php: " . $e->getMessage());
    echo '<div class="alert alert-danger">Error al cargar los detalles.</div>';
}
?>