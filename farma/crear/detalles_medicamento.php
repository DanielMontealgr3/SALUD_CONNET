<?php
// --- BLOQUE 1: CABECERAS Y CONFIGURACIÓN INICIAL ---
// Se establece la cabecera para indicar que la respuesta será en formato JSON.
header('Content-Type: application/json; charset=utf-8');

// Se incluye el archivo de configuración central. La ruta __DIR__ . '/../../' sube dos niveles
// desde 'farma/crear/' para encontrar la carpeta 'include/'.
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// --- BLOQUE 2: VALIDACIÓN DEL PARÁMETRO DE ENTRADA ---
// Se verifica que se haya proporcionado un ID de medicamento válido a través de la URL (GET).
// Si no es un entero válido, se detiene la ejecución y se devuelve un error JSON.
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'message' => 'ID de medicamento no válido.']);
    exit;
}

$id_medicamento = $_GET['id'];

// --- BLOQUE 3: CONSULTA A LA BASE DE DATOS ---
try {
    // La conexión $con ya está disponible desde el archivo config.php, no es necesario crearla de nuevo.
    
    // Consulta SQL para obtener los detalles del medicamento especificado, uniendo con la tabla de tipos.
    $sql = "SELECT 
                m.nom_medicamento, 
                m.descripcion, 
                m.codigo_barras, 
                tm.nom_tipo_medi 
            FROM medicamentos m 
            JOIN tipo_de_medicamento tm ON m.id_tipo_medic = tm.id_tip_medic 
            WHERE m.id_medicamento = :id_medicamento";
            
    $stmt = $con->prepare($sql);
    $stmt->execute([':id_medicamento' => $id_medicamento]);
    $medicamento = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- BLOQUE 4: RESPUESTA JSON ---
    // Si la consulta encuentra el medicamento, se envía una respuesta exitosa con los datos.
    if ($medicamento) {
        echo json_encode(['success' => true, 'medicamento' => $medicamento]);
    } else {
        // Si no se encuentra, se envía un mensaje de error.
        echo json_encode(['success' => false, 'message' => 'No se encontró el medicamento.']);
    }
} catch (PDOException $e) {
    // En caso de un error en la base de datos, se envía un mensaje genérico de error.
    error_log("Error en detalles_medicamento.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al consultar la base de datos.']);
}
?>