<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php');

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) {
    header('Location: ../inicio_sesion.php'); exit;
}
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf_token = $_SESSION['csrf_token'];

$farmaceutas_list = [];
$farmacias_para_filtro = [];
$error_db = '';

$filtro_farmacia_opcion = trim($_GET['filtro_farmacia'] ?? '');
$filtro_doc_farmaceuta = trim($_GET['filtro_doc_farmaceuta'] ?? '');
$filtro_estado_usuario = trim($_GET['filtro_estado'] ?? '');


$registros_por_pagina = 4;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$total_registros = 0; $total_paginas = 1;

if ($con) {
    try {
        $stmt_farmacias_filter_list = $con->query("
            SELECT DISTINCT f.nit_farm, f.nom_farm 
            FROM farmacias f
            ORDER BY f.nom_farm ASC
        ");
        $farmacias_para_filtro = $stmt_farmacias_filter_list->fetchAll(PDO::FETCH_ASSOC);

        $sql_base_from = "FROM usuarios u
                          JOIN rol r ON u.id_rol = r.id_rol
                          LEFT JOIN estado est ON u.id_est = est.id_est";

        $sql_where_clauses = ["u.id_rol = 3"]; 
        $params_php = [];
        
        if (!empty($filtro_doc_farmaceuta)) {
            $sql_where_clauses[] = "u.doc_usu LIKE :doc_farmaceuta_filtro";
            $params_php[':doc_farmaceuta_filtro'] = "%" . $filtro_doc_farmaceuta . "%";
        }

        if (!empty($filtro_farmacia_opcion)) {
            if ($filtro_farmacia_opcion === 'sin_asignacion_activa') {
                $sql_where_clauses[] = "NOT EXISTS (SELECT 1 FROM asignacion_farmaceuta af_check WHERE af_check.doc_farma = u.doc_usu AND af_check.id_estado = 1)";
            } elseif ($filtro_farmacia_opcion === 'con_asignacion_inactiva') {
                $sql_where_clauses[] = "EXISTS (SELECT 1 FROM asignacion_farmaceuta af_check WHERE af_check.doc_farma = u.doc_usu AND af_check.id_estado = 2)";
            } else {
                $sql_where_clauses[] = "EXISTS (SELECT 1 FROM asignacion_farmaceuta af_filt WHERE af_filt.doc_farma = u.doc_usu AND af_filt.nit_farma = :nit_farma AND af_filt.id_estado = 1)";
                $params_php[':nit_farma'] = $filtro_farmacia_opcion;
            }
        }
        
        if ($filtro_estado_usuario !== '') {
             $sql_where_clauses[] = "u.id_est = :id_est_filtro";
             $params_php[':id_est_filtro'] = $filtro_estado_usuario;
        }

        $sql_where = "";
        if (!empty($sql_where_clauses)) { $sql_where = " WHERE " . implode(" AND ", $sql_where_clauses); }

        $stmt_total_sql = "SELECT COUNT(DISTINCT u.doc_usu) $sql_base_from $sql_where";
        $stmt_total = $con->prepare($stmt_total_sql);
        $stmt_total->execute($params_php);
        $total_registros = (int)$stmt_total->fetchColumn();

        if ($total_registros > 0) {
            $total_paginas = ceil($total_registros / $registros_por_pagina);
            if ($total_paginas == 0) $total_paginas = 1;
            if ($pagina_actual > $total_paginas && $total_paginas > 0) $pagina_actual = $total_paginas;
            $offset = ($pagina_actual - 1) * $registros_por_pagina;

            $sql_farmaceutas = "SELECT u.doc_usu, u.id_tipo_doc, u.nom_usu, u.tel_usu, u.correo_usu, u.id_est AS id_estado_usuario, est.nom_est AS nombre_estado_usuario,
                                   (SELECT COUNT(*) FROM asignacion_farmaceuta af_check WHERE af_check.doc_farma = u.doc_usu AND af_check.id_estado = 1) AS asignado_farmacia_activo_count,
                                   (SELECT GROUP_CONCAT(DISTINCT farm_sub.nom_farm SEPARATOR ', ') 
                                    FROM asignacion_farmaceuta af_farm_sub 
                                    JOIN farmacias farm_sub ON af_farm_sub.nit_farma = farm_sub.nit_farm 
                                    WHERE af_farm_sub.doc_farma = u.doc_usu AND af_farm_sub.id_estado = 1) AS nombres_farmacias_activas
                             $sql_base_from
                             $sql_where
                             GROUP BY u.doc_usu, u.id_tipo_doc, u.nom_usu, u.tel_usu, u.correo_usu, u.id_est, est.nom_est
                             ORDER BY u.nom_usu ASC
                             LIMIT :limit OFFSET :offset_val";

            $stmt_farmaceutas = $con->prepare($sql_farmaceutas);
            foreach ($params_php as $key => $value) {
                $stmt_farmaceutas->bindValue($key, $value);
            }
            $stmt_farmaceutas->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
            $stmt_farmaceutas->bindParam(':offset_val', $offset, PDO::PARAM_INT);
            $stmt_farmaceutas->execute();
            $farmaceutas_list = $stmt_farmaceutas->fetchAll(PDO::FETCH_ASSOC);
        } else { $farmaceutas_list = []; $total_paginas = 1; $pagina_actual = 1; }
    } catch (PDOException $e) { $error_db = "Error al consultar la base de datos: " . $e->getMessage(); error_log("PDO Lista Farmaceutas: ".$e->getMessage()); }
} else { $error_db = "Error de conexión a la base de datos."; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="../img/loguito.png">
    <title>SaludConnect - Lista de Farmaceutas</title>
    <style>
        .btn-group-acciones .btn { margin-right: 3px; margin-bottom: 3px; display: inline-flex; align-items: center; font-size:0.8rem; padding: 0.25rem 0.5rem;}
        .btn-group-acciones .btn i { margin-right: 3px;}
        .btn-group-acciones .btn:last-child { margin-right: 0; }
        .badge-estado-usuario { font-size: 0.75em; padding: 0.4em 0.65em; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <h3 class="titulo-lista-tabla">Lista de Farmaceutas</h3>

                <?php if (isset($_SESSION['mensaje_accion']) || isset($_GET['msg_estado']) ): ?>
                    <div class="alert alert-<?php 
                        if(isset($_GET['msg_estado'])) echo htmlspecialchars($_GET['tipo_msg_estado']);
                        else echo htmlspecialchars($_SESSION['mensaje_accion_tipo'] ?? 'info'); 
                    ?> alert-dismissible fade show" role="alert">
                        <?php 
                           if(isset($_GET['msg_estado'])) echo htmlspecialchars(urldecode($_GET['msg_estado']));
                           else echo htmlspecialchars($_SESSION['mensaje_accion'] ?? '');
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['mensaje_accion']); unset($_SESSION['mensaje_accion_tipo']); ?>
                <?php endif; ?>

                <?php if (!empty($error_db)) : ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_db); ?></div><?php endif; ?>
                
                <form method="GET" action="lista_farmaceutas.php" class="mb-4 filtros-tabla-container">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="filtro_farmacia" class="form-label">Filtrar por Asignación:</label>
                            <select name="filtro_farmacia" id="filtro_farmacia" class="form-select form-select-sm">
                                <option value="">-- Todas las Asignaciones --</option>
                                <option value="sin_asignacion_activa" <?php echo ($filtro_farmacia_opcion === 'sin_asignacion_activa') ? 'selected' : ''; ?>>-- Sin Asignación Activa --</option>
                                <option value="con_asignacion_inactiva" <?php echo ($filtro_farmacia_opcion === 'con_asignacion_inactiva') ? 'selected' : ''; ?>>-- Con Asignación Inactiva --</option>
                                <?php foreach ($farmacias_para_filtro as $farmacia_item): ?>
                                    <option value="<?php echo htmlspecialchars($farmacia_item['nit_farm']); ?>" <?php echo ($filtro_farmacia_opcion == $farmacia_item['nit_farm'] && !in_array($filtro_farmacia_opcion, ['sin_asignacion_activa', 'con_asignacion_inactiva'])) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($farmacia_item['nom_farm']); ?> (Activa)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtro_doc_farmaceuta" class="form-label">Buscar por Documento:</label>
                            <input type="text" id="filtro_doc_farmaceuta" name="filtro_doc_farmaceuta" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtro_doc_farmaceuta); ?>" placeholder="Documento...">
                        </div>
                        <div class="col-md-3">
                            <label for="filtro_estado" class="form-label">Estado Usuario:</label>
                            <select name="filtro_estado" id="filtro_estado" class="form-select form-select-sm">
                                <option value="">-- Todos --</option>
                                <option value="1" <?php echo ($filtro_estado_usuario === '1') ? 'selected' : ''; ?>>Activo</option>
                                <option value="2" <?php echo ($filtro_estado_usuario === '2') ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-sm btn-filtrar-tabla w-100">Filtrar <i class="bi bi-search"></i></button>
                        </div>
                        <div class="col-md-auto">
                            <a href="lista_farmaceutas.php" class="btn btn-sm btn-outline-secondary w-100">Limpiar</a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="tabla-admin-mejorada">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>Farmacia(s) Activa(s)</th>
                                <th>Estado Usuario</th>
                                <th class="columna-acciones-tabla">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla_farmaceutas_body">
                            <?php if (!empty($farmaceutas_list)) : ?>
                                <?php foreach ($farmaceutas_list as $farmaceuta) : ?>
                                    <?php $url_actual_para_retorno = $_SERVER['REQUEST_URI']; ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($farmaceuta['doc_usu']); ?></td>
                                        <td><span class="truncate-text" title="<?php echo htmlspecialchars($farmaceuta['nom_usu']); ?>"><?php echo htmlspecialchars($farmaceuta['nom_usu']); ?></span></td>
                                        <td><span class="truncate-text" title="<?php echo htmlspecialchars($farmaceuta['correo_usu']); ?>"><?php echo htmlspecialchars($farmaceuta['correo_usu'] ?? 'N/A'); ?></span></td>
                                        <td><?php echo htmlspecialchars($farmaceuta['nombres_farmacias_activas'] ?: 'Ninguna'); ?></td>
                                        <td>
                                            <?php if ($farmaceuta['id_estado_usuario'] == 1): ?>
                                                <span class="badge bg-success badge-estado-usuario">Activo</span>
                                            <?php elseif ($farmaceuta['id_estado_usuario'] == 2): ?>
                                                <span class="badge bg-danger badge-estado-usuario">Inactivo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary badge-estado-usuario"><?php echo htmlspecialchars($farmaceuta['nombre_estado_usuario'] ?? 'N/D'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="acciones-tabla btn-group-acciones">
                                            <?php if ($farmaceuta['id_estado_usuario'] == 1): ?>
                                                <button class="btn btn-warning btn-sm btn-cambiar-estado"
                                                        data-doc-usu="<?php echo htmlspecialchars($farmaceuta['doc_usu']); ?>"
                                                        data-nom-usu="<?php echo htmlspecialchars($farmaceuta['nom_usu']); ?>"
                                                        data-correo-usu="<?php echo htmlspecialchars($farmaceuta['correo_usu']); ?>"
                                                        data-accion="inactivar"
                                                        title="Inactivar Usuario">
                                                    <i class="bi bi-person-slash"></i><span>Inactivar</span>
                                                </button>
                                            <?php elseif ($farmaceuta['id_estado_usuario'] == 2): ?>
                                                <button class="btn btn-success btn-sm btn-cambiar-estado"
                                                        data-doc-usu="<?php echo htmlspecialchars($farmaceuta['doc_usu']); ?>"
                                                        data-nom-usu="<?php echo htmlspecialchars($farmaceuta['nom_usu']); ?>"
                                                        data-correo-usu="<?php echo htmlspecialchars($farmaceuta['correo_usu']); ?>"
                                                        data-accion="activar"
                                                        title="Activar Usuario">
                                                    <i class="bi bi-person-check"></i><span>Activar</span>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-info btn-sm btn-editar-usuario"
                                                    data-doc-usu="<?php echo htmlspecialchars($farmaceuta['doc_usu']); ?>"
                                                    title="Editar Farmaceuta">
                                                <i class="bi bi-pencil-square"></i><span>Editar</span>
                                            </button>
                                            <button class="btn btn-danger btn-sm btn-eliminar"
                                                    data-id="<?php echo htmlspecialchars($farmaceuta['doc_usu']); ?>"
                                                    data-tipodoc="<?php echo htmlspecialchars($farmaceuta['id_tipo_doc']); ?>"
                                                    data-nombre="<?php echo htmlspecialchars($farmaceuta['nom_usu']); ?>"
                                                    data-tipo="usuario_farmaceuta" 
                                                    data-asignado-farmacia="<?php echo htmlspecialchars($farmaceuta['asignado_farmacia_activo_count'] > 0 ? '1' : '0'); ?>"
                                                    title="Eliminar Farmaceuta">
                                                <i class="bi bi-trash3"></i><span>Eliminar</span>
                                            </button>
                                            <a href="asignar_farmaceuta.php?doc_farma=<?php echo htmlspecialchars($farmaceuta['doc_usu']); ?>&nom_farma=<?php echo urlencode($farmaceuta['nom_usu']); ?>&return_to=<?php echo urlencode($url_actual_para_retorno); ?>"
                                               class="btn btn-primary btn-sm"
                                               title="Gestionar Asignación Farmacia">
                                                <i class="bi bi-shop-window"></i><span>Farmacia</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center">No hay farmaceutas que coincidan con los filtros.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_registros > 0 && $total_paginas > 1): ?>
                <nav aria-label="Paginación de farmaceutas" class="mt-3 paginacion-tabla-container">
                    <ul class="pagination pagination-sm">
                        <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>&filtro_farmacia=<?php echo urlencode($filtro_farmacia_opcion); ?>&filtro_doc_farmaceuta=<?php echo urlencode($filtro_doc_farmaceuta); ?>&filtro_estado=<?php echo urlencode($filtro_estado_usuario); ?>"><i class="bi bi-chevron-left"></i></a>
                        </li>
                        <li class="page-item active" id="page-number-container">
                            <span class="page-link page-number-display" title="Click/Enter para ir a página"><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></span>
                            <input type="number" class="form-control form-control-sm page-number-input-field" value="<?php echo $pagina_actual; ?>" min="1" max="<?php echo $total_paginas; ?>" style="display: none;" data-total="<?php echo $total_paginas; ?>">
                        </li>
                        <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>&filtro_farmacia=<?php echo urlencode($filtro_farmacia_opcion); ?>&filtro_doc_farmaceuta=<?php echo urlencode($filtro_doc_farmaceuta); ?>&filtro_estado=<?php echo urlencode($filtro_estado_usuario); ?>"><i class="bi bi-chevron-right"></i></a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>    
        </div>
    </main>
    <?php include '../include/footer.php'; ?>
    
    <div class="modal-confirmacion" id="modalConfirmacionAccion" style="display:none;">
        <div class="modal-contenido">
            <h4 id="tituloConfirmacionAccion">Confirmar Acción</h4>
            <p id="mensajeModalConfirmacionAccion">¿Está seguro?</p>
            <div class="modal-botones">
                <button id="btnConfirmarAccionModal" class="btn btn-danger">Confirmar</button>
                <button id="btnCancelarAccionModal" class="btn btn-secondary">Cancelar</button>
            </div>
        </div>
    </div>

    <div class="modal-confirmacion" id="modalAlertaSimple" style="display:none;">
        <div class="modal-contenido">
            <h4 id="tituloAlertaSimple">Alerta</h4>
            <p id="mensajeAlertaSimple">Mensaje.</p>
            <div class="modal-botones">
                <button id="btnAceptarAlertaSimple" class="btn btn-primary">Aceptar</button>
            </div>
        </div>
    </div>

    <div id="editarUsuarioModalPlaceholder"></div>

    <script src="../js/editar_usuario_admin.js?v=<?php echo time(); ?>"></script>
    <script src="../js/lista_usuarios_admin_acciones.js?v=<?php echo time(); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            inicializarAccionesListaUsuariosAdmin({
                csrfToken: '<?php echo $csrf_token; ?>',
                urlEditarModal: 'modal_editar_usuario.php',
                urlEliminar: 'eliminar_registro.php',
                urlCambiarEstado: '../ajax/cambiar_estado_usuario.php',
                urlCorreoActivacion: 'correo_activacion.php',
                rolUsuarioActual: 'farmaceuta'
            });

            const pageContainer = document.getElementById('page-number-container'); 
            if(pageContainer) {
                const pageIS = pageContainer.querySelector('.page-number-display'); 
                const pageIF = pageContainer.querySelector('.page-number-input-field'); 
                if(pageIS && pageIF){ 
                    pageIS.addEventListener('click', () => { pageIS.style.display = 'none'; pageIF.style.display = 'inline-block'; pageIF.focus(); pageIF.select(); }); 
                    const goPg = () => { const tp = parseInt(pageIF.dataset.total, 10) || 1; let tgPg = parseInt(pageIF.value, 10); if (isNaN(tgPg) || tgPg < 1) tgPg = 1; else if (tgPg > tp) tgPg = tp; const curl = new URL(window.location.href); curl.searchParams.set('pagina', tgPg); window.location.href = curl.toString(); }; 
                    const hideInput = () => { const totalPgs = parseInt(pageIF.dataset.total, 10) || 1; const currentPageVal = parseInt(pageIF.value, 10); let displayPage; if (isNaN(currentPageVal) || currentPageVal < 1) { displayPage = <?php echo $pagina_actual; ?>; pageIF.value = <?php echo $pagina_actual; ?>; } else if (currentPageVal > totalPgs) { displayPage = totalPgs; pageIF.value = totalPgs; } else { displayPage = pageIF.value; } pageIS.textContent = displayPage + ' / ' + totalPgs; pageIS.style.display = 'inline-block'; pageIF.style.display = 'none'; }; 
                    pageIF.addEventListener('blur', () => { setTimeout(hideInput, 150); }); 
                    pageIF.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); goPg(); } else if (e.key === 'Escape'){ pageIF.value = '<?php echo $pagina_actual; ?>'; hideInput(); } }); 
                }
            }
            
            if (window.location.search.includes('msg_estado=')) {
                const url = new URL(window.location);
                url.searchParams.delete('msg_estado');
                url.searchParams.delete('tipo_msg_estado');
                window.history.replaceState({}, document.title, url);
            }
        });
    </script>
</body>
</html>