<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 || !isset($_SESSION['nombre_usuario'])) {
    header('Location: ../inicio_sesion.php');
    exit;
}

$ID_ROL_MEDICO_PHP = 4;
$ID_ESPECIALIDAD_NO_APLICA_PHP = 46; 
$ID_ESTADO_AFILIADO_ACTIVO = 1;
$ID_ESTADO_AFILIADO_INACTIVO = 2; 

$tipos_doc = []; $departamentos = []; $municipios_pre = []; $barrios_pre = [];
$generos = []; $estados_usuarios = []; $especialidades = []; $roles = [];

$doc_usu_form = ''; $id_tipo_doc_sel_form = ''; $nom_usu_form = ''; $fecha_nac_form = '';
$tel_usu_form = ''; $correo_usu_form = ''; $id_dep_sel_form = ''; $id_mun_sel_form = '';
$id_barrio_sel_form = ''; $direccion_usu_form = ''; $id_gen_sel_form = ''; $id_est_sel_usuario_form = 1;
$id_especialidad_sel_form = ''; $id_rol_sel_form = '';

$php_error_message = '';
$php_success_message_registro = '';
$php_warning_message_afiliacion_link = '';
$php_info_message_correo = '';


if (isset($_SESSION['form_data_crear_usu'])) {
    $form_data = $_SESSION['form_data_crear_usu'];
    $doc_usu_form = trim($form_data['doc_usu'] ?? $doc_usu_form);
    $id_tipo_doc_sel_form = filter_var($form_data['id_tipo_doc'] ?? $id_tipo_doc_sel_form, FILTER_VALIDATE_INT);
    $nom_usu_form = trim($form_data['nom_usu'] ?? $nom_usu_form);
    $fecha_nac_form = trim($form_data['fecha_nac'] ?? $fecha_nac_form);
    $tel_usu_form = trim($form_data['tel_usu'] ?? $tel_usu_form);
    $correo_usu_form = filter_var(trim($form_data['correo_usu'] ?? $correo_usu_form), FILTER_SANITIZE_EMAIL);
    $id_dep_sel_form = trim($form_data['id_dep'] ?? $id_dep_sel_form);
    $id_mun_sel_form = trim($form_data['id_mun'] ?? $id_mun_sel_form);
    $id_barrio_sel_form = trim($form_data['id_barrio'] ?? $id_barrio_sel_form);
    $direccion_usu_form = trim($form_data['direccion_usu'] ?? $direccion_usu_form);
    $id_gen_sel_form = filter_var($form_data['id_gen'] ?? $id_gen_sel_form, FILTER_VALIDATE_INT);
    $id_est_sel_usuario_form = filter_var($form_data['id_est'] ?? $id_est_sel_usuario_form, FILTER_VALIDATE_INT);
    if (empty($id_est_sel_usuario_form)) $id_est_sel_usuario_form = 1;
    $id_especialidad_sel_form = filter_var($form_data['id_especialidad'] ?? $id_especialidad_sel_form, FILTER_VALIDATE_INT);
    $id_rol_sel_form = filter_var($form_data['id_rol'] ?? $id_rol_sel_form, FILTER_VALIDATE_INT);
    unset($_SESSION['form_data_crear_usu']);
}

