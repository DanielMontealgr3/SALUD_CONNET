<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$response = ['success' => false, 'message' => 'Acción no válida o datos insuficientes.'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['accion']) || !isset($_SESSION['doc_usu']) || $_SESSION['id_rol'] != 3) { $response['message'] = 'Acceso denegado o método no permitido.'; echo json_encode($response); exit; }

$accion = $_POST['accion'];
$id_medicamento = isset($_POST['id_medicamento']) ? filter_var($_POST['id_medicamento'], FILTER_VALIDATE_INT) : null;
if (!$id_medicamento) { $response['message'] = 'ID de medicamento no válido.'; echo json_encode($response); exit; }

try {
    $db = new database();
    $con = $db->conectar();
    if ($accion === 'actualizar_detalles') {
        $nom_medicamento = trim($_POST['nom_medicamento'] ?? '');
        $id_tipo_medic = filter_var($_POST['id_tipo_medic'], FILTER_VALIDATE_INT);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $codigo_barras = trim($_POST['codigo_barras'] ?? '');
        $codigo_barras = empty($codigo_barras) ? null : $codigo_barras;

        if (empty($nom_medicamento) || strlen($nom_medicamento) <= 4 || !$id_tipo_medic || $id_tipo_medic == 0) {
            $response['message'] = 'Verifique los datos. El nombre debe tener más de 4 caracteres y el tipo es obligatorio.';
            echo json_encode($response);
            exit;
        }
        if (strlen($descripcion) > 0 && strlen($descripcion) < 10) {
            $response['message'] = 'La descripción debe tener al menos 10 caracteres.';
            echo json_encode($response);
            exit;
        }
        
        $stmt_check_name = $con->prepare("SELECT id_medicamento FROM medicamentos WHERE nom_medicamento = ? AND id_medicamento != ?");
        $stmt_check_name->execute([$nom_medicamento, $id_medicamento]);
        if ($stmt_check_name->fetch()) {
            $response['message'] = 'El nombre "' . htmlspecialchars($nom_medicamento) . '" ya está en uso por otro medicamento.';
            echo json_encode($response);
            exit;
        }

        if ($codigo_barras !== null) {
            $stmt_check_barcode = $con->prepare("SELECT nom_medicamento FROM medicamentos WHERE codigo_barras = ? AND id_medicamento != ?");
            $stmt_check_barcode->execute([$codigo_barras, $id_medicamento]);
            if ($medicamento_existente = $stmt_check_barcode->fetch(PDO::FETCH_ASSOC)) {
                $response['message'] = 'Este código de barras ya pertenece al medicamento: ' . $medicamento_existente['nom_medicamento'];
                echo json_encode($response);
                exit;
            }
        }
        
        $stmt = $con->prepare("UPDATE medicamentos SET nom_medicamento = ?, id_tipo_medic = ?, descripcion = ?, codigo_barras = ? WHERE id_medicamento = ?");
        $stmt->execute([$nom_medicamento, $id_tipo_medic, $descripcion, $codigo_barras, $id_medicamento]);
        $response['success'] = true;
        $response['message'] = 'Medicamento actualizado correctamente.';
        
    } else { $response['message'] = 'La acción especificada es desconocida.'; }
} catch (PDOException $e) { $response['message'] = 'Error en la base de datos: ' . $e->getMessage(); }
echo json_encode($response);
?>