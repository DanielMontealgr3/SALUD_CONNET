<?php
require_once '../../../include/validar_sesion.php';
require_once '../../../include/inactividad.php';
require_once '../../../include/conexion.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) { header('Location: ../../inicio_sesion.php'); exit; }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf_token = $_SESSION['csrf_token'];

$conex = new database(); 
$con = $conex->conectar();
$error_db = '';
$titulo_tabla = 'Departamentos';
$datos_vista = [];

$registros_por_pagina = 5;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;

$filtro_orden = trim($_GET['orden'] ?? 'nombre_asc');
$filtro_nombre = trim($_GET['filtro_nombre'] ?? '');

$columnas_tabla = ['ID', 'Nombre', 'Acciones'];
$total_registros = 0;
$total_paginas = 1;

if ($con) {
    try {
        $params_query = [];
        $where_clauses = [];

        if (!empty($filtro_nombre)) {
            $where_clauses[] = "nom_dep LIKE :nombre_filtro";
            $params_query[':nombre_filtro'] = "%" . $filtro_nombre . "%";
        }
        
        $sql_where = "";
        if (!empty($where_clauses)) {
            $sql_where = " WHERE " . implode(" AND ", $where_clauses);
        }

        $stmt_total = $con->prepare("SELECT COUNT(*) FROM departamento" . $sql_where);
        $stmt_total->execute($params_query);
        $total_registros = (int)$stmt_total->fetchColumn();

        if ($total_registros > 0) {
            $total_paginas = ceil($total_registros / $registros_por_pagina);
            if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;
            if ($total_paginas == 0) $total_paginas = 1;
            $offset = ($pagina_actual - 1) * $registros_por_pagina;

            $sql_order_by = " ORDER BY nom_dep ASC"; 
            if ($filtro_orden === 'nombre_desc') {
                $sql_order_by = " ORDER BY nom_dep DESC";
            } elseif ($filtro_orden === 'recientes') {
                $sql_order_by = " ORDER BY id_dep DESC"; 
            }

            $sql_select = "SELECT id_dep, nom_dep FROM departamento" . $sql_where . $sql_order_by . " LIMIT :limit OFFSET :offset_val";
            $stmt_select = $con->prepare($sql_select);
            foreach($params_query as $key => $val){ $stmt_select->bindValue($key, $val); }
            $stmt_select->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
            $stmt_select->bindParam(':offset_val', $offset, PDO::PARAM_INT);
            $stmt_select->execute();
            $datos_vista = $stmt_select->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $datos_vista = [];
            $total_paginas = 1;
        }

    } catch (PDOException $e) {
        $error_db = "Error al consultar la base de datos: " . $e->getMessage();
        error_log("PDO Ver Departamentos: ".$e->getMessage());
        $datos_vista = [];
    }
} else {
    $error_db = "Error de conexión a la base de datos.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../../img/loguito.png">
    <title>Ver Departamentos - Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../../css/estilos_form.css"> 
    <link rel="stylesheet" href="../../../css/estilos_tabla.css">
    <style>
        .tabla-admin-mejorada .acciones-header,
        .tabla-admin-mejorada .acciones-celda {
            width: 190px; /* Ajusta si es necesario para tus botones */
            text-align: right;
            padding-right: 1rem; /* Añade un poco de padding para que no estén pegados al borde */
        }
    </style>
</head>
<body class="d-flex flex-column">
    <?php include '../../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <input type="hidden" id="csrf_token_global" value="<?php echo htmlspecialchars($csrf_token); ?>">
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
                 <?php if (isset($_GET['mensaje_exito'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['mensaje_exito']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_db)) : ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_db); ?></div>
                <?php endif; ?>

                <form method="GET" action="ver_departamentos.php" class="mb-4 filtros-tabla-container">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="filtro_nombre" class="form-label">Buscar por Nombre:</label>
                            <input type="text" name="filtro_nombre" id="filtro_nombre" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtro_nombre); ?>" placeholder="Nombre del departamento...">
                        </div>
                        <div class="col-md-3">
                            <label for="orden" class="form-label">Ordenar por:</label>
                            <select name="orden" id="orden" class="form-select form-select-sm">
                                <option value="nombre_asc" <?php echo ($filtro_orden == 'nombre_asc') ? 'selected' : ''; ?>>Nombre (A-Z)</option>
                                <option value="nombre_desc" <?php echo ($filtro_orden == 'nombre_desc') ? 'selected' : ''; ?>>Nombre (Z-A)</option>
                                <option value="recientes" <?php echo ($filtro_orden == 'recientes') ? 'selected' : ''; ?>>Más Recientes</option>
                            </select>
                        </div>
                        <div class="col-md-auto">
                             <button type="submit" class="btn btn-sm btn-filtrar-tabla w-100">Filtrar <i class="bi bi-search"></i></button>
                        </div>
                        <div class="col-md-auto">
                            <a href="ver_departamentos.php" class="btn btn-sm btn-limpiar-filtros-tabla w-100">Limpiar <i class="bi bi-eraser"></i></a>
                        </div>
                         <div class="col-md-auto ms-auto">
                            <a href="crear_departamento.php?origen=ver_departamentos" class="btn btn-sm btn-success">Nuevo Departamento <i class="bi bi-plus-circle"></i></a>
                        </div>
                    </div>
                </form>

                <?php if (empty($error_db)): ?>
                    <div class="table-responsive">
                        <table class="tabla-admin-mejorada table">
                            <thead>
                                <tr>
                                    <th><?php echo htmlspecialchars($columnas_tabla[0]); ?></th>
                                    <th><?php echo htmlspecialchars($columnas_tabla[1]); ?></th>
                                    <th class="acciones-header"><?php echo htmlspecialchars($columnas_tabla[2]); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($datos_vista)) : ?>
                                    <?php foreach ($datos_vista as $item) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['id_dep'] ?? 'N/A'); ?></td>
                                            <td><span class="truncate-text" title="<?php echo htmlspecialchars($item['nom_dep'] ?? ''); ?>"><?php echo htmlspecialchars($item['nom_dep'] ?? 'N/A'); ?></span></td>
                                            <td class="acciones-celda">
                                                <button class="btn btn-success btn-sm btn-editar-geografica" 
                                                        data-id="<?php echo htmlspecialchars($item['id_dep']); ?>" 
                                                        data-tipo="departamento"
                                                        title="Editar">
                                                    <i class="bi bi-pencil-square"></i> <span>Editar</span>
                                                </button>
                                                <button class="btn btn-danger btn-sm btn-eliminar-geografica" 
                                                        data-id="<?php echo htmlspecialchars($item['id_dep']); ?>" 
                                                        data-nombre="<?php echo htmlspecialchars($item['nom_dep'] ?? ''); ?>" 
                                                        data-tipo="departamento" title="Eliminar">
                                                    <i class="bi bi-trash3"></i> <span>Eliminar</span>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                     <tr><td colspan="<?php echo count($columnas_tabla); ?>" class="text-center">
                                        No hay departamentos que coincidan con los filtros aplicados.
                                    </td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_registros > 0 && $total_paginas > 1): ?>
                    <nav aria-label="Paginación" class="mt-3 paginacion-tabla-container">
                        <ul class="pagination pagination-sm justify-content-center">
                             <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>&filtro_nombre=<?php echo urlencode($filtro_nombre); ?>&orden=<?php echo urlencode($filtro_orden); ?>" aria-label="Anterior"> <i class="bi bi-chevron-left"></i></a>
                            </li>
                             <li class="page-item active" id="page-number-container">
                               <span class="page-number-display" title="Click/Enter para ir a pág."><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></span>
                               <input type="number" class="form-control form-control-sm page-number-input-field" value="<?php echo $pagina_actual; ?>" min="1" max="<?php echo $total_paginas; ?>" style="display: none;" data-total="<?php echo $total_paginas; ?>">
                             </li>
                            <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>&filtro_nombre=<?php echo urlencode($filtro_nombre); ?>&orden=<?php echo urlencode($filtro_orden); ?>" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php include '../../../include/footer.php'; ?>

    <div id="modalEditarGeograficaContainer"></div>
    <div class="modal-confirmacion" id="modalConfirmacionEliminarGeografica" style="display:none;"><div class="modal-contenido"><h4>Confirmar Eliminación</h4><p id="mensajeConfirmacionGeografica">¿Está seguro?</p><div class="modal-botones"><button id="btnConfirmarEliminacionGeografica" class="btn btn-danger">Eliminar</button><button id="btnCancelarEliminacionGeografica" class="btn btn-secondary">Cancelar</button></div></div></div>

    <script src="../../js/gestion_geografica.js?v=<?php echo time(); ?>"></script>
</body>
</html>