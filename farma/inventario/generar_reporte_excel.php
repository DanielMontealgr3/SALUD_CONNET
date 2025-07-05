<?php
// --- BLOQUE 1: CONFIGURACIÓN INICIAL Y SEGURIDAD ---
// ¡CORRECCIÓN CLAVE! Se incluye PRIMERO el archivo de configuración central.
// Este archivo inicia la sesión, define las constantes ROOT_PATH y BASE_URL, y crea la conexión $con.
require_once __DIR__ . '/../../include/config.php';

// Se incluye el script de validación de sesión, que ahora funcionará correctamente.
require_once ROOT_PATH . '/include/validar_sesion.php';

// Se incluye el archivo de arranque de la librería PhpSpreadsheet, usando la constante ROOT_PATH
// para asegurar una ruta absoluta y correcta desde la raíz del proyecto.
require_once ROOT_PATH . '/farma/includes_farm/PhpSpreadsheet-master/src/Bootstrap.php';

// Se importan las clases necesarias de PhpSpreadsheet.
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Se verifica que la petición sea de tipo POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso no permitido.");
}

// --- BLOQUE 2: RECOLECCIÓN DE FILTROS Y CONSTRUCCIÓN DE CONSULTA ---
try {
    // La conexión $con ya está disponible y lista para usar desde config.php.

    // Recolección de los filtros del formulario.
    $fecha_inicio = $_POST['fecha_inicio'] . ' 00:00:00';
    $fecha_fin = $_POST['fecha_fin'] . ' 23:59:59';
    $tipo_movimiento = $_POST['tipo_movimiento'];
    $tipo_medicamento = $_POST['tipo_medicamento'];
    $nit_farmacia = $_SESSION['nit_farma']; // Usamos la variable de sesión correcta.

    // Construcción de la consulta SQL.
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

    // Se añaden los filtros opcionales.
    if ($tipo_movimiento !== 'todos') {
        $sql .= " AND mov.id_tipo_mov = :tipo_mov";
        $params[':tipo_mov'] = $tipo_movimiento;
    }
    if ($tipo_medicamento !== 'todos') {
        $sql .= " AND m.id_tipo_medic = :tipo_medic";
        $params[':tipo_medic'] = $tipo_medicamento;
    }
    $sql .= " ORDER BY mov.fecha_movimiento DESC";
    
    // Se ejecuta la consulta.
    $stmt = $con->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- BLOQUE 3: CREACIÓN Y CONFIGURACIÓN DEL ARCHIVO EXCEL ---
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Movimientos');
    
    // Se inserta el logo. La ruta se construye con ROOT_PATH para ser infalible.
    $drawing = new Drawing();
    $logoPath = ROOT_PATH . '/img/Logo.png'; 
    if (file_exists($logoPath)) {
        $drawing->setName('Logo')->setDescription('Logo')->setPath($logoPath)->setHeight(60)->setCoordinates('A1')->setWorksheet($sheet);
    }
    
    // Se configuran los títulos y estilos.
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
    
    // Se insertan los datos.
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
    
    // --- BLOQUE 4: GENERACIÓN Y ENVÍO DEL ARCHIVO EXCEL ---
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