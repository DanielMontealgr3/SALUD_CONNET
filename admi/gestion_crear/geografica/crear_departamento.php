<?php
require_once '../../../include/validar_sesion.php';
require_once '../../../include/inactividad.php';
require_once '../../../include/conexion.php';

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 || !isset($_SESSION['nombre_usuario'])) {
    header('Location: ../inicio_sesion.php');
    exit;
}

$id_dep_form = '';
$nom_dep_form = '';
$php_error_message = '';
$php_success_message = '';

if (isset($_SESSION['form_data_crear_dep'])) {
    $form_data = $_SESSION['form_data_crear_dep'];
    $id_dep_form = trim($form_data['id_dep'] ?? $id_dep_form);
    $nom_dep_form = trim($form_data['nom_dep'] ?? $nom_dep_form);
    unset($_SESSION['form_data_crear_dep']);
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['insertar_departamento'])) { 
    $id_dep_post = trim($_POST['id_dep'] ?? '');
    $nom_dep_post = trim($_POST['nom_dep'] ?? '');

    $id_dep_form = $id_dep_post;
    $nom_dep_form = $nom_dep_post;

    $errores_validacion = [];

    if (empty($id_dep_post)) {
        $errores_validacion[] = "El ID del departamento es obligatorio.";
    } elseif (!ctype_digit($id_dep_post)) {
        $errores_validacion[] = "El ID del departamento solo debe contener números.";
    } elseif (strlen($id_dep_post) > 10) {
        $errores_validacion[] = "El ID del departamento no puede exceder los 10 dígitos.";
    }


    if (empty($nom_dep_post)) {
        $errores_validacion[] = "El nombre del departamento es obligatorio.";
    } elseif (!preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/u', $nom_dep_post)) {
        $errores_validacion[] = "El nombre del departamento solo debe contener letras y espacios.";
    } elseif (strlen($nom_dep_post) < 3 || strlen($nom_dep_post) > 80) {
        $errores_validacion[] = "El nombre del departamento debe tener entre 3 y 80 caracteres.";
    }

    if (!$con) {
        $conex_db_temp = new database(); $con = $conex_db_temp->conectar();
    }

    if ($con && empty($errores_validacion)) {
        $sql_check_existencia_id = "SELECT id_dep FROM departamento WHERE id_dep = :id_dep";
        $stmt_check_id = $con->prepare($sql_check_existencia_id);
        $stmt_check_id->execute([':id_dep' => $id_dep_post]);

        $sql_check_existencia_nombre = "SELECT nom_dep FROM departamento WHERE nom_dep = :nom_dep";
        $stmt_check_nombre = $con->prepare($sql_check_existencia_nombre);
        $stmt_check_nombre->execute([':nom_dep' => $nom_dep_post]);

        if ($stmt_check_id->rowCount() > 0) {
            $php_error_message = "<div class='alert alert-danger error-servidor-especifico' data-campo-error='id_dep'>El ID de departamento '" . htmlspecialchars($id_dep_post) . "' ya existe.</div>";
            $_SESSION['form_data_crear_dep'] = $_POST;
        } elseif ($stmt_check_nombre->rowCount() > 0) {
            $php_error_message = "<div class='alert alert-danger error-servidor-especifico' data-campo-error='nom_dep'>El nombre de departamento '" . htmlspecialchars($nom_dep_post) . "' ya existe.</div>";
            $_SESSION['form_data_crear_dep'] = $_POST;
        } else {
            $sql_insert_departamento = "INSERT INTO departamento (id_dep, nom_dep) VALUES (:id_dep, :nom_dep)";
            $stmt_insert = $con->prepare($sql_insert_departamento);

            try {
                if ($stmt_insert->execute([':id_dep' => $id_dep_post, ':nom_dep' => $nom_dep_post])) {
                    $php_success_message = "<div class='alert alert-success'>Departamento '" . htmlspecialchars($nom_dep_post) . "' insertado exitosamente.</div>";
                    unset($_SESSION['form_data_crear_dep']);
                    $id_dep_form = ''; 
                    $nom_dep_form = ''; 
                } else {
                    $errorInfo = $stmt_insert->errorInfo();
                    $php_error_message = "<div class='alert alert-danger'>Error SQL al insertar departamento: " . htmlspecialchars($errorInfo[2] ?? 'Desconocido') . "</div>"; 
                    $_SESSION['form_data_crear_dep'] = $_POST;
                }
            } catch (PDOException $e) {
                $php_error_message = "<div class='alert alert-danger'>PDOException al insertar departamento: " . htmlspecialchars($e->getMessage()) . "</div>"; 
                $_SESSION['form_data_crear_dep'] = $_POST;
            }
        }
    } elseif (!empty($errores_validacion)) {
        $php_error_message = "<div class='alert alert-danger'><ul>";
        foreach ($errores_validacion as $error) {
            $php_error_message .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $php_error_message .= "</ul></div>";
        $_SESSION['form_data_crear_dep'] = $_POST;
    } else {
        $php_error_message = "<div class='alert alert-danger'>Error de conexión a la base de datos. No se pudo procesar la inserción.</div>";
        $_SESSION['form_data_crear_dep'] = $_POST;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    
    <link rel="icon" type="image/png" href="../../../img/loguito.png">
    <title>Insertar Nuevo Departamento - Salud Connected</title> 
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1">
        <div class="container-fluid mt-4">
            <div class="form-container mx-auto" style="max-width: 600px;">
                <h3 class="text-center mb-4">Insertar Nuevo Departamento</h3>
                
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

                <form id="formCrearDepartamento" action="crear_departamento.php" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="id_dep" class="form-label">ID Departamento (*):</label>
                        <input type="text" id="id_dep" name="id_dep" class="form-control" value="<?php echo htmlspecialchars($id_dep_form); ?>" required maxlength="10">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="nom_dep" class="form-label">Nombre Departamento (*):</label>
                        <input type="text" id="nom_dep" name="nom_dep" class="form-control" value="<?php echo htmlspecialchars($nom_dep_form); ?>" required maxlength="80">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="text-center">
                        <button type="submit" name="insertar_departamento" id="btnCrearDepartamentoSubmit" class="btn btn-primary w-100" disabled> <!-- Cambiado name del botón -->
                            Insertar Departamento <i class="bi bi-plus-circle"></i> <!-- Cambiado texto del botón -->
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include '../../../include/footer.php'; ?>
    <script src="../../js/crear_departamento.js"></script> 
</body>
</html>