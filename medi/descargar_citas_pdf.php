<?php
require_once __DIR__ . '/../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/tcpdf/tcpdf.php';

if (!in_array($_SESSION['id_rol'], [1, 4])) {
    die('Acceso no autorizado para generar PDF.');
}

$doc_medico_actual = $_SESSION['doc_usu'];

$filtros_estado_disponibles = [
    'todas'          => ['label' => 'Todas (Historial)', 'ids' => [3, 5, 6, 7, 8]],
    'activas'        => ['label' => 'Activas/Asignadas', 'ids' => [3]],
    'realizadas'     => ['label' => 'Realizadas',        'ids' => [5]],
    'no_completadas' => ['label' => 'No Completadas',    'ids' => [6, 7, 8]],
];

$filtro_actual_key = isset($_GET['filtro_estado']) && array_key_exists($_GET['filtro_estado'], $filtros_estado_disponibles) ? $_GET['filtro_estado'] : 'todas';
$ids_estado_filtrar = $filtros_estado_disponibles[$filtro_actual_key]['ids'];
$report_filter_label = $filtros_estado_disponibles[$filtro_actual_key]['label'];

$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$fecha_desde = isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : '';

$base_sql_select_data = "SELECT c.id_cita, up.nom_usu AS nom_paciente, c.doc_pac, 
                                c.fecha_solici, hm.fecha_horario AS fecha_cita_programada, 
                                hm.horario AS hora_cita_programada, e.nom_est 
                         FROM citas c 
                         INNER JOIN estado e ON c.id_est = e.id_est 
                         LEFT JOIN usuarios up ON c.doc_pac = up.doc_usu
                         LEFT JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med";

$where_clauses = [];
$query_params = [];

$where_clauses[] = "hm.doc_medico = ?";
$query_params[] = $doc_medico_actual;

if (!empty($ids_estado_filtrar)) {
    $placeholders = implode(',', array_fill(0, count($ids_estado_filtrar), '?'));
    $where_clauses[] = "c.id_est IN ($placeholders)";
    $query_params = array_merge($query_params, $ids_estado_filtrar);
}
if ($busqueda !== '') {
    $likeBusqueda = "%$busqueda%";
    $where_clauses[] = "(c.id_cita LIKE ? OR c.doc_pac LIKE ? OR up.nom_usu LIKE ?)";
    array_push($query_params, $likeBusqueda, $likeBusqueda, $likeBusqueda);
}
if ($fecha_desde !== '' && $fecha_hasta !== '') {
    $where_clauses[] = "hm.fecha_horario BETWEEN ? AND ?";
    array_push($query_params, $fecha_desde, $fecha_hasta);
} elseif ($fecha_desde !== '') {
    $where_clauses[] = "hm.fecha_horario >= ?";
    $query_params[] = $fecha_desde;
} elseif ($fecha_hasta !== '') {
    $where_clauses[] = "hm.fecha_horario <= ?";
    $query_params[] = $fecha_hasta;
}

$final_where_sql = " WHERE " . implode(" AND ", $where_clauses);
$query_data_sql = $base_sql_select_data . $final_where_sql . " ORDER BY hm.fecha_horario DESC, hm.horario DESC";

$stmt_data = $con->prepare($query_data_sql);
$stmt_data->execute($query_params);
$citas = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

$mainBlueHex = '#005A9C';

class MYPDF extends TCPDF {
    public $reportFilterLabel = '';
    public $reportSearchTerm = '';
    public $logoPath = ROOT_PATH . '/img/logo.png';
    public $mainBlueColor = [0, 90, 156];

