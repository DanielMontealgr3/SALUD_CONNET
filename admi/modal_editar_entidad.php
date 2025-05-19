<?php
require_once __DIR__ . '/../include/conexion.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$tipo_entidad_get = trim($_GET['tipo'] ?? '');
$id_entidad_get = trim($_GET['id'] ?? '');
$entidad_data = null;
$departamentos = [];
$municipios_actuales = [];
$id_dep_ips_form = '';
$error_modal = '';

if (empty($tipo_entidad_get) || empty($id_entidad_get) || !in_array($tipo_entidad_get, ['farmacias', 'eps', 'ips'])) {
    $error_modal = "Tipo de entidad o ID no válido.";
} else {
    $conex_db = new database();
    $con = $conex_db->conectar();
    if ($con) {
        $config = [];
        if ($tipo_entidad_get === 'farmacias') { $config = ['tabla' => 'farmacias', 'pk' => 'nit_farm', 'nombre_col' => 'nom_farm', 'cols' => ['nit_farm', 'nom_farm', 'direc_farm', 'nom_gerente', 'tel_farm', 'correo_farm']]; }
        elseif ($tipo_entidad_get === 'eps') { $config = ['tabla' => 'eps', 'pk' => 'nit_eps', 'nombre_col' => 'nombre_eps', 'cols' => ['nit_eps', 'nombre_eps', 'direc_eps', 'nom_gerente', 'telefono', 'correo']]; }
        elseif ($tipo_entidad_get === 'ips') { $config = ['tabla' => 'ips', 'pk' => 'Nit_IPS', 'nombre_col' => 'nom_IPS', 'cols' => ['Nit_IPS', 'nom_IPS', 'direc_IPS', 'nom_gerente', 'tel_IPS', 'correo_IPS', 'ubicacion_mun']]; }

        try {
            $sql_ent = "SELECT * FROM " . $config['tabla'] . " WHERE " . $config['pk'] . " = :id";
            $stmt_ent = $con->prepare($sql_ent);
            $stmt_ent->bindParam(':id', $id_entidad_get);
            $stmt_ent->execute();
            $entidad_data = $stmt_ent->fetch(PDO::FETCH_ASSOC);

            if (!$entidad_data) { $error_modal = "Entidad no encontrada."; }
            else {
                if ($tipo_entidad_get === 'ips') {
                    $stmt_dep_all = $con->query("SELECT id_dep, nom_dep FROM departamento ORDER BY nom_dep ASC");
                    $departamentos = $stmt_dep_all->fetchAll(PDO::FETCH_ASSOC);
                    if (!empty($entidad_data['ubicacion_mun'])) {
                        $stmt_mun_info = $con->prepare("SELECT m.id_mun, m.nom_mun, m.id_dep, d.nom_dep FROM municipio m JOIN departamento d ON m.id_dep = d.id_dep WHERE m.id_mun = :id_mun_ips");
                        $stmt_mun_info->bindParam(':id_mun_ips', $entidad_data['ubicacion_mun']);
                        $stmt_mun_info->execute();
                        $mun_info = $stmt_mun_info->fetch(PDO::FETCH_ASSOC);
                        if ($mun_info) { $id_dep_ips_form = $mun_info['id_dep']; }

                        $stmt_mun_dep = $con->prepare("SELECT id_mun, nom_mun FROM municipio WHERE id_dep = :id_dep_actual ORDER BY nom_mun ASC");
                        $stmt_mun_dep->bindParam(':id_dep_actual', $id_dep_ips_form);
                        $stmt_mun_dep->execute();
                        $municipios_actuales = $stmt_mun_dep->fetchAll(PDO::FETCH_ASSOC);
                    }
                }
            }
        } catch (PDOException $e) { $error_modal = "Error DB: " . $e->getMessage(); }
        $conex_db->desconectar();
    } else { $error_modal = "Error de conexión a BD."; }
}
?>
<style>
    #dynamicEditEntidadModal .modal-content { background-color: #f0f2f5; border: 3px solid #87CEEB; border-radius: .5rem; } 
    #dynamicEditEntidadModal .modal-header { background-color: #005A9C; color: white; border-bottom: 1px solid #0047AB; } 
    #dynamicEditEntidadModal .modal-header .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
    #dynamicEditEntidadModal .modal-dialog { max-width: 850px; } 
    #dynamicEditEntidadModal .invalid-feedback { display: none; width: 100%; margin-top: .15rem; font-size: .80em; color: #dc3545; }
    #dynamicEditEntidadModal .form-control.is-invalid, #dynamicEditEntidadModal .form-select.is-invalid { border-color: #dc3545; padding-right: calc(1.5em + .75rem); background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right calc(.375em + .1875rem) center; background-size: calc(.75em + .375rem) calc(.75em + .375rem); }
    #dynamicEditEntidadModal .form-control.is-invalid:focus, #dynamicEditEntidadModal .form-select.is-invalid:focus { border-color: #dc3545; box-shadow: 0 0 0 .25rem rgba(220,53,69,.25); }
    #dynamicEditEntidadModal .form-control.is-invalid ~ .invalid-feedback,
    #dynamicEditEntidadModal .form-select.is-invalid ~ .invalid-feedback { display: block; }
