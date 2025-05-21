<?php
require_once('../include/conexion.php');
$db = new Database();
$pdo = $db->conectar();

$fecha = $_GET['fecha'] ?? '';
$medico = $_GET['medico'] ?? '';

if ($fecha && $medico) {
    $sql = "SELECT id_horario_med, CONCAT(horario, ' ', IF(meridiano = 1, 'AM', 'PM')) AS horario 
            FROM horario_medico 
            WHERE fecha_horario = :fecha 
              AND doc_medico = :medico 
              AND id_estado = 4"; // Disponible

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'fecha' => $fecha,
        'medico' => $medico
    ]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($horarios);
} else {
    echo json_encode([]);
}
?>



















<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php'); 

$db = new Database();
$pdo = $db->conectar();

// Procesar formulario de agendar cita
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = $_POST['doc_pac'];
    $medico_id = $_POST['doc_med'];
    $horario_id = $_POST['fecha_horario']; // Este contiene fecha y hora combinadas
    $procedimiento_id = $_POST['id_proced'];

    // Validar que no haya una cita duplicada
    $check = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE doc_pac = ? AND doc_med = ? AND hora_cita = ?");
    $check->execute([$usuario_id, $medico_id, $horario_id]);
    
    if ($check->fetchColumn() > 0) {
        echo "❌ Ya existe una cita agendada con esos datos.";
    } else {
        $sql = "INSERT INTO citas (doc_pac, doc_med, hora_cita, id_proced) 
                VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$usuario_id, $medico_id, $horario_id, $procedimiento_id])) {
            echo "✅ Cita agendada exitosamente.";
        } else {
            echo "❌ Error al agendar la cita.";
        }
    }
}

// Obtener datos para el formulario
$usuarios = $pdo->query("SELECT doc_usu, nom_usu FROM usuarios");
$medicos = $pdo->query("SELECT DISTINCT doc_medico FROM horario_medico");
$procedimientos = $pdo->query("SELECT id_proced, procedimiento FROM procedimientos");

// Obtener fechas y horarios disponibles
$fechas_horarios = $pdo->query("
    SELECT 
        id_horario_med, 
        fecha_horario, 
        CONCAT(horario, ' ', IF(meridiano = 1, 'AM', 'PM')) AS horario_completo 
    FROM horario_medico 
    WHERE id_estado = 4
");

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agendar Cita</title>
</head>
<body>
    <h1>Agendar Cita</h1>
    <form method="POST" action="">
        <label for="doc_pac">Usuario:</label>
        <select name="doc_pac" id="doc_pac" required>
            <option value="">Seleccione un usuario</option>
            <?php while ($usuario = $usuarios->fetch(PDO::FETCH_ASSOC)): ?>
                <option value="<?= $usuario['doc_usu'] ?>">
                    <?= htmlspecialchars($usuario['doc_usu'] . ' - ' . $usuario['nom_usu']) ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <label for="doc_med">Médico:</label>
        <select name="doc_med" id="doc_med" required>
            <option value="">Seleccione un médico</option>
            <?php while ($medico = $medicos->fetch(PDO::FETCH_ASSOC)): ?>
                <option value="<?= $medico['doc_medico'] ?>">
                    <?= htmlspecialchars($medico['doc_medico']) ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <label for="id_proced">Procedimiento:</label>
        <select name="id_proced" id="id_proced" required>
            <option value="">Seleccione un procedimiento</option>
            <?php while ($procedimiento = $procedimientos->fetch(PDO::FETCH_ASSOC)): ?>
                <option value="<?= $procedimiento['id_proced'] ?>">
                    <?= htmlspecialchars($procedimiento['procedimiento']) ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <label for="fecha_horario">Fecha y horario:</label>
        <select name="fecha_horario" id="fecha_horario" required>
            <option value="">Seleccione una fecha y horario</option>
            <?php while ($row = $fechas_horarios->fetch(PDO::FETCH_ASSOC)): ?>
                <option value="<?= $row['id_horario_med'] ?>">
                    <?= htmlspecialchars($row['fecha_horario'] . ' - ' . $row['horario_completo']) ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <button type="submit">Agendar Cita</button>
    </form>
</body>
</html>
