<?php
// --- BLOQUE 1: CONFIGURACIÓN INICIAL Y SEGURIDAD ---
// Se incluye el archivo de configuración central. Este archivo es crucial ya que define
// las constantes ROOT_PATH y BASE_URL, y establece la conexión a la base de datos ($con).
// El uso de __DIR__ . '/../' asegura que la ruta funcione sin importar dónde esté el proyecto.
require_once __DIR__ . '/../include/config.php';

// Se incluyen los scripts de seguridad para validar que el usuario tenga una sesión activa
// y para manejar el tiempo de inactividad, cerrando la sesión si es necesario.
// Usamos ROOT_PATH para garantizar que la ruta sea siempre absoluta desde la raíz del proyecto.
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

// --- BLOQUE 2: VERIFICACIÓN DE ROL Y ACCESO ---
// Se verifica que el usuario haya iniciado sesión y que tenga el rol de Farmaceuta (id_rol = 3).
// Si no cumple con estas condiciones, se le redirige a la página de inicio de sesión con un mensaje de error.
// La constante BASE_URL asegura que la redirección funcione en local y en el hosting.
if ($_SESSION['id_rol'] != 3) {
    header('Location: ' . BASE_URL . '/inicio_sesion.php?error=rol_invalido');
    exit;
}

// --- BLOQUE 3: OBTENCIÓN DE DATOS DE SESIÓN Y PREPARACIÓN DE VARIABLES ---
// Se obtienen los datos del farmaceuta desde la sesión para personalizar la página.
$nombre_usuario = $_SESSION['nom_usu'] ?? 'Usuario';
$documento_farmaceuta = $_SESSION['doc_usu'];
$pageTitle = "Inicio Farmaceuta";

// Se inicializan las variables que contendrán la información de la farmacia y los contadores de alertas.
$asignacion_activa = false;
$nombre_farmacia_asignada = "";
$nit_farmacia_asignada = "";

$count_entregas_pendientes = 0;
$count_stock_bajo = 0;
$count_por_vencer = 0;
$count_vencidos = 0;

