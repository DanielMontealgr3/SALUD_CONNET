<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$response = ['success' => false, 'message' => 'Código de barras no proporcionado.'];

if (isset($_GET['codigo_barras'])) {
    $codigo_barras = trim($_GET['codigo_barras']);
    $nit_farmacia = $_SESSION['nit_farmacia_asignada_actual'] ?? '';
    
    if (empty($codigo_barras) || empty($nit_farmacia)) {
        $response['message'] = 'Faltan datos para la búsqueda.';
        echo json_encode($response);
        exit;
    }

    try {
        $db = new database();
        $con = $db->conectar();
        
        $stmt_med = $con->prepare("SELECT id_medicamento, nom_medicamento FROM medicamentos WHERE codigo_barras = ?");
        $stmt_med->execute([$codigo_barras]);
        $medicamento = $stmt_med->fetch(PDO::FETCH_ASSOC);

        if ($medicamento) {
            $stmt_inv = $con->prepare("SELECT cantidad_actual FROM inventario_farmacia WHERE id_medicamento = ? AND nit_farm = ?");
            $stmt_inv->execute([$medicamento['id_medicamento'], $nit_farmacia]);
            $stock = $stmt_inv->fetchColumn();

            $response['success'] = true;
            $response['data'] = [
                'id_medicamento' => $medicamento['id_medicamento'],
                'nombre' => $medicamento['nom_medicamento'],
                'stock_actual' => $stock !== false ? $stock : 0
            ];
        } else {
            $response['message'] = 'No se encontró ningún medicamento con ese código de barras.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Error en la base de datos.';
        error_log($e->getMessage());
    }
}

echo json_encode($response);
?>