<?php
require_once('../include/conexion.php');

$db = new Database();
$pdo = $db->conectar();

// Consulta para obtener los detalles con sus nombres relacionados
$sql = "SELECT 
            dh.id_detalle,
            hc.id_historia,
            d.diagnostico,
            e.nom_enfer,
            m.nom_medicamento,
            dh.can_medica,
            p.procedimiento,
            dh.cant_proced,
            dh.prescripcion
        FROM detalles_histo_clini dh
        INNER JOIN historia_clinica hc ON dh.id_historia = hc.id_historia
        INNER JOIN diagnostico d ON dh.id_diagnostico = d.id_diagnos
        INNER JOIN enfermedades e ON dh.id_enferme = e.id_enferme
        INNER JOIN medicamentos m ON dh.id_medicam = m.id_medicamento
        INNER JOIN procedimientos p ON dh.id_proced = p.id_proced
        ORDER BY dh.id_detalle DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles de Historia Clínica</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-4">
    <h2 class="mb-4">Detalles de Historia Clínica</h2>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID Detalle</th>
                <th>ID Historia</th>
                <th>Diagnóstico</th>
                <th>Enfermedad</th>
                <th>Medicamento</th>
                <th>Cant. Medicamento</th>
                <th>Procedimiento</th>
                <th>Cant. Procedimiento</th>
                <th>Prescripción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $fila): ?>
                <tr>
                    <td><?= htmlspecialchars($fila['id_detalle']) ?></td>
                    <td><?= htmlspecialchars($fila['id_historia']) ?></td>
                    <td><?= htmlspecialchars($fila['diagnostico']) ?></td>
                    <td><?= htmlspecialchars($fila['nom_enfer']) ?></td>
                    <td><?= htmlspecialchars($fila['nom_medicamento']) ?></td>
                    <td><?= htmlspecialchars($fila['can_medica']) ?></td>
                    <td><?= htmlspecialchars($fila['procedimiento']) ?></td>
                    <td><?= htmlspecialchars($fila['cant_proced']) ?></td>
                    <td><?= htmlspecialchars($fila['prescripcion']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
