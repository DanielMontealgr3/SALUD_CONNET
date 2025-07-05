<?php
// =======================================================================================
// BLOQUE 1: CONFIGURACIÓN CENTRAL Y SEGURIDAD
// Incluye config.php para la conexión ($con) y el inicio de sesión.
// Incluye validar_sesion.php para proteger el endpoint usando la ruta absoluta con ROOT_PATH.
// =======================================================================================
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// =======================================================================================
// BLOQUE 2: VERIFICACIÓN DE ACCESO
// Se asegura de que solo un paciente (rol 2) pueda acceder a este script.
// =======================================================================================
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['id_rol'] != 2) {
    echo "<option value=''>Error: Sesión no válida</option>";
    exit;
}

$doc_usuario = $_SESSION['doc_usu'];

// =======================================================================================
// BLOQUE 3: LÓGICA DE CONSULTA
// Busca las IPS asociadas al paciente según su EPS y municipio.
// =======================================================================================
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

    // Se genera el HTML para las opciones del <select>.
    echo "<option value=''>Seleccione una IPS</option>";
    if (empty($ips_list)) {
        echo "<option value='' disabled>No hay IPS disponibles para su municipio y EPS</option>";
    } else {
        foreach ($ips_list as $row) {
            echo "<option value='" . htmlspecialchars($row['Nit_IPS']) . "'>" . htmlspecialchars($row['nom_ips']) . "</option>";
        }
    }
} catch (PDOException $e) {
    error_log("Error en ips.php: " . $e->getMessage());
    echo "<option value='' disabled>Error al cargar las IPS</option>";
}
?>