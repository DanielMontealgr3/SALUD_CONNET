<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) { session_start(); }
$currentPage = basename($_SERVER['PHP_SELF']);
$rol_usuario = isset($_SESSION['id_rol']) ? $_SESSION['id_rol'] : null;
$nombre_usuario_display = isset($_SESSION['nombre_usuario']) ? htmlspecialchars($_SESSION['nombre_usuario']) : 'Usuario';
$base_path_role_pages = "";
$path_to_include_folder_php_context = "../include/";
$path_to_img_folder_php_context = "../img/";
$path_to_root_files_php_context = "../";
$path_to_js_folder_php_context = "../js/";
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú - SaludConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="<?php echo $path_to_img_folder_php_context; ?>loguito.png">
    <?php
    $estilo_rol_path = "";
    if ($rol_usuario == 1 && file_exists('../admi/estilo.css')) { $estilo_rol_path = '../admi/estilo.css'; }
    elseif ($rol_usuario == 2 && file_exists('../paci/estilos.css')) { $estilo_rol_path = '../paci/estilos.css'; }
    elseif ($rol_usuario == 3 && file_exists('../farma/estilos.css')) { $estilo_rol_path = '../farma/estilos.css'; }
    elseif ($rol_usuario == 4 && file_exists('../medi/estilos.css')) { $estilo_rol_path = '../medi/estilos.css'; }
    if (!empty($estilo_rol_path)) { echo '<link rel="stylesheet" href="' . $estilo_rol_path . '?v=' . time() . '">'; }
    ?>
    <style>
        .navbar-custom-blue .navbar-brand img { max-height: 45px; width: auto; margin-right: 8px; }
        .navbar-custom-blue .navbar-brand { display: flex; align-items: center; font-weight: 500; color: #ffffff; }
        .navbar-custom-blue .nav-link { color: #e0e0e0; padding: .6rem .8rem; margin: 0 .1rem; border-radius: .25rem; transition: background-color 0.2s ease, color 0.2s ease; }
        .navbar-custom-blue .nav-link:hover,
        .navbar-custom-blue .nav-link:focus { color: #fff; background-color: rgba(255,255,255,.12); }
        .navbar-custom-blue .nav-link.active { color: #fff !important; font-weight: 600 !important; background-color: rgba(255,255,255,.20); }
        .navbar-custom-blue .nav-link.dropdown-toggle i.bi-person-circle { font-size: 1.7rem; vertical-align: middle; }
        .navbar-custom-blue .nav-link.dropdown-toggle { display: flex; align-items: center; padding-top: .4rem; padding-bottom: .4rem; }
        .navbar-custom-blue .dropdown-toggle::after { display: none; }
        .navbar-custom-blue .navbar-toggler { border-color: rgba(255,255,255,.45); }
        .navbar-custom-blue .navbar-toggler-icon { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2.2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e"); }
        .navbar-custom-blue .dropdown-menu { background-color: #005A9C; border: 1px solid rgba(0,0,0,0.15); border-radius: .3rem; margin-top: .6rem !important; font-size: .9rem; min-width: 220px; padding-top: 0; padding-bottom: 0; box-shadow: 0 .5rem 1rem rgba(0,0,0,.15); }
        .navbar-custom-blue .dropdown-header-custom { padding: .8rem 1.2rem .5rem 1.2rem; color: #ffffff; font-weight: bold; font-size: 1rem; background-color: transparent; pointer-events: none; }
        .navbar-custom-blue .dropdown-item { color: #f8f9fa; padding: .6rem 1.2rem; display: flex; align-items: center; font-size: 0.9rem; }
        .navbar-custom-blue .dropdown-item:hover,
        .navbar-custom-blue .dropdown-item:focus { color: #ffffff; background-color: rgba(255,255,255,.15); }
        .navbar-custom-blue .dropdown-item i.bi { margin-right: .8rem; font-size: 1rem; width: 1.3em; text-align: center; line-height: 1; }
        .navbar-custom-blue .dropdown-divider { border-top: 1px solid rgba(255,255,255,.2); margin: 0; }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-custom-blue navbar-dark py-0 fixed-top">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?php echo $base_path_role_pages; ?>inicio.php"><img src="<?php echo $path_to_img_folder_php_context; ?>Logo.png" alt="SaludConnect Logo"></a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavSaludConnect"><span class="navbar-toggler-icon"></span></button>
                <div class="collapse navbar-collapse" id="navbarNavSaludConnect">
                    <ul class="navbar-nav ms-auto align-items-center">
                        <?php if ($rol_usuario == 1): ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'inicio.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>inicio.php">Inicio</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'ver_usu.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>ver_usu.php">Usuarios</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'crear_usu.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>crear_usu.php">Crear Usuario</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'ver_entidades.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>ver_entidades.php">Ver Entidades</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'crear_entidad.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>crear_entidad.php">Crear Entidad</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'crear_alianza.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>crear_alianza.php">Alianzas</a></li>
                        <?php elseif ($rol_usuario == 2): ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'inicio.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>inicio.php">Inicio</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'citas.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>citas.php">Citas Médicas</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'mis_citas.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>mis_citas.php">Mis Citas</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'mis_medicamentos.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>mis_medicamentos.php">Mis Medicamentos</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'mis_pedidos.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>mis_pedidos.php">Mis pedidos</a></li>
                        <?php elseif ($rol_usuario == 3): ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'inicio.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>inicio.php">Inicio</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'pedidos_pendientes.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>pedidos_pendientes.php">Pedidos</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'inventario_farma.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>inventario_farma.php">Inventario</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'reportes_farma.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>reportes_farma.php">Reportes</a></li>
                        <?php elseif ($rol_usuario == 4): ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'inicio.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>inicio.php">Inicio</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'citas.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>citas.php.php">Citas</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'crear_orden.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>crear_orden.php">Crear ordenes</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'ver_ordenes.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>ver_ordenes.php">Ver ordenes</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'historial.php') ? 'active' : ''; ?>" href="<?php echo $base_path_role_pages; ?>historial.php">Historial paciente</a></li>
                        <?php endif; ?>
                        <?php if ($rol_usuario): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUserMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-person-circle"></i></a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownUserMenu">
                                    <li><div class="dropdown-header-custom" id="menuUserNameDisplay"><?php echo $nombre_usuario_display; ?></div></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" id="abrirPerfilModalLink"><i class="bi bi-person-vcard"></i> Mi Perfil</a></li>
                                    <li><a class="dropdown-item" href="<?php echo $path_to_include_folder_php_context; ?>salir.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'inicio_sesion.php') ? 'active' : ''; ?>" href="<?php echo $path_to_root_files_php_context; ?>inicio_sesion.php"><i class="bi bi-box-arrow-in-right me-1"></i>Iniciar Sesión</a></li>
                            <li class="nav-item"><a class="nav-link <?php echo ($currentPage === 'registro.php') ? 'active' : ''; ?>" href="<?php echo $path_to_root_files_php_context; ?>registro.php"><i class="bi bi-person-plus me-1"></i>Registrarse</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    <div id="perfilModalPlaceholderContainerGlobal"></div>

    <script src="<?php echo $path_to_js_folder_php_context; ?>editar_perfil.js?v=<?php echo time(); ?>"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const perfilLink = document.getElementById('abrirPerfilModalLink');
        const modalGlobalContainer = document.getElementById('perfilModalPlaceholderContainerGlobal');
        let finalFormValidator = null;

        if (perfilLink && modalGlobalContainer) {
            perfilLink.addEventListener('click', function(e) {
                e.preventDefault();
                modalGlobalContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index: 2000;"><div class="spinner-border text-light" role="status"></div></div>';

                fetch('../include/modal_perfil.php')
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
                                } else {
                                    console.error("Función inicializarValidacionesPerfil no encontrada.");
                                }
                                attachProfileFormSubmitHandler();
                                attachImagePreviewListener();

                            } catch (bootstrapError) {
                                console.error("Error Bootstrap modal:", bootstrapError);
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
                        console.error('Error cargar modal_perfil.php:', error);
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
            
            fetch('../include/mi_perfil.php', { method: 'POST', body: formData })
            .then(response => response.text().then(text => {
                try { return JSON.parse(text); } catch (e) { console.error("POST no JSON:", text); throw new Error("Respuesta servidor (update) no JSON.");}
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
                console.error('Error actualizar perfil:', error); if (messageDiv) { messageDiv.className = 'mt-3 alert alert-danger'; messageDiv.textContent = error.message || 'Error procesar respuesta.'; }
            });
        }
    });
    </script>
</body>