if(isset($_GET['correo_status'])) {
    $nombre_usuario_creado = htmlspecialchars($_GET['nombre'] ?? 'el usuario');
    if ($_GET['correo_status'] === 'enviado_activo') {
        $php_info_message_correo = "<div class='alert alert-info'>Cuenta para <strong>$nombre_usuario_creado</strong> creada y activada. Correo de bienvenida enviado a ".htmlspecialchars($_GET['email'] ?? '').".</div>";
    } elseif ($_GET['correo_status'] === 'enviado_inactivo') {
         $php_info_message_correo = "<div class='alert alert-info'>Cuenta para <strong>$nombre_usuario_creado</strong> creada como inactiva. Correo de bienvenida enviado a ".htmlspecialchars($_GET['email'] ?? '').". Espere correo de activación.</div>";
    } elseif ($_GET['correo_status'] === 'error') {
        $php_error_message .= "<div class='alert alert-danger'>Usuario registrado, pero hubo un error al enviar el correo de creación de cuenta.</div>";
    }
    $doc_usu_form = ''; $id_tipo_doc_sel_form = ''; $nom_usu_form = ''; $fecha_nac_form = ''; $tel_usu_form = ''; $correo_usu_form = ''; $id_dep_sel_form = ''; $id_mun_sel_form = ''; $id_barrio_sel_form = ''; $direccion_usu_form = ''; $id_gen_sel_form = ''; $id_est_sel_usuario_form = 1; $id_especialidad_sel_form = ''; $id_rol_sel_form = ''; $municipios_pre = []; $barrios_pre = [];
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['crear_usuario'])) {
    $doc_usu_post = trim($_POST['doc_usu'] ?? '');
    $id_tipo_doc_sel_post = filter_input(INPUT_POST, 'id_tipo_doc', FILTER_VALIDATE_INT);
    $nom_usu_post = trim($_POST['nom_usu'] ?? '');
    $fecha_nac_post = trim($_POST['fecha_nac'] ?? '');
    $tel_usu_post = trim($_POST['tel_usu'] ?? '');
    $correo_usu_post_form = filter_var(trim($_POST['correo_usu'] ?? ''), FILTER_SANITIZE_EMAIL);
    $id_dep_sel_post = trim($_POST['id_dep'] ?? '');
    $id_mun_sel_post = trim($_POST['id_mun'] ?? '');
    $id_barrio_sel_post = trim($_POST['id_barrio'] ?? '');
    $direccion_usu_post = trim($_POST['direccion_usu'] ?? '');
    $id_gen_sel_post = filter_input(INPUT_POST, 'id_gen', FILTER_VALIDATE_INT);
    $id_est_sel_usuario_post = filter_input(INPUT_POST, 'id_est', FILTER_VALIDATE_INT);
    if (empty($id_est_sel_usuario_post)) $id_est_sel_usuario_post = 1;
    $id_especialidad_sel_post = filter_input(INPUT_POST, 'id_especialidad', FILTER_VALIDATE_INT);
    $id_rol_sel_post = filter_input(INPUT_POST, 'id_rol', FILTER_VALIDATE_INT);
    $contra_nueva_post = $_POST['contraseña'] ?? '';

    $doc_usu_form = $doc_usu_post; $id_tipo_doc_sel_form = $id_tipo_doc_sel_post; $nom_usu_form = $nom_usu_post;
    $fecha_nac_form = $fecha_nac_post; $tel_usu_form = $tel_usu_post; $correo_usu_form = $correo_usu_post_form;
    $id_dep_sel_form = $id_dep_sel_post; $id_mun_sel_form = $id_mun_sel_post; $id_barrio_sel_form = $id_barrio_sel_post;
    $direccion_usu_form = $direccion_usu_post; $id_gen_sel_form = $id_gen_sel_post; $id_est_sel_usuario_form = $id_est_sel_usuario_post;
    $id_especialidad_sel_form = $id_especialidad_sel_post; $id_rol_sel_form = $id_rol_sel_post;

    $registro_usuario_exitoso = false;
    $errores_validacion = [];

    if (empty($doc_usu_post)) $errores_validacion[] = "El documento es obligatorio.";
    elseif (!ctype_digit($doc_usu_post)) $errores_validacion[] = "El documento solo debe contener números.";
    elseif (strlen($doc_usu_post) < 7 || strlen($doc_usu_post) > 11) $errores_validacion[] = "El documento debe tener entre 7 y 11 dígitos.";
    if (empty($id_tipo_doc_sel_post)) $errores_validacion[] = "El tipo de documento es obligatorio.";
    if (empty($fecha_nac_post)) $errores_validacion[] = "La fecha de nacimiento es obligatoria.";
    else {
        $fecha_dt = DateTime::createFromFormat('Y-m-d', $fecha_nac_post);
        if (!$fecha_dt || $fecha_dt->format('Y-m-d') !== $fecha_nac_post) $errores_validacion[] = "Formato de fecha de nacimiento inválido.";
        elseif ($fecha_dt >= new DateTime('today')) $errores_validacion[] = "La fecha de nacimiento debe ser anterior a hoy.";
    }
    if (empty($nom_usu_post)) $errores_validacion[] = "El nombre completo es obligatorio.";
    elseif (!preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/u', $nom_usu_post)) $errores_validacion[] = "El nombre completo solo debe contener letras y espacios.";
    elseif (strlen($nom_usu_post) < 5 || strlen($nom_usu_post) > 100) $errores_validacion[] = "El nombre completo debe tener entre 5 y 100 caracteres.";
    if (empty($tel_usu_post)) $errores_validacion[] = "El teléfono es obligatorio.";
    elseif (!ctype_digit($tel_usu_post)) $errores_validacion[] = "El teléfono solo debe contener números.";
    elseif (strlen($tel_usu_post) < 7 || strlen($tel_usu_post) > 11) $errores_validacion[] = "El teléfono debe tener entre 7 y 11 dígitos.";
    if (empty($correo_usu_post_form)) $errores_validacion[] = "El correo electrónico es obligatorio.";
    elseif (!filter_var($correo_usu_post_form, FILTER_VALIDATE_EMAIL)) $errores_validacion[] = "El formato del correo electrónico no es válido.";
    elseif (strlen($correo_usu_post_form) > 150) $errores_validacion[] = "El correo electrónico no puede exceder los 150 caracteres.";
    if (empty($id_gen_sel_post)) $errores_validacion[] = "El género es obligatorio.";
    if (empty($id_dep_sel_post)) $errores_validacion[] = "El departamento es obligatorio.";
    if (empty($id_mun_sel_post)) $errores_validacion[] = "El municipio es obligatorio.";
    if (empty($id_barrio_sel_post)) $errores_validacion[] = "El barrio es obligatorio.";
    if (!empty($direccion_usu_post) && strlen($direccion_usu_post) > 200) $errores_validacion[] = "La dirección no puede exceder los 200 caracteres.";
    if (empty($id_rol_sel_post)) $errores_validacion[] = "El rol es obligatorio.";
    if ($id_rol_sel_post == $ID_ROL_MEDICO_PHP && (empty($id_especialidad_sel_post) || $id_especialidad_sel_post == $ID_ESPECIALIDAD_NO_APLICA_PHP) ) {
        $errores_validacion[] = "La especialidad es obligatoria y válida para el rol Médico.";
    }
    if (empty($id_est_sel_usuario_post)) $errores_validacion[] = "El estado del usuario es obligatorio.";
    if (empty($contra_nueva_post)) $errores_validacion[] = "La contraseña es obligatoria.";
    elseif (strlen($contra_nueva_post) < 8) $errores_validacion[] = "La contraseña debe tener al menos 8 caracteres.";
    elseif (!preg_match('/[A-Z]/', $contra_nueva_post) || !preg_match('/[a-z]/', $contra_nueva_post) || !preg_match('/[0-9]/', $contra_nueva_post) || !preg_match('/[\W_]/', $contra_nueva_post)) {
        $errores_validacion[] = "La contraseña debe contener al menos una mayúscula, una minúscula, un número y un símbolo.";
    }

    if (!$con) {
        $conex_db_temp = new database(); $con = $conex_db_temp->conectar();
    }

    if ($con && empty($errores_validacion)) {
        $sql_check_existencia_doc = "SELECT doc_usu FROM usuarios WHERE doc_usu = :doc";
        $stmt_check_existencia_doc = $con->prepare($sql_check_existencia_doc);
        $stmt_check_existencia_doc->execute([':doc' => $doc_usu_post]);

        $sql_check_existencia_correo = "SELECT correo_usu FROM usuarios WHERE correo_usu = :correo";
        $stmt_check_existencia_correo = $con->prepare($sql_check_existencia_correo);
        $stmt_check_existencia_correo->execute([':correo' => $correo_usu_post_form]);

        if ($stmt_check_existencia_doc->rowCount() > 0) {
            $php_error_message = "<div class='alert alert-danger'>El número de documento '" . htmlspecialchars($doc_usu_post) . "' ya se encuentra registrado en el sistema.</div>";
            $_SESSION['form_data_crear_usu'] = $_POST;
        } elseif ($stmt_check_existencia_correo->rowCount() > 0) {
            $php_error_message = "<div class='alert alert-danger'>El correo electrónico '" . htmlspecialchars($correo_usu_post_form) . "' ya se encuentra en uso.</div>";
            $_SESSION['form_data_crear_usu'] = $_POST;
        } else {
            $hashed_password = password_hash($contra_nueva_post, PASSWORD_DEFAULT);
            $id_especialidad_db = ($id_rol_sel_post != $ID_ROL_MEDICO_PHP || empty($id_especialidad_sel_post)) ? $ID_ESPECIALIDAD_NO_APLICA_PHP : $id_especialidad_sel_post;
            
            $params_insert_usuario = [
                ':doc_usu' => $doc_usu_post, 
                ':id_tipo_doc' => $id_tipo_doc_sel_post, 
                ':nom_usu' => $nom_usu_post,
                ':fecha_nac' => $fecha_nac_post,
                ':tel_usu' => $tel_usu_post,
                ':correo_usu' => $correo_usu_post_form,
                ':id_barrio' => (int)$id_barrio_sel_post,
                ':direccion_usu' => $direccion_usu_post ?: '',
                ':pass' => $hashed_password,
                ':id_gen' => (int)$id_gen_sel_post,
                ':id_est' => (int)$id_est_sel_usuario_post,
                ':id_especialidad' => (int)$id_especialidad_db,
                ':id_rol' => (int)$id_rol_sel_post
            ];
            
            $sql_insert_usuario = "INSERT INTO usuarios (doc_usu, id_tipo_doc, nom_usu, fecha_nac, tel_usu, correo_usu, id_barrio, direccion_usu, foto_usu, pass, id_gen, id_est, id_especialidad, id_rol) VALUES (:doc_usu, :id_tipo_doc, :nom_usu, :fecha_nac, :tel_usu, :correo_usu, :id_barrio, :direccion_usu, NULL, :pass, :id_gen, :id_est, :id_especialidad, :id_rol)";
            $stmt_insert_usuario = $con->prepare($sql_insert_usuario);

            try {
                if ($stmt_insert_usuario->execute($params_insert_usuario)) {
                    $registro_usuario_exitoso = true;
                    unset($_SESSION['form_data_crear_usu']);
                    
                    // Obtener nombre del rol para el correo
                    $nombre_rol_creado = 'Usuario'; // Valor por defecto
                    $stmt_rol = $con->prepare("SELECT nombre_rol FROM rol WHERE id_rol = :id_rol");
                    $stmt_rol->bindParam(':id_rol', $id_rol_sel_post, PDO::PARAM_INT);
                    $stmt_rol->execute();
                    $rol_info = $stmt_rol->fetch(PDO::FETCH_ASSOC);
                    if($rol_info) {
                        $nombre_rol_creado = $rol_info['nombre_rol'];
                    }

                    $_SESSION['correo_nuevo_usuario_info'] = [
                        'correo' => $correo_usu_post_form,
                        'nombre' => $nom_usu_post,
                        'documento' => $doc_usu_post,
                        'contrasena_plain' => $contra_nueva_post,
                        'estado_inicial' => $id_est_sel_usuario_post,
                        'nombre_rol' => $nombre_rol_creado 
                    ];
                    $_SESSION['ultimo_tipo_doc_creado'] = $id_tipo_doc_sel_post;
                    
                } else {
                    $errorInfo = $stmt_insert_usuario->errorInfo();
                    $php_error_message = "<div class='alert alert-danger'>Error SQL al Insertar Usuario: " . htmlspecialchars($errorInfo[2] ?? 'Desconocido') . "</div>";
                    $_SESSION['form_data_crear_usu'] = $_POST;
                }
            } catch (PDOException $e) {
                $php_error_message = "<div class='alert alert-danger'>PDOException al Insertar Usuario: " . htmlspecialchars($e->getMessage()) . "</div>";
                $_SESSION['form_data_crear_usu'] = $_POST;
            }
        }
    } elseif(!empty($errores_validacion)) {
        $php_error_message = "<div class='alert alert-danger'><ul>";
        foreach($errores_validacion as $error) {
            $php_error_message .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $php_error_message .= "</ul></div>";
        $_SESSION['form_data_crear_usu'] = $_POST;
    } else {
        $php_error_message = "<div class='alert alert-danger'>Error de conexión a la base de datos. No se pudo procesar el registro.</div>";
        $_SESSION['form_data_crear_usu'] = $_POST;
    }

    if ($registro_usuario_exitoso && empty($php_error_message)) {
        header('Location: correo_creacion.php');
        exit;
    }
}


if ($con) {
    try {
        $stmt = $con->query("SELECT id_tipo_doc, nom_doc FROM tipo_identificacion ORDER BY nom_doc ASC"); $tipos_doc = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $con->query("SELECT id_dep, nom_dep FROM departamento ORDER BY nom_dep ASC"); $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $con->query("SELECT id_gen, nom_gen FROM genero ORDER BY nom_gen ASC"); $generos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $con->query("SELECT id_est, nom_est FROM estado WHERE id_est IN (1, 2) ORDER BY nom_est ASC"); $estados_usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $con->query("SELECT id_espe, nom_espe FROM especialidad ORDER BY nom_espe ASC"); $especialidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $con->query("SELECT id_rol, nombre_rol FROM rol ORDER BY nombre_rol ASC"); $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($id_dep_sel_form)) {
            $stmt_mun = $con->prepare("SELECT id_mun, nom_mun FROM municipio WHERE id_dep = :id_dep ORDER BY nom_mun ASC");
            $stmt_mun->bindParam(':id_dep', $id_dep_sel_form, PDO::PARAM_STR);
            $stmt_mun->execute();
            $municipios_pre = $stmt_mun->fetchAll(PDO::FETCH_ASSOC);
        }

        if (!empty($id_mun_sel_form) && !empty($id_dep_sel_form)) {
            $stmt_check_mun_validez = $con->prepare("SELECT COUNT(*) FROM municipio WHERE id_mun = :id_mun AND id_dep = :id_dep");
            $stmt_check_mun_validez->bindParam(':id_mun', $id_mun_sel_form, PDO::PARAM_STR);
            $stmt_check_mun_validez->bindParam(':id_dep', $id_dep_sel_form, PDO::PARAM_STR);
            $stmt_check_mun_validez->execute();
            if ($stmt_check_mun_validez->fetchColumn() > 0) {
                $stmt_bar = $con->prepare("SELECT id_barrio, nom_barrio FROM barrio WHERE id_mun = :id_mun ORDER BY nom_barrio ASC");
                $stmt_bar->bindParam(':id_mun', $id_mun_sel_form, PDO::PARAM_STR);
                $stmt_bar->execute();
                $barrios_pre = $stmt_bar->fetchAll(PDO::FETCH_ASSOC);
            } else { 
                $id_mun_sel_form = ''; $id_barrio_sel_form = ''; 
            }
        } else { 
            $id_barrio_sel_form = ''; 
        }

    } catch (PDOException $e) {
        if (empty($php_error_message)) { $php_error_message = "<div class='alert alert-danger'>PDOException (carga inicial de selects): " . htmlspecialchars($e->getMessage()) . "</div>"; }
    }
} elseif (empty($php_error_message) && empty($php_info_message_correo) ) {
    $php_error_message = "<div class='alert alert-danger'>Error: No se pudo conectar a la base de datos para carga inicial de selects.</div>";
}

if ($_SERVER["REQUEST_METHOD"] != "POST" && isset($_GET['doc_creado']) && empty($php_error_message) && empty($php_info_message_correo)) {
    $doc_creado_get = trim($_GET['doc_creado']);
    $nom_creado_get = trim($_GET['nom_creado'] ?? 'El usuario');
    $tipo_doc_creado_get = trim($_GET['tipo_doc_creado'] ?? '');

    $url_afiliacion_pagina_get = 'afiliacion.php?doc_usu=' . urlencode($doc_creado_get) . '&id_tipo_doc=' . urlencode($tipo_doc_creado_get);
    $nombre_usuario_msg_afi_get = htmlspecialchars($nom_creado_get);
    
    if ($con) {
        try {
            $stmt_afiliado_get = $con->prepare("SELECT id_estado FROM afiliados WHERE doc_afiliado = :doc_usu_param_get");
            $stmt_afiliado_get->bindParam(':doc_usu_param_get', $doc_creado_get, PDO::PARAM_STR);
            $stmt_afiliado_get->execute();
            $afiliado_data_get = $stmt_afiliado_get->fetch(PDO::FETCH_ASSOC);

            if ($afiliado_data_get) { 
                if ($afiliado_data_get['id_estado'] == $ID_ESTADO_AFILIADO_ACTIVO) {
                    $php_success_message_registro = "<div class='alert alert-success'>Usuario <strong>" . $nombre_usuario_msg_afi_get . "</strong> registrado y afiliado exitosamente.</div>";
                    $php_warning_message_afiliacion_link = ''; 
                } else { 
                    $php_warning_message_afiliacion_link = "<div class='alert alert-warning mensaje-afiliacion-pendiente'>" .
                                           "El usuario <strong>" . $nombre_usuario_msg_afi_get . "</strong> no tiene afiliaciones activas. " .
                                           "<a href='" . $url_afiliacion_pagina_get . "' class='btn btn-info btn-sm ms-2'>Gestionar Afiliación</a>" .
                                           "</div>";
                }
            } else { 
                $php_warning_message_afiliacion_link = "<div class='alert alert-warning mensaje-afiliacion-pendiente'>" .
                                       "El usuario <strong>" . $nombre_usuario_msg_afi_get . "</strong> no se encuentra afiliado. " .
                                       "<a href='" . $url_afiliacion_pagina_get . "' class='btn btn-success btn-sm ms-2'>Afiliar Usuario</a>" .
                                       "</div>";
            }
        } catch (PDOException $e) {
             $php_warning_message_afiliacion_link = "<div class='alert alert-danger'>Hubo un error al verificar el estado de afiliación del usuario " . $nombre_usuario_msg_afi_get . ": " . htmlspecialchars($e->getMessage()) . ". Por favor, verifique manualmente. <a href='" . $url_afiliacion_pagina_get . "' class='btn btn-info btn-sm mt-2'>Intentar Afiliar</a></div>";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Crear Nuevo Usuario - Salud Connected</title>
    <link rel="icon" type="image/png" href="../img/loguito.png">
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1">
        <div class="container-fluid mt-4">
             <div class="form-container mx-auto" style="max-width: 900px;">
                <h3 class="text-center mb-4">Crear Nuevo Usuario</h3>
                
                <div id="mensajesServidorGlobal">
                    <?php
                        if (!empty($php_error_message)) {
                            echo $php_error_message;
                        }
                        if (!empty($php_success_message_registro) && empty($php_info_message_correo) ) {
                             echo $php_success_message_registro;
                        }
                        if (!empty($php_warning_message_afiliacion_link) && empty($php_info_message_correo)) {
                            echo $php_warning_message_afiliacion_link;
                        }
                         if (!empty($php_info_message_correo)) {
                            echo $php_info_message_correo;
                        }
                    ?>
                </div>

                <form id="formCrearUsuario" action="crear_usu.php" method="POST" novalidate>
                    <div id="paso1">
                        <p class="text-muted text-center mb-3">Paso 1 de 2: Datos Personales</p>
                        <div class="row g-3">
                             <div class="col-md-4">
                                <label for="id_tipo_doc" class="form-label">Tipo Documento (*):</label>
                                <select id="id_tipo_doc" name="id_tipo_doc" class="form-select" required tabindex="1">
                                    <option value="" <?php echo empty($id_tipo_doc_sel_form) ? 'selected' : ''; ?>>Seleccione...</option>
                                    <?php foreach ($tipos_doc as $tipo) : ?>
                                        <option value="<?php echo htmlspecialchars($tipo['id_tipo_doc']); ?>" <?php echo ($id_tipo_doc_sel_form == $tipo['id_tipo_doc']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($tipo['nom_doc']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                 <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="doc_usu" class="form-label">Documento (*):</label>
                                <input type="text" id="doc_usu" name="doc_usu" class="form-control" value="<?php echo htmlspecialchars($doc_usu_form); ?>" required tabindex="2" maxlength="11">
                                 <div class="invalid-feedback"></div>
                            </div>
                             <div class="col-md-4">
                                 <label for="fecha_nac" class="form-label">Fecha Nacimiento (*):</label>
                                <input type="date" id="fecha_nac" name="fecha_nac" class="form-control" value="<?php echo htmlspecialchars($fecha_nac_form); ?>" required tabindex="3">
                                 <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-8">
                                <label for="nom_usu" class="form-label">Nombre Completo (*):</label>
                                <input type="text" id="nom_usu" name="nom_usu" class="form-control" value="<?php echo htmlspecialchars($nom_usu_form); ?>" required tabindex="4" maxlength="100">
                                 <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="tel_usu" class="form-label">Teléfono (*):</label>
                                <input type="text" id="tel_usu" name="tel_usu" class="form-control" value="<?php echo htmlspecialchars($tel_usu_form); ?>" required tabindex="5" maxlength="11">
                                 <div class="invalid-feedback"></div>
                            </div>
                             <div class="col-md-8">
                                 <label for="correo_usu" class="form-label">Correo Electrónico (*):</label>
                                <input type="email" id="correo_usu" name="correo_usu" class="form-control" value="<?php echo htmlspecialchars($correo_usu_form); ?>" required tabindex="6" maxlength="150">
                                 <div class="invalid-feedback"></div>
                            </div>
                             <div class="col-md-4">
                                <label for="id_gen" class="form-label">Género (*):</label>
                                <select id="id_gen" name="id_gen" class="form-select" required tabindex="7">
                                    <option value="" <?php echo empty($id_gen_sel_form) ? 'selected' : ''; ?>>Seleccione...</option>
                                    <?php foreach ($generos as $gen) : ?>
                                        <option value="<?php echo htmlspecialchars($gen['id_gen']); ?>" <?php echo ($id_gen_sel_form == $gen['id_gen']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($gen['nom_gen']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                 <div class="invalid-feedback"></div>
                             </div>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="button" id="btnSiguiente" class="btn btn-secondary w-100" disabled tabindex="8">Siguiente <i class="bi bi-arrow-right-circle"></i></button>
                        </div>
                    </div>

                    <div id="paso2" style="display: none;">
                        <p class="text-muted text-center mb-3">Paso 2 de 2: Datos de Ubicación y Cuenta</p>
                        <div class="row g-3">
                           <div class="col-md-4">
                                <label for="id_dep" class="form-label">Departamento (*):</label>
                                <select id="id_dep" name="id_dep" class="form-select" required tabindex="9">
                                    <option value="" <?php echo empty($id_dep_sel_form) ? 'selected' : ''; ?>>Seleccione...</option>
                                    <?php foreach ($departamentos as $dep) : ?>
                                        <option value="<?php echo htmlspecialchars($dep['id_dep']); ?>" <?php echo ($id_dep_sel_form == $dep['id_dep']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dep['nom_dep']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                 <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4" id="div-municipio">
                                <label for="id_mun" class="form-label">Municipio (*):</label>
                                 <select id="id_mun" name="id_mun" class="form-select" required tabindex="10" <?php echo empty($id_dep_sel_form) ? 'disabled' : ''; ?> title="<?php echo empty($id_dep_sel_form) ? 'Seleccione un departamento primero' : ''; ?>">
                                    <option value="">Seleccione departamento...</option>
                                    <?php if (!empty($municipios_pre)): ?>
                                         <option value="" <?php echo ($id_mun_sel_form === '') ? 'selected' : ''; ?>>Seleccione municipio...</option>
                                        <?php foreach ($municipios_pre as $mun) : ?>
                                            <option value="<?php echo htmlspecialchars($mun['id_mun']); ?>" <?php echo ($id_mun_sel_form == $mun['id_mun']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($mun['nom_mun']); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                 <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4" id="div-barrio">
                                <label for="id_barrio" class="form-label">Barrio (*):</label>
                                <select id="id_barrio" name="id_barrio" class="form-select" required tabindex="11" <?php echo (empty($id_mun_sel_form) || empty($id_dep_sel_form)) ? 'disabled' : ''; ?> title="<?php echo (empty($id_mun_sel_form) || empty($id_dep_sel_form)) ? 'Seleccione un municipio primero' : ''; ?>">
                                     <option value="">Seleccione municipio...</option>
                                     <?php if (!empty($barrios_pre)): ?>
                                          <option value="" <?php echo ($id_barrio_sel_form === '') ? 'selected' : ''; ?>>Seleccione barrio...</option>
                                         <?php foreach ($barrios_pre as $bar) : ?>
                                            <option value="<?php echo htmlspecialchars($bar['id_barrio']); ?>" <?php echo ($id_barrio_sel_form == $bar['id_barrio']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($bar['nom_barrio']); ?></option>
                                         <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                 <div class="invalid-feedback"></div>
                            </div>
                             <div class="col-12">
                                 <label for="direccion_usu" class="form-label">Dirección (Detalle):</label>
                                <input type="text" id="direccion_usu" name="direccion_usu" class="form-control" placeholder="Ej: Calle 5 # 10-15 Apto 201" value="<?php echo htmlspecialchars($direccion_usu_form); ?>" tabindex="12" maxlength="200">
                                 <div class="invalid-feedback"></div>
                             </div>
                             <div class="col-md-4">
                                <label for="id_rol" class="form-label">Rol (*):</label>
                                <select id="id_rol" name="id_rol" class="form-select" required tabindex="13">
                                    <option value="" <?php echo empty($id_rol_sel_form) ? 'selected' : ''; ?>>Seleccione...</option>
                                    <?php foreach ($roles as $rol_item) : ?>
                                        <option value="<?php echo htmlspecialchars($rol_item['id_rol']); ?>" <?php echo ($id_rol_sel_form == $rol_item['id_rol']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($rol_item['nombre_rol']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                 <div class="invalid-feedback"></div>
                             </div>
                             <div class="col-md-4">
                                <label for="id_especialidad" class="form-label">Especialidad:</label>
                                <select id="id_especialidad" name="id_especialidad" class="form-select" tabindex="14"
                                        title="<?php echo (empty($id_rol_sel_form) || $id_rol_sel_form != $ID_ROL_MEDICO_PHP) ? 'Seleccione el rol Médico para habilitar' : ''; ?>"
                                        <?php echo (empty($id_rol_sel_form) || $id_rol_sel_form != $ID_ROL_MEDICO_PHP) ? 'disabled' : ''; ?>>
                                     <option value="">Seleccione rol...</option>
                                    <?php
                                        $especialidad_seleccionada_para_no_medico = false;
                                        foreach ($especialidades as $esp) {
                                            $selected_attr = ''; $style_attr = '';
                                            if (!empty($id_rol_sel_form) && $id_rol_sel_form != $ID_ROL_MEDICO_PHP) {
                                                if ($esp['id_espe'] == $ID_ESPECIALIDAD_NO_APLICA_PHP) {
                                                    $selected_attr = 'selected'; $especialidad_seleccionada_para_no_medico = true;
                                                } else { $style_attr = 'style="display:none;"'; }
                                            } elseif (!empty($id_rol_sel_form) && $id_rol_sel_form == $ID_ROL_MEDICO_PHP) {
                                                if ($esp['id_espe'] == $ID_ESPECIALIDAD_NO_APLICA_PHP) {
                                                    $style_attr = 'style="display:none;"';
                                                } elseif ($id_especialidad_sel_form == $esp['id_espe']) { $selected_attr = 'selected'; }
                                            } elseif ($id_especialidad_sel_form == $esp['id_espe']) { $selected_attr = 'selected';}
                                            echo "<option value=\"" . htmlspecialchars($esp['id_espe']) . "\" $selected_attr $style_attr>" . htmlspecialchars($esp['nom_espe']) . "</option>";
                                        }
                                        if (!empty($id_rol_sel_form) && $id_rol_sel_form != $ID_ROL_MEDICO_PHP && !$especialidad_seleccionada_para_no_medico) {
                                             $no_aplica_esp_array = array_filter($especialidades, function($e) use ($ID_ESPECIALIDAD_NO_APLICA_PHP) { return $e['id_espe'] == $ID_ESPECIALIDAD_NO_APLICA_PHP; });
                                             if (!empty($no_aplica_esp_array)) {
                                                 $esp_no_aplica_obj = reset($no_aplica_esp_array);
                                                  echo "<option value=\"" . htmlspecialchars($esp_no_aplica_obj['id_espe']) . "\" selected>" . htmlspecialchars($esp_no_aplica_obj['nom_espe']) . "</option>";
                                             }
                                        }
                                    ?>
                                </select>
                                 <div class="invalid-feedback"></div>
                             </div>
                             <div class="col-md-4">
                                <label for="id_est" class="form-label">Estado Inicial Usuario (*):</label>
                                <select id="id_est" name="id_est" class="form-select" required tabindex="15">
                                    <?php foreach ($estados_usuarios as $est_usu) : ?>
                                        <option value="<?php echo htmlspecialchars($est_usu['id_est']); ?>" <?php echo ($id_est_sel_usuario_form == $est_usu['id_est']) ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($est_usu['nom_est'])); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                 <div class="invalid-feedback"></div>
                             </div>
                             <div class="col-12">
                                <label for="contraseña" class="form-label">Contraseña (*):</label>
                                <input type="password" id="contraseña" name="contraseña" class="form-control" required tabindex="16" maxlength="200">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-6">
                                <button type="button" id="btnAnterior" class="btn btn-secondary w-100" tabindex="17"><i class="bi bi-arrow-left-circle"></i> Anterior</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" name="crear_usuario" id="btnCrearUsuarioSubmit" class="btn btn-primary w-100" disabled tabindex="18">Crear Usuario <i class="bi bi-check-circle"></i></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include '../../include/footer.php'; ?>
    <script src="../js/crear_usu.js"></script> 
</body>
</html>