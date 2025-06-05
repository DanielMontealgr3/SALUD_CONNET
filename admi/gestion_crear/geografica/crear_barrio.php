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

$nom_barrio_form = '';
$id_dep_sel_form = '';
$id_mun_sel_form = '';
$php_error_message = '';
$php_success_message = '';
$departamentos_disponibles = [];
$municipios_para_select_php = [];
$formulario_deshabilitado_no_dep = false;


if ($con) {
    try {
        $stmt_dep = $con->query("SELECT id_dep, nom_dep FROM departamento ORDER BY nom_dep ASC");
        $departamentos_disponibles = $stmt_dep->fetchAll(PDO::FETCH_ASSOC);

        if (empty($departamentos_disponibles)) {
            $php_error_message = "<div class='alert alert-warning'>No hay departamentos creados. Por favor, <a href='crear_departamento.php' class='alert-link'>cree un departamento</a> primero.</div>";
            $formulario_deshabilitado_no_dep = true;
        }
    } catch (PDOException $e) {
        $php_error_message = "<div class='alert alert-danger'>Error al cargar departamentos: " . htmlspecialchars($e->getMessage()) . "</div>";
        $formulario_deshabilitado_no_dep = true;
    }
} else {
    $php_error_message = "<div class='alert alert-danger'>Error de conexión a la base de datos.</div>";
    $formulario_deshabilitado_no_dep = true;
}

