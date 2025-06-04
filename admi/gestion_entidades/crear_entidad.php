<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) {
    header('Location: ../../inicio_sesion.php');
    exit;
}

$conex_db = new database();
$con = $conex_db->conectar();

$departamentos = [];
$php_error_message = '';
$php_success_message = '';

$tipo_entidad_sel = $_POST['tipo_entidad_selector'] ?? '';
$nit_farm_val = $_POST['nit_farm'] ?? '';
$nom_farm_val = $_POST['nom_farm'] ?? '';
$direc_farm_val = $_POST['direc_farm'] ?? '';
$nom_gerente_farm_val = $_POST['nom_gerente_farm'] ?? '';
$tel_farm_val = $_POST['tel_farm'] ?? '';
$correo_farm_val = $_POST['correo_farm'] ?? '';
$nit_eps_val = $_POST['nit_eps'] ?? '';
$nombre_eps_val = $_POST['nombre_eps'] ?? '';
$direc_eps_val = $_POST['direc_eps'] ?? '';
$nom_gerente_eps_val = $_POST['nom_gerente_eps'] ?? '';
$telefono_eps_val = $_POST['telefono_eps'] ?? '';
$correo_eps_val = $_POST['correo_eps'] ?? '';
$nit_ips_val = $_POST['nit_ips'] ?? '';
$nom_ips_val = $_POST['nom_ips'] ?? '';
$id_dep_ips_val = $_POST['id_dep_ips'] ?? '';
$ubicacion_mun_ips_val = $_POST['ubicacion_mun_ips'] ?? '';
$direc_ips_val = $_POST['direc_ips'] ?? '';
$nom_gerente_ips_val = $_POST['nom_gerente_ips'] ?? '';
$tel_ips_val = $_POST['tel_ips'] ?? '';
$correo_ips_val = $_POST['correo_ips'] ?? '';

