<?php
// Asegurarse de que los errores de PHP se muestren para depuración (solo en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// La ruta '../' es correcta si 'guarda_consul.php' está en 'medi/' y 'include/' está en la raíz.
require_once('../include/conexion.php');
require_once('../include/validar_sesion.php');

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) session_start();

// 1. Verificación de Sesión y Método
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 4) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// 2. Recolección y Limpieza de Datos
$id_cita = filter_input(INPUT_POST, 'id_cita', FILTER_VALIDATE_INT);
$doc_paciente = trim($_POST['doc_pac_hidden'] ?? ''); // Lo mantenemos para la redirección
$motivo_consulta = trim($_POST['motivo_de_cons'] ?? '');
$presion = trim($_POST['presion'] ?? '');
$saturacion = trim($_POST['saturacion'] ?? '');
$peso = trim($_POST['peso'] ?? '');
$estatura = trim($_POST['estatura'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');

// 3. Validación de Datos en el Servidor
$errors = [];
if (empty($id_cita)) $errors[] = "ID de cita inválido.";
if (empty($doc_paciente)) $errors[] = "Documento de paciente no encontrado (necesario para la redirección).";

if (!preg_match('/^[a-zA-Z0-9\s.,áéíóúÁÉÍÓÚñÑ]+$/u', $motivo_consulta)) $errors[] = "Formato de motivo de consulta inválido.";
if (!preg_match('/^[0-9]{2,3}\/[0-9]{2,3}$/', $presion)) $errors[] = "Formato de presión arterial inválido.";
if (!preg_match('/^[0-9]{2,3}$/', $saturacion)) $errors[] = "Formato de saturación inválido.";
if (!preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $peso)) $errors[] = "Formato de peso inválido.";
if (!preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $estatura)) $errors[] = "Formato de estatura inválido.";
if (!preg_match('/^[a-zA-Z0-9\s.,áéíóúÁÉÍÓÚñÑ]+$/u', $observaciones)) $errors[] = "Formato de observaciones inválido.";

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// 4. Interacción con la Base de Datos
$db = new Database();
$pdo = $db->conectar();

try {
    $pdo->beginTransaction();

    // ----- CORRECCIÓN PRINCIPAL AQUÍ -----
    // Se han eliminado doc_pac y doc_med del INSERT para que coincida con la tabla
    $sql_historia = "INSERT INTO historia_clinica (id_cita, motivo_de_cons, presion, saturacion, peso, estatura, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_historia = $pdo->prepare($sql_historia);
    $stmt_historia->execute([
        $id_cita,
        $motivo_consulta,
        $presion,
        $saturacion,
        $peso,
        $estatura,
        $observaciones
    ]);

    // Actualizar el estado de la cita a 'Realizada' (ID 5)
    $id_estado_realizada = 5;
    $sql_update_cita = "UPDATE citas SET id_est = ? WHERE id_cita = ?";
    $stmt_update_cita = $pdo->prepare($sql_update_cita);
    $stmt_update_cita->execute([$id_estado_realizada, $id_cita]);

    $pdo->commit();
    
    // 5. Respuesta Exitosa
    // Usamos el doc_paciente que recibimos del formulario para construir la URL de redirección
    $redirect_url = "deta_historia_clini.php?documento=" . urlencode($doc_paciente);
    echo json_encode(['success' => true, 'redirect_url' => $redirect_url]);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $error_message = 'Error de base de datos. Detalles: ' . $e->getMessage();
    error_log("Error al guardar consulta: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit;
}
?>