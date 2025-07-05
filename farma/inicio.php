<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once '../include/conexion.php';

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true ||
    !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 3 ||
    !isset($_SESSION['nombre_usuario']) ||
    !isset($_SESSION['doc_usu'])
) {
    header('Location: ../inicio_sesion.php?error=nosession');
    exit;
}

$nombre_usuario = $_SESSION['nombre_usuario'];
$documento_farmaceuta = $_SESSION['doc_usu'];
$pageTitle = "Inicio Farmaceuta";

$asignacion_activa = false;
$nombre_farmacia_asignada = "";
$nit_farmacia_asignada = "";

$count_entregas_pendientes = 0;
$count_stock_bajo = 0;
$count_por_vencer = 0;
$count_vencidos = 0;

if ($documento_farmaceuta && isset($con) && $con instanceof PDO) {
    
    // --- Búsqueda de asignación de farmacia activa ---
    $sql_asignacion = "SELECT f.nom_farm, af.nit_farma, af.id_estado FROM asignacion_farmaceuta af JOIN farmacias f ON af.nit_farma = f.nit_farm WHERE af.doc_farma = :doc_farma AND af.id_estado = 1 LIMIT 1";
    $stmt_asignacion = $con->prepare($sql_asignacion);
    $stmt_asignacion->bindParam(':doc_farma', $documento_farmaceuta, PDO::PARAM_STR);
    $stmt_asignacion->execute();
    $fila_asignacion = $stmt_asignacion->fetch(PDO::FETCH_ASSOC);

    if ($fila_asignacion) {
        $asignacion_activa = true;
        $nombre_farmacia_asignada = $fila_asignacion['nom_farm'];
        $nit_farmacia_asignada = $fila_asignacion['nit_farma'];
        $_SESSION['nit_farma'] = $nit_farmacia_asignada;
        $_SESSION['nit_farmacia_asignada_actual'] = $nit_farmacia_asignada;

        // --- Búsqueda de Entregas Pendientes ---
        $sql_entregas = "SELECT COUNT(DISTINCT ep.id_entrega_pendiente) FROM entrega_pendiente ep JOIN detalles_histo_clini dh ON ep.id_detalle_histo = dh.id_detalle JOIN historia_clinica hc ON dh.id_historia = hc.id_historia JOIN citas c ON hc.id_cita = c.id_cita JOIN usuarios u ON c.doc_pac = u.doc_usu JOIN afiliados a ON u.doc_usu = a.doc_afiliado JOIN detalle_eps_farm def ON a.id_eps = def.nit_eps WHERE def.nit_farm = :nit AND ep.id_estado = 10";
        $stmt_entregas = $con->prepare($sql_entregas);
        $stmt_entregas->execute(['nit' => $nit_farmacia_asignada]);
        $count_entregas_pendientes = $stmt_entregas->fetchColumn();

        // --- Búsqueda de Medicamentos con Stock Bajo ---
        $sql_stock = "SELECT COUNT(*) FROM inventario_farmacia WHERE nit_farm = :nit AND cantidad_actual <= 10";
        $stmt_stock = $con->prepare($sql_stock);
        $stmt_stock->execute(['nit' => $nit_farmacia_asignada]);
        $count_stock_bajo = $stmt_stock->fetchColumn();

        // Subconsulta para calcular el stock real por lote, usada en las siguientes consultas
        $stock_por_lote_sql = "(SELECT SUM(CASE WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad WHEN id_tipo_mov IN (2, 4) THEN -cantidad ELSE 0 END) FROM movimientos_inventario WHERE lote = mi.lote AND id_medicamento = mi.id_medicamento AND nit_farm = mi.nit_farm)";

        // --- Búsqueda de Productos Próximos a Vencer ---
        $sql_por_vencer = "SELECT COUNT(DISTINCT mi.lote, mi.id_medicamento) FROM movimientos_inventario mi WHERE mi.nit_farm = :nit AND mi.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND $stock_por_lote_sql > 0";
        $stmt_por_vencer = $con->prepare($sql_por_vencer);
        $stmt_por_vencer->execute(['nit' => $nit_farmacia_asignada]);
        $count_por_vencer = $stmt_por_vencer->fetchColumn();
        
        // --- Búsqueda de Productos Vencidos ---
        $sql_vencidos = "SELECT COUNT(DISTINCT mi.lote, mi.id_medicamento) FROM movimientos_inventario mi WHERE mi.nit_farm = :nit AND mi.fecha_vencimiento < CURDATE() AND $stock_por_lote_sql > 0";
        $stmt_vencidos = $con->prepare($sql_vencidos);
        $stmt_vencidos->execute(['nit' => $nit_farmacia_asignada]);
        $count_vencidos = $stmt_vencidos->fetchColumn();
    }
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

    <main id="contenido-principal" class="flex-grow-1">
        <div class="container-fluid">
            <?php if ($asignacion_activa) : ?>
                <div class="welcome-container">
                    <div class="welcome-text-content">
                        <h1 class="welcome-title">Bienvenido de nuevo, <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong></h1>
                        <p class="pharmacy-info"><i class="fas fa-clinic-medical"></i>Gestionando Farmacia: <strong><?php echo htmlspecialchars($nombre_farmacia_asignada); ?></strong></p>
                    </div>
                    <div class="welcome-image-container">
                        <img src="../img/bodyfarma.png" alt="Imagen Farmaceuta" class="welcome-image">
                    </div>
                </div>

                <div class="alerts-grid">
                    <div class="alert-card position-relative <?php echo ($count_entregas_pendientes > 0) ? 'has-alerts' : ''; ?>">
                        <div class="alert-card-icon icon-deliveries"><i class="fas fa-file-prescription"></i></div>
                        <div class="alert-card-info">
                            <h5>Entregas Pendientes</h5>
                            <p>Pacientes esperando medicamentos.</p>
                        </div>
                        <div class="alert-card-count"><?php echo $count_entregas_pendientes; ?></div>
                        <a href="entregar/entregas_pendientes.php" class="stretched-link"></a>
                    </div>

                    <div class="alert-card <?php echo ($count_stock_bajo > 0) ? 'has-alerts' : ''; ?>" data-bs-toggle="modal" data-bs-target="#alertasModal" data-alert-type="stock-bajo">
                        <div class="alert-card-icon icon-stock"><i class="fas fa-cubes"></i></div>
                        <div class="alert-card-info">
                            <h5>Stock Bajo</h5>
                            <p>Medicamentos que necesitan reposición.</p>
                        </div>
                        <div class="alert-card-count"><?php echo $count_stock_bajo; ?></div>
                    </div>

                    <div class="alert-card <?php echo ($count_por_vencer > 0) ? 'has-alerts' : ''; ?>" data-bs-toggle="modal" data-bs-target="#alertasModal" data-alert-type="por-vencer">
                        <div class="alert-card-icon icon-expiring"><i class="fas fa-hourglass-half"></i></div>
                        <div class="alert-card-info">
                            <h5>Próximos a Vencer</h5>
                            <p>En los siguientes 30 días.</p>
                        </div>
                        <div class="alert-card-count"><?php echo $count_por_vencer; ?></div>
                    </div>

                    <div class="alert-card <?php echo ($count_vencidos > 0) ? 'has-alerts' : ''; ?>" data-bs-toggle="modal" data-bs-target="#alertasModal" data-alert-type="vencidos">
                        <div class="alert-card-icon icon-expired"><i class="fas fa-calendar-times"></i></div>
                        <div class="alert-card-info">
                            <h5>Productos Vencidos</h5>
                            <p>Retirar de inventario urgentemente.</p>
                        </div>
                        <div class="alert-card-count"><?php echo $count_vencidos; ?></div>
                    </div>
                </div>

            <?php else : ?>
                <div class="contenedor-bienvenida text-center mt-5">
                    <h1 class="mensaje-bienvenida-admin display-5 mb-3 text-warning"><i class="fas fa-exclamation-triangle me-2"></i>Atención</h1>
                    <p class="submensaje-bienvenida-admin lead mb-4">
                        Estimado/a <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>, actualmente no tiene una farmacia activa asignada.
                        <br>Para acceder a las funcionalidades, por favor, comuníquese con el administrador del sistema.
                    </p>
                    <img src="../img/bloqueo_acceso.png" alt="Acceso Restringido" class="imagen-rol img-fluid rounded mb-3" style="max-width: 200px; height: auto; border:none;">
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <div class="modal fade" id="alertasModal" tabindex="-1" aria-labelledby="alertasModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alertasModalLabel">Detalles de la Alerta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="alertasModalBody"></div>
                <div class="modal-footer" id="alertasModalFooter">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <div id="modal-secundario-placeholder"></div>

    <?php include '../include/footer.php'; ?>

    <script src="js/alertas_dashboard.js?v=<?php echo time(); ?>"></script>

    <?php if (!$asignacion_activa) : ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const userMenuToggleId = 'navbarDropdownUserMenu';
            const allNavLinksAndItems = document.querySelectorAll('.navbar-nav .nav-link, .navbar-nav .dropdown-item');
            
            allNavLinksAndItems.forEach(link => {
                const userMenuDropdown = link.closest('.dropdown-menu[aria-labelledby="' + userMenuToggleId + '"]');
                if (link.id !== userMenuToggleId && !userMenuDropdown) {
                    link.classList.add('disabled');
                    link.setAttribute('aria-disabled', 'true');
                    link.style.pointerEvents = 'none';
                    if (link.hasAttribute('data-bs-toggle')) {
                         link.removeAttribute('data-bs-toggle');
                    }
                }
            });
        });
    </script>
    <?php endif; ?>

</body>
</html>