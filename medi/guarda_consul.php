<?php
require_once('../include/conexion.php');
$db = new Database();
$pdo = $db->conectar();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cita = $_POST['id_cita'] ?? null;
    $motivo_consulta = trim($_POST['motivo_de_cons'] ?? '');
    $presion = trim($_POST['presion'] ?? '');
    $saturacion = trim($_POST['saturacion'] ?? '');
    $peso = trim($_POST['peso'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');
    $estatura = trim($_POST['estatura'] ?? '');

    // Validar campos obligatorios
      if (!$id_cita || $motivo_consulta === '' || $presion === '' || $saturacion === '' || $peso === '' || $estatura === '') {
        echo "<script>
                alert('Todos los campos son obligatorios.');
                window.history.back(); // Regresa al formulario anterior
              </script>";
        exit;
      }
    try {
        $query = "INSERT INTO historia_clinica 
                  (id_cita, motivo_de_cons, presion, saturacion, peso, estatura, observaciones)
                  VALUES 
                  (:id_cita, :motivo_de_cons, :presion, :saturacion, :peso, :estatura, :observaciones)";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':id_cita' => $id_cita,
            ':motivo_de_cons' => $motivo_consulta,
            ':presion' => $presion,
            ':saturacion' => $saturacion,
            ':peso' => $peso,
            ':estatura' => $estatura,
            ':observaciones' => $observaciones // puedes dejar observaciones opcional si lo deseas
        ]);

       header("Location: deta_historia_clini.php?documento=" . urlencode($cita['doc_pac']));

    
        exit;
    } catch (PDOException $e) {
        echo "Error al guardar consulta: " . $e->getMessage();
    }
} else {
    echo "Acceso no permitido.";
}


?>
