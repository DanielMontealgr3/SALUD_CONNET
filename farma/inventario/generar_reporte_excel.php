<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';
// Reemplaza la línea del require_once con esta:
require_once '../farma/includes_farm/PhpSpreadsheet-master/src/Bootstrap.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso no permitido.");
}

// ... (El resto del código PHP para la consulta y creación del Excel no cambia)
// ... (Puedes usar el código completo de la respuesta anterior, solo asegúrate de que la línea `require_once` de arriba sea la correcta)

try {
    $db = new database();
    $con = $db->conectar();

    $fecha_inicio = $_POST['fecha_inicio'] . ' 00:00:00';
    $fecha_fin = $_POST['fecha_fin'] . ' 23:59:59';
    $tipo_movimiento = $_POST['tipo_movimiento'];
    $tipo_medicamento = $_POST['tipo_medicamento'];
    $nit_farmacia = $_SESSION['nit_farmacia_asignada_actual'];

    $sql = "SELECT 
                mov.fecha_movimiento, m.nom_medicamento, m.codigo_barras, tm.nom_tipo_medi,
                tmov.nom_mov, mov.cantidad, mov.lote, mov.fecha_vencimiento, u.nom_usu,
                f.nom_farm, mov.notas, inv.cantidad_actual AS stock_final, est.nom_est AS estado_stock
            FROM movimientos_inventario mov
            JOIN medicamentos m ON mov.id_medicamento = m.id_medicamento
            JOIN tipo_de_medicamento tm ON m.id_tipo_medic = tm.id_tip_medic
            JOIN tipo_movimiento tmov ON mov.id_tipo_mov = tmov.id_tipo_mov
            LEFT JOIN usuarios u ON mov.id_usuario_responsable = u.doc_usu
            LEFT JOIN farmacias f ON mov.nit_farm = f.nit_farm
            LEFT JOIN inventario_farmacia inv ON mov.id_medicamento = inv.id_medicamento AND mov.nit_farm = inv.nit_farm
            LEFT JOIN estado est ON inv.id_estado = est.id_est
            WHERE mov.nit_farm = :nit_farmacia AND mov.fecha_movimiento BETWEEN :fecha_inicio AND :fecha_fin";

    $params = [
        ':nit_farmacia' => $nit_farmacia,
        ':fecha_inicio' => $fecha_inicio,
        ':fecha_fin' => $fecha_fin
    ];

    if ($tipo_movimiento !== 'todos') {
        $sql .= " AND mov.id_tipo_mov = :tipo_mov";
        $params[':tipo_mov'] = $tipo_movimiento;
    }
    if ($tipo_medicamento !== 'todos') {
        $sql .= " AND m.id_tipo_medic = :tipo_medic";
        $params[':tipo_medic'] = $tipo_medicamento;
    }
    $sql .= " ORDER BY mov.fecha_movimiento DESC";
    
    $stmt = $con->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Movimientos');
    
    $drawing = new Drawing();
    $logoPath = __DIR__ . '/../../img/Logo.png';
    if (file_exists($logoPath)) {
        $drawing->setName('Logo')->setDescription('Logo')->setPath($logoPath)->setHeight(60)->setCoordinates('A1')->setWorksheet($sheet);
    }
    
    $sheet->mergeCells('C1:M2')->setCellValue('C1', 'Reporte de Movimientos de Inventario');
    $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(22)->setName('Calibri');
    $sheet->getStyle('C1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    
    $sheet->mergeCells('C3:M3')->setCellValue('C3', 'Período: ' . date('d/m/Y', strtotime($_POST['fecha_inicio'])) . ' al ' . date('d/m/Y', strtotime($_POST['fecha_fin'])));
    $sheet->getStyle('C3')->getFont()->setSize(12)->setName('Calibri');
    $sheet->getStyle('C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getRowDimension('1')->setRowHeight(25);
    $sheet->getRowDimension('2')->setRowHeight(25);

    $headers = ['Fecha y Hora', 'Medicamento', 'Código Barras', 'Tipo Medicamento', 'Tipo Movimiento', 'Cantidad Movida', 'Lote', 'Vencimiento', 'Responsable', 'Farmacia', 'Notas', 'Stock Final', 'Estado Stock'];
    $sheet->fromArray($headers, NULL, 'A5');
    
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'name' => 'Calibri', 'size' => 11],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4F81BD']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ];
    $sheet->getStyle('A5:M5')->applyFromArray($headerStyle);
    
    $rowNum = 6;
    if (!empty($resultados)) {
        foreach ($resultados as $row) {
            $sheet->fromArray(array_values($row), NULL, 'A' . $rowNum++);
        }
        $sheet->setAutoFilter("A5:M" . ($rowNum - 1));
    }
    
    foreach (range('A', 'M') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    $nombre_archivo = "Reporte_Movimientos_" . date('Y-m-d') . ".xlsx";
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $nombre_archivo . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    die('Error al generar el reporte: ' . $e->getMessage());
}
?>