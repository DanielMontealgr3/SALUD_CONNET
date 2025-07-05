<?php
require_once '../../include/conexion.php';
require_once '../../include/validar_sesion.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['id_rol'] != 2) {
    echo "<option value=''>Error: Sesión no válida</option>";
    exit;
}

$conex = new database();
$con = $conex->conectar();

try {
    $stmtFarmacias = $con->prepare("SELECT nit_farm, nom_farm, direc_farm FROM farmacias ORDER BY nom_farm ASC");
    $stmtFarmacias->execute();
    $farmacias = $stmtFarmacias->fetchAll(PDO::FETCH_ASSOC);
    error_log("Available pharmacies from farmacias: " . json_encode($farmacias));

    if (empty($farmacias)) {
        echo "<option value=''>No hay farmacias registradas</option>";
    } else {
        foreach ($farmacias as $row) {
            $nombre_direccion = $row['nom_farm'] . " (" . $row['direc_farm'] . ")";
            echo "<option value='" . $row['nit_farm'] . "'>$nombre_direccion</option>";
        }
    }
} catch (PDOException $e) {
    echo "<option value=''>Error: " . htmlspecialchars($e->getMessage()) . "</option>";
}
?>