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
    if (!empty($origen_valido)) {
        $_SESSION['redirect_after_create'] = $origen_valido . '.php';
        $redirect_to_view = true;
    }
}

$nom_barrio_form = ''; // Cambiado de nom_bar_form
$id_dep_sel_form = '';
$id_mun_sel_form = '';
$php_error_message = '';
$php_success_message = '';
$departamentos_disponibles = [];
$municipios_iniciales = []; 
$formulario_deshabilitado = false;
$show_js_redirect = false;

if ($con) {
    try {
        $stmt_dep = $con->query("SELECT id_dep, nom_dep FROM departamento ORDER BY nom_dep ASC");
        $departamentos_disponibles = $stmt_dep->fetchAll(PDO::FETCH_ASSOC);

        if (empty($departamentos_disponibles)) {
            $php_error_message = "<div class='alert alert-warning'>No hay departamentos creados. Por favor, <a href='crear_departamento.php' class='alert-link'>cree un departamento</a> primero.</div>";
            $formulario_deshabilitado = true;
        }
    } catch (PDOException $e) {
        $php_error_message = "<div class='alert alert-danger'>Error al cargar departamentos: " . htmlspecialchars($e->getMessage()) . "</div>";
        $formulario_deshabilitado = true;
    }
} else {
    $php_error_message = "<div class='alert alert-danger'>Error de conexión a la base de datos. No se pueden cargar los departamentos.</div>";
    $formulario_deshabilitado = true;
}

