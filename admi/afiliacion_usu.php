<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php');
$conex_db = new database();
$con = $conex_db->conectar();

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1)) {
    header('Location: ../inicio_sesion.php');
    exit;
}

$doc_afiliado_get = '';
$id_tipo_doc_get_val = '';
$eps_list = [];
$regimen_list = [];
$arl_list = [];
$estado_list_afiliado = [];

// Cambiado id_eps_sel a nit_eps_sel
$nit_eps_sel = '';
$id_regimen_sel = '';
$id_arl_sel = '';
$id_estado_sel = 1;
$fecha_afi_mostrar = date('Y-m-d');
$php_error_message = '';
$php_success_message = '';

if (isset($_GET['doc_usu'])) {
    $doc_afiliado_get = trim($_GET['doc_usu']);
} else {
    $php_error_message = "No se ha proporcionado un documento válido para la afiliación.";
}
if (isset($_GET['id_tipo_doc'])) {
    $id_tipo_doc_get_val = trim($_GET['id_tipo_doc']);
}

if ($con && empty($php_error_message)) {
    try {
        // Asumiendo que quieres mostrar nombre_eps pero el valor del select será nit_eps
        // Y que nit_eps es único y el identificador que quieres guardar en 'afiliados'
        $stmt = $con->query("SELECT nit_eps, nombre_eps FROM eps ORDER BY nombre_eps ASC");
        $eps_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $con->query("SELECT id_regimen, nom_reg FROM regimen ORDER BY nom_reg ASC");
        $regimen_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $con->query("SELECT id_arl, nom_arl FROM arl ORDER BY nom_arl ASC");
        $arl_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $con->query("SELECT id_est, nom_est FROM estado WHERE id_est IN (1, 2) ORDER BY nom_est ASC");
        $estado_list_afiliado = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $php_error_message = "Error al cargar datos para el formulario: " . $e->getMessage();
    }
} elseif (!$con && empty($php_error_message)) {
    $php_error_message = "Error de conexión a la base de datos.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_afiliacion'])) {
    $doc_afiliado_post = trim($_POST['doc_afiliado'] ?? '');
    // Cambiado 'id_eps' a 'nit_eps' para el input
    $nit_eps_sel_post = trim($_POST['nit_eps'] ?? ''); // nit_eps puede ser string
    $id_regimen_sel = filter_input(INPUT_POST, 'id_regimen', FILTER_VALIDATE_INT);
    $id_arl_sel = filter_input(INPUT_POST, 'id_arl', FILTER_VALIDATE_INT);
    $id_estado_sel_post = filter_input(INPUT_POST, 'id_estado', FILTER_VALIDATE_INT);
    $fecha_actual_db = date('Y-m-d H:i:s');

    if (empty($doc_afiliado_post)) {
        $php_error_message = "El documento del afiliado es requerido.";
    } elseif (empty($id_regimen_sel)) {
        $php_error_message = "El régimen es requerido.";
    } elseif (empty($id_estado_sel_post)) {
        $php_error_message = "El estado es requerido.";
    // Cambiado empty($id_eps_sel) a empty($nit_eps_sel_post)
    } elseif (empty($nit_eps_sel_post) && empty($id_arl_sel)) {
        $php_error_message = "Debe seleccionar una EPS o una ARL.";
    } else {
        if ($con) {
            try {
                // Asumiendo que la columna en 'afiliados' para guardar el NIT de la EPS se llama 'nit_eps' o similar.
                // ¡DEBES AJUSTAR 'nit_eps_fk_column_name' AL NOMBRE REAL DE LA COLUMNA EN TU TABLA 'afiliados'!
                $columna_fk_eps_en_afiliados = 'id_eps'; // O 'nit_eps_registrado', o como se llame en tu tabla afiliados.
                                                        // Si es 'id_eps' y almacena el NIT, está bien.

                $stmt_check = $con->prepare("SELECT id_afiliacion FROM afiliados WHERE doc_afiliado = :doc_afiliado"); // Usand
                $stmt_check->bindParam(':doc_afiliado', $doc_afiliado_post, PDO::PARAM_STR);
                $stmt_check->execute();
                $existe_afiliado_id = $stmt_check->fetchColumn();

                if ($existe_afiliado_id) {
                    // AJUSTA $columna_fk_eps_en_afiliados si es necesario
                    $sql = "UPDATE afiliados SET fecha_afi = :fecha_afi, {$columna_fk_eps_en_afiliados} = :nit_eps_param, id_regimen = :id_regimen, id_arl = :id_arl, id_estado = :id_estado WHERE doc_afiliado = :doc_afiliado";
                } else {
                    // AJUSTA $columna_fk_eps_en_afiliados si es necesario
                    $sql = "INSERT INTO afiliados (doc_afiliado, fecha_afi, {$columna_fk_eps_en_afiliados}, id_regimen, id_arl, id_estado) VALUES (:doc_afiliado, :fecha_afi, :nit_eps_param, :id_regimen, :id_arl, :id_estado)";
                }
                
                $stmt_guardar = $con->prepare($sql);
                $params_guardar = [
                    ':doc_afiliado' => $doc_afiliado_post, 
                    ':fecha_afi' => $fecha_actual_db,
                    // Cambiado ':id_eps' a ':nit_eps_param' y usando $nit_eps_sel_post
                    ':nit_eps_param' => $nit_eps_sel_post ?: null, 
                    ':id_regimen' => $id_regimen_sel,
                    ':id_arl' => $id_arl_sel ?: null, 
                    ':id_estado' => $id_estado_sel_post
                ];

                if ($stmt_guardar->execute($params_guardar)) {
                    $php_success_message = "<div class='alert alert-success'>Afiliación guardada para " . htmlspecialchars($doc_afiliado_post) . ". Intente crear el usuario nuevamente.</div>";
                } else {
                    $errorInfo = $stmt_guardar->errorInfo();
                    $php_error_message = "<div class='alert alert-danger'>Error SQL: " . ($errorInfo[2] ?? 'Desconocido') . "</div>";
                }
            } catch (PDOException $e) {
                $php_error_message = "<div class='alert alert-danger'>Error DB: " . $e->getMessage() . "</div>";
            }
        } else {
            $php_error_message = "<div class='alert alert-danger'>Error de conexión.</div>";
        }
    }
    $id_estado_sel = $id_estado_sel_post ?? $id_estado_sel;
    $doc_afiliado_get = $doc_afiliado_post ?: $doc_afiliado_get;
    // Mantener el NIT de EPS seleccionado
    $nit_eps_sel = $nit_eps_sel_post ?: $nit_eps_sel;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="../img/loguito.png">
    <title>Gestión de Afiliación</title>
    <style>
        #contenedor-pagina_inicio { display: flex; flex-direction: column; min-height: 80vh; }
        #contenido-principal { flex-grow: 1; padding: 30px 20px; }
        .mensaje-bienvenida strong { color: #0b5ed7; }
        .imagen-admin { max-width: 250px; height: auto; display: inline-block; }
        .navbar-custom-blue { background-color:rgb(0, 117, 201) !important; }
        .footer-collapsible.navbar-custom-blue { padding-top: 0.7rem !important; padding-bottom: 0.7rem !important; position: relative; }
        .footer-visible-bar { display: flex; justify-content: space-between; align-items: center; cursor: pointer; padding-left: var(--bs-gutter-x, 0.75rem); padding-right: var(--bs-gutter-x, 0.75rem); }
        .footer-toggler { color: #ffffff; background: none; border: none; }
        #contenido-principal.text-center { display: flex; justify-content: center; align-items: center; text-align: center; }
        h3.titulo-lista { text-align: center; color: #333; margin-bottom: 25px; border-bottom: 2px solid #0b5ed7; padding-bottom: 10px; }
        .filtro-form { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; padding: 15px; background-color: #e9ecef; border-radius: 5px; }
        .filtro-form label { font-weight: bold; color: #555; margin-bottom: 0; }
        .filtro-form select { padding: 8px 12px; border: 1px solid #ccc; border-radius: 4px; min-width: 180px; font-size: 0.95em; }
        .filtro-form button { padding: 8px 18px; background-color: #0b5ed7; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.95em; transition: background-color 0.2s ease; }
        .filtro-form button:hover { background-color: #0a58ca; }
        .tabla-usuarios { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .tabla-usuarios th, .tabla-usuarios td { border: 1px solid #ddd; padding: 10px 12px; text-align: left; vertical-align: middle; font-size: 0.9em; }
        .tabla-usuarios thead th { background-color: #343a40; color: white; font-weight: bold; position: sticky; top: 0; }
        .tabla-usuarios tbody tr:nth-child(even) { background-color: #f8f9fa; }
        .tabla-usuarios tbody tr:hover { background-color: #e9ecef; }
        .form-control.input-error, .form-select.input-error { border-color: #dc3545 !important; }
        .form-control.input-success, .form-select.input-success { border-color: #198754 !important; }
        .error-msg { color: #dc3545; font-size: 0.875em; display: none; width: 100%; margin-top: 0.25rem; }
        .error-msg.visible { display: block; }
        select:disabled, .form-select:disabled { background-color: #e9ecef; opacity: 0.7; cursor: not-allowed; }
        .form-page-content .form-container { background-color: #f8f9fa; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1); }
        #contenido-principal.form-page-content { display: block; padding: 20px; }
        .form-container { max-width: 800px; margin: 20px auto; padding: 25px 30px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); border: 1px solid #e0e0e0; }
        .form-container h3 { text-align: center; color: #333; margin-bottom: 30px; font-weight: 600; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .form-container label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 0.9em; color: #555; }
        .form-container .form-control, .form-container .form-select { font-size: 0.95em; transition: border-color 0.2s ease, box-shadow 0.2s ease; }
        .form-container .form-control:focus, .form-container .form-select:focus { border-color: #86b7fe; outline: 0; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
        .form-container .form-text { font-size: 0.8em; color: #6c757d; margin-top: 3px; display: block; }
        .mensaje-exito, .mensaje-error { padding: 10px 15px; margin: 0 0 15px 0; border-radius: 5px; font-size: 0.9em; text-align: center; }
        .mensaje-exito { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .mensaje-error { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .contenedor-entidades { max-width: 1100px; margin: 20px auto; padding: 20px; background-color: #f9f9f9; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .tabla-entidades { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .tabla-entidades th, .tabla-entidades td { border: 1px solid #ddd; padding: 10px 12px; text-align: left; vertical-align: middle; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.9em; }
        .tabla-entidades thead th { background-color: #343a40; color: white; font-weight: bold; position: sticky; top: 0; }
        .tabla-entidades tbody tr:nth-child(even) { background-color: #f8f9fa; }
        .tabla-entidades tbody tr:hover { background-color: #e9ecef; }
        .form-control.input-error, .form-select.input-error { border-color: #dc3545 !important; box-shadow: none; }
        .form-control.input-success, .form-select.input-success { border-color: #198754 !important; background-color: transparent; box-shadow: none; }
        .error-msg { color: #dc3545; font-size: 0.8rem; display: none; width: 100%; margin-top: 0.25rem; font-weight: 500; }
        .error-msg.visible { display: block; }
        select:disabled, .form-select:disabled { background-color: #e9ecef; opacity: 0.7; cursor: not-allowed; border-color: #ced4da; }
        .error-msg { color: #dc3545; font-size: 0.875em; display: none; width: 100%; margin-top: 0.25rem; }
        .error-msg.visible { display: block; }
        .form-control.input-error { border-color: #dc3545 !important; }
        .form-select.input-error { border-color: #dc3545 !important; }
        .form-control.input-success { border-color: #198754 !important; }
        .form-select.input-success { border-color: #198754 !important; }
        .form-select:disabled.input-error, .form-select:disabled.input-success { border-color: #ced4da !important; }
        .raw-php-error { color: red; border: 2px solid red; padding: 15px; background-color: #ffeeee; font-family: monospace; white-space: pre-wrap; margin-bottom: 15px; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 mb-5 form-page-content">
        <div class="container mt-4">
            <div class="form-container mx-auto" style="max-width: 700px;">
                <h3 class="text-center mb-3">Registrar / Actualizar Afiliación</h3>
                <?php
                    if (!empty($php_error_message) && strpos($php_error_message, "<div class='alert") === false) { echo "<div class='raw-php-error'>" . htmlspecialchars($php_error_message) . "</div>"; }
                    elseif (!empty($php_error_message)) { echo $php_error_message; }
                    if (!empty($php_success_message)) { echo $php_success_message; }
                ?>
                <?php if (!empty($doc_afiliado_get) && strpos($php_success_message, "alert-success") === false): ?>
                <form id="formAfiliacionUsuario" action="afiliacion_usu.php?doc_usu=<?php echo htmlspecialchars(urlencode($doc_afiliado_get)); ?>&id_tipo_doc=<?php echo htmlspecialchars(urlencode($id_tipo_doc_get_val)); ?>" method="POST" novalidate>
                    <input type="hidden" name="doc_afiliado" value="<?php echo htmlspecialchars($doc_afiliado_get); ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="doc_afiliado_display" class="form-label">Documento Usuario:</label>
                            <input type="text" id="doc_afiliado_display" class="form-control" value="<?php echo htmlspecialchars($doc_afiliado_get); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="fecha_afi_display" class="form-label">Fecha Afiliación (Sistema):</label>
                            <input type="text" id="fecha_afi_display" class="form-control" value="<?php echo htmlspecialchars($fecha_afi_mostrar); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="nit_eps" class="form-label">EPS:</label>
                            <select id="nit_eps" name="nit_eps" class="form-select">
                                <option value="">Seleccione EPS...</option>
                                <?php foreach ($eps_list as $eps) : ?>
                                    <option value="<?php echo htmlspecialchars($eps['nit_eps']); ?>" <?php echo ($nit_eps_sel == $eps['nit_eps']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($eps['nombre_eps']); ?> (NIT: <?php echo htmlspecialchars($eps['nit_eps']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="error-msg" id="error-nit_eps"></span>
                        </div>
                        <div class="col-md-6">
                            <label for="id_regimen" class="form-label">Régimen:</label>
                            <select id="id_regimen" name="id_regimen" class="form-select">
                                <option value="">Seleccione Régimen...</option>
                                <?php foreach ($regimen_list as $reg) : ?>
                                    <option value="<?php echo htmlspecialchars($reg['id_regimen']); ?>" <?php echo ($id_regimen_sel == $reg['id_regimen']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($reg['nom_reg']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="error-msg" id="error-id_regimen"></span>
                        </div>
                        <div class="col-md-6">
                            <label for="id_arl" class="form-label">ARL:</label>
                            <select id="id_arl" name="id_arl" class="form-select">
                                <option value="">Seleccione ARL...</option>
                                <?php 
                                    foreach ($arl_list as $arl_item) {
                                        echo "<option value=\"" . htmlspecialchars($arl_item['id_arl']) . "\" " . ($id_arl_sel == $arl_item['id_arl'] ? 'selected' : '') . ">" . htmlspecialchars($arl_item['nom_arl']) . "</option>";
                                    }
                                ?>
                            </select>
                            <span class="error-msg" id="error-id_arl"></span>
                        </div>
                        <div class="col-md-6">
                            <label for="id_estado" class="form-label">Estado Afiliación:</label>
                            <select id="id_estado" name="id_estado" class="form-select">
                                <?php foreach ($estado_list_afiliado as $est) : ?>
                                    <option value="<?php echo htmlspecialchars($est['id_est']); ?>" <?php echo ($id_estado_sel == $est['id_est']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(ucfirst($est['nom_est'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <span class="error-msg" id="error-id_estado"></span>
                        </div>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" name="guardar_afiliacion" class="btn btn-primary w-100">Guardar Afiliación</button>
                    </div>
                </form>
                <?php endif; ?>
                <div class="col-12 mt-3 text-center">
                    <a href="crear_usu.php" class="btn btn-link">Volver a Creación de Usuario</a>
                </div>
            </div>
        </div>
    </main>
    <?php include '../include/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/afiliacion_usu.js"></script> 
</body>
</html>