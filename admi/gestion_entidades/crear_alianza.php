<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Sesión no válida o expirada.']);
        exit;
    } else {
        header('Location: ../inicio_sesion.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Error desconocido.'];

    if (empty($_SESSION['csrf_token_alianza']) || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token_alianza'], $_POST['csrf_token'])) {
        $response['message'] = 'Error de validación CSRF.';
        echo json_encode($response);
        exit;
    }

    $id_eps_post = trim($_POST['id_eps_alianza'] ?? '');
    $tipo_alianza_post = trim($_POST['tipo_alianza'] ?? '');
    $id_entidad_aliada_post = trim($_POST['id_entidad_aliada'] ?? '');
    $id_estado_post = filter_input(INPUT_POST, 'id_estado_alianza', FILTER_VALIDATE_INT);
    $fecha_actual_db = date('Y-m-d');

    $errores_validacion_alianza = [];
    if (empty($id_eps_post)) $errores_validacion_alianza[] = "Debe seleccionar una EPS.";
    if (empty($tipo_alianza_post) || !in_array($tipo_alianza_post, ['farmacia', 'ips'])) $errores_validacion_alianza[] = "Debe seleccionar un tipo de alianza (Farmacia o IPS).";
    if (empty($id_entidad_aliada_post)) $errores_validacion_alianza[] = "Debe seleccionar la entidad específica a aliar.";
    if (empty($id_estado_post) || !in_array($id_estado_post, [1, 2])) $errores_validacion_alianza[] = "Debe seleccionar un estado válido para la alianza.";

    if (!empty($errores_validacion_alianza)) {
        $response['message'] = implode("\n", $errores_validacion_alianza);
        echo json_encode($response);
        exit;
    }

    if (isset($con) && $con instanceof PDO) {
        try {
            $tabla_detalle = '';
            $columna_entidad_aliada = '';
            $nombre_entidad_aliada_tipo = '';

            if ($tipo_alianza_post === 'farmacia') {
                $tabla_detalle = 'detalle_eps_farm';
                $columna_entidad_aliada = 'nit_farm';
                $nombre_entidad_aliada_tipo = 'Farmacia';
            } elseif ($tipo_alianza_post === 'ips') {
                $tabla_detalle = 'detalle_eps_ips';
                $columna_entidad_aliada = 'nit_ips';
                $nombre_entidad_aliada_tipo = 'IPS';
            }

            $sql_check = "SELECT id_estado FROM $tabla_detalle WHERE nit_eps = :nit_eps AND $columna_entidad_aliada = :id_entidad_aliada";
            $stmt_check = $con->prepare($sql_check);
            $stmt_check->bindParam(':nit_eps', $id_eps_post, PDO::PARAM_STR);
            $stmt_check->bindParam(':id_entidad_aliada', $id_entidad_aliada_post, PDO::PARAM_STR);
            $stmt_check->execute();
            $estado_actual_alianza_db = $stmt_check->fetchColumn(); // Puede ser false si no existe, o el id_estado
            
            $alianza_existe_fisicamente = ($estado_actual_alianza_db !== false);

            if ($alianza_existe_fisicamente && $estado_actual_alianza_db == 1 && $id_estado_post == 1) {
                $response['message'] = "Ya existe una alianza activa entre esta EPS y la $nombre_entidad_aliada_tipo seleccionada.";
                echo json_encode($response);
                exit;
            }
            if ($alianza_existe_fisicamente && $estado_actual_alianza_db == 2 && $id_estado_post == 2) {
                 $response['message'] = "La alianza con esta $nombre_entidad_aliada_tipo ya se encuentra inactiva.";
                 echo json_encode($response);
                 exit;
            }


            if ($alianza_existe_fisicamente) {
                $sql_update = "UPDATE $tabla_detalle SET fecha = :fecha, id_estado = :id_estado WHERE nit_eps = :nit_eps AND $columna_entidad_aliada = :id_entidad_aliada";
                $stmt_save = $con->prepare($sql_update);
                $response['action_type'] = 'updated';
            } else {
                $sql_insert = "INSERT INTO $tabla_detalle (nit_eps, $columna_entidad_aliada, fecha, id_estado) VALUES (:nit_eps, :id_entidad_aliada, :fecha, :id_estado)";
                $stmt_save = $con->prepare($sql_insert);
                $response['action_type'] = 'created';
            }

            $stmt_save->bindParam(':nit_eps', $id_eps_post, PDO::PARAM_STR);
            $stmt_save->bindParam(':id_entidad_aliada', $id_entidad_aliada_post, PDO::PARAM_STR);
            $stmt_save->bindParam(':fecha', $fecha_actual_db, PDO::PARAM_STR);
            $stmt_save->bindParam(':id_estado', $id_estado_post, PDO::PARAM_INT);

            if ($stmt_save->execute()) {
                $response['success'] = true;
                $accion_texto = ($response['action_type'] == 'updated') ? 'actualizada' : 'creada';
                $estado_texto = ($id_estado_post == 1) ? 'activa' : 'inactiva';
                $response['message'] = "Alianza $accion_texto como $estado_texto correctamente.";
            } else {
                $errorInfo = $stmt_save->errorInfo();
                $response['message'] = 'Error al guardar la alianza: ' . ($errorInfo[2] ?? 'Error desconocido');
            }

        } catch (PDOException $e) {
            $response['message'] = 'Error de base de datos: ' . $e->getMessage();
            error_log("PDOException en procesar_alianza (integrado): " . $e->getMessage());
        }
    } else {
        $response['message'] = 'Error de conexión a la base de datos.';
    }

    echo json_encode($response);
    exit; 
}


