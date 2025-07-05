<?php
// --- BLOQUE 1: CABECERAS Y CONFIGURACIÓN INICIAL ---
// Se establece la cabecera para indicar que la respuesta será en formato JSON.
header('Content-Type: application/json; charset=utf-8');

// Se incluye el archivo de configuración central. La ruta __DIR__ . '/../../' sube dos niveles
// desde 'farma/crear/' para encontrar la carpeta 'include/'.
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// --- BLOQUE 2: RESPUESTA POR DEFECTO Y VALIDACIÓN DEL MÉTODO ---
// Se inicializa una respuesta de error por defecto.
$response = ['success' => false, 'message' => 'Datos inválidos.'];

// Se verifica que la petición se haya realizado mediante el método POST y que los datos necesarios existan.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_tip_medic'], $_POST['nom_tipo_medi'])) {
    
    // --- BLOQUE 3: RECOLECCIÓN Y VALIDACIÓN DE DATOS ---
    // Se recogen y limpian los datos enviados desde el frontend.
    $id = filter_var($_POST['id_tip_medic'], FILTER_VALIDATE_INT);
    $nombre = trim($_POST['nom_tipo_medi']);

    // Se validan las reglas de negocio (longitud, caracteres permitidos).
    if (!$id || strlen($nombre) < 5 || !preg_match('/^[a-zA-Z\s()]+$/', $nombre)) {
        $response['message'] = 'El nombre proporcionado no es válido.';
        echo json_encode($response);
        exit;
    }

    // --- BLOQUE 4: OPERACIONES DE BASE DE DATOS ---
    try {
        // La conexión $con ya está disponible desde el archivo config.php.

        // 1. Verificar si el nuevo nombre ya pertenece a OTRO tipo de medicamento para evitar duplicados.
        $stmt_check = $con->prepare("SELECT id_tip_medic FROM tipo_de_medicamento WHERE nom_tipo_medi = ? AND id_tip_medic != ?");
        $stmt_check->execute([$nombre, $id]);
        if ($stmt_check->fetch()) {
            $response['message'] = 'Ese nombre ya está en uso por otro tipo de medicamento.';
        } else {
            // 2. Si el nombre es único, se procede a actualizar el registro.
            $stmt_update = $con->prepare("UPDATE tipo_de_medicamento SET nom_tipo_medi = ? WHERE id_tip_medic = ?");
            if ($stmt_update->execute([$nombre, $id])) {
                $response['success'] = true;
                $response['message'] = 'El tipo de medicamento ha sido actualizado.';
            } else {
                $response['message'] = 'Error al actualizar en la base de datos.';
            }
        }
    } catch (PDOException $e) {
        // En caso de un error en la base de datos, se envía un mensaje genérico y se registra el detalle.
        $response['message'] = 'Error de base de datos.';
        error_log("Error en ajax_editar_tipo_medi.php: " . $e->getMessage());
    }
}

// --- BLOQUE 5: ENVÍO DE LA RESPUESTA FINAL ---
// Se imprime la respuesta final en formato JSON para que el JavaScript la procese.
echo json_encode($response);
?>