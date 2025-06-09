<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once '../include/conexion.php';

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['doc_usu']) || $_SESSION['id_rol'] != 3) {
    header('Location: ../inicio_sesion.php?error=no_permiso');
    exit;
}

$documento_farmaceuta = $_SESSION['doc_usu'];
$pageTitle = "Inventario de Farmacia";

$nombre_farmacia_asignada = "";
$nit_farmacia_actual = "";

if ($documento_farmaceuta && isset($con)) {
    $sql_asignacion = "SELECT f.nom_farm, af.nit_farma
                       FROM asignacion_farmaceuta af
                       JOIN farmacias f ON af.nit_farma = f.nit_farm
                       WHERE af.doc_farma = :doc_farma AND af.id_estado = 1
                       LIMIT 1";
    try {
        $stmt_asignacion = $con->prepare($sql_asignacion);
        $stmt_asignacion->bindParam(':doc_farma', $documento_farmaceuta, PDO::PARAM_STR);
        $stmt_asignacion->execute();
        $fila_asignacion = $stmt_asignacion->fetch(PDO::FETCH_ASSOC);

        if ($fila_asignacion) {
            $nombre_farmacia_asignada = $fila_asignacion['nom_farm'];
            $nit_farmacia_actual = $fila_asignacion['nit_farma'];
        } else {
            die("Acceso denegado: No tiene una farmacia activa asignada.");
        }
    } catch (PDOException $e) { die("Error crítico al verificar la asignación de farmacia."); }
} else {
    die("Error de sistema: No se pudo verificar la sesión del usuario.");
}

$inventario_list = [];
$tipos_medicamento = [];
$medicamentos_agotados = [];
$error_db = '';
$total_registros = 0;
$total_paginas = 1;

$filtro_tipo = trim($_GET['filtro_tipo'] ?? 'todos');
$filtro_nombre = trim($_GET['filtro_nombre'] ?? '');
$filtro_estado_stock = trim($_GET['filtro_stock'] ?? 'todos');
$filtro_orden = trim($_GET['filtro_orden'] ?? 'asc');
$umbral_pocas_unidades = 10;

