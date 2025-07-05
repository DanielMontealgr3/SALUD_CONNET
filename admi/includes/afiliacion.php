<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }

$pagina_anterior_para_volver = $_GET['return_to'] ?? '../gestion_pacientes/lista_pacientes.php';
$_SESSION['pagina_anterior_afiliacion_actual'] = $pagina_anterior_para_volver;

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header('Location: ../../inicio_sesion.php');
    exit;
}

$conex_db = new database();
$con = $conex_db->conectar();

$page_error_message = '';
$doc_afiliado_get = '';
$id_tipo_doc_get_val = '';
$modal_regimen_list = [];
$modal_estado_list_afiliado = [];
$nombre_afiliado_display_for_title = 'Usuario';

if (isset($_GET['doc_usu']) && !empty(trim($_GET['doc_usu'])) && isset($_GET['id_tipo_doc']) && !empty(trim($_GET['id_tipo_doc']))) {
    $doc_afiliado_get = trim($_GET['doc_usu']);
    $id_tipo_doc_get_val = trim($_GET['id_tipo_doc']);

    if ($con) {
        try {
            $stmt_usuario_nom = $con->prepare("SELECT nom_usu FROM usuarios WHERE doc_usu = :doc_usu AND id_tipo_doc = :id_tipo_doc");
            $stmt_usuario_nom->bindParam(':doc_usu', $doc_afiliado_get, PDO::PARAM_STR);
            $stmt_usuario_nom->bindParam(':id_tipo_doc', $id_tipo_doc_get_val, PDO::PARAM_INT);
            $stmt_usuario_nom->execute();
            $usuario_nom_info = $stmt_usuario_nom->fetch(PDO::FETCH_ASSOC);
            
            if($usuario_nom_info && !empty($usuario_nom_info['nom_usu'])){
                $nombre_afiliado_display_for_title = htmlspecialchars($usuario_nom_info['nom_usu']);
            } else {
                $page_error_message = "<div class='alert alert-danger'>No se encontró un usuario con el documento proporcionado.</div>";
            }

            if(empty($page_error_message)) {
                $modal_regimen_list = $con->query("SELECT id_regimen, nom_reg FROM regimen ORDER BY nom_reg ASC")->fetchAll(PDO::FETCH_ASSOC);
                $modal_estado_list_afiliado = $con->query("SELECT id_est, nom_est FROM estado WHERE id_est IN (1, 2) ORDER BY FIELD(id_est, 1, 2)")->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $page_error_message = "<div class='alert alert-danger'>Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        $page_error_message = "<div class='alert alert-danger'>Error de conexión a la base de datos.</div>";
    }
} else {
    $page_error_message = "<div class='alert alert-danger'>Parámetros incompletos. Regrese e inténtelo de nuevo.</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Gestionar Afiliación de <?php echo $nombre_afiliado_display_for_title; ?></title>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 py-4">
        <div class="container">
            <?php if (!empty($page_error_message)): ?>
                <div class="alert alert-danger"><?php echo $page_error_message; ?></div>
                <div class="text-center mt-3"><a href="<?php echo htmlspecialchars($pagina_anterior_para_volver); ?>" class="btn btn-secondary">Volver</a></div>
            <?php else: ?>
                <?php include 'modal_afiliacion_usu.php'; ?>
            <?php endif; ?>
        </div>
    </main>
    <?php include '../../include/footer.php'; ?>
    <div class="modal fade" id="responseModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-custom"><div class="modal-content modal-content-custom"><div class="modal-body text-center p-4"><div class="modal-icon-container"><div id="modalIcon"></div></div><h4 class="mt-3 fw-bold" id="modalTitle"></h4><p id="modalMessage" class="mt-2 text-muted"></p></div><div class="modal-footer-custom"><button type="button" class="btn btn-primary-custom" data-bs-dismiss="modal">OK</button></div></div></div>
    </div>
    <script src="../js/afiliacion_modal.js?v=<?php echo time(); ?>"></script>
    <?php if (empty($page_error_message) && !empty($doc_afiliado_get)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const docUsuarioJS = '<?php echo addslashes(htmlspecialchars($doc_afiliado_get)); ?>';
            const tipoDocUsuarioJS = '<?php echo addslashes(htmlspecialchars($id_tipo_doc_get_val)); ?>';
            const paginaAnteriorJS = '<?php echo addslashes(htmlspecialchars($pagina_anterior_para_volver)); ?>';
            
            if (typeof abrirModalAfiliacion === 'function') {
                abrirModalAfiliacion(docUsuarioJS, tipoDocUsuarioJS);
            }
            
            const responseModalEl = document.getElementById('responseModal');
            responseModalEl.addEventListener('hidden.bs.modal', function (event) {
                if (window.lastAffiliationSuccess) {
                    window.location.href = paginaAnteriorJS;
                }
            });

            const modalAfiliacionEl = document.getElementById('modalAfiliacionUsuario');
            modalAfiliacionEl.addEventListener('hidden.bs.modal', function (event) {
                if (!window.lastAffiliationSuccess) {
                    window.location.href = paginaAnteriorJS;
                }
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>