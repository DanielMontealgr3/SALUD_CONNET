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
$roles_disponibles = []; $usuarios = []; $error_db = '';
$filtro_rol_seleccionado = trim($_GET['filtro_rol'] ?? '');

$registros_por_pagina = 5;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$total_registros = 0; $total_paginas = 1;

$doc_admin_actual_session = $_SESSION['doc_usu_actual'] ?? '';

if ($con) {
    try {
        $stmt_roles = $con->query("SELECT id_rol, nombre_rol FROM rol ORDER BY nombre_rol ASC");
        $roles_disponibles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

        $sql_base = "FROM usuarios u
                     JOIN rol r ON u.id_rol = r.id_rol
                     LEFT JOIN afiliados af ON u.doc_usu = af.doc_afiliado";

        $sql_where_clauses = [];
        $params_php = [];

        if (!empty($filtro_rol_seleccionado) && is_numeric($filtro_rol_seleccionado)) {
            $sql_where_clauses[] = "u.id_rol = :id_rol";
            $params_php[':id_rol'] = $filtro_rol_seleccionado;
        }

        if (!empty($doc_admin_actual_session)) {
            $sql_where_clauses[] = "u.doc_usu != :doc_admin_actual";
            $params_php[':doc_admin_actual'] = $doc_admin_actual_session;
        }

        $sql_where = "";
        if (!empty($sql_where_clauses)) { $sql_where = " WHERE " . implode(" AND ", $sql_where_clauses); }

        $stmt_total_sql = "SELECT COUNT(DISTINCT u.doc_usu) $sql_base $sql_where";
        $stmt_total = $con->prepare($stmt_total_sql);
        $stmt_total->execute($params_php);
        $total_registros = (int)$stmt_total->fetchColumn();

        if ($total_registros > 0) {
            $total_paginas = ceil($total_registros / $registros_por_pagina);
            if ($total_paginas == 0) $total_paginas = 1;
            if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;
            $offset = ($pagina_actual - 1) * $registros_por_pagina;

            $sql_usuarios = "SELECT u.doc_usu, u.id_tipo_doc, u.nom_usu, u.fecha_nac, u.tel_usu, u.correo_usu, r.nombre_rol, af.id_estado AS estado_afiliacion
                             $sql_base
                             $sql_where
                             ORDER BY u.nom_usu ASC
                             LIMIT :limit OFFSET :offset_val";

            $stmt_usuarios = $con->prepare($sql_usuarios);
            foreach ($params_php as $key => $value) {
                $stmt_usuarios->bindValue($key, $value);
            }
            $stmt_usuarios->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
            $stmt_usuarios->bindParam(':offset_val', $offset, PDO::PARAM_INT);
            $stmt_usuarios->execute();
            $usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);
        } else { $usuarios = []; $total_paginas = 1; $pagina_actual = 1; }
    } catch (PDOException $e) { $error_db = "Error al consultar la base de datos."; error_log("PDO Ver Usu: ".$e->getMessage()); }
} else { $error_db = "Error de conexión a la base de datos."; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaludConnect - Ver Usuarios</title>
    <link rel="stylesheet" href="../css/estilo.css?v=<?php echo time(); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="d-flex flex-column">
    <?php include '../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            
            <h3 class="titulo-lista mb-3">Lista de Usuarios Registrados</h3>

            <?php if (isset($_SESSION['mensaje_accion'])): ?>
                <div class="alert alert-<?php echo htmlspecialchars($_SESSION['mensaje_accion_tipo']); ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_SESSION['mensaje_accion']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['mensaje_accion']); unset($_SESSION['mensaje_accion_tipo']); ?>
            <?php endif; ?>

            <?php if (!empty($error_db)) : ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_db); ?></div><?php endif; ?>

            <div class="filtros-superiores-container">
                <div class="filtro-item">
                    <label for="filtro_rol" class="form-label">Rol:</label>
                    <select id="filtro_rol" name="filtro_rol" class="form-select form-select-sm filtro-rol-select">
                        <option value="">-- Todos los Roles --</option>
                        <?php foreach ($roles_disponibles as $rol) : ?>
                            <option value="<?php echo htmlspecialchars($rol['id_rol']); ?>" <?php echo ($filtro_rol_seleccionado == $rol['id_rol']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtro-item filtro-item-expandir">
                    <label for="buscador_documento" class="form-label">Buscar por Documento:</label>
                    <input type="text" id="buscador_documento" class="form-control form-control-sm" placeholder="Escriba para filtrar documento...">
                </div>
            </div>

            <?php if (empty($error_db)): ?>
                <div class="tabla-container flex-grow-1">
                    <table class="tabla-usuarios tabla-estilo-alternado">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Nombre</th>
                                <th>F. Nac.</th>
                                <th>Teléfono</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th class="columna-acciones">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla_usuarios_body">
                            <?php if (!empty($usuarios)) : ?>
                                <?php foreach ($usuarios as $usuario) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($usuario['doc_usu']); ?></td>
                                        <td><span class="truncate-text" title="<?php echo htmlspecialchars($usuario['nom_usu']); ?>"><?php echo htmlspecialchars($usuario['nom_usu']); ?></span></td>
                                        <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($usuario['fecha_nac'] ?? ''))); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['tel_usu'] ?? 'N/A'); ?></td>
                                        <td><span class="truncate-text" title="<?php echo htmlspecialchars($usuario['correo_usu']); ?>"><?php echo htmlspecialchars($usuario['correo_usu'] ?? 'N/A'); ?></span></td>
                                        <td><span class="badge rounded-pill bg-primary"><?php echo htmlspecialchars($usuario['nombre_rol']); ?></span></td>
                                        <td class="acciones-tabla">
                                            <button class="btn btn-success btn-sm btn-editar-usuario"
                                                    data-doc-usu="<?php echo htmlspecialchars($usuario['doc_usu']); ?>"
                                                    title="Editar Usuario">
                                                <i class="bi bi-pencil-square"></i>
                                                <span>Editar</span>
                                            </button>
                                            <button class="btn btn-danger btn-sm btn-eliminar"
                                                    data-id="<?php echo htmlspecialchars($usuario['doc_usu']); ?>"
                                                    data-tipodoc="<?php echo htmlspecialchars($usuario['id_tipo_doc']); ?>"
                                                    data-nombre="<?php echo htmlspecialchars($usuario['nom_usu']); ?>"
                                                    data-tipo="usuario"
                                                    data-estado-afiliacion="<?php echo htmlspecialchars($usuario['estado_afiliacion'] ?? ''); ?>"
                                                    title="Eliminar Usuario">
                                                <i class="bi bi-trash3"></i>
                                                <span>Eliminar</span>
                                            </button>
                                            <?php
                                            $estado_afiliacion = $usuario['estado_afiliacion'] ?? null;
                                            if (is_null($estado_afiliacion) || $estado_afiliacion == 2) {
                                            ?>
                                                <a href="afiliacion.php?doc_usu=<?php echo htmlspecialchars($usuario['doc_usu']); ?>&id_tipo_doc=<?php echo htmlspecialchars($usuario['id_tipo_doc']); ?>&nom_usu=<?php echo urlencode($usuario['nom_usu']); ?>"
                                                   class="btn btn-info btn-sm accion-afiliar"
                                                   title="Afiliar Usuario">
                                                    <i class="bi bi-person-plus-fill"></i>
                                                    <span>Afiliar</span>
                                                </a>
                                            <?php
                                            } elseif ($estado_afiliacion == 1) { 
                                            ?>
                                                <button class="btn btn-secondary btn-sm accion-afiliado" disabled title="Usuario Afiliado Activo">
                                                    <i class="bi bi-person-check-fill"></i>
                                                    <span>Afiliado</span>
                                                </button>
                                            <?php
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr id="fila_no_usuarios_php"><td colspan="7" class="text-center"><?php echo (!empty($filtro_rol_seleccionado) || !empty($doc_admin_actual_session) ) ? 'No hay usuarios que coincidan con los filtros.' : 'No hay usuarios registrados.'; ?></td></tr>
                            <?php endif; ?>
                            <tr id="fila_no_encontrado_js" style="display: none;"><td colspan="7" class="text-center">No se encontraron usuarios con ese documento.</td></tr>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_registros > 0 && $total_paginas > 1): ?>
                <nav aria-label="Paginación de usuarios" class="mt-3 paginacion-abajo">
                    <ul class="pagination justify-content-center pagination-sm">
                        <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>&filtro_rol=<?php echo urlencode($filtro_rol_seleccionado); ?>"><i class="bi bi-chevron-left"></i></a></li>
                        <li class="page-item active" id="page-number-container"><span class="page-link page-number-display" title="Click/Enter para ir"><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></span><input type="number" class="form-control form-control-sm page-number-input-field" value="<?php echo $pagina_actual; ?>" min="1" max="<?php echo $total_paginas; ?>" style="display: none;" data-total="<?php echo $total_paginas; ?>"></li>
                         <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>"><a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>&filtro_rol=<?php echo urlencode($filtro_rol_seleccionado); ?>"><i class="bi bi-chevron-right"></i></a></li>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
            
        </div>
    </main>
    <?php include '../include/footer.php'; ?>

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

    <script src="../js/bootstrap.bundle.min.js"></script>
    <script src="../js/editar_usuario_admin.js?v=<?php echo time(); ?>"></script>
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
                    const estadoAfiliacion = this.dataset.estadoAfiliacion;

                    if (estadoAfiliacion === '1') {
                        tituloAlerta.textContent = 'Eliminación no permitida';
                        msgAlerta.textContent = `El usuario ${n} (Documento: ${id}) tiene una afiliación activa y no puede ser eliminado. Sus datos están relacionados en otras tablas.`;
                        modalAlerta.style.display = 'flex';
                    } else {
                        msgConf.textContent = `¿Eliminar a ${n} (Documento: ${id})? Esto también podría afectar registros relacionados si existen.`;
                        modalConfirm.style.display = 'flex';
                        formDel = document.createElement('form');
                        formDel.method = 'POST';
                        formDel.action = 'eliminar_registro.php';
                        const iId = document.createElement('input'); iId.type = 'hidden'; iId.name = 'id_registro'; iId.value = id; formDel.appendChild(iId);
                        if (td) { const iTD = document.createElement('input'); iTD.type = 'hidden'; iTD.name = 'id_tipo_doc'; iTD.value = td; formDel.appendChild(iTD); }
                        const iT = document.createElement('input'); iT.type = 'hidden'; iT.name = 'tipo_registro'; iT.value = t; formDel.appendChild(iT);
                        const iTk = document.createElement('input'); iTk.type = 'hidden'; iTk.name = 'csrf_token'; iTk.value = '<?php echo $csrf_token; ?>'; formDel.appendChild(iTk);
                        document.body.appendChild(formDel);
                    }
                });
            });

            btnConf.addEventListener('click', () => { if (formDel) formDel.submit(); });
            btnCanc.addEventListener('click', () => { modalConfirm.style.display = 'none'; if (formDel && formDel.parentNode) formDel.parentNode.removeChild(formDel); formDel = null; });
            btnAceptarAlerta.addEventListener('click', () => { modalAlerta.style.display = 'none'; });

            window.addEventListener('click', (e) => {
                if (e.target == modalConfirm) btnCanc.click();
                if (e.target == modalAlerta) modalAlerta.style.display = 'none';
            });

            const pageContainer = document.getElementById('page-number-container'); const pageIS = pageContainer?.querySelector('.page-number-display'); const pageIF = pageContainer?.querySelector('.page-number-input-field'); if(pageIS && pageIF){ pageIS.addEventListener('click', () => { pageIS.style.display = 'none'; pageIF.style.display = 'inline-block'; pageIF.focus(); pageIF.select(); }); const goPg = () => { const tp = parseInt(pageIF.dataset.total, 10) || 1; let tgPg = parseInt(pageIF.value, 10); if (isNaN(tgPg) || tgPg < 1) tgPg = 1; else if (tgPg > tp) tgPg = tp; const curl = new URL(window.location.href); curl.searchParams.set('pagina', tgPg); window.location.href = curl.toString(); }; const hideInput = () => { const totalPgs = parseInt(pageIF.dataset.total, 10) || 1; pageIS.textContent = pageIF.value + ' / ' + totalPgs; pageIS.style.display = 'inline-block'; pageIF.style.display = 'none'; }; pageIF.addEventListener('blur', () => { setTimeout(hideInput, 150); }); pageIF.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); goPg(); } else if (e.key === 'Escape'){ pageIF.value = <?php echo $pagina_actual; ?>; hideInput(); } }); }
            const filtroRolSelect = document.getElementById('filtro_rol'); if(filtroRolSelect){ filtroRolSelect.addEventListener('change', function() { const selectedRol = this.value; const currentUrl = new URL(window.location.href); currentUrl.searchParams.set('filtro_rol', selectedRol); currentUrl.searchParams.set('pagina', '1'); window.location.href = currentUrl.toString(); }); }
            const buscadorDocumentoInput = document.getElementById('buscador_documento'); const tablaUsuariosBody = document.getElementById('tabla_usuarios_body'); const filaNoEncontradoJS = document.getElementById('fila_no_encontrado_js'); const filaNoUsuariosPHP = document.getElementById('fila_no_usuarios_php'); const filasDeDatosOriginales = tablaUsuariosBody ? Array.from(tablaUsuariosBody.querySelectorAll('tr:not(#fila_no_encontrado_js):not(#fila_no_usuarios_php)')) : [];
            if (buscadorDocumentoInput && tablaUsuariosBody && filaNoEncontradoJS) { buscadorDocumentoInput.addEventListener('input', function() { const terminoBusqueda = this.value.toLowerCase().trim(); let filasVisibles = 0; if (filaNoUsuariosPHP && filasDeDatosOriginales.length > 0) { filaNoUsuariosPHP.style.display = 'none'; } if (terminoBusqueda === "") { filasDeDatosOriginales.forEach(fila => { fila.style.display = ''; }); filaNoEncontradoJS.style.display = 'none'; if (filaNoUsuariosPHP && filasDeDatosOriginales.length === 0) { filaNoUsuariosPHP.style.display = ''; } else if (filaNoUsuariosPHP && filasDeDatosOriginales.length > 0) { filaNoUsuariosPHP.style.display = 'none'; } } else { if (filasDeDatosOriginales.length > 0) { filasDeDatosOriginales.forEach(fila => { const celdaDocumento = fila.cells[0]; if (celdaDocumento) { const textoDocumento = celdaDocumento.textContent.toLowerCase().trim(); if (textoDocumento.startsWith(terminoBusqueda)) { fila.style.display = ''; filasVisibles++; } else { fila.style.display = 'none'; } } }); } if (filasVisibles === 0) { filaNoEncontradoJS.style.display = ''; if (filaNoUsuariosPHP) { filaNoUsuariosPHP.style.display = 'none'; } } else { filaNoEncontradoJS.style.display = 'none'; } } }); if (filasDeDatosOriginales.length === 0 && filaNoUsuariosPHP) {  } }
            const modalEditUserContainer = document.getElementById('editarUsuarioModalPlaceholder');
            let currentEditModalInstance = null;
            document.querySelectorAll('.btn-editar-usuario').forEach(button => {
                button.addEventListener('click', function() {
                    const docUsu = this.dataset.docUsu;
                    modalEditUserContainer.innerHTML = '<div class="d-flex justify-content-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
                    fetch(`modal_editar_usuario.php?doc_usu_editar=${encodeURIComponent(docUsu)}`)
                        .then(response => {
                            if (!response.ok) throw new Error(`HTTP error! status: ${response.status} ${response.statusText}`);
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
                                modalEditUserContainer.innerHTML = '<div class="alert alert-danger">Error: #editUserModal no encontrado.</div>';
                            }
                        })
                        .catch(error => {
                            modalEditUserContainer.innerHTML = `<div class="alert alert-danger">No se pudo cargar el contenido: ${error.message}.</div>`;
                        });
                });
            });
        });
    </script>
</body>
</html>