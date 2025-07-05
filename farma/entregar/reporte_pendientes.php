<?php
ob_start();

require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';
require_once '../../include/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

try {
    $db = new database();
    $con = $db->conectar();
} catch (PDOException $e) {
    ob_end_clean();
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

$nit_farmacia_actual = $_SESSION['nit_farmacia_asignada_actual'] ?? null;
if (!$nit_farmacia_actual) {
    ob_end_clean();
    die("Error de sesión: No se ha podido identificar la farmacia actual.");
}

$filtro_radicado = trim($_GET['q_radicado'] ?? '');
$filtro_documento = trim($_GET['q_documento'] ?? '');
$filtro_orden = trim($_GET['orden'] ?? 'desc');
$filtro_estado = trim($_GET['estado'] ?? '10');
$filtro_fecha_inicio = trim($_GET['fecha_inicio'] ?? '');
$filtro_fecha_fin = trim($_GET['fecha_fin'] ?? '');

$params = [':nit_farma' => $nit_farmacia_actual];
$sql_where_conditions = [];

if ($filtro_estado !== 'todos') {
    $sql_where_conditions[] = "ep.id_estado = :id_estado";
    $params[':id_estado'] = (int)$filtro_estado;
}
if (!empty($filtro_radicado)) {
    $sql_where_conditions[] = "ep.radicado_pendiente LIKE :radicado";
    $params[':radicado'] = "%" . $filtro_radicado . "%";
}
if (!empty($filtro_documento)) {
    $sql_where_conditions[] = "u.doc_usu LIKE :documento";
    $params[':documento'] = "%" . $filtro_documento . "%";
}
if (!empty($filtro_fecha_inicio)) {
    $sql_where_conditions[] = "ep.fecha_generacion >= :fecha_inicio";
    $params[':fecha_inicio'] = $filtro_fecha_inicio;
}
if (!empty($filtro_fecha_fin)) {
    $sql_where_conditions[] = "ep.fecha_generacion <= :fecha_fin_ajustada";
    $params[':fecha_fin_ajustada'] = $filtro_fecha_fin . ' 23:59:59';
}

$sql_from_join = "
    FROM entrega_pendiente ep
    JOIN detalles_histo_clini dh ON ep.id_detalle_histo = dh.id_detalle
    JOIN historia_clinica hc ON dh.id_historia = hc.id_historia
    JOIN citas c ON hc.id_cita = c.id_cita
    JOIN usuarios u ON c.doc_pac = u.doc_usu
    JOIN medicamentos m ON dh.id_medicam = m.id_medicamento
    JOIN usuarios fg ON ep.id_farmaceuta_genera = fg.doc_usu
    JOIN asignacion_farmaceuta af ON fg.doc_usu = af.doc_farma
    JOIN estado est ON ep.id_estado = est.id_est
";

$sql_base_where = " WHERE af.nit_farma = :nit_farma ";
$sql_final_where = $sql_base_where . (!empty($sql_where_conditions) ? " AND " . implode(" AND ", $sql_where_conditions) : "");

$order_by = ($filtro_orden === 'asc') ? "ep.fecha_generacion ASC" : "ep.fecha_generacion DESC";

$sql_final = "
    SELECT ep.radicado_pendiente, u.nom_usu, u.doc_usu, m.nom_medicamento, 
           ep.cantidad_pendiente, ep.fecha_generacion, fg.nom_usu as farmaceuta_genera, 
           fg.doc_usu as doc_farmaceuta_genera, est.nom_est
    " . $sql_from_join . $sql_final_where . "
    GROUP BY ep.id_entrega_pendiente
    ORDER BY " . $order_by;

try {
    $stmt = $con->prepare($sql_final);
    $stmt->execute($params);
    $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    ob_end_clean();
    die("Error al ejecutar la consulta: " . $e->getMessage());
}

$nombre_farmacia_asignada = $_SESSION['nombre_farmacia_actual'] ?? 'Farmacia';
$datos_para_excel = [];

$header_texts = [
    'Radicado', 'Doc. Paciente', 'Nombre Paciente', 'Medicamento', 'Cant. Pendiente', 
    'Fecha Generación', 'Doc. Farmaceuta', 'Generado Por', 'Estado'
];
$styled_headers = array_map(function($text) {
    return '<style bgcolor="#0D6EFD" color="#FFFFFF"><b>' . $text . '</b></style>';
}, $header_texts);
$datos_para_excel[] = $styled_headers;

if (empty($pendientes)) {
    $datos_para_excel[] = ['No se encontraron pendientes con los filtros aplicados.'];
} else {
    foreach ($pendientes as $p) {
        $datos_para_excel[] = [
            $p['radicado_pendiente'],
            $p['doc_usu'],
            $p['nom_usu'],
            $p['nom_medicamento'],
            $p['cantidad_pendiente'],
            date('Y-m-d H:i:s', strtotime($p['fecha_generacion'])),
            $p['doc_farmaceuta_genera'],
            $p['farmaceuta_genera'],
            $p['nom_est']
        ];
    }
}

$fileName = "reporte_pendientes_" . preg_replace('/[^a-zA-Z0-9]/', '_', $nombre_farmacia_asignada) . "_" . date('Y-m-d') . ".xlsx";

ob_end_clean();

SimpleXLSXGen::fromArray($datos_para_excel)
    ->setColWidth(1, 20)->setColWidth(2, 18)->setColWidth(3, 30)
    ->setColWidth(4, 30)->setColWidth(5, 15)->setColWidth(6, 20)
    ->setColWidth(7, 18)->setColWidth(8, 30)->setColWidth(9, 15)
    ->downloadAs($fileName);

exit;
