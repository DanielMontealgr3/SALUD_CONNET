<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 4 || !isset($_SESSION['doc_usu'])) {
    header('Location: ../inicio_sesion.php');
    exit;
}

define('ID_ESTADO_ASIGNADA', 3);
define('ID_ESTADO_EN_PROCESO', 11);

$db = new Database();
$pdo = $db->conectar();
$doc_medico_logueado = $_SESSION['doc_usu'];

$base_sql = "FROM citas c 
             JOIN estado e ON c.id_est = e.id_est 
             JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med
             LEFT JOIN usuarios up ON c.doc_pac = up.doc_usu";
$where_clauses = ["hm.doc_medico = ?", "hm.fecha_horario = CURDATE()", "c.id_est IN (?, ?)"];
$query_params = [$doc_medico_logueado, ID_ESTADO_ASIGNADA, ID_ESTADO_EN_PROCESO];
$final_where_sql = " WHERE " . implode(" AND ", $where_clauses);
$sql_data = "SELECT c.id_cita, c.doc_pac, c.id_est, e.nom_est, up.nom_usu AS nom_paciente, hm.fecha_horario, hm.horario " . $base_sql . $final_where_sql . " ORDER BY hm.horario ASC";
$stmt_data = $pdo->prepare($sql_data);
$stmt_data->execute($query_params);
$citas = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Citas Diarias</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="estilos.css">

    <!-- ========================================================== -->
    <!-- ==      CSS RESPONSIVO        == -->
    <!-- ========================================================== -->
    <style>
        .table-responsive-cards { width: 100%; }
        .table-responsive-cards thead { display: table-header-group; }
        .table-responsive-cards tr { display: table-row; }

        @media screen and (max-width: 768px) {
            .table-responsive-cards thead {
                display: none; /* Oculta los encabezados */
            }
            .table-responsive-cards tbody, .table-responsive-cards tr, .table-responsive-cards td {
                display: block; /* Todo se convierte en bloques */
                width: 100%;
            }
            .table-responsive-cards tr {
                margin-bottom: 1.5rem; /* Espacio entre tarjetas */
                border: 1px solid #e0e0e0;
                border-radius: 0.5rem;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
                padding: 0.5rem; /* Padding interno a la tarjeta */
            }
            .table-responsive-cards td {
                /* Se elimina la alineación derecha y el padding izquierdo */
                text-align: center; /* Centramos el contenido por defecto */
                border: none;
                border-bottom: 1px solid #f0f0f0;
                padding-top: 1.25rem; /* Espacio para la etiqueta de arriba */
                padding-bottom: 0.5rem;
                position: relative;
            }
            .table-responsive-cards td:before {
                content: attr(data-label); /* Se sigue usando el data-label */
                position: absolute;
                top: 0;
                left: 50%;
                transform: translateX(-50%); /* Centra la etiqueta horizontalmente */
                width: 100%;
                text-align: center;
                font-size: 0.75rem; /* Etiqueta más pequeña */
                color: #6c757d; /* Color gris para la etiqueta */
                font-weight: normal;
                text-transform: uppercase;
                padding-top: 0.25rem;
            }
            .table-responsive-cards td:last-child {
                border-bottom: 0;
            }
            /* Estilos específicos para mejorar la legibilidad */
            .td-paciente-responsive {
                font-size: 1.2rem;
                font-weight: bold;
                padding-top: 0.5rem !important; /* Menos espacio arriba para el nombre */
                border-bottom: 1px solid #dee2e6 !important;
                margin-bottom: 0.5rem;
            }
            .td-paciente-responsive:before {
                display: none; /* No se necesita etiqueta para el nombre del paciente */
            }
            .actions-container {
                padding-top: 1rem !important;
            }
            .actions-container:before {
                display: none; /* No se necesita etiqueta para las acciones */
            }
        }
    </style>
</head>
<body>
<?php include '../include/menu.php'; ?>
<main class="main-content-area">
    <div class="page-container">
        <div class="header-section">
            <h2 class="page-title">Gestión de Citas de Hoy</h2>
            <div id="real-time-clock" class="real-time-clock">Cargando...</div>
        </div>
        
        <div class="table-container">
            <table class="table table-hover table-bordered align-middle table-responsive-cards">
                <thead>
                    <tr><th>Paciente</th><th>Documento</th><th>Hora Cita</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody id="citas-table-body">
                    <?php if (count($citas) > 0): ?>
                        <?php foreach ($citas as $cita):
                            $fecha_hora_cita_str = $cita['fecha_horario'] . 'T' . $cita['horario'];
                        ?>
                            <tr id="cita-row-<?= $cita['id_cita']; ?>" 
                                data-cita-id="<?= $cita['id_cita']; ?>" 
                                data-datetime="<?= $fecha_hora_cita_str; ?>"
                                data-doc-paciente="<?= htmlspecialchars($cita['doc_pac']); ?>"
                                data-nom-paciente="<?= htmlspecialchars($cita['nom_paciente'] ?? 'N/A'); ?>">
                                
                                <!-- CORRECCIÓN: Se añade una clase extra para estilizar el nombre del paciente en móvil -->
                                <td data-label="Paciente:" class="td-paciente-responsive"><?= htmlspecialchars($cita['nom_paciente'] ?? 'N/A'); ?></td>
                                <td data-label="Documento:"><?= htmlspecialchars($cita['doc_pac']); ?></td>
                                <td data-label="Hora Cita:"><strong><?= htmlspecialchars(date('h:i A', strtotime($cita['horario']))); ?></strong></td>
                                <td data-label="Estado:" id="estado-cita-<?= $cita['id_cita']; ?>">
                                    <span class="badge bg-<?= ($cita['id_est'] == ID_ESTADO_ASIGNADA) ? 'info text-dark' : 'warning text-dark'; ?>"><?= htmlspecialchars($cita['nom_est']); ?></span>
                                </td>
                                <td class="actions-container" id="actions-cita-<?= $cita['id_cita'] ?>">
                                    <?php if ($cita['id_est'] == ID_ESTADO_ASIGNADA): ?>
                                        <button type="button" class="btn btn-sm llamar-paciente-btn" disabled><i class="fas fa-clock"></i> Esperando...</button>
                                        <button type="button" class="btn btn-sm btn-success paciente-llego-btn" style="display:none;"><i class="fas fa-user-check"></i> Paciente Llegó</button>
                                    <?php elseif ($cita['id_est'] == ID_ESTADO_EN_PROCESO): ?>
                                        <button type="button" class="btn btn-sm btn-primary iniciar-consulta-btn" data-bs-toggle="modal" data-bs-target="#modalConsulta" data-id-cita="<?= $cita['id_cita']; ?>" data-doc-paciente="<?= htmlspecialchars($cita['doc_pac']); ?>" data-nom-paciente="<?= htmlspecialchars($cita['nom_paciente'] ?? 'N/A'); ?>"><i class="fas fa-play"></i> Iniciar Consulta</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="no-citas-row"><td colspan="5" class="text-center p-4">No tiene citas activas para hoy.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include 'includes_medi/modal_inicio_cita.php'; ?>
<?php include '../include/footer.php'; ?>
<script src="js/vista_citas.js"></script>
<script src="js/inicio_consul.js"></script>
</body>
</html>