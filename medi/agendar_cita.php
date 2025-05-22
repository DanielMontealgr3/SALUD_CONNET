<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php'); 
require_once( '../include/menu.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// $nombre_usuario = $_SESSION['nombre_usuario'];
$db = new Database();
$pdo = $db->conectar();
$nombre_usuario = $_SESSION['nombre_usuario'];

// Conexión a la base de datos
$db = new Database();
$pdo = $db->conectar();

// Obtener el documento del paciente desde la URL
$documento = $_GET['documento'] ?? null;
if (!$documento) {
    echo "Documento no proporcionado.";
    exit;
}
// echo "Documento($documento)";

// Obtener datos de la cita
$query = "SELECT * FROM citas WHERE doc_pac = :documento";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':documento', $documento, PDO::PARAM_STR);
$stmt->execute();
$cita = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cita) {
    echo "No se encontró cita para el documento proporcionado.";
    exit;
}

$query = "SELECT nom_usu FROM usuarios WHERE doc_usu = :documento";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':documento', $documento, PDO::PARAM_STR);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario) {
    $nombre = $usuario['nom_usu'];
    echo "<h4>Documento ($documento) - $nombre</h4>";
} else {
    echo "<h4>Documento ($documento) - Usuario no encontrado</h4>";
}


$mes_actual = $_GET['mes'] ?? date('m');
$anio_actual = $_GET['anio'] ?? date('Y');

// Fechas con horarios disponibles
$fechas_disponibles = $pdo->prepare("
    SELECT DISTINCT DATE(fecha_horario) AS fecha 
    FROM horario_medico 
    WHERE id_estado = 4 
    AND MONTH(fecha_horario) = :mes 
    AND YEAR(fecha_horario) = :anio
");


$fechas_disponibles->execute(['mes' => $mes_actual, 'anio' => $anio_actual]);
$fechas_disponibles_array = array_column($fechas_disponibles->fetchAll(PDO::FETCH_ASSOC), 'fecha');

// Navegación de meses
$mes_anterior = $mes_actual - 1;
$anio_anterior = $anio_actual;
if ($mes_anterior == 0) {
    $mes_anterior = 12;
    $anio_anterior--;
}
$mes_siguiente = $mes_actual + 1;
$anio_siguiente = $anio_actual;
if ($mes_siguiente == 13) {
    $mes_siguiente = 1;
    $anio_siguiente++;
}

// Procesar agendamiento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_horario'])) {
    $usuario_id = $_SESSION['documento_usuario'];
    $horario_id = $_POST['id_horario'];
    $procedimiento_id = 1;

    $check = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE doc_pac = ? AND hora_cita = ?");
    $check->execute([$usuario_id, $horario_id]);

    if ($check->fetchColumn() > 0) {
        $mensaje = "❌ Ya tienes una cita agendada en ese horario.";
    } else {
        $stmtMed = $pdo->prepare("SELECT doc_medico FROM horario_medico WHERE id_horario_med = ?");
        $stmtMed->execute([$horario_id]);
        $medico = $stmtMed->fetch(PDO::FETCH_ASSOC);

        if ($medico) {
            $sql = "INSERT INTO citas (doc_pac, doc_med, hora_cita, id_proced) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$usuario_id, $medico['doc_medico'], $horario_id, $procedimiento_id])) {
                $mensaje = "✅ Cita agendada exitosamente.";
            } else {
                $mensaje = "❌ Error al agendar la cita.";
            }
        } else {
            $mensaje = "❌ No se encontró médico para ese horario.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
        <head>
    <meta charset="UTF-8">
    <title>Agendar Cita</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>

<div class="contenedor">

    <h1>Agendar Cita</h1>
    <?php if (isset($mensaje)) echo "<p class='mensaje'>$mensaje</p>"; ?>

    <h3>Seleccione una fecha:</h3>

    <table class="calendario">
        <tr class="cabecera">
            <th>Lun</th><th>Mar</th><th>Mié</th><th>Jue</th><th>Vie</th><th>Sáb</th>
        </tr>
        <tr>
<?php
$primer_dia_mes = date('N', strtotime("$anio_actual-$mes_actual-01"));
$dias_mes = cal_days_in_month(CAL_GREGORIAN, $mes_actual, $anio_actual);

$dia_semana = 1;
for ($i = 1; $i < $primer_dia_mes; $i++) {
    echo "<td></td>";
    $dia_semana++;
}

for ($dia = 1; $dia <= $dias_mes; $dia++) {
    $fecha_actual = "$anio_actual-$mes_actual-" . str_pad($dia, 2, '0', STR_PAD_LEFT);
    $dia_nombre = date('N', strtotime($fecha_actual));

    $clase = ($fecha_actual < date('Y-m-d') || $dia_nombre == 7) ? 'no-disponible' : 'disponible';

    echo "<td class='$clase'>";
    echo "<strong>$dia</strong>";

    if ($clase === 'disponible') {
        echo "<form method='POST'>
                <input type='hidden' name='fecha_seleccionada' value='$fecha_actual'>
                <button type='submit' class='btn-disponible'>DISPONIBLE</button>
              </form>";
    } else {
        echo "<button type='button' class='btn-no-disponible' disabled>No disponible</button>";
    }

    echo "</td>";

    if ($dia_semana % 7 == 0) {
        echo "</tr><tr>";
    }
    $dia_semana++;
}
?>
        </tr>
    </table>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fecha_seleccionada'])) {
    $fecha_seleccionada = $_POST['fecha_seleccionada'];

    $horarios = $pdo->prepare("
        SELECT id_horario_med, CONCAT(horario, ' ', IF(meridiano = 1, 'AM', 'PM')) AS horario_completo 
        FROM horario_medico
        WHERE DATE(fecha_horario) = ? AND id_estado = 4
    ");
    $horarios->execute([$fecha_seleccionada]);

    echo "<h3>Horarios disponibles para $fecha_seleccionada:</h3>";
    if ($horarios->rowCount() > 0) {
        echo "<form method='POST' class='form-horarios'>";
        while ($hora = $horarios->fetch(PDO::FETCH_ASSOC)) {
            echo "<label>
                    <input type='radio' name='id_horario' value='{$hora['id_horario_med']}' required>
                    {$hora['horario_completo']}
                  </label>";
        }
        echo "<div class='centrado'>
                <button type='submit' class='btn-agendar'>Agendar</button>
              </div>";
        echo "</form>";
    } else {
        echo "<p>No hay horarios disponibles para esta fecha.</p>";
    }
}
?>

</div>

<?php include '../include/footer.php'; ?>
</body>


<?php include '../include/footer.php'; ?>
</body>
</html>