// --- BLOQUE 4: LÓGICA DE NEGOCIO Y CONSULTAS A LA BASE DE DATOS ---
// Este bloque solo se ejecuta si el farmaceuta tiene un documento válido.
if ($documento_farmaceuta) {
    
    // 1. Se busca si el farmaceuta tiene una farmacia activa asignada (id_estado = 1).
    $sql_asignacion = "SELECT f.nom_farm, af.nit_farma FROM asignacion_farmaceuta af JOIN farmacias f ON af.nit_farma = f.nit_farm WHERE af.doc_farma = :doc_farma AND af.id_estado = 1 LIMIT 1";
    $stmt_asignacion = $con->prepare($sql_asignacion);
    $stmt_asignacion->bindParam(':doc_farma', $documento_farmaceuta, PDO::PARAM_STR);
    $stmt_asignacion->execute();
    $fila_asignacion = $stmt_asignacion->fetch(PDO::FETCH_ASSOC);

    // 2. Si se encuentra una asignación, se procede a contar las diferentes alertas.
    if ($fila_asignacion) {
        $asignacion_activa = true;
        $nombre_farmacia_asignada = $fila_asignacion['nom_farm'];
        $nit_farmacia_asignada = $fila_asignacion['nit_farma'];
        $_SESSION['nit_farma'] = $nit_farmacia_asignada; // Se guarda en sesión para uso en otras páginas.

        // 2.1. Contar entregas pendientes (id_estado = 10).
        $sql_entregas = "SELECT COUNT(DISTINCT ep.id_entrega_pendiente) FROM entrega_pendiente ep JOIN detalles_histo_clini dh ON ep.id_detalle_histo = dh.id_detalle JOIN historia_clinica hc ON dh.id_historia = hc.id_historia JOIN citas c ON hc.id_cita = c.id_cita JOIN usuarios u ON c.doc_pac = u.doc_usu JOIN afiliados a ON u.doc_usu = a.doc_afiliado JOIN detalle_eps_farm def ON a.id_eps = def.nit_eps WHERE def.nit_farm = :nit AND ep.id_estado = 10";
        $stmt_entregas = $con->prepare($sql_entregas);
        $stmt_entregas->execute(['nit' => $nit_farmacia_asignada]);
        $count_entregas_pendientes = $stmt_entregas->fetchColumn();

        // 2.2. Contar medicamentos con stock bajo (id_estado = 14: Pocas unidades).
        $sql_stock = "SELECT COUNT(*) FROM inventario_farmacia WHERE nit_farm = :nit AND id_estado = 14";
        $stmt_stock = $con->prepare($sql_stock);
        $stmt_stock->execute(['nit' => $nit_farmacia_asignada]);
        $count_stock_bajo = $stmt_stock->fetchColumn();

        // Subconsulta reutilizable para obtener el stock real de un lote específico.
        $stock_por_lote_sql = "(SELECT SUM(CASE WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad WHEN id_tipo_mov IN (2, 4) THEN -cantidad ELSE 0 END) FROM movimientos_inventario WHERE lote = mi.lote AND id_medicamento = mi.id_medicamento AND nit_farm = mi.nit_farm)";

        // 2.3. Contar lotes de productos próximos a vencer (en los siguientes 30 días) que aún tengan stock.
        $sql_por_vencer = "SELECT COUNT(DISTINCT mi.lote, mi.id_medicamento) FROM movimientos_inventario mi WHERE mi.nit_farm = :nit AND mi.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND $stock_por_lote_sql > 0";
        $stmt_por_vencer = $con->prepare($sql_por_vencer);
        $stmt_por_vencer->execute(['nit' => $nit_farmacia_asignada]);
        $count_por_vencer = $stmt_por_vencer->fetchColumn();
        
        // 2.4. Contar lotes de productos ya vencidos que aún tengan stock.
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
    <!-- --- BLOQUE 5: METADATOS Y ENLACES CSS/JS DEL HEAD --- -->
    <!-- Se usa la constante BASE_URL para asegurar que la ruta a los recursos (imágenes, css, etc.) sea correcta en cualquier entorno. -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/loguito.png">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
    <!-- Se incluye el menú principal. La constante ROOT_PATH garantiza que la ruta es absoluta en el servidor. -->
    <?php require_once ROOT_PATH . '/include/menu.php'; ?>
</head>
<body class="d-flex flex-column min-vh-100">

    <main id="contenido-principal" class="flex-grow-1">
        <div class="container-fluid">
            <!-- --- BLOQUE 6: CONTENIDO PRINCIPAL DE LA PÁGINA --- -->
            <?php if ($asignacion_activa) : ?>
                <!-- Si el farmaceuta TIENE una farmacia asignada, se muestra el panel de bienvenida y las tarjetas de alertas. -->
                <div class="welcome-container">
                    <div class="welcome-text-content">
                        <h1 class="welcome-title">Bienvenido de nuevo, <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong></h1>
                        <p class="pharmacy-info"><i class="fas fa-clinic-medical"></i>Gestionando Farmacia: <strong><?php echo htmlspecialchars($nombre_farmacia_asignada); ?></strong></p>
                    </div>
                    <div class="welcome-image-container">
                        <!-- La ruta a la imagen usa BASE_URL para que el navegador la encuentre correctamente. -->
                        <img src="<?php echo BASE_URL; ?>/img/bodyfarma.png" alt="Imagen Farmaceuta" class="welcome-image">
                    </div>
                </div>

                <div class="alerts-grid">
                    <!-- Tarjeta de Entregas Pendientes -->
                    <div class="alert-card position-relative <?php echo ($count_entregas_pendientes > 0) ? 'has-alerts' : ''; ?>">
                        <div class="alert-card-icon icon-deliveries"><i class="fas fa-file-prescription"></i></div>
                        <div class="alert-card-info">
                            <h5>Entregas Pendientes</h5>
                            <p>Pacientes esperando medicamentos.</p>
                        </div>
                        <div class="alert-card-count"><?php echo $count_entregas_pendientes; ?></div>
                        <a href="entregar/entregas_pendientes.php" class="stretched-link"></a>
                    </div>

                    <!-- Tarjeta de Stock Bajo -->
                    <div class="alert-card <?php echo ($count_stock_bajo > 0) ? 'has-alerts' : ''; ?>" data-bs-toggle="modal" data-bs-target="#alertasModal" data-alert-type="stock-bajo">
                        <div class="alert-card-icon icon-stock"><i class="fas fa-cubes"></i></div>
                        <div class="alert-card-info">
                            <h5>Stock Bajo</h5>
                            <p>Medicamentos que necesitan reposición.</p>
                        </div>
                        <div class="alert-card-count"><?php echo $count_stock_bajo; ?></div>
                    </div>

                    <!-- Tarjeta de Próximos a Vencer -->
                    <div class="alert-card <?php echo ($count_por_vencer > 0) ? 'has-alerts' : ''; ?>" data-bs-toggle="modal" data-bs-target="#alertasModal" data-alert-type="por-vencer">
                        <div class="alert-card-icon icon-expiring"><i class="fas fa-hourglass-half"></i></div>
                        <div class="alert-card-info">
                            <h5>Próximos a Vencer</h5>
                            <p>En los siguientes 30 días.</p>
                        </div>
                        <div class="alert-card-count"><?php echo $count_por_vencer; ?></div>
                    </div>

                    <!-- Tarjeta de Productos Vencidos -->
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
                <!-- Si el farmaceuta NO TIENE una farmacia asignada, se muestra un mensaje de bloqueo. -->
                <div class="contenedor-bienvenida text-center mt-5">
                    <h1 class="mensaje-bienvenida-admin display-5 mb-3 text-warning"><i class="fas fa-exclamation-triangle me-2"></i>Atención</h1>
                    <p class="submensaje-bienvenida-admin lead mb-4">
                        Estimado/a <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>, actualmente no tiene una farmacia activa asignada.
                        <br>Para acceder a las funcionalidades, por favor, comuníquese con el administrador del sistema.
                    </p>
                    <img src="<?php echo BASE_URL; ?>/img/bloqueo_acceso.png" alt="Acceso Restringido" class="imagen-rol img-fluid rounded mb-3" style="max-width: 200px; height: auto; border:none;">
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- --- BLOQUE 7: MODALES Y SCRIPTS FINALES --- -->
    <!-- Modal genérico para mostrar los detalles de las alertas que se cargan con AJAX. -->
    <div class="modal fade" id="alertasModal" tabindex="-1" aria-labelledby="alertasModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alertasModalLabel">Detalles de la Alerta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="alertasModalBody">
                    <!-- El contenido se cargará dinámicamente aquí -->
                </div>
                <div class="modal-footer" id="alertasModalFooter">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Incluir el footer, llamado con una ruta absoluta desde la raíz del proyecto. -->
    <?php require_once ROOT_PATH . '/include/footer.php'; ?>

    <!-- Se enlaza el script de JavaScript para esta página usando BASE_URL, para que el navegador lo encuentre. -->
    <script src="<?php echo BASE_URL; ?>/farma/js/alertas_dashboard.js?v=<?php echo time(); ?>"></script>

    <!-- Este script se ejecuta solo si el usuario no tiene farmacia, para deshabilitar los enlaces del menú y prevenir errores. -->
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