if (empty($_SESSION['csrf_token_alianza'])) { 
    $_SESSION['csrf_token_alianza'] = bin2hex(random_bytes(32)); 
}
$csrf_token_alianza = $_SESSION['csrf_token_alianza'];

$eps_list = [];
$estados_alianza = [];
$error_carga_inicial = '';

if ($con) {
    try {
        $stmt_eps = $con->query("SELECT nit_eps, nombre_eps FROM eps ORDER BY nombre_eps ASC");
        $eps_list = $stmt_eps->fetchAll(PDO::FETCH_ASSOC);

        $stmt_estados = $con->query("SELECT id_est, nom_est FROM estado WHERE id_est IN (1, 2) ORDER BY FIELD(id_est, 1, 2)");
        $estados_alianza = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error_carga_inicial = "Error al cargar datos iniciales: " . $e->getMessage();
        error_log("PDO Error en crear_alianza.php (carga inicial): " . $e->getMessage());
    }
} else {
    $error_carga_inicial = "Error de conexión a la base de datos.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Crear Nueva Alianza EPS</title>
    <link rel="icon" type="image/png" href="../img/loguito.png">
<body class="d-flex flex-column min-vh-100">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1">
        <div class="container-fluid mt-4">
            <div class="form-container mx-auto" style="max-width: 700px;">
                <h3 class="text-center mb-4">Crear Nueva Alianza con EPS</h3>

                <div id="mensajesAlianzaServidor" class="mb-3">
                    <?php if (!empty($error_carga_inicial)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error_carga_inicial); ?></div>
                    <?php endif; ?>
                </div>

                <form id="formCrearAlianza" method="POST" action="crear_alianza.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_alianza); ?>">
                    
                    <div class="mb-3">
                        <label for="id_eps_alianza" class="form-label">Seleccione EPS (*):</label>
                        <select id="id_eps_alianza" name="id_eps_alianza" class="form-select" required>
                            <option value="">Seleccione una EPS...</option>
                            <?php foreach ($eps_list as $eps) : ?>
                                <option value="<?php echo htmlspecialchars($eps['nit_eps']); ?>"><?php echo htmlspecialchars($eps['nombre_eps']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="tipo_alianza" class="form-label">Aliar con (*):</label>
                        <select id="tipo_alianza" name="tipo_alianza" class="form-select" required>
                            <option value="">Seleccione tipo de entidad...</option>
                            <option value="farmacia">Farmacia</option>
                            <option value="ips">IPS</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    
                    <div class="mb-3" id="contenedor_select_entidad_aliada" style="display: none;">
                        <label for="id_entidad_aliada" class="form-label">Seleccione Entidad Específica (*):</label>
                        <select id="id_entidad_aliada" name="id_entidad_aliada" class="form-select" required disabled>
                            <option value="">Seleccione tipo de alianza primero...</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="id_estado_alianza" class="form-label">Estado de la Alianza (*):</label>
                        <select id="id_estado_alianza" name="id_estado_alianza" class="form-select" required>
                            <?php foreach ($estados_alianza as $estado) : ?>
                                <option value="<?php echo htmlspecialchars($estado['id_est']); ?>" <?php echo ($estado['id_est'] == 1) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($estado['nom_est'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-link-45deg"></i> Crear / Actualizar Alianza
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include '../../include/footer.php'; ?>
    <script src="../js/crear_alianza.js?v=<?php echo time(); ?>"></script>
</body>
</html>