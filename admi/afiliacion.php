<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once '../include/conexion.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }

$pagina_anterior_para_volver = $_SESSION['pagina_anterior_afiliacion'] ?? $_SERVER['HTTP_REFERER'] ?? 'crear_usu.php';
if (isset($_SESSION['pagina_anterior_afiliacion'])) {
    unset($_SESSION['pagina_anterior_afiliacion']);
}


if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    header('Location: ../inicio_sesion.php');
    exit;
}

$conex_db = new database();
$con = $conex_db->conectar();

$page_error_message = '';
$doc_afiliado_get = '';
$id_tipo_doc_get_val = '';
$modal_regimen_list = [];
$modal_estado_list_afiliado = [];
$modal_php_error_message_init = '';
$nombre_afiliado_display_for_title = 'Usuario';


if (isset($_GET['doc_usu']) && !empty(trim($_GET['doc_usu'])) && isset($_GET['id_tipo_doc']) && !empty(trim($_GET['id_tipo_doc']))) {
    $doc_afiliado_get = trim($_GET['doc_usu']);
    $id_tipo_doc_get_val = trim($_GET['id_tipo_doc']);

    if ($con) {
        try {
            $sql_usuario_nom = "SELECT nom_usu FROM usuarios WHERE doc_usu = :doc_usu AND id_tipo_doc = :id_tipo_doc";
            $stmt_usuario_nom = $con->prepare($sql_usuario_nom);
            $stmt_usuario_nom->bindParam(':doc_usu', $doc_afiliado_get, PDO::PARAM_STR);
            $stmt_usuario_nom->bindParam(':id_tipo_doc', $id_tipo_doc_get_val, PDO::PARAM_INT);
            $stmt_usuario_nom->execute();
            $usuario_nom_info = $stmt_usuario_nom->fetch(PDO::FETCH_ASSOC);
            
            if($usuario_nom_info && !empty($usuario_nom_info['nom_usu'])){
                $nombre_afiliado_display_for_title = htmlspecialchars($usuario_nom_info['nom_usu']);
            } else {
                $page_error_message = "<div class='alert alert-danger'>No se encontró un usuario con el documento y tipo de documento proporcionados.</div>";
            }

            if(empty($page_error_message)) {
                $stmt_reg = $con->query("SELECT id_regimen, nom_reg FROM regimen ORDER BY nom_reg ASC");
                $modal_regimen_list = $stmt_reg->fetchAll(PDO::FETCH_ASSOC);
            
                $stmt_est = $con->query("SELECT id_est, nom_est FROM estado WHERE id_est IN (1, 2) ORDER BY FIELD(id_est, 1, 2), nom_est ASC");
                $modal_estado_list_afiliado = $stmt_est->fetchAll(PDO::FETCH_ASSOC);
            }

        } catch (PDOException $e) {
            $page_error_message = "<div class='alert alert-danger'>Error al cargar datos iniciales para la afiliación: " . htmlspecialchars($e->getMessage()) . "</div>";
            $modal_php_error_message_init = "Error al cargar datos para el formulario: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $page_error_message = "<div class='alert alert-danger'>Error de conexión a la base de datos. No se pueden cargar los datos para la afiliación.</div>";
    }
} else {
    $page_error_message = "<div class='alert alert-danger'>Parámetros incompletos o incorrectos. No se puede procesar la solicitud de afiliación. Por favor, regrese e inténtelo de nuevo.</div>";
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_afiliacion_modal_submit'])) {
    $response = ['success' => false, 'message' => 'Error desconocido al procesar la afiliación.'];
    
    $doc_afiliado_post = trim($_POST['doc_afiliado_modal_hidden'] ?? '');
    $tipo_entidad_sel_post = trim($_POST['tipo_entidad_afiliacion_modal'] ?? '');
    
    $id_eps_valor = null;
    $id_arl_valor = null;

    if ($tipo_entidad_sel_post === 'eps') {
        $id_eps_valor = trim($_POST['entidad_especifica_eps_modal'] ?? null); 
    } elseif ($tipo_entidad_sel_post === 'arl') {
        $id_arl_valor = filter_input(INPUT_POST, 'entidad_especifica_arl_modal', FILTER_VALIDATE_INT);
    }

    $id_regimen_sel_post = filter_input(INPUT_POST, 'id_regimen_modal', FILTER_VALIDATE_INT);
    $id_estado_sel_post = filter_input(INPUT_POST, 'id_estado_modal', FILTER_VALIDATE_INT);
    $fecha_actual_db = date('Y-m-d');

    if (empty($doc_afiliado_post)) {
        $response['message'] = "El documento del afiliado es un campo requerido.";
    } elseif (empty($tipo_entidad_sel_post) || !in_array($tipo_entidad_sel_post, ['eps', 'arl'])) {
        $response['message'] = "Debe seleccionar un tipo de entidad válido (EPS o ARL).";
    } elseif ($tipo_entidad_sel_post === 'eps' && (empty($id_eps_valor) || !is_string($id_eps_valor) )) {
        $response['message'] = "Debe seleccionar una EPS específica.";
    } elseif ($tipo_entidad_sel_post === 'arl' && (empty($id_arl_valor) || !is_numeric($id_arl_valor))) {
        $response['message'] = "Debe seleccionar una ARL específica.";
    } elseif (empty($id_regimen_sel_post) || !is_numeric($id_regimen_sel_post)) {
        $response['message'] = "El régimen es un campo requerido.";
    } elseif (empty($id_estado_sel_post) || !is_numeric($id_estado_sel_post)) {
        $response['message'] = "El estado de afiliación es un campo requerido.";
    } else {
        if ($con) {
            try {
                $stmt_check = $con->prepare("SELECT id_afiliacion FROM afiliados WHERE doc_afiliado = :doc_afiliado");
                $stmt_check->bindParam(':doc_afiliado', $doc_afiliado_post, PDO::PARAM_STR);
                $stmt_check->execute();
                $id_afiliacion_existente = $stmt_check->fetchColumn();

                if ($id_afiliacion_existente) {
                    $sql = "UPDATE afiliados SET 
                                fecha_afi = :fecha_afi, 
                                id_eps = :id_eps, 
                                id_regimen = :id_regimen, 
                                id_arl = :id_arl, 
                                id_estado = :id_estado 
                            WHERE id_afiliacion = :id_afiliacion";
                } else {
                    $sql = "INSERT INTO afiliados (doc_afiliado, fecha_afi, id_eps, id_regimen, id_arl, id_estado) 
                            VALUES (:doc_afiliado, :fecha_afi, :id_eps, :id_regimen, :id_arl, :id_estado)";
                }
                
                $stmt_guardar = $con->prepare($sql);
                
                $params_guardar = [
                    ':fecha_afi' => $fecha_actual_db,
                    ':id_eps' => ($tipo_entidad_sel_post === 'eps' ? $id_eps_valor : null),
                    ':id_regimen' => $id_regimen_sel_post,
                    ':id_arl' => ($tipo_entidad_sel_post === 'arl' ? $id_arl_valor : null),
                    ':id_estado' => $id_estado_sel_post
                ];

                if ($id_afiliacion_existente) {
                    $params_guardar[':id_afiliacion'] = $id_afiliacion_existente;
                } else {
                    $params_guardar[':doc_afiliado'] = $doc_afiliado_post;
                }

                if ($stmt_guardar->execute($params_guardar)) {
                    $response['success'] = true;
                    $accion = $id_afiliacion_existente ? "actualizada" : "registrada";
                    $response['message'] = "Afiliación " . $accion . " exitosamente para el documento " . htmlspecialchars($doc_afiliado_post) . ".";
                } else {
                    $errorInfo = $stmt_guardar->errorInfo();
                    $response['message'] = "Error SQL al guardar la afiliación: " . htmlspecialchars($errorInfo[2] ?? 'Desconocido');
                }
            } catch (PDOException $e) {
                $response['message'] = "Excepción de Base de Datos al guardar: " . htmlspecialchars($e->getMessage());
            }
        } else {
            $response['message'] = "Error de conexión a la base de datos al intentar procesar el formulario.";
        }
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit; 
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Afiliación de <?php echo $nombre_afiliado_display_for_title; ?></title>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../include/menu.php'; ?>

    <main id="contenido-principal" class="flex-grow-1 py-4">
        <div class="container">
            <?php if (!empty($page_error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $page_error_message; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="<?php echo htmlspecialchars($pagina_anterior_para_volver); ?>" class="btn btn-secondary">Volver</a>
                </div>
            <?php else: ?>
                <?php 
                    if (!empty($modal_php_error_message_init)) {
                        echo "<div class='alert alert-warning'>$modal_php_error_message_init. El formulario puede no funcionar correctamente.</div>";
                    }
                    include 'modal_afiliacion_usu.php'; 
                ?>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/afiliacion_modal.js"></script>

    <?php 
    // El script para abrir el modal y manejar su cierre SÓLO se incluye si NO hay error de página
    if (empty($page_error_message) && !empty($doc_afiliado_get) && !empty($id_tipo_doc_get_val)): 
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const docUsuarioJS = '<?php echo addslashes(htmlspecialchars($doc_afiliado_get)); ?>';
            const tipoDocUsuarioJS = '<?php echo addslashes(htmlspecialchars($id_tipo_doc_get_val)); ?>';
            const paginaAnteriorJS = '<?php echo addslashes(htmlspecialchars($pagina_anterior_para_volver)); ?>'; 
            
            if (docUsuarioJS && tipoDocUsuarioJS && typeof abrirModalAfiliacion === 'function') {
                abrirModalAfiliacion(docUsuarioJS, tipoDocUsuarioJS);
            } else {
                console.error("Error crítico: No se pudo abrir el modal de afiliación o la función no está definida. Doc:", docUsuarioJS, "TipoDoc:", tipoDocUsuarioJS);
                const mainContent = document.getElementById('contenido-principal');
                if (mainContent) {
                    const errorDivContainer = document.createElement('div');
                    errorDivContainer.className = 'container mt-3';
                    errorDivContainer.innerHTML = '<div class="alert alert-danger">Error crítico: No se pudo inicializar el formulario de afiliación. Por favor, <a href="' + paginaAnteriorJS + '" class="alert-link">intente de nuevo</a> o contacte a soporte.</div>';
                    mainContent.insertBefore(errorDivContainer, mainContent.firstChild);
                }
            }

            const modalAfiliacionElementJS = document.getElementById('modalAfiliacionUsuario');
            if (modalAfiliacionElementJS) {
                modalAfiliacionElementJS.addEventListener('hidden.bs.modal', function (event) {
                    window.location.href = paginaAnteriorJS;
                });
            }
        });
    </script>
    <?php 
    endif; 
    ?>
</body>
</html>