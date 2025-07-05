<?php
// =======================================================================================
// BLOQUE 1: CONFIGURACIÓN CENTRAL
// Incluye config.php para tener acceso a la conexión ($con).
// =======================================================================================
require_once __DIR__ . '/../../include/config.php';

// =======================================================================================
// BLOQUE 2: OBTENCIÓN Y VALIDACIÓN DE PARÁMETROS
// Se recupera el NIT de la IPS enviado por POST.
// =======================================================================================
$nit_ips = $_POST['nit_ips'] ?? '';

if (empty($nit_ips)) {
    echo '<option value="">Seleccione una IPS primero</option>';
    exit;
}

// =======================================================================================
// BLOQUE 3: LÓGICA DE CONSULTA
// Busca los médicos de Medicina General (especialidad 45) activos en la IPS seleccionada.
// =======================================================================================
try {
    $sql = "SELECT u.doc_usu, u.nom_usu 
            FROM usuarios u
            JOIN asignacion_medico am ON u.doc_usu = am.doc_medico
            WHERE am.nit_ips = :nit_ips 
              AND u.id_rol = 4 
              AND u.id_est = 1
              AND u.id_especialidad = 45
              AND am.id_estado = 1";
    $stmt = $con->prepare($sql);
    $stmt->execute([':nit_ips' => $nit_ips]);
    $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Se genera el HTML para las opciones del <select> de médicos.
    if (empty($medicos)) {
        echo '<option value="">No hay médicos de Medicina General activos en esta IPS</option>';
    } else {
        echo '<option value="">Seleccione un médico</option>';
        foreach ($medicos as $medico) {
            echo '<option value="' . htmlspecialchars($medico['doc_usu']) . '">' . htmlspecialchars($medico['nom_usu']) . '</option>';
        }
    }
} catch (PDOException $e) {
    error_log("Error en medico.php: " . $e->getMessage());
    echo '<option value="">Error al cargar médicos</option>';
}
?>