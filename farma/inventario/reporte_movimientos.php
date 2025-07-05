<?php
// =========================================================================
// ==              REPORTE_MOVIMIENTOS.PHP - VERSIÓN CORREGIDA              ==
// =========================================================================

// --- BLOQUE 1: CONFIGURACIÓN CENTRALIZADA ---
// CAMBIO 1: Reemplazamos las inclusiones separadas por una única llamada a config.php.
// Este archivo se encarga de todo: sesión, rutas, conexión a BD.
require_once __DIR__ . '/../../include/config.php';

// CAMBIO 2: Incluimos la validación de sesión DESPUÉS de config.php, usando ROOT_PATH.
require_once ROOT_PATH . '/include/validar_sesion.php';

// CAMBIO 3: Incluimos la librería de Excel usando ROOT_PATH para una ruta infalible.
// Verificamos que la ruta sea correcta desde la raíz del proyecto.
require_once ROOT_PATH . '/include/SimpleXLSXGen.php'; 

// Importamos la clase de la librería.
use Shuchkin\SimpleXLSXGen;

// --- BLOQUE 2: LÓGICA DEL REPORTE ---

// CAMBIO 4: Eliminamos la creación manual de la conexión.
// La variable $con ya está disponible globalmente desde config.php.
// $db = new database(); // <- ELIMINADO
// $con = $db->conectar(); // <- ELIMINADO

// CAMBIO 5: Estandarizamos el nombre de la variable de sesión para el NIT de la farmacia.
// Usamos 'nit_farma', que es la que se establece al iniciar sesión y se usa en otras partes.
$nit_farmacia_actual = $_SESSION['nit_farma'] ?? null;
if (!$nit_farmacia_actual) {
    // Si no hay farmacia, se genera un Excel de error claro.
    SimpleXLSXGen::fromArray([['Error: No se ha podido identificar la farmacia. Verifique su sesión.']])->downloadAs('error_reporte_movimientos.xlsx');
    exit;
}

// Tu lógica para recoger los filtros y construir la consulta es CORRECTA.
// No necesita cambios, ya que usa la variable $con.
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
        mi.id_movimiento, mi.fecha_movimiento, med.nom_medicamento, tm.nom_mov, mi.cantidad, 
        mi.lote, mi.fecha_vencimiento, u.doc_usu, u.nom_usu, mi.notas
    " . $sql_base_from . $sql_where . "
    ORDER BY mi.fecha_movimiento DESC, mi.id_movimiento DESC
";

$stmt = $con->prepare($sql_final);
$stmt->execute($params);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CAMBIO 6: Estandarizamos el nombre de la variable de sesión para el NOMBRE de la farmacia.
// Usamos 'nombre_farmacia_actual' para mantener la consistencia.
$nombre_farmacia_asignada = $_SESSION['nombre_farmacia_actual'] ?? 'Farmacia';
$datos_para_excel = [];

// Tu lógica para construir el array para Excel es PERFECTA. No necesita cambios.
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

// La generación del Excel es PERFECTA. No necesita cambios.
SimpleXLSXGen::fromArray($datos_para_excel)
    ->setColWidth(2, 35) // Medicamento
    ->setColWidth(3, 20) // Tipo Movimiento
    ->setColWidth(8, 25) // Doc. Responsable
    ->setColWidth(9, 35) // Nombre Responsable
    ->setColWidth(10, 40) // Notas
    ->downloadAs($fileName);

exit;
?>