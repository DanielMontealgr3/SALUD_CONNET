<?php
// =================================================================
// === INICIO DEL BLOQUE CORREGIDO (PORTABILIDAD) ===
// =================================================================

// 1. Inclusión de la configuración centralizada.
// Esto establece ROOT_PATH, BASE_URL, inicia sesión y conecta a la BD.
require_once __DIR__ . '/../include/config.php';

// 2. Inclusión de los scripts de seguridad usando ROOT_PATH.
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'esp');

// La variable de conexión `$con` ya está disponible desde config.php.
// No es necesario crear una nueva instancia de Database.
$doc_usuario = $_SESSION['doc_usu'];

// La sesión ya se inicia en config.php.
// if (session_status() == PHP_SESSION_NONE) { session_start(); } // Esta línea ya no es necesaria.

// =================================================================
// === FIN DEL BLOQUE CORREGIDO ===
// El resto del código permanece exactamente igual.
// =================================================================

$mensaje_alerta = '';
$tipo_alerta = '';
if (isset($_SESSION['reagendamiento_status'])) {
    $tipo_alerta = $_SESSION['reagendamiento_status']['tipo'];
    $mensaje_alerta = $_SESSION['reagendamiento_status']['mensaje'];
    unset($_SESSION['reagendamiento_status']);
}

// aca se definen los filtros por si el usuario busca por especialidad estado o por fechas
$registros_por_pagina = 3; 
$pagina_actual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$filtro_especialidad = $_GET['especialidad'] ?? 'todos';
$filtro_estado = $_GET['estado'] ?? 'todos';
$filtro_mes_inicio = $_GET['mes_inicio'] ?? '';
$filtro_anio_inicio = $_GET['anio_inicio'] ?? '';
$filtro_mes_fin = $_GET['mes_fin'] ?? '';
$filtro_anio_fin = $_GET['anio_fin'] ?? '';
$orden = isset($_GET['sort']) && strtolower($_GET['sort']) === 'asc' ? 'ASC' : 'DESC';
$orden_contrario = ($orden === 'ASC') ? 'desc' : 'asc';
$hay_filtros = ($filtro_especialidad !== 'todos' || $filtro_estado !== 'todos' || !empty($filtro_mes_inicio) || !empty($filtro_mes_fin));

$stmt_especialidades = $con->query("SELECT id_espe, nom_espe FROM especialidad WHERE id_espe != 46 ORDER BY nom_espe");
$especialidades = $stmt_especialidades->fetchAll(PDO::FETCH_ASSOC);

$estados_relevantes = [1, 3, 7, 8, 10, 16]; 
$in_placeholders = implode(',', array_fill(0, count($estados_relevantes), '?'));
$stmt_estados = $con->prepare("SELECT id_est, nom_est FROM estado WHERE id_est IN ($in_placeholders) ORDER BY nom_est");
$stmt_estados->execute($estados_relevantes);
$estados = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);

// aca se hacen las tres consultas principales una por cada tipo de evento cita entrega examen
$sql_citas = "SELECT hm.fecha_horario AS fecha_evento, hm.horario AS hora_evento, e.nom_est AS estado_nombre, c.id_est AS evento_id_est, 'Cita Médica' AS tipo_evento, esp.nom_espe AS detalle_evento, c.id_cita AS id_registro, 'medica' AS tipo_registro_slug, u.doc_usu as id_referencia, esp.id_espe as id_espe FROM citas c JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med JOIN estado e ON c.id_est = e.id_est JOIN usuarios u ON c.doc_med = u.doc_usu JOIN especialidad esp ON u.id_especialidad = esp.id_espe WHERE c.doc_pac = :doc_usuario_citas";

$sql_medicamentos = "SELECT tem.fecha_entreg AS fecha_evento, hf.horario AS hora_evento, e.nom_est AS estado_nombre, tem.id_est AS evento_id_est, 'Entrega Medicamentos' AS tipo_evento, 'Farmacia' AS detalle_evento, tem.id_turno_ent AS id_registro, 'medicamento' AS tipo_registro_slug, hf.nit_farm as id_referencia, 'medicamentos' as id_espe FROM turno_ent_medic tem JOIN horario_farm hf ON tem.hora_entreg = hf.id_horario_farm JOIN estado e ON tem.id_est = e.id_est JOIN historia_clinica hc ON tem.id_historia = hc.id_historia JOIN citas c ON hc.id_cita = c.id_cita WHERE c.doc_pac = :doc_usuario_medicamentos";

