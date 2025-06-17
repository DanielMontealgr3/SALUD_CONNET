<?php
require_once __DIR__ . '/../../include/conexion.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$tipo_entidad = trim($_GET['tipo'] ?? '');
$id_entidad = trim($_GET['id'] ?? '');
$html_content = '<div class="alert alert-danger">Error: Datos insuficientes para cargar detalles.</div>';

if (!empty($tipo_entidad) && !empty($id_entidad)) {
    $conex_db = new database();
    $con = $conex_db->conectar();

    if ($con) {
        $data = null;
        try {
            if ($tipo_entidad === 'farmacias') {
                $stmt = $con->prepare("SELECT nit_farm as id, nom_farm as nombre, direc_farm as direccion, nom_gerente, tel_farm as telefono, correo_farm as correo FROM farmacias WHERE nit_farm = :id");
            } elseif ($tipo_entidad === 'eps') {
                $stmt = $con->prepare("SELECT nit_eps as id, nombre_eps as nombre, direc_eps as direccion, nom_gerente, telefono, correo FROM eps WHERE nit_eps = :id");
            } elseif ($tipo_entidad === 'ips') {
                $stmt = $con->prepare("SELECT i.Nit_IPS as id, i.nom_IPS as nombre, i.direc_IPS as direccion, i.nom_gerente, i.tel_IPS as telefono, i.correo_IPS as correo, m.nom_mun, d.nom_dep FROM ips i LEFT JOIN municipio m ON i.ubicacion_mun = m.id_mun LEFT JOIN departamento d ON m.id_dep = d.id_dep WHERE i.Nit_IPS = :id");
            }
            
            if (isset($stmt)) {
                $stmt->execute([':id' => $id_entidad]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($data) {
                $badge_class = '';
                $tipo_display = '';
                 switch ($tipo_entidad) {
                    case 'farmacias': $badge_class = 'bg-success'; $tipo_display = 'Farmacia'; break;
                    case 'eps': $badge_class = 'bg-primary'; $tipo_display = 'EPS'; break;
                    case 'ips': $badge_class = 'bg-info text-dark'; $tipo_display = 'IPS'; break;
                }

                $html_content = '
                <div class="row">
                    <div class="col-lg-6">
                        <div class="detalle-seccion">
                            <h6><i class="bi bi-building"></i> Datos Generales</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Nombre:</strong> ' . htmlspecialchars($data['nombre'] ?? 'N/A') . '</li>
                                <li class="list-group-item"><strong>NIT:</strong> ' . htmlspecialchars($data['id'] ?? 'N/A') . '</li>
                                <li class="list-group-item"><strong>Tipo de Entidad:</strong> <span class="badge ' . $badge_class . '">' . $tipo_display . '</span></li>
                                <li class="list-group-item"><strong>Gerente / Representante:</strong> ' . htmlspecialchars($data['nom_gerente'] ?? 'N/A') . '</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-6">
                         <div class="detalle-seccion">
                            <h6><i class="bi bi-geo-alt-fill"></i> Contacto y Ubicación</h6>
                             <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Dirección:</strong> ' . htmlspecialchars($data['direccion'] ?? 'N/A') . '</li>';
                
                if ($tipo_entidad === 'ips') {
                     $html_content .= '<li class="list-group-item"><strong>Municipio:</strong> ' . htmlspecialchars($data['nom_mun'] ?? 'N/A') . '</li>';
                     $html_content .= '<li class="list-group-item"><strong>Departamento:</strong> ' . htmlspecialchars($data['nom_dep'] ?? 'N/A') . '</li>';
                }

                $html_content .= '
                                <li class="list-group-item"><strong>Teléfono:</strong> ' . htmlspecialchars($data['telefono'] ?? 'N/A') . '</li>
                                <li class="list-group-item"><strong>Correo Electrónico:</strong> ' . htmlspecialchars($data['correo'] ?? 'N/A') . '</li>
                            </ul>
                        </div>
                    </div>
                </div>';
            } else {
                 $html_content = '<div class="alert alert-warning">No se encontraron detalles para la entidad seleccionada.</div>';
            }

        } catch (PDOException $e) {
            error_log("Error PDO en ajax_detalle_entidad: " . $e->getMessage());
            $html_content = '<div class="alert alert-danger">Error de base de datos al cargar los detalles.</div>';
        }
        $conex_db->desconectar();
    } else {
        $html_content = '<div class="alert alert-danger">Error de conexión con la base de datos.</div>';
    }
}

echo $html_content;
?>