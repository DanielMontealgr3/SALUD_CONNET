<?php
require_once '../include/validar_sesion.php';
require_once('../include/conexion.php');
// Adjust the path to your TCPDF library's tcpdf.php file
// If you installed via Composer, it's usually like this:
require_once '../INCLUDE/tcpdf/tcpdf.php'; 
// If you downloaded manually, it might be: require_once('path/to/tcpdf/tcpdf.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 4) {
    die('Acceso no autorizado para generar PDF.');
}

// --- STATUS IDs ---
define('ID_ESTADO_ASIGNADA', 3);
define('ID_ESTADO_REALIZADA', 5);
define('ID_ESTADO_NO_REALIZADA', 6);
define('ID_ESTADO_CANCELADA', 7);
define('ID_ESTADO_NO_ASISTIO', 8);

$db = new Database();
$pdo = $db->conectar();

// --- FILTER CONFIGURATION ---
$filtros_estado_disponibles = [
    'activas'       => ['label' => 'Activas',       'ids' => [ID_ESTADO_ASIGNADA]],
    'realizadas'    => ['label' => 'Realizadas',    'ids' => [ID_ESTADO_REALIZADA]],
    'no_realizadas' => ['label' => 'No Realizadas', 'ids' => [ID_ESTADO_NO_REALIZADA, ID_ESTADO_NO_ASISTIO, ID_ESTADO_CANCELADA]],
    'todas'         => ['label' => 'Todas',         'ids' => [ID_ESTADO_ASIGNADA, ID_ESTADO_REALIZADA, ID_ESTADO_NO_REALIZADA, ID_ESTADO_NO_ASISTIO, ID_ESTADO_CANCELADA]],
];

$filtro_actual_key = isset($_GET['filtro_estado']) && array_key_exists($_GET['filtro_estado'], $filtros_estado_disponibles)
    ? $_GET['filtro_estado']
    : 'todas';
$ids_estado_filtrar = $filtros_estado_disponibles[$filtro_actual_key]['ids'];
$report_filter_label = $filtros_estado_disponibles[$filtro_actual_key]['label'];

$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$fecha_desde = isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : '';

// --- Database Query (Corrected for horario_medico) ---
$base_sql_select_data = "SELECT c.id_cita, up.nom_usu AS nom_paciente, c.doc_pac, 
                                c.fecha_solici, 
                                hm.fecha_horario AS fecha_cita_programada, 
                                hm.horario AS hora_cita_programada, 
                                e.nom_est 
                         FROM citas c 
                         INNER JOIN estado e ON c.id_est = e.id_est 
                         LEFT JOIN usuarios up ON c.doc_pac = up.doc_usu
                         LEFT JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med"; // Ensure JOIN keys are correct

$where_clauses = []; 
$query_params = []; 

if (!empty($ids_estado_filtrar)) {
    if (!(isset($filtros_estado_disponibles[$filtro_actual_key]['ids']) && empty($filtros_estado_disponibles[$filtro_actual_key]['ids']) && $filtro_actual_key === 'todas')) {
        $status_placeholders = [];
        foreach ($ids_estado_filtrar as $index => $id_est_val) {
            $ph = ":id_est_filter_" . $index; $status_placeholders[] = $ph; $query_params[$ph] = $id_est_val;
        }
        if (!empty($status_placeholders)) { $where_clauses[] = "c.id_est IN (" . implode(", ", $status_placeholders) . ")";}
    }
}
if ($busqueda != '') { 
    $likeBusqueda = "%$busqueda%";
    $search_conditions_sql = "(c.id_cita LIKE :s1 OR c.doc_pac LIKE :s2 OR c.fecha_solici LIKE :s3 OR hm.fecha_horario LIKE :s4 OR hm.horario LIKE :s5 OR e.nom_est LIKE :s6 OR up.nom_usu LIKE :s7)";
    $where_clauses[] = $search_conditions_sql;
    $query_params[':s1']=$likeBusqueda; $query_params[':s2']=$likeBusqueda; $query_params[':s3']=$likeBusqueda; 
    $query_params[':s4']=$likeBusqueda; $query_params[':s5']=$likeBusqueda; $query_params[':s6']=$likeBusqueda; $query_params[':s7']=$likeBusqueda;
}
if ($fecha_desde !== '' && $fecha_hasta !== '') {
    $where_clauses[] = "(hm.fecha_horario BETWEEN :fecha_desde AND :fecha_hasta)";
    $query_params[':fecha_desde'] = $fecha_desde; $query_params[':fecha_hasta'] = $fecha_hasta;
} elseif ($fecha_desde !== '') {
    $where_clauses[] = "(hm.fecha_horario >= :fecha_desde)"; $query_params[':fecha_desde'] = $fecha_desde;
} elseif ($fecha_hasta !== '') {
    $where_clauses[] = "(hm.fecha_horario <= :fecha_hasta)"; $query_params[':fecha_hasta'] = $fecha_hasta;
}

