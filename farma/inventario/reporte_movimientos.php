<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';
require_once '../../include/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

$db = new database();
$con = $db->conectar();

$nit_farmacia_actual = $_SESSION['nit_farmacia_asignada_actual'] ?? null;
if (!$nit_farmacia_actual) {
    SimpleXLSXGen::fromArray([['Error: No se ha identificado la farmacia.']])->downloadAs('error_reporte.xlsx');
    exit;
}

$filtro_doc_resp = trim($_GET['filtro_doc_resp'] ?? '');
$filtro_medicamento = trim($_GET['filtro_medicamento'] ?? '');
$filtro_tipo_mov = trim($_GET['filtro_tipo_mov'] ?? 'todos');
$filtro_fecha_inicio = trim($_GET['filtro_fecha_inicio'] ?? '');
$filtro_fecha_fin = trim($_GET['filtro_fecha_fin'] ?? '');
$filtro_lote = trim($_GET['filtro_lote'] ?? '');
$filtro_vencimiento = trim($_GET['filtro_vencimiento'] ?? '');

$sql_base_from = "
    FROM movimientos_inventario mi
    JOIN medicamentos med ON mi.id_medicamento = med.id_medicamento
    JOIN tipo_movimiento tm ON mi.id_tipo_mov = tm.id_tipo_mov
    LEFT JOIN usuarios u ON mi.id_usuario_responsable = u.doc_usu
";
$sql_where_conditions = ["mi.nit_farm = :nit_farma_actual"];
$params = [':nit_farma_actual' => $nit_farmacia_actual];

if (!empty($filtro_doc_resp)) { $sql_where_conditions[] = "u.doc_usu LIKE :doc_resp"; $params[':doc_resp'] = "%" . $filtro_doc_resp . "%"; }
if (!empty($filtro_medicamento)) { $sql_where_conditions[] = "med.nom_medicamento LIKE :medicamento"; $params[':medicamento'] = "%" . $filtro_medicamento . "%"; }
if ($filtro_tipo_mov !== 'todos') { $sql_where_conditions[] = "mi.id_tipo_mov = :tipo_mov"; $params[':tipo_mov'] = $filtro_tipo_mov; }
if (!empty($filtro_fecha_inicio)) { $sql_where_conditions[] = "DATE(mi.fecha_movimiento) >= :fecha_inicio"; $params[':fecha_inicio'] = $filtro_fecha_inicio; }
if (!empty($filtro_fecha_fin)) { $sql_where_conditions[] = "DATE(mi.fecha_movimiento) <= :fecha_fin"; $params[':fecha_fin'] = $filtro_fecha_fin; }
if (!empty($filtro_lote)) { $sql_where_conditions[] = "mi.lote LIKE :lote"; $params[':lote'] = "%" . $filtro_lote . "%"; }
if (!empty($filtro_vencimiento)) { $sql_where_conditions[] = "mi.fecha_vencimiento = :vencimiento"; $params[':vencimiento'] = $filtro_vencimiento; }

$sql_where = " WHERE " . implode(" AND ", $sql_where_conditions);
$sql_final = "
    SELECT 
        mi.id_movimiento, med.nom_medicamento, tm.nom_mov, mi.cantidad, mi.lote, 
        mi.fecha_vencimiento, u.nom_usu, u.doc_usu, mi.fecha_movimiento, mi.notas
    " . $sql_base_from . $sql_where . "
    ORDER BY mi.fecha_movimiento DESC, mi.id_movimiento DESC
";

$stmt = $con->prepare($sql_final);
$stmt->execute($params);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$nombre_farmacia_asignada = $_SESSION['nombre_farmacia_actual'] ?? 'Farmacia';
$datos_para_excel = [];

$header_texts = [
    'ID Movimiento', 'Fecha Movimiento', 'Medicamento', 'Tipo Movimiento', 'Cantidad',
    'Lote', 'Fecha Vencimiento', 'Doc. Responsable', 'Nombre Responsable', 'Notas'
];
$styled_headers = array_map(fn($text) => '<style bgcolor="#0D6EFD" color="#FFFFFF"><b>' . $text . '</b></style>', $header_texts);
$datos_para_excel[] = $styled_headers;

if (empty($movimientos)) {
    $datos_para_excel[] = ['No se encontraron movimientos con los filtros seleccionados.'];
} else {
    foreach ($movimientos as $mov) {
        $datos_para_excel[] = [
            $mov['id_movimiento'],
            date('d/m/Y H:i:s', strtotime($mov['fecha_movimiento'])),
            $mov['nom_medicamento'],
            $mov['nom_mov'],
            $mov['cantidad'],
            $mov['lote'] ?? 'N/A',
            $mov['fecha_vencimiento'] ? date('d/m/Y', strtotime($mov['fecha_vencimiento'])) : 'N/A',
            $mov['doc_usu'] ?? 'N/A',
            $mov['nom_usu'] ?? 'Sistema',
            $mov['notas'] ?? ''
        ];
    }
}

$fileName = "reporte_movimientos_" . preg_replace('/[^a-zA-Z0-9]/', '_', $nombre_farmacia_asignada) . "_" . date('Y-m-d') . ".xlsx";

SimpleXLSXGen::fromArray($datos_para_excel)
    ->setColWidth(2, 35)->setColWidth(3, 20)->setColWidth(8, 30)->setColWidth(9, 40)
    ->downloadAs($fileName);

exit;