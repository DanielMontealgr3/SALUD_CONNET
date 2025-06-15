<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) { session_start(); }

$currentPage = basename($_SERVER['PHP_SELF']);
$rol_usuario = isset($_SESSION['id_rol']) ? $_SESSION['id_rol'] : null;
$nombre_usuario_display = isset($_SESSION['nombre_usuario']) ? htmlspecialchars($_SESSION['nombre_usuario']) : 'Usuario';

$project_uri_base = '/SALUDCONNECT';
$current_script_directory_from_doc_root = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$path_inside_project = '';

if (strpos($current_script_directory_from_doc_root, $project_uri_base) === 0) {
    $path_inside_project = substr($current_script_directory_from_doc_root, strlen($project_uri_base));
}
$path_inside_project_trimmed = ltrim($path_inside_project, '/');

if ($path_inside_project_trimmed === '') {
    $depth_in_project = 0;
} else {
    $depth_in_project = substr_count($path_inside_project_trimmed, '/') + 1;
}

if ($depth_in_project === 0) {
    $base_href_to_project_root = './';
} else {
    $base_href_to_project_root = str_repeat('../', $depth_in_project);
}

$path_to_img_folder_href = $base_href_to_project_root . "img/";
$path_to_js_folder_href = $base_href_to_project_root . "js/";
$path_to_include_folder_href = $base_href_to_project_root . "include/";

$filesystem_project_root = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . $project_uri_base;

$paginas_gestion_entidades = ['ver_entidades.php', 'crear_entidad.php', 'editar_entidad.php', 'crear_alianza.php', 'lista_alianzas.php'];
$paginas_gestion_pacientes = ['lista_pacientes.php'];
$paginas_gestion_farmaceutas = ['lista_farmaceutas.php', 'asignar_farmaceuta.php'];
$paginas_gestion_medicos = ['ver_horarios.php', 'crear_horario.php', 'lista_medicos.php', 'asignar_ips_medico.php'];

$paginas_gestion_geografica_crear = ['crear_departamento.php', 'crear_municipio.php', 'crear_barrio.php'];
$paginas_gestion_geografica_ver = ['ver_departamentos.php', 'ver_municipios.php', 'ver_barrios.php'];
$paginas_gestion_geografica_todas = array_merge($paginas_gestion_geografica_crear, $paginas_gestion_geografica_ver);

$paginas_gestion_enfermedades_crear = ['crear_enfermedad.php', 'crear_tipo_enfermedad.php'];
$paginas_gestion_enfermedades_ver = ['ver_enfermedades.php', 'ver_tipos_enfermedad.php'];
$paginas_gestion_enfermedades_todas = array_merge($paginas_gestion_enfermedades_crear, $paginas_gestion_enfermedades_ver);

$paginas_gestion_roles_crear = ['crear_rol.php'];
$paginas_gestion_roles_ver = ['ver_roles.php'];
$paginas_gestion_roles_todas = array_merge($paginas_gestion_roles_crear, $paginas_gestion_roles_ver);

$paginas_farma_gestion_medicamentos = ['crear_tipo_medi.php', 'crear_medicamento.php', 'ver_tipo_medi.php', 'ver_medicamento.php'];
$paginas_farma_gestion_inventario = ['inventario.php', 'insertar_inventario.php'];

$es_pagina_gestion_sistema_activa = false;
if (strpos($path_inside_project_trimmed, 'admi/gestion_crear') === 0 &&
    (
     (strpos($path_inside_project_trimmed, 'admi/gestion_crear/geografica') === 0 && in_array($currentPage, $paginas_gestion_geografica_todas)) ||
     (strpos($path_inside_project_trimmed, 'admi/gestion_crear/enfermedades') === 0 && in_array($currentPage, $paginas_gestion_enfermedades_todas)) ||
     (strpos($path_inside_project_trimmed, 'admi/gestion_crear/roles') === 0 && in_array($currentPage, $paginas_gestion_roles_todas))
    )
) {
    $es_pagina_gestion_sistema_activa = true;
}

