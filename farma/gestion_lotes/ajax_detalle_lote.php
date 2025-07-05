<?php
// =========================================================================
// ==           AJAX_DETALLE_LOTE.PHP - VERSIÓN CORREGIDA Y EXPLICADA         ==
// =========================================================================

// --- BLOQUE 1: CONFIGURACIÓN CENTRAL Y SEGURIDAD ---
// Se incluye el archivo de configuración central. Este es el PRIMER y ÚNICO archivo
// de configuración que necesitamos. Se encarga de:
// 1. Iniciar las sesiones de forma segura.
// 2. Definir las constantes de ruta (ROOT_PATH, BASE_URL).
// 3. Crear la conexión a la base de datos ($con) que funcionará tanto en local como en hosting.
require_once __DIR__ . '/../../include/config.php';

// Se incluye el script de validación de sesión. Este script ahora puede usar
// las sesiones iniciadas por config.php para verificar si el usuario tiene permiso
// para acceder a esta funcionalidad. Se usa ROOT_PATH para una ruta segura.
require_once ROOT_PATH . '/include/validar_sesion.php';

// --- BLOQUE 2: DEFINICIÓN DEL TIPO DE RESPUESTA ---
// Se establece la cabecera HTTP para indicar que este script devolverá una respuesta
// en formato JSON. Esto es fundamental para que las peticiones AJAX (como las de JavaScript con `fetch` o jQuery)
// interpreten correctamente los datos que reciben.
header('Content-Type: application/json');

// --- BLOQUE 3: RECOLECCIÓN Y VALIDACIÓN DE PARÁMETROS ---
// Se recuperan los datos enviados a través de la URL (método GET).
// - `id`: El ID del medicamento. Se usa `filter_input` para limpiarlo y asegurar que es un número entero.
// - `lote`: El número de lote del producto. Se usa `trim` para quitar espacios en blanco.
// - `nit_farma`: Se obtiene el NIT de la farmacia desde la variable de sesión, estandarizando
//   el nombre a 'nit_farma' para ser consistente con el resto de la aplicación.
$id_medicamento = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$lote_str = trim($_GET['lote'] ?? '');
$nit_farmacia = $_SESSION['nit_farma'] ?? null; // Variable de sesión estandarizada.

// Se verifica que todos los datos necesarios existan. Si falta alguno, el script
// no puede continuar. Se devuelve un error en formato JSON y se detiene la ejecución con `exit`.
if (!$id_medicamento || empty($lote_str) || !$nit_farmacia) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos o sesión no encontrada.']);
    exit;
}

// --- BLOQUE 4: CONSULTA A LA BASE DE DATOS Y RESPUESTA ---
// Se envuelve toda la lógica de la base de datos en un bloque `try...catch` para manejar
// posibles errores de conexión o de consulta de forma segura, sin exponer detalles sensibles.
try {
    // IMPORTANTE: Ya no se crea una nueva conexión a la BD. Se usa la variable global `$con`
    // que fue creada y configurada en `config.php`.
    
    // Se define la consulta SQL para calcular el stock actual de un lote específico.
    // - SUM(CASE...): Suma las entradas (tipos 1, 3, 5) y resta las salidas (tipos 2, 4) para obtener el stock real.
    // - DATEDIFF(...): Calcula los días que faltan para el vencimiento del lote.
    $stock_por_lote_sql = "SUM(CASE WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad WHEN id_tipo_mov IN (2, 4) THEN -cantidad ELSE 0 END)";
    $sql = "SELECT lote, fecha_vencimiento, $stock_por_lote_sql AS stock_lote, DATEDIFF(fecha_vencimiento, CURDATE()) AS dias_restantes 
            FROM movimientos_inventario 
            WHERE id_medicamento = :id_medicamento AND lote = :lote AND nit_farm = :nit_farm 
            GROUP BY lote, fecha_vencimiento";
            
    // Se prepara la consulta para evitar inyecciones SQL.
    $stmt = $con->prepare($sql);
    
    // Se asocian los valores a los marcadores de la consulta y se ejecuta.
    $stmt->execute([
        ':id_medicamento' => $id_medicamento,
        ':lote' => $lote_str,
        ':nit_farm' => $nit_farmacia
    ]);
    
    // Se obtiene el resultado como un array asociativo.
    $lote_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se comprueba si se encontró el lote.
    if ($lote_data) {
        // Si se encontró, se devuelve una respuesta JSON exitosa con los datos del lote.
        echo json_encode(['success' => true, 'data' => $lote_data]);
    } else {
        // Si no se encontró (porque no existe o el stock es cero), se devuelve un mensaje de error.
        echo json_encode(['success' => false, 'message' => 'No se encontró información para el lote especificado.']);
    }

} catch (PDOException $e) {
    // Si ocurre un error durante la preparación o ejecución de la consulta (ej. un error de sintaxis SQL),
    // se captura aquí. Se registra el error real en el log del servidor (si está configurado)
    // y se devuelve un mensaje genérico al cliente para no exponer información sensible.
    error_log("Error en ajax_detalle_lote.php: " . $e->getMessage()); // Buena práctica
    echo json_encode(['success' => false, 'message' => 'Error al consultar la base de datos.']);
}
?>