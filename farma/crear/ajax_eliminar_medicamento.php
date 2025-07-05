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
$response = ['success' => false, 'message' => 'ID no proporcionado.'];

// Se verifica que la petición se haya realizado mediante el método POST y que el ID del medicamento esté presente.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_medicamento'])) {
    
    // --- BLOQUE 3: RECOLECCIÓN Y VALIDACIÓN DE DATOS ---
    // Se recoge y valida que el ID sea un entero.
    $id = filter_var($_POST['id_medicamento'], FILTER_VALIDATE_INT);

    if (!$id) {
        $response['message'] = 'ID no válido.';
        echo json_encode($response);
        exit;
    }

    // --- BLOQUE 4: OPERACIONES DE BASE DE DATOS ---
    try {
        // La conexión $con ya está disponible desde el archivo config.php.

        // 1. Se verifica si el medicamento está en uso en la tabla 'inventario_farmacia'.
        // Esto es una medida de seguridad para prevenir la eliminación de medicamentos que ya tienen stock registrado.
        $stmt_check = $con->prepare("SELECT COUNT(*) FROM inventario_farmacia WHERE id_medicamento = ?");
        $stmt_check->execute([$id]);
        $count = $stmt_check->fetchColumn();

        if ($count > 0) {
            // Si el medicamento está en uso, se informa al usuario y no se permite la eliminación.
            $response['message'] = "No se puede eliminar. Este medicamento tiene registros de inventario en {$count} farmacia(s).";
        } else {
            // 2. Si no está en uso, se procede con la eliminación.
            $stmt_delete = $con->prepare("DELETE FROM medicamentos WHERE id_medicamento = ?");
            if ($stmt_delete->execute([$id])) {
                $response['success'] = true;
                $response['message'] = 'El medicamento ha sido eliminado.';
            } else {
                $response['message'] = 'Error al eliminar de la base de datos.';
            }
        }
    } catch (PDOException $e) {
        // En caso de un error de base de datos (por ejemplo, una restricción de clave foránea no contemplada),
        // se envía un mensaje genérico y se registra el detalle.
        $response['message'] = 'Error de base de datos. Es posible que el registro esté protegido por otras relaciones.';
        error_log("Error en ajax_eliminar_medicamento.php: " . $e->getMessage());
    }
}

// --- BLOQUE 5: ENVÍO DE LA RESPUESTA FINAL ---
// Se imprime la respuesta final en formato JSON.
echo json_encode($response);
?>