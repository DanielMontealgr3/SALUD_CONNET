<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once '../include/conexion.php';
if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}
if (session_status() == PHP_SESSION_NONE) { session_start(); }
date_default_timezone_set('America/Bogota');
$doc_farmaceuta_logueado = $_SESSION['doc_usu'] ?? null;
if (!$doc_farmaceuta_logueado || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 3) {
    header('Location: ../inicio_sesion.php?error=nosession');
    exit;
}
$nit_farmacia_asignada_actual = $_SESSION['nit_farmacia_asignada_actual'] ?? null;
$nombre_farmacia_asignada = 'Farmacia';

if (!$nit_farmacia_asignada_actual) {
    $stmt_check_farma = $con->prepare("SELECT nit_farma FROM asignacion_farmaceuta WHERE doc_farma = :doc_farma AND id_estado = 1 LIMIT 1");
    $stmt_check_farma->execute([':doc_farma' => $doc_farmaceuta_logueado]);
    if ($farma_asignada = $stmt_check_farma->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION['nit_farmacia_asignada_actual'] = $farma_asignada['nit_farma'];
        $nit_farmacia_asignada_actual = $farma_asignada['nit_farma'];
    } else {
        header('Location: inicio.php');
        exit;
    }
}

if ($nit_farmacia_asignada_actual && isset($con)) {
    try {
        $stmt_nombre = $con->prepare("SELECT nom_farm FROM farmacias WHERE nit_farm = ?");
        $stmt_nombre->execute([$nit_farmacia_asignada_actual]);
        $nombre_farma = $stmt_nombre->fetchColumn();
        if ($nombre_farma) {
            $nombre_farmacia_asignada = $nombre_farma;
        }
    } catch (PDOException $e) { }
}


