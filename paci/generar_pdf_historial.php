<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once '../include/conexion.php';

$conex = new Database();
$con = $conex->conectar();
$doc_usuario = $_SESSION['doc_usu'];

// Obtener lista de especialidades disponibles
$stmt_especialidades = $con->prepare("SELECT id_espe, nom_espe FROM especialidad WHERE id_espe != 46 ORDER BY nom_espe");
$stmt_especialidades->execute();
$especialidades = $stmt_especialidades->fetchAll(PDO::FETCH_ASSOC);

// Filtros y paginación
$registros_por_pagina = 3;
$pagina_actual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$filtro_mes = $_GET['mes'] ?? '';
$filtro_anio = $_GET['anio'] ?? '';
$filtro_especialidad = $_GET['especialidad'] ?? 'todos';
$hay_filtros = (!empty($filtro_mes) || !empty($filtro_anio) || $filtro_especialidad !== 'todos');

// Consulta para obtener el historial de citas
$sql = "
    SELECT 
        dh.id_detalle,
        c.id_cita,
        hm.fecha_horario,
        esp.nom_espe
    FROM historia_clinica hc
    JOIN detalles_histo_clini dh ON hc.id_historia = dh.id_historia
    JOIN citas c ON hc.id_cita = c.id_cita
    JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med
    JOIN usuarios u ON c.doc_med = u.doc_usu
    JOIN especialidad esp ON u.id_especialidad = esp.id_espe
    WHERE c.doc_pac = :doc_usuario
";

$params = [':doc_usuario' => $doc_usuario];

if ($hay_filtros) {
    $where_clauses = [];
    if (!empty($filtro_mes) && !empty($filtro_anio)) {
        $where_clauses[] = "MONTH(hm.fecha_horario) = :mes AND YEAR(hm.fecha_horario) = :anio";
        $params[':mes'] = $filtro_mes;
        $params[':anio'] = $filtro_anio;
    }
    if ($filtro_especialidad !== 'todos') {
        $where_clauses[] = "esp.id_espe = :especialidad";
        $params[':especialidad'] = $filtro_especialidad;
    }
    if (!empty($where_clauses)) {
        $sql .= " AND " . implode(" AND ", $where_clauses);
    }
}

$sql .= " ORDER BY hm.fecha_horario DESC";

// Contar total de registros para paginación
$stmt_count = $con->prepare($sql);
$stmt_count->execute($params);
$total_registros = $stmt_count->rowCount();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta paginada
$sql .= " LIMIT :limit OFFSET :offset";
$stmt = $con->prepare($sql);
$params[':limit'] = $registros_por_pagina;
$params[':offset'] = $offset;
foreach ($params as $key => &$val) {
    $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Historial Médico - Salud Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="estilos.css">
</head>

<?php include '../include/menu.php'; ?>

<body class="d-flex flex-column min-vh-100">
<main class="container py-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="mb-4 text-center" style="color: #004a99;">Mi Historial de Atenciones</h2>
            <p class="text-center text-muted mb-4">Aquí puede ver y descargar un resumen de sus atenciones médicas pasadas.</p>
            
            <form method="GET" action="" class="row g-3 mb-4">
                <div class="col-md-6 col-lg-3">
                    <label for="especialidad" class="form-label">Especialidad</label>
                    <select name="especialidad" id="especialidad" class="form-select">
                        <option value="todos" <?php echo $filtro_especialidad == 'todos' ? 'selected' : ''; ?>>Todas</option>
                        <?php foreach ($especialidades as $esp): ?>
                            <option value="<?php echo $esp['id_espe']; ?>" <?php echo $filtro_especialidad == $esp['id_espe'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($esp['nom_espe']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="mes" class="form-label">Mes</label>
                    <select name="mes" id="mes" class="form-select">
                        <option value="">Todos</option>
                        <?php
                        $meses = [
                            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                        ];
                        foreach ($meses as $num => $nombre): ?>
                            <option value="<?php echo $num; ?>" <?php echo $filtro_mes == $num ? 'selected' : ''; ?>>
                                <?php echo $nombre; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <label for="anio" class="form-label">Año</label>
                    <select name="anio" id="anio" class="form-select">
                        <option value="">Todos</option>
                        <?php for ($i = date('Y') + 5; $i >= date('Y') - 10; $i--): ?>
                            <option value="<?php echo $i; ?>" <?php echo $filtro_anio == $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-lg-3 d-flex align-items-end mt-3 mt-lg-0">
                    <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                    <?php if ($hay_filtros): ?>
                        <a href="historial_medico.php" class="btn btn-outline-danger ms-2" title="Quitar filtros"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID Detalle Historial</th>
                            <th>Fecha de Atención</th>
                            <th>Especialidad</th>
                            <th class="text-center">Descargar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historial)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No tiene historiales médicos disponibles.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($historial as $registro): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($registro['id_detalle']); ?></td>
                                    <td><strong><?php echo date('d/m/Y', strtotime($registro['fecha_horario'])); ?></strong></td>
                                    <td><?php echo htmlspecialchars($registro['nom_espe']); ?></td>
                                    <td class="text-center">
                                        <a href="generar_pdf_historial.php?id_historia=<?php echo $registro['id_detalle']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           target="_blank"
                                           title="Descargar PDF">
                                            <i class="bi bi-file-earmark-pdf-fill"></i> PDF
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_paginas > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center align-items-center">
                        <?php
                        $query_params = $_GET;
                        unset($query_params['page']);
                        $query_string = http_build_query($query_params);
                        echo '<li class="page-item ' . ($pagina_actual <= 1 ? 'disabled' : '') . '"><a class="page-link" href="?page=' . ($pagina_actual - 1) . '&' . $query_string . '"><</a></li>';
                        echo '<li class="page-item active"><span class="page-link">' . $pagina_actual . '/' . $total_paginas . '</span></li>';
                        echo '<li class="page-item ' . ($pagina_actual >= $total_paginas ? 'disabled' : '') . '"><a class="page-link" href="?page=' . ($pagina_actual + 1) . '&' . $query_string . '">></a></li>';
                        ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../include/footer.php'; ?>

</body>
</html>