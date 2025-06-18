<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../include/conexion.php';

$usuario_data = null; $error_message = null; $departamentos = [];
$municipios_del_depto_actual = []; $barrios_del_mun_actual = [];
$generos = []; $estados = []; $roles = []; $especialidades = [];
$doc_usuario_a_editar = $_GET['doc_usu_editar'] ?? null;
$default_avatar_path = '../../img/perfiles/foto_por_defecto.webp'; 

if (empty($doc_usuario_a_editar)) {
    $error_message = "Documento de usuario no proporcionado.";
} else {
    $pdo = null;
    try {
        $pdo = Database::connect();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql_usuario = "SELECT u.*, ti.nom_doc AS tipo_documento_nombre, r.nombre_rol, e.nom_est as estado_nombre, b.id_barrio AS id_barrio_actual, m.id_mun AS id_municipio_actual, d.id_dep AS id_departamento_actual FROM usuarios u LEFT JOIN tipo_identificacion ti ON u.id_tipo_doc = ti.id_tipo_doc LEFT JOIN rol r ON u.id_rol = r.id_rol LEFT JOIN estado e ON u.id_est = e.id_est LEFT JOIN barrio b ON u.id_barrio = b.id_barrio LEFT JOIN municipio m ON b.id_mun = m.id_mun LEFT JOIN departamento d ON m.id_dep = d.id_dep WHERE u.doc_usu = :doc_usu";
        $q_usuario = $pdo->prepare($sql_usuario);
        $q_usuario->bindParam(':doc_usu', $doc_usuario_a_editar, PDO::PARAM_STR);
        $q_usuario->execute();
        $usuario_data = $q_usuario->fetch(PDO::FETCH_ASSOC);

        if (!$usuario_data) {
            $error_message = "No se encontraron datos para el usuario.";
        } else {
            $departamentos = $pdo->query("SELECT id_dep, nom_dep FROM departamento ORDER BY nom_dep")->fetchAll(PDO::FETCH_ASSOC);
            $generos = $pdo->query("SELECT id_gen, nom_gen FROM genero ORDER BY nom_gen")->fetchAll(PDO::FETCH_ASSOC);
            $estados = $pdo->query("SELECT id_est, nom_est FROM estado WHERE id_est IN (1, 2) ORDER BY nom_est")->fetchAll(PDO::FETCH_ASSOC);
            $roles = $pdo->query("SELECT id_rol, nombre_rol FROM rol ORDER BY nombre_rol")->fetchAll(PDO::FETCH_ASSOC);
            $especialidades = $pdo->query("SELECT id_espe, nom_espe FROM especialidad ORDER BY nom_espe")->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) { $error_message = "Error al consultar datos: " . $e->getMessage(); }
    finally { if ($pdo) { Database::disconnect(); } }
}
?>
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"> <h5 class="modal-title" id="editUserModalLabel"><i class="bi bi-person-circle me-2"></i>Editar Perfil de Usuario</h5> <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> </div>
            <div class="modal-body p-4 modal-compact">
                <?php if ($error_message): ?> <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error_message); ?></div>
                <?php elseif ($usuario_data): 
                    $foto_src_db = $usuario_data['foto_usu'] ?? '';
                    $ruta_fisica_foto = __DIR__ . '/../../../' . $foto_src_db;
                    $foto_a_mostrar = (file_exists($ruta_fisica_foto) && !empty($foto_src_db) && is_file($ruta_fisica_foto)) ? '../../' . htmlspecialchars($foto_src_db) : $default_avatar_path;
                ?>
                    <form id="editUserFormAdmin" action="../includes/editar_usuario.php" method="POST" novalidate 
                          data-initial-municipio="<?php echo htmlspecialchars($usuario_data['id_municipio_actual'] ?? ''); ?>"
                          data-initial-barrio="<?php echo htmlspecialchars($usuario_data['id_barrio_actual'] ?? ''); ?>">

                        <input type="hidden" name="doc_usu_editar" value="<?php echo htmlspecialchars($doc_usuario_a_editar); ?>">
                        <div class="row g-4">
                            <div class="col-lg-4 text-center d-flex flex-column align-items-center justify-content-center border-end pe-4">
                                <img src="<?php echo $foto_a_mostrar; ?>?t=<?php echo time(); ?>" alt="Foto de Perfil" class="rounded-circle mb-3" style="width: 140px; height: 140px; object-fit: cover; border: 4px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                <div class="w-100 mt-3">
                                    <div class="mb-3"><label class="form-label text-muted small">Documento:</label><input type="text" class="form-control form-control-sm text-center" value="<?php echo htmlspecialchars($usuario_data['doc_usu']); ?>" readonly></div>
                                    <div class="mb-3"><label class="form-label text-muted small">Tipo Doc.:</label><input type="text" class="form-control form-control-sm text-center" value="<?php echo htmlspecialchars($usuario_data['tipo_documento_nombre'] ?? 'N/A'); ?>" readonly></div>
                                    <div class="mb-3"><label class="form-label text-muted small">Rol Actual:</label><input type="text" class="form-control form-control-sm text-center" value="<?php echo htmlspecialchars($usuario_data['nombre_rol'] ?? 'N/A'); ?>" readonly></div>
                                    <div class="mb-3"><label class="form-label text-muted small">Estado Actual:</label><input type="text" class="form-control form-control-sm text-center" value="<?php echo htmlspecialchars($usuario_data['estado_nombre'] ?? 'N/A'); ?>" readonly></div>
                                </div>
                            </div>

                            <div class="col-lg-8 ps-4">
                                <div class="mb-2"> <label for="nom_usu_edit" class="form-label">Nombre Completo (*)</label> <input type="text" class="form-control form-control-sm" id="nom_usu_edit" name="nom_usu_edit" value="<?php echo htmlspecialchars($usuario_data['nom_usu']); ?>" required> <div class="invalid-feedback"></div> </div>
                                <div class="row g-2">
                                    <div class="col-md-7 mb-2"> <label for="correo_usu_edit" class="form-label">Correo (*)</label> <input type="email" class="form-control form-control-sm" id="correo_usu_edit" name="correo_usu_edit" value="<?php echo htmlspecialchars($usuario_data['correo_usu']); ?>" required> <div class="invalid-feedback"></div> </div>
                                    <div class="col-md-5 mb-2"> <label for="tel_usu_edit" class="form-label">Teléfono (*)</label> <input type="tel" class="form-control form-control-sm" id="tel_usu_edit" name="tel_usu_edit" value="<?php echo htmlspecialchars($usuario_data['tel_usu'] ?? ''); ?>" required> <div class="invalid-feedback"></div> </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-7 mb-2"> <label class="form-label">Fecha Nacimiento</label> <input type="date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($usuario_data['fecha_nac']); ?>" disabled></div>
                                    <div class="col-md-5 mb-2"> <label for="id_gen_edit" class="form-label">Género (*)</label> <select class="form-select form-select-sm" id="id_gen_edit" name="id_gen_edit" required> <option value="">Seleccione...</option> <?php foreach ($generos as $genero): ?> <option value="<?php echo $genero['id_gen']; ?>" <?php echo ($usuario_data['id_gen'] == $genero['id_gen']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($genero['nom_gen']); ?></option> <?php endforeach; ?> </select> <div class="invalid-feedback"></div> </div>
                                </div>
                                <div class="mb-2"> <label for="direccion_usu_edit" class="form-label">Dirección (*)</label> <input type="text" class="form-control form-control-sm" id="direccion_usu_edit" name="direccion_usu_edit" value="<?php echo htmlspecialchars($usuario_data['direccion_usu'] ?? ''); ?>" required> <div class="invalid-feedback"></div> </div>
                                <div class="row g-2">
                                    <div class="col-md-6 mb-2"> <label for="id_departamento_edit" class="form-label">Departamento (*)</label> <select class="form-select form-select-sm" id="id_departamento_edit" name="id_departamento_edit" required> <option value="">Seleccione...</option> <?php foreach ($departamentos as $depto): ?> <option value="<?php echo $depto['id_dep']; ?>" <?php echo ($usuario_data['id_departamento_actual'] == $depto['id_dep']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($depto['nom_dep']); ?></option> <?php endforeach; ?> </select> <div class="invalid-feedback"></div> </div>
                                    <div class="col-md-6 mb-2"> <label for="id_municipio_edit" class="form-label">Municipio (*)</label> <select class="form-select form-select-sm" id="id_municipio_edit" name="id_municipio_edit" required disabled><option value="">Seleccione Departamento</option></select> <div class="invalid-feedback"></div> </div>
                                </div>
                                <div class="mb-2"> <label for="id_barrio_edit" class="form-label">Barrio (*)</label> <select class="form-select form-select-sm" id="id_barrio_edit" name="id_barrio_edit" required disabled><option value="">Seleccione Municipio</option></select> <div class="invalid-feedback"></div> </div>
                                <div class="row g-2">
                                    <div class="col-md-6 mb-2"> <label for="id_rol_edit" class="form-label">Rol (*)</label> <select class="form-select form-select-sm" id="id_rol_edit" name="id_rol_edit" required> <option value="">Seleccione...</option> <?php foreach ($roles as $rol_item): ?> <option value="<?php echo $rol_item['id_rol']; ?>" <?php echo ($usuario_data['id_rol'] == $rol_item['id_rol']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($rol_item['nombre_rol']); ?></option> <?php endforeach; ?> </select> <div class="invalid-feedback"></div> </div>
                                    <div class="col-md-6 mb-2"> <label for="id_est_edit" class="form-label">Estado (*)</label> <select class="form-select form-select-sm" id="id_est_edit" name="id_est_edit" required> <option value="">Seleccione...</option> <?php foreach ($estados as $estado): ?> <option value="<?php echo $estado['id_est']; ?>" <?php echo ($usuario_data['id_est'] == $estado['id_est']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($estado['nom_est']); ?></option> <?php endforeach; ?> </select> <div class="invalid-feedback"></div> </div>
                                </div>
                                <div class="mb-2" id="div_especialidad_edit" style="<?php echo ($usuario_data['id_rol'] ?? '') == 4 ? '' : 'display: none;'; ?>">
                                    <label for="id_especialidad_edit" class="form-label">Especialidad (*)</label>
                                    <select class="form-select form-select-sm" id="id_especialidad_edit" name="id_especialidad_edit"><option value="">Seleccione...</option><?php foreach ($especialidades as $espe): ?><option value="<?php echo $espe['id_espe']; ?>" <?php echo ($usuario_data['id_especialidad'] == $espe['id_espe']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($espe['nom_espe']); ?></option><?php endforeach; ?></select><div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php else: ?> <div class="alert alert-warning" role="alert">No se pudieron cargar los datos del usuario.</div> <?php endif; ?>
            </div>
            <div class="modal-footer"> <div id="modalEditUserUpdateMessage" class="me-auto"></div> <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button> <?php if ($usuario_data): ?> <button type="submit" class="btn btn-primary" form="editUserFormAdmin" id="saveUserChangesAdminButton" disabled>Guardar Cambios</button> <?php endif; ?> </div>
        </div>
    </div>
</div>