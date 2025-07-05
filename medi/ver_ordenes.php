<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol'])) {
    header('Location: ../inicio_sesion.php');
    exit;
}

$pageTitle = "Detalles de Historia Clínica";
$db = new Database();
$pdo = $db->conectar();

// --- CONFIGURACIÓN ---

// Define cuántos registros se mostrarán por página
define('REGISTROS_POR_PAGINA_DETALLES', 5);

// Obtiene el número de página actual desde la URL, o usa 1 por defecto
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

// Asegura que la página actual no sea menor que 1
if ($pagina_actual < 1) $pagina_actual = 1;

// Captura el término de búsqueda desde la URL, si existe
$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// --- LÓGICA DE BASE DE DATOS --- //
// Arma la parte base del query con las uniones necesarias entre tablas
$sql_base = "FROM detalles_histo_clini dh
             INNER JOIN historia_clinica hc ON dh.id_historia = hc.id_historia
             INNER JOIN citas ci ON hc.id_cita = ci.id_cita         
             INNER JOIN usuarios u_pac ON ci.doc_pac = u_pac.doc_usu 
             LEFT JOIN diagnostico d ON dh.id_diagnostico = d.id_diagnos
             LEFT JOIN enfermedades e ON dh.id_enferme = e.id_enferme
             LEFT JOIN medicamentos m ON dh.id_medicam = m.id_medicamento
             LEFT JOIN procedimientos p ON dh.id_proced = p.id_proced";

// Inicializa cláusulas WHERE y parámetros del query
$where_clauses = [];
$query_params = [];

// Si se ingresó una búsqueda, agrega condiciones dinámicas con LIKE
if ($busqueda != '') {
    $likeBusqueda = "%$busqueda%";
    $where_clauses[] = "(
        u_pac.nom_usu LIKE ? OR u_pac.doc_usu LIKE ? OR d.diagnostico LIKE ? OR
        e.nom_enfer LIKE ? OR m.nom_medicamento LIKE ? OR p.procedimiento LIKE ? OR
        dh.prescripcion LIKE ? OR dh.id_detalle LIKE ? OR hc.id_historia LIKE ?
    )";

    // Agrega el mismo patrón de búsqueda 9 veces (uno por cada campo)
    for ($i = 0; $i < 9; $i++) {
        $query_params[] = $likeBusqueda;
    }
}

// Une las cláusulas WHERE si hay alguna
$final_where_sql = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Construye el query para contar cuántos registros totales hay (para la paginación)
$count_sql = "SELECT COUNT(dh.id_detalle) " . $sql_base . $final_where_sql;
$stmt_count = $pdo->prepare($count_sql);
$stmt_count->execute($query_params);
$total_registros = (int)$stmt_count->fetchColumn();

// Calcula el número total de páginas
$total_paginas = ceil($total_registros / REGISTROS_POR_PAGINA_DETALLES);

// Asegura que la página actual no supere el máximo
if ($pagina_actual > $total_paginas && $total_paginas > 0) {
    $pagina_actual = $total_paginas;
}

// Recalcula offset: cuántos registros se deben saltar para esta página
$offset = ($pagina_actual - 1) * REGISTROS_POR_PAGINA_DETALLES;

// Arma el query final para obtener los datos paginados
$sql_data = "SELECT 
                dh.id_detalle, 
                hc.id_historia, 
                u_pac.nom_usu AS nombre_paciente, 
                u_pac.doc_usu AS documento_paciente, 
                d.diagnostico, 
                e.nom_enfer,
                m.nom_medicamento, 
                dh.can_medica, 
                p.procedimiento,
                dh.cant_proced, 
                dh.prescripcion " 
            . $sql_base . $final_where_sql 
            . " ORDER BY dh.id_detalle DESC LIMIT ? OFFSET ?";

// Se agregan los parámetros del LIMIT y OFFSET al final
$data_query_params = $query_params;
$data_query_params[] = REGISTROS_POR_PAGINA_DETALLES; // Límite
$data_query_params[] = $offset;                        // Desde dónde empezar

// Prepara y ejecuta el query final con los datos
$stmt_data = $pdo->prepare($sql_data);
$param_index = 1;

// Asocia cada parámetro al placeholder correspondiente (STR o INT según corresponda)
foreach ($data_query_params as $param_value) {
    if ($param_index > count($query_params)) {
        // Los últimos dos parámetros son enteros (LIMIT, OFFSET)
        $stmt_data->bindValue($param_index, $param_value, PDO::PARAM_INT);
    } else {
        // Los primeros son cadenas de texto (para los LIKE)
        $stmt_data->bindValue($param_index, $param_value, PDO::PARAM_STR);
    }
    $param_index++;
}

