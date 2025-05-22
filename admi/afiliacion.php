<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once '../include/conexion.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['pagina_anterior_afiliacion'])) {
    $_SESSION['pagina_anterior_afiliacion'] = $_SERVER['HTTP_REFERER'] ?? 'crear_usu.php';
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
$nombre_afiliado_display = 'Usuario no encontrado';
$tipo_doc_nombre_display = '';
$modal_regimen_list = [];
$modal_estado_list_afiliado = [];
$modal_php_error_message_init = '';


if (isset($_GET['doc_usu']) && isset($_GET['id_tipo_doc'])) {
    $doc_afiliado_get = trim($_GET['doc_usu']);
    $id_tipo_doc_get_val = trim($_GET['id_tipo_doc']);

    if ($con) {
        try {
            $sql_usuario = "SELECT u.nom_usu, ti.nom_doc 
                            FROM usuarios u 
                            JOIN tipo_identificacion ti ON u.id_tipo_doc = ti.id_tipo_doc
                            WHERE u.doc_usu = :doc_usu AND u.id_tipo_doc = :id_tipo_doc";
            $stmt_usuario = $con->prepare($sql_usuario);
            $stmt_usuario->bindParam(':doc_usu', $doc_afiliado_get, PDO::PARAM_STR);
            $stmt_usuario->bindParam(':id_tipo_doc', $id_tipo_doc_get_val, PDO::PARAM_INT);
            $stmt_usuario->execute();
            $usuario_info = $stmt_usuario->fetch(PDO::FETCH_ASSOC);

            if ($usuario_info) {
                $nombre_afiliado_display = htmlspecialchars($usuario_info['nom_usu']);
                $tipo_doc_nombre_display = htmlspecialchars($usuario_info['nom_doc']);

                $stmt_reg = $con->query("SELECT id_regimen, nom_reg FROM regimen ORDER BY nom_reg ASC");
                $modal_regimen_list = $stmt_reg->fetchAll(PDO::FETCH_ASSOC);
        
                $stmt_est = $con->query("SELECT id_est, nom_est FROM estado WHERE id_est IN (1, 2) ORDER BY nom_est ASC");
                $modal_estado_list_afiliado = $stmt_est->fetchAll(PDO::FETCH_ASSOC);

            } else {
                $page_error_message = "<div class='alert alert-danger'>No se encontró información para el usuario especificado.</div>";
            }
        } catch (PDOException $e) {
            $page_error_message = "<div class='alert alert-danger'>Error al consultar datos del usuario o listas: " . $e->getMessage() . "</div>";
             $modal_php_error_message_init = "Error al cargar datos para el formulario: " . $e->getMessage();
        }
    } else {
        $page_error_message = "<div class='alert alert-danger'>Error de conexión a la base de datos.</div>";
    }
} else {
    $page_error_message = "<div class='alert alert-danger'>No se ha proporcionado un documento y tipo de documento válidos para la afiliación.</div>";
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_afiliacion_modal_submit'])) {
    $response = ['success' => false, 'message' => 'Error desconocido.'];
    
    $doc_afiliado_post = trim($_POST['doc_afiliado_modal_hidden'] ?? '');
    $tipo_entidad_sel_post = trim($_POST['tipo_entidad_afiliacion_modal'] ?? '');
    $id_entidad_sel_post = '';

    if ($tipo_entidad_sel_post === 'eps' && isset($_POST['entidad_especifica_eps_modal'])) {
        $id_entidad_sel_post = trim($_POST['entidad_especifica_eps_modal']);
    } elseif ($tipo_entidad_sel_post === 'arl' && isset($_POST['entidad_especifica_arl_modal'])) {
        $id_entidad_sel_post = filter_input(INPUT_POST, 'entidad_especifica_arl_modal', FILTER_VALIDATE_INT);
    }

    $id_regimen_sel_post = filter_input(INPUT_POST, 'id_regimen_modal', FILTER_VALIDATE_INT);
    $id_estado_sel_post = filter_input(INPUT_POST, 'id_estado_modal', FILTER_VALIDATE_INT);
    $fecha_actual_db = date('Y-m-d H:i:s');

    if (empty($doc_afiliado_post)) {
        $response['message'] = "El documento del afiliado es requerido.";
    } elseif (empty($tipo_entidad_sel_post) || ($tipo_entidad_sel_post != 'eps' && $tipo_entidad_sel_post != 'arl')) {
        $response['message'] = "Debe seleccionar un tipo de entidad válido (EPS o ARL).";
    } elseif (empty($id_entidad_sel_post)) {
        $response['message'] = "Debe seleccionar una " . strtoupper($tipo_entidad_sel_post) . " específica.";
    } elseif (empty($id_regimen_sel_post)) {
        $response['message'] = "El régimen es requerido.";
    } elseif (empty($id_estado_sel_post)) {
        $response['message'] = "El estado de afiliación es requerido.";
    } else {
        if ($con) {
            try {
                $nit_eps_param = null;
                $id_arl_param = null;
                $columna_fk_eps_en_afiliados = 'id_eps'; 

                if ($tipo_entidad_sel_post === 'eps') {
                    $nit_eps_param = $id_entidad_sel_post; 
                } elseif ($tipo_entidad_sel_post === 'arl') {
                    $id_arl_param = (int)$id_entidad_sel_post; 
                }

                $stmt_check = $con->prepare("SELECT id_afiliacion FROM afiliados WHERE doc_afiliado = :doc_afiliado");
                $stmt_check->bindParam(':doc_afiliado', $doc_afiliado_post, PDO::PARAM_STR);
                $stmt_check->execute();
                $existe_afiliado_id = $stmt_check->fetchColumn();

                if ($existe_afiliado_id) {
                    $sql = "UPDATE afiliados SET fecha_afi = :fecha_afi, {$columna_fk_eps_en_afiliados} = :nit_eps_param, id_regimen = :id_regimen, id_arl = :id_arl_param, id_estado = :id_estado WHERE doc_afiliado = :doc_afiliado";
                } else {
                    $sql = "INSERT INTO afiliados (doc_afiliado, fecha_afi, {$columna_fk_eps_en_afiliados}, id_regimen, id_arl, id_estado) VALUES (:doc_afiliado, :fecha_afi, :nit_eps_param, :id_regimen, :id_arl_param, :id_estado)";
                }
                
                $stmt_guardar = $con->prepare($sql);
                $params_guardar = [
                    ':doc_afiliado' => $doc_afiliado_post, 
                    ':fecha_afi' => $fecha_actual_db,
                    ':nit_eps_param' => $nit_eps_param, 
                    ':id_regimen' => $id_regimen_sel_post,
                    ':id_arl_param' => $id_arl_param, 
                    ':id_estado' => $id_estado_sel_post
                ];

                if ($stmt_guardar->execute($params_guardar)) {
                    $response['success'] = true;
                    $response['message'] = "Afiliación guardada para " . htmlspecialchars($doc_afiliado_post) . ".";
                    $response['doc_afiliado'] = $doc_afiliado_post;
                } else {
                    $errorInfo = $stmt_guardar->errorInfo();
                    $response['message'] = "Error SQL: " . ($errorInfo[2] ?? 'Desconocido');
                }
            } catch (PDOException $e) {
                $response['message'] = "Error DB: " . $e->getMessage();
            }
        } else {
            $response['message'] = "Error de conexión al procesar formulario.";
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
    <title>Gestionar Afiliación de Usuario</title>

</head>
<body class="d-flex flex-column">
    <?php include '../include/menu.php'; ?>

    <main id="contenido-principal" class="flex-grow-1">
        <div class="page-content-wrapper">
            <div class="contenedor-info-usuario">
                <?php if (!empty($page_error_message)): ?>
                    <?php echo $page_error_message; ?>
                    <div class="text-center mt-3">
                        <a href="<?php echo htmlspecialchars($_SESSION['pagina_anterior_afiliacion'] ?? 'crear_usu.php'); ?>" class="btn btn-primary">Volver</a>
                    </div>
                <?php else: ?>
                    <div class="usuario-info-header">
                        <p class="mb-0"><strong><?php echo $tipo_doc_nombre_display; ?>:</strong> <?php echo htmlspecialchars($doc_afiliado_get); ?></p>
                    </div>
                    <?php 
                        if (!empty($modal_php_error_message_init)) {
                            echo "<div class='alert alert-danger'>$modal_php_error_message_init</div>";
                        }
                        include 'modal_afiliacion_usu.php'; 
                    ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/afiliacion_modal.js"></script>

    <?php if (empty($page_error_message) && !empty($doc_afiliado_get) && !empty($id_tipo_doc_get_val)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const docUsuarioJS = '<?php echo addslashes(htmlspecialchars($doc_afiliado_get)); ?>';
            const tipoDocUsuarioJS = '<?php echo addslashes(htmlspecialchars($id_tipo_doc_get_val)); ?>';
            const paginaAnteriorJS = '<?php echo addslashes(htmlspecialchars($_SESSION['pagina_anterior_afiliacion'] ?? "crear_usu.php")); ?>';
            
            if (docUsuarioJS && tipoDocUsuarioJS && typeof abrirModalAfiliacion === 'function') {
                abrirModalAfiliacion(docUsuarioJS, tipoDocUsuarioJS);
            } else {
                console.error("No se pudo abrir el modal de afiliación automáticamente. Datos: ", docUsuarioJS, tipoDocUsuarioJS);
                const errorDivModal = document.getElementById('modalAfiliacionGlobalError');
                if(errorDivModal){
                     errorDivModal.innerHTML = '<div class="alert alert-danger">Error al preparar el formulario de afiliación. Por favor, intente de nuevo o contacte a soporte.</div>';
                } else if(document.querySelector('.contenedor-info-usuario')){
                     document.querySelector('.contenedor-info-usuario').innerHTML += '<div class="alert alert-danger mt-3">Error al preparar el formulario de afiliación. Por favor, intente de nuevo o contacte a soporte.</div>';
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
        unset($_SESSION['pagina_anterior_afiliacion']);
    endif; 
    ?>
</body>
</html>