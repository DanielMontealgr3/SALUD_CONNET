<?php
require_once '../../../include/validar_sesion.php';
require_once '../../../include/inactividad.php';
require_once '../../../include/conexion.php';

$con = null;
if (class_exists('database')) {
    $db_instance = new database();
    if ($db_instance) {
        $con = $db_instance->conectar();
    }
}

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 || !isset($_SESSION['nombre_usuario'])) {
    header('Location: ../../inicio_sesion.php');
    exit;
}

$redirect_to_view = false;
if (isset($_GET['origen'])) {
    $origen_valido = basename(filter_var($_GET['origen'], FILTER_SANITIZE_URL));
    if (!empty($origen_valido) && $origen_valido === 'ver_tipos_enfermedad') {
        $_SESSION['redirect_after_create_enf_generic'] = 'ver_tipos_enfermedad.php';
        $redirect_to_view = true; 
    }
}

$tipo_enfermer_form = '';
$php_error_message = '';
$php_success_message = '';
$show_js_redirect = false; 

if (isset($_SESSION['form_data_crear_tipo_enf'])) {
    $form_data = $_SESSION['form_data_crear_tipo_enf'];
    $tipo_enfermer_form = trim($form_data['tipo_enfermer'] ?? $tipo_enfermer_form);
    unset($_SESSION['form_data_crear_tipo_enf']);
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_tipo_enfermedad'])) { 
    $tipo_enfermer_post = trim($_POST['tipo_enfermer'] ?? '');
    $tipo_enfermer_form = $tipo_enfermer_post;
    $errores_validacion = [];

    if (empty($tipo_enfermer_post)) {
        $errores_validacion[] = "El nombre del tipo de enfermedad es obligatorio.";
    } elseif (!preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\s.,()#-]+$/u', $tipo_enfermer_post)) {
        $errores_validacion[] = "El nombre contiene caracteres no permitidos.";
    } elseif (strlen($tipo_enfermer_post) < 3 || strlen($tipo_enfermer_post) > 150) {
        $errores_validacion[] = "El nombre debe tener entre 3 y 150 caracteres.";
    }
    
    if (!$con) {
        $php_error_message = "<div class='alert alert-danger'>Error crítico: No se pudo establecer la conexión inicial a la base de datos.</div>";
        $_SESSION['form_data_crear_tipo_enf'] = $_POST;
    } elseif (empty($errores_validacion)) {
        try {
            $sql_check_existencia_nombre = "SELECT id_tipo_enfer FROM tipo_enfermedades WHERE tipo_enfermer = :tipo_enfermer";
            $stmt_check_nombre = $con->prepare($sql_check_existencia_nombre);
            $stmt_check_nombre->execute([':tipo_enfermer' => $tipo_enfermer_post]);

            if ($stmt_check_nombre->rowCount() > 0) {
                $php_error_message = "<div class='alert alert-danger error-servidor-especifico' data-campo-error='tipo_enfermer'>El tipo de enfermedad '" . htmlspecialchars($tipo_enfermer_post) . "' ya existe.</div>";
                $_SESSION['form_data_crear_tipo_enf'] = $_POST;
            } else {
                $sql_insert = "INSERT INTO tipo_enfermedades (tipo_enfermer) VALUES (:tipo_enfermer)";
                $stmt_insert = $con->prepare($sql_insert);
                
                if ($stmt_insert->execute([':tipo_enfermer' => $tipo_enfermer_post])) {
                    unset($_SESSION['form_data_crear_tipo_enf']);
                    $tipo_enfermer_form = ''; 

                    if ($redirect_to_view && isset($_SESSION['redirect_after_create_enf_generic'])) {
                        $redirect_url = $_SESSION['redirect_after_create_enf_generic'];
                        unset($_SESSION['redirect_after_create_enf_generic']);
                        $_SESSION['mensaje_accion'] = 'Tipo de enfermedad \'' . htmlspecialchars($tipo_enfermer_post) . '\' creado exitosamente.';
                        $_SESSION['mensaje_accion_tipo'] = 'success';
                        header('Location: ' . $redirect_url);
                        exit;
                    } else {
                        $php_success_message = "<div class='alert alert-success'>Tipo de enfermedad '" . htmlspecialchars($tipo_enfermer_post) . "' insertado exitosamente.</div>";
                        $show_js_redirect = true; 
                    }
                } else {
                    $errorInfo = $stmt_insert->errorInfo();
                    $php_error_message = "<div class='alert alert-danger'>Error SQL al insertar: " . htmlspecialchars($errorInfo[2] ?? 'Desconocido') . "</div>"; 
                    $_SESSION['form_data_crear_tipo_enf'] = $_POST;
                }
            }
        } catch (PDOException $e) {
            $php_error_message = "<div class='alert alert-danger'>PDOException: " . htmlspecialchars($e->getMessage()) . "</div>"; 
            error_log("PDOException en crear_tipo_enfermedad.php: " . $e->getMessage());
            $_SESSION['form_data_crear_tipo_enf'] = $_POST;
        }
    } elseif (!empty($errores_validacion)) {
        $php_error_message = "<div class='alert alert-danger'><ul>";
        foreach ($errores_validacion as $error) {
            $php_error_message .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $php_error_message .= "</ul></div>";
        $_SESSION['form_data_crear_tipo_enf'] = $_POST;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../../img/loguito.png">
    <title>Insertar Tipo de Enfermedad - Salud Connected</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../../css/estilos_form.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1">
        <div class="container-fluid mt-4">
            <div class="form-container mx-auto" style="max-width: 600px;">
                <h3 class="text-center mb-4">Insertar Nuevo Tipo de Enfermedad</h3>
                
                <div id="mensajesServidorGlobal">
                    <?php
                        if (!empty($php_error_message)) { echo $php_error_message; }
                        if (!empty($php_success_message)) { echo $php_success_message; }
                    ?>
                </div>

                <form id="formCrearTipoEnfermedad" action="crear_tipo_enfermedad.php<?php echo $redirect_to_view && isset($_SESSION['redirect_after_create_enf_generic']) ? '?origen=ver_tipos_enfermedad' : ''; ?>" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="tipo_enfermer" class="form-label">Nombre del Tipo de Enfermedad (*):</label>
                        <input type="text" id="tipo_enfermer" name="tipo_enfermer" class="form-control" value="<?php echo htmlspecialchars($tipo_enfermer_form); ?>" required maxlength="150">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="text-center">
                        <button type="submit" name="crear_tipo_enfermedad" id="btnCrearTipoEnfermedadSubmit" class="btn btn-primary w-100" disabled> 
                            Insertar Tipo de Enfermedad <i class="bi bi-plus-circle"></i> 
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include '../../../include/footer.php'; ?>
    <script src="../../js/crear_tipo_enfermedad.js"></script> 
    <?php if ($show_js_redirect): ?>
    <script>
        setTimeout(function() {
            window.location.href = 'ver_tipos_enfermedad.php?mensaje_exito=Tipo de enfermedad creado exitosamente.';
        }, 3000); 
    </script>
    <?php endif; ?>
</body>
</html>