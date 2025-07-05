<?php
// --- BLOQUE 1: CABECERAS Y CONFIGURACIÓN INICIAL ---
// Se establece la cabecera para indicar que la respuesta será en formato JSON.
header('Content-Type: application/json; charset=utf-8');

// Se incluye el archivo de configuración central para obtener la conexión a la base de datos.
require_once __DIR__ . '/../../include/config.php';

// --- BLOQUE 2: RESPUESTA POR DEFECTO ---
// Se inicializa una respuesta por defecto. Si algo falla, esto es lo que se enviará.
$response = ['success' => false, 'tipos' => []];

// --- BLOQUE 3: CONSULTA A LA BASE DE DATOS ---
try {
    // La conexión $con ya está disponible desde el archivo config.php.
    
    // Se ejecuta una consulta para obtener todos los tipos de medicamento, ordenados alfabéticamente.
    $stmt = $con->query("SELECT id_tip_medic, nom_tipo_medi FROM tipo_de_medicamento ORDER BY nom_tipo_medi ASC");
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si la consulta devuelve resultados, se actualiza la respuesta a exitosa y se añaden los datos.
    if ($tipos) {
        $response['success'] = true;
        $response['tipos'] = $tipos;
    }
} catch (PDOException $e) {
    // En caso de un error en la base de datos, no se envía el mensaje de error al cliente por seguridad,
    // pero se registra en el log del servidor para que el administrador pueda revisarlo.
    error_log("Error en ajax_obtener_tipos_medi.php: " . $e->getMessage());
}

// --- BLOQUE 4: ENVÍO DE LA RESPUESTA FINAL ---
// Se imprime la respuesta final en formato JSON. El script de JavaScript la usará para poblar el menú desplegable.
echo json_encode($response);
?>