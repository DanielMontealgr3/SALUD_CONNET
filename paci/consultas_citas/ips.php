<?php
require_once '../../include/conexion.php';
require_once '../../include/validar_sesion.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['id_rol'] != 2) {
    echo "<option value=''>Error: Sesión no válida</option>";
    exit;
}

$doc_usuario = $_SESSION['doc_usu'];

$conex = new database();
$con = $conex->conectar();

try {
    $stmt = $con->prepare("SELECT i.Nit_IPS, i.nom_ips 
                           FROM usuarios u 
                           INNER JOIN barrio b ON u.id_barrio = b.id_barrio 
                           INNER JOIN afiliados a ON u.doc_usu = a.doc_afiliado 
                           INNER JOIN detalle_eps_ips dei ON a.id_eps = dei.nit_eps 
                           INNER JOIN ips i ON dei.nit_ips = i.Nit_IPS 
                           WHERE u.doc_usu = ? AND i.ubicacion_mun = b.id_mun");
    $stmt->execute([$doc_usuario]);
    $ips_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<option value=''>Seleccione una IPS</option>";
    if (empty($ips_list)) {
        echo "<option value='' disabled>No hay IPS disponibles para su municipio y EPS</option>";
    } else {
        foreach ($ips_list as $row) {
            echo "<option value='{$row['Nit_IPS']}'>{$row['nom_ips']}</option>";
        }
    }
} catch (PDOException $e) {
    echo "<option value='' disabled>Error: " . htmlspecialchars($e->getMessage()) . "</option>";
}
?>