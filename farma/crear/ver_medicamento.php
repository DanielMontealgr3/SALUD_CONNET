<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

$pageTitle = "Gestionar Medicamentos";

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}
$nombre_farmacia_asignada = "";
$nit_farmacia_actual = $_SESSION['nit_farmacia_asignada_actual'] ?? '';
if ($nit_farmacia_actual) {
    $stmt_nombre = $con->prepare("SELECT nom_farm FROM farmacias WHERE nit_farm = ?");
    $stmt_nombre->execute([$nit_farmacia_actual]);
    $nombre_farmacia_asignada = $stmt_nombre->fetchColumn();
}

function renderizar_tabla_y_paginacion($con)
{
    $registros_por_pagina = 3;
    $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina_actual < 1) $pagina_actual = 1;

    $searchTerm = $_GET['q'] ?? '';
    $filtro_tipo = $_GET['filtro_tipo'] ?? 'todos';
    $filtro_orden = $_GET['filtro_orden'] ?? 'asc';

    $sql_base = "FROM medicamentos m JOIN tipo_de_medicamento tm ON m.id_tipo_medic = tm.id_tip_medic";
    $sql_where_conditions = [];
    $params = [];

    if (!empty($searchTerm)) {
        $sql_where_conditions[] = "(m.nom_medicamento LIKE :searchTerm1 OR m.codigo_barras LIKE :searchTerm2)";
        $searchValue = "%" . $searchTerm . "%";
        $params[':searchTerm1'] = $searchValue;
        $params[':searchTerm2'] = $searchValue;
    }

    if ($filtro_tipo !== 'todos') {
        $sql_where_conditions[] = "m.id_tipo_medic = :tipo_id";
        $params[':tipo_id'] = $filtro_tipo;
    }
    
    $sql_con_where = $sql_base;
    if (!empty($sql_where_conditions)) {
        $sql_con_where .= " WHERE " . implode(' AND ', $sql_where_conditions);
    }

    $stmt_total = $con->prepare("SELECT COUNT(*) " . $sql_con_where);
    $stmt_total->execute($params);
    $total_registros = (int)$stmt_total->fetchColumn();

    $total_paginas = ceil($total_registros / $registros_por_pagina);
    if ($total_paginas == 0) $total_paginas = 1;
    if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;
    
    $offset = ($pagina_actual - 1) * $registros_por_pagina;

    $order_by = ($filtro_orden === 'desc') ? "m.nom_medicamento DESC" : "m.nom_medicamento ASC";
    $sql_final = "SELECT m.id_medicamento, m.nom_medicamento, tm.nom_tipo_medi, m.descripcion, m.codigo_barras " . $sql_con_where . " ORDER BY " . $order_by . " LIMIT :limit OFFSET :offset";
    
    $stmt = $con->prepare($sql_final);

    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val, PDO::PARAM_STR);
    }
    $stmt->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
?>
    <div class="table-responsive">
        <table class="tabla-admin-mejorada">
            <thead>
                <tr>
                    <th>Nombre</th><th>Tipo</th><th>Descripción</th><th>Código Barras</th><th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-medicamentos-body">
                <?php if (!empty($medicamentos)): foreach ($medicamentos as $medicamento): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($medicamento['nom_medicamento']); ?></td>
                        <td><?php echo htmlspecialchars($medicamento['nom_tipo_medi']); ?></td>
                        <td><?php echo htmlspecialchars(substr($medicamento['descripcion'], 0, 50)) . (strlen($medicamento['descripcion']) > 50 ? '...' : ''); ?></td>
                        <td class="barcode-cell">
                            <?php if (!empty($medicamento['codigo_barras'])): ?>
                                <svg class="barcode" jsbarcode-value="<?php echo htmlspecialchars($medicamento['codigo_barras']); ?>"></svg>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-info btn-sm btn-ver" data-id="<?php echo $medicamento['id_medicamento']; ?>"><i class="bi bi-eye-fill"></i> Ver</button>
                            <button class="btn btn-warning btn-sm btn-editar" data-id="<?php echo $medicamento['id_medicamento']; ?>"><i class="bi bi-pencil-fill"></i> Editar</button>
                            <button class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $medicamento['id_medicamento']; ?>" data-nombre="<?php echo htmlspecialchars($medicamento['nom_medicamento']); ?>"><i class="bi bi-trash-fill"></i> Eliminar</button>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-center p-4">No se encontraron medicamentos.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($total_paginas > 1): ?>
        <nav class="mt-3 paginacion-tabla-container">
            <ul class="pagination pagination-sm">
                <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="#" data-page="<?php echo $pagina_actual - 1; ?>"><i class="bi bi-chevron-left"></i></a></li>
                <li class="page-item active"><span class="page-link"><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></span></li>
                <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>"><a class="page-link" href="#" data-page="<?php echo $pagina_actual + 1; ?>"><i class="bi bi-chevron-right"></i></a></li>
            </ul>
        </nav>
    <?php endif; ?>
