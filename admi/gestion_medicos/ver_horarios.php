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

$conex_db = new database();
$con = $conex_db->conectar();
$medicos_data = [];
$ips_disponibles = [];
$filtro_nit_ips_seleccionado = isset($_GET['filtro_ips']) ? trim($_GET['filtro_ips']) : '';
$filtro_doc_medico = isset($_GET['filtro_doc_medico']) ? trim($_GET['filtro_doc_medico']) : '';
$php_error_message = '';

$registros_por_pagina = 4;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$total_registros = 0;
$total_paginas = 1;


if ($con) {
    try {
        $stmt_ips = $con->query("
            SELECT DISTINCT i.nit_ips, i.nom_ips 
            FROM ips i
            INNER JOIN asignacion_medico am ON i.nit_ips = am.nit_ips
            WHERE am.id_estado = 1
            ORDER BY i.nom_ips ASC
        ");
        $ips_disponibles = $stmt_ips->fetchAll(PDO::FETCH_ASSOC);

        $sql_base_from = "FROM usuarios u
                          INNER JOIN especialidad esp ON u.id_especialidad = esp.id_espe
                          INNER JOIN asignacion_medico am ON u.doc_usu = am.doc_medico AND am.id_estado = 1
                          INNER JOIN ips i ON am.nit_ips = i.Nit_IPS
                          INNER JOIN horario_medico hm ON u.doc_usu = hm.doc_medico";
        
        $sql_where_clauses = ["u.id_rol = 4"];
        $params_php = [];

        if (!empty($filtro_nit_ips_seleccionado)) {
            $sql_where_clauses[] = "am.nit_ips = :nit_ips";
            $params_php[':nit_ips'] = $filtro_nit_ips_seleccionado;
        }
        if (!empty($filtro_doc_medico)) {
            $sql_where_clauses[] = "u.doc_usu LIKE :doc_medico_filtro";
            $params_php[':doc_medico_filtro'] = "%" . $filtro_doc_medico . "%";
        }
        
        $sql_where = "";
        if (!empty($sql_where_clauses)) {
            $sql_where = " WHERE " . implode(" AND ", $sql_where_clauses);
        }
        
        $stmt_total_sql = "SELECT COUNT(DISTINCT u.doc_usu) $sql_base_from $sql_where";
        $stmt_total = $con->prepare($stmt_total_sql);
        $stmt_total->execute($params_php);
        $total_registros = (int)$stmt_total->fetchColumn();

        if ($total_registros > 0) {
            $total_paginas = ceil($total_registros / $registros_por_pagina);
            if ($pagina_actual > $total_paginas && $total_paginas > 0) $pagina_actual = $total_paginas;
            if($total_paginas == 0) $total_paginas = 1;
            $offset = ($pagina_actual - 1) * $registros_por_pagina;

            $sql_medicos = "SELECT DISTINCT u.doc_usu, u.nom_usu, esp.nom_espe, i.nom_ips, am.nit_ips
                            $sql_base_from
                            $sql_where
                            GROUP BY u.doc_usu, u.nom_usu, esp.nom_espe, i.nom_ips, am.nit_ips 
                            ORDER BY u.nom_usu ASC
                            LIMIT :limit OFFSET :offset_val";
            
            $stmt_medicos = $con->prepare($sql_medicos);
            foreach ($params_php as $key => $value) {
                $stmt_medicos->bindValue($key, $value);
            }
            $stmt_medicos->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
            $stmt_medicos->bindParam(':offset_val', $offset, PDO::PARAM_INT);
            $stmt_medicos->execute();
            $medicos_data = $stmt_medicos->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        $php_error_message = "<div class='alert alert-danger'>Error al cargar datos: " . htmlspecialchars($e->getMessage()) . "</div>";
        error_log("Error PDO en ver_horarios.php: " . $e->getMessage());
    }
} else {
    $php_error_message = "<div class='alert alert-danger'>Error de conexión a la base de datos.</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    
    <link rel="icon" type="image/png" href="../img/loguito.png">
    <title>Horarios de Médicos - Administración</title>
    <style>
        #modalHorariosMedico .modal-content { 
            background-color: #f0f2f5; 
            border: 3px solid #87CEEB; 
            border-radius: .5rem; 
        }
        #modalHorariosMedico .modal-header { 
            background-color: #005A9C; 
            color: white; 
            border-bottom: 1px solid #0047AB; 
        }
        #modalHorariosMedico .modal-header .btn-close { 
            filter: invert(1) grayscale(100%) brightness(200%); 
        }
        #modalHorariosMedico .form-label { 
            font-weight: 500; 
        }
        #modalHorariosMedico .detalle-horario-dia-container { 
            max-height: 300px; 
            overflow-y: auto;
            border: 1px solid #dee2e6;
            padding: 1rem;
            border-radius: .25rem;
            background-color: #fff;
        }
        #modalHorariosMedico .mensaje-seleccione-fecha { margin-top: 1rem; }
        #modalHorariosMedico .subtitulo-horas { color: #005A9C; font-weight: bold; }
        #modalHorariosMedico .nombre-medico-titulo { color: #333; font-weight: bold; font-size: 1.2rem; }
    </style>
