<?php
// exportar citas examenes medicamentos a excel

require_once '../include/validar_sesion.php';
require_once '../include/conexion.php';

// inicializacion de conexion y sesion
$conex = new Database();
$con = $conex->conectar();
$doc_usuario = $_SESSION['doc_usu'];

// filtros recibidos por get para filtrar resultados
$filtro_especialidad = $_GET['especialidad'] ?? 'todos';
$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_mes_inicio = $_GET['mes_inicio'] ?? '';
$filtro_anio_inicio = $_GET['anio_inicio'] ?? '';
$filtro_mes_fin = $_GET['mes_fin'] ?? '';
$filtro_anio_fin = $_GET['anio_fin'] ?? '';

// consultas para cada tipo de evento
$sql_citas = "SELECT hm.fecha_horario AS fecha_evento, hm.horario AS hora_evento, e.nom_est AS estado_nombre, c.id_est AS evento_id_est, 'Cita Medica' AS tipo_evento, esp.nom_espe AS detalle_evento FROM citas c JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med JOIN estado e ON c.id_est = e.id_est JOIN usuarios u ON c.doc_med = u.doc_usu JOIN especialidad esp ON u.id_especialidad = esp.id_espe WHERE c.doc_pac = :doc_usuario_citas";

$sql_medicamentos = "SELECT tem.fecha_entreg AS fecha_evento, hf.horario AS hora_evento, e.nom_est AS estado_nombre, tem.id_est AS evento_id_est, 'Entrega Medicamentos' AS tipo_evento, 'Farmacia' AS detalle_evento FROM turno_ent_medic tem JOIN horario_farm hf ON tem.hora_entreg = hf.id_horario_farm JOIN estado e ON tem.id_est = e.id_est JOIN historia_clinica hc ON tem.id_historia = hc.id_historia JOIN citas c ON hc.id_cita = c.id_cita WHERE c.doc_pac = :doc_usuario_medicamentos";

$sql_examenes = "SELECT tex.fech_exam AS fecha_evento, he.horario AS hora_evento, e.nom_est AS estado_nombre, tex.id_est AS evento_id_est, 'Examen Medico' AS tipo_evento, 'Laboratorio' AS detalle_evento FROM turno_examen tex JOIN horario_examen he ON tex.hora_exam = he.id_horario_exan JOIN estado e ON tex.id_est = e.id_est JOIN historia_clinica hc ON tex.id_historia = hc.id_historia JOIN citas c ON hc.id_cita = c.id_cita WHERE c.doc_pac = :doc_usuario_examenes";

// construccion de consulta final segun filtros
$sql_final = "";
$params = [];

if ($filtro_especialidad === 'todos') {
    $sql_final = "($sql_citas) UNION ALL ($sql_medicamentos) UNION ALL ($sql_examenes)";
    $params = [
        ':doc_usuario_citas' => $doc_usuario,
        ':doc_usuario_medicamentos' => $doc_usuario,
        ':doc_usuario_examenes' => $doc_usuario
    ];
} elseif ($filtro_especialidad === 'medicamentos') {
    $sql_final = $sql_medicamentos;
    $params = [':doc_usuario_medicamentos' => $doc_usuario];
} elseif ($filtro_especialidad === 'examenes') {
    $sql_final = $sql_examenes;
    $params = [':doc_usuario_examenes' => $doc_usuario];
} else {
    $sql_final = $sql_citas . " AND esp.id_espe = :id_espe";
    $params = [':doc_usuario_citas' => $doc_usuario, ':id_espe' => $filtro_especialidad];
}

// filtros por estado y rango de fechas
$sql_filtrada = "SELECT * FROM ($sql_final) AS eventos_unificados";
$where_clauses = [];

if ($filtro_estado !== 'todos') {
    $where_clauses[] = "evento_id_est = :id_est";
    $params[':id_est'] = $filtro_estado;
}

if (!empty($filtro_mes_inicio) && !empty($filtro_anio_inicio)) {
    $fecha_inicio = $filtro_anio_inicio . '-' . str_pad($filtro_mes_inicio, 2, '0', STR_PAD_LEFT) . '-01';
    $where_clauses[] = "fecha_evento >= :fecha_inicio";
    $params[':fecha_inicio'] = $fecha_inicio;
}

if (!empty($filtro_mes_fin) && !empty($filtro_anio_fin)) {
    $fecha_fin = (new DateTime($filtro_anio_fin . '-' . $filtro_mes_fin . '-01'))->format('Y-m-t');
    $where_clauses[] = "fecha_evento <= :fecha_fin";
    $params[':fecha_fin'] = $fecha_fin;
}

if (!empty($where_clauses)) {
    $sql_filtrada .= " WHERE " . implode(" AND ", $where_clauses);
}

// ordenamiento final por fecha y hora descendente
$orden_sql = "ORDER BY fecha_evento DESC, hora_evento DESC";
$sql_ejecutar = "$sql_filtrada $orden_sql";

// ejecucion de la consulta
$stmt = $con->prepare($sql_ejecutar);
$stmt->execute($params);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// encabezados para generar archivo excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=reporte_citas_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Citas</title>
</head>
<body>
    <table border="1">
        <thead>
            <tr>
                <th>Fecha Evento</th>
                <th>Hora</th>
                <th>Tipo</th>
                <th>Detalle</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($resultados)): ?>
                <tr>
                    <td colspan="5">No se encontraron registros con los filtros aplicados</td>
                </tr>
            <?php else: ?>
                <?php foreach ($resultados as $evento): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($evento['fecha_evento'])); ?></td>
                        <td><?php echo date('h:i A', strtotime($evento['hora_evento'])); ?></td>
                        <td><?php echo htmlspecialchars($evento['tipo_evento']); ?></td>
                        <td><?php echo htmlspecialchars($evento['detalle_evento']); ?></td>
                        <td><?php echo htmlspecialchars($evento['estado_nombre']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>