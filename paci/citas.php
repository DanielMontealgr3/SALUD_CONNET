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
    <head>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    </head>

<?php include '../include/menu.php'; ?> 

<body class="d-flex flex-column min-vh-100 cuerpo_citas"> 

    <main id="contenido-principal" class="flex-grow-1 mb-5">
        <div class="container menu_citas">
            <h1 class="titulo_citas">Selecciona el tipo de cita:</h1>

            <div class="row row-cols-1 row-cols-md-3 g-4 mt-4">

                <!-- Cita Médica -->
                <div class="col">
                    <div class="card opcion_cita text-center">
                        <div class="card-body d-flex flex-column align-items-center">
                            <i class="bi bi-calendar-plus icono-opcion text-primary mb-2"></i>
                            <h5 class="card-title">Cita Médica</h5>
                            <a href="cita_medica.php" class="btn btn-primary mt-2">Ingresar</a>
                        </div>
                    </div>
                </div>

                <!-- Turno Examen -->
                <div class="col">
                    <div class="card opcion_cita text-center">
                        <div class="card-body d-flex flex-column align-items-center">
                            <i class="bi bi-clipboard2-pulse icono-opcion text-success mb-2"></i>
                            <h5 class="card-title">Turno Examen</h5>
                            <a href="#" class="btn btn-primary mt-2">Ingresar</a>
                        </div>
                    </div>
                </div>

                <!-- Turno Medicamentos -->
                <div class="col">
                    <div class="card opcion_cita text-center">
                        <div class="card-body d-flex flex-column align-items-center">
                            <i class="bi bi-capsule-pill icono-opcion text-danger mb-2"></i>
                            <h5 class="card-title">Turno Medicamentos</h5>
                            <a href="#" class="btn btn-primary mt-2">Ingresar</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>


    <?php include '../include/footer.php'; ?> 

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
