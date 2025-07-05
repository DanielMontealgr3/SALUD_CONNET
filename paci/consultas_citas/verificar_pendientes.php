<?php
// =================================================================
// === INICIO DEL BLOQUE CORREGIDO (PORTABILIDAD) ===
// =================================================================

// 1. Inclusión de la configuración centralizada.
// Esto establece ROOT_PATH, BASE_URL, inicia sesión y conecta a la BD.
require_once __DIR__ . '/../../include/config.php';

// La sesión ya se inicia en config.php. No es necesario iniciarla manualmente.
// session_start();

header('Content-Type: application/json');

// 2. Validación de sesión más robusta (se incluye rol por seguridad).
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || !isset($_POST['id_detalle'])) {
    // Es importante enviar un código de estado HTTP adecuado para errores de autorización.
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Acceso no autorizado o datos insuficientes.']);
    exit;
}

$id_detalle = $_POST['id_detalle'];
$doc_usuario = $_SESSION['doc_usu'];

// La variable de conexión `$con` ya está disponible desde config.php.
// No es necesario crear una nueva instancia de Database.

// =================================================================
// === FIN DEL BLOQUE CORREGIDO ===
// El resto del código permanece exactamente igual.
// =================================================================

try {
    // 0. Validar que el detalle existe y pertenece al usuario
    $stmt_val = $con->prepare("SELECT hc.id_historia FROM detalles_histo_clini dhc JOIN historia_clinica hc ON dhc.id_historia = hc.id_historia JOIN citas c ON hc.id_cita = c.id_cita WHERE dhc.id_detalle = :id_detalle AND c.doc_pac = :doc_usuario");
    $stmt_val->execute([':id_detalle' => $id_detalle, ':doc_usuario' => $doc_usuario]);
    if ($stmt_val->rowCount() == 0) {
        throw new Exception('El ID de detalle no existe o no le pertenece.');
    }

    // 1. Obtener la farmacia del usuario
    $stmt_farm = $con->prepare("SELECT def.nit_farm FROM afiliados af JOIN detalle_eps_farm def ON af.id_eps = def.nit_eps WHERE af.doc_afiliado = ? LIMIT 1");
    $stmt_farm->execute([$doc_usuario]);
    $nit_farmacia = $stmt_farm->fetchColumn();

    if (!$nit_farmacia) {
        throw new Exception('No se pudo determinar la farmacia asignada al usuario.');
    }

    // 2. Buscar medicamentos pendientes para ese id_detalle
    $sql_pendientes = "
        SELECT ep.id_medicamento, m.nom_medicamento, ep.cantidad_pendiente
        FROM entrega_pendiente ep
        JOIN medicamentos m ON ep.id_medicamento = m.id_medicamento
        WHERE ep.id_detalle_histo = :id_detalle AND ep.id_estado = 10
    ";
    $stmt_pendientes = $con->prepare($sql_pendientes);
    $stmt_pendientes->execute([':id_detalle' => $id_detalle]);
    $pendientes = $stmt_pendientes->fetchAll(PDO::FETCH_ASSOC);

    if (empty($pendientes)) {
        echo json_encode(['no_pendientes' => true, 'message' => 'No se encontraron entregas pendientes para este ID. Puede proceder a agendar un turno normal.']);
        exit;
    }
    
    // 3. Verificar stock de cada medicamento pendiente
    $medicamentos_con_stock = [];
    $todos_disponibles = true;
    
    foreach ($pendientes as $pendiente) {
        $stmt_stock = $con->prepare("SELECT cantidad_actual FROM inventario_farmacia WHERE id_medicamento = ? AND nit_farm = ?");
        $stmt_stock->execute([$pendiente['id_medicamento'], $nit_farmacia]);
        $stock_actual = (int)$stmt_stock->fetchColumn();

        $disponible = ($stock_actual >= $pendiente['cantidad_pendiente']);
        if (!$disponible) {
            $todos_disponibles = false;
        }

        $medicamentos_con_stock[] = [
            'nombre' => $pendiente['nom_medicamento'],
            'cantidad' => $pendiente['cantidad_pendiente'],
            'disponible' => $disponible
        ];
    }

    echo json_encode([
        'pendientes' => true,
        'medicamentos' => $medicamentos_con_stock,
        'todos_disponibles' => $todos_disponibles
    ]);

} catch (Exception $e) {
    // Para errores del servidor, es mejor usar el código de estado 500.
    http_response_code(500); // Internal Server Error
    error_log("Error en verificar_pendientes.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error al verificar pendientes: ' . $e->getMessage()]);
}
?>