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
    header('Location: ../inicio_sesion.php');
    exit;
}

$id_mun_form = '';
$nom_mun_form = '';
$id_dep_sel_form = '';
$php_error_message = '';
$php_success_message = '';
$departamentos_disponibles = [];
$formulario_deshabilitado = false;

if ($con) {
    try {
        $stmt_dep = $con->query("SELECT id_dep, nom_dep FROM departamento ORDER BY nom_dep ASC");
        $departamentos_disponibles = $stmt_dep->fetchAll(PDO::FETCH_ASSOC);

        if (empty($departamentos_disponibles)) {
            $php_error_message = "<div class='alert alert-warning'>No hay departamentos creados. Por favor, <a href='crear_departamento.php' class='alert-link'>cree un departamento</a> primero para poder agregar municipios.</div>";
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


if (isset($_SESSION['form_data_crear_mun'])) {
    $form_data = $_SESSION['form_data_crear_mun'];
    $id_mun_form = trim($form_data['id_mun'] ?? $id_mun_form);
    $nom_mun_form = trim($form_data['nom_mun'] ?? $nom_mun_form);
    $id_dep_sel_form = trim($form_data['id_dep'] ?? $id_dep_sel_form);
    unset($_SESSION['form_data_crear_mun']);
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_municipio']) && !$formulario_deshabilitado) {
    $id_mun_post = trim($_POST['id_mun'] ?? '');
    $nom_mun_post = trim($_POST['nom_mun'] ?? '');
    $id_dep_sel_post = trim($_POST['id_dep'] ?? '');

    $id_mun_form = $id_mun_post;
    $nom_mun_form = $nom_mun_post;
    $id_dep_sel_form = $id_dep_sel_post;

    $errores_validacion = [];

    if (empty($id_dep_sel_post)) {
        $errores_validacion[] = "Debe seleccionar un departamento.";
    }
    if (empty($id_mun_post)) {
        $errores_validacion[] = "El ID del municipio es obligatorio.";
    } elseif (!ctype_digit($id_mun_post)) {
        $errores_validacion[] = "El ID del municipio solo debe contener números.";
    } elseif (strlen($id_mun_post) > 10) { // Asumiendo una longitud máxima similar a id_dep
        $errores_validacion[] = "El ID del municipio no puede exceder los 10 dígitos.";
    }

    if (empty($nom_mun_post)) {
        $errores_validacion[] = "El nombre del municipio es obligatorio.";
    } elseif (!preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/u', $nom_mun_post)) {
        $errores_validacion[] = "El nombre del municipio solo debe contener letras y espacios.";
    } elseif (strlen($nom_mun_post) < 3 || strlen($nom_mun_post) > 100) {
        $errores_validacion[] = "El nombre del municipio debe tener entre 3 y 100 caracteres.";
    }

    if ($con && empty($errores_validacion)) {
        $sql_check_existencia_id = "SELECT id_mun FROM municipio WHERE id_mun = :id_mun";
        $stmt_check_id = $con->prepare($sql_check_existencia_id);
        $stmt_check_id->execute([':id_mun' => $id_mun_post]);

        $sql_check_existencia_nombre = "SELECT nom_mun FROM municipio WHERE nom_mun = :nom_mun AND id_dep = :id_dep";
        $stmt_check_nombre = $con->prepare($sql_check_existencia_nombre);
        $stmt_check_nombre->execute([':nom_mun' => $nom_mun_post, ':id_dep' => $id_dep_sel_post]);


        if ($stmt_check_id->rowCount() > 0) {
            $php_error_message = "<div class='alert alert-danger error-servidor-especifico' data-campo-error='id_mun'>El ID de municipio '" . htmlspecialchars($id_mun_post) . "' ya existe.</div>";
            $_SESSION['form_data_crear_mun'] = $_POST;
        } elseif ($stmt_check_nombre->rowCount() > 0) {
             $php_error_message = "<div class='alert alert-danger error-servidor-especifico' data-campo-error='nom_mun'>El municipio '" . htmlspecialchars($nom_mun_post) . "' ya existe en el departamento seleccionado.</div>";
            $_SESSION['form_data_crear_mun'] = $_POST;
        } else {
            $sql_insert_municipio = "INSERT INTO municipio (id_mun, nom_mun, id_dep) VALUES (:id_mun, :nom_mun, :id_dep)";
            $stmt_insert = $con->prepare($sql_insert_municipio);

            try {
                if ($stmt_insert->execute([':id_mun' => $id_mun_post, ':nom_mun' => $nom_mun_post, ':id_dep' => $id_dep_sel_post])) {
                    $php_success_message = "<div class='alert alert-success'>Municipio '" . htmlspecialchars($nom_mun_post) . "' creado exitosamente.</div>";
                    unset($_SESSION['form_data_crear_mun']);
                    $id_mun_form = ''; 
                    $nom_mun_form = ''; 
                    $id_dep_sel_form = '';
                } else {
                    $errorInfo = $stmt_insert->errorInfo();
                    $php_error_message = "<div class='alert alert-danger'>Error SQL al crear municipio: " . htmlspecialchars($errorInfo[2] ?? 'Desconocido') . "</div>";
                    $_SESSION['form_data_crear_mun'] = $_POST;
                }
            } catch (PDOException $e) {
                $php_error_message = "<div class='alert alert-danger'>PDOException al crear municipio: " . htmlspecialchars($e->getMessage()) . "</div>";
                $_SESSION['form_data_crear_mun'] = $_POST;
            }
        }
    } elseif (!empty($errores_validacion)) {
        $php_error_message = "<div class='alert alert-danger'><ul>";
        foreach ($errores_validacion as $error) {
            $php_error_message .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $php_error_message .= "</ul></div>";
        $_SESSION['form_data_crear_mun'] = $_POST;
    } elseif ($formulario_deshabilitado) {
         $php_error_message = "<div class='alert alert-warning'>El formulario está deshabilitado porque no hay departamentos creados.</div>";
    } elseif (!$con) {
        $php_error_message = "<div class='alert alert-danger'>Error de conexión a la base de datos. No se pudo procesar la creación.</div>";
        $_SESSION['form_data_crear_mun'] = $_POST;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    
    <link rel="icon" type="image/png" href="../../../img/loguito.png">
    <title>Insertar Nuevo Municipio - Salud Connected</title>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1">
        <div class="container-fluid mt-4">
            <div class="form-container mx-auto" style="max-width: 600px;">
                <h3 class="text-center mb-4">Insertar Nuevo Municipio</h3>
                
                <div id="mensajesServidorGlobal">
                    <?php
                        if (!empty($php_error_message)) {
                            echo $php_error_message;
                        }
                        if (!empty($php_success_message)) {
                             echo $php_success_message;
                        }
                    ?>
                </div>

                <form id="formCrearMunicipio" action="crear_municipio.php" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="id_dep" class="form-label">Departamento (*):</label>
                        <select id="id_dep" name="id_dep" class="form-select" required <?php echo $formulario_deshabilitado ? 'disabled' : ''; ?>>
                            <option value="" <?php echo empty($id_dep_sel_form) ? 'selected' : ''; ?>>Seleccione un departamento...</option>
                            <?php foreach ($departamentos_disponibles as $dep) : ?>
                                <option value="<?php echo htmlspecialchars($dep['id_dep']); ?>" <?php echo ($id_dep_sel_form == $dep['id_dep']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dep['nom_dep']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="id_mun" class="form-label">ID Municipio (*):</label>
                        <input type="text" id="id_mun" name="id_mun" class="form-control" value="<?php echo htmlspecialchars($id_mun_form); ?>" required maxlength="10" <?php echo $formulario_deshabilitado ? 'disabled' : ''; ?>>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="nom_mun" class="form-label">Nombre Municipio (*):</label>
                        <input type="text" id="nom_mun" name="nom_mun" class="form-control" value="<?php echo htmlspecialchars($nom_mun_form); ?>" required maxlength="100" <?php echo $formulario_deshabilitado ? 'disabled' : ''; ?>>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="text-center">
                        <button type="submit" name="crear_municipio" id="btnCrearMunicipioSubmit" class="btn btn-primary w-100" <?php echo $formulario_deshabilitado ? 'disabled' : 'disabled'; ?>>
                            Insertar Municipio <i class="bi bi-plus-circle"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include '../../../include/footer.php'; ?>
    <script src="../../js/crear_municipio.js"></script> 
</body>
</html>