    public function Header() {
        if (file_exists($this->logoPath)) {
            $this->Image($this->logoPath, 15, 8, 30, 0, 'PNG', '', 'T', false, 300, '', false, false, 0);
        }
        $this->SetFont('helvetica', 'B', 18);
        $this->SetTextColorArray($this->mainBlueColor);
        $this->SetXY(50, 10);
        $this->Cell(0, 10, 'SALUDCONNECT', 0, 1, 'L');
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(0, 0, 0);
        $this->SetXY(50, 18);
        $this->Cell(0, 10, 'Reporte de Mis Citas: ' . $this->reportFilterLabel, 0, 1, 'L');
        if (!empty($this->reportSearchTerm)) {
            $this->SetFont('helvetica', 'I', 9);
            $this->SetXY(50, 25);
            $this->Cell(0, 8, 'Criterio de Búsqueda: ' . $this->reportSearchTerm, 0, 1, 'L');
        }
        $this->Ln(5);
        $this->Line(15, $this->GetY(), $this->getPageWidth() - 15, $this->GetY());
        $this->Ln(2);
    }
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        $this->Cell(0, 10, '© ' . date('Y') . ' SaludConnect. Todos los derechos reservados.', 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}

$pdf = new MYPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);

$pdf->SetCreator('SaludConnect');
$pdf->SetAuthor($_SESSION['nombre_usuario'] ?? 'Sistema');
$pdf->SetTitle('Reporte de Mis Citas - ' . $report_filter_label);
$pdf->SetSubject('Listado de Citas del Médico');
$pdf->SetKeywords('citas, reporte, medico, pdf');

$pdf->reportFilterLabel = $report_filter_label;
$pdf->reportSearchTerm = $busqueda;
$pdf->mainBlueColor = [0, 90, 156];

$pdf->setPrintHeader(true);
$pdf->setPrintFooter(true);
$pdf->SetMargins(15, 42, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(15);
$pdf->SetAutoPageBreak(TRUE, 25);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->setFontSubsetting(true);
$pdf->SetFont('helvetica', '', 9);
$pdf->AddPage();

$html = <<<EOD
<style>
    table { border-collapse: collapse; width: 100%; font-family: helvetica; font-size: 9pt;}
    th, td { border: 1px solid #cccccc; padding: 6px; text-align: center;}
    th { background-color: {$mainBlueHex}; color: #FFFFFF; font-weight: bold; }
    tr:nth-child(even) td { background-color: #f8f9fa; }
    td.paciente-nombre { text-align: left; } 
</style>
<table>
<thead>
<tr>
    <th width="8%">ID Cita</th>
    <th width="25%" style="text-align:left;">Paciente</th>
    <th width="15%">Doc. Pac.</th>
    <th width="13%">F. Solicitud</th>
    <th width="13%">F. Cita Prog.</th>
    <th width="11%">H. Cita Prog.</th>
    <th width="15%">Estado</th>
</tr>
</thead>
<tbody>
EOD;

if (count($citas) > 0) {
    foreach ($citas as $cita) {
        $fecha_cita_formateada = $cita['fecha_cita_programada'] ? date('d/m/Y', strtotime($cita['fecha_cita_programada'])) : 'N/D';
        $hora_cita_formateada = $cita['hora_cita_programada'] ? date('h:i A', strtotime($cita['hora_cita_programada'])) : 'N/D';
        
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($cita['id_cita']) . '</td>';
        $html .= '<td class="paciente-nombre">' . htmlspecialchars($cita['nom_paciente'] ?? 'N/A') . '</td>';
        $html .= '<td>' . htmlspecialchars($cita['doc_pac']) . '</td>';
        $html .= '<td>' . date('d/m/Y', strtotime($cita['fecha_solici'])) . '</td>';
        $html .= '<td>' . $fecha_cita_formateada . '</td>';
        $html .= '<td>' . $hora_cita_formateada . '</td>';
        $html .= '<td>' . htmlspecialchars($cita['nom_est']) . '</td>';
        $html .= '</tr>';
    }
} else {
    $html .= '<tr><td colspan="7" align="center">No se encontraron citas con los filtros aplicados.</td></tr>';
}
$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');

$filename = "reporte_mis_citas_" . date('Ymd_His') . ".pdf";
$pdf->Output($filename, 'I');
exit;
?>