// Ejecuta el query y obtiene todos los resultados
$stmt_data->execute();
$detalles = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        /* Estilos de la tabla y responsivos (SIN CAMBIOS) */
        body { background-color: #f8f9fa; }
        .page-container-table { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
        .table-detalles thead th { background-color: #0d6efd; color: white; text-align: center; position: sticky; top: 0; z-index: 10; }
        .table-detalles td, .table-detalles th { font-size: 0.85rem; padding: 0.5rem; vertical-align: middle; }
        .container-ver-ordenes { max-width: 1600px; }
        @media screen and (max-width: 992px) {
            .table-responsive-cards thead { display: none; }
            .table-responsive-cards tbody, .table-responsive-cards tr, .table-responsive-cards td { display: block; width: 100%; }
            .table-responsive-cards tr { margin-bottom: 1.5rem; border: 1px solid #dee2e6; border-radius: 0.375rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
            .table-responsive-cards td { text-align: right; padding-left: 45%; position: relative; border: none; border-bottom: 1px solid #f0f0f0; min-height: 40px; display: flex; align-items: center; justify-content: flex-end; }
            .table-responsive-cards td:before { content: attr(data-label); position: absolute; left: 0.75rem; width: calc(45% - 1.5rem); white-space: nowrap; text-align: left; font-weight: bold; color: #0d6efd; }
            .table-responsive-cards td:last-child { border-bottom: 0; }
        }

        /* ========================================================== */
        /* ==   NUEVOS ESTILOS PARA LA PAGINACIÓN COMPACTA         == */
        /* ========================================================== */
        .pagination-compact {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 1.5rem;
        }
        .pagination-compact .page-link {
            border-radius: 50% !important;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 5px;
            border-color: #dee2e6;
            color: #0d6efd;
        }
        .pagination-compact .page-item.disabled .page-link {
            background-color: #e9ecef;
            border-color: #dee2e6;
        }
        .pagination-compact .page-counter {
            margin: 0 1rem;
            font-weight: 500;
            color: #495057;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<?php include '../include/menu.php'; ?>

    <div class="container mt-4 container-ver-ordenes"> 
        <div class="page-container-table"> 
            <!-- ... (código del encabezado y formulario de búsqueda SIN CAMBIOS) ... -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                 <a href="citas_hoy.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Volver</a>
                <h2 class="mb-0 text-center flex-grow-1 fs-4"><?php echo htmlspecialchars($pageTitle); ?></h2>
                <div style="width:90px;"></div> 
            </div>
            <form method="GET" action="" class="mb-3"> 
                <div class="input-group input-group-sm">
                    <input type="text" name="buscar" class="form-control" placeholder="Buscar en detalles..." value="<?= htmlspecialchars($busqueda) ?>">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    <?php if ($busqueda != ''): ?>
                        <a href="ver_ordenes.php" class="btn btn-outline-secondary">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>
            
            <?php if (empty($detalles)): ?>
                <div class="alert alert-info text-center">No hay detalles para mostrar <?= ($busqueda != '') ? 'con el criterio "' . htmlspecialchars($busqueda) . '"' : '' ?>.</div>
            <?php else: ?>
                <div class="table-container"> 
                    <table class="table table-bordered table-hover table-detalles table-responsive-cards"> 
                        <!-- ... (código de la tabla SIN CAMBIOS) ... -->
                        <thead> 
                            <tr>
                                <th>ID Det.</th><th>ID Hist.</th><th>Paciente</th><th>Documento</th><th>Diagnóstico</th>
                                <th>Enfermedad</th><th>Medicamento</th><th>Cant. Med.</th><th>Procedimiento</th>
                                <th>Cant. Proc.</th><th>Prescripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $fila): ?>
                                <tr>
                                    <td data-label="ID Detalle:" class="text-center"><?= htmlspecialchars($fila['id_detalle']) ?></td>
                                    <td data-label="ID Historia:" class="text-center"><?= htmlspecialchars($fila['id_historia']) ?></td>
                                    <td data-label="Paciente:"><?= htmlspecialchars($fila['nombre_paciente']) ?></td>
                                    <td data-label="Documento:" class="text-center"><?= htmlspecialchars($fila['documento_paciente']) ?></td>
                                    <td data-label="Diagnóstico:"><?= htmlspecialchars($fila['diagnostico'] ?? 'N/A') ?></td>
                                    <td data-label="Enfermedad:"><?= htmlspecialchars($fila['nom_enfer'] ?? 'N/A') ?></td>
                                    <td data-label="Medicamento:"><?= htmlspecialchars($fila['nom_medicamento'] ?? 'N/A') ?></td>
                                    <td data-label="Cant. Med.:" class="text-center"><?= htmlspecialchars($fila['can_medica'] ?? 'N/A') ?></td>
                                    <td data-label="Procedimiento:"><?= htmlspecialchars($fila['procedimiento'] ?? 'N/A') ?></td>
                                    <td data-label="Cant. Proc.:" class="text-center"><?= htmlspecialchars($fila['cant_proced'] ?? 'N/A') ?></td>
                                    <td data-label="Prescripción:"><?= nl2br(htmlspecialchars($fila['prescripcion'] ?? 'N/A')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ========================================================== -->
                <!-- ==        BLOQUE DE PAGINACIÓN COMPACTA (NUEVO)         == -->
                <!-- ========================================================== -->
                <?php if ($total_paginas > 0): ?>
                    <div class="pagination-compact">
                        <ul class="pagination mb-0">
                            <li class="page-item <?= ($pagina_actual <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $pagina_actual - 1 ?>&buscar=<?= urlencode($busqueda) ?>" aria-label="Anterior">
                                    <span><</span>
                                </a>
                            </li>
                        </ul>
                        <span class="page-counter">
                            <?= $pagina_actual ?> / <?= $total_paginas > 0 ? $total_paginas : 1 ?>
                        </span>
                        <ul class="pagination mb-0">
                            <li class="page-item <?= ($pagina_actual >= $total_paginas) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?pagina=<?= $pagina_actual + 1 ?>&buscar=<?= urlencode($busqueda) ?>" aria-label="Siguiente">
                                    <span>></span>
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>

    <?php include '../include/footer.php'; ?>
</body>
</html>