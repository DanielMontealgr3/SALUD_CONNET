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
    if (!empty($origen_valido) && $origen_valido === 'ver_enfermedades') {
        $_SESSION['redirect_after_create_enf_generic'] = 'ver_enfermedades.php';
        $redirect_to_view = true; 
    }
}

$nom_enfer_form = '';
$id_tipo_enfer_sel_form = '';
$php_error_message = '';
$php_success_message = '';
$tipos_enfermedad_disponibles = [];
$formulario_deshabilitado = false;
$show_js_redirect = false; 

if ($con) {
    try {
        $stmt_tipos = $con->query("SELECT id_tipo_enfer, tipo_enfermer FROM tipo_enfermedades ORDER BY tipo_enfermer ASC");
        $tipos_enfermedad_disponibles = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);

        if (empty($tipos_enfermedad_disponibles)) {
            $php_error_message = "<div class='alert alert-warning'>No hay tipos de enfermedad creados. Por favor, <a href='crear_tipo_enfermedad.php' class='alert-link'>cree un tipo de enfermedad</a> primero.</div>";
            $formulario_deshabilitado = true;
        }
    } catch (PDOException $e) {
        $php_error_message = "<div class='alert alert-danger'>Error al cargar tipos de enfermedad: " . htmlspecialchars($e->getMessage()) . "</div>";
        $formulario_deshabilitado = true;
    }
} else {
    $php_error_message = "<div class='alert alert-danger'>Error de conexión a la base de datos.</div>";
    $formulario_deshabilitado = true;
}


