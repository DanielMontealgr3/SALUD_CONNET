<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once('../../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) {
    header('Location: ../inicio_sesion.php'); exit;
}
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf_token = $_SESSION['csrf_token'];

$db = new database();
$con = $db->conectar();
$eps_para_filtro = [];
$error_db = '';

if ($con) {
    try {
        $stmt_eps = $con->query("SELECT DISTINCT nit_eps, nombre_eps FROM eps ORDER BY nombre_eps ASC");
        $eps_para_filtro = $stmt_eps->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_db = "Error al cargar las EPS para el filtro: " . $e->getMessage();
    }
} else {
    $error_db = "Error de conexión a la base de datos.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="../../img/loguito.png">
    <title>SaludConnect - Lista de Pacientes</title>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="titulo-lista-tabla m-0 border-0 p-0">Lista de Pacientes</h3>
                    <a href="../gestion_crear/crear_usu.php" class="btn btn-success btn-sm flex-shrink-0">
                        <i class="bi bi-plus-circle-fill"></i> Nuevo Paciente
                    </a>
                </div>

                <div id="feedback_container"></div>
                <?php if (!empty($error_db)) : ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_db); ?></div><?php endif; ?>
                
                <div class="filtros-tabla-container">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="filtro_eps" class="form-label">Filtrar por EPS / Afiliación:</label>
                            <select id="filtro_eps" class="form-select form-select-sm">
                                <option value="">Todos los pacientes</option>
                                <option value="sin_afiliacion_activa">Sin Afiliación Activa</option>
                                <?php foreach ($eps_para_filtro as $eps_item): ?>
                                    <option value="<?php echo htmlspecialchars($eps_item['nit_eps']); ?>">
                                        <?php echo htmlspecialchars($eps_item['nombre_eps']); ?> (Activos)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtro_doc_paciente" class="form-label">Buscar por Documento:</label>
                            <input type="text" id="filtro_doc_paciente" class="form-control form-control-sm" placeholder="Documento...">
                        </div>
                        <div class="col-md-3">
                            <label for="filtro_estado_paciente" class="form-label">Estado Usuario:</label>
                            <select id="filtro_estado_paciente" class="form-select form-select-sm">
                                <option value="">Todos (Excepto Eliminados)</option>
                                <option value="1">Activo</option>
                                <option value="2">Inactivo</option>
                                <option value="17">Eliminados</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-grid">
                            <button type="button" id="btn_limpiar_filtros" class="btn btn-sm btn-outline-secondary">Limpiar Filtros</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="tabla-admin-mejorada">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Nombre</th>
                                <th>Correo</th>
                                <th>EPS (Activa)</th>
                                <th>Estado Usuario</th>
                                <!-- INICIO DE LA MODIFICACIÓN -->
                                <th class="columna-acciones-fija">Acciones</th>
                                <!-- FIN DE LA MODIFICACIÓN -->
                            </tr>
                        </thead>
                        <tbody id="tabla_pacientes_body">
                           <tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>
                        </tbody>
                    </table>
                </div>
                <nav aria-label="Paginación de pacientes" class="mt-3 paginacion-tabla-container">
                    <ul class="pagination pagination-sm" id="paginacion_lista"></ul>
                </nav>
            </div>    
        </div>
    </main>
    <?php include '../../include/footer.php'; ?>
    <div id="modalContainer"></div>
    <div class="modal fade" id="responseModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-custom"><div class="modal-content modal-content-custom"><div class="modal-body text-center p-4"><div class="modal-icon-container"><div id="modalIcon"></div></div><h4 class="mt-3 fw-bold" id="modalTitle"></h4><p id="modalMessage" class="mt-2 text-muted"></p></div><div class="modal-footer-custom"><button type="button" class="btn btn-primary-custom" data-bs-dismiss="modal">OK</button></div></div></div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>const csrfToken = '<?php echo $csrf_token; ?>';</script>
    <script src="../js/lista_pacientes.js?v=<?php echo time(); ?>"></script>
    <script src="../js/editar_usuario_admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>