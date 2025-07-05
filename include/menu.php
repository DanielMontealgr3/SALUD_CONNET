<?php
// BLOQUE DE INICIALIZACIÓN Y CONFIGURACIÓN DEL MENÚ
// INICIA LA SESIÓN SI NO ESTÁ ACTIVA Y OBTIENE LOS DATOS DEL USUARIO LOGUEADO.
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// OBTIENE LA PÁGINA ACTUAL, EL ROL DEL USUARIO Y SU NOMBRE PARA MARCAR LOS ENLACES COMO 'ACTIVOS'.
$currentPage = basename($_SERVER['PHP_SELF']);
$rol_usuario = $_SESSION['id_rol'] ?? null;
$nombre_usuario_display = isset($_SESSION['nombre_usuario']) ? htmlspecialchars($_SESSION['nombre_usuario']) : 'Usuario';

// DETERMINA LA RUTA DE LA CARPETA ACTUAL (EJ: 'admi', 'medi', ETC.) PARA LA LÓGICA DE 'ACTIVE'.
$path_parts = explode('/', trim(str_replace(BASE_URL, '', $_SERVER['SCRIPT_NAME']), '/'));
$current_folder = $path_parts[0] ?? '';

// ***** LÓGICA PARA EL ENLACE DEL LOGO *****
// DETERMINA LA PÁGINA DE INICIO POR DEFECTO BASADO EN EL ROL DEL USUARIO.
$inicio_link = BASE_URL . '/index.php'; // Por defecto para usuarios no logueados.
if ($rol_usuario) {
    if ($rol_usuario == 4) {
        // SI ES MÉDICO, EL ENLACE DEL LOGO VA A 'CITAS_HOY.PHP'.
        $inicio_link = BASE_URL . '/medi/citas_hoy.php';
    } else {
        // PARA LOS DEMÁS ROLES, VA A 'INICIO.PHP' DENTRO DE SU CARPETA.
        $inicio_link = BASE_URL . '/' . $current_folder . '/inicio.php';
    }
}
?>
<head>
    <!-- BLOQUE DE CONFIGURACIÓN DEL HEAD -->
    <!-- ESTE BLOQUE CONTIENE TODOS LOS ENLACES A LIBRERÍAS EXTERNAS (BOOTSTRAP, SWEETALERT, ETC.) Y ESTILOS. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Salud Connect'; ?></title>
    <!-- USA 'BASE_URL' PARA CONSTRUIR RUTAS CORRECTAS EN CUALQUIER ENTORNO. -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/loguito.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    
    <!-- CARGA DINÁMICA DE LA HOJA DE ESTILOS CORRESPONDIENTE AL ROL DEL USUARIO. -->
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
        body { padding-top: 62px; } /* Ajuste para el navbar fijo */
    </style>
</head>
<body>
    <!-- ESTRUCTURA DEL HEADER Y LA BARRA DE NAVEGACIÓN FIJA DE BOOTSTRAP 5. -->
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark py-0 fixed-top" style="background-color: rgb(0, 117, 201);">
            <div class="container-fluid">
                <!-- LOGOTIPO Y ENLACE A LA PÁGINA DE INICIO DEL ROL CORRESPONDIENTE. -->
                <a class="navbar-brand" href="<?php echo $inicio_link; ?>"><img src="<?php echo BASE_URL; ?>/img/Logo.png" alt="SaludConnect Logo" style="max-height: 45px;"></a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavSaludConnect"><span class="navbar-toggler-icon"></span></button>
                
                <div class="collapse navbar-collapse" id="navbarNavSaludConnect">
                    <ul class="navbar-nav ms-auto align-items-center">

                        <!-- ===================== MENÚ ADMINISTRADOR (ROL 1) ===================== -->
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

                        <!-- ===================== MENÚ PACIENTE (ROL 2) ===================== -->
                        <?php if ($rol_usuario == 2): ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'inicio.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/paci/inicio.php">Inicio</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'citas.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/paci/citas.php">Agendar Citas</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'citas_actuales.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/paci/citas_actuales.php">Mis Citas</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'historial_medico.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/paci/historial_medico.php">Historial Médico</a></li>
                        <?php endif; ?>
                        
                        <!-- ===================== MENÚ FARMACEUTA (ROL 3) ===================== -->
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

                        <!-- ===================== MENÚ MÉDICO (ROL 4) ===================== -->
                        <?php if ($rol_usuario == 4): ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'citas_hoy.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/medi/citas_hoy.php">Citas</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'ver_ordenes.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/medi/ver_ordenes.php">Ver Órdenes</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'historial_citas.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/medi/historial_citas.php">Historial Citas</a></li>
                        <?php endif; ?>

                        <!-- ===================== MENÚ DE USUARIO LOGUEADO (PERFIL) ===================== -->
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
    <!-- CONTENEDOR DONDE SE CARGARÁ EL MODAL DE PERFIL MEDIANTE JAVASCRIPT. -->
    <div id="perfilModalPlaceholderContainerGlobal"></div>

    <!-- BLOQUE DE CONFIGURACIÓN Y CARGA DE SCRIPTS JAVASCRIPT -->
    <!-- PASA VARIABLES DE PHP (COMO BASE_URL) A JAVASCRIPT DE FORMA SEGURA. -->
    <script>
        const AppConfig = {
            BASE_URL: '<?php echo BASE_URL; ?>',
            INCLUDE_PATH: '<?php echo BASE_URL; ?>/include/'
        };
    </script>
    <!-- ENLACE A LIBRERÍAS EXTERNAS Y AL SCRIPT PERSONALIZADO DEL MENÚ. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/js/app_menu.js?v=<?php echo time(); ?>"></script>
</body>