<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['id_rol'] != 4) {
    header('Location: ../inicio_sesion.php');
    exit;
}

$db = new Database();
$pdo = $db->conectar();

// Guardar detalles de historia clínica
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // Verificar campos obligatorios
        if (
            empty($_POST['id_historia']) ||
            empty($_POST['id_diagnostico'])
        ) {
            throw new Exception("id_historia y diagnostico son campos obligatorios.");
        }

        // Insertar en detalles_histo_clini
        $insertDetalle = "INSERT INTO detalles_histo_clini 
            (id_historia, id_diagnostico, id_enferme, id_medicam, can_medica, id_proced, cant_proced, prescripcion)
            VALUES
            (:id_historia, :id_diagnostico, :id_enferme, :id_medicam, :can_medica, :id_proced, :cant_proced, :prescripcion)";

        $stmtDet = $pdo->prepare($insertDetalle);
        $stmtDet->bindParam(':id_historia', $_POST['id_historia']);
        $stmtDet->bindParam(':id_diagnostico', $_POST['id_diagnostico']);
        $stmtDet->bindParam(':id_enferme', $_POST['id_enferme']);
        $stmtDet->bindParam(':id_medicam', $_POST['id_medicam']);
        $stmtDet->bindParam(':can_medica', $_POST['can_medica']);
        $stmtDet->bindParam(':id_proced', $_POST['id_proced']);
        $stmtDet->bindParam(':cant_proced', $_POST['cant_proced']);
        $stmtDet->bindParam(':prescripcion', $_POST['prescripcion']);
        $stmtDet->execute();

        $pdo->commit();

     
    // Obtener documento del paciente a partir del id_historia
$queryDoc = "
    SELECT c.doc_pac 
    FROM historia_clinica h
    JOIN citas c ON h.id_cita = c.id_cita
    WHERE h.id_historia = :id_historia
";
$stmtDoc = $pdo->prepare($queryDoc);
$stmtDoc->execute([':id_historia' => $_POST['id_historia']]);
$cita = $stmtDoc->fetch(PDO::FETCH_ASSOC);

if ($cita && isset($cita['doc_pac'])) {
    header("Location: agendar_cita.php?documento=" . urlencode($cita['doc_pac']));
    exit;
} else {
    echo "<script>
            alert('No se pudo obtener el documento del paciente.');
            window.history.back();
          </script>";
    exit;
}


    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error al guardar consulta: " . $e->getMessage();
    }
}

?>
<!-- agendar,sacar cita medica -->