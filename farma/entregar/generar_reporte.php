<?php
// --- BLOQUE 1: CONFIGURACIÓN Y SEGURIDAD ---
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';
// Asegúrate de que la ruta a la librería SimpleXLSXGen es correcta
require_once ROOT_PATH . '/include/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

// --- BLOQUE 2: VALIDACIÓN DE SESIÓN Y PARÁMETROS ---
// Se usa la variable de sesión estandarizada
$nit_farmacia_actual = $_SESSION['nit_farma'] ?? null;

if (!$nit_farmacia_actual) {
    // Si no hay farmacia, se genera un Excel de error
    SimpleXLSXGen::fromArray([['Error: No se ha podido identificar la farmacia actual.']])->downloadAs('error_reporte_entregas.xlsx');
    exit;
}

// Se recogen los filtros desde la URL (GET)
$filtro_doc = trim($_GET['filtro_doc'] ?? '');
$filtro_id = trim($_GET['filtro_id'] ?? '');
$filtro_orden_fecha = trim($_GET['filtro_orden_fecha'] ?? 'desc');
$filtro_fecha_inicio = trim($_GET['filtro_fecha_inicio'] ?? '');
$filtro_fecha_fin = trim($_GET['filtro_fecha_fin'] ?? '');

// --- BLOQUE 3: CONSTRUCCIÓN Y EJECUCIÓN DE LA CONSULTA ---
try {
    // Se usa la conexión global $con de config.php
    global $con;
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Se mantiene tu lógica de consulta original para el historial de entregas
    $sql_base_from = "
        FROM entrega_medicamentos em
        JOIN usuarios far ON em.doc_farmaceuta = far.doc_usu
        JOIN estado est ON em.id_estado = est.id_est
        JOIN detalles_histo_clini dhc ON em.id_detalle_histo = dhc.id_detalle
        JOIN medicamentos med ON dhc.id_medicam = med.id_medicamento
        JOIN historia_clinica hc ON dhc.id_historia = hc.id_historia
        JOIN citas c ON hc.id_cita = c.id_cita
        JOIN usuarios pac ON c.doc_pac = pac.doc_usu
        JOIN turno_ent_medic tem ON hc.id_historia = tem.id_historia AND tem.id_est = 9
        JOIN asignacion_farmaceuta af ON em.doc_farmaceuta = af.doc_farma
    ";

    $sql_where_conditions = ["af.nit_farma = :nit_farma_actual"];
    $params = [':nit_farma_actual' => $nit_farmacia_actual];

    if (!empty($filtro_doc)) { $sql_where_conditions[] = "pac.doc_usu LIKE :doc_pac"; $params[':doc_pac'] = "%" . $filtro_doc . "%"; }
    if (!empty($filtro_id)) { $sql_where_conditions[] = "em.id_entrega = :id_entrega"; $params[':id_entrega'] = $filtro_id; }
    if (!empty($filtro_fecha_inicio)) { $sql_where_conditions[] = "DATE(tem.fecha_entreg) >= :fecha_inicio"; $params[':fecha_inicio'] = $filtro_fecha_inicio; }
    if (!empty($filtro_fecha_fin)) { $sql_where_conditions[] = "DATE(tem.fecha_entreg) <= :fecha_fin"; $params[':fecha_fin'] = $filtro_fecha_fin; }

    $sql_where = " WHERE " . implode(" AND ", $sql_where_conditions);
    $orden_sql = ($filtro_orden_fecha === 'asc') ? 'ASC' : 'DESC';

    $sql_final = "
        SELECT 
            em.id_entrega, 
            MAX(tem.fecha_entreg) AS fecha_entrega, 
            pac.nom_usu AS nombre_paciente,
            pac.doc_usu AS doc_paciente, 
            far.nom_usu AS nombre_farmaceuta, 
            far.doc_usu AS doc_farmaceuta,
            med.nom_medicamento, 
            em.cantidad_entregada, 
            em.lote, 
            est.nom_est
        " . $sql_base_from . $sql_where . "
        GROUP BY em.id_entrega
        ORDER BY fecha_entrega " . $orden_sql . ", em.id_entrega DESC
    ";

    $stmt = $con->prepare($sql_final);
    $stmt->execute($params);
    $entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error en generar_reporte.php: " . $e->getMessage());
    SimpleXLSXGen::fromArray([['Error al generar el reporte: ' . $e->getMessage()]])->downloadAs('error_reporte_entregas.xlsx');
    exit;
}

// --- BLOQUE 4: PREPARACIÓN Y GENERACIÓN DEL ARCHIVO EXCEL ---
$nombre_farmacia_asignada = $_SESSION['nombre_farmacia_actual'] ?? 'Farmacia';
$datos_para_excel = [];

$header_texts = [
    'ID Entrega', 'Fecha Entrega', 'Doc. Paciente', 'Nombre Paciente', 'Medicamento',
    'Cantidad', 'Lote', 'Doc. Farmaceuta', 'Nombre Farmaceuta', 'Estado'
];
$styled_headers = array_map(fn($text) => '<style bgcolor="#0D6EFD" color="#FFFFFF"><b>' . $text . '</b></style>', $header_texts);
$datos_para_excel[] = $styled_headers;

if (empty($entregas)) {
    $datos_para_excel[] = ['No se encontraron registros con los filtros seleccionados.'];
} else {
    foreach ($entregas as $entrega) {
        $datos_para_excel[] = [
            $entrega['id_entrega'], 
            date('d/m/Y H:i', strtotime($entrega['fecha_entrega'])), 
            $entrega['doc_paciente'],
            $entrega['nombre_paciente'], 
            $entrega['nom_medicamento'], 
            $entrega['cantidad_entregada'],
            $entrega['lote'], 
            $entrega['doc_farmaceuta'], 
            $entrega['nombre_farmaceuta'],
            $entrega['nom_est']
        ];
    }
}

$fileName = "reporte_entregas_" . preg_replace('/[^a-zA-Z0-9]/', '_', $nombre_farmacia_asignada) . "_" . date('Y-m-d') . ".xlsx";

SimpleXLSXGen::fromArray($datos_para_excel)
    ->setColWidth(2, 20)->setColWidth(3, 30)->setColWidth(4, 30)
    ->setColWidth(8, 20)->setColWidth(9, 30)
    ->downloadAs($fileName);

exit;