if (isset($_SESSION['form_data_crear_barrio'])) {
    $form_data = $_SESSION['form_data_crear_barrio'];
    $nom_barrio_form = trim($form_data['nom_barrio'] ?? $nom_barrio_form);
    $id_dep_sel_form = trim($form_data['id_dep'] ?? $id_dep_sel_form);
    $id_mun_sel_form = trim($form_data['id_mun'] ?? $id_mun_sel_form);
    unset($_SESSION['form_data_crear_barrio']);

    if (!$formulario_deshabilitado_no_dep && !empty($id_dep_sel_form) && $con) {
        try {
            $stmt_mun_pre = $con->prepare("SELECT id_mun, nom_mun FROM municipio WHERE id_dep = :id_dep ORDER BY nom_mun ASC");
            $stmt_mun_pre->bindParam(':id_dep', $id_dep_sel_form, PDO::PARAM_STR);
            $stmt_mun_pre->execute();
            $municipios_para_select_php = $stmt_mun_pre->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error precargando municipios en crear_barrio.php: " . $e->getMessage());
        }
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['insertar_barrio']) && !$formulario_deshabilitado_no_dep) {
    $nom_barrio_post = trim($_POST['nom_barrio'] ?? '');
    $id_mun_sel_post_from_form = trim($_POST['id_mun'] ?? '');
    $id_dep_sel_post_from_form = trim($_POST['id_dep'] ?? '');

    $nom_barrio_form = $nom_barrio_post;
    $id_mun_sel_form = $id_mun_sel_post_from_form;
    $id_dep_sel_form = $id_dep_sel_post_from_form;

    if (!empty($id_dep_sel_form) && $con) {
        try {
            $stmt_mun_reload = $con->prepare("SELECT id_mun, nom_mun FROM municipio WHERE id_dep = :id_dep ORDER BY nom_mun ASC");
            $stmt_mun_reload->bindParam(':id_dep', $id_dep_sel_form, PDO::PARAM_STR);
            $stmt_mun_reload->execute();
            $municipios_para_select_php = $stmt_mun_reload->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error recargando municipios en POST crear_barrio.php: " . $e->getMessage());
        }
    }

    $errores_validacion = [];

    if (empty($id_dep_sel_form)) {
        $errores_validacion[] = "Debe seleccionar un departamento.";
    }
    if (empty($id_mun_sel_form)) {
        $errores_validacion[] = "Debe seleccionar un municipio.";
    } elseif (empty($municipios_para_select_php) && !empty($id_dep_sel_form)) {
        $errores_validacion[] = "No hay municipios disponibles para el departamento seleccionado. Cree uno primero.";
    }

    if (empty($nom_barrio_post)) {
        $errores_validacion[] = "El nombre del barrio es obligatorio.";
    } elseif (!preg_match('/^[a-zA-Z0-9ñÑáéíóúÁÉÍÓÚ\s\-\#\.]+$/u', $nom_barrio_post)) {
        $errores_validacion[] = "El nombre del barrio solo puede contener letras, números, espacios y los símbolos . - #";
    } elseif (strlen($nom_barrio_post) < 3 || strlen($nom_barrio_post) > 120) {
        $errores_validacion[] = "El nombre del barrio debe tener entre 3 y 120 caracteres.";
    }

    if ($con && empty($errores_validacion)) {
        $sql_check_existencia_nombre = "SELECT nom_barrio FROM barrio WHERE nom_barrio = :nom_barrio AND id_mun = :id_mun";
        $stmt_check_nombre = $con->prepare($sql_check_existencia_nombre);
        $stmt_check_nombre->execute([':nom_barrio' => $nom_barrio_post, ':id_mun' => $id_mun_sel_form]);

        if ($stmt_check_nombre->rowCount() > 0) {
             $php_error_message = "<div class='alert alert-danger error-servidor-especifico' data-campo-error='nom_barrio'>El barrio '" . htmlspecialchars($nom_barrio_post) . "' ya existe en el municipio seleccionado.</div>";
            $_SESSION['form_data_crear_barrio'] = $_POST;
        } else {
            $sql_insert_barrio = "INSERT INTO barrio (nom_barrio, id_mun) VALUES (:nom_barrio, :id_mun)";
            $stmt_insert = $con->prepare($sql_insert_barrio);

            try {
                if ($stmt_insert->execute([':nom_barrio' => $nom_barrio_post, ':id_mun' => $id_mun_sel_form])) {
                    $php_success_message = "<div class='alert alert-success'>Barrio '" . htmlspecialchars($nom_barrio_post) . "' insertado exitosamente.</div>";
                    unset($_SESSION['form_data_crear_barrio']);
                    $nom_barrio_form = '';
                    $id_dep_sel_form = '';
                    $id_mun_sel_form = '';
                    $municipios_para_select_php = [];
                } else {
                    $errorInfo = $stmt_insert->errorInfo();
                    $php_error_message = "<div class='alert alert-danger'>Error SQL al insertar barrio: " . htmlspecialchars($errorInfo[2] ?? 'Desconocido') . "</div>";
                    $_SESSION['form_data_crear_barrio'] = $_POST;
                }
            } catch (PDOException $e) {
                $php_error_message = "<div class='alert alert-danger'>PDOException al insertar barrio: " . htmlspecialchars($e->getMessage()) . "</div>";
                $_SESSION['form_data_crear_barrio'] = $_POST;
            }
        }
    } elseif (!empty($errores_validacion)) {
        $php_error_message = "<div class='alert alert-danger'><ul>";
        foreach ($errores_validacion as $error) {
            $php_error_message .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $php_error_message .= "</ul></div>";
        $_SESSION['form_data_crear_barrio'] = $_POST;
    } elseif ($formulario_deshabilitado_no_dep) {
         $php_error_message = "<div class='alert alert-warning'>El formulario está deshabilitado porque no hay departamentos creados.</div>";
    } elseif (!$con) {
        $php_error_message = "<div class='alert alert-danger'>Error de conexión a la base de datos. No se pudo procesar la inserción.</div>";
        $_SESSION['form_data_crear_barrio'] = $_POST;
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
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1">
        <div class="container-fluid mt-4">
            <div class="form-container mx-auto" style="max-width: 600px;">
                <h3 class="text-center mb-4">Insertar Nuevo Barrio</h3>

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

                <form id="formCrearBarrio" action="crear_barrio.php" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="id_dep" class="form-label">Departamento (*):</label>
                        <select id="id_dep" name="id_dep" class="form-select" required <?php echo $formulario_deshabilitado_no_dep ? 'disabled' : ''; ?>>
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
                        <label for="id_mun" class="form-label">Municipio (*):</label>
                        <select id="id_mun" name="id_mun" class="form-select" required
                                <?php echo ($formulario_deshabilitado_no_dep || (empty($id_dep_sel_form) && empty($municipios_para_select_php)) ) ? 'disabled' : ''; ?>
                                title="<?php echo ($formulario_deshabilitado_no_dep) ? 'Cree departamentos primero.' : ( (empty($id_dep_sel_form) && empty($municipios_para_select_php)) ? 'Seleccione un departamento primero.' : ''); ?>">

                            <?php if (!empty($id_dep_sel_form) && !empty($municipios_para_select_php)): ?>
                                <option value="" <?php echo empty($id_mun_sel_form) ? 'selected' : ''; ?>>Seleccione un municipio...</option>
                                <?php foreach ($municipios_para_select_php as $mun) : ?>
                                    <option value="<?php echo htmlspecialchars($mun['id_mun']); ?>" <?php echo ($id_mun_sel_form == $mun['id_mun']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($mun['nom_mun']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php elseif (!empty($id_dep_sel_form) && empty($municipios_para_select_php) && !$formulario_deshabilitado_no_dep): ?>
                                <option value="">No hay municipios para este departamento</option>
                            <?php else: ?>
                                 <option value="">Seleccione departamento...</option>
                            <?php endif; ?>
                        </select>
                        <div class="invalid-feedback"></div>
                        <div id="mensajeNoMunicipios" class="form-text text-danger mt-1" style="display: <?php echo (!empty($id_dep_sel_form) && empty($municipios_para_select_php) && !$formulario_deshabilitado_no_dep && empty($php_success_message) ) ? 'block' : 'none'; ?>;">
                            No hay municipios disponibles para el departamento seleccionado. Por favor, <a href='crear_municipio.php' class='alert-link'>cree un municipio</a>.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nom_barrio" class="form-label">Nombre Barrio (*):</label>
                        <input type="text" id="nom_barrio" name="nom_barrio" class="form-control" value="<?php echo htmlspecialchars($nom_barrio_form); ?>" required maxlength="120" <?php echo ($formulario_deshabilitado_no_dep || empty($id_mun_sel_form) && empty($municipios_para_select_php) ) ? 'disabled' : ''; ?>>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="text-center">
                        <button type="submit" name="insertar_barrio" id="btnCrearBarrioSubmit" class="btn btn-primary w-100" <?php echo ($formulario_deshabilitado_no_dep) ? 'disabled' : ''; ?>>
                            Insertar Barrio <i class="bi bi-plus-circle"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include '../../../include/footer.php'; ?>

    <script>
        const phpInitialData = {
            idDepSelForm: <?php echo json_encode($id_dep_sel_form); ?>,
            idMunSelForm: <?php echo json_encode($id_mun_sel_form); ?>,
            formularioDeshabilitadoNoDep: <?php echo json_encode($formulario_deshabilitado_no_dep); ?>,
            municipiosPreload: <?php echo json_encode($municipios_para_select_php); ?>
        };
    </script>
    <script src="../../js/crear_barrio.js"></script>
</body>
</html>