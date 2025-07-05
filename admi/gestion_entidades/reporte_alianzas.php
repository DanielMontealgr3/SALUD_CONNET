<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';
require_once '../../include/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

$db = new database();
$con = $db->conectar();

$filtros = [
    'tipo' => $_GET['filtro_tipo'] ?? 'todas',
    'estado' => $_GET['filtro_estado'] ?? '',
    'nombre_eps' => $_GET['filtro_nombre_eps'] ?? '',
    'entidad_aliada' => $_GET['filtro_nombre_entidad_aliada'] ?? '',
    'orden_fecha' => $_GET['filtro_orden_fecha'] ?? 'desc',
    'fecha_inicio' => $_GET['filtro_fecha_inicio'] ?? '',
    'fecha_fin' => $_GET['filtro_fecha_fin'] ?? '',
    'fecha_exacta' => $_GET['filtro_fecha_exacta'] ?? '',
];
$filtro_orden_fecha = $filtros['orden_fecha'] === 'asc' ? 'ASC' : 'DESC';

$params = [];
$query_parts = [];

$build_where = function($tipo_entidad, &$params_ref) use ($filtros) {
    $where = [];
    $suffix = "_" . $tipo_entidad . rand(100, 999);

    if (!empty($filtros['nombre_eps'])) {
        $where[] = "eps.nombre_eps LIKE :nombre_eps" . $suffix;
        $params_ref[':nombre_eps' . $suffix] = "%" . $filtros['nombre_eps'] . "%";
    }
    if (!empty($filtros['entidad_aliada'])) {
        $where[] = ($tipo_entidad === 'farmacia' ? "f.nom_farm" : "i.nom_IPS") . " LIKE :entidad_aliada" . $suffix;
        $params_ref[':entidad_aliada' . $suffix] = "%" . $filtros['entidad_aliada'] . "%";
    }
    if ($filtros['estado'] !== '') {
        $where[] = ($tipo_entidad === 'farmacia' ? "def.id_estado" : "dei.id_estado") . " = :estado" . $suffix;
        $params_ref[':estado' . $suffix] = $filtros['estado'];
    }
    
    $fecha_col = $tipo_entidad === 'farmacia' ? "def.fecha" : "dei.fecha";
    if (!empty($filtros['fecha_exacta'])) {
        $where[] = "DATE($fecha_col) = :fecha_exacta" . $suffix;
        $params_ref[':fecha_exacta' . $suffix] = $filtros['fecha_exacta'];
    } else {
        if (!empty($filtros['fecha_inicio'])) {
            $where[] = "$fecha_col >= :fecha_inicio" . $suffix;
            $params_ref[':fecha_inicio' . $suffix] = $filtros['fecha_inicio'];
        }
        if (!empty($filtros['fecha_fin'])) {
            $where[] = "$fecha_col <= :fecha_fin" . $suffix;
            $params_ref[':fecha_fin' . $suffix] = $filtros['fecha_fin'];
        }
    }
    return !empty($where) ? " WHERE " . implode(" AND ", $where) : "";
};

$estilo_header = '<style bgcolor="#0D6EFD" color="#FFFFFF"><b>_TEXT_</b></style>';
$headers = ['ID Alianza', 'NIT EPS', 'Nombre EPS', 'NIT Aliada', 'Nombre Aliada', 'Tipo Aliada', 'Fecha', 'Estado'];

if ($filtros['tipo'] === 'todas' || $filtros['tipo'] === 'farmacia') {
    $sql_farm = "SELECT def.id_eps_farm, def.nit_eps, eps.nombre_eps, def.nit_farm, f.nom_farm, 'Farmacia', def.fecha, est.nom_est FROM detalle_eps_farm def JOIN eps ON def.nit_eps = eps.nit_eps JOIN farmacias f ON def.nit_farm = f.nit_farm JOIN estado est ON def.id_estado = est.id_est" . $build_where('farmacia', $params);
    $query_parts[] = "($sql_farm)";
}

if ($filtros['tipo'] === 'todas' || $filtros['tipo'] === 'ips') {
    $sql_ips = "SELECT dei.id_eps_ips, dei.nit_eps, eps.nombre_eps, dei.nit_ips, i.nom_IPS, 'IPS', dei.fecha, est.nom_est FROM detalle_eps_ips dei JOIN eps ON dei.nit_eps = eps.nit_eps JOIN ips i ON dei.nit_ips = i.Nit_IPS JOIN estado est ON dei.id_estado = est.id_est" . $build_where('ips', $params);
    $query_parts[] = "($sql_ips)";
}

try {
    $datos_para_excel = [];
    if (!empty($query_parts)) {
        $sql_final = "SELECT * FROM (" . implode(" UNION ALL ", $query_parts) . ") as final_query ORDER BY 7 $filtro_orden_fecha";
        $stmt = $con->prepare($sql_final);
        $stmt->execute($params);
        $datos_para_excel = $stmt->fetchAll(PDO::FETCH_NUM);
    }
    
    $xlsx_data = [];
    $styled_headers = array_map(fn($h) => str_replace('_TEXT_', $h, $estilo_header), $headers);
    $xlsx_data[] = $styled_headers;

    if (empty($datos_para_excel)) {
        $xlsx_data[] = ['No se encontraron alianzas con los filtros seleccionados.'];
    } else {
        foreach ($datos_para_excel as $row) {
            $xlsx_data[] = $row;
        }
    }

    $fileName = "reporte_alianzas_" . date('Y-m-d') . ".xlsx";
    SimpleXLSXGen::fromArray($xlsx_data)->downloadAs($fileName);

} catch (PDOException $e) {
    $error_data = [['Error al generar el reporte'], ['Mensaje: ' . $e->getMessage()]];
    SimpleXLSXGen::fromArray($error_data)->downloadAs('error_reporte.xlsx');
}
exit;