$sql_examenes = "SELECT tex.fech_exam AS fecha_evento, he.horario AS hora_evento, e.nom_est AS estado_nombre, tex.id_est AS evento_id_est, 'Examen Médico' AS tipo_evento, 'Laboratorio' AS detalle_evento, tex.id_turno_exa AS id_registro, 'examen' AS tipo_registro_slug, 'laboratorio' as id_referencia, 'examenes' as id_espe FROM turno_examen tex JOIN horario_examen he ON tex.hora_exam = he.id_horario_exan JOIN estado e ON tex.id_est = e.id_est JOIN historia_clinica hc ON tex.id_historia = hc.id_historia JOIN citas c ON hc.id_cita = c.id_cita WHERE c.doc_pac = :doc_usuario_examenes";

// aca se define que query final usar dependiendo de lo que haya filtrado el usuario
$sql_final = "SELECT 1 FROM (SELECT 1) AS t WHERE 1=0";
$params = [];

if ($filtro_especialidad === 'todos') {
    $sql_final = "($sql_citas) UNION ALL ($sql_medicamentos) UNION ALL ($sql_examenes)";
    $params = [':doc_usuario_citas' => $doc_usuario, ':doc_usuario_medicamentos' => $doc_usuario, ':doc_usuario_examenes' => $doc_usuario];
} elseif ($filtro_especialidad === 'medicamentos') {
    $sql_final = $sql_medicamentos; 
    $params = [':doc_usuario_medicamentos' => $doc_usuario];
} elseif ($filtro_especialidad === 'examenes') {
    $sql_final = $sql_examenes; 
    $params = [':doc_usuario_examenes' => $doc_usuario];
} else {
    $sql_final = $sql_citas . " AND esp.id_espe = :id_espe";
    $params = [':doc_usuario_citas' => $doc_usuario, ':id_espe' => $filtro_especialidad];
}

// aca se aplica lo que el usuario filtro como estado y fechas a la query anterior
$sql_filtrada = "SELECT * FROM ($sql_final) AS eventos_unificados";
$where_clauses = [];

if ($filtro_estado !== 'todos') { 
    $where_clauses[] = "evento_id_est = :id_est"; 
    $params[':id_est'] = $filtro_estado; 
}
if (!empty($filtro_mes_inicio) && !empty($filtro_anio_inicio)) { 
    $fecha_inicio = $filtro_anio_inicio . '-' . str_pad($filtro_mes_inicio, 2, '0', STR_PAD_LEFT) . '-01'; 
    $where_clauses[] = "fecha_evento >= :fecha_inicio"; 
    $params[':fecha_inicio'] = $fecha_inicio; 
}
if (!empty($filtro_mes_fin) && !empty($filtro_anio_fin)) { 
    $fecha_fin = (new DateTime($filtro_anio_fin . '-' . $filtro_mes_fin . '-01'))->format('Y-m-t'); 
    $where_clauses[] = "fecha_evento <= :fecha_fin"; 
    $params[':fecha_fin'] = $fecha_fin; 
}
if (!empty($where_clauses)) { 
    $sql_filtrada .= " WHERE " . implode(" AND ", $where_clauses); 
}

// aca se cuentan los registros para la paginacion
$stmt_count = $con->prepare($sql_filtrada); 
$stmt_count->execute($params);
$total_registros = $stmt_count->rowCount(); 
$total_paginas = ceil($total_registros / $registros_por_pagina);

// aca se agrega el ordenamiento por prioridad de estado luego fecha y hora
$orden_sql = "ORDER BY CASE evento_id_est WHEN 16 THEN 1 WHEN 3 THEN 2 WHEN 10 THEN 3 ELSE 4 END ASC, fecha_evento $orden, hora_evento $orden";
$sql_paginado = "$sql_filtrada $orden_sql LIMIT :limit OFFSET :offset";

