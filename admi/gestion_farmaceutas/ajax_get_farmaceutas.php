<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

function render_farmaceutas_body($farmaceutas) {
    ob_start();
    if (!empty($farmaceutas)) {
        foreach ($farmaceutas as $farmaceuta) {
            $url_actual_para_retorno = 'lista_farmaceutas.php' . '?' . http_build_query($_GET);
            $es_activo = $farmaceuta['id_estado_usuario'] == 1;
            $es_eliminado = $farmaceuta['id_estado_usuario'] == 17;

            $badge_class = 'bg-secondary';
            if ($es_activo) $badge_class = 'bg-success';
            if ($farmaceuta['id_estado_usuario'] == 2) $badge_class = 'bg-warning text-dark';
            if ($es_eliminado) $badge_class = 'bg-danger';
            ?>
            <tr>
                <td><?php echo htmlspecialchars($farmaceuta['doc_usu']); ?></td>
                <td><span class="truncate-text" title="<?php echo htmlspecialchars($farmaceuta['nom_usu']); ?>"><?php echo htmlspecialchars($farmaceuta['nom_usu']); ?></span></td>
                <td><span class="truncate-text" title="<?php echo htmlspecialchars($farmaceuta['correo_usu']); ?>"><?php echo htmlspecialchars($farmaceuta['correo_usu'] ?? 'N/A'); ?></span></td>
                <td><?php echo htmlspecialchars($farmaceuta['nombres_farmacias_activas'] ?: 'Ninguna'); ?></td>
                <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($farmaceuta['nombre_estado_usuario']); ?></span></td>
                <td class="acciones-tabla celda-acciones-fija">
                    <?php if ($es_eliminado): ?>
                        <button class="btn btn-success btn-sm btn-cambiar-estado" data-doc-usu="<?php echo htmlspecialchars($farmaceuta['doc_usu']); ?>" data-nom-usu="<?php echo htmlspecialchars($farmaceuta['nom_usu']); ?>" data-accion="revertir" title="Revertir Eliminación">
                            <i class="bi bi-arrow-counterclockwise"></i><span>Revertir</span>
                        </button>
                    <?php else: ?>
                        <button class="btn btn-sm btn-cambiar-estado <?php echo $es_activo ? 'btn-warning' : 'btn-success'; ?>" data-doc-usu="<?php echo htmlspecialchars($farmaceuta['doc_usu']); ?>" data-nom-usu="<?php echo htmlspecialchars($farmaceuta['nom_usu']); ?>" data-accion="<?php echo $es_activo ? 'inactivar' : 'activar'; ?>" title="<?php echo $es_activo ? 'Inactivar' : 'Activar'; ?>">
                            <i class="bi <?php echo $es_activo ? 'bi-person-slash' : 'bi-person-check'; ?>"></i><span><?php echo $es_activo ? 'Inactivar' : 'Activar'; ?></span>
                        </button>
                        <button class="btn btn-info btn-sm btn-editar-usuario" data-doc-usu="<?php echo htmlspecialchars($farmaceuta['doc_usu']); ?>" title="Editar Farmaceuta">
                            <i class="bi bi-pencil-square"></i><span>Editar</span>
                        </button>
                        <button class="btn btn-danger btn-sm btn-eliminar-farmaceuta" data-doc-usu="<?php echo htmlspecialchars($farmaceuta['doc_usu']); ?>" data-nom-usu="<?php echo htmlspecialchars($farmaceuta['nom_usu']); ?>" data-id-tipo-doc="<?php echo htmlspecialchars($farmaceuta['id_tipo_doc']); ?>" data-asignado-farmacia="<?php echo htmlspecialchars($farmaceuta['asignado_farmacia_activo_count'] > 0 ? '1' : '0'); ?>" title="Eliminar Farmaceuta">
                            <i class="bi bi-trash3"></i><span>Eliminar</span>
                        </button>
                        <a href="asignar_farmaceuta.php?doc_farma=<?php echo htmlspecialchars($farmaceuta['doc_usu']); ?>&nom_farma=<?php echo urlencode($farmaceuta['nom_usu']); ?>&return_to=<?php echo urlencode($url_actual_para_retorno); ?>" class="btn btn-primary btn-sm" title="Gestionar Asignación Farmacia">
                            <i class="bi bi-shop-window"></i><span>Farmacia</span>
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
        }
    } else {
        echo '<tr><td colspan="6" class="text-center">No se encontraron farmaceutas que coincidan con los filtros.</td></tr>';
    }
    return ob_get_clean();
}

