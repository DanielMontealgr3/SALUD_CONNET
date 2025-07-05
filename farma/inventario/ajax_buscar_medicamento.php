<?php
// --- BLOQUE 1: CABECERAS Y CONFIGURACIÓN INICIAL ---
// Se establece la cabecera para indicar que la respuesta será en formato JSON.
header('Content-Type: application/json; charset=utf-8');

// Se incluye el archivo de configuración central. La ruta __DIR__ . '/../../' sube dos niveles
// desde 'farma/inventario/' para encontrar la carpeta 'include/'.
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// --- BLOQUE 2: RESPUESTA POR DEFECTO Y VALIDACIÓN DE DATOS ---
// Se inicializa una respuesta de error por defecto.
$response = ['success' => false, 'message' => 'Código de barras no proporcionado.'];

// Se verifica que la petición contenga el código de barras.
if (isset($_GET['codigo_barras'])) {
    $codigo_barras = trim($_GET['codigo_barras']);
    $nit_farmacia = $_SESSION['nit_farma'] ?? ''; // Se usa 'nit_farma' que es la que se establece al iniciar sesión.
    
    // Se valida que los datos necesarios no estén vacíos.
    if (empty($codigo_barras) || empty($nit_farmacia)) {
        $response['message'] = 'Faltan datos para la búsqueda (código de barras o farmacia no identificada).';
        echo json_encode($response);
        exit;
    }

    // --- BLOQUE 3: OPERACIONES DE BASE DE DATOS ---
    try {
        // La conexión $con ya está disponible desde el archivo config.php.
        
        // 1. Buscar el medicamento por su código de barras.
        $stmt_med = $con->prepare("SELECT id_medicamento, nom_medicamento FROM medicamentos WHERE codigo_barras = ?");
        $stmt_med->execute([$codigo_barras]);
        $medicamento = $stmt_med->fetch(PDO::FETCH_ASSOC);

        if ($medicamento) {
            // 2. Si se encuentra el medicamento, se busca su stock actual en el inventario de la farmacia actual.
            $stmt_inv = $con->prepare("SELECT cantidad_actual FROM inventario_farmacia WHERE id_medicamento = ? AND nit_farm = ?");
            $stmt_inv->execute([$medicamento['id_medicamento'], $nit_farmacia]);
            $stock = $stmt_inv->fetchColumn();

            // 3. Se prepara la respuesta exitosa con los datos encontrados.
            $response['success'] = true;
            $response['data'] = [
                'id_medicamento' => $medicamento['id_medicamento'],
                'nombre' => $medicamento['nom_medicamento'],
                // Si no hay registro de stock, se asume que es 0.
                'stock_actual' => $stock !== false ? (int)$stock : 0
            ];
        } else {
            // Si no se encuentra el medicamento, se devuelve un mensaje de error.
            $response['message'] = 'No se encontró ningún medicamento con ese código de barras.';
        }
    } catch (PDOException $e) {
        // En caso de un error en la base de datos, se envía un mensaje genérico y se registra el detalle.
        $response['message'] = 'Error en la base de datos.';
        error_log("Error en ajax_buscar_medicamento.php: " . $e->getMessage());
    }
}

// --- BLOQUE 4: ENVÍO DE LA RESPUESTA FINAL ---
// Se imprime la respuesta final en formato JSON.
echo json_encode($response);
?>