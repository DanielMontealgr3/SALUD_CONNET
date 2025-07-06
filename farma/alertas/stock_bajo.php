<?php
// --- BLOQUE 1: CONFIGURACIÓN Y SEGURIDAD ---
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// --- BLOQUE 2: PREPARACIÓN DE LA RESPUESTA Y PARÁMETROS ---
// Se establece la cabecera JSON desde el principio para asegurar la respuesta correcta.
header('Content-Type: application/json; charset=utf-8');

// Se usa la variable de sesión estandarizada
$nit_farmacia = $_SESSION['nit_farma'] ?? null;
// Umbral para considerar el stock como bajo
$umbral_stock_bajo = 10;

// Si no se pudo obtener el NIT de la farmacia, se devuelve un error JSON y se detiene.
if (!$nit_farmacia) {
    echo json_encode(['error' => 'No se pudo identificar la farmacia.']);
    exit;
}

// --- BLOQUE 3: CONSULTA A LA BASE DE DATOS ---
try {
    // Se usa la conexión global $con de config.php
    global $con;
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tu consulta SQL original (es correcta)
    $sql = "SELECT 
                med.nom_medicamento,
                inv.cantidad_actual,
                med.codigo_barras
            FROM inventario_farmacia inv
            JOIN medicamentos med ON inv.id_medicamento = med.id_medicamento
            WHERE inv.nit_farm = :nit_farmacia
            AND inv.cantidad_actual <= :umbral
            ORDER BY inv.cantidad_actual ASC";

    $stmt = $con->prepare($sql);
    $stmt->bindParam(':nit_farmacia', $nit_farmacia, PDO::PARAM_STR);
    $stmt->bindParam(':umbral', $umbral_stock_bajo, PDO::PARAM_INT);
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- BLOQUE 4: ENVÍO DE LA RESPUESTA ---
    // Se envían los resultados (incluso si es un array vacío)
    echo json_encode($resultados);

} catch (PDOException $e) {
    // En caso de error de base de datos, se registra y se devuelve un error JSON.
    error_log("Error en alerta de stock bajo: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Ocurrió un error al consultar el inventario.']);
}
?>