if (empty($_SESSION['csrf_token_farma_lista'])) {
    $_SESSION['csrf_token_farma_lista'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token_farma_lista'];
$pageTitle = "Lista de Pacientes para Entrega";
$pacientes_pendientes = [];
if (isset($con)) {
    try {
        $sql_base = "SELECT * FROM (
                        SELECT
                            tem.id_turno_ent, tem.id_historia, u.doc_usu AS documento_paciente, u.nom_usu AS nombre_paciente,
                            CASE WHEN mer.id_periodo = 2 AND TIME_FORMAT(hf.horario, '%H:%i:%s') < '12:00:00' THEN ADDTIME(hf.horario, '12:00:00') ELSE hf.horario END AS hora_24h,
                            vt.id_farmaceuta AS farmaceuta_atendiendo, vt.hora_llamado, vt.id_estado AS estado_llamado
                        FROM turno_ent_medic tem
                        JOIN historia_clinica hc ON tem.id_historia = hc.id_historia
                        JOIN citas ci ON hc.id_cita = ci.id_cita
                        JOIN usuarios u ON ci.doc_pac = u.doc_usu
                        JOIN afiliados afi ON u.doc_usu = afi.doc_afiliado
                        JOIN detalle_eps_farm def ON afi.id_eps = def.nit_eps
                        JOIN horario_farm hf ON tem.hora_entreg = hf.id_horario_farm
                        LEFT JOIN meridiano mer ON hf.meridiano = mer.id_periodo
                        LEFT JOIN vista_televisor vt ON tem.id_turno_ent = vt.id_turno
                        WHERE tem.id_est IN (3, 11) AND tem.fecha_entreg = CURDATE() AND def.nit_farm= :nit_farma AND def.id_estado = 1
                        GROUP BY tem.id_turno_ent
                    ) AS t
                    JOIN (
                        SELECT id_historia, GROUP_CONCAT(DISTINCT med.nom_medicamento SEPARATOR ', ') AS medicamentos, SUM(can_medica) AS cantidad_total
                        FROM detalles_histo_clini
                        JOIN medicamentos med ON detalles_histo_clini.id_medicam = med.id_medicamento
                        GROUP BY id_historia
                    ) AS dh ON t.id_historia = dh.id_historia
                    ORDER BY CASE WHEN t.estado_llamado = 11 THEN 0 ELSE 1 END ASC, t.hora_24h ASC, t.nombre_paciente ASC";
        
        $stmt_pacientes = $con->prepare($sql_base);
        $stmt_pacientes->execute([':nit_farma' => $nit_farmacia_asignada_actual]);
        $pacientes_pendientes = $stmt_pacientes->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) { }
}

function render_fila_paciente_data($paciente, $doc_farmaceuta_logueado) {
    $ahora_ts = time();
    $hora_programada_ts = strtotime(date('Y-m-d') . ' ' . $paciente['hora_24h']);
    $hora_formateada = date("h:i A", $hora_programada_ts);
    $clase_fila = ''; $tiempo_restante_seg = 0;
    $en_llamada = !empty($paciente['hora_llamado']);
    $estado_llamado = $paciente['estado_llamado'] ?? 0;
    $tiempo_espera_no_asistio = 60;
    $esta_vencido = $ahora_ts > $hora_programada_ts && !$en_llamada && $estado_llamado != 11;
    
    if ($en_llamada && $estado_llamado == 1) {
        $hora_llamado_obj = new DateTime($paciente['hora_llamado']);
        $diferencia = $ahora_ts - $hora_llamado_obj->getTimestamp();
        $tiempo_restante_seg = max(0, $tiempo_espera_no_asistio - $diferencia);
        $clase_fila = 'table-info';
    } elseif ($estado_llamado == 11) {
        $clase_fila = 'table-success';
    } elseif ($esta_vencido) {
        $clase_fila = 'table-danger';
    }

    $se_puede_llamar = ($hora_programada_ts - $ahora_ts) <= 300;
    
    $acciones_html = '';
    if ($estado_llamado == 1) {
        if ($paciente['farmaceuta_atendiendo'] == $doc_farmaceuta_logueado) {
            $acciones_html .= '<button class="btn btn-warning btn-sm btn-paciente-llego" title="Confirmar que el paciente llegó"><i class="bi bi-check-circle-fill"></i> Paciente Llegó</button><span class="badge bg-danger text-white ms-2 contador-espera"></span>';
        } else {
            $acciones_html .= '<button class="btn btn-secondary btn-sm" disabled><i class="bi bi-person-gear"></i> Atendiendo</button>';
        }
    } elseif ($estado_llamado == 11) {
        if ($paciente['farmaceuta_atendiendo'] == $doc_farmaceuta_logueado) {
            $acciones_html .= '<button class="btn btn-success btn-sm btn-entregar-medicamentos" title="Proceder a la entrega"><i class="bi bi-box-seam-fill"></i> Entregar</button>';
        } else {
            $acciones_html .= '<button class="btn btn-secondary btn-sm" disabled><i class="bi bi-person-check"></i> En Entrega</button>';
        }
    } else {
        $disabled = !$se_puede_llamar ? 'disabled' : '';
        $btn_class = $esta_vencido ? 'btn-danger' : 'btn-primary';
        $acciones_html .= "<button class=\"btn {$btn_class} btn-sm btn-llamar-paciente\" title=\"Llamar Paciente\" {$disabled}><i class=\"bi bi-megaphone-fill\"></i> Llamar</button>";
    }
    
    return [
        'id_turno_ent' => $paciente['id_turno_ent'],
        'clase_fila' => $clase_fila,
        'estado_llamado' => $estado_llamado,
        'tiempo_restante' => $tiempo_restante_seg,
        'celdas' => [
            "<td><span class=\"badge bg-secondary\">#" . htmlspecialchars($paciente['id_turno_ent']) . "</span></td>",
            "<td>" . htmlspecialchars($paciente['documento_paciente']) . "</td>",
            "<td>" . htmlspecialchars($paciente['nombre_paciente']) . "</td>",
            "<td>" . htmlspecialchars($paciente['medicamentos']) . "</td>",
            "<td>" . htmlspecialchars($paciente['cantidad_total']) . "</td>",
            "<td>" . htmlspecialchars($hora_formateada) . "</td>"
        ],
        'acciones_html' => $acciones_html
    ];
}

if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    $pacientes_data = [];
    foreach ($pacientes_pendientes as $paciente) {
        $pacientes_data[] = render_fila_paciente_data($paciente, $doc_farmaceuta_logueado);
    }
    echo json_encode(['pacientes' => $pacientes_data, 'total' => count($pacientes_data)]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="../img/loguito.png">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../include/menu.php'; ?>
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100; margin-top: 62px;">
    </div>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <h3 class="titulo-lista-tabla">
                    Pacientes para Entrega en: <strong><?php echo htmlspecialchars($nombre_farmacia_asignada); ?></strong>
                    <span class="badge bg-primary rounded-pill ms-2" id="contador-pacientes"><?php echo count($pacientes_pendientes); ?></span>
                </h3>
                <div class="table-responsive">
                    <table class="tabla-admin-mejorada">
                        <thead>
                            <tr><th>Turno</th><th>Documento</th><th>Nombre</th><th>Medicamento</th><th>Cantidad</th><th>Hora Prog.</th><th>Acción</th></tr>
                        </thead>
                        <tbody id="cuerpo-tabla-pacientes">
                            <?php
                                if (empty($pacientes_pendientes)) {
                                    echo '<tr><td colspan="7" class="text-center p-4">No hay pacientes pendientes de entrega en este momento.</td></tr>';
                                } else {
                                    foreach ($pacientes_pendientes as $paciente) {
                                        $data = render_fila_paciente_data($paciente, $doc_farmaceuta_logueado);
                                        echo "<tr class=\"{$data['clase_fila']}\" id=\"turno-{$data['id_turno_ent']}\" data-estado=\"{$data['estado_llamado']}\">";
                                        echo implode('', $data['celdas']);
                                        echo "<td class=\"acciones-tabla\" data-idturno=\"{$data['id_turno_ent']}\" data-tiempo-restante=\"{$data['tiempo_restante']}\">";
                                        echo $data['acciones_html'];
                                        echo "</td></tr>";
                                    }
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <div class="modal fade" id="modalNoAsistio" tabindex="-1" aria-labelledby="modalNoAsistioLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header border-0"><h5 class="modal-title" id="modalNoAsistioLabel"><i class="bi bi-person-x-fill text-danger me-2"></i>Paciente No Asistió</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
          <div class="modal-body text-center"><p>El paciente no se presentó dentro del tiempo de espera. El turno ha sido cancelado.</p></div>
          <div class="modal-footer border-0 justify-content-center"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Aceptar</button></div>
        </div>
      </div>
    </div>
    <script> const csrfTokenListaPacientesGlobal = '<?php echo $csrf_token; ?>'; const docFarmaceutaLogueado = '<?php echo $doc_farmaceuta_logueado; ?>';</script>
    <script src="js/gestion_pacientes_auto.js?v=<?php echo time(); ?>"></script>
</body>
<?php include_once '../include/footer.php'; ?>
</html>