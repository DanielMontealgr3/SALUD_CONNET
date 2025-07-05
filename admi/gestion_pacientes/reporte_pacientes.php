<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';
require_once '../../include/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

$db = new database();
$con = $db->conectar();

$filtros = [
    'eps' => $_GET['filtro_eps'] ?? '',
    'doc' => $_GET['filtro_doc_paciente'] ?? '',
    'estado' => $_GET['filtro_estado_paciente'] ?? ''
];

$params = [];
$where_clauses = ["u.id_rol = 2"];

if (empty($filtros['estado'])) {
    $where_clauses[] = "u.id_est != 17";
} else {
    $where_clauses[] = "u.id_est = :estado";
    $params[':estado'] = $filtros['estado'];
}

if (!empty($filtros['doc'])) {
    $where_clauses[] = "u.doc_usu LIKE :doc";
    $params[':doc'] = "%" . $filtros['doc'] . "%";
}

$from_join = "FROM usuarios u
              JOIN tipo_identificacion td ON u.id_tipo_doc = td.id_tipo_doc
              LEFT JOIN genero g ON u.id_gen = g.id_gen
              LEFT JOIN barrio b ON u.id_barrio = b.id_barrio
              LEFT JOIN municipio m ON b.id_mun = m.id_mun
              LEFT JOIN departamento d ON m.id_dep = d.id_dep
              LEFT JOIN estado est ON u.id_est = est.id_est
              LEFT JOIN (
                  SELECT doc_afiliado, id_eps, fecha_afi, id_regimen FROM afiliados WHERE id_estado = 1
              ) af ON u.doc_usu = af.doc_afiliado
              LEFT JOIN eps e ON af.id_eps = e.nit_eps
              LEFT JOIN regimen r ON af.id_regimen = r.id_regimen";

if ($filtros['eps'] === 'sin_afiliacion_activa') {
    $where_clauses[] = "af.id_eps IS NULL";
} elseif (!empty($filtros['eps'])) {
    $where_clauses[] = "af.id_eps = :eps";
    $params[':eps'] = $filtros['eps'];
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

$sql_final = "SELECT 
                u.doc_usu,
                td.nom_doc,
                u.nom_usu,
                u.correo_usu,
                u.tel_usu,
                u.fecha_nac,
                g.nom_gen,
                d.nom_dep,
                m.nom_mun,
                b.nom_barrio,
                u.direccion_usu,
                est.nom_est,
                e.nombre_eps,
                r.nom_reg,
                af.fecha_afi
              " . $from_join . " " . $where_sql . " GROUP BY u.doc_usu ORDER BY u.nom_usu ASC";

try {
    $stmt = $con->prepare($sql_final);
    $stmt->execute($params);
    $datos_para_excel = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $estilo_header = '<style bgcolor="#0D6EFD" color="#FFFFFF"><b>_TEXT_</b></style>';
    $headers = ['Documento', 'Tipo Doc', 'Nombre Completo', 'Correo', 'Teléfono', 'Fecha Nacimiento', 'Género', 'Departamento', 'Municipio', 'Barrio', 'Dirección', 'Estado Usuario', 'EPS Afiliada (Activa)', 'Régimen', 'Fecha Afiliación'];
    
    $xlsx_data = [];
    $styled_headers = array_map(fn($h) => str_replace('_TEXT_', $h, $estilo_header), $headers);
    $xlsx_data[] = $styled_headers;

    if (empty($datos_para_excel)) {
        $xlsx_data[] = ['No se encontraron pacientes con los filtros seleccionados.'];
    } else {
        foreach ($datos_para_excel as $row) {
            $xlsx_data[] = [
                $row['doc_usu'],
                $row['nom_doc'],
                $row['nom_usu'],
                $row['correo_usu'] ?? 'N/A',
                $row['tel_usu'] ?? 'N/A',
                $row['fecha_nac'] ? date("d/m/Y", strtotime($row['fecha_nac'])) : 'N/A',
                $row['nom_gen'] ?? 'N/A',
                $row['nom_dep'] ?? 'N/A',
                $row['nom_mun'] ?? 'N/A',
                $row['nom_barrio'] ?? 'N/A',
                $row['direccion_usu'] ?? 'N/A',
                $row['nom_est'] ?? 'N/A',
                $row['nombre_eps'] ?? 'Sin EPS Activa',
                $row['nom_reg'] ?? 'N/A',
                $row['fecha_afi'] ? date("d/m/Y", strtotime($row['fecha_afi'])) : 'N/A'
            ];
        }
    }

    $fileName = "reporte_pacientes_" . date('Y-m-d') . ".xlsx";
    SimpleXLSXGen::fromArray($xlsx_data)->downloadAs($fileName);

} catch (PDOException $e) {
    $error_data = [['Error al generar el reporte'], ['Mensaje: ' . $e->getMessage()]];
    SimpleXLSXGen::fromArray($error_data)->downloadAs('error_reporte.xlsx');
}
exit;