if (isset($_SESSION['form_data_crear_enf'])) {
    $form_data = $_SESSION['form_data_crear_enf'];
    $nom_enfer_form = trim($form_data['nom_enfer'] ?? $nom_enfer_form);
    $id_tipo_enfer_sel_form = trim($form_data['id_tipo_enfer'] ?? $id_tipo_enfer_sel_form);
    unset($_SESSION['form_data_crear_enf']);
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_enfermedad']) && !$formulario_deshabilitado) { 
    $nom_enfer_post = trim($_POST['nom_enfer'] ?? '');
    $id_tipo_enfer_post = trim($_POST['id_tipo_enfer'] ?? '');

    $nom_enfer_form = $nom_enfer_post;
    $id_tipo_enfer_sel_form = $id_tipo_enfer_post;

    $errores_validacion = [];

    if (empty($nom_enfer_post)) {
        $errores_validacion[] = "El nombre de la enfermedad es obligatorio.";
    } elseif (!preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\s.,()#-]+$/u', $nom_enfer_post)) {
        $errores_validacion[] = "El nombre contiene caracteres no permitidos.";
    } elseif (strlen($nom_enfer_post) < 3 || strlen($nom_enfer_post) > 150) {
        $errores_validacion[] = "El nombre debe tener entre 3 y 150 caracteres.";
    }
    if (empty($id_tipo_enfer_post)) {
        $errores_validacion[] = "Debe seleccionar un tipo de enfermedad.";
    }
    
    if (!$con) {
        $php_error_message = "<div class='alert alert-danger'>Error crítico: No se pudo establecer la conexión inicial a la base de datos.</div>";
        $_SESSION['form_data_crear_enf'] = $_POST;
    } elseif (empty($errores_validacion)) {
        try {
            $sql_check_existencia = "SELECT id_enferme FROM enfermedades WHERE nom_enfer = :nom_enfer AND id_tipo_enfer = :id_tipo_enfer"; // Tabla: enfermedades
            $stmt_check = $con->prepare($sql_check_existencia);
            $stmt_check->execute([':nom_enfer' => $nom_enfer_post, ':id_tipo_enfer' => $id_tipo_enfer_post]);

            if ($stmt_check->rowCount() > 0) {
                $php_error_message = "<div class='alert alert-danger error-servidor-especifico' data-campo-error='nom_enfer'>La enfermedad '" . htmlspecialchars($nom_enfer_post) . "' ya existe para el tipo seleccionado.</div>";
                $_SESSION['form_data_crear_enf'] = $_POST;
            } else {
                $sql_insert = "INSERT INTO enfermedades (nom_enfer, id_tipo_enfer) VALUES (:nom_enfer, :id_tipo_enfer)"; // Tabla: enfermedades
                $stmt_insert = $con->prepare($sql_insert);
                
                if ($stmt_insert->execute([':nom_enfer' => $nom_enfer_post, ':id_tipo_enfer' => $id_tipo_enfer_post])) {
                    unset($_SESSION['form_data_crear_enf']);
                    $nom_enfer_form = ''; 
                    $id_tipo_enfer_sel_form = '';

                    if ($redirect_to_view && isset($_SESSION['redirect_after_create_enf_generic'])) {
                        $redirect_url = $_SESSION['redirect_after_create_enf_generic'];
                        unset($_SESSION['redirect_after_create_enf_generic']);
                        $_SESSION['mensaje_accion'] = 'Enfermedad \'' . htmlspecialchars($nom_enfer_post) . '\' creada exitosamente.';
                        $_SESSION['mensaje_accion_tipo'] = 'success';
                        header('Location: ' . $redirect_url);
                        exit;
                    } else {
                        $php_success_message = "<div class='alert alert-success'>Enfermedad '" . htmlspecialchars($nom_enfer_post) . "' insertada exitosamente.</div>";
                        $show_js_redirect = true; 
                    }
                } else {
                    $errorInfo = $stmt_insert->errorInfo();
                    $php_error_message = "<div class='alert alert-danger'>Error SQL al insertar: " . htmlspecialchars($errorInfo[2] ?? 'Desconocido') . "</div>"; 
                    $_SESSION['form_data_crear_enf'] = $_POST;
                }
            }
        } catch (PDOException $e) {
            $php_error_message = "<div class='alert alert-danger'>PDOException: " . htmlspecialchars($e->getMessage()) . "</div>"; 
            error_log("PDOException en crear_enfermedad.php: " . $e->getMessage());
            $_SESSION['form_data_crear_enf'] = $_POST;
        }
    } elseif (!empty($errores_validacion)) {
        $php_error_message = "<div class='alert alert-danger'><ul>";
        foreach ($errores_validacion as $error) {
            $php_error_message .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $php_error_message .= "</ul></div>";
        $_SESSION['form_data_crear_enf'] = $_POST;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../../img/loguito.png">
    <title>Insertar Nueva Enfermedad - Salud Connected</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../../css/estilos_form.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1">
        <div class="container-fluid mt-4">
            <div class="form-container mx-auto" style="max-width: 600px;">
                <h3 class="text-center mb-4">Insertar Nueva Enfermedad</h3>
                
                <div id="mensajesServidorGlobal">
                    <?php
                        if (!empty($php_error_message)) { echo $php_error_message; }
                        if (!empty($php_success_message)) { echo $php_success_message; }
                    ?>
                </div>

                <form id="formCrearEnfermedad" action="crear_enfermedad.php<?php echo $redirect_to_view && isset($_SESSION['redirect_after_create_enf_generic']) ? '?origen=ver_enfermedades' : ''; ?>" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="nom_enfer" class="form-label">Nombre de la Enfermedad (*):</label>
                        <input type="text" id="nom_enfer" name="nom_enfer" class="form-control" value="<?php echo htmlspecialchars($nom_enfer_form); ?>" required maxlength="150" <?php echo $formulario_deshabilitado ? 'disabled' : ''; ?>>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="id_tipo_enfer" class="form-label">Tipo de Enfermedad (*):</label>
                        <select id="id_tipo_enfer" name="id_tipo_enfer" class="form-select" required <?php echo $formulario_deshabilitado ? 'disabled' : ''; ?>>
                            <option value="" <?php echo empty($id_tipo_enfer_sel_form) ? 'selected' : ''; ?>>Seleccione un tipo...</option>
                            <?php foreach ($tipos_enfermedad_disponibles as $tipo) : ?>
                                <option value="<?php echo htmlspecialchars($tipo['id_tipo_enfer']); ?>" <?php echo ($id_tipo_enfer_sel_form == $tipo['id_tipo_enfer']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tipo['tipo_enfermer']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="text-center">
                        <button type="submit" name="crear_enfermedad" id="btnCrearEnfermedadSubmit" class="btn btn-primary w-100" <?php echo $formulario_deshabilitado ? 'disabled' : 'disabled'; ?>> 
                            Insertar Enfermedad <i class="bi bi-plus-circle"></i> 
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include '../../../include/footer.php'; ?>
    <script src="../../js/crear_enfermedad.js"></script> 
    <?php if ($show_js_redirect): ?>
    <script>
        setTimeout(function() {
            window.location.href = 'ver_enfermedades.php?mensaje_exito=Enfermedad creada exitosamente.';
        }, 3000); 
    </script>
    <?php endif; ?>
</body>
</html>