try {
    $stmt_tipos = $con->query("SELECT id_tip_medic, nom_tipo_medi FROM tipo_de_medicamento ORDER BY nom_tipo_medi ASC");
    $tipos_medicamento = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);
    
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
    if ($filtro_estado_stock !== 'todos') {
        if ($filtro_estado_stock === 'disponible') $sql_where_conditions[] = "i.id_estado = 13";
        elseif ($filtro_estado_stock === 'pocas_unidades') $sql_where_conditions[] = "i.id_estado = 14";
        elseif ($filtro_estado_stock === 'no_disponible') $sql_where_conditions[] = "i.id_estado = 15";
    }

    $sql_from_join = "FROM inventario_farmacia i
                      JOIN medicamentos m ON i.id_medicamento = m.id_medicamento
                      JOIN tipo_de_medicamento tm ON m.id_tipo_medic = tm.id_tip_medic
                      JOIN estado e ON i.id_estado = e.id_est
                      WHERE i.nit_farm = :nit_farma";
    if (!empty($sql_where_conditions)) {
        $sql_from_join .= " AND " . implode(" AND ", $sql_where_conditions);
    }

    $stmt_total = $con->prepare("SELECT COUNT(*) " . $sql_from_join);
    $stmt_total->execute($params);
    $total_registros = (int)$stmt_total->fetchColumn();

    $registros_por_pagina = 4;
    $total_paginas = ceil($total_registros / $registros_por_pagina);
    $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina_actual < 1) $pagina_actual = 1;
    if ($pagina_actual > $total_paginas && $total_paginas > 0) $pagina_actual = $total_paginas;
    $offset = ($pagina_actual - 1) * $registros_por_pagina;

    $order_by = ($filtro_orden === 'desc') ? "m.nom_medicamento DESC" : "m.nom_medicamento ASC";
    $sql_final = "SELECT m.id_medicamento, m.nom_medicamento, tm.nom_tipo_medi, m.codigo_barras, i.cantidad_actual, e.nom_est, e.id_est " . $sql_from_join . " ORDER BY " . $order_by . " LIMIT :limit OFFSET :offset_val";
    
    $stmt_inventario = $con->prepare($sql_final);
    foreach ($params as $key => &$val) $stmt_inventario->bindParam($key, $val);
    $stmt_inventario->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt_inventario->bindParam(':offset_val', $offset, PDO::PARAM_INT);
    $stmt_inventario->execute();
    $inventario_list = $stmt_inventario->fetchAll(PDO::FETCH_ASSOC);

    $stmt_agotados = $con->prepare("SELECT m.nom_medicamento, i.cantidad_actual FROM inventario_farmacia i JOIN medicamentos m ON i.id_medicamento = m.id_medicamento WHERE i.nit_farm = :nit_farma AND i.cantidad_actual <= :umbral ORDER BY i.cantidad_actual ASC, m.nom_medicamento ASC");
    $stmt_agotados->execute([':nit_farma' => $nit_farmacia_actual, ':umbral' => $umbral_pocas_unidades]);
    $medicamentos_agotados = $stmt_agotados->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { $error_db = "Error al consultar la base de datos: " . $e->getMessage(); }

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="../img/loguito.png">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
    <link rel="stylesheet" href="../css/estilos.css?v=<?php echo time(); ?>">
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="titulo-lista-tabla m-0">Inventario de: <strong><?php echo htmlspecialchars($nombre_farmacia_asignada); ?></strong></h3>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-dark btn-sm" title="Escanear Código de Barras"><i class="bi bi-upc-scan"></i></button>
                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgotados"><i class="bi bi-exclamation-triangle-fill"></i> Ver Stock Crítico (<?php echo count($medicamentos_agotados); ?>)</button>
                        <button class="btn btn-success btn-sm"><i class="bi bi-file-earmark-excel-fill"></i> Generar Reporte</button>
                    </div>
                </div>
                
                <?php if (!empty($error_db)) : ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_db); ?></div><?php endif; ?>

                <form method="GET" action="inventario.php" class="mb-4 filtros-tabla-container">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg-3 col-md-6">
                            <label for="filtro_tipo" class="form-label">Tipo Medicamento:</label>
                            <select id="filtro_tipo" name="filtro_tipo" class="form-select form-select-sm">
                                <option value="todos">Todos</option>
                                <?php foreach($tipos_medicamento as $tipo): ?>
                                    <option value="<?php echo $tipo['id_tip_medic']; ?>" <?php echo ($filtro_tipo == $tipo['id_tip_medic']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipo['nom_tipo_medi']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label for="filtro_stock" class="form-label">Estado Stock:</label>
                            <select name="filtro_stock" id="filtro_stock" class="form-select form-select-sm">
                                <option value="todos" <?php echo ($filtro_estado_stock == 'todos') ? 'selected' : ''; ?>>Todos</option>
                                <option value="disponible" <?php echo ($filtro_estado_stock == 'disponible') ? 'selected' : ''; ?>>Disponible</option>
                                <option value="pocas_unidades" <?php echo ($filtro_estado_stock == 'pocas_unidades') ? 'selected' : ''; ?>>Pocas Unidades</option>
                                <option value="no_disponible" <?php echo ($filtro_estado_stock == 'no_disponible') ? 'selected' : ''; ?>>No Disponible</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label for="filtro_orden" class="form-label">Ordenar por Nombre:</label>
                            <select name="filtro_orden" id="filtro_orden" class="form-select form-select-sm">
                                <option value="asc" <?php echo ($filtro_orden === 'asc') ? 'selected' : ''; ?>>A - Z</option>
                                <option value="desc" <?php echo ($filtro_orden === 'desc') ? 'selected' : ''; ?>>Z - A</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label for="filtro_nombre" class="form-label">Buscar por Nombre:</label>
                            <input type="text" name="filtro_nombre" id="filtro_nombre" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtro_nombre); ?>" placeholder="Nombre del medicamento...">
                        </div>
                        <div class="col-lg-1 col-md-6"><button type="submit" class="btn btn-sm btn-filtrar-tabla w-100">Filtrar</button></div>
                        <div class="col-lg-1 col-md-6"><a href="inventario.php" class="btn btn-sm btn-outline-secondary w-100">Limpiar</a></div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="tabla-admin-mejorada">
                        <thead>
                            <tr>
                                <th>Medicamento</th>
                                <th>Tipo</th>
                                <th>Código de Barras</th>
                                <th>Cantidad Actual</th>
                                <th>Estado</th>
                                <th class="columna-acciones-fija">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($inventario_list)) : ?>
                                <?php foreach ($inventario_list as $item) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['nom_medicamento']); ?></td>
                                        <td><?php echo htmlspecialchars($item['nom_tipo_medi']); ?></td>
                                        <td><?php echo htmlspecialchars($item['codigo_barras'] ?: 'N/A'); ?></td>
                                        <td><strong><?php echo htmlspecialchars($item['cantidad_actual']); ?></strong></td>
                                        <td>
                                            <?php 
                                                $clase_badge = 'bg-secondary';
                                                if ($item['id_est'] == 13) $clase_badge = 'bg-success';
                                                if ($item['id_est'] == 14) $clase_badge = 'bg-warning text-dark';
                                                if ($item['id_est'] == 15) $clase_badge = 'bg-danger';
                                            ?>
                                            <span class="badge <?php echo $clase_badge; ?>"><?php echo htmlspecialchars($item['nom_est']); ?></span>
                                        </td>
                                        <td class="acciones-tabla">
                                            <button class="btn btn-info btn-sm btn-ver-detalles" data-bs-toggle="modal" data-bs-target="#modalDetallesMedicamento" data-id-medicamento="<?php echo $item['id_medicamento']; ?>" title="Ver Detalles Completos">
                                                <i class="bi bi-info-circle-fill"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                 <tr><td colspan="6" class="text-center p-4">No hay medicamentos que coincidan con los filtros aplicados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_registros > 0 && $total_paginas > 1): ?>
                <nav aria-label="Paginación de inventario" class="mt-3 paginacion-tabla-container">
                    <ul class="pagination pagination-sm">
                        <?php
                            $query_params = http_build_query(array_filter([
                                'filtro_tipo' => $filtro_tipo,
                                'filtro_stock' => $filtro_estado_stock,
                                'filtro_orden' => $filtro_orden,
                                'filtro_nombre' => $filtro_nombre
                            ]));
                        ?>
                        <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>&<?php echo $query_params; ?>"><i class="bi bi-chevron-left"></i></a></li>
                        <li class="page-item active" id="page-number-container">
                           <span class="page-link page-number-display" title="Click/Enter para ir a pág."><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></span>
                           <input type="number" class="form-control form-control-sm page-number-input-field" value="<?php echo $pagina_actual; ?>" min="1" max="<?php echo $total_paginas; ?>" style="display: none;" data-total="<?php echo $total_paginas; ?>">
                        </li>
                        <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>"><a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>&<?php echo $query_params; ?>"><i class="bi bi-chevron-right"></i></a></li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div class="modal fade" id="modalAgotados" tabindex="-1" aria-labelledby="modalAgotadosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgotadosLabel"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Reporte de Stock Crítico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Lista de medicamentos agotados o con pocas unidades (<?php echo $umbral_pocas_unidades; ?> o menos).</p>
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Medicamento</th>
                                <th>Cantidad Restante</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($medicamentos_agotados)): ?>
                                <?php foreach($medicamentos_agotados as $med_agotado): ?>
                                    <tr class="<?php echo ($med_agotado['cantidad_actual'] == 0) ? 'table-danger' : 'table-warning'; ?>">
                                        <td><?php echo htmlspecialchars($med_agotado['nom_medicamento']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($med_agotado['cantidad_actual']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="2" class="text-center p-3">¡Excelente! No hay medicamentos con stock crítico.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="modalDetallesMedicamento" tabindex="-1" aria-labelledby="modalDetallesLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalDetallesLabel"><i class="bi bi-capsule-pill me-2"></i>Detalles del Medicamento</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="contenidoModalDetalles">
            <div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <?php include '../include/footer.php'; ?>

    <script>
    const umbralPocasUnidades = <?php echo $umbral_pocas_unidades; ?>;

    document.addEventListener('DOMContentLoaded', function () {
        const modalDetallesElement = document.getElementById('modalDetallesMedicamento');
        if (modalDetallesElement) {
            const contenidoModal = document.getElementById('contenidoModalDetalles');
            const tituloModal = document.getElementById('modalDetallesLabel');

            modalDetallesElement.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const idMedicamento = button.getAttribute('data-id-medicamento');
                
                tituloModal.innerHTML = 'Cargando detalles...';
                contenidoModal.innerHTML = `<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>`;

                fetch(`detalles_medicamento.php?id=${idMedicamento}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const med = data.medicamento;
                            
                            tituloModal.innerHTML = `<i class="bi bi-capsule-pill me-2"></i> ${med.nom_medicamento}`;
                            
                            let claseBadge = 'bg-secondary';
                            if (med.id_est == 13) claseBadge = 'bg-success';
                            if (med.id_est == 14) claseBadge = 'bg-warning text-dark';
                            if (med.id_est == 15) claseBadge = 'bg-danger';

                            let alertaStockBajoHTML = '';
                            if (med.cantidad_actual !== null && med.cantidad_actual <= umbralPocasUnidades && med.cantidad_actual > 0) {
                                alertaStockBajoHTML = `<div class="alert alert-warning d-flex align-items-center mb-4" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i><div><strong>¡Atención!</strong> Este medicamento tiene pocas unidades en stock.</div></div>`;
                            } else if (med.cantidad_actual === "0") {
                                alertaStockBajoHTML = `<div class="alert alert-danger d-flex align-items-center mb-4" role="alert"><i class="bi bi-x-octagon-fill me-2"></i><div><strong>¡Agotado!</strong> Este medicamento no tiene unidades disponibles.</div></div>`;
                            }

                            contenidoModal.innerHTML = `
                                ${alertaStockBajoHTML} 
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <div class="form-card">
                                            <h6 class="card-subtitle mb-2 text-muted">Descripción General</h6>
                                            <p class="card-text" style="white-space: pre-wrap;">${med.descripcion || 'No disponible.'}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-card">
                                            <h6 class="card-subtitle mb-3 text-muted">Información de Inventario</h6>
                                            <ul class="list-group list-group-flush">
                                                <li class="list-group-item d-flex justify-content-between align-items-center">Tipo: <strong>${med.nom_tipo_medi}</strong></li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">Código de Barras: <strong>${med.codigo_barras || 'N/A'}</strong></li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">Cantidad Actual: <strong class="fs-5">${med.cantidad_actual !== null ? med.cantidad_actual : 'N/A'}</strong></li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">Estado: <span class="badge ${claseBadge} fs-6">${med.nom_est || 'No en inventario'}</span></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>`;
                        } else {
                            tituloModal.innerHTML = 'Error';
                            contenidoModal.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                        }
                    })
                    .catch(error => {
                        tituloModal.innerHTML = 'Error de Conexión';
                        contenidoModal.innerHTML = `<div class="alert alert-danger">No se pudieron cargar los detalles del medicamento.</div>`;
                    });
            });
        }

        const pageContainer = document.getElementById('page-number-container');
        if (pageContainer) {
            const pageIS = pageContainer.querySelector('.page-number-display');
            const pageIF = pageContainer.querySelector('.page-number-input-field');
            if (pageIS && pageIF) {
                pageIS.addEventListener('click', () => { pageIS.style.display = 'none'; pageIF.style.display = 'inline-block'; pageIF.focus(); pageIF.select(); });
                const goPg = () => {
                    const tp = parseInt(pageIF.dataset.total, 10) || 1;
                    let tgPg = parseInt(pageIF.value, 10);
                    if (isNaN(tgPg) || tgPg < 1) tgPg = 1; else if (tgPg > tp) tgPg = tp;
                    const curl = new URL(window.location.href);
                    curl.searchParams.set('pagina', tgPg);
                    window.location.href = curl.toString();
                };
                pageIF.addEventListener('blur', () => { setTimeout(() => { pageIS.style.display = 'inline-block'; pageIF.style.display = 'none'; }, 150); });
                pageIF.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') { e.preventDefault(); goPg(); }
                    else if (e.key === 'Escape') { pageIF.blur(); }
                });
            }
        }
    });
    </script>
</body>
</html>