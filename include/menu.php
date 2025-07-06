<?php
// BLOQUE DE INICIALIZACIÓN Y CONFIGURACIÓN DEL MENÚ
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$currentPage = basename($_SERVER['PHP_SELF']);
$rol_usuario = $_SESSION['id_rol'] ?? null;
$nombre_usuario_display = isset($_SESSION['nombre_usuario']) ? htmlspecialchars($_SESSION['nombre_usuario']) : 'Usuario';

$path_parts = explode('/', trim(str_replace(BASE_URL, '', $_SERVER['SCRIPT_NAME']), '/'));
$current_folder = $path_parts[0] ?? '';

$inicio_link = BASE_URL . '/index.php';
if ($rol_usuario) {
    if ($rol_usuario == 4) {
        $inicio_link = BASE_URL . '/medi/citas_hoy.php';
    } else {
        $inicio_link = BASE_URL . '/' . $current_folder . '/inicio.php';
    }
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Salud Connect'; ?></title>
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/loguito.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    
    <?php
    $estilo_rol = '';
    if ($rol_usuario == 1) { $estilo_rol = 'admi/estilo.css'; }
    elseif ($rol_usuario == 2) { $estilo_rol = 'paci/estilos.css'; }
    elseif ($rol_usuario == 3) { $estilo_rol = 'farma/estilos.css'; }
    elseif ($rol_usuario == 4) { $estilo_rol = 'medi/estilos.css'; }

    if ($estilo_rol && file_exists(ROOT_PATH . '/' . $estilo_rol)) { 
        echo '<link rel="stylesheet" href="' . BASE_URL . '/' . $estilo_rol . '?v=' . time() . '">'; 
    }
    ?>
    <style>
        body { padding-top: 62px; }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark py-0 fixed-top" style="background-color: rgb(0, 117, 201);">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?php echo $inicio_link; ?>"><img src="<?php echo BASE_URL; ?>/img/Logo.png" alt="SaludConnect Logo" style="max-height: 45px;"></a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavSaludConnect"><span class="navbar-toggler-icon"></span></button>
                
                <div class="collapse navbar-collapse" id="navbarNavSaludConnect">
                    <ul class="navbar-nav ms-auto align-items-center">

                        <?php if ($rol_usuario == 1): ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'inicio.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admi/inicio.php">Inicio</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'crear_usu.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admi/gestion_crear/crear_usu.php">Crear Usuario</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Gestión Entidades</a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admi/gestion_entidades/ver_entidades.php">Ver Entidades</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admi/gestion_entidades/crear_entidad.php">Crear Entidad</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admi/gestion_entidades/crear_alianza.php">Crear Alianza</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admi/gestion_entidades/lista_alianzas.php">Ver Alianzas</a></li>
                                </ul>
                            </li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admi/gestion_pacientes/lista_pacientes.php">Gestión Pacientes</a></li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admi/gestion_farmaceutas/lista_farmaceutas.php">Gestión Farmaceutas</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Gestión Médicos</a>
                                <ul class="dropdown-menu">
                                     <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admi/gestion_medicos/lista_medicos.php">Lista Médicos</a></li>
                                     <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admi/gestion_medicos/crear_horario.php">Crear Horario</a></li>
                                     <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admi/gestion_medicos/ver_horarios.php">Ver Horarios</a></li>
                                </ul>
                            </li>
                             <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Gestión Sistema</a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admi/gestion_crear/geografica/ver_departamentos.php">Config. Geográfica</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admi/gestion_crear/enfermedades/ver_enfermedades.php">Gestión Enfermedades</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <?php if ($rol_usuario == 2): ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'inicio.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/paci/inicio.php">Inicio</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'citas.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/paci/citas.php">Agendar Citas</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'citas_actuales.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/paci/citas_actuales.php">Mis Citas</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'historial_medico.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/paci/historial_medico.php">Historial Médico</a></li>
                        <?php endif; ?>
                        
                        <?php if ($rol_usuario == 3): ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'inicio.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/farma/inicio.php">Inicio</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'lista_pacientes.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/farma/lista_pacientes.php">Pacientes en Espera</a></li>
                             <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Entregas</a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/farma/entregar/lista_entregas.php">Historial Entregas</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/farma/entregar/entregas_pendientes.php">Entregas Pendientes</a></li>
                                </ul>
                            </li>
                             <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Inventario</a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/farma/inventario/inventario.php">Ver Inventario</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/farma/inventario/insertar_inventario.php">Insertar en Inventario</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/farma/inventario/movimientos_inventario.php">Ver Movimientos</a></li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Medicamentos</a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/farma/crear/crear_medicamento.php">Crear Medicamento</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/farma/crear/ver_medicamento.php">Ver Medicamentos</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/farma/crear/crear_tipo_medi.php">Crear Tipo Medicamento</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/farma/crear/ver_tipo_medi.php">Ver Tipos de Medicamento</a></li>
                                </ul>
                            </li>
                            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/farma/televisor/pantalla_tv.php">TV</a></li>
                        <?php endif; ?>

                        <?php if ($rol_usuario == 4): ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'citas_hoy.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/medi/citas_hoy.php">Citas</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'ver_ordenes.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/medi/ver_ordenes.php">Ver Órdenes</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'historial_citas.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/medi/historial_citas.php">Historial Citas</a></li>
                        <?php endif; ?>

                        <?php if ($rol_usuario): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUserMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle fs-4"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownUserMenu">
                                    <li><h6 class="dropdown-header" id="menuUserNameDisplay"><?php echo $nombre_usuario_display; ?></h6></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" id="abrirPerfilModalLink"><i class="bi bi-person-vcard me-2"></i>Mi Perfil</a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/include/salir.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <div id="perfilModalPlaceholderContainerGlobal"></div>

    <!-- ================== CAMBIO CLAVE AQUÍ ================== -->
    <script>
        // Se usa `var` y se adjunta a `window` para crear una variable global y re-utilizable.
        // Esto evita el error "has already been declared" y permite que otras páginas añadan propiedades.
        var AppConfig = window.AppConfig || {};
        
        // Se establecen las propiedades base. Otras páginas pueden añadir más (como API_URL).
        AppConfig.BASE_URL = '<?php echo BASE_URL; ?>';
        AppConfig.INCLUDE_PATH = '<?php echo BASE_URL; ?>/include/';
    </script>
    <!-- ======================================================= -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/js/app_menu.js?v=<?php echo time(); ?>"></script>
</body>