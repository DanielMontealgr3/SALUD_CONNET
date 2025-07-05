<?php
// =========================================================================
// ==                 REPORTE_ACTUAL.PHP - VERSIÓN CORREGIDA                ==
// =========================================================================

// --- BLOQUE 1: CONFIGURACIÓN CENTRALIZADA ---
// CAMBIO 1: Se elimina la inclusión de archivos por separado.
// Se incluye PRIMERO y ÚNICAMENTE el archivo de configuración central.
// Este archivo ya inicia la sesión, define ROOT_PATH y crea la conexión $con.
require_once __DIR__ . '/../../include/config.php';

// CAMBIO 2: Ahora incluimos la validación de sesión DESPUÉS de config.php.
// Usamos ROOT_PATH para una ruta segura.
require_once ROOT_PATH . '/include/validar_sesion.php';

// CAMBIO 3: Usamos ROOT_PATH para incluir la librería.
// OJO: Asegúrate de que la ruta a SimpleXLSXGen.php es correcta desde la raíz.
// Si está en 'farma/includes_farm', la ruta sería: ROOT_PATH . '/farma/includes_farm/SimpleXLSXGen.php'
// Por tu ruta original, parece que está en 'include', así que lo dejo así:
require_once ROOT_PATH . '/include/SimpleXLSXGen.php'; 

// Se importa la clase de la librería.
use Shuchkin\SimpleXLSXGen;

// --- BLOQUE 2: LÓGICA DEL REPORTE ---

// CAMBIO 4: Se elimina la creación manual de la conexión.
// La variable $con ya existe y está configurada correctamente desde config.php.
// $db = new database(); // <- ELIMINADO
// $con = $db->conectar(); // <- ELIMINADO

// CAMBIO 5: Se estandariza la variable de sesión.
// Usamos 'nit_farma' que es la que se usa en la página de inventario y en el otro reporte.
$nit_farmacia_actual = $_SESSION['nit_farma'] ?? null;
if (!$nit_farmacia_actual) {
    // Si no se encuentra la farmacia, se genera un Excel de error.
    SimpleXLSXGen::fromArray([['Error: No se ha podido identificar la farmacia. Verifique su sesión.']])->downloadAs('error_reporte.xlsx');
    exit;
}

// El resto de tu lógica para obtener los filtros y construir la consulta es CORRECTA.
// No necesita cambios, ya que usa la variable $con.
$filtro_tipo = trim($_GET['filtro_tipo'] ?? 'todos');
$filtro_nombre = trim($_GET['filtro_nombre'] ?? '');
$filtro_estado_stock = trim($_GET['filtro_stock'] ?? 'todos');
$filtro_orden = trim($_GET['filtro_orden'] ?? 'asc');
$filtro_codigo_barras = trim($_GET['filtro_codigo_barras'] ?? '');

$params = [':nit_farma' => $nit_farmacia_actual];
$sql_where_conditions = [];

if ($filtro_tipo !== 'todos') {
    $sql_where_conditions[] = "m.id_tipo_medic = :id_tipo";
    $params[':id_tipo'] = $filtro_tipo;
}
if (!empty($filtro_nombre)) {
    $sql_where_conditions[] = "m.nom_medicamento LIKE :nombre_medic";
    $params[':nombre_medic'] = "%" . $filtro_nombre . "%";
}
if (!empty($filtro_codigo_barras)) {
    $sql_where_conditions[] = "m.codigo_barras LIKE :codigo_barras";
    $params[':codigo_barras'] = "%" . $filtro_codigo_barras . "%";
}
if ($filtro_estado_stock !== 'todos') {
    if ($filtro_estado_stock === 'disponible') $sql_where_conditions[] = "i.id_estado = 13";
    elseif ($filtro_estado_stock === 'pocas_unidades') $sql_where_conditions[] = "i.id_estado = 14";
    elseif ($filtro_estado_stock === 'no_disponible') $sql_where_conditions[] = "i.id_estado = 15";
}

