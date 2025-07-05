<?php
// --- BLOQUE 1: CABECERAS Y CONFIGURACIÓN INICIAL ---
// Se establece la cabecera para indicar que la respuesta será en formato JSON.
header('Content-Type: application/json; charset=utf-8');

// Se incluye el archivo de configuración central para acceder a la BD y a las constantes de ruta.
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// --- BLOQUE 2: RESPUESTA POR DEFECTO Y VALIDACIÓN DEL MÉTODO ---
// Se inicializa una respuesta de error por defecto.
$response = ['success' => false, 'message' => 'Datos inválidos o faltantes.'];

// Se verifica que la petición se haya realizado mediante el método POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- BLOQUE 3: RECOLECCIÓN Y SANITIZACIÓN DE DATOS DEL FORMULARIO ---
    // Se recogen y limpian los datos enviados desde el frontend para prevenir inyecciones y errores.
    $nombre = trim($_POST['nom_medicamento'] ?? '');
    $id_tipo = filter_var($_POST['id_tipo_medic'], FILTER_VALIDATE_INT);
    $descripcion = trim($_POST['descripcion'] ?? '');
    $codigo_barras = trim($_POST['codigo_barras'] ?? '');
    // Si el código de barras está vacío, se asigna NULL para la base de datos.
    $codigo_barras = empty($codigo_barras) ? null : $codigo_barras;

    // Se validan las reglas de negocio (longitud mínima, selección, etc.).
    if (strlen($nombre) < 5 || !$id_tipo || strlen($descripcion) < 10) {
        $response['message'] = 'Por favor, complete todos los campos requeridos correctamente.';
        echo json_encode($response);
        exit;
    }

    // --- BLOQUE 4: OPERACIONES DE BASE DE DATOS ---
    try {
        // La conexión $con ya está disponible desde el archivo config.php.

        // 1. Verificar si el nombre del medicamento ya existe para evitar duplicados.
        $stmt_check = $con->prepare("SELECT id_medicamento FROM medicamentos WHERE nom_medicamento = ?");
        $stmt_check->execute([$nombre]);
        if ($stmt_check->fetch()) {
            $response['message'] = 'Ya existe un medicamento con este nombre.';
            echo json_encode($response);
            exit;
        }

        // 2. Verificar si el código de barras ya existe (solo si se proporcionó uno).
        if ($codigo_barras !== null) {
            $stmt_check_code = $con->prepare("SELECT id_medicamento FROM medicamentos WHERE codigo_barras = ?");
            $stmt_check_code->execute([$codigo_barras]);
            if ($stmt_check_code->fetch()) {
                $response['message'] = 'Este código de barras ya está asignado a otro medicamento.';
                echo json_encode($response);
                exit;
            }
        }
        
        // 3. Insertar el nuevo medicamento en la base de datos.
        $sql = "INSERT INTO medicamentos (nom_medicamento, id_tipo_medic, descripcion, codigo_barras) VALUES (?, ?, ?, ?)";
        $stmt_insert = $con->prepare($sql);

        if ($stmt_insert->execute([$nombre, $id_tipo, $descripcion, $codigo_barras])) {
            $response['success'] = true;
            $response['message'] = 'Medicamento creado con éxito.';
        } else {
            $response['message'] = 'Error al guardar el medicamento en la base de datos.';
        }
    } catch (PDOException $e) {
        // En caso de un error en la base de datos, se envía un mensaje genérico de error y se registra el detalle.
        $response['message'] = 'Error de base de datos.';
        error_log("Error en ajax_crear_medicamento.php: " . $e->getMessage());
    }
}

// --- BLOQUE 5: ENVÍO DE LA RESPUESTA FINAL ---
// Se imprime la respuesta final en formato JSON para que el JavaScript la procese.
echo json_encode($response);
?>