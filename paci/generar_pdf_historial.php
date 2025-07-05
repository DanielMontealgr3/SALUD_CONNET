<?php
require_once '../include/validar_sesion.php';
require_once '../include/conexion.php';
require_once '../include/tcpdf/tcpdf.php';

if (!isset($_GET['id_historia']) || !is_numeric($_GET['id_historia'])) {
    die("ID de historial no proporcionado o inválido.");
}

$id_historia = $_GET['id_historia'];
$doc_usuario = $_SESSION['doc_usu'];

$conex = new Database();
$con = $conex->conectar();

// Consulta principal para obtener los datos del historial
$sql = "
    SELECT 
        pac.doc_usu AS doc_paciente, pac.nom_usu AS nom_paciente, pac.fecha_nac, pac.tel_usu, pac.correo_usu,
        med.nom_usu AS nom_medico,
        esp.nom_espe,
        hc.motivo_de_cons, hc.saturacion, hc.presion, hc.peso, hc.estatura,
        det.id_detalle, det.id_diagnostico, diag.diagnostico, det.prescripcion,
        c.id_cita,
        ips.nit_ips, ips.nom_IPS, af.id_eps, eps.nombre_eps
    FROM historia_clinica hc
    JOIN citas c ON hc.id_cita = c.id_cita
    JOIN usuarios pac ON c.doc_pac = pac.doc_usu
    JOIN usuarios med ON c.doc_med = med.doc_usu
    JOIN especialidad esp ON med.id_especialidad = esp.id_espe
    JOIN ips ON c.nit_IPS = ips.nit_ips
    JOIN afiliados af ON pac.doc_usu = af.doc_afiliado
    JOIN eps ON af.id_eps = eps.nit_eps
    JOIN detalles_histo_clini det ON hc.id_historia = det.id_historia
    LEFT JOIN diagnostico diag ON det.id_diagnostico = diag.id_diagnos
    WHERE hc.id_historia = :id_historia AND pac.doc_usu = :doc_usuario
    LIMIT 1
";

$stmt = $con->prepare($sql);
$stmt->execute([':id_historia' => $id_historia, ':doc_usuario' => $doc_usuario]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$datos) {
    die("Historial no encontrado o no le pertenece.");
}

// Obtener todos los medicamentos y procedimientos
$stmt_meds = $con->prepare("SELECT m.nom_medicamento, d.can_medica FROM detalles_histo_clini d JOIN medicamentos m ON d.id_medicam = m.id_medicamento WHERE d.id_historia = ? AND d.id_medicam IS NOT NULL");
$stmt_meds->execute([$id_historia]);
$medicamentos = $stmt_meds->fetchAll(PDO::FETCH_ASSOC);

$stmt_procs = $con->prepare("SELECT p.procedimiento, d.cant_proced FROM detalles_histo_clini d JOIN procedimientos p ON d.id_proced = p.id_proced WHERE d.id_historia = ? AND d.id_proced IS NOT NULL");
$stmt_procs->execute([$id_historia]);
$procedimientos = $stmt_procs->fetchAll(PDO::FETCH_ASSOC);


// === CLASE PDF CON HEADER CORREGIDO ===
class MYPDF extends TCPDF {
    public $id_detalle;
    public $eps_data;

    public function Header() {
        // 1. Logo a la izquierda
        $image_file = __DIR__ . '/../img/logo.png';
        if (file_exists($image_file)) {
            $this->Image($image_file, 15, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0);
        }
        
        // 2. Título principal centrado
        $this->SetY(15);
        $this->SetFont('helvetica', 'B', 20);
        $this->Cell(0, 10, 'Historia Clínica #' . $this->id_detalle, 0, true, 'C');
        
        // === 3. CORRECCIÓN: Usar MultiCell para los datos de la derecha ===
        $this->SetFont('helvetica', 'B', 8);
        
        // Texto para EPS
        $eps_text = 'EPS: ' . htmlspecialchars($this->eps_data['nombre_eps']) . ' (NIT: ' . htmlspecialchars($this->eps_data['id_eps']) . ')';
        // Texto para IPS
        $ips_text = 'IPS: ' . htmlspecialchars($this->eps_data['nom_IPS']) . ' (NIT: ' . htmlspecialchars($this->eps_data['nit_ips']) . ')';

        // Definimos la posición y el tamaño del bloque de texto
        $x_pos = 145; // Posición X (desde la izquierda)
        $y_pos = 10;  // Posición Y (desde arriba)
        $width = 50;  // Ancho del bloque de texto

        // Imprimir el bloque de la EPS
        $this->SetXY($x_pos, $y_pos);
        $this->MultiCell($width, 5, $eps_text, 0, 'R', 0, 1, '', '', true);

        // Imprimir el bloque de la IPS justo debajo
        $this->SetX($x_pos); // Mantenemos la misma posición X
        $this->MultiCell($width, 5, $ips_text, 0, 'R', 0, 1, '', '', true);
        
        // 4. Línea divisoria debajo de todo el encabezado
        $this->Line(15, 38, 195, 38);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C');
        $this->Cell(0, 10, 'Generado el ' . date('d/m/Y h:i A'), 0, false, 'R');
    }
}

