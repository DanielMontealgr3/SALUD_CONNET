<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php'); 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2 || !isset($_SESSION['nombre_usuario'])) {
    header('Location: ../inicio_sesion.php'); 
    exit;
}

$nombre_usuario = $_SESSION['nombre_usuario'];
$pageTitle = "Inicio Paciente"; 

?>
<!DOCTYPE html>
<html lang="es">

<?php include '../include/menu.php'; ?> 

<body class="d-flex flex-column min-vh-100"> 

    <main id="contenido-principal" class="flex-grow-1 mb-5">
        <div class="container mt-5 pt-5"> 
            <div class="contenedor-bienvenida text-center">
                <h1 class="mensaje-bienvenida display-5 mb-4">
                    Bienvenido Paciente, <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>
                </h1>
                <div class="contenedor-imagen-rol">
                    <img src="../img/bodyadmi.png" alt="Imagen Paciente" class="imagen-rol img-fluid rounded" style="max-width: 500px; height: auto;"> 
                </div>
            </div>
        </div>
    </main>

    <?php include '../include/footer.php'; ?> 

</body>
</html>