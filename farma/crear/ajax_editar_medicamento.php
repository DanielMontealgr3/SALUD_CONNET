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

// Se verifica que la petición se haya realizado mediante el método POST y que el ID del medicamento esté presente.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_medicamento'])) {
    
    // --- BLOQUE 3: RECOLECCIÓN Y SANITIZACIÓN DE DATOS DEL FORMULARIO ---
    // Se recogen y limpian los datos enviados desde el frontend para prevenir inyecciones y errores.
    $id = filter_var($_POST['id_medicamento'], FILTER_VALIDATE_INT);
    $nombre = trim($_POST['nom_medicamento']);
    $id_tipo = filter_var($_POST['id_tipo_medic'], FILTER_VALIDATE_INT);
    $descripcion = trim($_POST['descripcion']);
    $codigo_barras = trim($_POST['codigo_barras']);
    // Si el código de barras está vacío, se asigna NULL para la base de datos.
    $codigo_barras = empty($codigo_barras) ? null : $codigo_barras;

    // Se validan los datos obligatorios.
    if (!$id || empty($nombre) || !$id_tipo || empty($descripcion)) {
        $response['message'] = 'Todos los campos obligatorios deben ser completados.';
        echo json_encode($response);
        exit;
    }
    
    // --- BLOQUE 4: OPERACIONES DE BASE DE DATOS ---
    try {
        // La conexión $con ya está disponible desde el archivo config.php.
        
        // 1. Verificar si el nuevo nombre ya pertenece a OTRO medicamento para evitar duplicados.
        $stmt_check_name = $con->prepare("SELECT id_medicamento FROM medicamentos WHERE nom_medicamento = ? AND id_medicamento != ?");
        $stmt_check_name->execute([$nombre, $id]);
        if ($stmt_check_name->fetch()) {
            $response['message'] = 'Ese nombre ya pertenece a otro medicamento.';
            echo json_encode($response);
            exit;
        }

        // 2. Verificar si el nuevo código de barras ya pertenece a OTRO medicamento.
        if ($codigo_barras !== null) {
            $stmt_check_code = $con->prepare("SELECT id_medicamento FROM medicamentos WHERE codigo_barras = ? AND id_medicamento != ?");
            $stmt_check_code->execute([$codigo_barras, $id]);
            if ($stmt_check_code->fetch()) {
                $response['message'] = 'Ese código de barras ya pertenece a otro medicamento.';
                echo json_encode($response);
                exit;
            }
        }

        // 3. Actualizar el medicamento en la base de datos con los nuevos valores.
        $sql = "UPDATE medicamentos SET nom_medicamento = ?, id_tipo_medic = ?, descripcion = ?, codigo_barras = ? WHERE id_medicamento = ?";
        $stmt_update = $con->prepare($sql);
        if($stmt_update->execute([$nombre, $id_tipo, $descripcion, $codigo_barras, $id])){
            $response['success'] = true;
            $response['message'] = 'Medicamento actualizado correctamente.';
        } else {
            $response['message'] = 'Error al actualizar el medicamento.';
        }
    } catch (PDOException $e) {
        // En caso de un error en la base de datos, se envía un mensaje genérico de error y se registra el detalle.
        $response['message'] = 'Error de base de datos.';
        error_log("Error en ajax_editar_medicamento.php: " . $e->getMessage());
    }
}

// --- BLOQUE 5: ENVÍO DE LA RESPUESTA FINAL ---
// Se imprime la respuesta final en formato JSON para que el JavaScript la procese.
echo json_encode($response);
?>