</head>
<body>
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1">
            <div class="vista-datos-container">
                <h3 class="titulo-lista-tabla">Horarios de Médicos Asignados</h3>

                <?php if (!empty($php_error_message)): ?>
                    <?php echo $php_error_message; ?>
                <?php endif; ?>

                <form method="GET" action="ver_horarios.php" class="mb-4 filtros-tabla-container">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="filtro_ips" class="form-label">Filtrar por IPS:</label>
                            <select name="filtro_ips" id="filtro_ips" class="form-select form-select-sm">
                                <option value="">-- Todas las IPS --</option>
                                <?php foreach ($ips_disponibles as $ips): ?>
                                    <option value="<?php echo htmlspecialchars($ips['nit_ips']); ?>" <?php echo ($filtro_nit_ips_seleccionado == $ips['nit_ips']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ips['nom_ips']); ?> (<?php echo htmlspecialchars($ips['nit_ips']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="filtro_doc_medico" class="form-label">Buscar por Documento Médico:</label>
                            <input type="text" name="filtro_doc_medico" id="filtro_doc_medico" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filtro_doc_medico); ?>" placeholder="Escriba el documento...">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-filtrar-tabla w-100">Filtrar <i class="bi bi-search"></i></button>
                        </div>
                    </div>
                </form>

                <?php if (empty($medicos_data) && empty($php_error_message)): ?>
                    <div class="alert alert-info text-center" role="alert">
                        No hay médicos con horarios que cumplan con los criterios de búsqueda.
                    </div>
                <?php elseif (!empty($medicos_data)): ?>
                    <div class="table-responsive">
                        <table class="tabla-admin-mejorada">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Nombre Médico</th>
                                    <th>Especialidad</th>
                                    <th>IPS Asignada</th>
                                    <th class="columna-acciones-tabla">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($medicos_data as $medico): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($medico['doc_usu']); ?></td>
                                        <td><?php echo htmlspecialchars($medico['nom_usu']); ?></td>
                                        <td><?php echo htmlspecialchars($medico['nom_espe']); ?></td>
                                        <td><?php echo htmlspecialchars($medico['nom_ips']); ?></td>
                                        <td class="acciones-tabla">
                                            <button type="button" class="btn btn-primary btn-sm btn-ver-horarios" 
                                                    data-bs-toggle="modal" data-bs-target="#modalHorariosMedico"
                                                    data-doc-medico="<?php echo htmlspecialchars($medico['doc_usu']); ?>"
                                                    data-nombre-medico="<?php echo htmlspecialchars($medico['nom_usu']); ?>"
                                                    data-nit-ips="<?php echo htmlspecialchars($medico['nit_ips']); ?>"
                                                    title="Ver Horarios">
                                                <i class="bi bi-calendar-week"></i> <span>Horarios</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($total_registros > 0 && $total_paginas > 1): ?>
                    <nav aria-label="Paginación de horarios de médicos" class="mt-3 paginacion-tabla-container">
                        <ul class="pagination pagination-sm">
                            <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>&filtro_ips=<?php echo urlencode($filtro_nit_ips_seleccionado); ?>&filtro_doc_medico=<?php echo urlencode($filtro_doc_medico); ?>"><i class="bi bi-chevron-left"></i></a>
                            </li>
                            <li class="page-item active" id="page-number-container">
                               <span class="page-link page-number-display" title="Click/Enter para ir a pág."><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></span>
                               <input type="number" class="form-control form-control-sm page-number-input-field" value="<?php echo $pagina_actual; ?>" min="1" max="<?php echo $total_paginas; ?>" style="display: none;" data-total="<?php echo $total_paginas; ?>">
                             </li>
                            <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>&filtro_ips=<?php echo urlencode($filtro_nit_ips_seleccionado); ?>&filtro_doc_medico=<?php echo urlencode($filtro_doc_medico); ?>"><i class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/modal_horario_medico.php'; ?>
    <?php include '../../include/footer.php'; ?>
    <script src="../js/ver_horarios_admin.js"></script> 
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const pageContainer = document.getElementById('page-number-container'); 
        const pageIS = pageContainer?.querySelector('.page-number-display'); 
        const pageIF = pageContainer?.querySelector('.page-number-input-field'); 
        if(pageIS && pageIF){ 
            pageIS.addEventListener('click', () => { 
                pageIS.style.display = 'none'; 
                pageIF.style.display = 'inline-block'; 
                pageIF.focus(); pageIF.select(); 
            }); 
            const goPg = () => { 
                const tp = parseInt(pageIF.dataset.total, 10) || 1; 
                let tgPg = parseInt(pageIF.value, 10); 
                if (isNaN(tgPg) || tgPg < 1) tgPg = 1; 
                else if (tgPg > tp) tgPg = tp; 
                const curl = new URL(window.location.href); 
                curl.searchParams.set('pagina', tgPg); 
                window.location.href = curl.toString(); 
            }; 
            const hideInput = () => { 
                const totalPgs = parseInt(pageIF.dataset.total, 10) || 1; 
                const currentPage = parseInt(pageIF.value, 10);
                if (isNaN(currentPage) || currentPage < 1) {
                    pageIS.textContent = '<?php echo $pagina_actual; ?> / ' + totalPgs;
                    pageIF.value = '<?php echo $pagina_actual; ?>';
                } else if (currentPage > totalPgs) {
                     pageIS.textContent = totalPgs + ' / ' + totalPgs;
                     pageIF.value = totalPgs;
                }
                else {
                    pageIS.textContent = pageIF.value + ' / ' + totalPgs;
                }
                pageIS.style.display = 'inline-block'; 
                pageIF.style.display = 'none'; 
            }; 
            pageIF.addEventListener('blur', () => { 
                setTimeout(hideInput, 150); 
            }); 
            pageIF.addEventListener('keydown', (e) => { 
                if (e.key === 'Enter') { e.preventDefault(); goPg(); } 
                else if (e.key === 'Escape'){ pageIF.value = '<?php echo $pagina_actual; ?>'; hideInput(); } 
            }); 
        }
    });
    </script>
</body>
</html>