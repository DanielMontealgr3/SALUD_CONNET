<?php
require_once('../include/conexion.php');

if (isset($_POST['doc_med']) && isset($_POST['fecha_cita'])) {
    $medico = $_POST['doc_med'];
    $fecha = $_POST['fecha_cita'];

    $sql = $con->prepare("SELECT horario, meridiano FROM horario_medico 
        WHERE doc_medico = ? AND fecha_horario = ? AND id_estado = 1 
        AND CONCAT(horario, ' ', meridiano) NOT IN (
            SELECT CONCAT(hora_cita, '') FROM citas 
            WHERE doc_med = ? AND fecha_cita = ? AND id_est = 1
        )");

    $sql->execute([$medico, $fecha, $medico, $fecha]);

    if ($sql->rowCount() > 0) {
        while ($row = $sql->fetch(PDO::FETCH_ASSOC)) {
            $hora = $row['horario'] . ' ' . $row['meridiano'];
            echo "<option value=\"$hora\">$hora</option>";
        }
    } else {
        echo '<option value="">No hay horas disponibles</option>';
    }
}
