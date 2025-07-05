<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once('../../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) {
    header('Location: ../inicio_sesion.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    $conex_db_post = new database();
    $con_post = $conex_db_post->conectar();
    
    if (!$con_post) {
        echo json_encode(['status' => 'error', 'message' => 'Error crítico: No se pudo conectar a la base de datos para guardar.']);
        exit;
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
        if (empty($telefono)) return "El teléfono es obligatorio.";
        if (!ctype_digit($telefono)) return "El teléfono debe contener solo números.";
        if (strlen($telefono) < 7 || strlen($telefono) > 10) return "El teléfono debe tener entre 7 y 10 dígitos.";
        return true;
    }
    function validar_correo_php($correo) {
        if (empty($correo)) return "El correo es obligatorio.";
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) return "El formato del correo no es válido.";
        return true;
    }

    $tipo_entidad = $_POST['tipo_entidad_selector'] ?? '';
    $errores_validacion = [];
    $nombre_entidad_creada = '';
    
    try {
        $con_post->beginTransaction();

        if ($tipo_entidad === 'farmacia') {
            $nit_farm = trim($_POST['nit_farm'] ?? '');
            $nom_farm = trim($_POST['nom_farm'] ?? '');
            $direc_farm = trim($_POST['direc_farm'] ?? '');
            $nom_gerente_farm = trim($_POST['nom_gerente_farm'] ?? '');
            $tel_farm = trim($_POST['tel_farm'] ?? '');
            $correo_farm = filter_var(trim($_POST['correo_farm'] ?? ''), FILTER_SANITIZE_EMAIL);

            $val_nit = validar_nit_php($nit_farm); if ($val_nit !== true) $errores_validacion[] = $val_nit;
            $val_nom = validar_nombre_php($nom_farm, "Nombre Farmacia"); if ($val_nom !== true) $errores_validacion[] = $val_nom;
            $val_dir = validar_direccion_php($direc_farm); if ($val_dir !== true) $errores_validacion[] = $val_dir;
            $val_gerente = validar_nombre_php($nom_gerente_farm, "Nombre Gerente"); if ($val_gerente !== true) $errores_validacion[] = $val_gerente;
            $val_tel = validar_telefono_php($tel_farm); if ($val_tel !== true) $errores_validacion[] = $val_tel;
            $val_correo = validar_correo_php($correo_farm); if ($val_correo !== true) $errores_validacion[] = $val_correo;

            if (empty($errores_validacion)) {
                $sql = "INSERT INTO farmacias (nit_farm, nom_farm, direc_farm, nom_gerente, tel_farm, correo_farm) VALUES (:nit, :nombre, :direccion, :gerente, :telefono, :correo)";
                $stmt = $con_post->prepare($sql);
                $params_farm = [':nit' => $nit_farm, ':nombre' => $nom_farm, ':direccion' => $direc_farm, ':gerente' => $nom_gerente_farm, ':telefono' => $tel_farm, ':correo' => $correo_farm];
                $stmt->execute($params_farm);
                $nombre_entidad_creada = $nom_farm;
            }
        } elseif ($tipo_entidad === 'eps') {
            $nit_eps = trim($_POST['nit_eps'] ?? '');
            $nombre_eps = trim($_POST['nombre_eps'] ?? '');
            $direc_eps = trim($_POST['direc_eps'] ?? '');
            $nom_gerente_eps = trim($_POST['nom_gerente_eps'] ?? '');
            $telefono_eps = trim($_POST['telefono_eps'] ?? '');
            $correo_eps = filter_var(trim($_POST['correo_eps'] ?? ''), FILTER_SANITIZE_EMAIL);

            $val_nit = validar_nit_php($nit_eps); if ($val_nit !== true) $errores_validacion[] = $val_nit;
            $val_nom = validar_nombre_php($nombre_eps, "Nombre EPS"); if ($val_nom !== true) $errores_validacion[] = $val_nom;
            $val_dir = validar_direccion_php($direc_eps); if ($val_dir !== true) $errores_validacion[] = $val_dir;
            $val_gerente = validar_nombre_php($nom_gerente_eps, "Nombre Gerente"); if ($val_gerente !== true) $errores_validacion[] = $val_gerente;
            $val_tel = validar_telefono_php($telefono_eps); if ($val_tel !== true) $errores_validacion[] = $val_tel;
            $val_correo = validar_correo_php($correo_eps); if ($val_correo !== true) $errores_validacion[] = $val_correo;
            
            if (empty($errores_validacion)) {
                $sql = "INSERT INTO eps (nit_eps, nombre_eps, direc_eps, nom_gerente, telefono, correo) VALUES (:nit, :nombre, :direccion, :gerente, :telefono, :correo)";
                $stmt = $con_post->prepare($sql);
                $params_eps = [':nit' => $nit_eps, ':nombre' => $nombre_eps, ':direccion' => $direc_eps, ':gerente' => $nom_gerente_eps, ':telefono' => $telefono_eps, ':correo' => $correo_eps];
                $stmt->execute($params_eps);
                $nombre_entidad_creada = $nombre_eps;
            }
        } elseif ($tipo_entidad === 'ips') {
            $nit_ips_post = trim($_POST['nit_ips'] ?? '');
            $nom_ips_post = trim($_POST['nom_ips'] ?? '');
            $direc_ips_post = trim($_POST['direc_ips'] ?? '');
            $nom_gerente_ips_post = trim($_POST['nom_gerente_ips'] ?? '');
            $tel_ips_post = trim($_POST['tel_ips'] ?? '');
            $correo_ips_post = filter_var(trim($_POST['correo_ips'] ?? ''), FILTER_SANITIZE_EMAIL);
            $ubicacion_mun_ips = trim($_POST['ubicacion_mun_ips'] ?? '');

            $val_nit = validar_nit_php($nit_ips_post); if ($val_nit !== true) $errores_validacion[] = $val_nit;
            $val_nom = validar_nombre_php($nom_ips_post, "Nombre IPS"); if ($val_nom !== true) $errores_validacion[] = $val_nom;
            $val_dir = validar_direccion_php($direc_ips_post, "Dirección (Detalle)"); if ($val_dir !== true) $errores_validacion[] = $val_dir;
            if (empty($ubicacion_mun_ips)) $errores_validacion[] = "El municipio de ubicación es obligatorio para IPS.";
            else if(!ctype_digit($ubicacion_mun_ips)) $errores_validacion[] = "El valor del municipio no es válido.";
            $val_gerente = validar_nombre_php($nom_gerente_ips_post, "Nombre Gerente"); if ($val_gerente !== true) $errores_validacion[] = $val_gerente;
            $val_tel = validar_telefono_php($tel_ips_post); if ($val_tel !== true) $errores_validacion[] = $val_tel;
            $val_correo = validar_correo_php($correo_ips_post); if ($val_correo !== true) $errores_validacion[] = $val_correo;

            if (empty($errores_validacion)) {
                $sql = "INSERT INTO ips (Nit_IPS, nom_IPS, direc_IPS, nom_gerente, tel_IPS, correo_IPS, ubicacion_mun) VALUES (:nit_param, :nombre_param, :direccion_param, :gerente_param, :telefono_param, :correo_param, :municipio_param)";
                $stmt = $con_post->prepare($sql);
                $params_ips = [':nit_param' => $nit_ips_post, ':nombre_param' => $nom_ips_post, ':direccion_param' => $direc_ips_post, ':gerente_param' => $nom_gerente_ips_post, ':telefono_param' => $tel_ips_post, ':correo_param' => $correo_ips_post, ':municipio_param' => $ubicacion_mun_ips];
                $stmt->execute($params_ips);
                $nombre_entidad_creada = $nom_ips_post;
            }
        } else {
            $errores_validacion[] = "Debe seleccionar un tipo de entidad válido.";
        }

        if (!empty($errores_validacion)) {
            if ($con_post->inTransaction()) { $con_post->rollBack(); }
            echo json_encode(['status' => 'error', 'message' => implode(' ', $errores_validacion)]);
        } else {
            if ($con_post->inTransaction()) { $con_post->commit(); }
            echo json_encode(['status' => 'success', 'message' => "La entidad '" . htmlspecialchars($nombre_entidad_creada) . "' ha sido creada correctamente."]);
        }
    } catch (PDOException $e) {
        if ($con_post->inTransaction()) { $con_post->rollBack(); }
        $mensaje_error_pdo = "Error al guardar la entidad.";
        if ($e->getCode() == '23000' || $e->getCode() == 1062) { 
             $mensaje_error_pdo = "Error: Ya existe una entidad con el NIT proporcionado.";
        }
        echo json_encode(['status' => 'error', 'message' => $mensaje_error_pdo]);
    }
    exit;
}

