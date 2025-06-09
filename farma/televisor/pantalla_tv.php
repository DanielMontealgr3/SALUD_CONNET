<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

if (!isset($_SESSION['id_rol']) || !in_array($_SESSION['id_rol'], [1, 3])) {
    header('Location: ../../inicio_sesion.php?error=rol_invalido');
    exit;
}

$nit_farmacia_actual = $_SESSION['nit_farmacia_asignada_actual'] ?? null;
$nombre_farmacia = 'Farmacia';

if ($nit_farmacia_actual) {
    try {
        $db = new database();
        $con = $db->conectar();
        $stmt = $con->prepare("SELECT nom_farm FROM farmacias WHERE nit_farm = ?");
        $stmt->execute([$nit_farmacia_actual]);
        $nombre_farmacia = $stmt->fetchColumn() ?: 'Farmacia';
    } catch (Exception $e) { $nombre_farmacia = 'Farmacia'; }
} else {
    die("Error: No se pudo determinar la farmacia asignada.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantalla de Turnos - <?php echo htmlspecialchars($nombre_farmacia); ?></title>
    <link rel="stylesheet" href="estilos_tv.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" href="../../img/loguito.png">
</head>
<body>
    <div id="inicio-overlay">
        <button id="btn-iniciar-pantalla">▶ Iniciar Pantalla</button>
    </div>

    <div class="pantalla-tv">
        <header class="header-tv">
            <div class="header-center">
                <img src="../../img/Loguito.png" alt="Logo" class="logo-header">
                <h1 class="nombre-farmacia-header"><?php echo htmlspecialchars($nombre_farmacia); ?></h1>
            </div>
            <div id="reloj" class="reloj-container"></div>
        </header>
        <main class="contenido-tv">
            <div class="columna-llamando">
                <div class="columna-header">Llamando a</div>
                <div id="lista-llamando" class="lista-turnos"></div>
            </div>
            <div class="columna-atencion">
                <div class="columna-header">En Atención</div>
                <div id="lista-atencion" class="lista-turnos"></div>
            </div>
        </main>
    </div>
    <div id="modal-notificacion" class="modal-notificacion-overlay">
        <div class="modal-notificacion-contenido">
            <div class="modal-turno" id="modal-notificacion-turno"></div>
            <div class="modal-paciente" id="modal-notificacion-paciente"></div>
            <div class="modal-destino" id="modal-notificacion-destino"></div>
        </div>
    </div>
    <script src="../js/controlador_tv.js?v=<?php echo time(); ?>"></script>
</body>
</html>