$es_pagina_config_geo_activa = (strpos($path_inside_project_trimmed, 'admi/gestion_crear/geografica') === 0 && in_array($currentPage, $paginas_gestion_geografica_todas));
$es_pagina_gestion_enf_activa = (strpos($path_inside_project_trimmed, 'admi/gestion_crear/enfermedades') === 0 && in_array($currentPage, $paginas_gestion_enfermedades_todas));
$es_pagina_gestion_roles_activa = (strpos($path_inside_project_trimmed, 'admi/gestion_crear/roles') === 0 && in_array($currentPage, $paginas_gestion_roles_todas));

?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú - SaludConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="<?php echo $path_to_img_folder_href; ?>loguito.png">
    <?php
    $estilo_rol_path_href = "";
    $css_path_admi_from_project_root = "admi/estilo.css";
    $css_path_paci_from_project_root = "paci/estilos.css";
    $css_path_farma_from_project_root = "farma/estilos.css";
    $css_path_medi_from_project_root = "medi/estilos.css";
    $css_path_general_from_project_root = "css/estilo.css";

    if ($rol_usuario == 1 && file_exists($filesystem_project_root . "/" . $css_path_admi_from_project_root)) { $estilo_rol_path_href = $base_href_to_project_root . $css_path_admi_from_project_root; }
    elseif ($rol_usuario == 2 && file_exists($filesystem_project_root . "/" . $css_path_paci_from_project_root)) { $estilo_rol_path_href = $base_href_to_project_root . $css_path_paci_from_project_root; }
    elseif ($rol_usuario == 3 && file_exists($filesystem_project_root . "/" . $css_path_farma_from_project_root)) { $estilo_rol_path_href = $base_href_to_project_root . $css_path_farma_from_project_root; }
    elseif ($rol_usuario == 4 && file_exists($filesystem_project_root . "/" . $css_path_medi_from_project_root)) { $estilo_rol_path_href = $base_href_to_project_root . $css_path_medi_from_project_root; }

    if (!empty($estilo_rol_path_href)) { echo '<link rel="stylesheet" href="' . $estilo_rol_path_href . '?v=' . time() . '">'; }
    else if (file_exists($filesystem_project_root . "/" . $css_path_general_from_project_root)) { echo '<link rel="stylesheet" href="' . $base_href_to_project_root . $css_path_general_from_project_root . '?v=' . time() . '">'; }
    ?>
    <style>
        body { padding-top: 62px; }
        .navbar-custom-blue { background-color: rgb(0, 117, 201) !important; }
        .navbar-custom-blue .navbar-brand img { max-height: 45px; width: auto; margin-right: 8px; }
        .navbar-custom-blue .navbar-brand { display: flex; align-items: center; font-weight: 500; color: #ffffff; }
        .navbar-custom-blue .nav-link { color: #e0e0e0; padding: .6rem .8rem; margin: 0 .5rem; border-radius: .25rem; transition: background-color 0.2s ease, color 0.2s ease; font-size: 0.9rem; }
        .navbar-custom-blue .nav-link:hover,
        .navbar-custom-blue .nav-link:focus { color: #fff; background-color: rgba(255,255,255,.12); }
        .navbar-custom-blue .nav-link.active { color: #fff !important; font-weight: 600 !important; background-color: rgba(255,255,255,.20); }
        .navbar-custom-blue .nav-link.dropdown-toggle i.bi-person-circle { font-size: 1.7rem; vertical-align: middle; }
        .navbar-custom-blue .nav-link.dropdown-toggle { display: flex; align-items: center; padding-top: .4rem; padding-bottom: .4rem; }
        .navbar-custom-blue .navbar-toggler { border-color: rgba(255,255,255,.45); padding: .25rem .6rem;}
        .navbar-custom-blue .navbar-toggler-icon { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2.2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e"); width: 1.3em; height: 1.3em;}
        .navbar-custom-blue .dropdown-menu { background-color: #005A9C; border: 1px solid rgba(0,0,0,0.15); border-radius: .3rem; margin-top: 0.8rem !important; font-size: .9rem; min-width: auto; padding-top: 0; padding-bottom: 0; box-shadow: 0 .5rem 1rem rgba(0,0,0,.15); }
        .navbar-custom-blue .dropdown-menu.dropdown-menu-end { margin-top: .6rem !important; min-width: 220px; }
        .navbar-custom-blue .dropdown-header-custom { padding: .8rem 1.2rem .5rem 1.2rem; color: #ffffff; font-weight: bold; font-size: 1rem; background-color: transparent; pointer-events: none; }
        .navbar-custom-blue .dropdown-item { color: #f8f9fa; padding: .6rem 1.2rem; display: flex; align-items: center; font-size: 0.9rem; }
        .navbar-custom-blue .dropdown-item:hover,
        .navbar-custom-blue .dropdown-item:focus { color: #ffffff; background-color: rgba(255,255,255,.15); }
        .navbar-custom-blue .dropdown-item i.bi { margin-right: .8rem; font-size: 1rem; width: 1.3em; text-align: center; line-height: 1; }
        .navbar-custom-blue .dropdown-divider { border-top: 1px solid rgba(255,255,255,.2); margin: 0; }
        .navbar-custom-blue .dropdown-item.active,
        .navbar-custom-blue .dropdown-item:active { color: #fff !important; font-weight: 600 !important; background-color: rgba(255,255,255,.20); }

        .navbar-custom-blue .dropdown-menu .dropdown-submenu { position: relative; }
        .navbar-custom-blue .dropdown-menu .dropdown-submenu > .dropdown-item.dropdown-toggle-submenu::after {
            display: inline-block; width: 0; height: 0; margin-left: .355em; vertical-align: .255em; content: "";
            border-top: .3em solid; border-right: .3em solid transparent; border-bottom: 0; border-left: .3em solid transparent;
            float: right; margin-top: .4em;
        }
         .navbar-custom-blue .dropdown-menu .dropdown-submenu > .dropdown-item.dropdown-toggle-submenu.show::after { transform: rotate(180deg); }
        .navbar-custom-blue .dropdown-menu .dropdown-submenu > .dropdown-menu-submenu {
            display: none; list-style: none; padding-left: 0; margin-left: 0; background-color: #004c86; width: 100%;
        }
        .navbar-custom-blue .dropdown-menu .dropdown-submenu > .dropdown-menu-submenu .dropdown-item { padding-left: 2rem; }
        .navbar-custom-blue .dropdown-menu .dropdown-submenu.show > .dropdown-menu-submenu { display: block; }
        .navbar-custom-blue .nav-link.dropdown-toggle::after {
            display: inline-block; margin-left: .255em; vertical-align: .255em; content: "";
            border-top: .3em solid; border-right: .3em solid transparent; border-bottom: 0; border-left: .3em solid transparent;
        }
        .navbar-custom-blue li.dropdown > a.dropdown-toggle[href="#navbarDropdownUserMenu"]::after,
        .navbar-custom-blue .nav-link.dropdown-toggle#navbarDropdownUserMenu::after { display: none; }
        .navbar-custom-blue .dropdown-menu .dropdown-submenu.active-parent > .dropdown-item.dropdown-toggle-submenu {
             background-color: rgba(255,255,255,.10); color: #fff;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-custom-blue navbar-dark py-0 fixed-top">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?php echo $base_href_to_project_root; ?>inicio.php"><img src="<?php echo $path_to_img_folder_href; ?>Logo.png" alt="SaludConnect Logo"></a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavSaludConnect"><span class="navbar-toggler-icon"></span></button>
                <div class="collapse navbar-collapse" id="navbarNavSaludConnect">
                    <ul class="navbar-nav ms-auto align-items-center">
                        <?php if ($rol_usuario == 1): ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'inicio.php' && $path_inside_project_trimmed === 'admi') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/inicio.php">Inicio</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'crear_usu.php' && strpos($path_inside_project_trimmed, 'admi/gestion_crear') === 0 && !$es_pagina_gestion_sistema_activa) ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_crear/crear_usu.php">Crear Usuario</a></li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo (strpos($path_inside_project_trimmed, 'admi/gestion_entidades') === 0 && in_array($currentPage, $paginas_gestion_entidades)) ? 'active' : ''; ?>" href="#" id="navbarDropdownEntidades" role="button" data-bs-toggle="dropdown" aria-expanded="false">Gestion Entidades</a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdownEntidades">
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'ver_entidades.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_entidades/ver_entidades.php">Ver Entidades</a></li>
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'crear_entidad.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_entidades/crear_entidad.php">Crear Entidad</a></li>
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'crear_alianza.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_entidades/crear_alianza.php">Crear Alianza</a></li>
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'lista_alianzas.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_entidades/lista_alianzas.php">Ver Alianzas</a></li>
                                </ul>
                            </li>
                             <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo (strpos($path_inside_project_trimmed, 'admi/gestion_pacientes') === 0 && in_array($currentPage, $paginas_gestion_pacientes)) ? 'active' : ''; ?>" href="#" id="navbarDropdownGestionPacientes" role="button" data-bs-toggle="dropdown" aria-expanded="false">Gestion Pacientes</a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdownGestionPacientes">
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'lista_pacientes.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_pacientes/lista_pacientes.php">Lista Pacientes</a></li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo (strpos($path_inside_project_trimmed, 'admi/gestion_farmaceutas') === 0 && in_array($currentPage, $paginas_gestion_farmaceutas)) ? 'active' : ''; ?>" href="#" id="navbarDropdownGestionFarmaceutas" role="button" data-bs-toggle="dropdown" aria-expanded="false">Gestion Farmaceutas</a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdownGestionFarmaceutas">
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'lista_farmaceutas.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_farmaceutas/lista_farmaceutas.php">Lista Farmaceutas</a></li>
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'asignar_farmaceuta.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_farmaceutas/asignar_farmaceuta.php">Asignar Farmaceuta</a></li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo (strpos($path_inside_project_trimmed, 'admi/gestion_medicos') === 0 && in_array($currentPage, $paginas_gestion_medicos)) ? 'active' : ''; ?>" href="#" id="navbarDropdownGestionMedicos" role="button" data-bs-toggle="dropdown" aria-expanded="false">Gestion Medicos</a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdownGestionMedicos">
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'lista_medicos.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_medicos/lista_medicos.php">Lista Medicos</a></li>
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'crear_horario.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_medicos/crear_horario.php">Crear Horario</a></li>
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'ver_horarios.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_medicos/ver_horarios.php">Ver Horarios</a></li>
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'asignar_ips_medico.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_medicos/asignar_ips_medico.php">Asignar IPS a Médico</a></li>
                                </ul>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo $es_pagina_gestion_sistema_activa ? 'active' : ''; ?>" href="#" id="navbarDropdownGestionSistema" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Gestión Sistema
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdownGestionSistema">
                                    <li class="dropdown-submenu <?php echo $es_pagina_config_geo_activa ? 'active-parent' : ''; ?>">
                                        <a class="dropdown-item dropdown-toggle-submenu <?php echo $es_pagina_config_geo_activa ? 'active' : ''; ?>" href="#" id="configGeoSubmenuToggle" aria-expanded="false">Config. Geográfica</a>
                                        <ul class="dropdown-menu-submenu" aria-labelledby="configGeoSubmenuToggle">
                                            <li><a class="dropdown-item <?php echo ($currentPage === 'crear_departamento.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_crear/geografica/crear_departamento.php">Insertar Departamento</a></li>
                                            <li><a class="dropdown-item <?php echo ($currentPage === 'crear_municipio.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_crear/geografica/crear_municipio.php">Insertar Municipio</a></li>
                                            <li><a class="dropdown-item <?php echo ($currentPage === 'crear_barrios.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_crear/geografica/crear_barrios.php">Insertar Barrio</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item <?php echo ($currentPage === 'ver_departamentos.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_crear/geografica/ver_departamentos.php">Ver Departamentos</a></li>
                                            <li><a class="dropdown-item <?php echo ($currentPage === 'ver_municipios.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_crear/geografica/ver_municipios.php">Ver Municipios</a></li>
                                            <li><a class="dropdown-item <?php echo ($currentPage === 'ver_barrios.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_crear/geografica/ver_barrios.php">Ver Barrios</a></li>
                                        </ul>
                                    </li>
                                    <li class="dropdown-submenu <?php echo $es_pagina_gestion_enf_activa ? 'active-parent' : ''; ?>">
                                        <a class="dropdown-item dropdown-toggle-submenu <?php echo $es_pagina_gestion_enf_activa ? 'active' : ''; ?>" href="#" id="enfermedadesSubmenuToggle" aria-expanded="false">Gestión Enfermedades</a>
                                        <ul class="dropdown-menu-submenu" aria-labelledby="enfermedadesSubmenuToggle">
                                            <li><a class="dropdown-item <?php echo ($currentPage === 'crear_tipo_enfermedad.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_crear/enfermedades/crear_tipo_enfermedad.php">Insertar Tipo Enfermedad</a></li>
                                            <li><a class="dropdown-item <?php echo ($currentPage === 'crear_enfermedad.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_crear/enfermedades/crear_enfermedad.php">Insertar Enfermedad</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item <?php echo ($currentPage === 'ver_tipos_enfermedad.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_crear/enfermedades/ver_tipos_enfermedad.php">Ver Tipos Enfermedad</a></li>
                                            <li><a class="dropdown-item <?php echo ($currentPage === 'ver_enfermedades.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_crear/enfermedades/ver_enfermedades.php">Ver Enfermedades</a></li>
                                        </ul>
                                    </li>
                                    <li class="dropdown-submenu <?php echo $es_pagina_gestion_roles_activa ? 'active-parent' : ''; ?>">
                                        <a class="dropdown-item dropdown-toggle-submenu <?php echo $es_pagina_gestion_roles_activa ? 'active' : ''; ?>" href="#" id="rolesSubmenuToggle" aria-expanded="false">Gestión Roles</a>
                                        <ul class="dropdown-menu-submenu" aria-labelledby="rolesSubmenuToggle">
                                            <li><a class="dropdown-item <?php echo ($currentPage === 'crear_rol.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_crear/roles/crear_rol.php">Insertar Rol</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item <?php echo ($currentPage === 'ver_roles.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>admi/gestion_crear/roles/ver_roles.php">Ver Roles</a></li>
                                        </ul>
                                    </li>
                                </ul>
                            </li>

                        <?php elseif ($rol_usuario == 2): ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'inicio.php' && $path_inside_project_trimmed === '') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>inicio.php">Inicio</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'citas.php' && $path_inside_project_trimmed === 'paci') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>paci/citas.php">Citas Médicas</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'mis_citas.php' && $path_inside_project_trimmed === 'paci') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>paci/mis_citas.php">Mis Citas</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'mis_medicamentos.php' && $path_inside_project_trimmed === 'paci') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>paci/mis_medicamentos.php">Mis Medicamentos</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'mis_pedidos.php' && $path_inside_project_trimmed === 'paci') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>paci/mis_pedidos.php">Mis pedidos</a></li>
                        
                        <?php elseif ($rol_usuario == 3): ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'inicio.php' && $path_inside_project_trimmed === 'farma') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>farma/inicio.php">Inicio</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'lista_pacientes.php' && $path_inside_project_trimmed === 'farma') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>farma/lista_pacientes.php">Lista pacientes</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'entregas_pendientes.php' && strpos($path_inside_project_trimmed, 'farma/entregar') === 0) ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>farma/entregar/entregas_pendientes.php">Entregas pendientes</a></li>
                            
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo (strpos($path_inside_project_trimmed, 'farma/inventario') === 0 && in_array($currentPage, $paginas_farma_gestion_inventario)) ? 'active' : ''; ?>" href="#" id="navbarDropdownInventario" role="button" data-bs-toggle="dropdown" aria-expanded="false">Gestión Inventario</a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdownInventario">
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'inventario.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>farma/inventario/inventario.php">Ver Inventario</a></li>
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'insertar_inventario.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>farma/inventario/insertar_inventario.php">Insertar en Inventario</a></li>
                                </ul>
                            </li>
                            
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo (strpos($path_inside_project_trimmed, 'farma/crear') === 0 && in_array($currentPage, $paginas_farma_gestion_medicamentos)) ? 'active' : ''; ?>" href="#" id="navbarDropdownMedicamentos" role="button" data-bs-toggle="dropdown" aria-expanded="false">Gestión Medicamentos</a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdownMedicamentos">
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'crear_medicamento.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>farma/crear/crear_medicamento.php">Crear Medicamento</a></li>
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'ver_medicamento.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>farma/crear/ver_medicamento.php">Ver Medicamentos</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'crear_tipo_medi.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>farma/crear/crear_tipo_medi.php">Crear Tipo Medicamento</a></li>
                                    <li><a class="dropdown-item <?php echo ($currentPage === 'ver_tipo_medi.php') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>farma/crear/ver_tipo_medi.php">Ver Tipos de Medicamento</a></li>
                                </ul>
                            </li>

                        <?php elseif ($rol_usuario == 4): ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'inicio.php' && $path_inside_project_trimmed === 'medi') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>medi/inicio.php">Inicio</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'citas_hoy.php' && $path_inside_project_trimmed === 'medi') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>medi/citas_hoy.php">Citas</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'crear_orden.php' && $path_inside_project_trimmed === 'medi') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>medi/crear_orden.php">Crear ordenes</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'ver_ordenes.php' && $path_inside_project_trimmed === 'medi') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>medi/ver_ordenes.php">Ver ordenes</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'historial_paci.php' && $path_inside_project_trimmed === 'medi') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>medi/historial_paci.php">Historial paciente</a></li>
                        <?php endif; ?>

                        <?php if ($rol_usuario): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUserMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-person-circle"></i></a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownUserMenu">
                                    <li><div class="dropdown-header-custom" id="menuUserNameDisplay"><?php echo $nombre_usuario_display; ?></div></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" id="abrirPerfilModalLink"><i class="bi bi-person-vcard"></i> Mi Perfil</a></li>
                                    <li><a class="dropdown-item" href="<?php echo $path_to_include_folder_href; ?>salir.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'inicio_sesion.php' && $path_inside_project_trimmed === '') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>inicio_sesion.php"><i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'registro.php' && $path_inside_project_trimmed === '') ? 'active' : ''; ?>" href="<?php echo $base_href_to_project_root; ?>registro.php"><i class="bi bi-person-plus me-1"></i>Registrarse</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <div id="perfilModalPlaceholderContainerGlobal"></div>

    <script src="<?php echo $path_to_js_folder_href; ?>editar_perfil.js?v=<?php echo time(); ?>"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const perfilLink = document.getElementById('abrirPerfilModalLink');
        const modalGlobalContainer = document.getElementById('perfilModalPlaceholderContainerGlobal');
        let finalFormValidator = null;

        if (perfilLink && modalGlobalContainer) {
            perfilLink.addEventListener('click', function(e) {
                e.preventDefault();
                modalGlobalContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index: 2000;"><div class="spinner-border text-light" role="status"></div></div>';
                fetch('<?php echo $path_to_include_folder_href; ?>modal_perfil.php')
                    .then(response => {
                        if (!response.ok) throw new Error('Fetch modal_perfil.php: ' + response.status + ' ' + response.statusText);
                        return response.text();
                    })
                    .then(modalHtml => {
                        modalGlobalContainer.innerHTML = modalHtml;
                        const perfilModalElement = document.getElementById('userProfileModal');
                        if (perfilModalElement) {
                            try {
                                const perfilModal = new bootstrap.Modal(perfilModalElement);
                                perfilModal.show();
                                if (typeof inicializarValidacionesPerfil === "function") {
                                    finalFormValidator = inicializarValidacionesPerfil();
                                }
                                attachProfileFormSubmitHandler();
                                attachImagePreviewListener();
                            } catch (bootstrapError) {
                                modalGlobalContainer.innerHTML = '<div class="alert alert-danger fixed-top">Error inicializando modal: ' + bootstrapError.message + '</div>';
                            }
                            perfilModalElement.addEventListener('hidden.bs.modal', function () {
                                modalGlobalContainer.innerHTML = '';
                                finalFormValidator = null;
                            });
                        } else {
                             modalGlobalContainer.innerHTML = '<div class="alert alert-danger fixed-top">Error: ID userProfileModal no encontrado.</div>';
                        }
                    })
                    .catch(error => {
                        modalGlobalContainer.innerHTML = '<div class="alert alert-danger fixed-top">Error cargar perfil: ' + error.message + '</div>';
                    });
            });
        }

        function attachProfileFormSubmitHandler() {
            const profileForm = document.getElementById('profileFormModalActual');
            if (profileForm) {
                profileForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    if (finalFormValidator && !finalFormValidator()) {
                        const globalMessageDiv = document.getElementById('modalUpdateMessage');
                        if(globalMessageDiv){
                            globalMessageDiv.innerHTML = 'Por favor, corrija los errores del formulario.';
                            globalMessageDiv.className = 'mt-3 alert alert-warning';
                        }
                        return false;
                    }
                    handleProfileUpdateSubmitActual(profileForm);
                });
            }
        }

        function attachImagePreviewListener(){
            const fotoInput = document.getElementById('foto_usu_modal');
            if(fotoInput) { fotoInput.addEventListener('change', previewImageModalInForm); }
        }

        function previewImageModalInForm(event) {
            const reader = new FileReader();
            const imagePreview = document.getElementById('imagePreviewModal');
            if (event.target.files && event.target.files[0] && imagePreview) {
                reader.onload = function(){ imagePreview.src = reader.result; };
                reader.readAsDataURL(event.target.files[0]);
            }
        }

        function handleProfileUpdateSubmitActual(formElement) {
            const formData = new FormData(formElement);
            const messageDiv = document.getElementById('modalUpdateMessage');
            const saveButton = document.getElementById('saveProfileChangesButton');
            if(saveButton) saveButton.disabled = true;
            if(messageDiv) messageDiv.innerHTML = '<div class="d-flex align-items-center"><div class="spinner-border spinner-border-sm me-2"></div><span>Actualizando...</span></div>';
            fetch('<?php echo $path_to_include_folder_href; ?>mi_perfil.php', { method: 'POST', body: formData })
            .then(response => response.text().then(text => {
                try { return JSON.parse(text); } catch (e) { throw new Error("Respuesta servidor (update) no JSON.");}
            }))
            .then(data => {
                if (messageDiv) { messageDiv.className = 'mt-3 ' + (data.success ? 'alert alert-success' : 'alert alert-danger'); messageDiv.textContent = data.message; }
                if (data.success) {
                    if (data.new_nom_usu) {
                        const userNameDisplayOnMenu = document.getElementById('menuUserNameDisplay');
                        if (userNameDisplayOnMenu) { userNameDisplayOnMenu.textContent = data.new_nom_usu; }
                    }
                    if (data.new_foto_usu_path_for_modal) {
                        const imagePreviewInModal = document.getElementById('imagePreviewModal');
                        if(imagePreviewInModal) { imagePreviewInModal.src = data.new_foto_usu_path_for_modal + '?' + new Date().getTime(); }
                    }
                    if(data.success){
                        setTimeout(() => {
                            const perfilModalEl = document.getElementById('userProfileModal');
                            if (perfilModalEl) {
                                const perfilModalInstance = bootstrap.Modal.getInstance(perfilModalEl);
                                if (perfilModalInstance) { perfilModalInstance.hide(); }
                            }
                            if(messageDiv) messageDiv.innerHTML = '';
                        }, 2500);
                    } else {
                         if(saveButton) saveButton.disabled = false;
                    }
                } else {
                    if(saveButton) saveButton.disabled = false;
                }
            })
            .catch(error => {
                if(saveButton) saveButton.disabled = false;
                if (messageDiv) { messageDiv.className = 'mt-3 alert alert-danger'; messageDiv.textContent = error.message || 'Error procesar respuesta.'; }
            });
        }

        document.querySelectorAll('.navbar-custom-blue .dropdown-submenu > a.dropdown-toggle-submenu').forEach(function(element){
            element.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                let parentLi = this.parentElement;
                let submenu = parentLi.querySelector('.dropdown-menu-submenu');
                if (!parentLi.parentElement.classList.contains('dropdown-menu-submenu')) {
                    parentLi.parentElement.querySelectorAll('.dropdown-submenu.show').forEach(function(openSubLi){
                        if (openSubLi !== parentLi) {
                            openSubLi.classList.remove('show');
                            openSubLi.querySelector('.dropdown-menu-submenu').style.display = 'none';
                             openSubLi.querySelector('a.dropdown-toggle-submenu').classList.remove('show');
                        }
                    });
                }
                if (submenu) {
                    if (submenu.style.display === 'block') {
                        submenu.style.display = 'none';
                        parentLi.classList.remove('show');
                        this.classList.remove('show');
                    } else {
                        submenu.style.display = 'block';
                        parentLi.classList.add('show');
                        this.classList.add('show');
                    }
                }
            });
        });

        document.addEventListener('click', function(event) {
            const navbar = document.getElementById('navbarNavSaludConnect');
            if (navbar && !navbar.contains(event.target)) {
                let openDropdowns = document.querySelectorAll('.navbar-nav .dropdown-menu.show');
                openDropdowns.forEach(function(dropdown) {
                    let toggler = dropdown.previousElementSibling;
                    if (toggler && toggler.classList.contains('dropdown-toggle')) {
                         bootstrap.Dropdown.getInstance(toggler)?.hide();
                    }
                });
                document.querySelectorAll('.navbar-custom-blue .dropdown-submenu.show').forEach(function(openSubLi){
                    openSubLi.classList.remove('show');
                    openSubLi.querySelector('.dropdown-menu-submenu').style.display = 'none';
                    openSubLi.querySelector('a.dropdown-toggle-submenu').classList.remove('show');
                });
            }
        });

        function applyAutocompleteOff(element) {
            element.setAttribute('autocomplete', 'off');
            element.setAttribute('autocorrect', 'off');
            element.setAttribute('autocapitalize', 'off');
            element.setAttribute('spellcheck', 'false');

            if (element.type && element.type.toLowerCase() === 'password') {
                element.setAttribute('autocomplete', 'new-password');
            } else {
                element.setAttribute('autocomplete', 'nope-' + Math.random().toString(36).substring(2, 15));
            }
        }

        function disableAllAutocomplete() {
            const forms = document.getElementsByTagName('form');
            for (let i = 0; i < forms.length; i++) {
                forms[i].setAttribute('autocomplete', 'off');
                 forms[i].setAttribute('autocorrect', 'off');
                 forms[i].setAttribute('autocapitalize', 'off');
                 forms[i].setAttribute('spellcheck', 'false');
            }
            const inputs = document.getElementsByTagName('input');
            for (let i = 0; i < inputs.length; i++) {
                applyAutocompleteOff(inputs[i]);
            }
            const selects = document.getElementsByTagName('select');
            for (let i = 0; i < selects.length; i++) {
                applyAutocompleteOff(selects[i]);
            }
             const textareas = document.getElementsByTagName('textarea');
            for (let i = 0; i < textareas.length; i++) {
                applyAutocompleteOff(textareas[i]);
            }
        }

        disableAllAutocomplete();

        const observer = new MutationObserver((mutationsList, observerInstance) => {
            for(const mutation of mutationsList) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            if (node.tagName === 'FORM') {
                                node.setAttribute('autocomplete', 'off');
                                node.setAttribute('autocorrect', 'off');
                                node.setAttribute('autocapitalize', 'off');
                                node.setAttribute('spellcheck', 'false');
                            }
                            const newForms = node.getElementsByTagName ? node.getElementsByTagName('form') : [];
                            for (let i = 0; i < newForms.length; i++) {
                                newForms[i].setAttribute('autocomplete', 'off');
                                newForms[i].setAttribute('autocorrect', 'off');
                                newForms[i].setAttribute('autocapitalize', 'off');
                                newForms[i].setAttribute('spellcheck', 'false');
                            }
                            const newElements = node.querySelectorAll ? node.querySelectorAll('input, select, textarea') : [];
                            newElements.forEach(el => applyAutocompleteOff(el));

                            if (node.matches && node.matches('input, select, textarea')) {
                                applyAutocompleteOff(node);
                            }
                        }
                    });
                }
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
        
        setTimeout(disableAllAutocomplete, 500);
        setTimeout(disableAllAutocomplete, 1500);

    });
    </script>
</body>