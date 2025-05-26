<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) { header('Location: ../inicio_sesion.php'); exit; }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf_token = $_SESSION['csrf_token'];

$conex = new database(); $con = $conex->conectar();
$entidad_seleccionada = trim($_GET['tipo_entidad'] ?? 'todas'); 
$filtro_nombre_entidad = trim($_GET['filtro_nombre'] ?? '');
$datos_entidad = []; $columnas_tabla = []; $error_db = ''; $titulo_tabla = 'Entidades';

$registros_por_pagina = 4;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$total_registros = 0; $total_paginas = 1;

if ($con) {
    try {
        if ($entidad_seleccionada === 'todas') {
            $titulo_tabla = 'Todas las Entidades'; 
            $columnas_tabla = ['NIT', 'Nombre', 'Tipo', 'Teléfono', 'Correo', 'Acciones'];
            
            $where_clauses_union = [];
            $params_union = [];

            if (!empty($filtro_nombre_entidad)) {
                $where_clauses_union['farmacias'] = "nom_farm LIKE :nombre_filtro";
                $where_clauses_union['eps'] = "nombre_eps LIKE :nombre_filtro";
                $where_clauses_union['ips'] = "nom_IPS LIKE :nombre_filtro";
                $params_union[':nombre_filtro'] = "%" . $filtro_nombre_entidad . "%";
            }

            $sql_farmacias = "(SELECT nit_farm as id, nom_farm as nombre, 'Farmacia' as tipo_display, tel_farm as telefono, correo_farm as correo, 'farmacias' as tipo_key FROM farmacias " . (!empty($where_clauses_union['farmacias']) ? "WHERE ".$where_clauses_union['farmacias'] : "") . ")";
            $sql_eps = "(SELECT nit_eps as id, nombre_eps as nombre, 'EPS' as tipo_display, telefono, correo, 'eps' as tipo_key FROM eps " . (!empty($where_clauses_union['eps']) ? "WHERE ".$where_clauses_union['eps'] : "") . ")";
            $sql_ips = "(SELECT Nit_IPS as id, nom_IPS as nombre, 'IPS' as tipo_display, tel_IPS as telefono, correo_IPS as correo, 'ips' as tipo_key FROM ips " . (!empty($where_clauses_union['ips']) ? "WHERE ".$where_clauses_union['ips'] : "") . ")";

            $count_sql = "SELECT SUM(count) as total FROM (
                SELECT COUNT(*) as count FROM farmacias " . (!empty($where_clauses_union['farmacias']) ? "WHERE ".$where_clauses_union['farmacias'] : "") . "
                UNION ALL
                SELECT COUNT(*) as count FROM eps " . (!empty($where_clauses_union['eps']) ? "WHERE ".$where_clauses_union['eps'] : "") . "
                UNION ALL
                SELECT COUNT(*) as count FROM ips " . (!empty($where_clauses_union['ips']) ? "WHERE ".$where_clauses_union['ips'] : "") . "
            ) AS counts";

            $stmt_total = $con->prepare($count_sql);
            if (!empty($params_union)) { $stmt_total->execute($params_union); } else { $stmt_total->execute(); }
            $total_registros = (int)$stmt_total->fetchColumn();


            if ($total_registros > 0) {
                $total_paginas = ceil($total_registros / $registros_por_pagina); if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas; if($total_paginas == 0) $total_paginas = 1; $offset = ($pagina_actual - 1) * $registros_por_pagina;
                
                $sql_union = "$sql_farmacias UNION ALL $sql_eps UNION ALL $sql_ips ORDER BY nombre ASC LIMIT :limit OFFSET :offset_val";
                
                $stmt = $con->prepare($sql_union);
                if (!empty($params_union)) { 
                    foreach($params_union as $key => $val) { $stmt->bindValue($key, $val); }
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="../img/loguito.png">
    <title>Ver Entidades - Administración</title>
</head>
<body class="d-flex flex-column">
    <?php include '../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <h3 class="titulo-lista-tabla"><?php echo htmlspecialchars($titulo_tabla); ?></h3>
                <?php if (isset($_SESSION['mensaje_accion'])): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['mensaje_accion_tipo']); ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['mensaje_accion']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['mensaje_accion']); unset($_SESSION['mensaje_accion_tipo']); ?>
                <?php endif; ?>
                <?php if (!empty($error_db)) : ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_db); ?></div><?php endif; ?>

                <form method="GET" action="ver_entidades.php" class="mb-4 filtros-tabla-container">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="tipo_entidad_select" class="form-label">Filtrar por Tipo:</label>
                            <select id="tipo_entidad_select" name="tipo_entidad" class="form-select form-select-sm">
                                <option value="todas" <?php echo ($entidad_seleccionada == 'todas') ? 'selected' : ''; ?>>-- Todas las Entidades --</option>
                                <option value="farmacias" <?php echo ($entidad_seleccionada == 'farmacias') ? 'selected' : ''; ?>>Farmacias</option>
                                <option value="eps" <?php echo ($entidad_seleccionada == 'eps') ? 'selected' : ''; ?>>EPS</option>
                                <option value="ips" <?php echo ($entidad_seleccionada == 'ips') ? 'selected' : ''; ?>>IPS</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="filtro_nombre" class="form-label">Buscar por Nombre:</label>
                            <input type="text" name="filtro_nombre" id="filtro_nombre" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtro_nombre_entidad); ?>" placeholder="Nombre de la entidad...">
                        </div>
                        <div class="col-md-2">
                             <button type="submit" class="btn btn-sm btn-filtrar-tabla w-100">Filtrar <i class="bi bi-search"></i></button>
                        </div>
                    </div>
                </form>


                <?php if (empty($error_db)): ?>
                    <div class="table-responsive">
                        <table class="tabla-admin-mejorada">
                            <thead>
                                <tr>
                                    <?php foreach ($columnas_tabla as $columna) : ?>
                                        <th><?php echo htmlspecialchars($columna); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($datos_entidad)) : ?>
                                    <?php foreach ($datos_entidad as $entidad) : ?>
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
                                            <td class="acciones-tabla">
                                                <button class="btn btn-success btn-sm btn-editar-entidad" 
                                                        data-id="<?php echo htmlspecialchars($entidad['id']); ?>" 
                                                        data-tipo="<?php echo htmlspecialchars($entidad['tipo_key']); ?>" 
                                                        title="Editar">
                                                    <i class="bi bi-pencil-square"></i> <span>Editar</span>
                                                </button>
                                                <button class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo htmlspecialchars($entidad['id']); ?>" data-nombre="<?php echo htmlspecialchars($entidad_seleccionada === 'todas' ? ($entidad['nombre'] ?? '') : ($entidad[$config['nombre_col']] ?? '')); ?>" data-tipo="<?php echo htmlspecialchars($entidad['tipo_key']); ?>" title="Eliminar"><i class="bi bi-trash3"></i> <span>Eliminar</span></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                     <tr><td colspan="<?php echo count($columnas_tabla); ?>" class="text-center">
                                        No hay <?php echo htmlspecialchars(strtolower($titulo_tabla)); ?> que coincidan con los filtros aplicados.
                                    </td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_registros > 0 && $total_paginas > 1): ?>
                    <nav aria-label="Paginación de entidades" class="mt-3 paginacion-tabla-container">
                        <ul class="pagination pagination-sm">
                             <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>&tipo_entidad=<?php echo urlencode($entidad_seleccionada); ?>&filtro_nombre=<?php echo urlencode($filtro_nombre_entidad); ?>" aria-label="Anterior"> <i class="bi bi-chevron-left"></i></a>
                            </li>
                             <li class="page-item active" id="page-number-container">
                               <span class="page-link page-number-display" title="Click/Enter para ir a pág."><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></span>
                               <input type="number" class="form-control form-control-sm page-number-input-field" value="<?php echo $pagina_actual; ?>" min="1" max="<?php echo $total_paginas; ?>" style="display: none;" data-total="<?php echo $total_paginas; ?>">
                             </li>
                            <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>&tipo_entidad=<?php echo urlencode($entidad_seleccionada); ?>&filtro_nombre=<?php echo urlencode($filtro_nombre_entidad); ?>" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include '../include/footer.php'; ?>

    <div id="modalEditarEntidadContainer"></div>
    <div class="modal-confirmacion" id="modalConfirmacionEliminar" style="display:none;"><div class="modal-contenido"><h4>Confirmar Eliminación</h4><p id="mensajeConfirmacion">¿Está seguro?</p><div class="modal-botones"><button id="btnConfirmarEliminacion" class="btn btn-danger">Eliminar</button><button id="btnCancelarEliminacion" class="btn btn-secondary">Cancelar</button></div></div></div>

    <script src="../js/editar_entidad.js?v=<?php echo time(); ?>"></script>
    <script>
         document.addEventListener('DOMContentLoaded', function () {
            const modalDel = document.getElementById('modalConfirmacionEliminar'); const msgConfDel = document.getElementById('mensajeConfirmacion'); const btnConfDel = document.getElementById('btnConfirmarEliminacion'); const btnCancDel = document.getElementById('btnCancelarEliminacion'); let formDel = null;
            document.querySelectorAll('.btn-eliminar').forEach(b => { b.addEventListener('click', function () { const id = this.dataset.id; const n = this.dataset.nombre; const t = this.dataset.tipo; msgConfDel.textContent = `¿Eliminar a ${n} (ID: ${id}) de ${t}? Acción irreversible.`; modalDel.style.display = 'flex'; formDel = document.createElement('form'); formDel.method = 'POST'; formDel.action = 'eliminar_registro.php'; const iId = document.createElement('input'); iId.type = 'hidden'; iId.name = 'id_registro'; iId.value = id; formDel.appendChild(iId); const iT = document.createElement('input'); iT.type = 'hidden'; iT.name = 'tipo_registro'; iT.value = t; formDel.appendChild(iT); const iTk = document.createElement('input'); iTk.type = 'hidden'; iTk.name = 'csrf_token'; iTk.value = '<?php echo $csrf_token; ?>'; formDel.appendChild(iTk); document.body.appendChild(formDel); }); });
            btnConfDel.addEventListener('click', () => { if (formDel) formDel.submit(); }); btnCancDel.addEventListener('click', () => { modalDel.style.display = 'none'; if (formDel && formDel.parentNode) formDel.parentNode.removeChild(formDel); formDel = null; }); window.addEventListener('click', (e) => { if (e.target == modalDel) btnCancDel.click(); });
            
            const pageContainer = document.getElementById('page-number-container'); const pageIS = pageContainer?.querySelector('.page-number-display'); const pageIF = pageContainer?.querySelector('.page-number-input-field');
            if(pageIS && pageIF){ pageIS.addEventListener('click', () => { pageIS.style.display = 'none'; pageIF.style.display = 'inline-block'; pageIF.focus(); pageIF.select(); }); const goPg = () => { const tp = parseInt(pageIF.dataset.total, 10) || 1; let tgPg = parseInt(pageIF.value, 10); if (isNaN(tgPg) || tgPg < 1) tgPg = 1; else if (tgPg > tp) tgPg = tp; const curl = new URL(window.location.href); curl.searchParams.set('pagina', tgPg); window.location.href = curl.toString(); }; const hideInput = () => { const totalPgs = parseInt(pageIF.dataset.total, 10) || 1; const currentPage = parseInt(pageIF.value, 10); if (isNaN(currentPage) || currentPage < 1) { pageIS.textContent = '<?php echo $pagina_actual; ?> / ' + totalPgs; pageIF.value = '<?php echo $pagina_actual; ?>'; } else if (currentPage > totalPgs) { pageIS.textContent = totalPgs + ' / ' + totalPgs; pageIF.value = totalPgs; } else { pageIS.textContent = pageIF.value + ' / ' + totalPgs; } pageIS.style.display = 'inline-block'; pageIF.style.display = 'none'; }; pageIF.addEventListener('blur', () => { setTimeout(hideInput, 150); }); pageIF.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); goPg(); } else if (e.key === 'Escape'){ pageIF.value = '<?php echo $pagina_actual; ?>'; hideInput(); } }); }
            
             const tipoEntidadSelect = document.getElementById('tipo_entidad_select');
             if(tipoEntidadSelect){ tipoEntidadSelect.addEventListener('change', function() { const selectedTipo = this.value; const currentUrl = new URL(window.location.href); currentUrl.searchParams.set('tipo_entidad', selectedTipo); currentUrl.searchParams.set('pagina', '1'); currentUrl.searchParams.delete('filtro_nombre'); window.location.href = currentUrl.toString(); });}

             inicializarModalEdicionEntidad();
        });
    </script>
</body>
</html>