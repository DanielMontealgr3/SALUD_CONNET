<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php');
$conex = new database();
$con = $conex->conectar();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2 || !isset($_SESSION['nombre_usuario'])) {
    header('Location: ../inicio_sesion.php');
    exit;
}

$nombre_usuario = $_SESSION['nombre_usuario'];
$pageTitle = "Inicio Paciente";
$doc_usuario = $_SESSION['doc_usu'];


if (isset($_POST['enviar'])) {
    $medico = $_POST['medico_cita'];
    $fecha = $_POST['fecha_cita'];
    $hora = $_POST['hora_cita'];

    // Validar afiliación del usuario
    $consulta_validacion_afili = "SELECT * FROM afiliados WHERE doc_afiliado = '$doc_usuario' AND id_estado = 1";
    $resultado_validacion = $con->query($consulta_validacion_afili);

    if ($resultado_validacion->rowCount() == 0) {
        echo "<script>alert('Usuario no se encuentra afiliado, no puede solicitar citas');</script>";
        exit();
    }

    // Validar que los campos no estén vacíos
    if (empty($medico) || empty($fecha) || empty($hora)) {
        echo '<script>alert("Existen datos vacíos")</script>';
        echo '<script>window.location="cita_medica.php"</script>';
        exit();
    }

    // Verificar disponibilidad de la fecha y hora
    $verificar_cita = $con->prepare("SELECT * FROM citas WHERE doc_med = ? AND fecha_cita = ? AND hora_cita = ? AND id_est = 1");
    $verificar_cita->execute([$medico, $fecha, $hora]);

    if ($verificar_cita->rowCount() > 0) {
        echo '<script>alert("No hay disponibilidad en la fecha y hora seleccionadas.")</script>';
        echo '<script>window.location="cita_medica.php"</script>';
        exit();
    }

    // Insertar la cita
    $insert = $con->prepare("INSERT INTO citas (doc_pac, doc_med, fecha_cita, hora_cita, id_est) VALUES (?, ?, ?, ?, 1)");

    if ($insert->execute([$doc_usuario, $medico, $fecha, $hora])) {
        echo '<script>alert("Cita solicitada correctamente")</script>';
        echo '<script>window.location="citas_actuales.php"</script>';
    } else {
        echo '<script>alert("Error al solicitar la cita. Intenta nuevamente.")</script>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<?php include '../include/menu.php'; ?>

<body class="d-flex flex-column min-vh-100">
    <main id="contenido-principal" class="flex-grow-1 mb-5">
        <div class="contenedor_form_cita">
            <form class="dashboard-container FormularioAjax" method="POST" data-form="save" data-lang="es" autocomplete="off">
                <ul>
                    <div class="contenedor_medico_cita">
                        <h1>Formulario para sacar cita por medicina general</h1>

                        <!-- Municipio del usuario -->
                        <li>
                            <label for="municipio_cita" class="nav-link"><strong>Municipio</strong></label>
                            <select class="form-control" name="municipio_cita" id="municipio_cita" required>
                                <?php
                                // Obtener municipio del usuario
                                $sql_muni = $con->prepare("SELECT id_barrio FROM usuarios WHERE doc_usu = ?");
                                $sql_muni->execute([$_SESSION['doc_usu']]);
                                $municipio = $sql_muni->fetchColumn();
                                echo "<option value=\"$municipio\" selected>$municipio</option>";
                                ?>
                            </select>
                        </li>

                        <!-- IPS disponibles según municipio -->
                        <li>
                            <label for="ips_cita" class="nav-link"><strong>IPS</strong></label>
                            <select class="form-control" name="ips_cita" id="ips_cita" required>
                                <option value="">Seleccione una IPS</option>
                            </select>
                        </li>

                        <!-- Médicos disponibles según IPS -->
                        <li>
                            <label for="medico_cita" class="nav-link"><strong>Médico</strong></label>
                            <select class="form-control" name="medico_cita" id="medico_cita" required>
                                <option value="">Seleccione Médico</option>
                            </select>
                        </li>

                        <!-- Fecha de la cita -->
                        <li>
                            <label for="fecha_cita" class="nav-link"><strong>Fecha de la cita</strong></label>
                            <input type="date" class="form-control" name="fecha_cita" id="fecha_cita" required min="<?= date('Y-m-d') ?>">
                        </li>

                        <!-- Horas disponibles según fecha y médico -->
                        <li>
                            <label for="hora_cita" class="nav-link"><strong>Hora de la cita</strong></label>
                            <select class="form-control" name="hora_cita" id="hora_cita" required>
                                <option value="">Seleccione una hora</option>
                            </select>
                        </li>

                        <li>
                            <input type="submit" name="enviar" value="Solicitar">
                        </li>
                    </div>
                </ul>
            </form>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function(){
            // Cargar IPS según municipio
            cargarIPS();

            $('#municipio_cita').change(function(){
                cargarIPS();
            });

            $('#ips_cita').change(function(){
                cargarMedicos();
            });

            $('#fecha_cita').change(function(){
                actualizarHoras();
            });

            $('#medico_cita').change(function(){
                actualizarHoras();
            });
        });

        function cargarIPS(){
            let municipio = $('#municipio_cita').val();
            $.ajax({
                type: "POST",
                url: "obtener_ips.php",
                data: { municipio: municipio },
                success: function(res){
                    $('#ips_cita').html(res);
                }
            });
        }

        function cargarMedicos(){
            let ips = $('#ips_cita').val();
            $.ajax({
                type: "POST",
                url: "obtener_medicos.php",
                data: { ips: ips },
                success: function(res){
                    $('#medico_cita').html(res);
                }
            });
        }

        function actualizarHoras(){
            let medico = $('#medico_cita').val();
            let fecha = $('#fecha_cita').val();
            $.ajax({
                type: "POST",
                url: "horas_disponibles.php",
                data: { medico: medico, fecha: fecha },
                success: function(res){
                    $('#hora_cita').html(res);
                }
            });
        }
    </script>
</body>
</html>
