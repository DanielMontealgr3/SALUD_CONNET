<?php
// BLOQUE 1: CONFIGURACIÓN Y SEGURIDAD
// INCLUYE EL ARCHIVO DE CONFIGURACIÓN GLOBAL. ESTA DEBE SER SIEMPRE LA PRIMERA LÍNEA.
// 'config.php' INICIA LA SESIÓN, DEFINE LAS RUTAS (BASE_URL, ROOT_PATH) Y CONECTA A LA BASE DE DATOS ($con).
require_once __DIR__ . '/../include/config.php';

// INCLUYE EL SCRIPT PARA VALIDAR QUE EL USUARIO TENGA UNA SESIÓN ACTIVA Y SEA DEL ROL CORRECTO.
require_once ROOT_PATH . '/include/validar_sesion.php';
// INCLUYE EL SCRIPT QUE MANEJA LA INACTIVIDAD DE LA SESIÓN.
require_once ROOT_PATH . '/include/inactividad.php';

// VERIFICACIÓN ESPECÍFICA DEL ROL. SI EL USUARIO NO ES MÉDICO (ROL 4), SE LE REDIRIGE.
if ($_SESSION['id_rol'] != 4) {
    header('Location: ' . BASE_URL . '/inicio_sesion.php');
    exit;
}

// BLOQUE 2: LÓGICA DE NEGOCIO Y CONSULTA A LA BASE DE DATOS
// DEFINE CONSTANTES PARA LOS ESTADOS DE LAS CITAS, HACIENDO EL CÓDIGO MÁS LEGIBLE.
define('ID_ESTADO_ASIGNADA', 3);
define('ID_ESTADO_EN_PROCESO', 11);

// OBTIENE EL DOCUMENTO DEL MÉDICO LOGUEADO DESDE LA SESIÓN.
$doc_medico_logueado = $_SESSION['doc_usu'];

// PREPARACIÓN DE LA CONSULTA SQL PARA OBTENER LAS CITAS DE HOY PARA EL MÉDICO.
$base_sql = "FROM citas c 
             JOIN estado e ON c.id_est = e.id_est 
             JOIN horario_medico hm ON c.id_horario_med = hm.id_horario_med
             LEFT JOIN usuarios up ON c.doc_pac = up.doc_usu";
$where_clauses = ["hm.doc_medico = ?", "hm.fecha_horario = CURDATE()", "c.id_est IN (?, ?)"];
$query_params = [$doc_medico_logueado, ID_ESTADO_ASIGNADA, ID_ESTADO_EN_PROCESO];
$final_where_sql = " WHERE " . implode(" AND ", $where_clauses);
$sql_data = "SELECT c.id_cita, c.doc_pac, c.id_est, e.nom_est, up.nom_usu AS nom_paciente, hm.fecha_horario, hm.horario " . $base_sql . $final_where_sql . " ORDER BY hm.horario ASC";

// EJECUCIÓN DE LA CONSULTA Y OBTENCIÓN DE LOS RESULTADOS.
$stmt_data = $con->prepare($sql_data);
$stmt_data->execute($query_params);
$citas = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