$sql_from_join = "FROM inventario_farmacia i JOIN medicamentos m ON i.id_medicamento = m.id_medicamento JOIN tipo_de_medicamento tm ON m.id_tipo_medic = tm.id_tip_medic JOIN estado e ON i.id_estado = e.id_est WHERE i.nit_farm = :nit_farma";
if (!empty($sql_where_conditions)) {
    $sql_from_join .= " AND " . implode(" AND ", $sql_where_conditions);
}

$order_by = ($filtro_orden === 'desc') ? "m.nom_medicamento DESC" : "m.nom_medicamento ASC";
$sql_final = "SELECT m.id_medicamento, m.nom_medicamento, m.codigo_barras, tm.nom_tipo_medi, i.cantidad_actual, e.nom_est, i.fecha_ultima_actualizacion " . $sql_from_join . " GROUP BY m.id_medicamento, m.nom_medicamento, m.codigo_barras, tm.nom_tipo_medi, i.cantidad_actual, e.nom_est, i.fecha_ultima_actualizacion ORDER BY " . $order_by;

$stmt_inventario = $con->prepare($sql_final);
$stmt_inventario->execute($params);
$inventario_list = $stmt_inventario->fetchAll(PDO::FETCH_ASSOC);

// CAMBIO 6: Se estandariza la variable de sesión para el nombre de la farmacia.
$nombre_farmacia_asignada = $_SESSION['nombre_farmacia_actual'] ?? 'Farmacia';
$datos_para_excel = [];

// Tu lógica para construir el array para Excel es PERFECTA. No necesita cambios.
$header_texts = [
    'Código Barras', 'Medicamento', 'Tipo', 'Cantidad Total', 'Estado', 'Lotes Activos (Vencimiento)', 'Última Actualización'
];
$styled_headers = array_map(fn($text) => '<style bgcolor="#0D6EFD" color="#FFFFFF"><b>' . $text . '</b></style>', $header_texts);
$datos_para_excel[] = $styled_headers;

if (empty($inventario_list)) {
    $datos_para_excel[] = ['No se encontraron registros con los filtros seleccionados.'];
} else {
    $stock_por_lote_sql = "SUM(CASE WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad WHEN id_tipo_mov IN (2, 4) THEN -cantidad ELSE 0 END)";
    $sql_lotes = "SELECT lote, fecha_vencimiento FROM movimientos_inventario WHERE id_medicamento = :id_medicamento AND nit_farm = :nit_farm AND lote IS NOT NULL GROUP BY lote, fecha_vencimiento HAVING $stock_por_lote_sql > 0 ORDER BY fecha_vencimiento ASC";
    $stmt_lotes = $con->prepare($sql_lotes);

    foreach ($inventario_list as $item) {
        $lotes_str = 'N/A';
        if ($item['cantidad_actual'] > 0) {
            $stmt_lotes->execute([
                ':id_medicamento' => $item['id_medicamento'],
                ':nit_farm' => $nit_farmacia_actual
            ]);
            $lotes_activos = $stmt_lotes->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($lotes_activos)) {
                $lotes_info = array_map(fn($lote) => $lote['lote'] . ' (' . date('d/m/Y', strtotime($lote['fecha_vencimiento'])) . ')', $lotes_activos);
                $lotes_str = implode(', ', $lotes_info);
            }
        }
        
        $datos_para_excel[] = [
            $item['codigo_barras'],
            $item['nom_medicamento'],
            $item['nom_tipo_medi'],
            $item['cantidad_actual'],
            $item['nom_est'],
            $lotes_str,
            date('d/m/Y H:i:s', strtotime($item['fecha_ultima_actualizacion']))
        ];
    }
}

$fileName = "reporte_inventario_" . preg_replace('/[^a-zA-Z0-9]/', '_', $nombre_farmacia_asignada) . "_" . date('Y-m-d') . ".xlsx";

// La generación del Excel es PERFECTA. No necesita cambios.
SimpleXLSXGen::fromArray($datos_para_excel)
    ->setColWidth(1, 35)
    ->setColWidth(2, 20)
    ->setColWidth(6, 45)
    ->setColWidth(7, 20)
    ->downloadAs($fileName);

exit;
?>