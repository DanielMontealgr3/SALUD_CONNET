<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

function render_pacientes_body($pacientes, $filtro_estado) {
    ob_start();
    if (!empty($pacientes)) {
        foreach ($pacientes as $paciente) {
            $return_page = '../gestion_pacientes/lista_pacientes.php';
            $tiene_afiliacion_eps_activa = !empty($paciente['nombre_eps_activa']);
            $es_activo = $paciente['id_estado_usuario'] == 1;
            $es_eliminado = $paciente['id_estado_usuario'] == 17;

            $badge_class = 'bg-secondary';
            if ($es_activo) $badge_class = 'bg-success';
            if ($paciente['id_estado_usuario'] == 2) $badge_class = 'bg-warning text-dark';
            if ($es_eliminado) $badge_class = 'bg-danger';

            ?>
            <tr>
                <td><?php echo htmlspecialchars($paciente['doc_usu']); ?></td>
                <td><span class="truncate-text" title="<?php echo htmlspecialchars($paciente['nom_usu']); ?>"><?php echo htmlspecialchars($paciente['nom_usu']); ?></span></td>
                <td><span class="truncate-text" title="<?php echo htmlspecialchars($paciente['correo_usu']); ?>"><?php echo htmlspecialchars($paciente['correo_usu'] ?? 'N/A'); ?></span></td>
                <td><?php echo $tiene_afiliacion_eps_activa ? htmlspecialchars($paciente['nombre_eps_activa']) : 'Sin EPS Activa'; ?></td>
                <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($paciente['nombre_estado_usuario']); ?></span></td>
                
                <td class="acciones-tabla celda-acciones-fija">
                    <?php if ($es_eliminado): ?>
                        <button class="btn btn-success btn-sm btn-cambiar-estado" data-doc-usu="<?php echo htmlspecialchars($paciente['doc_usu']); ?>" data-nom-usu="<?php echo htmlspecialchars($paciente['nom_usu']); ?>" data-correo-usu="<?php echo htmlspecialchars($paciente['correo_usu']); ?>" data-accion="revertir" title="Revertir Eliminación">
                            <i class="bi bi-arrow-counterclockwise"></i><span>Revertir</span>
                        </button>
                    <?php else: ?>
                        <button class="btn btn-sm btn-cambiar-estado <?php echo $es_activo ? 'btn-warning' : 'btn-success'; ?>" data-doc-usu="<?php echo htmlspecialchars($paciente['doc_usu']); ?>" data-nom-usu="<?php echo htmlspecialchars($paciente['nom_usu']); ?>" data-correo-usu="<?php echo htmlspecialchars($paciente['correo_usu']); ?>" data-accion="<?php echo $es_activo ? 'inactivar' : 'activar'; ?>" title="<?php echo $es_activo ? 'Inactivar' : 'Activar'; ?>">
                            <i class="bi <?php echo $es_activo ? 'bi-person-slash' : 'bi-person-check'; ?>"></i><span><?php echo $es_activo ? 'Inactivar' : 'Activar'; ?></span>
                        </button>
                        <button class="btn btn-info btn-sm btn-editar-usuario" data-doc-usu="<?php echo htmlspecialchars($paciente['doc_usu']); ?>" title="Editar Paciente">
                            <i class="bi bi-pencil-square"></i><span>Editar</span>
                        </button>
                        <button class="btn btn-danger btn-sm btn-eliminar-paciente" data-doc-usu="<?php echo htmlspecialchars($paciente['doc_usu']); ?>" data-id-tipo-doc="<?php echo htmlspecialchars($paciente['id_tipo_doc']); ?>" data-nom-usu="<?php echo htmlspecialchars($paciente['nom_usu']); ?>" title="Eliminar Paciente">
                            <i class="bi bi-trash3"></i><span>Eliminar</span>
                        </button>
                        <a href="../includes/afiliacion.php?doc_usu=<?php echo htmlspecialchars($paciente['doc_usu']); ?>&id_tipo_doc=<?php echo htmlspecialchars($paciente['id_tipo_doc']); ?>&return_to=<?php echo urlencode($return_page); ?>" class="btn btn-primary btn-sm" title="<?php echo $tiene_afiliacion_eps_activa ? 'Gestionar Afiliación' : 'Afiliar a EPS'; ?>">
                            <i class="bi <?php echo $tiene_afiliacion_eps_activa ? 'bi-arrow-repeat' : 'bi-person-plus-fill'; ?>"></i><span>Gestionar EPS</span>
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
        }
    } else {
        echo '<tr><td colspan="6" class="text-center">No se encontraron pacientes que coincidan con los filtros.</td></tr>';
    }
    return ob_get_clean();
}

$db = new database();
$con = $db->conectar();

$registros_por_pagina = 5;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

$filtro_eps = $_GET['filtro_eps'] ?? '';
$filtro_doc = $_GET['filtro_doc_paciente'] ?? '';
$filtro_estado = $_GET['filtro_estado_paciente'] ?? '';

$params = [];
$where_clauses = ["u.id_rol = 2"];

if (empty($filtro_estado)) {
    $where_clauses[] = "u.id_est != 17"; 
} else {
    $where_clauses[] = "u.id_est = :estado";
    $params[':estado'] = $filtro_estado;
}

if (!empty($filtro_doc)) {
    $where_clauses[] = "u.doc_usu LIKE :doc";
    $params[':doc'] = "%" . $filtro_doc . "%";
}

$from_join = "FROM usuarios u
              LEFT JOIN estado est ON u.id_est = est.id_est
              LEFT JOIN (SELECT doc_afiliado, id_eps FROM afiliados WHERE id_estado = 1) af ON u.doc_usu = af.doc_afiliado
              LEFT JOIN eps e ON af.id_eps = e.nit_eps";

if ($filtro_eps === 'sin_afiliacion_activa') {
    $where_clauses[] = "af.id_eps IS NULL";
} elseif (!empty($filtro_eps)) {
    $where_clauses[] = "af.id_eps = :eps";
    $params[':eps'] = $filtro_eps;
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);
$count_sql = "SELECT COUNT(DISTINCT u.doc_usu) " . $from_join . " " . $where_sql;
$stmt_total = $con->prepare($count_sql);
$stmt_total->execute($params);
$total_registros = (int)$stmt_total->fetchColumn();

$total_paginas = $total_registros > 0 ? ceil($total_registros / $registros_por_pagina) : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$pacientes_sql = "SELECT DISTINCT u.doc_usu, u.id_tipo_doc, u.nom_usu, u.correo_usu, u.id_est as id_estado_usuario, est.nom_est as nombre_estado_usuario, e.nombre_eps as nombre_eps_activa "
                 . $from_join . " " . $where_sql . " GROUP BY u.doc_usu ORDER BY u.nom_usu ASC LIMIT :limit OFFSET :offset";

$stmt_pacientes = $con->prepare($pacientes_sql);
foreach ($params as $key => &$val) { $stmt_pacientes->bindParam($key, $val); }
$stmt_pacientes->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
$stmt_pacientes->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt_pacientes->execute();
$pacientes_list = $stmt_pacientes->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['html_body' => render_pacientes_body($pacientes_list, $filtro_estado), 'paginacion' => ['actual' => $pagina_actual, 'total' => $total_paginas, 'total_registros' => $total_registros]]);
?>