<?php
    return ob_get_clean();
}

if (isset($_GET['ajax'])) {
    if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
        $db = new database();
        $con = $db->conectar();
    }
    echo renderizar_tabla_y_paginacion($con);
    exit;
}

$stmt_tipos = $con->prepare("SELECT id_tip_medic, nom_tipo_medi FROM tipo_de_medicamento ORDER BY nom_tipo_medi ASC");
$stmt_tipos->execute();
$tipos_medicamento = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
     <link rel="icon" type="image/png" href="../../img/loguito.png">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
    <style>
        .barcode-cell svg { height: 30px; width: auto; max-width: 150px; display: block; }
        .barcode-detail { width: 100%; max-width: 300px; height: auto; }
        .filtros-tabla-container .form-label { font-size: 0.8rem; margin-bottom: 0.2rem; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="titulo-lista-tabla m-0">Medicamentos de: <strong><?php echo htmlspecialchars($nombre_farmacia_asignada); ?></strong></h3>
                    <a href="crear_medicamento.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle-fill me-2"></i>Crear Nuevo Medicamento</a>
                </div>
                
                <form id="formFiltros" class="mb-4 filtros-tabla-container">
                    <div class="row g-2 align-items-end">
                        <div class="col-md">
                            <label for="searchInput" class="form-label"><i class="bi bi-search"></i> Buscar</label>
                            <input type="search" class="form-control form-control-sm" id="searchInput" name="q" placeholder="Nombre o Código...">
                        </div>
                        <div class="col-md">
                            <label for="filtro_tipo" class="form-label"><i class="bi bi-tag"></i> Tipo</label>
                            <select id="filtro_tipo" name="filtro_tipo" class="form-select form-select-sm">
                                <option value="todos">Todos</option>
                                <?php foreach ($tipos_medicamento as $tipo): ?>
                                    <option value="<?php echo $tipo['id_tip_medic']; ?>"><?php echo htmlspecialchars($tipo['nom_tipo_medi']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md">
                            <label for="filtro_orden" class="form-label"><i class="bi bi-sort-alpha-down"></i> Ordenar</label>
                            <select name="filtro_orden" id="filtro_orden" class="form-select form-select-sm">
                                <option value="asc">A - Z</option>
                                <option value="desc">Z - A</option>
                            </select>
                        </div>
                        <div class="col-md-auto">
                           <button class="btn btn-outline-secondary btn-sm" type="button" id="btnLimpiarFiltros">Limpiar</button>
                        </div>
                    </div>
                </form>

                <div id="contenedor-tabla">
                    <?php echo renderizar_tabla_y_paginacion($con); ?>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="modalDetallesMedicamento" tabindex="-1" aria-labelledby="modalDetallesLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetallesLabel">Detalles del Medicamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="cuerpoModalDetalles">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarMedicamento" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formEditarMedicamento" novalidate>
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditarLabel">Editar Medicamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="cuerpoModalEditar">
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" name="id_medicamento" id="edit-id-medicamento">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarCambios" disabled>Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../include/footer.php'; ?>
    <script src="../includes_farm/JsBarcode.all.min.js"></script>
    <script src="../js/gestion_medicamentos.js?v=<?php echo time(); ?>"></script>
</body>
</html>