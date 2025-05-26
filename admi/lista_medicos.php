<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) {
    header('Location: ../inicio_sesion.php'); exit;
}
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf_token = $_SESSION['csrf_token'];

$conex = new Database(); $con = $conex->conectar();
$medicos_list = [];
$ips_para_filtro = [];
$error_db = '';

$filtro_nit_ips_seleccionado = trim($_GET['filtro_ips'] ?? '');
$filtro_doc_medico = trim($_GET['filtro_doc_medico'] ?? '');

$registros_por_pagina = 4;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$total_registros = 0; $total_paginas = 1;

if ($con) {
    try {
        $stmt_ips_filter_list = $con->query("
            SELECT DISTINCT i.nit_ips, i.nom_ips 
            FROM ips i
            INNER JOIN asignacion_medico am ON i.nit_ips = am.nit_ips
            WHERE am.id_estado = 1
            ORDER BY i.nom_ips ASC
        ");
        $ips_para_filtro = $stmt_ips_filter_list->fetchAll(PDO::FETCH_ASSOC);

        $sql_base_from = "FROM usuarios u
                          JOIN rol r ON u.id_rol = r.id_rol
                          LEFT JOIN especialidad esp ON u.id_especialidad = esp.id_espe
                          LEFT JOIN afiliados af ON u.doc_usu = af.doc_afiliado";

        $sql_where_clauses = ["u.id_rol = 4"]; 
        $params_php = [];
        
        if (!empty($filtro_doc_medico)) {
            $sql_where_clauses[] = "u.doc_usu LIKE :doc_medico_filtro";
            $params_php[':doc_medico_filtro'] = "%" . $filtro_doc_medico . "%";
        }

        if (!empty($filtro_nit_ips_seleccionado)) {
            if ($filtro_nit_ips_seleccionado === 'sin_asignacion') {
                $sql_where_clauses[] = "(SELECT COUNT(*) FROM asignacion_medico am_check WHERE am_check.doc_medico = u.doc_usu AND am_check.id_estado = 1) = 0";
            } else {
                $sql_where_clauses[] = "EXISTS (SELECT 1 FROM asignacion_medico am_filt WHERE am_filt.doc_medico = u.doc_usu AND am_filt.nit_ips = :nit_ips AND am_filt.id_estado = 1)";
                $params_php[':nit_ips'] = $filtro_nit_ips_seleccionado;
            }
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

            $sql_medicos = "SELECT u.doc_usu, u.id_tipo_doc, u.nom_usu, u.tel_usu, u.correo_usu,
                                   esp.nom_espe,
                                   af.id_estado AS estado_afiliacion,
                                   (SELECT COUNT(*) FROM asignacion_medico am_check WHERE am_check.doc_medico = u.doc_usu AND am_check.id_estado = 1) AS asignado_ips_activo,
                                   (SELECT GROUP_CONCAT(DISTINCT i_sub.nom_IPS SEPARATOR ', ') 
                                    FROM asignacion_medico am_ips_sub 
                                    JOIN ips i_sub ON am_ips_sub.nit_ips = i_sub.Nit_IPS 
                                    WHERE am_ips_sub.doc_medico = u.doc_usu AND am_ips_sub.id_estado = 1) AS nombres_ips_activas
                             $sql_base_from
                             $sql_where
                             GROUP BY u.doc_usu, u.id_tipo_doc, u.nom_usu, u.tel_usu, u.correo_usu, esp.nom_espe, af.id_estado
                             ORDER BY u.nom_usu ASC
                             LIMIT :limit OFFSET :offset_val";

            $stmt_medicos = $con->prepare($sql_medicos);
            foreach ($params_php as $key => $value) {
                $stmt_medicos->bindValue($key, $value);
            }
            $stmt_medicos->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
            $stmt_medicos->bindParam(':offset_val', $offset, PDO::PARAM_INT);
            $stmt_medicos->execute();
            $medicos_list = $stmt_medicos->fetchAll(PDO::FETCH_ASSOC);
        } else { $medicos_list = []; $total_paginas = 1; $pagina_actual = 1; }
    } catch (PDOException $e) { $error_db = "Error al consultar la base de datos: " . $e->getMessage(); error_log("PDO Lista Medicos: ".$e->getMessage()); }
} else { $error_db = "Error de conexión a la base de datos."; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="../img/loguito.png">
    <title>SaludConnect - Lista de Médicos</title>
    <style>
        .btn-group-acciones .btn { margin-right: 5px; margin-bottom: 5px; display: inline-flex; align-items: center; }
        .btn-group-acciones .btn i { margin-right: 4px;}
        .btn-group-acciones .btn:last-child { margin-right: 0; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <h3 class="titulo-lista-tabla">Lista de Médicos</h3>

                <?php if (isset($_SESSION['mensaje_accion']) || isset($_GET['afiliacion_exitosa']) || isset($_GET['asignacion_exitosa'])): ?>
                    <div class="alert alert-<?php 
                        if(isset($_GET['afiliacion_exitosa']) || isset($_GET['asignacion_exitosa'])) echo 'success'; 
                        else echo htmlspecialchars($_SESSION['mensaje_accion_tipo']); 
                    ?> alert-dismissible fade show" role="alert">
                        <?php 
                            if(isset($_GET['afiliacion_exitosa']) && isset($_GET['msg'])) {
                                echo htmlspecialchars(urldecode($_GET['msg']));
                            } elseif (isset($_GET['asignacion_exitosa'])) {
                                echo "Asignación de IPS procesada exitosamente.";
                            } else {
                                echo htmlspecialchars($_SESSION['mensaje_accion']);
                            }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['mensaje_accion']); unset($_SESSION['mensaje_accion_tipo']); ?>
                <?php endif; ?>

                <?php if (!empty($error_db)) : ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_db); ?></div><?php endif; ?>
                
                <form method="GET" action="lista_medicos.php" class="mb-4 filtros-tabla-container">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="filtro_ips" class="form-label">Filtrar por IPS:</label>
                            <select name="filtro_ips" id="filtro_ips" class="form-select form-select-sm">
                                <option value="">-- Todas las IPS / Asignaciones --</option>
                                <option value="sin_asignacion" <?php echo ($filtro_nit_ips_seleccionado === 'sin_asignacion') ? 'selected' : ''; ?>>-- Médicos Sin Asignación IPS --</option>
                                <?php foreach ($ips_para_filtro as $ips_item): ?>
                                    <option value="<?php echo htmlspecialchars($ips_item['nit_ips']); ?>" <?php echo ($filtro_nit_ips_seleccionado == $ips_item['nit_ips'] && $filtro_nit_ips_seleccionado !== 'sin_asignacion') ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ips_item['nom_ips']); ?> (<?php echo htmlspecialchars($ips_item['nit_ips']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="filtro_doc_medico" class="form-label">Buscar Médico por Documento:</label>
                            <input type="text" id="filtro_doc_medico" name="filtro_doc_medico" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtro_doc_medico); ?>" placeholder="Escriba el documento...">
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
                                    <th>Documento</th>
                                    <th>Nombre</th>
                                    <th>Teléfono</th>
                                    <th>Correo</th>
                                    <th>Especialidad</th>
                                    <th>IPS Activa(s)</th>
                                    <th class="columna-acciones-tabla">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tabla_medicos_body">
                                <?php if (!empty($medicos_list)) : ?>
                                    <?php foreach ($medicos_list as $medico) : ?>
                                        <?php
                                            $url_actual_para_retorno = $_SERVER['REQUEST_URI'];
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($medico['doc_usu']); ?></td>
                                            <td><span class="truncate-text" title="<?php echo htmlspecialchars($medico['nom_usu']); ?>"><?php echo htmlspecialchars($medico['nom_usu']); ?></span></td>
                                            <td><?php echo htmlspecialchars($medico['tel_usu'] ?? 'N/A'); ?></td>
                                            <td><span class="truncate-text" title="<?php echo htmlspecialchars($medico['correo_usu']); ?>"><?php echo htmlspecialchars($medico['correo_usu'] ?? 'N/A'); ?></span></td>
                                            <td><?php echo htmlspecialchars($medico['nom_espe'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($medico['nombres_ips_activas'] ?: 'Ninguna'); ?></td>
                                            <td class="acciones-tabla btn-group-acciones">
                                                <button class="btn btn-success btn-sm btn-editar-usuario"
                                                        data-doc-usu="<?php echo htmlspecialchars($medico['doc_usu']); ?>"
                                                        title="Editar Médico">
                                                    <i class="bi bi-pencil-square"></i><span>Editar</span>
                                                </button>
                                                <button class="btn btn-danger btn-sm btn-eliminar"
                                                        data-id="<?php echo htmlspecialchars($medico['doc_usu']); ?>"
                                                        data-tipodoc="<?php echo htmlspecialchars($medico['id_tipo_doc']); ?>"
                                                        data-nombre="<?php echo htmlspecialchars($medico['nom_usu']); ?>"
                                                        data-tipo="usuario" 
                                                        data-asignado-ips="<?php echo htmlspecialchars($medico['asignado_ips_activo'] > 0 ? '1' : '0'); ?>"
                                                        data-estado-afiliacion="<?php echo htmlspecialchars($medico['estado_afiliacion'] ?? ''); ?>"
                                                        title="Eliminar Médico">
                                                    <i class="bi bi-trash3"></i><span>Eliminar</span>
                                                </button>
                                                 <?php
                                                $estado_afiliacion = $medico['estado_afiliacion'] ?? null;
                                                if (is_null($estado_afiliacion) || $estado_afiliacion == 2) {
                                                ?>
                                                    <a href="afiliacion.php?doc_usu=<?php echo htmlspecialchars($medico['doc_usu']); ?>&id_tipo_doc=<?php echo htmlspecialchars($medico['id_tipo_doc']); ?>&nom_usu=<?php echo urlencode($medico['nom_usu']); ?>&return_to=<?php echo urlencode($url_actual_para_retorno); ?>"
                                                       class="btn btn-primary btn-sm accion-afiliar" 
                                                       title="Afiliar Médico">
                                                        <i class="bi bi-person-plus-fill"></i><span>Afiliar</span>
                                                    </a>
                                                <?php
                                                } elseif ($estado_afiliacion == 1) { 
                                                ?>
                                                    <button class="btn btn-secondary btn-sm accion-afiliado" disabled title="Médico ya Afiliado Activo">
                                                        <i class="bi bi-person-check-fill"></i><span>Afiliado</span>
                                                    </button>
                                                <?php
                                                }
                                                ?>
                                                <?php if ($medico['asignado_ips_activo'] > 0) : ?>
                                                    <button class="btn btn-secondary btn-sm" disabled title="Asignado a: <?php echo htmlspecialchars($medico['nombres_ips_activas'] ?: 'N/D'); ?>">
                                                        <i class="bi bi-building-check"></i><span>Asignado</span>
                                                    </button>
                                                    <a href="asignar_ips_medico.php?doc_medico=<?php echo htmlspecialchars($medico['doc_usu']); ?>&nom_medico=<?php echo urlencode($medico['nom_usu']); ?>&return_to=<?php echo urlencode($url_actual_para_retorno); ?>"
                                                       class="btn btn-warning btn-sm"
                                                       title="Gestionar/Nueva Asignación IPS">
                                                        <i class="bi bi-buildings"></i><span>Gestionar IPS</span>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="asignar_ips_medico.php?doc_medico=<?php echo htmlspecialchars($medico['doc_usu']); ?>&nom_medico=<?php echo urlencode($medico['nom_usu']); ?>&return_to=<?php echo urlencode($url_actual_para_retorno); ?>"
                                                       class="btn btn-info btn-sm"
                                                       title="Asignar Médico a IPS">
                                                        <i class="bi bi-building-add"></i><span>Asignar IPS</span>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr id="fila_no_medicos_php"><td colspan="7" class="text-center">No hay médicos registrados que coincidan con los filtros.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_registros > 0 && $total_paginas > 1): ?>
                    <nav aria-label="Paginación de médicos" class="mt-3 paginacion-tabla-container">
                        <ul class="pagination pagination-sm">
                            <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>&filtro_ips=<?php echo urlencode($filtro_nit_ips_seleccionado); ?>&filtro_doc_medico=<?php echo urlencode($filtro_doc_medico); ?>"><i class="bi bi-chevron-left"></i></a>
                            </li>
                            <li class="page-item active" id="page-number-container">
                                <span class="page-link page-number-display" title="Click/Enter para ir a página"><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></span>
                                <input type="number" class="form-control form-control-sm page-number-input-field" value="<?php echo $pagina_actual; ?>" min="1" max="<?php echo $total_paginas; ?>" style="display: none;" data-total="<?php echo $total_paginas; ?>">
                            </li>
                            <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>&filtro_ips=<?php echo urlencode($filtro_nit_ips_seleccionado); ?>&filtro_doc_medico=<?php echo urlencode($filtro_doc_medico); ?>"><i class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>    
        </div>
    </main>
    <?php include '../include/footer.php'; ?>
    <?php include __DIR__ . '/modal_afiliacion_usu.php'; ?>
    
    <div class="modal-confirmacion" id="modalConfirmacionEliminar" style="display:none;">
        <div class="modal-contenido">
            <h4>Confirmar Eliminación</h4>
            <p id="mensajeConfirmacion">¿Está seguro?</p>
            <div class="modal-botones">
                <button id="btnConfirmarEliminacion" class="btn btn-danger">Eliminar</button>
                <button id="btnCancelarEliminacion" class="btn btn-secondary">Cancelar</button>
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
    <script src="../js/afiliacion_modal.js?v=<?php echo time(); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalConfirm = document.getElementById('modalConfirmacionEliminar');
            const msgConf = document.getElementById('mensajeConfirmacion');
            const btnConf = document.getElementById('btnConfirmarEliminacion');
            const btnCanc = document.getElementById('btnCancelarEliminacion');
            let formDel = null;

            const modalAlerta = document.getElementById('modalAlertaSimple');
            const tituloAlerta = document.getElementById('tituloAlertaSimple');
            const msgAlerta = document.getElementById('mensajeAlertaSimple');
            const btnAceptarAlerta = document.getElementById('btnAceptarAlertaSimple');

            document.querySelectorAll('.btn-eliminar').forEach(b => {
                b.addEventListener('click', function () {
                    const id = this.dataset.id;
                    const td = this.dataset.tipodoc;
                    const n = this.dataset.nombre;
                    const t = this.dataset.tipo;
                    const asignadoIPS = this.dataset.asignadoIps;
                    const estadoAfiliacion = this.dataset.estadoAfiliacion;

                    if (asignadoIPS === '1') {
                        tituloAlerta.textContent = 'Eliminación no permitida';
                        msgAlerta.textContent = `El médico ${n} (Doc: ${id}) está asignado a una IPS. Debe desasignarlo primero.`;
                        modalAlerta.style.display = 'flex';
                        return;
                    }
                    if (estadoAfiliacion === '1') {
                        tituloAlerta.textContent = 'Eliminación no permitida';
                        msgAlerta.textContent = `El médico ${n} (Doc: ${id}) tiene una afiliación activa. Debe inactivar su afiliación primero.`;
                        modalAlerta.style.display = 'flex';
                        return;
                    }

                    msgConf.innerHTML = `¿Eliminar al médico <strong>${n}</strong> (Doc: ${id})? Esta acción no se puede deshacer.`;
                    modalConfirm.style.display = 'flex';
                    formDel = document.createElement('form');
                    formDel.method = 'POST';
                    formDel.action = 'eliminar_registro.php';
                    const iId = document.createElement('input'); iId.type = 'hidden'; iId.name = 'id_registro'; iId.value = id; formDel.appendChild(iId);
                    if (td) { const iTD = document.createElement('input'); iTD.type = 'hidden'; iTD.name = 'id_tipo_doc'; iTD.value = td; formDel.appendChild(iTD); }
                    const iT = document.createElement('input'); iT.type = 'hidden'; iT.name = 'tipo_registro'; iT.value = t; formDel.appendChild(iT);
                    const iTk = document.createElement('input'); iTk.type = 'hidden'; iTk.name = 'csrf_token'; iTk.value = '<?php echo $csrf_token; ?>'; formDel.appendChild(iTk);
                    document.body.appendChild(formDel);
                });
            });

            btnConf.addEventListener('click', () => { if (formDel) formDel.submit(); });
            btnCanc.addEventListener('click', () => { modalConfirm.style.display = 'none'; if (formDel && formDel.parentNode) formDel.parentNode.removeChild(formDel); formDel = null; });
            btnAceptarAlerta.addEventListener('click', () => { modalAlerta.style.display = 'none'; });

            window.addEventListener('click', (e) => {
                if (e.target == modalConfirm) btnCanc.click();
                if (e.target == modalAlerta) modalAlerta.style.display = 'none';
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
            
            const modalEditUserContainer = document.getElementById('editarUsuarioModalPlaceholder');
            let currentEditModalInstance = null;
            document.querySelectorAll('.btn-editar-usuario').forEach(button => {
                button.addEventListener('click', function() {
                    const docUsu = this.dataset.docUsu;
                    modalEditUserContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center p-5" style="min-height:200px;"><div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"><span class="visually-hidden">Cargando...</span></div></div>';
                    fetch(`modal_editar_usuario.php?doc_usu_editar=${encodeURIComponent(docUsu)}&csrf_token=<?php echo $csrf_token; ?>`)
                        .then(response => {
                            if (!response.ok) throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
                            return response.text();
                        })
                        .then(html => {
                            modalEditUserContainer.innerHTML = html;
                            const editModalElement = document.getElementById('editUserModal');
                            if (editModalElement) {
                                if (currentEditModalInstance && typeof currentEditModalInstance.dispose === 'function') {
                                    currentEditModalInstance.dispose();
                                }
                                currentEditModalInstance = new bootstrap.Modal(editModalElement);
                                editModalElement.addEventListener('shown.bs.modal', function onModalShown() {
                                    if (typeof inicializarValidacionesEdicionAdmin === "function") {
                                        inicializarValidacionesEdicionAdmin();
                                    }
                                    editModalElement.removeEventListener('shown.bs.modal', onModalShown);
                                });
                                editModalElement.addEventListener('hidden.bs.modal', function onModalHidden() {
                                    if (currentEditModalInstance && typeof currentEditModalInstance.dispose === 'function') {
                                        currentEditModalInstance.dispose();
                                    }
                                    currentEditModalInstance = null;
                                    modalEditUserContainer.innerHTML = '';
                                    editModalElement.removeEventListener('hidden.bs.modal', onModalHidden);
                                });
                                currentEditModalInstance.show();
                            } else {
                                modalEditUserContainer.innerHTML = '<div class="alert alert-danger m-3">Error: No se pudo encontrar el elemento #editUserModal en la respuesta.</div>';
                            }
                        })
                        .catch(error => {
                            modalEditUserContainer.innerHTML = `<div class="alert alert-danger m-3">No se pudo cargar el modal de edición: ${error.message}. Verifique la consola para más detalles.</div>`;
                            console.error("Error al cargar modal de edición:", error);
                        });
                });
            });
            
            if (window.location.search.includes('afiliacion_exitosa=1') || window.location.search.includes('afiliacion_procesada=1') || window.location.search.includes('asignacion_exitosa=1')) {
                const url = new URL(window.location);
                url.searchParams.delete('afiliacion_exitosa');
                url.searchParams.delete('afiliacion_procesada');
                url.searchParams.delete('asignacion_exitosa');
                url.searchParams.delete('msg');
                window.history.replaceState({}, document.title, url);
            }
        });
    </script>
</body>
</html>