$db = new database();
$con = $db->conectar();
$registros_por_pagina = 5;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

$filtro_farmacia = trim($_GET['filtro_farmacia'] ?? '');
$filtro_doc = trim($_GET['filtro_doc_farmaceuta'] ?? '');
$filtro_estado = trim($_GET['filtro_estado_usuario'] ?? '');

$params = [];
$where_clauses = ["u.id_rol = 3"];

if (empty($filtro_estado)) {
    $where_clauses[] = "u.id_est != 17";
} else {
    $where_clauses[] = "u.id_est = :id_est_filtro";
    $params[':id_est_filtro'] = $filtro_estado;
}

if (!empty($filtro_doc)) {
    $where_clauses[] = "u.doc_usu LIKE :doc_farmaceuta_filtro";
    $params[':doc_farmaceuta_filtro'] = "%" . $filtro_doc . "%";
}

if (!empty($filtro_farmacia)) {
    if ($filtro_farmacia === 'sin_asignacion_activa') {
        $where_clauses[] = "NOT EXISTS (SELECT 1 FROM asignacion_farmaceuta af_check WHERE af_check.doc_farma = u.doc_usu AND af_check.id_estado = 1)";
    } elseif ($filtro_farmacia === 'con_asignacion_inactiva') {
        $where_clauses[] = "EXISTS (SELECT 1 FROM asignacion_farmaceuta af_check WHERE af_check.doc_farma = u.doc_usu AND af_check.id_estado = 2)";
    } else {
        $where_clauses[] = "EXISTS (SELECT 1 FROM asignacion_farmaceuta af_filt WHERE af_filt.doc_farma = u.doc_usu AND af_filt.nit_farma = :nit_farma AND af_filt.id_estado = 1)";
        $params[':nit_farma'] = $filtro_farmacia;
    }
}

$sql_from = "FROM usuarios u LEFT JOIN estado est ON u.id_est = est.id_est";
$sql_where = "WHERE " . implode(" AND ", $where_clauses);
$sql_count = "SELECT COUNT(DISTINCT u.doc_usu) " . $sql_from . " " . $sql_where;
$stmt_total = $con->prepare($sql_count);
$stmt_total->execute($params);
$total_registros = (int)$stmt_total->fetchColumn();

$total_paginas = $total_registros > 0 ? ceil($total_registros / $registros_por_pagina) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$sql_farmaceutas = "SELECT u.doc_usu, u.id_tipo_doc, u.nom_usu, u.correo_usu, u.id_est AS id_estado_usuario, est.nom_est AS nombre_estado_usuario,
                       (SELECT COUNT(*) FROM asignacion_farmaceuta af_check WHERE af_check.doc_farma = u.doc_usu AND af_check.id_estado = 1) AS asignado_farmacia_activo_count,
                       (SELECT GROUP_CONCAT(DISTINCT farm_sub.nom_farm SEPARATOR ', ') 
                        FROM asignacion_farmaceuta af_farm_sub 
                        JOIN farmacias farm_sub ON af_farm_sub.nit_farma = farm_sub.nit_farm 
                        WHERE af_farm_sub.doc_farma = u.doc_usu AND af_farm_sub.id_estado = 1) AS nombres_farmacias_activas
                 " . $sql_from . " " . $sql_where . "
                 GROUP BY u.doc_usu
                 ORDER BY u.nom_usu ASC
                 LIMIT :limit OFFSET :offset";

$stmt = $con->prepare($sql_farmaceutas);
foreach ($params as $key => &$val) { $stmt->bindParam($key, $val); }
$stmt->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$farmaceutas_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['html_body' => render_farmaceutas_body($farmaceutas_list), 'paginacion' => ['actual' => $pagina_actual, 'total' => $total_paginas, 'total_registros' => $total_registros]]);
?>