// Creación del objeto PDF
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Asignación de los datos para el header
$pdf->id_detalle = $datos['id_detalle'];
$pdf->eps_data = [
    'nombre_eps' => $datos['nombre_eps'],
    'id_eps' => $datos['id_eps'],
    'nom_IPS' => $datos['nom_IPS'],
    'nit_ips' => $datos['nit_ips']
];

// Configuración de metadatos del documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Salud Connected');
$pdf->SetTitle('Historia Clínica #' . $datos['id_detalle'] . ' - ' . $datos['nom_paciente']);
$pdf->SetSubject('Resumen de Atención Médica');

// Configuración de márgenes
$pdf->SetMargins(PDF_MARGIN_LEFT, 40, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Añadir una página
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// Función auxiliar para crear secciones
function seccion($pdf, $titulo, $contenido) {
    $pdf->Ln(4);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(220, 230, 240);
    $pdf->Cell(0, 8, $titulo, 0, 1, 'L', true);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->writeHTML($contenido, true, false, true, false, '');
}

// Cálculo de la edad
$edad = (new DateTime($datos['fecha_nac']))->diff(new DateTime())->y;

// Contenido del PDF (sin cambios aquí)
$html_paciente = '...'; // (El resto de tu código para el contenido del PDF es correcto y no necesita cambios)
// ...
// ...

$html_paciente = '
<table cellpadding="4" cellspacing="0" border="0">
    <tr>
        <td width="50%"><b>Nombre:</b> ' . htmlspecialchars($datos['nom_paciente']) . '</td>
        <td width="50%"><b>Documento:</b> ' . htmlspecialchars($datos['doc_paciente']) . '</td>
    </tr>
    <tr>
        <td width="50%"><b>Edad:</b> ' . $edad . ' años</td>
        <td width="50%"><b>Teléfono:</b> ' . htmlspecialchars($datos['tel_usu']) . '</td>
    </tr>
    <tr>
        <td colspan="2"><b>Correo:</b> ' . htmlspecialchars($datos['correo_usu']) . '</td>
    </tr>
</table>';
seccion($pdf, 'Datos del Paciente', $html_paciente);

$html_consulta = '
<table cellpadding="4" cellspacing="0" border="0">
    <tr>
        <td width="50%"><b>Médico Tratante:</b> ' . htmlspecialchars($datos['nom_medico']) . '</td>
        <td width="50%"><b>Especialidad:</b> ' . htmlspecialchars($datos['nom_espe']) . '</td>
    </tr>
</table>';
seccion($pdf, 'Información de la Consulta', $html_consulta);

$html_motivo = '<div>' . nl2br(htmlspecialchars($datos['motivo_de_cons'])) . '</div>';
seccion($pdf, 'Motivo de Consulta', $html_motivo);


$html_signos = '
<table cellpadding="4" cellspacing="0" border="0">
    <tr>
        <td width="50%"><b>Presión Arterial:</b> ' . htmlspecialchars($datos['presion']) . ' mmHg</td>
        <td width="50%"><b>Saturación O₂:</b> ' . htmlspecialchars($datos['saturacion']) . ' %</td>
    </tr>
    <tr>
        <td width="50%"><b>Peso:</b> ' . htmlspecialchars($datos['peso']) . ' kg</td>
        <td width="50%"><b>Estatura:</b> ' . htmlspecialchars($datos['estatura']) . ' m</td>
    </tr>
</table>';
seccion($pdf, 'Signos Vitales y Medidas', $html_signos);

$html_diagnostico = '
    <p><b>Diagnóstico Principal:</b> ' . htmlspecialchars($datos['diagnostico'] ?? 'No especificado') . '</p>
    <p><b>Prescripción y Comentarios:</b><br>' . nl2br(htmlspecialchars($datos['prescripcion'] ?? 'Sin prescripción.')) . '</p>';
seccion($pdf, 'Diagnóstico y Plan de Manejo', $html_diagnostico);

if (!empty($medicamentos)) {
    $html_medicamentos = '<ul>';
    foreach ($medicamentos as $med) {
        $html_medicamentos .= '<li>' . htmlspecialchars($med['nom_medicamento']) . ' (Cantidad: ' . htmlspecialchars($med['can_medica']) . ')</li>';
    }
    $html_medicamentos .= '</ul>';
    seccion($pdf, 'Medicamentos Recetados', $html_medicamentos);
}

if (!empty($procedimientos)) {
    $html_procedimientos = '<ul>';
    foreach ($procedimientos as $proc) {
        $html_procedimientos .= '<li>' . htmlspecialchars($proc['procedimiento']) . ' (Cantidad: ' . htmlspecialchars($proc['cant_proced']) . ')</li>';
    }
    $html_procedimientos .= '</ul>';
    seccion($pdf, 'Procedimientos Ordenados', $html_procedimientos);
}

// Generar el PDF y mostrarlo en el navegador
$pdf->Output('historial_' . $datos['id_detalle'] . '.pdf', 'I');
?>