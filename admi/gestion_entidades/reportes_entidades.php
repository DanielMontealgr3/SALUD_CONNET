<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';
require_once '../../include/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

$db = new database();
$con = $db->conectar();

$tipo_entidad = trim($_GET['tipo_entidad'] ?? 'todas');
$filtro_nombre = trim($_GET['filtro_nombre'] ?? '');
$filtro_like = "%" . $filtro_nombre . "%";

$datos_para_excel = [];
$params = [];
if (!empty($filtro_nombre)) {
    $params[':filtro'] = $filtro_like;
}

$estilo_header = '<style bgcolor="#0D6EFD" color="#FFFFFF"><b>_TEXT_</b></style>';

try {
    if ($tipo_entidad === 'farmacias') {
        $headers = ['NIT', 'Nombre', 'Dirección', 'Gerente', 'Teléfono', 'Correo'];
        $sql = "SELECT nit_farm, nom_farm, direc_farm, nom_gerente, tel_farm, correo_farm FROM farmacias";
        if (!empty($filtro_nombre)) $sql .= " WHERE nom_farm LIKE :filtro";
        $sql .= " ORDER BY nom_farm ASC";
        $stmt = $con->prepare($sql);
        $stmt->execute($params);
        $datos_para_excel = $stmt->fetchAll(PDO::FETCH_NUM);

    } elseif ($tipo_entidad === 'eps') {
        $headers = ['NIT', 'Nombre', 'Dirección', 'Gerente', 'Teléfono', 'Correo'];
        $sql = "SELECT nit_eps, nombre_eps, direc_eps, nom_gerente, telefono, correo FROM eps";
        if (!empty($filtro_nombre)) $sql .= " WHERE nombre_eps LIKE :filtro";
        $sql .= " ORDER BY nombre_eps ASC";
        $stmt = $con->prepare($sql);
        $stmt->execute($params);
        $datos_para_excel = $stmt->fetchAll(PDO::FETCH_NUM);

    } elseif ($tipo_entidad === 'ips') {
        $headers = ['NIT', 'Nombre', 'Dirección', 'Gerente', 'Teléfono', 'Correo', 'Municipio', 'Departamento'];
        $sql = "SELECT i.Nit_IPS, i.nom_IPS, i.direc_IPS, i.nom_gerente, i.tel_IPS, i.correo_IPS, m.nom_mun, d.nom_dep 
                FROM ips i 
                LEFT JOIN municipio m ON i.ubicacion_mun = m.id_mun 
                LEFT JOIN departamento d ON m.id_dep = d.id_dep";
        if (!empty($filtro_nombre)) $sql .= " WHERE i.nom_IPS LIKE :filtro";
        $sql .= " ORDER BY i.nom_IPS ASC";
        $stmt = $con->prepare($sql);
        $stmt->execute($params);
        $datos_para_excel = $stmt->fetchAll(PDO::FETCH_NUM);

    } elseif ($tipo_entidad === 'todas') {
        // Se añaden 'Gerente' y 'Dirección' a los encabezados
        $headers = ['NIT/ID', 'Nombre', 'Gerente', 'Dirección', 'Tipo', 'Teléfono', 'Correo'];
        
        $where_farm = !empty($filtro_nombre) ? "WHERE nom_farm LIKE :filtro_farm" : "";
        $where_eps  = !empty($filtro_nombre) ? "WHERE nombre_eps LIKE :filtro_eps" : "";
        $where_ips  = !empty($filtro_nombre) ? "WHERE nom_IPS LIKE :filtro_ips" : "";
        
        // Se añaden las columnas nom_gerente y la dirección correspondiente a cada SELECT
        $sql_union = "
            (SELECT nit_farm, nom_farm, nom_gerente, direc_farm, 'Farmacia', tel_farm, correo_farm FROM farmacias $where_farm) UNION ALL
            (SELECT nit_eps, nombre_eps, nom_gerente, direc_eps, 'EPS', telefono, correo FROM eps $where_eps) UNION ALL
            (SELECT Nit_IPS, nom_IPS, nom_gerente, direc_IPS, 'IPS', tel_IPS, correo_IPS FROM ips $where_ips)
            ORDER BY 2 ASC";
        
        $stmt = $con->prepare($sql_union);
        if (!empty($filtro_nombre)) {
            $stmt->bindValue(':filtro_farm', $filtro_like);
            $stmt->bindValue(':filtro_eps', $filtro_like);
            $stmt->bindValue(':filtro_ips', $filtro_like);
        }
        $stmt->execute();
        $datos_para_excel = $stmt->fetchAll(PDO::FETCH_NUM);
    }

    $xlsx_data = [];
    $styled_headers = array_map(fn($h) => str_replace('_TEXT_', $h, $estilo_header), $headers);
    $xlsx_data[] = $styled_headers;
    
    if (empty($datos_para_excel)) {
        $xlsx_data[] = ['No se encontraron registros con los filtros seleccionados.'];
    } else {
        foreach ($datos_para_excel as $row) {
            $xlsx_data[] = $row;
        }
    }

    $fileName = "reporte_entidades_" . $tipo_entidad . "_" . date('Y-m-d') . ".xlsx";
    SimpleXLSXGen::fromArray($xlsx_data)->downloadAs($fileName);

} catch (PDOException $e) {
    $error_data = [['Error al generar el reporte'], ['Mensaje: ' . $e->getMessage()]];
    SimpleXLSXGen::fromArray($error_data)->downloadAs('error_reporte.xlsx');
}

exit;