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
$response = ['success' => false, 'message' => 'Acceso no permitido o datos incorrectos.'];

// Se verifica que la petición se haya realizado mediante el método POST y que el dato necesario exista.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom_tipo_medi'])) {
    
    // --- BLOQUE 3: RECOLECCIÓN Y VALIDACIÓN DE DATOS ---
    // Se recoge y limpia el nombre del tipo de medicamento.
    $nombre_tipo = trim($_POST['nom_tipo_medi']);

    // Se valida el nombre usando una expresión regular para permitir letras, espacios y paréntesis.
    // También se verifica la longitud mínima.
    if (strlen($nombre_tipo) < 5 || !preg_match('/^[a-zA-Z\s()]+$/', $nombre_tipo)) {
        $response['message'] = 'El nombre no es válido. Debe tener al menos 5 caracteres y contener solo letras, espacios o paréntesis.';
        echo json_encode($response);
        exit;
    }

    // --- BLOQUE 4: OPERACIONES DE BASE DE DATOS ---
    try {
        // La conexión $con ya está disponible desde el archivo config.php.

        // 1. Verificar si el tipo de medicamento ya existe para evitar duplicados.
        $stmt_check = $con->prepare("SELECT id_tip_medic FROM tipo_de_medicamento WHERE nom_tipo_medi = ?");
        $stmt_check->execute([$nombre_tipo]);

        if ($stmt_check->fetch()) {
            $response['message'] = 'Este tipo de medicamento ya existe.';
        } else {
            // 2. Si no existe, se inserta el nuevo tipo en la base de datos.
            $stmt_insert = $con->prepare("INSERT INTO tipo_de_medicamento (nom_tipo_medi) VALUES (?)");
            if ($stmt_insert->execute([$nombre_tipo])) {
                $response['success'] = true;
                $response['message'] = 'Tipo de medicamento creado con éxito.';
            } else {
                $response['message'] = 'Error al guardar en la base de datos.';
            }
        }
    } catch (PDOException $e) {
        // En caso de un error en la base de datos, se envía un mensaje genérico de error y se registra el detalle.
        $response['message'] = 'Error de base de datos.';
        error_log("Error en ajax_crear_tipo_medi.php: " . $e->getMessage());
    }
}

// --- BLOQUE 5: ENVÍO DE LA RESPUESTA FINAL ---
// Se imprime la respuesta final en formato JSON para que el JavaScript la procese.
echo json_encode($response);
?>