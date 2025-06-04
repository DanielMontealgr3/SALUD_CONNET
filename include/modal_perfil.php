<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once 'conexion.php';
$usuario_data = null; $error_message = null; $departamentos = [];
$municipios_del_depto_actual = []; $barrios_del_mun_actual = []; $generos = [];

$path_prefix_for_html_src = ''; 
<<<<<<< HEAD

=======
>>>>>>> main
$default_avatar_relative_path = 'img/perfiles/default_avatar.png';
$default_avatar_display_path = $path_prefix_for_html_src . $default_avatar_relative_path;

if (isset($_SESSION['doc_usu'])) {
    $doc_usuario_actual = $_SESSION['doc_usu']; $pdo = null;
    try {
        $pdo = Database::connect();
        if ($pdo) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql_usuario = "SELECT u.*, ti.nom_doc AS tipo_documento_nombre, b.id_barrio AS id_barrio_actual, b.nom_barrio AS barrio_nombre_actual, m.id_mun AS id_municipio_actual, m.nom_mun AS municipio_nombre_actual, d.id_dep AS id_departamento_actual, d.nom_dep AS departamento_nombre_actual, g.nom_gen AS genero_nombre_actual, e.nom_est AS estado_nombre_actual, e.id_est AS id_estado_actual, esp.id_espe AS id_especialidad, esp.nom_espe AS especialidad_nombre, r.nombre_rol AS rol_nombre
                    FROM usuarios u
                    LEFT JOIN tipo_identificacion ti ON u.id_tipo_doc = ti.id_tipo_doc
                    LEFT JOIN barrio b ON u.id_barrio = b.id_barrio
                    LEFT JOIN municipio m ON b.id_mun = m.id_mun 
                    LEFT JOIN departamento d ON m.id_dep = d.id_dep
                    LEFT JOIN genero g ON u.id_gen = g.id_gen
                    LEFT JOIN estado e ON u.id_est = e.id_est
                    LEFT JOIN rol r ON u.id_rol = r.id_rol
                    LEFT JOIN especialidad esp ON u.id_especialidad = esp.id_espe
                    WHERE u.doc_usu = :doc_usu";
            $q_usuario = $pdo->prepare($sql_usuario);
            $q_usuario->bindParam(':doc_usu', $doc_usuario_actual);
            $q_usuario->execute();
            $usuario_data = $q_usuario->fetch(PDO::FETCH_ASSOC);
            if (!$usuario_data) { $error_message = "No se encontraron datos para el usuario."; } 
            else {
                $sql_departamentos = "SELECT id_dep, nom_dep FROM departamento ORDER BY nom_dep"; $departamentos = $pdo->query($sql_departamentos)->fetchAll();
                if (!empty($usuario_data['id_departamento_actual'])) {
                    $sql_municipios = "SELECT id_mun, nom_mun FROM municipio WHERE id_dep = :id_dep ORDER BY nom_mun";
                    $q_municipios = $pdo->prepare($sql_municipios); $q_municipios->bindParam(':id_dep', $usuario_data['id_departamento_actual']); $q_municipios->execute(); $municipios_del_depto_actual = $q_municipios->fetchAll();
                }
                if (!empty($usuario_data['id_municipio_actual'])) {
                     $sql_barrios = "SELECT id_barrio, nom_barrio FROM barrio WHERE id_mun = :id_mun ORDER BY nom_barrio";
                     $q_barrios = $pdo->prepare($sql_barrios); $q_barrios->bindParam(':id_mun', $usuario_data['id_municipio_actual']); $q_barrios->execute(); $barrios_del_mun_actual = $q_barrios->fetchAll();
                }
                $sql_generos = "SELECT id_gen, nom_gen FROM genero ORDER BY nom_gen"; $generos = $pdo->query($sql_generos)->fetchAll();
                $estado_display = "N/A";
                if (isset($usuario_data['id_estado_actual'])) { if ($usuario_data['id_estado_actual'] == 1) { $estado_display = "Activo"; } elseif ($usuario_data['id_estado_actual'] == 2) { $estado_display = "Inactivo"; } }
                $usuario_data['estado_a_mostrar'] = $estado_display;
            }
        } else { $error_message = "Error: No se pudo conectar a la base de datos."; }
    } catch (PDOException $e) { error_log("Error PDO modal_perfil: " . $e->getMessage()); $error_message = "Error al consultar datos: " . $e->getMessage(); }
    catch (Error $e) { error_log("Error general modal_perfil: " . $e->getMessage()); $error_message = "Error interno: " . $e->getMessage(); }
    finally { if ($pdo) { Database::disconnect(); } }
} else { $error_message = "Error: Sesión de usuario no encontrada."; }
?>
<style> #userProfileModal .modal-content { xCEEB; border-radius: .5rem; } #userProfileModal .modal-header { background-color: #005A9C; color: white; border-bottom: 1px solid #0047AB; } #userProfileModal .modal-header .btn-close-white { filter: invert(1) grayscale(100%) brightness(200%); } #userProfileModal .modal-dialog { max-width: 850px; } #userProfileModal .img-thumbnail { border: 1px solid #dee2e6; padding: 0.25rem; background-color: #fff; border-radius: 0.25rem; max-width: 100%; height: auto; } .invalid-feedback { display: block; width: 100%; margin-top: .15rem; font-size: .80em; color: #dc3545; } </style>
<div class="modal fade" id="userProfileModal" tabindex="-1" aria-labelledby="userProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"> <h5 class="modal-title" id="userProfileModalLabel">Mi Perfil</h5> <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button> </div>
            <div class="modal-body">
                <?php if ($error_message): ?> <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error_message); ?></div>
                <?php elseif ($usuario_data): 
                    $foto_src_from_db = $usuario_data['foto_usu'] ?? '';
                    $foto_a_mostrar = $default_avatar_display_path; 

                    if (!empty($foto_src_from_db)) {
                        if (filter_var($foto_src_from_db, FILTER_VALIDATE_URL)) {
                            $foto_a_mostrar = htmlspecialchars($foto_src_from_db);
                        } elseif ($foto_src_from_db === $default_avatar_relative_path) {
                             $foto_a_mostrar = $default_avatar_display_path;
                        } else {
                            $foto_a_mostrar = $path_prefix_for_html_src . htmlspecialchars($foto_src_from_db);
                        }
                    }
                ?>
                    <form id="profileFormModalActual" method="POST" enctype="multipart/form-data" novalidate>
                         <input type="hidden" name="id_est_modal_hidden" value="<?php echo htmlspecialchars($usuario_data['id_est'] ?? ''); ?>"> 
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <img src="<?php echo $foto_a_mostrar; ?>" alt="Foto" id="imagePreviewModal" class="img-thumbnail mb-3" style="width: 150px; height: 150px; object-fit: cover;" onerror="this.onerror=null; this.src='<?php echo $default_avatar_display_path; ?>';">
                                <div class="mb-3"> <label for="foto_usu_modal" class="form-label">Cambiar Foto</label> <input class="form-control form-control-sm" type="file" id="foto_usu_modal" name="foto_usu_modal" accept="image/*"> <div class="invalid-feedback"></div> </div>
                                <hr>
                                <div class="mb-3"><label class="form-label">Documento:</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario_data['doc_usu']); ?>" disabled></div>
                                <div class="mb-3"><label class="form-label">Tipo Doc.:</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario_data['tipo_documento_nombre'] ?? 'N/A'); ?>" disabled></div>
                                <div class="mb-3"><label class="form-label">Rol:</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario_data['rol_nombre'] ?? 'N/A'); ?>" disabled></div>
                                <?php if (isset($usuario_data['id_especialidad']) && $usuario_data['id_especialidad'] != 46 && !empty($usuario_data['especialidad_nombre'])): ?> <div class="mb-3"><label class="form-label">Especialidad:</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario_data['especialidad_nombre']); ?>" disabled></div> <?php endif; ?>
                                <div class="mb-3"><label class="form-label">Estado:</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario_data['estado_a_mostrar']); ?>" disabled></div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3"> <label for="nom_usu_modal" class="form-label">Nombre Completo</label> <input type="text" class="form-control" id="nom_usu_modal" name="nom_usu_modal" value="<?php echo htmlspecialchars($usuario_data['nom_usu']); ?>" required> <div class="invalid-feedback"></div> </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"> <label for="correo_usu_modal" class="form-label">Correo Electrónico</label> <input type="email" class="form-control" id="correo_usu_modal" name="correo_usu_modal" value="<?php echo htmlspecialchars($usuario_data['correo_usu']); ?>" required> <div class="invalid-feedback"></div> </div>
                                    <div class="col-md-6 mb-3"> <label for="tel_usu_modal" class="form-label">Teléfono</label> <input type="tel" class="form-control" id="tel_usu_modal" name="tel_usu_modal" value="<?php echo htmlspecialchars($usuario_data['tel_usu'] ?? ''); ?>"> <div class="invalid-feedback"></div> </div>
                                </div>
                                <div class="row">
                                     <div class="col-md-6 mb-3"> <label for="fecha_nac_modal" class="form-label">Fecha Nacimiento</label> <input type="date" class="form-control" id="fecha_nac_modal" name="fecha_nac_modal" value="<?php echo htmlspecialchars($usuario_data['fecha_nac']); ?>" required> <div class="invalid-feedback"></div> </div>
                                    <div class="col-md-6 mb-3"> <label for="id_gen_modal" class="form-label">Género</label> <select class="form-select" id="id_gen_modal" name="id_gen_modal" required> <option value="">Seleccione...</option> <?php foreach ($generos as $genero): ?> <option value="<?php echo $genero['id_gen']; ?>" <?php echo ($usuario_data['id_gen'] == $genero['id_gen']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($genero['nom_gen']); ?></option> <?php endforeach; ?> </select> <div class="invalid-feedback"></div> </div>
                                </div>
                                <div class="mb-3"> <label for="direccion_usu_modal" class="form-label">Dirección Residencia</label> <input type="text" class="form-control" id="direccion_usu_modal" name="direccion_usu_modal" value="<?php echo htmlspecialchars($usuario_data['direccion_usu'] ?? ''); ?>"> <div class="invalid-feedback"></div> </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"> <label for="id_departamento_modal" class="form-label">Departamento</label> <select class="form-select" id="id_departamento_modal" name="id_departamento_modal" required> <option value="">Seleccione...</option> <?php foreach ($departamentos as $depto): ?> <option value="<?php echo $depto['id_dep']; ?>" <?php echo (isset($usuario_data['id_departamento_actual']) && $usuario_data['id_departamento_actual'] == $depto['id_dep']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($depto['nom_dep']); ?></option> <?php endforeach; ?> </select> <div class="invalid-feedback"></div> </div>
                                    <div class="col-md-6 mb-3"> <label for="id_municipio_modal" class="form-label">Municipio</label> <select class="form-select" id="id_municipio_modal" name="id_municipio_modal" required <?php echo empty($municipios_del_depto_actual) ? 'disabled' : '';?>> <option value="">Seleccione...</option> <?php foreach ($municipios_del_depto_actual as $mun): ?> <option value="<?php echo $mun['id_mun']; ?>" <?php echo (isset($usuario_data['id_municipio_actual']) && $usuario_data['id_municipio_actual'] == $mun['id_mun']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($mun['nom_mun']); ?></option> <?php endforeach; ?> </select> <div class="invalid-feedback"></div> </div>
                                </div>
                                <div class="mb-3"> <label for="id_barrio_modal" class="form-label">Barrio</label> <select class="form-select" id="id_barrio_modal" name="id_barrio_modal" required <?php echo empty($barrios_del_mun_actual) ? 'disabled' : '';?>> <option value="">Seleccione...</option> <?php foreach ($barrios_del_mun_actual as $barrio): ?> <option value="<?php echo $barrio['id_barrio']; ?>" <?php echo (isset($usuario_data['id_barrio_actual']) && $usuario_data['id_barrio_actual'] == $barrio['id_barrio']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($barrio['nom_barrio']); ?></option> <?php endforeach; ?> </select> <div class="invalid-feedback"></div> </div>
                                <hr> <h6>Cambiar Contraseña (opcional)</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3"> <label for="pass_modal" class="form-label">Nueva Contraseña</label> <input type="password" class="form-control" id="pass_modal" name="pass_modal" placeholder="Dejar en blanco para no cambiar"> <div class="invalid-feedback"></div> </div>
                                    <div class="col-md-6 mb-3"> <label for="confirm_pass_modal" class="form-label">Confirmar</label> <input type="password" class="form-control" id="confirm_pass_modal" name="confirm_pass_modal"> <div class="invalid-feedback"></div> </div>
                                </div>
                            </div>
                        </div> <div id="modalUpdateMessage" class="mt-3"></div>
                    </form>
                <?php else: ?> <div class="alert alert-warning" role="alert">No se pudieron cargar los datos del perfil.</div> <?php endif; ?>
            </div>
            <div class="modal-footer"> <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button> <?php if ($usuario_data && !$error_message): ?> <button type="submit" class="btn btn-primary" form="profileFormModalActual" id="saveProfileChangesButton" disabled>Guardar Cambios</button> <?php endif; ?> </div>
        </div>
    </div>
</div>