if ($con) {
    try {
        $stmt_dep = $con->query("SELECT id_dep, nom_dep FROM departamento ORDER BY nom_dep ASC");
        $departamentos = $stmt_dep->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $php_error_message = "<div class='alert alert-danger'>Error al cargar departamentos: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    $php_error_message = "<div class='alert alert-danger'>Error de conexión inicial a la base de datos. No se pueden cargar datos iniciales.</div>";
}

function validar_nit_php($nit) {
    if (empty($nit)) return "El NIT es obligatorio.";
    if (!ctype_digit($nit)) return "El NIT debe contener solo números.";
    if (strlen($nit) < 7) return "El NIT debe tener al menos 7 dígitos.";
    return true;
}
function validar_nombre_php($nombre, $campo = "Nombre") {
    if (empty($nombre)) return "El $campo es obligatorio.";
    if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s\.]+$/u', $nombre)) return "El $campo solo debe contener letras, puntos y espacios.";
    return true;
}
function validar_direccion_php($direccion, $campo = "Dirección"){
    if (empty($direccion)) return "La $campo es obligatoria.";
    return true;
}
function validar_telefono_php($telefono) {
    if (empty($telefono)) return null;
    if (!ctype_digit($telefono)) return "El teléfono debe contener solo números.";
    if (strlen($telefono) < 7 || strlen($telefono) > 10) return "El teléfono debe tener entre 7 y 10 dígitos.";
    return true;
}
function validar_correo_php($correo) {
    if (empty($correo)) return null;
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) return "El formato del correo no es válido.";
    return true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guardar_entidad'])) {
    if (!$con) {
        $conex_db_temp = new database(); 
        $con = $conex_db_temp->conectar();
        if (!$con) {
            $php_error_message = "<div class='alert alert-danger'>Error crítico de conexión: No se pudo conectar a la base de datos para guardar. Por favor, intente más tarde.</div>";
        }
    }

    if ($con) {
        $tipo_entidad = $_POST['tipo_entidad_selector'] ?? '';
        $errores_validacion = [];
        $nit_a_validar = '';
        $tabla_a_verificar = '';
        $campo_nit_tabla = '';
        $nombre_entidad_tipo_msg = '';

        try {
            if ($tipo_entidad === 'farmacia') {
                $nit_farm = trim($_POST['nit_farm'] ?? ''); $nom_farm = trim($_POST['nom_farm'] ?? ''); $direc_farm = trim($_POST['direc_farm'] ?? ''); $nom_gerente_farm = trim($_POST['nom_gerente_farm'] ?? ''); $tel_farm = trim($_POST['tel_farm'] ?? ''); $correo_farm = filter_var(trim($_POST['correo_farm'] ?? ''), FILTER_SANITIZE_EMAIL);
                $val_nit = validar_nit_php($nit_farm); if ($val_nit !== true) $errores_validacion[] = $val_nit; else $nit_a_validar = $nit_farm;
                $val_nom = validar_nombre_php($nom_farm, "Nombre Farmacia"); if ($val_nom !== true) $errores_validacion[] = $val_nom;
                $val_dir = validar_direccion_php($direc_farm); if ($val_dir !== true) $errores_validacion[] = $val_dir;
                if (!empty($nom_gerente_farm)) { $val_gerente = validar_nombre_php($nom_gerente_farm, "Nombre Gerente"); if ($val_gerente !== true) $errores_validacion[] = $val_gerente; }
                if (!empty($tel_farm)) { $val_tel = validar_telefono_php($tel_farm); if ($val_tel !== true) $errores_validacion[] = $val_tel; }
                if (!empty($correo_farm)) { $val_correo = validar_correo_php($correo_farm); if ($val_correo !== true) $errores_validacion[] = $val_correo; }
                $tabla_a_verificar = 'farmacias'; $campo_nit_tabla = 'nit_farm'; $nombre_entidad_tipo_msg = 'Farmacia';
            } elseif ($tipo_entidad === 'eps') {
                $nit_eps = trim($_POST['nit_eps'] ?? ''); $nombre_eps = trim($_POST['nombre_eps'] ?? ''); $direc_eps = trim($_POST['direc_eps'] ?? ''); $nom_gerente_eps = trim($_POST['nom_gerente_eps'] ?? ''); $telefono_eps = trim($_POST['telefono_eps'] ?? ''); $correo_eps = filter_var(trim($_POST['correo_eps'] ?? ''), FILTER_SANITIZE_EMAIL);
                $val_nit = validar_nit_php($nit_eps); if ($val_nit !== true) $errores_validacion[] = $val_nit; else $nit_a_validar = $nit_eps;
                $val_nom = validar_nombre_php($nombre_eps, "Nombre EPS"); if ($val_nom !== true) $errores_validacion[] = $val_nom;
                $val_dir = validar_direccion_php($direc_eps); if ($val_dir !== true) $errores_validacion[] = $val_dir;
                if (!empty($nom_gerente_eps)) { $val_gerente = validar_nombre_php($nom_gerente_eps, "Nombre Gerente"); if ($val_gerente !== true) $errores_validacion[] = $val_gerente; }
                if (!empty($telefono_eps)) { $val_tel = validar_telefono_php($telefono_eps); if ($val_tel !== true) $errores_validacion[] = $val_tel; }
                if (!empty($correo_eps)) { $val_correo = validar_correo_php($correo_eps); if ($val_correo !== true) $errores_validacion[] = $val_correo; }
                $tabla_a_verificar = 'eps'; $campo_nit_tabla = 'nit_eps'; $nombre_entidad_tipo_msg = 'EPS';
            } elseif ($tipo_entidad === 'ips') {
                $nit_ips_post = trim($_POST['nit_ips'] ?? ''); $nom_ips_post = trim($_POST['nom_ips'] ?? ''); $direc_ips_post = trim($_POST['direc_ips'] ?? ''); $nom_gerente_ips_post = trim($_POST['nom_gerente_ips'] ?? ''); $tel_ips_post = trim($_POST['tel_ips'] ?? ''); $correo_ips_post = filter_var(trim($_POST['correo_ips'] ?? ''), FILTER_SANITIZE_EMAIL); $ubicacion_mun_ips = trim($_POST['ubicacion_mun_ips'] ?? '');
                $val_nit = validar_nit_php($nit_ips_post); if ($val_nit !== true) $errores_validacion[] = $val_nit; else $nit_a_validar = $nit_ips_post;
                $val_nom = validar_nombre_php($nom_ips_post, "Nombre IPS"); if ($val_nom !== true) $errores_validacion[] = $val_nom;
                $val_dir = validar_direccion_php($direc_ips_post, "Dirección (Detalle)"); if ($val_dir !== true) $errores_validacion[] = $val_dir;
                if (empty($ubicacion_mun_ips)) $errores_validacion[] = "El municipio de ubicación es obligatorio para IPS."; else if(!ctype_digit($ubicacion_mun_ips)) $errores_validacion[] = "El valor del municipio no es válido.";
                if (!empty($nom_gerente_ips_post)) { $val_gerente = validar_nombre_php($nom_gerente_ips_post, "Nombre Gerente"); if ($val_gerente !== true) $errores_validacion[] = $val_gerente; }
                if (!empty($tel_ips_post)) { $val_tel = validar_telefono_php($tel_ips_post); if ($val_tel !== true) $errores_validacion[] = $val_tel; }
                if (!empty($correo_ips_post)) { $val_correo = validar_correo_php($correo_ips_post); if ($val_correo !== true) $errores_validacion[] = $val_correo; }
                $tabla_a_verificar = 'ips'; $campo_nit_tabla = 'Nit_IPS'; $nombre_entidad_tipo_msg = 'IPS';
            } else {
                if(!empty($tipo_entidad)) { $errores_validacion[] = "Tipo de entidad seleccionado no es válido."; } 
                elseif(empty($tipo_entidad) && isset($_POST['guardar_entidad'])) { $errores_validacion[] = "Debe seleccionar un tipo de entidad para continuar."; }
            }

            if (empty($errores_validacion) && !empty($nit_a_validar) && !empty($tabla_a_verificar)) {
                $stmt_check_nit = $con->prepare("SELECT COUNT(*) FROM {$tabla_a_verificar} WHERE {$campo_nit_tabla} = :nit");
                $stmt_check_nit->bindParam(':nit', $nit_a_validar);
                $stmt_check_nit->execute();
                if ($stmt_check_nit->fetchColumn() > 0) {
                    $errores_validacion[] = "El NIT '" . htmlspecialchars($nit_a_validar) . "' ya se encuentra registrado para una " . htmlspecialchars($nombre_entidad_tipo_msg) . ".";
                }
            }
            
            if (empty($errores_validacion)) {
                $con->beginTransaction();
                if ($tipo_entidad === 'farmacia') {
                    $sql = "INSERT INTO farmacias (nit_farm, nom_farm, direc_farm, nom_gerente, tel_farm, correo_farm) VALUES (:nit, :nombre, :direccion, :gerente, :telefono, :correo)";
                    $stmt = $con->prepare($sql);
                    $params_farm = [':nit' => $nit_farm, ':nombre' => $nom_farm, ':direccion' => $direc_farm, ':gerente' => $nom_gerente_farm ?: null, ':telefono' => $tel_farm ?: null, ':correo' => $correo_farm ?: null ];
                    $stmt->execute($params_farm);
                    $php_success_message = "<div class='alert alert-success'>Farmacia '" . htmlspecialchars($nom_farm) . "' registrada exitosamente.</div>";
                } elseif ($tipo_entidad === 'eps') {
                    $sql = "INSERT INTO eps (nit_eps, nombre_eps, direc_eps, nom_gerente, telefono, correo) VALUES (:nit, :nombre, :direccion, :gerente, :telefono, :correo)";
                    $stmt = $con->prepare($sql);
                    $params_eps = [':nit' => $nit_eps, ':nombre' => $nombre_eps, ':direccion' => $direc_eps, ':gerente' => $nom_gerente_eps ?: null, ':telefono' => $telefono_eps ?: null, ':correo' => $correo_eps ?: null ];
                    $stmt->execute($params_eps);
                    $php_success_message = "<div class='alert alert-success'>EPS '" . htmlspecialchars($nombre_eps) . "' registrada exitosamente.</div>";
                } elseif ($tipo_entidad === 'ips') {
                    $sql = "INSERT INTO ips (Nit_IPS, nom_IPS, direc_IPS, nom_gerente, tel_IPS, correo_IPS, ubicacion_mun) VALUES (:nit_param, :nombre_param, :direccion_param, :gerente_param, :telefono_param, :correo_param, :municipio_param)";
                    $stmt = $con->prepare($sql);
                    $params_ips = [':nit_param' => $nit_ips_post, ':nombre_param' => $nom_ips_post, ':direccion_param' => $direc_ips_post, ':gerente_param' => $nom_gerente_ips_post ?: null, ':telefono_param' => $tel_ips_post ?: null, ':correo_param' => $correo_ips_post ?: null, ':municipio_param' => $ubicacion_mun_ips ];
                    $stmt->execute($params_ips);
                    $php_success_message = "<div class='alert alert-success'>IPS '" . htmlspecialchars($nom_ips_post) . "' registrada exitosamente.</div>";
                }
                $con->commit();
                $tipo_entidad_sel = ''; $nit_farm_val = ''; $nom_farm_val = ''; $direc_farm_val = ''; $nom_gerente_farm_val = ''; $tel_farm_val = ''; $correo_farm_val = '';
                $nit_eps_val = ''; $nombre_eps_val = ''; $direc_eps_val = ''; $nom_gerente_eps_val = ''; $telefono_eps_val = ''; $correo_eps_val = '';
                $nit_ips_val = ''; $nom_ips_val = ''; $id_dep_ips_val = ''; $ubicacion_mun_ips_val = ''; $direc_ips_val = ''; $nom_gerente_ips_val = ''; $tel_ips_val = ''; $correo_ips_val = '';
            } else {
                if ($con->inTransaction()) { $con->rollBack(); }
                $php_error_message = "<div class='alert alert-danger'><strong>No se pudo crear la entidad debido a los siguientes errores:</strong><ul>";
                foreach ($errores_validacion as $error) {
                    $php_error_message .= "<li>" . htmlspecialchars($error) . "</li>";
                }
                $php_error_message .= "</ul></div>";
            }
        } catch (PDOException $e) {
            if ($con->inTransaction()) { $con->rollBack(); }
            $mensaje_error_pdo = "Error de base de datos al intentar guardar la entidad. Por favor, revise los datos o contacte al administrador.";
            error_log("Error PDO en crear_entidad.php: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
            if ($e->getCode() == '23000' || strpos(strtolower($e->getMessage()), 'duplicate entry') !== false || strpos(strtolower($e->getMessage()), 'unique constraint') !== false) { 
                 $mensaje_error_pdo = "Error: Ya existe una entidad con el NIT proporcionado u otro campo único ya está en uso.";
            }
            $php_error_message = "<div class='alert alert-danger'>$mensaje_error_pdo</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Crear Nueva Entidad - Administración</title>
    <link rel="icon" type="image/png" href="../../img/loguito.png">
</head>
<body>
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
             <div class="form-container-entidad mx-auto d-flex flex-column flex-grow-1">
                <h3 class="text-center">Crear Nueva Entidad</h3>
                <div id="global-messages-container">
                    <?php
                        if (!empty($php_error_message)) { echo $php_error_message; }
                        if (!empty($php_success_message)) { echo $php_success_message; }
                    ?>
                </div>
                <form id="formCrearEntidad" action="crear_entidad.php" method="POST" novalidate class="d-flex flex-column flex-grow-1">
                    <div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="tipo_entidad_selector" class="form-label">Seleccione un tipo de entidad:<span class="text-danger">*</span></label>
                                <select id="tipo_entidad_selector" name="tipo_entidad_selector" class="form-select">
                                    <option value="">-- Seleccione un tipo --</option>
                                    <option value="farmacia" <?php echo ($tipo_entidad_sel == 'farmacia') ? 'selected' : ''; ?>>Farmacias</option>
                                    <option value="eps" <?php echo ($tipo_entidad_sel == 'eps') ? 'selected' : ''; ?>>EPS (Aseguradoras)</option>
                                    <option value="ips" <?php echo ($tipo_entidad_sel == 'ips') ? 'selected' : ''; ?>>IPS (Clínicas/Hospitales)</option>
                                </select>
                                <div class="invalid-feedback" id="feedback-tipo_entidad_selector"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-sections-container flex-grow-1">
                        <div id="form_farmacia" class="form-section" style="<?php echo ($tipo_entidad_sel == 'farmacia') ? 'display:block;' : 'display:none;'; ?>">
                            <h4>Datos de la Farmacia</h4>
                            <div class="row g-3">
                                <div class="col-md-4"><label for="nit_farm" class="form-label">NIT:<span class="text-danger">*</span></label><input type="text" id="nit_farm" name="nit_farm" class="form-control" value="<?php echo htmlspecialchars($nit_farm_val); ?>"><div class="invalid-feedback" id="feedback-nit_farm"></div></div>
                                <div class="col-md-8"><label for="nom_farm" class="form-label">Nombre Farmacia:<span class="text-danger">*</span></label><input type="text" id="nom_farm" name="nom_farm" class="form-control" value="<?php echo htmlspecialchars($nom_farm_val); ?>"><div class="invalid-feedback" id="feedback-nom_farm"></div></div>
                                <div class="col-md-12"><label for="direc_farm" class="form-label">Dirección:<span class="text-danger">*</span></label><input type="text" id="direc_farm" name="direc_farm" class="form-control" value="<?php echo htmlspecialchars($direc_farm_val); ?>"><div class="invalid-feedback" id="feedback-direc_farm"></div></div>
                                <div class="col-md-5"><label for="nom_gerente_farm" class="form-label">Nombre Gerente:</label><input type="text" id="nom_gerente_farm" name="nom_gerente_farm" class="form-control" value="<?php echo htmlspecialchars($nom_gerente_farm_val); ?>"><div class="invalid-feedback" id="feedback-nom_gerente_farm"></div></div>
                                <div class="col-md-3"><label for="tel_farm" class="form-label">Teléfono:</label><input type="text" id="tel_farm" name="tel_farm" class="form-control" value="<?php echo htmlspecialchars($tel_farm_val); ?>"><div class="invalid-feedback" id="feedback-tel_farm"></div></div>
                                <div class="col-md-4"><label for="correo_farm" class="form-label">Correo:</label><input type="email" id="correo_farm" name="correo_farm" class="form-control" value="<?php echo htmlspecialchars($correo_farm_val); ?>"><div class="invalid-feedback" id="feedback-correo_farm"></div></div>
                            </div>
                        </div>

                        <div id="form_eps" class="form-section" style="<?php echo ($tipo_entidad_sel == 'eps') ? 'display:block;' : 'display:none;'; ?>">
                            <h4>Datos de la EPS</h4>
                             <div class="row g-3">
                                <div class="col-md-4"><label for="nit_eps" class="form-label">NIT:<span class="text-danger">*</span></label><input type="text" id="nit_eps" name="nit_eps" class="form-control" value="<?php echo htmlspecialchars($nit_eps_val); ?>"><div class="invalid-feedback" id="feedback-nit_eps"></div></div>
                                <div class="col-md-8"><label for="nombre_eps" class="form-label">Nombre EPS:<span class="text-danger">*</span></label><input type="text" id="nombre_eps" name="nombre_eps" class="form-control" value="<?php echo htmlspecialchars($nombre_eps_val); ?>"><div class="invalid-feedback" id="feedback-nombre_eps"></div></div>
                                <div class="col-md-12"><label for="direc_eps" class="form-label">Dirección:<span class="text-danger">*</span></label><input type="text" id="direc_eps" name="direc_eps" class="form-control" value="<?php echo htmlspecialchars($direc_eps_val); ?>"><div class="invalid-feedback" id="feedback-direc_eps"></div></div>
                                 <div class="col-md-5"><label for="nom_gerente_eps" class="form-label">Nombre Gerente:</label><input type="text" id="nom_gerente_eps" name="nom_gerente_eps" class="form-control" value="<?php echo htmlspecialchars($nom_gerente_eps_val); ?>"><div class="invalid-feedback" id="feedback-nom_gerente_eps"></div></div>
                                <div class="col-md-3"><label for="telefono_eps" class="form-label">Teléfono:</label><input type="text" id="telefono_eps" name="telefono_eps" class="form-control" value="<?php echo htmlspecialchars($telefono_eps_val); ?>"><div class="invalid-feedback" id="feedback-telefono_eps"></div></div>
                                <div class="col-md-4"><label for="correo_eps" class="form-label">Correo:</label><input type="email" id="correo_eps" name="correo_eps" class="form-control" value="<?php echo htmlspecialchars($correo_eps_val); ?>"><div class="invalid-feedback" id="feedback-correo_eps"></div></div>
                            </div>
                        </div>

                        <div id="form_ips" class="form-section" style="<?php echo ($tipo_entidad_sel == 'ips') ? 'display:block;' : 'display:none;'; ?>">
                            <h4>Datos de la IPS</h4>
                            <div class="row g-3">
                                <div class="col-md-4"><label for="nit_ips" class="form-label">NIT:<span class="text-danger">*</span></label><input type="text" id="nit_ips" name="nit_ips" class="form-control" value="<?php echo htmlspecialchars($nit_ips_val); ?>"><div class="invalid-feedback" id="feedback-nit_ips"></div></div>
                                <div class="col-md-8"><label for="nom_ips" class="form-label">Nombre IPS:<span class="text-danger">*</span></label><input type="text" id="nom_ips" name="nom_ips" class="form-control" value="<?php echo htmlspecialchars($nom_ips_val); ?>"><div class="invalid-feedback" id="feedback-nom_ips"></div></div>
                               <div class="col-md-4">
                                    <label for="id_dep_ips" class="form-label">Departamento Ubicación:<span class="text-danger">*</span></label>
                                    <select id="id_dep_ips" name="id_dep_ips" class="form-select">
                                        <option value="">Seleccione Departamento...</option>
                                        <?php foreach ($departamentos as $dep) : ?>
                                            <option value="<?php echo htmlspecialchars($dep['id_dep']); ?>" <?php echo ($id_dep_ips_val == $dep['id_dep']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dep['nom_dep']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback" id="feedback-id_dep_ips"></div>
                                </div>
                                <div class="col-md-4">
                                    <label for="ubicacion_mun_ips" class="form-label">Municipio Ubicación:<span class="text-danger">*</span></label>
                                    <select id="ubicacion_mun_ips" name="ubicacion_mun_ips" class="form-select">
                                        <option value="">Seleccione Departamento primero...</option>
                                        <?php
                                        if (!empty($id_dep_ips_val) && $con) {
                                            try {
                                                $stmt_mun_ips_post = $con->prepare("SELECT id_mun, nom_mun FROM municipio WHERE id_dep = :id_dep ORDER BY nom_mun ASC");
                                                $stmt_mun_ips_post->bindParam(':id_dep', $id_dep_ips_val);
                                                $stmt_mun_ips_post->execute();
                                                $municipios_ips_post = $stmt_mun_ips_post->fetchAll(PDO::FETCH_ASSOC);
                                                if($municipios_ips_post){
                                                    echo '<option value="">Seleccione Municipio...</option>';
                                                    foreach($municipios_ips_post as $mun_ips){
                                                        $selected_mun_ips = ($ubicacion_mun_ips_val == $mun_ips['id_mun']) ? 'selected' : '';
                                                        echo "<option value='".htmlspecialchars($mun_ips['id_mun'])."' $selected_mun_ips>".htmlspecialchars($mun_ips['nom_mun'])."</option>";
                                                    }
                                                } else {
                                                    echo '<option value="">No hay municipios para este departamento</option>';
                                                }
                                            } catch (PDOException $e) {
                                                echo '<option value="">Error al cargar municipios</option>';
                                                error_log("Error cargando municipios para POST en crear_entidad: " . $e->getMessage());
                                            }
                                        }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback" id="feedback-ubicacion_mun_ips"></div>
                                </div>
                                 <div class="col-md-4"><label for="direc_ips" class="form-label">Dirección (Detalle):<span class="text-danger">*</span></label><input type="text" id="direc_ips" name="direc_ips" class="form-control" value="<?php echo htmlspecialchars($direc_ips_val); ?>"><div class="invalid-feedback" id="feedback-direc_ips"></div></div>
                                 <div class="col-md-5"><label for="nom_gerente_ips" class="form-label">Nombre Gerente:</label><input type="text" id="nom_gerente_ips" name="nom_gerente_ips" class="form-control" value="<?php echo htmlspecialchars($nom_gerente_ips_val); ?>"><div class="invalid-feedback" id="feedback-nom_gerente_ips"></div></div>
                                <div class="col-md-3"><label for="tel_ips" class="form-label">Teléfono:</label><input type="text" id="tel_ips" name="tel_ips" class="form-control" value="<?php echo htmlspecialchars($tel_ips_val); ?>"><div class="invalid-feedback" id="feedback-tel_ips"></div></div>
                                <div class="col-md-4"><label for="correo_ips" class="form-label">Correo:</label><input type="email" id="correo_ips" name="correo_ips" class="form-control" value="<?php echo htmlspecialchars($correo_ips_val); ?>"><div class="invalid-feedback" id="feedback-correo_ips"></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-auto pt-3">
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" name="guardar_entidad" class="btn btn-success w-100">
                                    Guardar Entidad <i class="bi bi-check-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include '../../include/footer.php'; ?>
    <script src="../js/crear_entidad.js?v=<?php echo time(); ?>"></script>
</body>
</html>