<?php
header('Content-Type: application/json');
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$response = ['error' => 'No se proporcionó un ID de entrega válido.'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_entrega = (int)$_GET['id'];
    $db = new database();
    $con = $db->conectar();

    $sql_entrega = "
        SELECT 
            em.id_detalle_histo, MAX(tem.fecha_entreg) AS fecha_entrega, em.cantidad_entregada, em.observaciones,
            far.nom_usu AS nombre_farmaceuta, pac.nom_usu AS nombre_paciente, pac.doc_usu AS doc_paciente,
            med.nom_medicamento
        FROM entrega_medicamentos em
        JOIN usuarios far ON em.doc_farmaceuta = far.doc_usu
        JOIN detalles_histo_clini dhc ON em.id_detalle_histo = dhc.id_detalle
        JOIN medicamentos med ON dhc.id_medicam = med.id_medicamento
        JOIN historia_clinica hc ON dhc.id_historia = hc.id_historia
        JOIN citas c ON hc.id_cita = c.id_cita
        JOIN usuarios pac ON c.doc_pac = pac.doc_usu
        JOIN turno_ent_medic tem ON dhc.id_historia = tem.id_historia AND tem.id_est = 9
        WHERE em.id_entrega = :id_entrega
        GROUP BY em.id_entrega
    ";
    $stmt = $con->prepare($sql_entrega);
    $stmt->execute([':id_entrega' => $id_entrega]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $response = [
            'entrega' => ['nombre_farmaceuta' => htmlspecialchars($data['nombre_farmaceuta']), 'fecha_entrega' => htmlspecialchars($data['fecha_entrega']), 'cantidad_entregada' => htmlspecialchars($data['cantidad_entregada']), 'observaciones' => htmlspecialchars($data['observaciones'])],
            'paciente' => ['nombre_paciente' => htmlspecialchars($data['nombre_paciente']), 'doc_paciente' => htmlspecialchars($data['doc_paciente'])],
            'medicamento' => ['nom_medicamento' => htmlspecialchars($data['nom_medicamento'])],
            'pendiente' => null
        ];

        $sql_pendiente = "SELECT ep.cantidad_pendiente, ep.fecha_generacion, m.nom_medicamento FROM entrega_pendiente ep JOIN detalles_histo_clini dhc ON ep.id_detalle_histo = dhc.id_detalle JOIN medicamentos m ON dhc.id_medicam = m.id_medicamento WHERE ep.id_detalle_histo = :id_detalle_histo";
        $stmt_p = $con->prepare($sql_pendiente);
        $stmt_p->execute([':id_detalle_histo' => $data['id_detalle_histo']]);
        $data_pendiente = $stmt_p->fetch(PDO::FETCH_ASSOC);

        if ($data_pendiente) {
            $response['pendiente'] = ['cantidad_pendiente' => htmlspecialchars($data_pendiente['cantidad_pendiente']), 'fecha_generacion' => htmlspecialchars($data_pendiente['fecha_generacion']), 'nom_medicamento' => htmlspecialchars($data_pendiente['nom_medicamento'])];
        }

    } else {
        $response = ['error' => 'No se encontró la entrega con el ID proporcionado.'];
    }
}

echo json_encode($response);
exit;