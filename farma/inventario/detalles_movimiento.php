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
$response = ['success' => false, 'message' => 'Error inesperado.'];

// Se verifica que se haya proporcionado un ID de movimiento válido a través de la URL (GET).
if (!isset($_GET['id_movimiento']) || !is_numeric($_GET['id_movimiento'])) {
    $response['message'] = 'ID de movimiento no válido.';
    echo json_encode($response);
    exit;
}

$id_movimiento = intval($_GET['id_movimiento']);
$nit_farmacia_actual = $_SESSION['nit_farma'] ?? null;

// Se verifica que el NIT de la farmacia esté en la sesión.
if (!$nit_farmacia_actual) {
    $response['message'] = 'No se pudo identificar la farmacia.';
    echo json_encode($response);
    exit;
}

// --- BLOQUE 3: CONSULTA A LA BASE DE DATOS ---
try {
    // La conexión $con ya está disponible desde el archivo config.php.
    
    // Consulta SQL para obtener todos los detalles de un movimiento específico.
    $sql = "SELECT 
                mi.id_movimiento, 
                mi.cantidad, 
                mi.lote, 
                mi.fecha_vencimiento, 
                mi.fecha_movimiento, 
                mi.notas,
                med.nom_medicamento, 
                med.codigo_barras,
                tm.nom_mov, 
                mi.id_tipo_mov,
                u.nom_usu AS nombre_responsable, 
                u.doc_usu AS doc_responsable,
                f.nom_farm
            FROM movimientos_inventario mi
            JOIN medicamentos med ON mi.id_medicamento = med.id_medicamento
            JOIN tipo_movimiento tm ON mi.id_tipo_mov = tm.id_tipo_mov
            JOIN farmacias f ON mi.nit_farm = f.nit_farm
            LEFT JOIN usuarios u ON mi.id_usuario_responsable = u.doc_usu
            WHERE mi.id_movimiento = :id_movimiento AND mi.nit_farm = :nit_farm";

    $stmt = $con->prepare($sql);
    $stmt->execute([':id_movimiento' => $id_movimiento, ':nit_farm' => $nit_farmacia_actual]);
    $movimiento = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- BLOQUE 4: RESPUESTA JSON ---
    // Si la consulta encuentra el movimiento, se envía una respuesta exitosa con los datos.
    if ($movimiento) {
        $response['success'] = true;
        $response['data'] = $movimiento;
    } else {
        // Si no se encuentra, se envía un mensaje de error.
        $response['message'] = 'No se encontró el movimiento o no tiene permiso para verlo.';
    }

} catch (PDOException $e) {
    // En caso de un error en la base de datos, se envía un mensaje genérico y se registra el detalle.
    $response['message'] = 'Error en la base de datos.';
    error_log("Error en detalles_movimiento.php: " . $e->getMessage());
}

// --- BLOQUE 5: ENVÍO DE LA RESPUESTA FINAL ---
// Se imprime la respuesta final en formato JSON.
echo json_encode($response);
?>