// aca se hace la consulta final ya con limite y desplazamiento para la paginacion
$stmt = $con->prepare($sql_paginado);
$params[':limit'] = $registros_por_pagina; 
$params[':offset'] = $offset;
foreach ($params as $key => &$val) { 
    $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR); 
}
$stmt->execute();
$citas_unificadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Citas y Turnos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
    <link rel="stylesheet" href="estilos.css">
    <style> 
        .btn-accion-xs { --bs-btn-padding-y: .15rem; --bs-btn-padding-x: .4rem; --bs-btn-font-size: .75rem; } 
        .btn-primary, .btn-primary:hover { background-color: #004a99; border-color: #004a99; }
        .pagination .page-item.active .page-link { background-color: #004a99; border-color: #004a99; }
        #reagendar-horas-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: 10px; max-height: 220px; overflow-y: auto; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../include/menu.php'; ?>
    <main class="container py-5">
        <div id="alerta-placeholder"></div>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="mb-4 text-center" style="color: #004a99;">Historial de Citas y Turnos</h2>
                <form id="form-filtros" method="GET" action="citas_actuales.php" class="row g-3">
                    <div class="col-md-6 col-lg-3"><label for="especialidad" class="form-label">Tipo de Evento</label><select name="especialidad" id="especialidad" class="form-select"><option value="todos" <?php if ($filtro_especialidad == 'todos') echo 'selected'; ?>>Todos</option><option value="medicamentos" <?php if ($filtro_especialidad == 'medicamentos') echo 'selected'; ?>>Entrega de Medicamentos</option><option value="examenes" <?php if ($filtro_especialidad == 'examenes') echo 'selected'; ?>>Exámenes Médicos</option><optgroup label="Citas Médicas"><?php foreach ($especialidades as $esp): ?><option value="<?php echo $esp['id_espe']; ?>" <?php if ($filtro_especialidad == $esp['id_espe']) echo 'selected'; ?>><?php echo htmlspecialchars($esp['nom_espe']); ?></option><?php endforeach; ?></optgroup></select></div>
                    <div class="col-md-6 col-lg-2"><label for="estado" class="form-label">Estado</label><select name="estado" id="estado" class="form-select"><option value="todos" <?php if ($filtro_estado == 'todos') echo 'selected'; ?>>Todos</option><?php foreach ($estados as $est): ?><option value="<?php echo $est['id_est']; ?>" <?php if ($filtro_estado == $est['id_est']) echo 'selected'; ?>><?php echo htmlspecialchars($est['nom_est']); ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6 col-lg-2"><label class="form-label">Desde</label><div class="input-group"><select name="mes_inicio" class="form-select"><option value="">Mes</option><?php for ($i=1; $i<=12; $i++): ?><option value="<?php echo $i; ?>" <?php if ($filtro_mes_inicio == $i) echo 'selected'; ?>><?php echo $i; ?></option><?php endfor; ?></select><select name="anio_inicio" class="form-select"><option value="">Año</option><?php for ($i=date('Y'); $i>=date('Y')-5; $i--): ?><option value="<?php echo $i; ?>" <?php if ($filtro_anio_inicio == $i) echo 'selected'; ?>><?php echo $i; ?></option><?php endfor; ?></select></div></div>
                    <div class="col-md-6 col-lg-2"><label class="form-label">Hasta</label><div class="input-group"><select name="mes_fin" class="form-select"><option value="">Mes</option><?php for ($i=1; $i<=12; $i++): ?><option value="<?php echo $i; ?>" <?php if ($filtro_mes_fin == $i) echo 'selected'; ?>><?php echo $i; ?></option><?php endfor; ?></select><select name="anio_fin" class="form-select"><option value="">Año</option><?php for ($i=date('Y'); $i>=date('Y')-5; $i--): ?><option value="<?php echo $i; ?>" <?php if ($filtro_anio_fin == $i) echo 'selected'; ?>><?php echo $i; ?></option><?php endfor; ?></select></div></div>
                    <div class="col-lg-3 d-flex align-items-end mt-3 mt-lg-0">
                        <button type="submit" name="filtrar" class="btn btn-primary me-2">Filtrar</button>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => $orden_contrario])); ?>" class="btn btn-outline-secondary" title="Ordenar por fecha"><i class="bi bi-arrow-<?php echo ($orden === 'DESC') ? 'down' : 'up'; ?>"></i></a>
                        <?php if ($hay_filtros): ?><a href="citas_actuales.php" class="btn btn-outline-danger ms-2" title="Quitar filtros"><i class="bi bi-x-lg"></i></a><?php endif; ?>
                        <button type="submit" name="generar_excel" class="btn btn-success ms-auto"><i class="bi bi-file-earmark-excel"></i></button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th>Fecha Evento</th><th>Hora</th><th>Tipo</th><th>Detalle</th><th>Estado</th><th class="text-center">Acciones</th></tr></thead>
                        <tbody>
                            <?php if (empty($citas_unificadas)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No se encontraron registros.</td></tr>
                            <?php else: foreach ($citas_unificadas as $evento): ?>
                                <tr>
                                    <td><strong><?php echo date('d/m/Y', strtotime($evento['fecha_evento'])); ?></strong></td>
                                    <td><?php echo date('h:i A', strtotime($evento['hora_evento'])); ?></td>
                                    <td><?php echo htmlspecialchars($evento['tipo_evento']); ?></td>
                                    <td><?php echo htmlspecialchars($evento['detalle_evento']); ?></td>
                                    <td>
                                        <?php
                                        $estado_class = 'bg-secondary';
                                        if ($evento['evento_id_est'] == 16) $estado_class = 'bg-warning text-dark';
                                        elseif ($evento['evento_id_est'] == 3) $estado_class = 'bg-primary';
                                        elseif ($evento['evento_id_est'] == 10) $estado_class = 'bg-info text-dark';
                                        elseif (in_array($evento['evento_id_est'], [5, 9, 2])) $estado_class = 'bg-success';
                                        elseif ($evento['evento_id_est'] == 7) $estado_class = 'bg-danger';
                                        ?><span class="badge <?php echo $estado_class; ?>"><?php echo htmlspecialchars($evento['estado_nombre']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($evento['evento_id_est'] == 3): ?>
                                            <button class="btn btn-accion-xs btn-warning me-1 btn-reagendar" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#reagendarModal"
                                                    data-id="<?php echo $evento['id_registro']; ?>" 
                                                    data-tipo="<?php echo $evento['tipo_registro_slug']; ?>" 
                                                    data-tipo-evento="<?php echo htmlspecialchars($evento['tipo_evento']); ?>"
                                                    data-detalle="<?php echo htmlspecialchars($evento['detalle_evento']); ?>" 
                                                    data-id-referencia="<?php echo $evento['id_referencia']; ?>" 
                                                    title="Reagendar">
                                                <i class="bi bi-calendar-event"></i>
                                            </button>
                                            <a href="cancelar_cita.php?id=<?php echo $evento['id_registro']; ?>&tipo=<?php echo $evento['tipo_registro_slug']; ?>" class="btn btn-accion-xs btn-danger btn-cancelar" title="Cancelar"><i class="bi bi-x-circle"></i></a>
                                        <?php else: ?><span class="text-muted">--</span><?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($total_paginas > 1): ?>
                    <nav><ul class="pagination justify-content-center align-items-center">
                        <?php
                        $query_params = $_GET; unset($query_params['page']);
                        $query_string = http_build_query($query_params);
                        echo '<li class="page-item ' . ($pagina_actual <= 1 ? 'disabled' : '') . '"><a class="page-link" href="?page=' . ($pagina_actual - 1) . '&' . $query_string . '"><</a></li>';
                        echo '<li class="page-item active"><span class="page-link">' . $pagina_actual . '/' . $total_paginas . '</span></li>';
                        echo '<li class="page-item ' . ($pagina_actual >= $total_paginas ? 'disabled' : '') . '"><a class="page-link" href="?page=' . ($pagina_actual + 1) . '&' . $query_string . '">></a></li>';
                        ?>
                    </ul></nav>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <div class="modal fade" id="reagendarModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reagendar Turno/Cita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="form-reagendar" action="procesar_reagendamiento.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id_registro" id="reagendar-id-registro">
                        <input type="hidden" name="tipo" id="reagendar-tipo">
                        <input type="hidden" name="reagendar_submit" value="1">
                        <input type="hidden" name="tipo_evento" id="reagendar-tipo-evento-hidden">
                        <div class="alert alert-secondary mb-3">Reagendando: <strong id="reagendar-detalle"></strong></div>
                        <div class="mb-3">
                            <label for="reagendar-fecha-input" class="form-label"><b>Seleccione la nueva fecha:</b></label>
                            <input type="text" class="form-control" id="reagendar-fecha-input" name="fecha" required readonly placeholder="Haga clic para seleccionar...">
                        </div>
                        <div>
                            <label class="form-label"><b>Seleccione la nueva hora:</b></label>
                            <div id="reagendar-horas-container" class="border p-2 rounded">
                                <small class="text-muted">Seleccione una fecha para ver las horas.</small>
                            </div>
                        </div>
                        <input type="hidden" name="id_horario" id="reagendar-id-horario-input" required>
                        <input type="hidden" name="hora" id="reagendar-hora-input-24h" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btn-confirmar-reagenda" disabled>Confirmar Reagendamiento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../include/footer.php'; ?>
    <script src="js/form-submission.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($mensaje_alerta)): ?>
            const alertPlaceholder = document.getElementById('alerta-placeholder');
            const alertType = '<?php echo $tipo_alerta === "exito" ? "success" : "danger"; ?>';
            const alertHTML = `<div class="alert alert-${alertType} alert-dismissible fade show" role="alert">
                                 <?php echo addslashes($mensaje_alerta); ?>
                                 <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                               </div>`;
            alertPlaceholder.innerHTML = alertHTML;
        <?php endif; ?>

        const reagendarModalEl = document.getElementById('reagendarModal');
        let fpReagendaInstance = null;

        reagendarModalEl.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const horasContainer = document.getElementById('reagendar-horas-container');
            const btnConfirmarReagenda = document.getElementById('btn-confirmar-reagenda');
            
            document.getElementById('reagendar-id-registro').value = button.dataset.id;
            document.getElementById('reagendar-tipo').value = button.dataset.tipo;
            document.getElementById('reagendar-detalle').textContent = button.dataset.detalle;
            document.getElementById('reagendar-tipo-evento-hidden').value = button.dataset.tipoEvento;

            horasContainer.innerHTML = '<small class="text-muted">Seleccione una fecha para ver las horas.</small>';
            btnConfirmarReagenda.disabled = true;
            document.getElementById('reagendar-id-horario-input').value = '';
            document.getElementById('reagendar-hora-input-24h').value = '';

            if (fpReagendaInstance) fpReagendaInstance.destroy();
            
            fpReagendaInstance = flatpickr("#reagendar-fecha-input", {
                locale: "es", minDate: "today", dateFormat: "Y-m-d", 
                altInput: true, altFormat: "d/m/Y",
                disable: [date => (date.getDay() === 0 || date.getDay() === 6)],
                onChange: function(selectedDates, dateStr) {
                    if (!dateStr) return;
                    const tipo = document.getElementById('reagendar-tipo').value;
                    horasContainer.innerHTML = '<div class="d-flex justify-content-center"><div class="spinner-border spinner-border-sm"></div></div>';
                    btnConfirmarReagenda.disabled = true;

                    $.ajax({
                        url: 'consultas_citas/horas_reagenda.php',
                        type: 'POST', dataType: 'json', data: { fecha: dateStr, tipo: tipo },
                        success: function(response) {
                            let html = '<p class="text-muted text-center">No hay horas disponibles.</p>';
                            if (response.error) {
                                html = `<p class="text-danger text-center">${response.error}</p>`;
                            } else if (response.hours && response.hours.length > 0) {
                                html = response.hours.map(hora => 
                                    `<button type="button" class="btn btn-sm btn-outline-primary hour-btn-reagendar" data-id-horario="${hora.id}" data-hora-24h="${hora.horario}">${hora.hora12}</button>`
                                ).join('');
                            }
                            horasContainer.innerHTML = html;
                        }
                    });
                }
            });
        });

        reagendarModalEl.addEventListener('hidden.bs.modal', function() {
            if (fpReagendaInstance) {
                fpReagendaInstance.destroy();
                fpReagendaInstance = null;
            }
        });

        $('#reagendar-horas-container').on('click', '.hour-btn-reagendar', function() {
            $('#reagendar-horas-container .hour-btn-reagendar').removeClass('btn-primary active').addClass('btn-outline-primary');
            $(this).addClass('btn-primary active');
            document.getElementById('reagendar-id-horario-input').value = $(this).data('id-horario');
            document.getElementById('reagendar-hora-input-24h').value = $(this).data('hora-24h');
            document.getElementById('btn-confirmar-reagenda').disabled = false;
        });
    });
    </script>
</body>
</html>