// ESTABLECE EL TÍTULO DE LA PÁGINA ANTES DE INCLUIR EL MENÚ.
$pageTitle = 'Gestión de Citas Diarias';
?>
<!DOCTYPE html>
<html lang="es">
<?php 
// BLOQUE 3: INCLUSIÓN DE LA VISTA (ENCABEZADO Y MENÚ)
// INCLUYE EL MENÚ. COMO 'config.php' YA SE CARGÓ, TODAS LAS VARIABLES Y CONSTANTES (COMO BASE_URL) ESTARÁN DISPONIBLES.
require_once ROOT_PATH . '/include/menu.php'; 
?>
<!-- EL '<body>' YA ESTÁ INCLUIDO DENTRO DE 'menu.php', POR LO QUE SE QUITA DE AQUÍ. -->
<main class="main-content-area">
    <div class="page-container">
        <!-- SECCIÓN DE ENCABEZADO DE LA PÁGINA CON TÍTULO Y RELOJ EN TIEMPO REAL. -->
        <div class="header-section">
            <h2 class="page-title"><?php echo $pageTitle; ?></h2>
            <div id="real-time-clock" class="real-time-clock">Cargando...</div>
        </div>
        
        <!-- CONTENEDOR DE LA TABLA DE CITAS. -->
        <div class="table-container">
            <table class="table table-hover table-bordered align-middle table-responsive-cards">
                <thead>
                    <tr><th>Paciente</th><th>Documento</th><th>Hora Cita</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody id="citas-table-body">
                    <?php 
                    // BLOQUE 4: RENDERIZADO DE DATOS
                    // SI HAY CITAS, SE RECORREN Y SE MUESTRAN EN LA TABLA.
                    if (count($citas) > 0): 
                        foreach ($citas as $cita):
                            $fecha_hora_cita_str = $cita['fecha_horario'] . 'T' . $cita['horario'];
                    ?>
                            <tr id="cita-row-<?php echo $cita['id_cita']; ?>" 
                                data-cita-id="<?php echo $cita['id_cita']; ?>" 
                                data-datetime="<?php echo $fecha_hora_cita_str; ?>"
                                data-doc-paciente="<?php echo htmlspecialchars($cita['doc_pac']); ?>"
                                data-nom-paciente="<?php echo htmlspecialchars($cita['nom_paciente'] ?? 'N/A'); ?>">
                                
                                <td data-label="Paciente:" class="td-paciente-responsive"><?php echo htmlspecialchars($cita['nom_paciente'] ?? 'N/A'); ?></td>
                                <td data-label="Documento:"><?php echo htmlspecialchars($cita['doc_pac']); ?></td>
                                <td data-label="Hora Cita:"><strong><?php echo htmlspecialchars(date('h:i A', strtotime($cita['horario']))); ?></strong></td>
                                <td data-label="Estado:" id="estado-cita-<?php echo $cita['id_cita']; ?>">
                                    <span class="badge bg-<?php echo ($cita['id_est'] == ID_ESTADO_ASIGNADA) ? 'info text-dark' : 'warning text-dark'; ?>"><?php echo htmlspecialchars($cita['nom_est']); ?></span>
                                </td>
                                <td class="actions-container" id="actions-cita-<?php echo $cita['id_cita'] ?>">
                                    <?php if ($cita['id_est'] == ID_ESTADO_ASIGNADA): ?>
                                        <button type="button" class="btn btn-sm llamar-paciente-btn" disabled><i class="fas fa-clock"></i> Esperando...</button>
                                        <button type="button" class="btn btn-sm btn-success paciente-llego-btn" style="display:none;"><i class="fas fa-user-check"></i> Paciente Llegó</button>
                                    <?php elseif ($cita['id_est'] == ID_ESTADO_EN_PROCESO): ?>
                                        <button type="button" class="btn btn-sm btn-primary iniciar-consulta-btn" data-bs-toggle="modal" data-bs-target="#modalConsulta" data-id-cita="<?php echo $cita['id_cita']; ?>" data-doc-paciente="<?php echo htmlspecialchars($cita['doc_pac']); ?>" data-nom-paciente="<?php echo htmlspecialchars($cita['nom_paciente'] ?? 'N/A'); ?>"><i class="fas fa-play"></i> Iniciar Consulta</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                        endforeach; 
                    // SI NO HAY CITAS, SE MUESTRA UN MENSAJE INFORMATIVO.
                    else: 
                    ?>
                        <tr id="no-citas-row"><td colspan="5" class="text-center p-4">No tiene citas activas para hoy.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php
// BLOQUE 5: INCLUSIÓN DE VISTAS ADICIONALES Y SCRIPTS
// INCLUYE EL MODAL PARA INICIAR LA CONSULTA Y EL PIE DE PÁGINA.
require_once __DIR__ . '/includes_medi/modal_inicio_cita.php'; 
require_once ROOT_PATH . '/include/footer.php'; 
?>
<!-- ENLACE A LOS ARCHIVOS JAVASCRIPT ESPECÍFICOS DE ESTA PÁGINA. -->
<script src="<?php echo BASE_URL; ?>/medi/js/vista_citas.js"></script>
<script src="<?php echo BASE_URL; ?>/medi/js/inicio_consul.js"></script>
<!-- '</body>' y '</html>' ya están en 'footer.php' o 'menu.php'. -->