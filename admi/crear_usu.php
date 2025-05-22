<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once '../include/conexion.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 || !isset($_SESSION['nombre_usuario'])) {
    header('Location: ../inicio_sesion.php');
    exit;
}

$conex_db = new database();
$con = $conex_db->conectar();

$ID_ROL_MEDICO_PHP = 4;
$ID_ESPECIALIDAD_NO_APLICA_PHP = 46;

$tipos_doc = []; $departamentos = []; $municipios_pre = []; $barrios_pre = [];
$generos = []; $estados_usuarios = []; $especialidades = []; $roles = [];

$doc_usu_form = ''; $id_tipo_doc_sel_form = ''; $nom_usu_form = ''; $fecha_nac_form = '';
$tel_usu_form = ''; $correo_usu_form = ''; $id_dep_sel_form = ''; $id_mun_sel_form = '';
$id_barrio_sel_form = ''; $direccion_usu_form = ''; $id_gen_sel_form = ''; $id_est_sel_usuario_form = 1;
$id_especialidad_sel_form = ''; $id_rol_sel_form = '';

$php_error_message = '';
$php_success_message_registro = '';
$php_warning_message_afiliacion_link = '';

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

    $doc_usu_form = $doc_usu_post;
    $id_tipo_doc_sel_form = $id_tipo_doc_sel_post;
    $nom_usu_form = $nom_usu_post;
    $fecha_nac_form = $fecha_nac_post;
    $tel_usu_form = $tel_usu_post;
    $correo_usu_form = $correo_usu_post_form;
    $id_dep_sel_form = $id_dep_sel_post;
    $id_mun_sel_form = $id_mun_sel_post;
    $id_barrio_sel_form = $id_barrio_sel_post;
    $direccion_usu_form = $direccion_usu_post;
    $id_gen_sel_form = $id_gen_sel_post;
    $id_est_sel_usuario_form = $id_est_sel_usuario_post;
    $id_especialidad_sel_form = $id_especialidad_sel_post;
    $id_rol_sel_form = $id_rol_sel_post;

    $registro_usuario_exitoso = false;
    $errores_validacion = []; 

    if (empty($doc_usu_post)) $errores_validacion[] = "El documento es obligatorio.";
    if (empty($id_tipo_doc_sel_post)) $errores_validacion[] = "El tipo de documento es obligatorio.";
    if (empty($nom_usu_post)) $errores_validacion[] = "El nombre completo es obligatorio.";
    if (empty($correo_usu_post_form)) {
        $errores_validacion[] = "El correo electrónico es obligatorio.";
    } elseif (!filter_var($correo_usu_post_form, FILTER_VALIDATE_EMAIL)) {
        $errores_validacion[] = "El formato del correo electrónico no es válido.";
    }
    if (empty($contra_nueva_post)) $errores_validacion[] = "La contraseña es obligatoria.";


    if (!$con) { 
        $conex_db_temp = new database();
        $con = $conex_db_temp->conectar();
    }

    if ($con && empty($errores_validacion)) {
        $sql_check_existencia_doc = "SELECT doc_usu FROM usuarios WHERE doc_usu = :doc AND id_tipo_doc = :tipo_doc";
        $stmt_check_existencia_doc = $con->prepare($sql_check_existencia_doc);
        $stmt_check_existencia_doc->execute([':doc' => $doc_usu_post, ':tipo_doc' => $id_tipo_doc_sel_post]);

        $sql_check_existencia_correo = "SELECT doc_usu FROM usuarios WHERE correo_usu = :correo";
        $stmt_check_existencia_correo = $con->prepare($sql_check_existencia_correo);
        $stmt_check_existencia_correo->execute([':correo' => $correo_usu_post_form]);

        if ($stmt_check_existencia_doc->rowCount() > 0) {
            $php_error_message = "<div class='alert alert-danger error-servidor-duplicidad'>El documento que intenta ingresar ya está inscrito con el tipo de documento seleccionado.</div>";
            $_SESSION['form_data_crear_usu'] = $_POST; 
        } elseif ($stmt_check_existencia_correo->rowCount() > 0) {
            $php_error_message = "<div class='alert alert-danger error-servidor-duplicidad'>El correo que intenta ingresar ya se encuentra en uso.</div>";
            $_SESSION['form_data_crear_usu'] = $_POST;
        } else {
            $hashed_password = password_hash($contra_nueva_post, PASSWORD_DEFAULT); 
            $id_especialidad_db = ($id_rol_sel_post != $ID_ROL_MEDICO_PHP || empty($id_especialidad_sel_post)) ? $ID_ESPECIALIDAD_NO_APLICA_PHP : $id_especialidad_sel_post;
            
            $sql_insert_usuario = "INSERT INTO usuarios (doc_usu, id_tipo_doc, nom_usu, fecha_nac, tel_usu, correo_usu, id_barrio, direccion_usu, pass, id_gen, id_est, id_especialidad, id_rol) VALUES (:doc_usu, :id_tipo_doc, :nom_usu, :fecha_nac, :tel_usu, :correo_usu, :id_barrio, :direccion_usu, :pass, :id_gen, :id_est_usuario, :id_especialidad, :id_rol)";
            $stmt_insert_usuario = $con->prepare($sql_insert_usuario);
            $params_insert_usuario = [
                ':doc_usu' => $doc_usu_post, ':id_tipo_doc' => $id_tipo_doc_sel_post, ':nom_usu' => $nom_usu_post,
                ':fecha_nac' => $fecha_nac_post ?: null, ':tel_usu' => $tel_usu_post ?: null, ':correo_usu' => $correo_usu_post_form,
                ':id_barrio' => $id_barrio_sel_post ?: null, ':direccion_usu' => $direccion_usu_post ?: null, ':pass' => $hashed_password,
                ':id_gen' => $id_gen_sel_post ?: null, ':id_est_usuario' => $id_est_sel_usuario_post ?: 1, 
                ':id_especialidad' => $id_especialidad_db, ':id_rol' => $id_rol_sel_post ?: null
            ];
            try {
                if ($stmt_insert_usuario->execute($params_insert_usuario)) {
                    $registro_usuario_exitoso = true;
                    unset($_SESSION['form_data_crear_usu']); 
                    $nombre_usuario_msg_reg = !empty($nom_usu_post) ? htmlspecialchars($nom_usu_post) : "El usuario";
                    $php_success_message_registro = "<div class='alert alert-success'>Usuario <strong>" . $nombre_usuario_msg_reg . "</strong> registrado exitosamente.</div>";

                } else { 
                    $errorInfo = $stmt_insert_usuario->errorInfo();
                    $php_error_message = "<div class='alert alert-danger'>Error SQL al Insertar Usuario: " . ($errorInfo[2] ?? 'Desconocido') . "</div>";
                    $_SESSION['form_data_crear_usu'] = $_POST;
                }
            } catch (PDOException $e) {
                $php_error_message = "<div class='alert alert-danger'>PDOException al Insertar Usuario: " . $e->getMessage() . "</div>";
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
    }
     else { 
        $php_error_message = "<div class='alert alert-danger'>Error de conexión a la base de datos. No se pudo procesar el registro.</div>";
        $_SESSION['form_data_crear_usu'] = $_POST;
    }
    if ($registro_usuario_exitoso && empty($php_error_message)) {
        try {
            $stmt_afiliado = $con->prepare("SELECT id_estado FROM afiliados WHERE doc_afiliado = :doc_usu_param");
            $stmt_afiliado->bindParam(':doc_usu_param', $doc_usu_post, PDO::PARAM_STR);
            $stmt_afiliado->execute();
            $afiliado_data = $stmt_afiliado->fetch(PDO::FETCH_ASSOC);

            $nombre_usuario_msg_afi = !empty($nom_usu_post) ? htmlspecialchars($nom_usu_post) : "El usuario";
            $url_afiliacion_pagina = 'afiliacion.php?doc_usu=' . urlencode($doc_usu_post) . '&id_tipo_doc=' . urlencode($id_tipo_doc_sel_post ?? '');
            
            if ($afiliado_data && $afiliado_data['id_estado'] == 1) { 
                $nom_tipo_doc_msg_completo = "Documento";
                 if (!empty($id_tipo_doc_sel_post)) { 
                    $stmt_tipo_doc_lookup_c = $con->prepare("SELECT nom_doc FROM tipo_identificacion WHERE id_tipo_doc = :id_tipo_doc");
                    $stmt_tipo_doc_lookup_c->bindParam(':id_tipo_doc', $id_tipo_doc_sel_post, PDO::PARAM_INT);
                    $stmt_tipo_doc_lookup_c->execute();
                    $tipo_doc_info_c = $stmt_tipo_doc_lookup_c->fetch(PDO::FETCH_ASSOC);
                    if ($tipo_doc_info_c) {
                        $nom_tipo_doc_msg_completo = $tipo_doc_info_c['nom_doc'];
                    }
                }
                $php_success_message_registro = "<div class='alert alert-success'>Usuario <strong>" . $nombre_usuario_msg_afi . "</strong> (" . htmlspecialchars($nom_tipo_doc_msg_completo) . ": " . htmlspecialchars($doc_usu_post) . ") creado y afiliado exitosamente.</div>";
                $php_warning_message_afiliacion_link = ''; 
                $doc_usu_form = ''; $id_tipo_doc_sel_form = ''; $nom_usu_form = ''; $fecha_nac_form = ''; $tel_usu_form = ''; $correo_usu_form = ''; $id_dep_sel_form = ''; $id_mun_sel_form = ''; $id_barrio_sel_form = ''; $direccion_usu_form = ''; $id_gen_sel_form = ''; $id_est_sel_usuario_form = 1; $id_especialidad_sel_form = ''; $id_rol_sel_form = ''; $municipios_pre = []; $barrios_pre = [];

            } else { 
                $php_warning_message_afiliacion_link = "<div class='alert alert-warning mensaje-afiliacion-pendiente'>" .
                                       "El usuario <strong>" . $nombre_usuario_msg_afi . "</strong> no está afiliado activamente." .
                                       "<a href='" . $url_afiliacion_pagina . "' class='btn btn-success btn-sm ms-2'>Afiliar Usuario</a>" .
                                       "</div>";
            }
        } catch (PDOException $e) { 
             $php_warning_message_afiliacion_link = "<div class='alert alert-danger'>Se registró el usuario, pero hubo un error al verificar su estado de afiliación: " . $e->getMessage() . ". Por favor, verifique manualmente. <a href='" . $url_afiliacion_pagina . "' class='btn btn-info btn-sm mt-2'>Intentar Afiliar</a></div>";
        }
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
            } else { $id_mun_sel_form = ''; $id_barrio_sel_form = ''; } 
        } else { $id_barrio_sel_form = ''; }

    } catch (PDOException $e) {
        if (empty($php_error_message)) { $php_error_message = "<div class='alert alert-danger'>PDOException (carga inicial de selects): " . $e->getMessage() . "</div>"; }
    }
} elseif (empty($php_error_message) && empty($php_warning_message_afiliacion_link) && empty($php_success_message_registro)) {

    $php_error_message = "<div class='alert alert-danger'>Error: No se pudo conectar a la base de datos para carga inicial de selects.</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Crear Nuevo Usuario</title>
    <link rel="icon" type="image/png" href="../img/loguito.png">
</head>
<body>
    <?php include '../include/menu.php'; ?>
    <main id="contenido-principal">
        <div class="container-fluid mt-3">
             <div class="form-container mx-auto">
                <h3 class="text-center">Crear Nuevo Usuario</h3>
                <div id="mensajesServidorGlobal">
                    <?php
                        if (!empty($php_error_message)) {
                            echo $php_error_message;
                        } else {
                            if (!empty($php_success_message_registro)) {
                                echo $php_success_message_registro;
                            }
                            if (!empty($php_warning_message_afiliacion_link)) {
                                echo $php_warning_message_afiliacion_link;
                            }
                        }
                    ?>
                </div>
                <form id="formCrearUsuario" action="crear_usu.php" method="POST" novalidate>
                    <div id="paso1">
                        <p class="text-muted text-center mb-2">Paso 1 de 2: Datos Personales</p>
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
                                <input type="text" id="doc_usu" name="doc_usu" class="form-control" value="<?php echo htmlspecialchars($doc_usu_form); ?>" required tabindex="2">
                                 <div class="invalid-feedback" id="error-doc_usu"></div>
                            </div>
                             <div class="col-md-4">
                                 <label for="fecha_nac" class="form-label">Fecha Nacimiento (*):</label>
                                <input type="date" id="fecha_nac" name="fecha_nac" class="form-control" value="<?php echo htmlspecialchars($fecha_nac_form); ?>" required tabindex="3">
                                 <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-8">
                                <label for="nom_usu" class="form-label">Nombre Completo (*):</label>
                                <input type="text" id="nom_usu" name="nom_usu" class="form-control" value="<?php echo htmlspecialchars($nom_usu_form); ?>" required tabindex="4">
                                 <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="tel_usu" class="form-label">Teléfono:</label>
                                <input type="text" id="tel_usu" name="tel_usu" class="form-control" value="<?php echo htmlspecialchars($tel_usu_form); ?>" tabindex="5">
                                 <div class="invalid-feedback"></div>
                            </div>
                             <div class="col-md-8">
                                 <label for="correo_usu" class="form-label">Correo Electrónico (*):</label>
                                <input type="email" id="correo_usu" name="correo_usu" class="form-control" value="<?php echo htmlspecialchars($correo_usu_form); ?>" required tabindex="6">
                                 <div class="invalid-feedback" id="error-correo_usu"></div>
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
                        <div class="col-12 mt-3">
                            <button type="button" id="btnSiguiente" class="btn btn-secondary w-100" disabled tabindex="8">Siguiente <i class="bi bi-arrow-right-circle"></i></button>
                        </div>
                    </div>
                    <div id="paso2" style="display: none;">
                        <p class="text-muted text-center mb-2">Paso 2 de 2: Datos de Ubicación y Cuenta</p>
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
                                <input type="text" id="direccion_usu" name="direccion_usu" class="form-control" placeholder="Ej: Calle 5 # 10-15 Apto 201" value="<?php echo htmlspecialchars($direccion_usu_form); ?>" tabindex="12">
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
                                        foreach ($especialidades as $esp) {
                                            $selected_attr = ($id_especialidad_sel_form == $esp['id_espe']) ? 'selected' : '';
                                            $style_attr = '';
                                             if (!empty($id_rol_sel_form) && $id_rol_sel_form != $ID_ROL_MEDICO_PHP && $esp['id_espe'] != $ID_ESPECIALIDAD_NO_APLICA_PHP) { $style_attr = 'style="display:none;"'; }
                                             elseif (!empty($id_rol_sel_form) && $id_rol_sel_form == $ID_ROL_MEDICO_PHP && $esp['id_espe'] == $ID_ESPECIALIDAD_NO_APLICA_PHP) { $style_attr = 'style="display:none;"'; }
                                             if (!empty($id_rol_sel_form) && $id_rol_sel_form != $ID_ROL_MEDICO_PHP && $esp['id_espe'] == $ID_ESPECIALIDAD_NO_APLICA_PHP) { $selected_attr = 'selected'; }
                                            echo "<option value=\"" . htmlspecialchars($esp['id_espe']) . "\" $selected_attr $style_attr>" . htmlspecialchars($esp['nom_espe']) . "</option>";
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
                                <input type="password" id="contraseña" name="contraseña" class="form-control" required tabindex="16">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row mt-3">
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
    <?php include '../include/footer.php'; ?>
    <script src="../js/crear_usu.js"></script> 
</body>
</html>