<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

function render_medicos_body($medicos) {
    ob_start();
    if (!empty($medicos)) {
        foreach ($medicos as $medico) {
            $url_actual_para_retorno = 'lista_medicos.php' . '?' . http_build_query($_GET);
            $es_activo = $medico['id_estado_usuario'] == 1;
            $es_eliminado = $medico['id_estado_usuario'] == 17;

            $badge_class = 'bg-secondary';
            if ($es_activo) $badge_class = 'bg-success';
            if ($medico['id_estado_usuario'] == 2) $badge_class = 'bg-warning text-dark';
            if ($es_eliminado) $badge_class = 'bg-danger';
            ?>
            <tr>
                <td><?php echo htmlspecialchars($medico['doc_usu']); ?></td>
                <td><span class="truncate-text" title="<?php echo htmlspecialchars($medico['nom_usu']); ?>"><?php echo htmlspecialchars($medico['nom_usu']); ?></span></td>
                <td><span class="truncate-text" title="<?php echo htmlspecialchars($medico['correo_usu']); ?>"><?php echo htmlspecialchars($medico['correo_usu'] ?? 'N/A'); ?></span></td>
                <td><?php echo htmlspecialchars($medico['nom_espe'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($medico['nombres_ips_activas'] ?: 'Ninguna'); ?></td>
                <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($medico['nombre_estado_usuario']); ?></span></td>
                <td class="acciones-tabla celda-acciones-fija">
                    <?php if ($es_eliminado): ?>
                        <button class="btn btn-success btn-sm btn-cambiar-estado" data-doc-usu="<?php echo htmlspecialchars($medico['doc_usu']); ?>" data-nom-usu="<?php echo htmlspecialchars($medico['nom_usu']); ?>" data-accion="revertir" title="Revertir Eliminación">
                            <i class="bi bi-arrow-counterclockwise"></i><span>Revertir</span>
                        </button>
                    <?php else: ?>
                        <button class="btn btn-sm btn-cambiar-estado <?php echo $es_activo ? 'btn-warning' : 'btn-success'; ?>" data-doc-usu="<?php echo htmlspecialchars($medico['doc_usu']); ?>" data-nom-usu="<?php echo htmlspecialchars($medico['nom_usu']); ?>" data-accion="<?php echo $es_activo ? 'inactivar' : 'activar'; ?>" title="<?php echo $es_activo ? 'Inactivar' : 'Activar'; ?>">
                            <i class="bi <?php echo $es_activo ? 'bi-person-slash' : 'bi-person-check'; ?>"></i><span><?php echo $es_activo ? 'Inactivar' : 'Activar'; ?></span>
                        </button>
                        <button class="btn btn-info btn-sm btn-editar-usuario" data-doc-usu="<?php echo htmlspecialchars($medico['doc_usu']); ?>" title="Editar Médico">
                            <i class="bi bi-pencil-square"></i><span>Editar</span>
                        </button>
                        <button class="btn btn-danger btn-sm btn-eliminar-medico" data-doc-usu="<?php echo htmlspecialchars($medico['doc_usu']); ?>" data-nom-usu="<?php echo htmlspecialchars($medico['nom_usu']); ?>" data-id-tipo-doc="<?php echo htmlspecialchars($medico['id_tipo_doc']); ?>" title="Eliminar Médico">
                            <i class="bi bi-trash3"></i><span>Eliminar</span>
                        </button>
                        <a href="asignar_ips_medico.php?doc_medico=<?php echo htmlspecialchars($medico['doc_usu']); ?>&nom_medico=<?php echo urlencode($medico['nom_usu']); ?>&return_to=<?php echo urlencode($url_actual_para_retorno); ?>" class="btn btn-primary btn-sm" title="Gestionar Asignación IPS">
                            <i class="bi bi-building-gear"></i><span>IPS</span>
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
        }
    } else {
        echo '<tr><td colspan="7" class="text-center">No se encontraron médicos que coincidan con los filtros.</td></tr>';
    }
    return ob_get_clean();
}

$db = new database();
$con = $db->conectar();
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

$filtro_ips = trim($_GET['filtro_ips'] ?? '');
$filtro_doc = trim($_GET['filtro_doc_medico'] ?? '');
$filtro_estado = trim($_GET['filtro_estado_usuario'] ?? '');

$params = [];
$where_clauses = ["u.id_rol = 4"];

if (empty($filtro_estado)) {
    $where_clauses[] = "u.id_est != 17";
} else {
    $where_clauses[] = "u.id_est = :id_est_filtro";
    $params[':id_est_filtro'] = $filtro_estado;
}

if (!empty($filtro_doc)) {
    $where_clauses[] = "u.doc_usu LIKE :doc_medico_filtro";
    $params[':doc_medico_filtro'] = "%" . $filtro_doc . "%";
}

if (!empty($filtro_ips)) {
    if ($filtro_ips === 'sin_asignacion_activa') {
        $where_clauses[] = "NOT EXISTS (SELECT 1 FROM asignacion_medico am_check WHERE am_check.doc_medico = u.doc_usu AND am_check.id_estado = 1)";
    } elseif ($filtro_ips === 'con_asignacion_inactiva') {
         $where_clauses[] = "EXISTS (SELECT 1 FROM asignacion_medico am_check WHERE am_check.doc_medico = u.doc_usu AND am_check.id_estado = 2)";
    } else {
        $where_clauses[] = "EXISTS (SELECT 1 FROM asignacion_medico am_filt WHERE am_filt.doc_medico = u.doc_usu AND am_filt.nit_ips = :nit_ips AND am_filt.id_estado = 1)";
        $params[':nit_ips'] = $filtro_ips;
    }
}

$sql_from = "FROM usuarios u 
             LEFT JOIN rol r ON u.id_rol = r.id_rol
             LEFT JOIN especialidad esp ON u.id_especialidad = esp.id_espe
             LEFT JOIN estado est ON u.id_est = est.id_est";
$sql_where = "WHERE " . implode(" AND ", $where_clauses);
$sql_count = "SELECT COUNT(DISTINCT u.doc_usu) " . $sql_from . " " . $sql_where;
$stmt_total = $con->prepare($sql_count);
$stmt_total->execute($params);
$total_registros = (int)$stmt_total->fetchColumn();

$total_paginas = $total_registros > 0 ? ceil($total_registros / $registros_por_pagina) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$sql_medicos = "SELECT u.doc_usu, u.id_tipo_doc, u.nom_usu, u.correo_usu, u.id_est AS id_estado_usuario, est.nom_est AS nombre_estado_usuario, esp.nom_espe,
                   (SELECT GROUP_CONCAT(DISTINCT i_sub.nom_ips SEPARATOR ', ') 
                    FROM asignacion_medico am_ips_sub 
                    JOIN ips i_sub ON am_ips_sub.nit_ips = i_sub.nit_ips 
                    WHERE am_ips_sub.doc_medico = u.doc_usu AND am_ips_sub.id_estado = 1) AS nombres_ips_activas
                 " . $sql_from . " " . $sql_where . "
                 GROUP BY u.doc_usu
                 ORDER BY u.nom_usu ASC
                 LIMIT :limit OFFSET :offset";

$stmt = $con->prepare($sql_medicos);
foreach ($params as $key => &$val) { $stmt->bindParam($key, $val); }
$stmt->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$medicos_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['html_body' => render_medicos_body($medicos_list), 'paginacion' => ['actual' => $pagina_actual, 'total' => $total_paginas]]);
?>