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

// Se verifica que la petición se haya realizado mediante el método POST y que el ID del tipo de medicamento esté presente.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_tip_medic'])) {
    
    // --- BLOQUE 3: RECOLECCIÓN Y VALIDACIÓN DE DATOS ---
    // Se recoge y valida que el ID sea un entero.
    $id = filter_var($_POST['id_tip_medic'], FILTER_VALIDATE_INT);

    if (!$id) {
        $response['message'] = 'ID no válido.';
        echo json_encode($response);
        exit;
    }

    // --- BLOQUE 4: OPERACIONES DE BASE DE DATOS ---
    try {
        // La conexión $con ya está disponible desde el archivo config.php.

        // 1. Se verifica si el tipo de medicamento está en uso en la tabla 'medicamentos'.
        // Esta es una medida de seguridad para prevenir la eliminación de tipos que ya están asignados.
        $stmt_check = $con->prepare("SELECT COUNT(*) FROM medicamentos WHERE id_tipo_medic = ?");
        $stmt_check->execute([$id]);
        $count = $stmt_check->fetchColumn();

        if ($count > 0) {
            // Si el tipo está en uso, se informa al usuario y no se permite la eliminación.
            $response['message'] = "No se puede eliminar este tipo porque está asignado a {$count} medicamento(s). Por favor, reasigne esos medicamentos primero.";
        } else {
            // 2. Si no está en uso, se procede con la eliminación.
            $stmt_delete = $con->prepare("DELETE FROM tipo_de_medicamento WHERE id_tip_medic = ?");
            if ($stmt_delete->execute([$id])) {
                $response['success'] = true;
                $response['message'] = 'El tipo de medicamento ha sido eliminado.';
            } else {
                $response['message'] = 'Error al eliminar de la base de datos.';
            }
        }
    } catch (PDOException $e) {
        // En caso de un error en la base de datos, se envía un mensaje genérico y se registra el detalle.
        $response['message'] = 'Error de base de datos. Es posible que el registro esté protegido por otras relaciones.';
        error_log("Error en ajax_eliminar_tipo_medi.php: " . $e->getMessage());
    }
}

// --- BLOQUE 5: ENVÍO DE LA RESPUESTA FINAL ---
// Se imprime la respuesta final en formato JSON.
echo json_encode($response);
?>