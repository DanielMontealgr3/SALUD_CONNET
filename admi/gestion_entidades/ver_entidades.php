<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once('../../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) { header('Location: ../inicio_sesion.php'); exit; }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf_token = $_SESSION['csrf_token'];

$conex = new database(); $con = $conex->conectar();
$entidad_seleccionada = trim($_GET['tipo_entidad'] ?? 'todas'); 
$filtro_nombre_entidad = trim($_GET['filtro_nombre'] ?? '');
$datos_entidad = []; $columnas_tabla = []; $error_db = ''; $titulo_tabla = 'Entidades';
$config_final = [];

$total_registros = 0; 
$registros_por_pagina = 4;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$total_paginas = 1;

if ($con) {
    try {
        if ($entidad_seleccionada === 'todas') {
            $titulo_tabla = 'Todas las Entidades'; 
            $columnas_tabla = ['NIT', 'Nombre', 'Tipo', 'Teléfono', 'Correo', 'Acciones'];
            $params_union = [];
            $where_clauses_union = ['farmacias' => '', 'eps' => '', 'ips' => ''];

            if (!empty($filtro_nombre_entidad)) {
                $filtro_valor = "%" . $filtro_nombre_entidad . "%";
                $where_clauses_union['farmacias'] = "WHERE nom_farm LIKE :nombre_filtro_farm";
                $where_clauses_union['eps'] = "WHERE nombre_eps LIKE :nombre_filtro_eps";
                $where_clauses_union['ips'] = "WHERE nom_IPS LIKE :nombre_filtro_ips";
                $params_union = [
                    ':nombre_filtro_farm' => $filtro_valor,
                    ':nombre_filtro_eps' => $filtro_valor,
                    ':nombre_filtro_ips' => $filtro_valor,
                ];
            }

            $sql_farmacias = "(SELECT nit_farm as id, nom_farm as nombre, 'Farmacia' as tipo_display, tel_farm as telefono, correo_farm as correo, 'farmacias' as tipo_key FROM farmacias " . $where_clauses_union['farmacias'] . ")";
            $sql_eps = "(SELECT nit_eps as id, nombre_eps as nombre, 'EPS' as tipo_display, telefono, correo, 'eps' as tipo_key FROM eps " . $where_clauses_union['eps'] . ")";
            $sql_ips = "(SELECT Nit_IPS as id, nom_IPS as nombre, 'IPS' as tipo_display, tel_IPS as telefono, correo_IPS as correo, 'ips' as tipo_key FROM ips " . $where_clauses_union['ips'] . ")";
            
            $count_sql = "SELECT SUM(count) as total FROM (
                SELECT COUNT(*) as count FROM farmacias " . $where_clauses_union['farmacias'] . " UNION ALL 
                SELECT COUNT(*) as count FROM eps " . $where_clauses_union['eps'] . " UNION ALL 
                SELECT COUNT(*) as count FROM ips " . $where_clauses_union['ips'] . "
            ) AS counts";
            
            $stmt_total = $con->prepare($count_sql);
            $stmt_total->execute($params_union);
            $total_registros = (int)$stmt_total->fetchColumn();

            if ($total_registros > 0) {
                $total_paginas = ceil($total_registros / $registros_por_pagina); if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas; if($total_paginas == 0) $total_paginas = 1; $offset = ($pagina_actual - 1) * $registros_por_pagina;
                $sql_union = "$sql_farmacias UNION ALL $sql_eps UNION ALL $sql_ips ORDER BY nombre ASC LIMIT :limit OFFSET :offset_val";
                $stmt = $con->prepare($sql_union);
                if (!empty($params_union)) { 
                    foreach($params_union as $key => &$val) { 
                        $stmt->bindParam($key, $val, PDO::PARAM_STR); 
                    }
                    unset($val);
                }
                $stmt->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
                $stmt->bindParam(':offset_val', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $datos_entidad = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else { $datos_entidad = []; $total_paginas = 1; }
        } else {
            $config = []; $params_individual = [];
            if ($entidad_seleccionada === 'farmacias') { 
                $config = ['tabla' => 'farmacias', 'pk' => 'nit_farm', 'nombre_col' => 'nom_farm', 'select_all' => '*', 'tipo_key' => 'farmacias', 'tipo_display' => 'Farmacia']; 
                $titulo_tabla = 'Farmacias'; 
                $columnas_tabla = ['NIT', 'Nombre', 'Dirección', 'Gerente', 'Teléfono', 'Correo', 'Acciones'];
            } elseif ($entidad_seleccionada === 'eps') { 
                $config = ['tabla' => 'eps', 'pk' => 'nit_eps', 'nombre_col' => 'nombre_eps', 'select_all' => '*', 'tipo_key' => 'eps', 'tipo_display' => 'EPS']; 
                $titulo_tabla = 'EPS'; 
                $columnas_tabla = ['NIT', 'Nombre', 'Dirección', 'Gerente', 'Teléfono', 'Correo', 'Acciones'];
            } elseif ($entidad_seleccionada === 'ips') { 
                $config = ['tabla' => 'ips', 'pk' => 'Nit_IPS', 'nombre_col' => 'nom_IPS', 'select_all' => 'i.*, m.nom_mun as nombre_municipio, d.nom_dep as nombre_departamento', 'from_join' => 'ips i LEFT JOIN municipio m ON i.ubicacion_mun = m.id_mun LEFT JOIN departamento d ON m.id_dep = d.id_dep', 'tipo_key' => 'ips', 'tipo_display' => 'IPS']; 
                $titulo_tabla = 'IPS'; 
                $columnas_tabla = ['NIT', 'Nombre', 'Dirección', 'Gerente', 'Teléfono', 'Correo', 'Municipio', 'Departamento', 'Acciones'];
            }
            $config_final = $config;

            if (!empty($config)) {
                 $from_clause = $config['from_join'] ?? $config['tabla'];
                 $where_individual = "";
                 if(!empty($filtro_nombre_entidad)){
                    $where_individual = " WHERE {$config['nombre_col']} LIKE :nombre_filtro ";
                    $params_individual[':nombre_filtro'] = "%".$filtro_nombre_entidad."%";
                 }
                 $stmt_total = $con->prepare("SELECT COUNT(*) FROM " . $from_clause . $where_individual);
                 $stmt_total->execute($params_individual);
                 $total_registros = (int)$stmt_total->fetchColumn();

                if ($total_registros > 0) {
                    $total_paginas = ceil($total_registros / $registros_por_pagina); if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas; if($total_paginas == 0) $total_paginas = 1; $offset = ($pagina_actual - 1) * $registros_por_pagina;
                    $sql_paginado = "SELECT {$config['select_all']} FROM {$from_clause} {$where_individual} ORDER BY {$config['nombre_col']} ASC LIMIT :limit OFFSET :offset_val";
                    
                    $stmt_select = $con->prepare($sql_paginado);
                    foreach($params_individual as $key => $val){ $stmt_select->bindValue($key, $val); }
                    $stmt_select->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
                    $stmt_select->bindParam(':offset_val', $offset, PDO::PARAM_INT);
                    $stmt_select->execute();
                    $datos_entidad = $stmt_select->fetchAll(PDO::FETCH_ASSOC);
                    foreach($datos_entidad as &$row){
                        $row['tipo_key'] = $config['tipo_key'];
                        $row['tipo_display'] = $config['tipo_display'];
                        $row['id'] = $row[$config['pk']];
                    }
                    unset($row);
                } else { $datos_entidad = []; $total_paginas = 1; }
            }
        }
    } catch (PDOException $e) { $error_db = "Error al consultar BD: " . $e->getMessage(); error_log("PDO Ver Ent: ".$e->getMessage()); $datos_entidad = []; }
} else { $error_db = "Error de conexión."; }

function render_table_header($columnas) {
    ob_start();
    foreach ($columnas as $columna) {
        if ($columna === 'Acciones') {
            echo '<th class="columna-acciones-fija">' . htmlspecialchars($columna) . '</th>';
        } else {
            echo '<th>' . htmlspecialchars($columna) . '</th>';
        }
    }
    return ob_get_clean();
}

function render_table_body($datos_entidad, $entidad_seleccionada, $config, $total_columnas) {
    ob_start();
    if (!empty($datos_entidad)) {
        foreach ($datos_entidad as $entidad) { ?>
            <tr>
                <?php if ($entidad_seleccionada === 'todas'): ?>
                    <td><?php echo htmlspecialchars($entidad['id'] ?? 'N/A'); ?></td>
                    <td><span class="truncate-text" title="<?php echo htmlspecialchars($entidad['nombre'] ?? ''); ?>"><?php echo htmlspecialchars($entidad['nombre'] ?? 'N/A'); ?></span></td>
                    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($entidad['tipo_display'] ?? 'N/A'); ?></span></td>
                    <td><?php echo htmlspecialchars($entidad['telefono'] ?? 'N/A'); ?></td>
                    <td><span class="truncate-text" title="<?php echo htmlspecialchars($entidad['correo'] ?? ''); ?>"><?php echo htmlspecialchars($entidad['correo'] ?? 'N/A'); ?></span></td>
                <?php elseif ($entidad_seleccionada === 'farmacias'): ?>
                    <td><?php echo htmlspecialchars($entidad['nit_farm'] ?? 'N/A'); ?></td>
                    <td><span class="truncate-text" title="<?php echo htmlspecialchars($entidad['nom_farm'] ?? ''); ?>"><?php echo htmlspecialchars($entidad['nom_farm'] ?? 'N/A'); ?></span></td>
                    <td><?php echo htmlspecialchars($entidad['direc_farm'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($entidad['nom_gerente'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($entidad['tel_farm'] ?? 'N/A'); ?></td>
                    <td><span class="truncate-text" title="<?php echo htmlspecialchars($entidad['correo_farm'] ?? ''); ?>"><?php echo htmlspecialchars($entidad['correo_farm'] ?? 'N/A'); ?></span></td>
                <?php elseif ($entidad_seleccionada === 'eps'): ?>
                    <td><?php echo htmlspecialchars($entidad['nit_eps'] ?? 'N/A'); ?></td>
                    <td><span class="truncate-text" title="<?php echo htmlspecialchars($entidad['nombre_eps'] ?? ''); ?>"><?php echo htmlspecialchars($entidad['nombre_eps'] ?? 'N/A'); ?></span></td>
                    <td><?php echo htmlspecialchars($entidad['direc_eps'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($entidad['nom_gerente'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($entidad['telefono'] ?? 'N/A'); ?></td>
                    <td><span class="truncate-text" title="<?php echo htmlspecialchars($entidad['correo'] ?? ''); ?>"><?php echo htmlspecialchars($entidad['correo'] ?? 'N/A'); ?></span></td>
                <?php elseif ($entidad_seleccionada === 'ips'): ?>
                    <td><?php echo htmlspecialchars($entidad['Nit_IPS'] ?? 'N/A'); ?></td>
                    <td><span class="truncate-text" title="<?php echo htmlspecialchars($entidad['nom_IPS'] ?? ''); ?>"><?php echo htmlspecialchars($entidad['nom_IPS'] ?? 'N/A'); ?></span></td>
                    <td><?php echo htmlspecialchars($entidad['direc_IPS'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($entidad['nom_gerente'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($entidad['tel_IPS'] ?? 'N/A'); ?></td>
                    <td><span class="truncate-text" title="<?php echo htmlspecialchars($entidad['correo_IPS'] ?? ''); ?>"><?php echo htmlspecialchars($entidad['correo_IPS'] ?? 'N/A'); ?></span></td>
                    <td><?php echo htmlspecialchars($entidad['nombre_municipio'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($entidad['nombre_departamento'] ?? 'N/A'); ?></td>
                <?php endif; ?>
                <td class="acciones-tabla celda-acciones-fija">
                    <button class="btn btn-info btn-sm btn-ver-detalles" data-id="<?php echo htmlspecialchars($entidad['id']); ?>" data-tipo="<?php echo htmlspecialchars($entidad['tipo_key']); ?>" title="Ver Detalles"><i class="bi bi-eye-fill"></i> <span>Ver</span></button>
                    <button class="btn btn-success btn-sm btn-editar-entidad" data-id="<?php echo htmlspecialchars($entidad['id']); ?>" data-tipo="<?php echo htmlspecialchars($entidad['tipo_key']); ?>" title="Editar"><i class="bi bi-pencil-square"></i> <span>Editar</span></button>
                    <button class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo htmlspecialchars($entidad['id']); ?>" data-nombre="<?php echo htmlspecialchars($entidad_seleccionada === 'todas' ? ($entidad['nombre'] ?? '') : ($entidad[$config['nombre_col']] ?? '')); ?>" data-tipo="<?php echo htmlspecialchars($entidad['tipo_key']); ?>" title="Eliminar"><i class="bi bi-trash3"></i> <span>Eliminar</span></button>
                </td>
            </tr>
        <?php }
    } else { ?>
        <tr><td colspan="<?php echo $total_columnas; ?>" class="text-center">No se encontraron entidades.</td></tr>
    <?php }
    return ob_get_clean();
}

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    echo json_encode([
        'html_header' => render_table_header($columnas_tabla),
        'html_body' => render_table_body($datos_entidad, $entidad_seleccionada, $config_final, count($columnas_tabla)),
        'titulo_tabla' => $titulo_tabla,
        'paginacion' => [
            'pagina_actual' => $pagina_actual,
            'total_paginas' => $total_paginas
        ]
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../img/loguito.png">
    <title>Ver Entidades - Administración</title>
    <style>
        .columna-acciones-fija { width: 220px; min-width: 220px; text-align: center; vertical-align: middle; }
        .celda-acciones-fija { justify-content: center !important; align-items: center; gap: 0.5rem; }
        #loaderBusqueda { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); }
    </style>
</head>
<body class="d-flex flex-column">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 id="titulo-de-tabla" class="titulo-lista-tabla m-0 border-0 p-0"><?php echo htmlspecialchars($titulo_tabla); ?></h3>
                    <a href="crear_entidad.php" class="btn btn-success btn-sm flex-shrink-0">
                        <i class="bi bi-plus-circle-fill"></i> Nueva Entidad
                    </a>
                </div>
                
                <div class="filtros-tabla-container mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="tipo_entidad_select" class="form-label"><i class="bi bi-filter-circle"></i> Filtrar por Tipo:</label>
                            <select id="tipo_entidad_select" name="tipo_entidad" class="form-select form-select-sm">
                                <option value="todas" <?php echo ($entidad_seleccionada == 'todas') ? 'selected' : ''; ?>>-- Todas las Entidades --</option>
                                <option value="farmacias" <?php echo ($entidad_seleccionada == 'farmacias') ? 'selected' : ''; ?>>Farmacias</option>
                                <option value="eps" <?php echo ($entidad_seleccionada == 'eps') ? 'selected' : ''; ?>>EPS</option>
                                <option value="ips" <?php echo ($entidad_seleccionada == 'ips') ? 'selected' : ''; ?>>IPS</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="filtro_nombre" class="form-label"><i class="bi bi-search"></i> Buscar por Nombre:</label>
                            <div class="input-group input-group-sm position-relative">
                                <input type="text" name="filtro_nombre" id="filtro_nombre" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtro_nombre_entidad); ?>" placeholder="Escriba para buscar...">
                                <div id="loaderBusqueda" class="spinner-border spinner-border-sm text-primary" role="status" style="display: none;"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2 justify-content-end">
                                <button type="button" id="btnLimpiarFiltros" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eraser"></i> Limpiar
                                </button>
                                <button id="btnGenerarReporte" type="button" class="btn btn-sm btn-success" <?php if ($total_registros === 0) echo 'disabled'; ?>>
                                    <i class="bi bi-file-earmark-excel-fill"></i> Reporte
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($error_db)) : ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_db); ?></div><?php endif; ?>

                <div class="table-responsive">
                    <table class="tabla-admin-mejorada">
                        <thead id="tabla-entidades-header">
                           <tr><?php echo render_table_header($columnas_tabla); ?></tr>
                        </thead>
                        <tbody id="tabla-entidades-body">
                           <?php echo render_table_body($datos_entidad, $entidad_seleccionada, $config_final, count($columnas_tabla)); ?>
                        </tbody>
                    </table>
                </div>
                
                <nav aria-label="Paginación de entidades" class="mt-3 paginacion-tabla-container">
                    <ul class="pagination pagination-sm" id="paginacion-lista"></ul>
                </nav>
            </div>
        </div>
    </main>
    <?php include '../../include/footer.php'; ?>

    <div id="modalEditarEntidadContainer"></div>

    <div class="modal fade" id="modalVerDetalles" tabindex="-1" aria-labelledby="modalVerDetallesLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="modalVerDetallesLabel"><i class="bi bi-info-circle-fill"></i> Detalles de la Entidad</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body" id="modalVerDetallesBody"><div class="text-center p-4"><div class="spinner-border text-primary"></div><p>Cargando...</p></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
        </div>
      </div>
    </div>
    
    <div class="modal fade" id="modalConfirmarReporte" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-file-earmark-excel-fill me-2"></i>Generar Reporte de Entidades</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p>Se generará un reporte en Excel con los siguientes filtros aplicados:</p>
                    <div id="confirmarReporteTexto" class="alert alert-light border"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnConfirmarGeneracion"><i class="bi bi-check-circle-fill"></i> Confirmar y Generar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/editar_entidad.js?v=<?php echo time(); ?>"></script>
    
    <script>
        const initialData = {
            pagina_actual: <?php echo $pagina_actual; ?>,
            total_paginas: <?php echo $total_paginas; ?>,
            csrf_token: '<?php echo $csrf_token; ?>'
        };
    </script>
    <script src="../js/lista_entidades.js?v=<?php echo time(); ?>"></script>
</body>
</html>