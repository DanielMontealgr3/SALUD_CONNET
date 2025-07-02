<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';
require_once '../../include/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

$db = new database();
$con = $db->conectar();

$filtros = [
    'ips' => trim($_GET['filtro_ips'] ?? ''),
    'doc' => trim($_GET['filtro_doc_medico'] ?? ''),
    'estado' => trim($_GET['filtro_estado_usuario'] ?? '')
];

$params = [];
$where_clauses = ["u.id_rol = 4"];

if (empty($filtros['estado'])) {
    $where_clauses[] = "u.id_est != 17";
} else {
    $where_clauses[] = "u.id_est = :id_est_filtro";
    $params[':id_est_filtro'] = $filtros['estado'];
}

if (!empty($filtros['doc'])) {
    $where_clauses[] = "u.doc_usu LIKE :doc_medico_filtro";
    $params[':doc_medico_filtro'] = "%" . $filtros['doc'] . "%";
}

if (!empty($filtros['ips'])) {
    if ($filtros['ips'] === 'sin_asignacion_activa') {
        $where_clauses[] = "NOT EXISTS (SELECT 1 FROM asignacion_medico am_check WHERE am_check.doc_medico = u.doc_usu AND am_check.id_estado = 1)";
    } elseif ($filtros['ips'] === 'con_asignacion_inactiva') {
         $where_clauses[] = "EXISTS (SELECT 1 FROM asignacion_medico am_check WHERE am_check.doc_medico = u.doc_usu AND am_check.id_estado = 2)";
    } else {
        $where_clauses[] = "EXISTS (SELECT 1 FROM asignacion_medico am_filt WHERE am_filt.doc_medico = u.doc_usu AND am_filt.nit_ips = :nit_ips AND am_filt.id_estado = 1)";
        $params[':nit_ips'] = $filtros['ips'];
    }
}

$sql_where = "WHERE " . implode(" AND ", $where_clauses);

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
                est.nom_est AS nombre_estado_usuario,
                esp.nom_espe,
                (SELECT GROUP_CONCAT(DISTINCT CONCAT(i_sub.nom_IPS, ' (', m_ips.nom_mun, ', ', d_ips.nom_dep, ')') SEPARATOR '; ') 
                 FROM asignacion_medico am_sub
                 JOIN ips i_sub ON am_sub.nit_ips = i_sub.Nit_IPS
                 LEFT JOIN municipio m_ips ON i_sub.ubicacion_mun = m_ips.id_mun
                 LEFT JOIN departamento d_ips ON m_ips.id_dep = d_ips.id_dep
                 WHERE am_sub.doc_medico = u.doc_usu AND am_sub.id_estado = 1) AS nombres_ips_activas
              FROM usuarios u
              JOIN tipo_identificacion td ON u.id_tipo_doc = td.id_tipo_doc
              LEFT JOIN genero g ON u.id_gen = g.id_gen
              LEFT JOIN barrio b ON u.id_barrio = b.id_barrio
              LEFT JOIN municipio m ON b.id_mun = m.id_mun
              LEFT JOIN departamento d ON m.id_dep = d.id_dep
              LEFT JOIN estado est ON u.id_est = est.id_est
              LEFT JOIN especialidad esp ON u.id_especialidad = esp.id_espe
              $sql_where
              GROUP BY u.doc_usu
              ORDER BY u.nom_usu ASC";

try {
    $stmt = $con->prepare($sql_final);
    $stmt->execute($params);
    $datos_para_excel = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $estilo_header = '<style bgcolor="#0D6EFD" color="#FFFFFF"><b>_TEXT_</b></style>';
    $headers = ['Documento', 'Tipo Doc', 'Nombre Completo', 'Correo', 'Teléfono', 'Fecha Nacimiento', 'Género', 'Departamento (Residencia)', 'Municipio (Residencia)', 'Barrio', 'Dirección', 'Estado Usuario', 'Especialidad', 'IPS Asignadas (Activas)'];
    
    $xlsx_data = [];
    $styled_headers = array_map(fn($h) => str_replace('_TEXT_', $h, $estilo_header), $headers);
    $xlsx_data[] = $styled_headers;

    if (empty($datos_para_excel)) {
        $xlsx_data[] = ['No se encontraron médicos con los filtros seleccionados.'];
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
                $row['nombre_estado_usuario'] ?? 'N/A',
                $row['nom_espe'] ?? 'N/A',
                $row['nombres_ips_activas'] ?? 'Ninguna'
            ];
        }
    }

    $fileName = "reporte_medicos_" . date('Y-m-d') . ".xlsx";
    SimpleXLSXGen::fromArray($xlsx_data)->downloadAs($fileName);

} catch (PDOException $e) {
    $error_data = [['Error al generar el reporte'], ['Mensaje: ' . $e->getMessage()]];
    SimpleXLSXGen::fromArray($error_data)->downloadAs('error_reporte.xlsx');
}
exit;