$conex_db = new database();
$con = $conex_db->conectar();
$departamentos = [];
$php_error_message = '';
if ($con) {
    try {
        $stmt_dep = $con->query("SELECT id_dep, nom_dep FROM departamento ORDER BY nom_dep ASC");
        $departamentos = $stmt_dep->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $php_error_message = "<div class='alert alert-danger'>Error al cargar departamentos: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    $php_error_message = "<div class='alert alert-danger'>Error de conexión inicial a la base de datos.</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Crear Nueva Entidad - Administración</title>
</head>
<body>
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
             <div class="form-container-entidad mx-auto d-flex flex-column flex-grow-1">
                <h3 class="text-center">Crear Nueva Entidad</h3>
                <div id="global-messages-container">
                    <?php if (!empty($php_error_message)) { echo $php_error_message; } ?>
                </div>
                <form id="formCrearEntidad" action="crear_entidad.php" method="POST" novalidate class="d-flex flex-column flex-grow-1">
                    <div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="tipo_entidad_selector" class="form-label">Seleccione un tipo de entidad:<span class="text-danger">*</span></label>
                                <select id="tipo_entidad_selector" name="tipo_entidad_selector" class="form-select">
                                    <option value="">-- Seleccione un tipo --</option>
                                    <option value="farmacia">Farmacias</option>
                                    <option value="eps">EPS (Aseguradoras)</option>
                                    <option value="ips">IPS (Clínicas/Hospitales)</option>
                                </select>
                                <div class="invalid-feedback" id="feedback-tipo_entidad_selector"></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-sections-container flex-grow-1">
                        <div id="form_farmacia" class="form-section" style="display:none;">
                            <h4>Datos de la Farmacia</h4>
                            <div class="row g-3">
                                <div class="col-md-4"><label for="nit_farm" class="form-label">NIT:<span class="text-danger">*</span></label><input type="text" id="nit_farm" name="nit_farm" class="form-control"><div class="invalid-feedback" id="feedback-nit_farm"></div></div>
                                <div class="col-md-8"><label for="nom_farm" class="form-label">Nombre Farmacia:<span class="text-danger">*</span></label><input type="text" id="nom_farm" name="nom_farm" class="form-control"><div class="invalid-feedback" id="feedback-nom_farm"></div></div>
                                <div class="col-md-12"><label for="direc_farm" class="form-label">Dirección:<span class="text-danger">*</span></label><input type="text" id="direc_farm" name="direc_farm" class="form-control"><div class="invalid-feedback" id="feedback-direc_farm"></div></div>
                                <div class="col-md-5"><label for="nom_gerente_farm" class="form-label">Nombre Gerente:<span class="text-danger">*</span></label><input type="text" id="nom_gerente_farm" name="nom_gerente_farm" class="form-control"><div class="invalid-feedback" id="feedback-nom_gerente_farm"></div></div>
                                <div class="col-md-3"><label for="tel_farm" class="form-label">Teléfono:<span class="text-danger">*</span></label><input type="text" id="tel_farm" name="tel_farm" class="form-control"><div class="invalid-feedback" id="feedback-tel_farm"></div></div>
                                <div class="col-md-4"><label for="correo_farm" class="form-label">Correo:<span class="text-danger">*</span></label><input type="email" id="correo_farm" name="correo_farm" class="form-control"><div class="invalid-feedback" id="feedback-correo_farm"></div></div>
                            </div>
                        </div>
                        <div id="form_eps" class="form-section" style="display:none;">
                            <h4>Datos de la EPS</h4>
                             <div class="row g-3">
                                <div class="col-md-4"><label for="nit_eps" class="form-label">NIT:<span class="text-danger">*</span></label><input type="text" id="nit_eps" name="nit_eps" class="form-control"><div class="invalid-feedback" id="feedback-nit_eps"></div></div>
                                <div class="col-md-8"><label for="nombre_eps" class="form-label">Nombre EPS:<span class="text-danger">*</span></label><input type="text" id="nombre_eps" name="nombre_eps" class="form-control"><div class="invalid-feedback" id="feedback-nombre_eps"></div></div>
                                <div class="col-md-12"><label for="direc_eps" class="form-label">Dirección:<span class="text-danger">*</span></label><input type="text" id="direc_eps" name="direc_eps" class="form-control"><div class="invalid-feedback" id="feedback-direc_eps"></div></div>
                                 <div class="col-md-5"><label for="nom_gerente_eps" class="form-label">Nombre Gerente:<span class="text-danger">*</span></label><input type="text" id="nom_gerente_eps" name="nom_gerente_eps" class="form-control"><div class="invalid-feedback" id="feedback-nom_gerente_eps"></div></div>
                                <div class="col-md-3"><label for="telefono_eps" class="form-label">Teléfono:<span class="text-danger">*</span></label><input type="text" id="telefono_eps" name="telefono_eps" class="form-control"><div class="invalid-feedback" id="feedback-telefono_eps"></div></div>
                                <div class="col-md-4"><label for="correo_eps" class="form-label">Correo:<span class="text-danger">*</span></label><input type="email" id="correo_eps" name="correo_eps" class="form-control"><div class="invalid-feedback" id="feedback-correo_eps"></div></div>
                            </div>
                        </div>
                        <div id="form_ips" class="form-section" style="display:none;">
                            <h4>Datos de la IPS</h4>
                            <div class="row g-3">
                                <div class="col-md-4"><label for="nit_ips" class="form-label">NIT:<span class="text-danger">*</span></label><input type="text" id="nit_ips" name="nit_ips" class="form-control"><div class="invalid-feedback" id="feedback-nit_ips"></div></div>
                                <div class="col-md-8"><label for="nom_ips" class="form-label">Nombre IPS:<span class="text-danger">*</span></label><input type="text" id="nom_ips" name="nom_ips" class="form-control"><div class="invalid-feedback" id="feedback-nom_ips"></div></div>
                               <div class="col-md-4">
                                    <label for="id_dep_ips" class="form-label">Departamento Ubicación:<span class="text-danger">*</span></label>
                                    <select id="id_dep_ips" name="id_dep_ips" class="form-select">
                                        <option value="">Seleccione Departamento...</option>
                                        <?php foreach ($departamentos as $dep) : ?>
                                            <option value="<?php echo htmlspecialchars($dep['id_dep']); ?>"><?php echo htmlspecialchars($dep['nom_dep']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback" id="feedback-id_dep_ips"></div>
                                </div>
                                <div class="col-md-4">
                                    <label for="ubicacion_mun_ips" class="form-label">Municipio Ubicación:<span class="text-danger">*</span></label>
                                    <select id="ubicacion_mun_ips" name="ubicacion_mun_ips" class="form-select" disabled>
                                        <option value="">Seleccione Departamento primero...</option>
                                    </select>
                                    <div class="invalid-feedback" id="feedback-ubicacion_mun_ips"></div>
                                </div>
                                 <div class="col-md-4"><label for="direc_ips" class="form-label">Dirección (Detalle):<span class="text-danger">*</span></label><input type="text" id="direc_ips" name="direc_ips" class="form-control"><div class="invalid-feedback" id="feedback-direc_ips"></div></div>
                                 <div class="col-md-5"><label for="nom_gerente_ips" class="form-label">Nombre Gerente:<span class="text-danger">*</span></label><input type="text" id="nom_gerente_ips" name="nom_gerente_ips" class="form-control"><div class="invalid-feedback" id="feedback-nom_gerente_ips"></div></div>
                                <div class="col-md-3"><label for="tel_ips" class="form-label">Teléfono:<span class="text-danger">*</span></label><input type="text" id="tel_ips" name="tel_ips" class="form-control"><div class="invalid-feedback" id="feedback-tel_ips"></div></div>
                                <div class="col-md-4"><label for="correo_ips" class="form-label">Correo:<span class="text-danger">*</span></label><input type="email" id="correo_ips" name="correo_ips" class="form-control"><div class="invalid-feedback" id="feedback-correo_ips"></div></div>
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

    <div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-custom">
            <div class="modal-content modal-content-custom">
                <div class="modal-body text-center p-4">
                    <div class="modal-icon-container">
                        <div id="modalIcon"></div>
                    </div>
                    <h4 class="mt-3 fw-bold" id="modalTitle"></h4>
                    <p id="modalMessage" class="mt-2 text-muted"></p>
                </div>
                <div class="modal-footer-custom">
                    <button type="button" class="btn btn-primary-custom" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../include/footer.php'; ?>
    <script src="../js/crear_entidad.js?v=<?php echo time(); ?>"></script>
</body>
</html>