</style>
<div class="modal fade" id="dynamicEditEntidadModal" tabindex="-1" aria-labelledby="dynamicEditEntidadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dynamicEditEntidadModalLabel">Editar Entidad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($error_modal)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_modal); ?></div>
                <?php elseif ($entidad_data): ?>
                    <p class="text-center text-muted">Modificando: <strong><?php echo htmlspecialchars($entidad_data[$config['nombre_col']] ?? 'N/A'); ?></strong> (ID: <?php echo htmlspecialchars($id_entidad_get); ?>)</p>
                    <form id="formActualizarEntidad" method="POST" novalidate>
                        <input type="hidden" name="id_entidad_original" value="<?php echo htmlspecialchars($id_entidad_get); ?>">
                        <input type="hidden" name="tipo_entidad_original" value="<?php echo htmlspecialchars($tipo_entidad_get); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                        <?php if ($tipo_entidad_get === 'farmacias'): ?>
                            <div class="mb-3"><label for="nit_farm_modal" class="form-label">NIT:</label><input type="text" id="nit_farm_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['nit_farm'] ?? ''); ?>" readonly disabled></div>
                            <div class="mb-3"><label for="nom_farm_modal" class="form-label">Nombre Farmacia (*):</label><input type="text" id="nom_farm_modal" name="nom_farm_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['nom_farm'] ?? ''); ?>" required> <div class="invalid-feedback"></div> </div>
                            <div class="mb-3"><label for="direc_farm_modal" class="form-label">Dirección:</label><input type="text" id="direc_farm_modal" name="direc_farm_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['direc_farm'] ?? ''); ?>"><div class="invalid-feedback"></div></div>
                            <div class="mb-3"><label for="nom_gerente_farm_modal" class="form-label">Nombre Gerente:</label><input type="text" id="nom_gerente_farm_modal" name="nom_gerente_farm_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['nom_gerente'] ?? ''); ?>"><div class="invalid-feedback"></div></div>
                            <div class="mb-3"><label for="tel_farm_modal" class="form-label">Teléfono:</label><input type="text" id="tel_farm_modal" name="tel_farm_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['tel_farm'] ?? ''); ?>"><div class="invalid-feedback"></div></div>
                            <div class="mb-3"><label for="correo_farm_modal" class="form-label">Correo:</label><input type="email" id="correo_farm_modal" name="correo_farm_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['correo_farm'] ?? ''); ?>"><div class="invalid-feedback"></div></div>
                        <?php elseif ($tipo_entidad_get === 'eps'): ?>
                            <div class="mb-3"><label for="nit_eps_modal" class="form-label">NIT:</label><input type="text" id="nit_eps_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['nit_eps'] ?? ''); ?>" readonly disabled></div>
                            <div class="mb-3"><label for="nombre_eps_modal" class="form-label">Nombre EPS (*):</label><input type="text" id="nombre_eps_modal" name="nombre_eps_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['nombre_eps'] ?? ''); ?>" required><div class="invalid-feedback"></div></div>
                            <div class="mb-3"><label for="direc_eps_modal" class="form-label">Dirección:</label><input type="text" id="direc_eps_modal" name="direc_eps_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['direc_eps'] ?? ''); ?>"><div class="invalid-feedback"></div></div>
                            <div class="mb-3"><label for="nom_gerente_eps_modal" class="form-label">Nombre Gerente:</label><input type="text" id="nom_gerente_eps_modal" name="nom_gerente_eps_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['nom_gerente'] ?? ''); ?>"><div class="invalid-feedback"></div></div>
                            <div class="mb-3"><label for="telefono_eps_modal" class="form-label">Teléfono:</label><input type="text" id="telefono_eps_modal" name="telefono_eps_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['telefono'] ?? ''); ?>"><div class="invalid-feedback"></div></div>
                            <div class="mb-3"><label for="correo_eps_modal" class="form-label">Correo:</label><input type="email" id="correo_eps_modal" name="correo_eps_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['correo'] ?? ''); ?>"><div class="invalid-feedback"></div></div>
                        <?php elseif ($tipo_entidad_get === 'ips'): ?>
                            <div class="mb-3"><label for="nit_ips_modal" class="form-label">NIT:</label><input type="text" id="nit_ips_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['Nit_IPS'] ?? ''); ?>" readonly disabled></div>
                            <div class="mb-3"><label for="nom_ips_modal" class="form-label">Nombre IPS (*):</label><input type="text" id="nom_ips_modal" name="nom_ips_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['nom_IPS'] ?? ''); ?>" required><div class="invalid-feedback"></div></div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="id_dep_ips_modal" class="form-label">Departamento Ubicación (*):</label>
                                    <select id="id_dep_ips_modal" name="id_dep_ips_modal" class="form-select" required>
                                        <option value="">Seleccione Departamento...</option>
                                        <?php foreach ($departamentos as $dep) : ?>
                                            <option value="<?php echo htmlspecialchars($dep['id_dep']); ?>" <?php echo ($id_dep_ips_form == $dep['id_dep']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dep['nom_dep']); ?></option>
                                        <?php endforeach; ?>
                                    </select><div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="ubicacion_mun_ips_modal" class="form-label">Municipio Ubicación (*):</label>
                                    <select id="ubicacion_mun_ips_modal" name="ubicacion_mun_ips_modal" class="form-select" required <?php echo empty($municipios_actuales) ? 'disabled' : ''; ?>>
                                        <option value="">Seleccione Departamento...</option>
                                        <?php foreach ($municipios_actuales as $mun_ips): ?>
                                            <option value="<?php echo htmlspecialchars($mun_ips['id_mun']); ?>" <?php echo ($entidad_data['ubicacion_mun'] == $mun_ips['id_mun']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($mun_ips['nom_mun']); ?></option>
                                        <?php endforeach; ?>
                                    </select><div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="mb-3"><label for="direc_ips_modal" class="form-label">Dirección (Detalle):</label><input type="text" id="direc_ips_modal" name="direc_ips_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['direc_IPS'] ?? ''); ?>"><div class="invalid-feedback"></div></div>
                            <div class="mb-3"><label for="nom_gerente_ips_modal" class="form-label">Nombre Gerente:</label><input type="text" id="nom_gerente_ips_modal" name="nom_gerente_ips_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['nom_gerente'] ?? ''); ?>"><div class="invalid-feedback"></div></div>
                            <div class="mb-3"><label for="tel_ips_modal" class="form-label">Teléfono:</label><input type="text" id="tel_ips_modal" name="tel_ips_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['tel_IPS'] ?? ''); ?>"><div class="invalid-feedback"></div></div>
                            <div class="mb-3"><label for="correo_ips_modal" class="form-label">Correo:</label><input type="email" id="correo_ips_modal" name="correo_ips_modal" class="form-control" value="<?php echo htmlspecialchars($entidad_data['correo_IPS'] ?? ''); ?>"><div class="invalid-feedback"></div></div>
                        <?php endif; ?>
                        <div id="modalEditEntidadMessage" class="mt-3"></div>
                    </form>
                <?php else: ?>
                     <div class="alert alert-warning">No se pudieron cargar los datos de la entidad para editar.</div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <?php if (!$error_modal && $entidad_data): ?>
                <button type="submit" class="btn btn-primary" form="formActualizarEntidad" id="btnGuardarCambiosEntidad">Guardar Cambios</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>