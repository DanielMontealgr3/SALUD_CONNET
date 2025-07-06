<?php
// --- BLOQUE 1: CONFIGURACIÓN Y SEGURIDAD ---
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// --- BLOQUE 2: DEFINICIÓN DEL TIPO DE RESPUESTA Y RESPUESTA POR DEFECTO ---
header('Content-Type: application/json; charset=utf-8');
// Se usa la estructura de respuesta original que espera tu JS
$response = ['error' => 'No se proporcionó un ID de entrega válido.'];

// --- BLOQUE 3: VALIDACIÓN DE PARÁMETROS DE ENTRADA ---
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_entrega = (int)$_GET['id'];

    // --- BLOQUE 4: CONSULTA A LA BASE DE DATOS Y PROCESAMIENTO ---
    try {
        // Se usa la conexión global $con de config.php
        global $con;
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Tu consulta principal original para obtener los detalles de la entrega
        $sql_entrega = "
            SELECT 
                em.id_detalle_histo, 
                MAX(tem.fecha_entreg) AS fecha_entrega, 
                em.cantidad_entregada, 
                em.observaciones,
                em.lote,
                far.nom_usu AS nombre_farmaceuta, 
                pac.nom_usu AS nombre_paciente, 
                pac.doc_usu AS doc_paciente,
                med.nom_medicamento
            FROM entrega_medicamentos em
            JOIN usuarios far ON em.doc_farmaceuta = far.doc_usu
            JOIN detalles_histo_clini dhc ON em.id_detalle_histo = dhc.id_detalle
            JOIN medicamentos med ON dhc.id_medicam = med.id_medicamento
            JOIN historia_clinica hc ON dhc.id_historia = hc.id_historia
            JOIN citas c ON hc.id_cita = c.id_cita
            JOIN usuarios pac ON c.doc_pac = pac.doc_usu
            JOIN turno_ent_medic tem ON hc.id_historia = tem.id_historia AND tem.id_est = 9
            WHERE em.id_entrega = :id_entrega
            GROUP BY em.id_entrega
        ";
        $stmt = $con->prepare($sql_entrega);
        $stmt->execute([':id_entrega' => $id_entrega]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // Se arma la respuesta con la ESTRUCTURA EXACTA que espera tu JS original
            $response = [
                'entrega' => [
                    'nombre_farmaceuta' => htmlspecialchars($data['nombre_farmaceuta']),
                    'fecha_entrega' => htmlspecialchars(date('d/m/Y H:i', strtotime($data['fecha_entrega']))),
                    'cantidad_entregada' => htmlspecialchars($data['cantidad_entregada']),
                    'observaciones' => htmlspecialchars($data['observaciones']),
                    'lote' => htmlspecialchars($data['lote'])
                ],
                'paciente' => [
                    'nombre_paciente' => htmlspecialchars($data['nombre_paciente']),
                    'doc_paciente' => htmlspecialchars($data['doc_paciente'])
                ],
                'medicamento' => [
                    'nom_medicamento' => htmlspecialchars($data['nom_medicamento'])
                ],
                'pendiente' => null // Se inicializa para añadirlo si existe
            ];

            // Tu consulta secundaria original para buscar el pendiente asociado
            $sql_pendiente = "SELECT ep.cantidad_pendiente, ep.fecha_generacion FROM entrega_pendiente ep WHERE ep.id_detalle_histo = :id_detalle_histo";
            $stmt_p = $con->prepare($sql_pendiente);
            $stmt_p->execute([':id_detalle_histo' => $data['id_detalle_histo']]);
            $data_pendiente = $stmt_p->fetch(PDO::FETCH_ASSOC);
            
            if ($data_pendiente) {
                $response['pendiente'] = [
                    'cantidad_pendiente' => htmlspecialchars($data_pendiente['cantidad_pendiente']),
                    'fecha_generacion' => htmlspecialchars(date('d/m/Y', strtotime($data_pendiente['fecha_generacion'])))
                ];
            }
        } else {
            $response = ['error' => 'No se encontró la entrega con el ID proporcionado.'];
        }

    } catch (PDOException $e) {
        error_log("Error en ajax_detalles_entrega.php: " . $e->getMessage());
        $response = ['error' => 'Ocurrió un error al consultar la base de datos.'];
    }
}

// --- BLOQUE 5: ENVÍO DE LA RESPUESTA FINAL ---
echo json_encode($response);
exit;