<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../../../include/conexion.php'; 
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Error de validación CSRF.']); exit;
    }

    $id_registro_original = trim($_POST['id_registro_original'] ?? '');
    $tipo_registro = trim($_POST['tipo_registro'] ?? '');
    $nombre_edit = trim($_POST['nombre_edit'] ?? '');
    $id_tipo_enfer_fk_edit = trim($_POST['id_tipo_enfer_fk_edit'] ?? null); // Solo para 'enfermedad'

    if (empty($id_registro_original) || empty($tipo_registro) || empty($nombre_edit)) {
        echo json_encode(['success' => false, 'message' => 'ID, tipo o nombre no especificados.']); exit;
    }
    if (strlen($nombre_edit) < 3 || strlen($nombre_edit) > 150) {
         echo json_encode(['success' => false, 'message' => 'El nombre debe tener entre 3 y 150 caracteres.']); exit;
    }
    if (!preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\s.,()#-]+$/u', $nombre_edit)) {
        echo json_encode(['success' => false, 'message' => 'El nombre contiene caracteres no permitidos.']); exit;
    }
    if ($tipo_registro === 'enfermedad' && empty($id_tipo_enfer_fk_edit)) {
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar un tipo de enfermedad.']); exit;
    }

    $conex = new database();
    $pdo = $conex->conectar();
    
    if (!$pdo) { echo json_encode(['success' => false, 'message' => 'Error de conexión.']); exit; }

    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->beginTransaction();

        if ($tipo_registro === 'tipo_enfermedad') {
            $tabla = "tipo_enfermedades";
            $col_nombre = "tipo_enfermer";
            $col_id = "id_tipo_enfer";

            $stmt_check = $pdo->prepare("SELECT $col_id FROM $tabla WHERE $col_nombre = :nombre AND $col_id != :id_original");
            $stmt_check->execute([':nombre' => $nombre_edit, ':id_original' => $id_registro_original]);
            if ($stmt_check->fetch()) {
                echo json_encode(['success' => false, 'message' => "El tipo de enfermedad '" . htmlspecialchars($nombre_edit) . "' ya existe."]);
                $pdo->rollBack(); exit;
            }
            $sql = "UPDATE $tabla SET $col_nombre = :nombre WHERE $col_id = :id_original";
            $params = [':nombre' => $nombre_edit, ':id_original' => $id_registro_original];

        } elseif ($tipo_registro === 'enfermedad') {
            $tabla = "enfermedades";
            $col_nombre = "nom_enfer";
            $col_id = "id_enferme";
            $col_fk = "id_tipo_enfer";

            $stmt_check = $pdo->prepare("SELECT $col_id FROM $tabla WHERE $col_nombre = :nombre AND $col_fk = :fk_val AND $col_id != :id_original");
            $stmt_check->execute([':nombre' => $nombre_edit, ':fk_val' => $id_tipo_enfer_fk_edit, ':id_original' => $id_registro_original]);
            if ($stmt_check->fetch()) {
                echo json_encode(['success' => false, 'message' => "La enfermedad '" . htmlspecialchars($nombre_edit) . "' ya existe para el tipo seleccionado."]);
                $pdo->rollBack(); exit;
            }
            $sql = "UPDATE $tabla SET $col_nombre = :nombre, $col_fk = :fk_val WHERE $col_id = :id_original";
            $params = [':nombre' => $nombre_edit, ':fk_val' => $id_tipo_enfer_fk_edit, ':id_original' => $id_registro_original];
        } else {
            echo json_encode(['success' => false, 'message' => 'Tipo de registro no válido.']); exit;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            $_SESSION['mensaje_accion'] = ucfirst(str_replace('_', ' ', $tipo_registro)) . " actualizado correctamente.";
            $_SESSION['mensaje_accion_tipo'] = 'success';
            echo json_encode(['success' => true, 'message' => "Actualizado correctamente."]);
        } else {
            $pdo->rollBack();
            echo json_encode(['success' => true, 'message' => "No se realizaron cambios."]);
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        echo json_encode(['success' => false, 'message' => "Error de BD: " . $e->getMessage()]);
    } finally {
        $pdo = null;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
exit;
?>