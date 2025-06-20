<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';
require_once '../../include/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

$db = new database();
$con = $db->conectar();

$nit_farmacia_actual = $_SESSION['nit_farmacia_asignada_actual'] ?? null;
if (!$nit_farmacia_actual) {
    $data_para_excel = [['Error: No se ha podido identificar la farmacia actual.']];
    SimpleXLSXGen::fromArray($data_para_excel)->downloadAs('error_reporte.xlsx');
    exit;
}

$filtro_doc = trim($_GET['filtro_doc'] ?? '');
$filtro_id = trim($_GET['filtro_id'] ?? '');
$filtro_orden_fecha = trim($_GET['filtro_orden_fecha'] ?? 'desc');
$filtro_fecha_inicio = trim($_GET['filtro_fecha_inicio'] ?? '');
$filtro_fecha_fin = trim($_GET['filtro_fecha_fin'] ?? '');

$sql_base_from = "
    FROM entrega_medicamentos em
    JOIN usuarios far ON em.doc_farmaceuta = far.doc_usu
    JOIN estado est ON em.id_estado = est.id_est
    JOIN detalles_histo_clini dhc ON em.id_detalle_histo = dhc.id_detalle
    JOIN medicamentos med ON dhc.id_medicam = med.id_medicamento
    JOIN historia_clinica hc ON dhc.id_historia = hc.id_historia
    JOIN citas c ON hc.id_cita = c.id_cita
    JOIN usuarios pac ON c.doc_pac = pac.doc_usu
    JOIN turno_ent_medic tem ON dhc.id_historia = tem.id_historia AND tem.id_est = 9
    JOIN asignacion_farmaceuta af ON em.doc_farmaceuta = af.doc_farma
";

$sql_where_conditions = ["af.nit_farma = :nit_farma_actual"];
$params = [':nit_farma_actual' => $nit_farmacia_actual];

if (!empty($filtro_doc)) { $sql_where_conditions[] = "pac.doc_usu LIKE :doc_pac"; $params[':doc_pac'] = "%" . $filtro_doc . "%"; }
if (!empty($filtro_id)) { $sql_where_conditions[] = "em.id_entrega LIKE :id_entrega"; $params[':id_entrega'] = "%" . $filtro_id . "%"; }
if (!empty($filtro_fecha_inicio)) { $sql_where_conditions[] = "tem.fecha_entreg >= :fecha_inicio"; $params[':fecha_inicio'] = $filtro_fecha_inicio; }
if (!empty($filtro_fecha_fin)) { $sql_where_conditions[] = "tem.fecha_entreg <= :fecha_fin"; $params[':fecha_fin'] = $filtro_fecha_fin; }

$sql_where = " WHERE " . implode(" AND ", $sql_where_conditions);
$orden_sql = ($filtro_orden_fecha === 'asc') ? 'ASC' : 'DESC';

$sql_final = "
    SELECT 
        em.id_entrega, MAX(tem.fecha_entreg) AS fecha_entrega, pac.nom_usu AS nombre_paciente,
        pac.doc_usu AS doc_paciente, far.nom_usu AS nombre_farmaceuta, far.doc_usu AS doc_farmaceuta,
        med.nom_medicamento, em.cantidad_entregada, em.lote, est.nom_est
    " . $sql_base_from . $sql_where . "
    GROUP BY em.id_entrega
    ORDER BY fecha_entrega " . $orden_sql . ", em.id_entrega DESC
";

$stmt = $con->prepare($sql_final);
$stmt->execute($params);
$entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nombre_farmacia_asignada = $_SESSION['nombre_farmacia_actual'] ?? 'Farmacia';

$datos_para_excel = [];

$header_texts = [
    'ID Entrega', 'Fecha Entrega', 'Doc. Paciente', 'Nombre Paciente', 'Medicamento',
    'Cantidad', 'Lote', 'Doc. Farmaceuta', 'Nombre Farmaceuta', 'Estado'
];
$styled_headers = array_map(function($text) {
    return '<style bgcolor="#0D6EFD" color="#FFFFFF"><b>' . $text . '</b></style>';
}, $header_texts);
$datos_para_excel[] = $styled_headers;

if (empty($entregas)) {
    $datos_para_excel[] = ['No se encontraron registros con los filtros seleccionados.'];
} else {
    foreach ($entregas as $entrega) {
        $datos_para_excel[] = [
            $entrega['id_entrega'], $entrega['fecha_entrega'], $entrega['doc_paciente'],
            $entrega['nombre_paciente'], $entrega['nom_medicamento'], $entrega['cantidad_entregada'],
            $entrega['lote'], $entrega['doc_farmaceuta'], $entrega['nombre_farmaceuta'],
            $entrega['nom_est']
        ];
    }
}

$fileName = "reporte_entregas_" . preg_replace('/[^a-zA-Z0-9]/', '_', $nombre_farmacia_asignada) . "_" . date('Y-m-d') . ".xlsx";

$xlsx = SimpleXLSXGen::fromArray($datos_para_excel)
    ->setColWidth(3, 18)->setColWidth(4, 30)->setColWidth(5, 30)
    ->setColWidth(8, 18)->setColWidth(9, 30)
    ->downloadAs($fileName);

exit;