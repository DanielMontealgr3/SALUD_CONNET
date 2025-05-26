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
$pacientes_list = [];
$eps_para_filtro = [];
$error_db = '';

$filtro_nit_eps_seleccionado = trim($_GET['filtro_eps'] ?? '');
$filtro_doc_paciente = trim($_GET['filtro_doc_paciente'] ?? '');

$registros_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$total_registros = 0; $total_paginas = 1;

if ($con) {
    try {
        $stmt_eps_filter_list = $con->query("
            SELECT DISTINCT eps.nit_eps, eps.nombre_eps
            FROM eps
            INNER JOIN afiliados a ON eps.nit_eps = a.id_eps
            INNER JOIN usuarios u ON a.doc_afiliado = u.doc_usu
            WHERE u.id_rol = 2 AND a.id_estado = 1
            ORDER BY eps.nombre_eps ASC
        ");
        $eps_para_filtro = $stmt_eps_filter_list->fetchAll(PDO::FETCH_ASSOC);

        $sql_base_from = "FROM usuarios u
                          LEFT JOIN afiliados af ON u.doc_usu = af.doc_afiliado AND af.id_estado = 1 AND af.id_eps IS NOT NULL
                          LEFT JOIN eps e ON af.id_eps = e.nit_eps";

        $sql_where_clauses = ["u.id_rol = 2"]; 
        $params_php = [];
        
        if (!empty($filtro_doc_paciente)) {
            $sql_where_clauses[] = "u.doc_usu LIKE :doc_paciente_filtro";
            $params_php[':doc_paciente_filtro'] = "%" . $filtro_doc_paciente . "%";
        }

        if (!empty($filtro_nit_eps_seleccionado)) {
            if ($filtro_nit_eps_seleccionado === 'sin_afiliacion_activa') {
                $sql_where_clauses[] = "af.id_afiliacion IS NULL";
            } else {
                $sql_where_clauses[] = "af.id_eps = :nit_eps_filtro";
                $params_php[':nit_eps_filtro'] = $filtro_nit_eps_seleccionado;
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

            $sql_pacientes = "SELECT u.doc_usu, u.id_tipo_doc, u.nom_usu, u.tel_usu, u.correo_usu,
                                   e.nombre_eps, 
                                   (SELECT af_check.id_estado FROM afiliados af_check WHERE af_check.doc_afiliado = u.doc_usu AND af_check.id_eps IS NOT NULL ORDER BY af_check.id_estado ASC, af_check.fecha_afi DESC LIMIT 1) AS estado_ultima_afiliacion_eps
                             $sql_base_from
                             $sql_where
                             GROUP BY u.doc_usu, u.id_tipo_doc, u.nom_usu, u.tel_usu, u.correo_usu, e.nombre_eps
                             ORDER BY u.nom_usu ASC
                             LIMIT :limit OFFSET :offset_val";

            $stmt_pacientes = $con->prepare($sql_pacientes);
            foreach ($params_php as $key => $value) {
                $stmt_pacientes->bindValue($key, $value);
            }
            $stmt_pacientes->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
            $stmt_pacientes->bindParam(':offset_val', $offset, PDO::PARAM_INT);
            $stmt_pacientes->execute();
            $pacientes_list = $stmt_pacientes->fetchAll(PDO::FETCH_ASSOC);
        } else { $pacientes_list = []; $total_paginas = 1; $pagina_actual = 1; }
    } catch (PDOException $e) { $error_db = "Error al consultar la base de datos: " . $e->getMessage(); error_log("PDO Lista Pacientes: ".$e->getMessage()); }
} else { $error_db = "Error de conexión a la base de datos."; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="../img/loguito.png">
    <title>SaludConnect - Lista de Pacientes</title>
    <style>
        .btn-group-acciones .btn { margin-right: 5px; margin-bottom: 5px; display: inline-flex; align-items: center; }
        .btn-group-acciones .btn i { margin-right: 4px;}
        .btn-group-acciones .btn:last-child { margin-right: 0; }
        .btn-afiliado-custom-disabled {
            background-color: #B0BEC5; /* Un gris más claro y plano, similar a la imagen */
            border-color: #B0BEC5;
            color: #FFFFFF; 
            pointer-events: none; 
            opacity: 1; 
            box-shadow: none; /* Quitar sombra para un look más plano */
        }
        .btn-afiliado-custom-disabled:hover {
             background-color: #B0BEC5;
             border-color: #B0BEC5;
             color: #FFFFFF;
        }
        .eps-nombre-texto {
            color: #212529; 
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <h3 class="titulo-lista-tabla">Lista de Pacientes</h3>

                <?php if (isset($_SESSION['mensaje_accion']) || isset($_GET['afiliacion_exitosa_paciente'])): ?>
                    <div class="alert alert-<?php 
                        if(isset($_GET['afiliacion_exitosa_paciente'])) {
                            echo 'success'; 
                        } else {
                            echo htmlspecialchars($_SESSION['mensaje_accion_tipo'] ?? 'info'); 
                        }
                    ?> alert-dismissible fade show" role="alert">
                        <?php 
                            if(isset($_GET['afiliacion_exitosa_paciente']) && isset($_GET['msg'])) {
                                echo htmlspecialchars(urldecode($_GET['msg']));
                            } elseif (isset($_SESSION['mensaje_accion'])) {
                                echo htmlspecialchars($_SESSION['mensaje_accion'] ?? '');
                            }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['mensaje_accion']); unset($_SESSION['mensaje_accion_tipo']); ?>
                <?php endif; ?>

                <?php if (!empty($error_db)) : ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_db); ?></div><?php endif; ?>
                
                <form method="GET" action="lista_pacientes.php" class="mb-4 filtros-tabla-container">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="filtro_eps" class="form-label">Filtrar por EPS:</label>
                            <select name="filtro_eps" id="filtro_eps" class="form-select form-select-sm">
                                <option value="">-- Todas las EPS / Afiliaciones --</option>
                                <option value="sin_afiliacion_activa" <?php echo ($filtro_nit_eps_seleccionado === 'sin_afiliacion_activa') ? 'selected' : ''; ?>>-- Pacientes Sin Afiliación Activa --</option>
                                <?php if (!empty($eps_para_filtro)): ?>
                                    <?php foreach ($eps_para_filtro as $eps_item): ?>
                                        <option value="<?php echo htmlspecialchars($eps_item['nit_eps']); ?>" <?php echo ($filtro_nit_eps_seleccionado == $eps_item['nit_eps'] && $filtro_nit_eps_seleccionado !== 'sin_afiliacion_activa') ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($eps_item['nombre_eps']); ?> (<?php echo htmlspecialchars($eps_item['nit_eps']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                     <option value="" disabled>No hay EPS con pacientes para filtrar</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="filtro_doc_paciente" class="form-label">Buscar Paciente por Documento:</label>
                            <input type="text" id="filtro_doc_paciente" name="filtro_doc_paciente" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtro_doc_paciente); ?>" placeholder="Escriba el documento...">
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
                                    <th>EPS Actual</th>
                                    <th class="columna-acciones-tabla">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tabla_pacientes_body">
                                <?php if (!empty($pacientes_list)) : ?>
                                    <?php foreach ($pacientes_list as $paciente) : ?>
                                        <?php
                                            $url_actual_para_retorno = $_SERVER['REQUEST_URI'];
                                            $tiene_afiliacion_eps_activa = !empty($paciente['nombre_eps']) && ($paciente['estado_ultima_afiliacion_eps'] == 1);
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($paciente['doc_usu']); ?></td>
                                            <td><span class="truncate-text" title="<?php echo htmlspecialchars($paciente['nom_usu']); ?>"><?php echo htmlspecialchars($paciente['nom_usu']); ?></span></td>
                                            <td><?php echo htmlspecialchars($paciente['tel_usu'] ?? 'N/A'); ?></td>
                                            <td><span class="truncate-text" title="<?php echo htmlspecialchars($paciente['correo_usu']); ?>"><?php echo htmlspecialchars($paciente['correo_usu'] ?? 'N/A'); ?></span></td>
                                            <td class="eps-nombre-texto">
                                                <?php 
                                                    if ($tiene_afiliacion_eps_activa) {
                                                        echo htmlspecialchars($paciente['nombre_eps']);
                                                    } elseif (!empty($paciente['nombre_eps']) && $paciente['estado_ultima_afiliacion_eps'] == 2) {
                                                        echo htmlspecialchars($paciente['nombre_eps']) . ' (Inactiva)';
                                                    } else {
                                                        echo 'Sin EPS';
                                                    }
                                                ?>
                                            </td>
                                            <td class="acciones-tabla btn-group-acciones">
                                                <button class="btn btn-success btn-sm btn-editar-usuario"
                                                        data-doc-usu="<?php echo htmlspecialchars($paciente['doc_usu']); ?>"
                                                        title="Editar Paciente">
                                                    <i class="bi bi-pencil-square"></i><span>Editar</span>
                                                </button>
                                                <button class="btn btn-danger btn-sm btn-eliminar"
                                                        data-id="<?php echo htmlspecialchars($paciente['doc_usu']); ?>"
                                                        data-tipodoc="<?php echo htmlspecialchars($paciente['id_tipo_doc']); ?>"
                                                        data-nombre="<?php echo htmlspecialchars($paciente['nom_usu']); ?>"
                                                        data-tipo="usuario_paciente" 
                                                        data-afiliacion-activa="<?php echo $tiene_afiliacion_eps_activa ? '1' : '0'; ?>"
                                                        title="Eliminar Paciente">
                                                    <i class="bi bi-trash3"></i><span>Eliminar</span>
                                                </button>
                                                
                                                <?php if ($tiene_afiliacion_eps_activa): ?>
                                                    <button class="btn btn-secondary btn-sm accion-afiliado" disabled="" title="Farmaceuta ya Afiliado Activo">
                                                        <i class="bi bi-person-check-fill"></i><span>Afiliado</span>
                                                    </button>
                                                    <a href="afiliacion.php?doc_usu=<?php echo htmlspecialchars($paciente['doc_usu']); ?>&id_tipo_doc=<?php echo htmlspecialchars($paciente['id_tipo_doc']); ?>&nom_usu=<?php echo urlencode($paciente['nom_usu']); ?>&return_to=<?php echo urlencode($url_actual_para_retorno); ?>"
                                                       class="btn btn-warning btn-sm" 
                                                       title="Gestionar Afiliación / Cambiar EPS">
                                                        <i class="bi bi-arrow-repeat"></i><span>Gestionar EPS</span>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="afiliacion.php?doc_usu=<?php echo htmlspecialchars($paciente['doc_usu']); ?>&id_tipo_doc=<?php echo htmlspecialchars($paciente['id_tipo_doc']); ?>&nom_usu=<?php echo urlencode($paciente['nom_usu']); ?>&return_to=<?php echo urlencode($url_actual_para_retorno); ?>"
                                                       class="btn btn-primary btn-sm accion-afiliar" 
                                                       title="Afiliar Paciente a EPS">
                                                        <i class="bi bi-person-plus-fill"></i><span>Afiliar EPS</span>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr id="fila_no_pacientes_php"><td colspan="6" class="text-center">No hay pacientes registrados que coincidan con los filtros.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_registros > 0 && $total_paginas > 1): ?>
                    <nav aria-label="Paginación de pacientes" class="mt-3 paginacion-tabla-container">
                        <ul class="pagination pagination-sm">
                            <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>&filtro_eps=<?php echo urlencode($filtro_nit_eps_seleccionado); ?>&filtro_doc_paciente=<?php echo urlencode($filtro_doc_paciente); ?>"><i class="bi bi-chevron-left"></i></a>
                            </li>
                            <li class="page-item active" id="page-number-container">
                                <span class="page-link page-number-display" title="Click/Enter para ir a página"><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></span>
                                <input type="number" class="form-control form-control-sm page-number-input-field" value="<?php echo $pagina_actual; ?>" min="1" max="<?php echo $total_paginas; ?>" style="display: none;" data-total="<?php echo $total_paginas; ?>">
                            </li>
                            <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>&filtro_eps=<?php echo urlencode($filtro_nit_eps_seleccionado); ?>&filtro_doc_paciente=<?php echo urlencode($filtro_doc_paciente); ?>"><i class="bi bi-chevron-right"></i></a>
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
                    const afiliacionActiva = this.dataset.afiliacionActiva;

                    if (afiliacionActiva === '1') {
                        tituloAlerta.textContent = 'Eliminación no permitida';
                        msgAlerta.textContent = `El paciente ${n} (Doc: ${id}) tiene una afiliación a EPS activa. Debe inactivar su afiliación primero.`;
                        modalAlerta.style.display = 'flex';
                        return;
                    }

                    msgConf.innerHTML = `¿Eliminar al paciente <strong>${n}</strong> (Doc: ${id})? Esta acción no se puede deshacer.`;
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
            
            if (window.location.search.includes('afiliacion_exitosa_paciente=1')) {
                const url = new URL(window.location);
                url.searchParams.delete('afiliacion_exitosa_paciente');
                url.searchParams.delete('msg');
                window.history.replaceState({}, document.title, url);
            }
        });
    </script>
</body>
</html>