$final_where_sql = ""; 
if (!empty($where_clauses)) { $final_where_sql = " WHERE " . implode(" AND ", $where_clauses); }

$query_data_sql = $base_sql_select_data . $final_where_sql . " ORDER BY hm.fecha_horario DESC, hm.horario DESC";
$stmt_data = $pdo->prepare($query_data_sql); 
if(!$stmt_data->execute($query_params)){
    error_log("Error executing PDF data query: " . json_encode($stmt_data->errorInfo()));
    die("Error al generar los datos para el PDF.");
};
$citas = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

// --- Define colors ---
$mainBlue = [13, 110, 253]; $mainBlueHex = '#0D6EFD';

// --- Custom PDF Class for Header and Footer ---
class MYPDF extends TCPDF {
    public $reportFilterLabel = ''; public $reportSearchTerm = '';
    public $logoPath = '../img/loguito.png';      // Main logo for header
    public $minsaludLogoPath = '../img/Minsalud.png'; // CORRECTED: Path to Minsalud PNG
    public $senaLogoPath = '../img/sena.png';       // Path to SENA logo
    public $mainBlueColor = [13, 110, 253];

    public function Header() { 
        if (file_exists($this->logoPath)) { $this->Image($this->logoPath, 15, 8, 30, 0, 'PNG', '', 'T', false, 300, '', false, false, 0); }
        $this->SetFont('helvetica', 'B', 18); $this->SetTextColorArray($this->mainBlueColor); 
        $this->SetXY(50, 10); $this->Cell(0, 10, 'SALUDCONNECT', 0, 1, 'L'); 
        $this->SetFont('helvetica', 'B', 14); $this->SetTextColor(0,0,0); 
        $this->SetXY(50, 18); $this->Cell(0, 10, 'Reporte de Citas: ' . $this->reportFilterLabel, 0, 1, 'L');
        if (!empty($this->reportSearchTerm)) { $this->SetFont('helvetica', 'I', 9); $this->SetXY(50, 25); $this->Cell(0, 8, 'Criterio de Búsqueda: ' . $this->reportSearchTerm, 0, 1, 'L'); }
        $this->Ln(5); $this->Line(15, $this->GetY(), $this->getPageWidth() - 15, $this->GetY()); $this->Ln(2);
    }
    public function Footer() { 
        $this->SetY(-35); $this->SetFont('helvetica', '', 8); $this->SetTextColor(80, 80, 80);
        $this->Line(15, $this->GetY(), $this->getPageWidth() - 15, $this->GetY()); $this->Ln(2);
        $current_y = $this->GetY(); $page_width = $this->getPageWidth(); $margin_left = 15; $margin_right = 15;
        $drawable_width = $page_width - $margin_left - $margin_right;
        $this->SetFont('helvetica', 'B', 7);
        $this->MultiCell($drawable_width * 0.6, 5, "Contáctenos", 0, 'L', false, 1, $margin_left, $current_y, true, 0, false, true, 0, 'T', false);
        $current_y += 4; $this->SetFont('helvetica', '', 7);
        $contact_text = "Línea nacional de información general 018000919100 (tel. fijo)\nLínea nacional de citas 018000940304 (tel. fijo)\nMarcación desde celular #936 (Tigo, Claro, Movistar)\nSocial: (F) (IG) (YT)";
        $this->MultiCell($drawable_width * 0.6, 15, $contact_text, 0, 'L', false, 1, $margin_left, $current_y, true, 0, false, true, 0, 'T', false);
        
        // Make sure these Y positions are adequate after MultiCell
        $logo_y_start = $current_y - 12; // Adjust this Y if logos overlap text or go off page
        if ($this->GetY() > $logo_y_start) $logo_y_start = $this->GetY(); // Prevent overlap

        $logo_x_start = $margin_left + ($drawable_width * 0.62); 
        if (file_exists($this->minsaludLogoPath)) { 
             $this->Image($this->minsaludLogoPath, $logo_x_start, $logo_y_start, 20, 0, 'PNG', '', 'T', false, 300, '', false, false, 0); // Specify PNG
        }
        if (file_exists($this->senaLogoPath)) { 
            $this->Image($this->senaLogoPath, $logo_x_start + 22, $logo_y_start, 15, 0, 'PNG', '', 'T', false, 300, '', false, false, 0); 
        }
        
        $this->SetY(-15); $this->SetFont('helvetica', 'I', 8); $this->SetTextColor(128,128,128);
        $this->Cell($drawable_width / 2, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'L', 0, '', 0, false, 'T', 'M');
        $currentYear = date("Y");
        $this->Cell($drawable_width / 2, 10, "© {$currentYear} SaludConnect. Todos los derechos reservados.", 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}

$pdf = new MYPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
$pdf->setPrintHeader(true); $pdf->setPrintFooter(true); 

$pdf->SetCreator('SaludConnect'); $pdf->SetAuthor($_SESSION['nombre_usuario'] ?? 'Sistema');
$pdf->SetTitle('Reporte de Citas - ' . $report_filter_label);
$pdf->SetSubject('Listado de Citas Programadas'); $pdf->SetKeywords('citas, reporte, pdf');

$pdf->reportFilterLabel = $report_filter_label; $pdf->reportSearchTerm = $busqueda;
$pdf->logoPath = '../img/logo.png'; 
$pdf->minsaludLogoPath = '../img/Minsalud.png'; // CORRECTED to .png
$pdf->senaLogoPath = '../img/sena.png';       
$pdf->mainBlueColor = $mainBlue;

$pdf->SetMargins(15, 42, 15); $pdf->SetHeaderMargin(5); $pdf->SetFooterMargin(PDF_MARGIN_FOOTER); 
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM + 20); 
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
if (@file_exists(dirname(__FILE__).'/../../vendor/tecnickcom/tcpdf/examples/lang/spa.php')) {
    require_once(dirname(__FILE__).'/../../vendor/tecnickcom/tcpdf/examples/lang/spa.php');
    $pdf->setLanguageArray($l);
}
$pdf->setFontSubsetting(true); $pdf->SetFont('helvetica', '', 9); $pdf->AddPage();

// --- Build HTML Table ---
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
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($cita['id_cita']) . '</td>';
        $html .= '<td class="paciente-nombre">' . htmlspecialchars($cita['nom_paciente'] ?? 'N/A') . '</td>';
        $html .= '<td>' . htmlspecialchars($cita['doc_pac']) . '</td>';
        $html .= '<td>' . htmlspecialchars($cita['fecha_solici']) . '</td>';
        $html .= '<td>' . htmlspecialchars($cita['fecha_cita_programada'] ?? 'N/D') . '</td>';
        $html .= '<td>' . htmlspecialchars($cita['hora_cita_programada'] ?? 'N/D') . '</td>';
        $html .= '<td>' . htmlspecialchars($cita['nom_est']) . '</td>';
        $html .= '</tr>';
    }
} else { $html .= '<tr><td colspan="7" align="center">No se encontraron citas.</td></tr>'; }
$html .= '</tbody></table>';

$pdf->writeHTML($html, true, false, true, false, '');

$filename = "reporte_citas_" . strtolower(str_replace([' ', '/'], '_', $report_filter_label)) . "_" . date('Ymd_His') . ".pdf";
$pdf->Output($filename, 'I');
exit;
?>