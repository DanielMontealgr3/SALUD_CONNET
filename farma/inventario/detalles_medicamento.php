<?php
// --- BLOQUE 1: CABECERAS Y CONFIGURACIÓN INICIAL ---
// Se establece la cabecera para indicar que la respuesta será en formato JSON.
header('Content-Type: application/json; charset=utf-8');

// Se incluye el archivo de configuración central. La ruta __DIR__ . '/../../' sube dos niveles
// desde 'farma/inventario/' para encontrar la carpeta 'include/'.
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// --- BLOQUE 2: VALIDACIÓN DE PARÁMETROS Y SESIÓN ---
// Se verifica que se haya proporcionado un ID de medicamento válido a través de la URL (GET).
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo json_encode(['success' => false, 'message' => 'ID de medicamento no válido.']);
    exit;
}

// Se verifica que el NIT de la farmacia esté en la sesión, ya que es necesario para la consulta.
if (!isset($_SESSION['nit_farma'])) {
    echo json_encode(['success' => false, 'message' => 'No se pudo identificar la farmacia.']);
    exit;
}

$id_medicamento = $_GET['id'];
$nit_farmacia = $_SESSION['nit_farma'];

// --- BLOQUE 3: CONSULTA A LA BASE DE DATOS ---
try {
    // La conexión $con ya está disponible desde el archivo config.php.
    
    // Consulta SQL para obtener los detalles completos del medicamento, incluyendo su stock y estado
    // en la farmacia actual. Se usa LEFT JOIN para asegurar que el medicamento se muestre incluso si no tiene inventario.
    $sql = "SELECT 
                m.nom_medicamento, 
                m.descripcion, 
                m.codigo_barras, 
                tm.nom_tipo_medi, 
                i.cantidad_actual, 
                e.nom_est, 
                e.id_est 
            FROM medicamentos m 
            JOIN tipo_de_medicamento tm ON m.id_tipo_medic = tm.id_tip_medic 
            LEFT JOIN inventario_farmacia i ON m.id_medicamento = i.id_medicamento AND i.nit_farm = :nit_farma 
            LEFT JOIN estado e ON i.id_estado = e.id_est 
            WHERE m.id_medicamento = :id_medicamento";
            
    $stmt = $con->prepare($sql);
    $stmt->execute([':id_medicamento' => $id_medicamento, ':nit_farma' => $nit_farmacia]);
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
    // En caso de un error en la base de datos, se envía un mensaje genérico y se registra el detalle.
    error_log("Error al obtener detalles de medicamento: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos.']);
}
?>