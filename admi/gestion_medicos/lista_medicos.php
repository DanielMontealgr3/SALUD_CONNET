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
$ips_para_filtro = [];
$error_db = '';

if ($con) {
    try {
        $stmt_ips_filter_list = $con->query("SELECT DISTINCT i.nit_ips, i.nom_ips FROM ips i ORDER BY i.nom_ips ASC");
        $ips_para_filtro = $stmt_ips_filter_list->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { $error_db = "Error al cargar las IPS para el filtro: " . $e->getMessage(); }
} else { $error_db = "Error de conexión a la base de datos."; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="../../img/loguito.png">
    <title>SaludConnect - Lista de Médicos</title>
    <style>
    .vista-datos-container {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
        min-height: 0;
    }
    .table-responsive {
        flex-grow: 1;
        overflow-y: auto;
    }
    .paginacion-tabla-container {
        flex-shrink: 0;
    }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                 <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="titulo-lista-tabla m-0 border-0 p-0">Lista de Médicos</h3>
                    <a href="../gestion_crear/crear_usu.php?rol=4" class="btn btn-success btn-sm flex-shrink-0">
                        <i class="bi bi-plus-circle-fill"></i> Nuevo Médico
                    </a>
                </div>
                <div id="feedback_container"></div>
                <?php if (!empty($error_db)) : ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_db); ?></div><?php endif; ?>
                
                <div class="filtros-tabla-container mb-4">
                    <div class="row g-2 align-items-end">
                        <div class="col-lg col-md-4">
                            <label for="filtro_ips" class="form-label">Filtrar por Asignación IPS:</label>
                            <select id="filtro_ips" class="form-select form-select-sm">
                                <option value="">Todas las Asignaciones</option>
                                <option value="sin_asignacion_activa">Sin Asignación Activa</option>
                                <option value="con_asignacion_inactiva">Con Asignación Inactiva</option>
                                <?php foreach ($ips_para_filtro as $ips_item): ?>
                                    <option value="<?php echo htmlspecialchars($ips_item['nit_ips']); ?>">
                                        <?php echo htmlspecialchars($ips_item['nom_ips']); ?> (Activa)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg col-md-4">
                            <label for="filtro_doc_medico" class="form-label">Buscar por Documento:</label>
                            <input type="text" id="filtro_doc_medico" class="form-control form-control-sm" placeholder="Documento...">
                        </div>
                        <div class="col-lg col-md-4">
                            <label for="filtro_estado_usuario" class="form-label">Estado Usuario:</label>
                            <select id="filtro_estado_usuario" class="form-select form-select-sm">
                                <option value="">Todos (Excepto Eliminados)</option>
                                <option value="1">Activo</option>
                                <option value="2">Inactivo</option>
                                <option value="17">Eliminados</option>
                            </select>
                        </div>
                        <div class="col-lg-auto col-md-12">
                           <div class="d-flex gap-2">
                                <button type="button" id="btn_limpiar_filtros" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eraser"></i> Limpiar</button>
                                <button type="button" id="btn_generar_reporte_medicos" class="btn btn-sm btn-success" disabled><i class="bi bi-file-earmark-excel-fill"></i> Reporte</button>
                            </div>
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
                                <th>Especialidad</th>
                                <th>IPS Activa(s)</th>
                                <th>Estado Usuario</th>
                                <th class="columna-acciones-fija">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla_medicos_body">
                           <tr><td colspan="7" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>
                        </tbody>
                    </table>
                </div>
                <nav aria-label="Paginación de médicos" class="mt-3 paginacion-tabla-container">
                    <ul class="pagination pagination-sm" id="paginacion_lista"></ul>
                </nav>
            </div>    
        </div>
    </main>
    <?php include '../../include/footer.php'; ?>
    
    <div id="modalContainer"></div>
    
    <div class="modal fade" id="modalConfirmarReporteMedicos" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-excel-fill me-2"></i>Generar Reporte de Médicos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Se generará un reporte en Excel con los siguientes filtros aplicados:</p>
                    <div id="confirmarReporteTextoMedicos" class="alert alert-light border"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnConfirmarGeneracionMedicos">
                        <i class="bi bi-check-circle-fill"></i> Confirmar y Generar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>const csrfToken = '<?php echo $csrf_token; ?>';</script>
    <script src="../js/lista_medicos.js?v=<?php echo time(); ?>"></script>
    <script src="../js/editar_usuario_admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>