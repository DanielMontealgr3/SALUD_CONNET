<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once '../include/conexion.php';

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true ||
    !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 3 ||
    !isset($_SESSION['nombre_usuario']) ||
    !isset($_SESSION['doc_usu'])
) {
    header('Location: ../inicio_sesion.php?error=nosession');
    exit;
}

$nombre_usuario = $_SESSION['nombre_usuario'];
$documento_farmaceuta = $_SESSION['doc_usu'];
$pageTitle = "Inicio Farmaceuta";

$asignacion_activa = false;
$nombre_farmacia_asignada = "";
$nit_farmacia_asignada = "";

if ($documento_farmaceuta && isset($con) && $con instanceof PDO) {
    $sql_asignacion = "SELECT f.nom_farm, af.nit_farma, af.id_estado
                       FROM asignacion_farmaceuta af
                       JOIN farmacias f ON af.nit_farma = f.nit_farm
                       WHERE af.doc_farma = :doc_farma
                       LIMIT 1";
    try {
        $stmt_asignacion = $con->prepare($sql_asignacion);
        $stmt_asignacion->bindParam(':doc_farma', $documento_farmaceuta, PDO::PARAM_STR);
        $stmt_asignacion->execute();
        $fila_asignacion = $stmt_asignacion->fetch(PDO::FETCH_ASSOC);

        if ($fila_asignacion) {
            if ($fila_asignacion['id_estado'] == 1) {
                $asignacion_activa = true;
                $nombre_farmacia_asignada = $fila_asignacion['nom_farm'];
                $nit_farmacia_asignada = $fila_asignacion['nit_farma'];
            } else {
                $asignacion_activa = false;
                error_log("Farmaceuta $documento_farmaceuta: Asignación encontrada (NIT: " . ($fila_asignacion['nit_farma'] ?? 'N/A') . ") pero con id_estado = " . ($fila_asignacion['id_estado'] ?? 'N/A'));
            }
        } else {
            $asignacion_activa = false;
            error_log("Farmaceuta $documento_farmaceuta: No se encontró ninguna asignación.");
        }
    } catch (PDOException $e) {
        $asignacion_activa = false;
        error_log("Error en consulta de asignación farmaceuta para $documento_farmaceuta: " . $e->getMessage());
    }
} else {
    if (!$documento_farmaceuta) {
        error_log("Error: Documento del farmaceuta no disponible en sesión para consulta de asignación.");
    }
    if (!(isset($con) && $con instanceof PDO)) {
        error_log("Error: La conexión PDO (\$con) no está disponible para consulta de asignación en farma/inicio.php.");
    }
    $asignacion_activa = false;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
     <link rel="icon" type="image/png" href="../img/loguito.png">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php include '../include/menu.php'; ?>

    <main id="contenido-principal-inicio" class="flex-grow-1 d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="contenedor-bienvenida text-center">
                <?php if ($asignacion_activa) : ?>
                    <h1 class="mensaje-bienvenida-admin display-5 mb-3">
                        Bienvenido Farmaceuta, <strong class="nombre-admin-bienvenida"><?php echo htmlspecialchars($nombre_usuario); ?></strong>
                    </h1>
                    <p class="submensaje-bienvenida-admin lead mb-4">
                        Acá podrás gestionar la farmacia: <strong><?php echo htmlspecialchars($nombre_farmacia_asignada); ?></strong>.
                    </p>
                    <div class="contenedor-imagen-rol mb-4">
                        <img src="../img/bodyadmi.png" alt="Imagen Farmaceuta" class="imagen-rol img-fluid rounded" style="max-width: 400px; height: auto;">
                    </div>
                <?php else : ?>
                    <h1 class="mensaje-bienvenida-admin display-5 mb-3 text-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>Atención
                    </h1>
                    <p class="submensaje-bienvenida-admin lead mb-4">
                        Estimado/a <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>, actualmente no tiene una farmacia activa asignada.
                        <br>Para acceder a las funcionalidades, por favor, comuníquese con el administrador del sistema.
                    </p>
                    <img src="../img/bloqueo_acceso.png" alt="Acceso Restringido" class="imagen-rol img-fluid rounded mb-3" style="max-width: 200px; height: auto; border:none;">
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>


    <?php if (!$asignacion_activa) : ?>
    <script>
        // Script para deshabilitar el menú si la asignación no está activa
        document.addEventListener('DOMContentLoaded', function () {
            const userMenuToggleId = 'navbarDropdownUserMenu';
            const userMenuDropdown = document.querySelector('.dropdown-menu[aria-labelledby="' + userMenuToggleId + '"]');

            const allNavLinksAndItems = document.querySelectorAll('.navbar-nav .nav-link, .navbar-nav .dropdown-item');
            const allDropdownToggles = document.querySelectorAll('.navbar-nav > .nav-item > .dropdown-toggle');


            allNavLinksAndItems.forEach(link => {
                let partOfUserMenu = false;
                if (link.id === userMenuToggleId) {
                    partOfUserMenu = true;
                } else if (userMenuDropdown && userMenuDropdown.contains(link)) {
                    partOfUserMenu = true;
                }

                if (!partOfUserMenu) {
                    link.classList.add('disabled');
                    link.setAttribute('aria-disabled', 'true');
                    link.setAttribute('tabindex', '-1');
                    link.style.pointerEvents = 'none';
                    if (link.hasAttribute('data-bs-toggle')) {
                         link.removeAttribute('data-bs-toggle');
                    }
                }
            });

            allDropdownToggles.forEach(toggle => {
                if (toggle.id !== userMenuToggleId) {
                    toggle.classList.add('disabled');
                    toggle.setAttribute('aria-disabled', 'true');
                    toggle.setAttribute('tabindex', '-1');
                    toggle.style.pointerEvents = 'none';
                    if (toggle.hasAttribute('data-bs-toggle')) {
                        toggle.removeAttribute('data-bs-toggle');
                    }
                }
            });
        });
    </script>
    <?php endif; ?>

</body>
</html>