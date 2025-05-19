<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../include/conexion.php';

$usuario_data = null; $error_message = null; $departamentos = [];
$municipios_del_depto_actual = []; $barrios_del_mun_actual = [];
$generos = []; $estados = []; $roles = []; $especialidades = [];
$doc_usuario_a_editar = null;
if (isset($_GET['doc_usu_editar'])) { $doc_usuario_a_editar = $_GET['doc_usu_editar']; }

$default_avatar_path_for_modal = '../img/perfiles/foto_por_defecto.webp'; 
$default_avatar_relative_path_in_db = 'img/usuarios/default_avatar.png';

if (empty($doc_usuario_a_editar)) {
    $error_message = "Error: Documento de usuario a editar no proporcionado.";
} else {
    $pdo = null;
    try {
        $pdo = Database::connect();
        if ($pdo) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql_usuario = "SELECT u.*, ti.nom_doc AS tipo_documento_nombre, b.id_barrio AS id_barrio_actual, b.nom_barrio AS barrio_nombre_actual, m.id_mun AS id_municipio_actual, m.nom_mun AS municipio_nombre_actual, d.id_dep AS id_departamento_actual, d.nom_dep AS departamento_nombre_actual, g.nom_gen AS genero_nombre_actual, e.nom_est AS estado_nombre_actual, esp.id_espe AS id_especialidad, esp.nom_espe AS especialidad_nombre_actual, r.nombre_rol AS rol_nombre_actual
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
            $q_usuario->bindParam(':doc_usu', $doc_usuario_a_editar);
            $q_usuario->execute();
            $usuario_data = $q_usuario->fetch(PDO::FETCH_ASSOC);
            if (!$usuario_data) {
                $error_message = "No se encontraron datos para el usuario: " . htmlspecialchars($doc_usuario_a_editar);
            } else {
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
                $sql_estados_todos = "SELECT id_est, nom_est FROM estado ORDER BY nom_est";
                $todos_los_estados = $pdo->query($sql_estados_todos)->fetchAll();
                $estados = array_filter($todos_los_estados, function($estado_item) {
                    return in_array($estado_item['id_est'], [1, 2]);
                });

                $sql_roles = "SELECT id_rol, nombre_rol FROM rol ORDER BY nombre_rol"; $roles = $pdo->query($sql_roles)->fetchAll();
                $sql_especialidades = "SELECT id_espe, nom_espe FROM especialidad ORDER BY nom_espe"; $especialidades = $pdo->query($sql_especialidades)->fetchAll();
            }
        } else { $error_message = "Error: No se pudo conectar a la base de datos."; }
    } catch (PDOException $e) { $error_message = "Error al consultar datos: " . $e->getMessage(); error_log("PDOException en modal_editar_usuario: " . $e->getMessage()); }
    finally { if ($pdo) { Database::disconnect(); } }
}
?>
<style> #editUserModal .modal-content { background-color: #f0f2f5; border: 3px solid #87CEEB; border-radius: .5rem; } #editUserModal .modal-header { background-color: #005A9C; color: white; border-bottom: 1px solid #0047AB; } #editUserModal .modal-header .btn-close-white { filter: invert(1) grayscale(100%) brightness(200%); } #editUserModal .modal-dialog { max-width: 850px; } #editUserModal .img-thumbnail { border: 1px solid #dee2e6; padding: 0.25rem; background-color: #fff; border-radius: 0.25rem; max-width: 100%; height: auto; } .invalid-feedback { display: block; width: 100%; margin-top: .15rem; font-size: .80em; color: #dc3545; } #div_especialidad_edit { display: none; } </style>
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"> <h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5> <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button> </div>
            <div class="modal-body">
                <?php if ($error_message): ?> <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error_message); ?></div>
                <?php elseif ($usuario_data): 
                    $foto_src_from_db = $usuario_data['foto_usu'] ?? '';
                    $foto_a_mostrar = $default_avatar_path_for_modal; 

                    if (!empty($foto_src_from_db)) {
                        if (filter_var($foto_src_from_db, FILTER_VALIDATE_URL)) {
                            $foto_a_mostrar = htmlspecialchars($foto_src_from_db);
                        } elseif ($foto_src_from_db === $default_avatar_relative_path_in_db) {
                             $foto_a_mostrar = $default_avatar_path_for_modal; 
                        } else {
                            $foto_a_mostrar = '../' . htmlspecialchars($foto_src_from_db); 
                        }
                    }
                ?>
                    <form id="editUserFormAdmin" method="POST" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="doc_usu_editar" value="<?php echo htmlspecialchars($doc_usuario_a_editar); ?>">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <img src="<?php echo $foto_a_mostrar; ?>" alt="Foto del Usuario" id="imagePreviewEditUser" class="img-thumbnail mb-3" style="width: 150px; height: 150px; object-fit: cover;" onerror="this.onerror=null; this.src='<?php echo $default_avatar_path_for_modal; ?>';">
                                <hr>
                                <div class="mb-3"><label class="form-label">Documento:</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario_data['doc_usu']); ?>" disabled></div>
                                <div class="mb-3"><label class="form-label">Tipo Doc.:</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($usuario_data['tipo_documento_nombre'] ?? 'N/A'); ?>" disabled></div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3"> <label for="nom_usu_edit" class="form-label">Nombre Completo (*)</label> <input type="text" class="form-control" id="nom_usu_edit" name="nom_usu_edit" value="<?php echo htmlspecialchars($usuario_data['nom_usu']); ?>" required> <div class="invalid-feedback"></div> </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"> <label for="correo_usu_edit" class="form-label">Correo (*)</label> <input type="email" class="form-control" id="correo_usu_edit" name="correo_usu_edit" value="<?php echo htmlspecialchars($usuario_data['correo_usu']); ?>" required> <div class="invalid-feedback"></div> </div>
                                    <div class="col-md-6 mb-3"> <label for="tel_usu_edit" class="form-label">Teléfono</label> <input type="tel" class="form-control" id="tel_usu_edit" name="tel_usu_edit" value="<?php echo htmlspecialchars($usuario_data['tel_usu'] ?? ''); ?>"> <div class="invalid-feedback"></div> </div>
                                </div>
                                <div class="row">
                                     <div class="col-md-6 mb-3"> <label for="fecha_nac_edit" class="form-label">Fecha Nacimiento (*)</label> <input type="date" class="form-control" id="fecha_nac_edit" name="fecha_nac_edit" value="<?php echo htmlspecialchars($usuario_data['fecha_nac']); ?>" required> <div class="invalid-feedback"></div> </div>
                                    <div class="col-md-6 mb-3"> <label for="id_gen_edit" class="form-label">Género (*)</label> <select class="form-select" id="id_gen_edit" name="id_gen_edit" required> <option value="">Seleccione...</option> <?php foreach ($generos as $genero): ?> <option value="<?php echo $genero['id_gen']; ?>" <?php echo ($usuario_data['id_gen'] == $genero['id_gen']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($genero['nom_gen']); ?></option> <?php endforeach; ?> </select> <div class="invalid-feedback"></div> </div>
                                </div>
                                <div class="mb-3"> <label for="direccion_usu_edit" class="form-label">Dirección</label> <input type="text" class="form-control" id="direccion_usu_edit" name="direccion_usu_edit" value="<?php echo htmlspecialchars($usuario_data['direccion_usu'] ?? ''); ?>"> <div class="invalid-feedback"></div> </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"> <label for="id_departamento_edit" class="form-label">Departamento (*)</label> <select class="form-select" id="id_departamento_edit" name="id_departamento_edit" required> <option value="">Seleccione...</option> <?php foreach ($departamentos as $depto): ?> <option value="<?php echo $depto['id_dep']; ?>" <?php echo (isset($usuario_data['id_departamento_actual']) && $usuario_data['id_departamento_actual'] == $depto['id_dep']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($depto['nom_dep']); ?></option> <?php endforeach; ?> </select> <div class="invalid-feedback"></div> </div>
                                    <div class="col-md-6 mb-3"> <label for="id_municipio_edit" class="form-label">Municipio (*)</label> <select class="form-select" id="id_municipio_edit" name="id_municipio_edit" required <?php echo empty($municipios_del_depto_actual) ? 'disabled' : '';?>> <option value="">Seleccione...</option> <?php foreach ($municipios_del_depto_actual as $mun): ?> <option value="<?php echo $mun['id_mun']; ?>" <?php echo (isset($usuario_data['id_municipio_actual']) && $usuario_data['id_municipio_actual'] == $mun['id_mun']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($mun['nom_mun']); ?></option> <?php endforeach; ?> </select> <div class="invalid-feedback"></div> </div>
                                </div>
                                <div class="mb-3"> <label for="id_barrio_edit" class="form-label">Barrio (*)</label> <select class="form-select" id="id_barrio_edit" name="id_barrio_edit" required <?php echo empty($barrios_del_mun_actual) ? 'disabled' : '';?>> <option value="">Seleccione...</option> <?php foreach ($barrios_del_mun_actual as $barrio): ?> <option value="<?php echo $barrio['id_barrio']; ?>" <?php echo ($usuario_data['id_barrio_actual'] == $barrio['id_barrio']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($barrio['nom_barrio']); ?></option> <?php endforeach; ?> </select> <div class="invalid-feedback"></div> </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"> <label for="id_est_edit" class="form-label">Estado (*)</label> <select class="form-select" id="id_est_edit" name="id_est_edit" required> <option value="">Seleccione...</option> <?php foreach ($estados as $estado): ?> <option value="<?php echo $estado['id_est']; ?>" <?php echo ($usuario_data['id_est'] == $estado['id_est']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($estado['nom_est']); ?></option> <?php endforeach; ?> </select> <div class="invalid-feedback"></div> </div>
                                    <div class="col-md-6 mb-3"> <label for="id_rol_edit" class="form-label">Rol (*)</label> <select class="form-select" id="id_rol_edit" name="id_rol_edit" required> <option value="">Seleccione...</option> <?php foreach ($roles as $rol_item): ?> <option value="<?php echo $rol_item['id_rol']; ?>" <?php echo ($usuario_data['id_rol'] == $rol_item['id_rol']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($rol_item['nombre_rol']); ?></option> <?php endforeach; ?> </select> <div class="invalid-feedback"></div> </div>
                                </div>
                                <div class="mb-3" id="div_especialidad_edit" style="<?php echo ($usuario_data['id_rol'] ?? '') == 4 ? '' : 'display: none;'; ?>">
                                    <label for="id_especialidad_edit" class="form-label">Especialidad (* si Rol es Médico)</label>
                                    <select class="form-select" id="id_especialidad_edit" name="id_especialidad_edit">
                                        <option value="">Seleccione Especialidad...</option>
                                        <?php foreach ($especialidades as $espe): ?>
                                            <option value="<?php echo $espe['id_espe']; ?>" <?php echo (isset($usuario_data['id_especialidad']) && $usuario_data['id_especialidad'] == $espe['id_espe']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($espe['nom_espe']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div> <div id="modalEditUserUpdateMessage" class="mt-3"></div>
                    </form>
                <?php else: ?> <div class="alert alert-warning" role="alert">No se pudieron cargar los datos del usuario.</div> <?php endif; ?>
            </div>
            <div class="modal-footer"> <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button> <?php if ($usuario_data && !$error_message): ?> <button type="submit" class="btn btn-primary" form="editUserFormAdmin" id="saveUserChangesAdminButton" disabled>Guardar Cambios</button> <?php endif; ?> </div>
        </div>
    </div>
</div>