if (isset($_SESSION['form_data_crear_barrio'])) {
    $form_data = $_SESSION['form_data_crear_barrio'];
    $id_dep_sel_form = trim($form_data['id_dep'] ?? $id_dep_sel_form);
    $id_mun_sel_form = trim($form_data['id_mun'] ?? $id_mun_sel_form);
    $nom_barrio_form = trim($form_data['nom_barrio'] ?? $nom_barrio_form); // Cambiado de nom_bar
    unset($_SESSION['form_data_crear_barrio']);

    if ($id_dep_sel_form !== '' && $con && !$formulario_deshabilitado) {
        try {
            $sql_mun = "SELECT id_mun, nom_mun FROM municipio WHERE id_dep = :id_dep ORDER BY nom_mun ASC";
            $stmt_mun = $con->prepare($sql_mun);
            $stmt_mun->bindParam(':id_dep', $id_dep_sel_form, PDO::PARAM_STR);
            $stmt_mun->execute();
            $municipios_iniciales = $stmt_mun->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { }
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_barrio']) && !$formulario_deshabilitado) {
    $id_dep_post = trim($_POST['id_dep'] ?? '');
    $id_mun_post = trim($_POST['id_mun'] ?? '');
    $nom_barrio_post = trim($_POST['nom_barrio'] ?? ''); // Cambiado de nom_bar

    $id_dep_sel_form = $id_dep_post;
    $id_mun_sel_form = $id_mun_post;
    $nom_barrio_form = $nom_barrio_post; // Cambiado de nom_bar

    $errores_validacion = [];

    if (empty($id_dep_post)) { $errores_validacion[] = "Debe seleccionar un departamento."; }
    if (empty($id_mun_post)) { $errores_validacion[] = "Debe seleccionar un municipio."; }
    
    if (empty($nom_barrio_post)) { $errores_validacion[] = "El nombre del barrio es obligatorio."; } 
    elseif (!preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\s.,#-]+$/u', $nom_barrio_post)) { $errores_validacion[] = "El nombre del barrio contiene caracteres no permitidos."; } 
    elseif (strlen($nom_barrio_post) < 3 || strlen($nom_barrio_post) > 150) { $errores_validacion[] = "El nombre del barrio debe tener entre 3 y 150 caracteres."; }

    if ($con && empty($errores_validacion)) {
        $stmt_check_mun = $con->prepare("SELECT id_mun FROM municipio WHERE id_mun = :id_mun AND id_dep = :id_dep");
        $stmt_check_mun->execute([':id_mun' => $id_mun_post, ':id_dep' => $id_dep_post]);
        if ($stmt_check_mun->rowCount() == 0) {
            $php_error_message = "<div class='alert alert-danger'>El municipio seleccionado no es válido para el departamento indicado.</div>";
            $_SESSION['form_data_crear_barrio'] = $_POST;
        } else {
            $sql_check_existencia_nombre = "SELECT id_barrio FROM barrio WHERE nom_barrio = :nom_barrio AND id_mun = :id_mun"; // Cambiado nom_bar a nom_barrio
            $stmt_check_nombre = $con->prepare($sql_check_existencia_nombre);
            $stmt_check_nombre->execute([':nom_barrio' => $nom_barrio_post, ':id_mun' => $id_mun_post]);

            if ($stmt_check_nombre->rowCount() > 0) {
                $php_error_message = "<div class='alert alert-danger error-servidor-especifico' data-campo-error='nom_barrio'>El barrio '" . htmlspecialchars($nom_barrio_post) . "' ya existe en el municipio seleccionado.</div>"; // Cambiado data-campo-error y nom_bar
                $_SESSION['form_data_crear_barrio'] = $_POST;
            } else {
                $sql_insert_barrio = "INSERT INTO barrio (nom_barrio, id_mun) VALUES (:nom_barrio, :id_mun)"; // Cambiado nom_bar a nom_barrio
                $stmt_insert = $con->prepare($sql_insert_barrio);

                try {
                    if ($stmt_insert->execute([':nom_barrio' => $nom_barrio_post, ':id_mun' => $id_mun_post])) {
                        
                        unset($_SESSION['form_data_crear_barrio']);
                        $nom_barrio_form = ''; $id_dep_sel_form = ''; $id_mun_sel_form = ''; $municipios_iniciales = [];
                        
                        if ($redirect_to_view && isset($_SESSION['redirect_after_create'])) {
                            $redirect_url = $_SESSION['redirect_after_create'];
                            unset($_SESSION['redirect_after_create']);
                            header('Location: ' . $redirect_url . '?mensaje_exito=Barrio \'' . htmlspecialchars($nom_barrio_post) . '\' creado exitosamente.');
                            exit;
                        } else {
                             $php_success_message = "<div class='alert alert-success'>Barrio '" . htmlspecialchars($nom_barrio_post) . "' creado exitosamente.</div>";
                             $show_js_redirect = true;
                        }
                    } else {
                        $errorInfo = $stmt_insert->errorInfo();
                        $php_error_message = "<div class='alert alert-danger'>Error SQL al crear barrio: " . htmlspecialchars($errorInfo[2] ?? 'Desconocido') . "</div>";
                        $_SESSION['form_data_crear_barrio'] = $_POST;
                    }
                } catch (PDOException $e) {
                    $php_error_message = "<div class='alert alert-danger'>PDOException al crear barrio: " . htmlspecialchars($e->getMessage()) . "</div>";
                    $_SESSION['form_data_crear_barrio'] = $_POST;
                }
            }
        }
    } elseif (!empty($errores_validacion)) {
        $php_error_message = "<div class='alert alert-danger'><ul>";
        foreach ($errores_validacion as $error) { $php_error_message .= "<li>" . htmlspecialchars($error) . "</li>"; }
        $php_error_message .= "</ul></div>";
        $_SESSION['form_data_crear_barrio'] = $_POST;
    } elseif ($formulario_deshabilitado) {
         $php_error_message = "<div class='alert alert-warning'>El formulario está deshabilitado porque no hay departamentos creados.</div>";
    } elseif (!$con) {
        $php_error_message = "<div class='alert alert-danger'>Error de conexión a la base de datos. No se pudo procesar la creación.</div>";
        $_SESSION['form_data_crear_barrio'] = $_POST;
    }
    if (!empty($php_error_message) && $id_dep_sel_form !== '' && $con && !$formulario_deshabilitado) {
        try {
            $sql_mun = "SELECT id_mun, nom_mun FROM municipio WHERE id_dep = :id_dep ORDER BY nom_mun ASC";
            $stmt_mun_err = $con->prepare($sql_mun);
            $stmt_mun_err->bindParam(':id_dep', $id_dep_sel_form, PDO::PARAM_STR);
            $stmt_mun_err->execute();
            $municipios_iniciales = $stmt_mun_err->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../../img/loguito.png">
    <title>Insertar Nuevo Barrio - Salud Connected</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../../css/estilos_form.css"> 
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1">
        <div class="container-fluid mt-4">
            <div class="form-container mx-auto" style="max-width: 600px;">
                <h3 class="text-center mb-4">Insertar Nuevo Barrio</h3>
                
                <div id="mensajesServidorGlobal">
                    <?php
                        if (!empty($php_error_message)) { echo $php_error_message; }
                        if (!empty($php_success_message)) { echo $php_success_message; }
                    ?>
                </div>

                <form id="formCrearBarrio" action="crear_barrios.php<?php echo $redirect_to_view ? '?origen=' . pathinfo($_SESSION['redirect_after_create'], PATHINFO_FILENAME) : ''; ?>" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="id_dep" class="form-label">Departamento (*):</label>
                        <select id="id_dep" name="id_dep" class="form-select" required <?php echo $formulario_deshabilitado ? 'disabled' : ''; ?>>
                            <option value="" <?php echo (empty($id_dep_sel_form) && !$formulario_deshabilitado) ? 'selected' : ''; ?>>Seleccione un departamento...</option>
                            <?php foreach ($departamentos_disponibles as $dep) : ?>
                                <option value="<?php echo htmlspecialchars($dep['id_dep']); ?>" <?php echo ($id_dep_sel_form == $dep['id_dep']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dep['nom_dep']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="id_mun" class="form-label">Municipio (*):</label>
                        <select id="id_mun" name="id_mun" class="form-select" required 
                                <?php echo ($formulario_deshabilitado || empty($id_dep_sel_form) || empty($municipios_iniciales)) ? 'disabled' : ''; ?>>
                            <option value="">Seleccione un municipio...</option>
                             <?php foreach ($municipios_iniciales as $mun) : ?>
                                <option value="<?php echo htmlspecialchars($mun['id_mun']); ?>" <?php echo ($id_mun_sel_form == $mun['id_mun']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mun['nom_mun']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nom_barrio" class="form-label">Nombre Barrio (*):</label> 
                        <input type="text" id="nom_barrio" name="nom_barrio" class="form-control" value="<?php echo htmlspecialchars($nom_barrio_form); ?>" required maxlength="150" <?php echo $formulario_deshabilitado ? 'disabled' : ''; ?>>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="text-center">
                        <button type="submit" name="crear_barrio" id="btnCrearBarrioSubmit" class="btn btn-primary w-100" <?php echo $formulario_deshabilitado ? 'disabled' : 'disabled'; ?>>
                            Insertar Barrio <i class="bi bi-plus-circle"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include '../../../include/footer.php'; ?>
    <script src="../../js/crear_barrios.js"></script> 
    <?php if ($show_js_redirect): ?>
    <script>
        setTimeout(function() {
            window.location.href = 'ver_barrios.php?mensaje_exito=Barrio creado exitosamente.';
        }, 3000); 
    </script>
    <?php endif; ?>
</body>
</html>