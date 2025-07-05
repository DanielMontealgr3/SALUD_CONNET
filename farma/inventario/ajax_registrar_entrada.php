<?php
// --- BLOQUE 1: CABECERAS Y CONFIGURACIÓN INICIAL ---
// Se establece la cabecera para indicar que la respuesta será en formato JSON.
header('Content-Type: application/json; charset=utf-8');

// Se incluye el archivo de configuración central. La ruta __DIR__ . '/../../' sube dos niveles
// desde 'farma/inventario/' para encontrar la carpeta 'include/'.
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// --- BLOQUE 2: RESPUESTA POR DEFECTO Y VALIDACIÓN DEL MÉTODO ---
// Se inicializa una respuesta de error por defecto.
$response = ['success' => false, 'message' => 'Acceso denegado o método no permitido.', 'pendientes_cubiertos' => []];

// Se verifica que la petición se haya realizado mediante el método POST y que el ID del medicamento esté presente.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_medicamento'])) {
    
    // --- BLOQUE 3: RECOLECCIÓN Y VALIDACIÓN DE DATOS ---
    // Se recogen y limpian los datos enviados desde el formulario.
    $id_medicamento = filter_var($_POST['id_medicamento'], FILTER_VALIDATE_INT);
    $cantidad = filter_var($_POST['cantidad'], FILTER_VALIDATE_INT);
    $lote = trim($_POST['lote'] ?? '');
    $fecha_vencimiento = trim($_POST['fecha_vencimiento'] ?? '');
    $notas = trim($_POST['notas'] ?? null);
    
    // Se obtienen datos cruciales de la sesión.
    $nit_farm = $_SESSION['nit_farma'] ?? null;
    $documento_responsable = $_SESSION['doc_usu'] ?? null;
    
    // Se valida que todos los datos necesarios existan y sean válidos.
    if (!$id_medicamento || !$cantidad || $cantidad <= 0 || empty($lote) || empty($fecha_vencimiento) || empty($nit_farm) || !$documento_responsable) {
        $response['message'] = 'Todos los campos son obligatorios o falta información de sesión del responsable.';
        echo json_encode($response);
        exit;
    }

    // Se valida que la fecha de vencimiento no sea demasiado próxima.
    $fecha_minima = new DateTime();
    $fecha_minima->modify('+3 months');
    $fecha_venc_obj = new DateTime($fecha_vencimiento);
    if ($fecha_venc_obj < $fecha_minima) {
        $response['message'] = 'La fecha de vencimiento debe ser de al menos 3 meses en el futuro para poder registrar la entrada.';
        echo json_encode($response);
        exit;
    }

    // --- BLOQUE 4: TRANSACCIÓN DE BASE DE DATOS ---
    try {
        // La conexión $con ya está disponible desde el archivo config.php.
        // Se inicia una transacción para asegurar la integridad de los datos.
        $con->beginTransaction();

        // 1. Insertar el movimiento de entrada en la tabla de movimientos.
        $id_tipo_movimiento_entrada = 1; // 1 = ENTRADA
        $sql = "INSERT INTO movimientos_inventario (id_medicamento, nit_farm, id_usuario_responsable, id_tipo_mov, cantidad, lote, fecha_vencimiento, notas) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $con->prepare($sql);
        
        if ($stmt->execute([$id_medicamento, $nit_farm, $documento_responsable, $id_tipo_movimiento_entrada, $cantidad, $lote, $fecha_vencimiento, $notas])) {
            
            // 2. Obtener el nuevo stock total del medicamento después de la entrada.
            // Nota: Esta consulta se hace después de la inserción pero antes del commit para obtener el valor actualizado dentro de la misma transacción.
            $stmt_stock = $con->prepare("SELECT cantidad_actual FROM inventario_farmacia WHERE id_medicamento = ? AND nit_farm = ?");
            $stmt_stock->execute([$id_medicamento, $nit_farm]);
            $stock_actual = (int)$stmt_stock->fetchColumn();
            
            // 3. Buscar si esta nueva entrada de stock cubre alguna entrega pendiente.
            $sql_pendientes = "SELECT u.nom_usu, ep.cantidad_pendiente AS can_medica
                               FROM entrega_pendiente ep
                               JOIN detalles_histo_clini dh ON ep.id_detalle_histo = dh.id_detalle
                               JOIN historia_clinica hc ON dh.id_historia = hc.id_historia
                               JOIN citas c ON hc.id_cita = c.id_cita
                               JOIN usuarios u ON c.doc_pac = u.doc_usu
                               WHERE dh.id_medicam = ?
                               AND ep.id_estado = 10 -- Solo busca pendientes
                               AND ep.cantidad_pendiente <= ?";

            $stmt_pendientes = $con->prepare($sql_pendientes);
            $stmt_pendientes->execute([$id_medicamento, $stock_actual]);
            $pendientes_cubiertos = $stmt_pendientes->fetchAll(PDO::FETCH_ASSOC);

            // 4. Preparar la respuesta de éxito.
            $response['success'] = true;
            $response['message'] = 'Entrada de inventario registrada con éxito.';
            $response['pendientes_cubiertos'] = $pendientes_cubiertos;
            
            // 5. Confirmar todos los cambios en la base de datos.
            $con->commit();
        } else {
            $con->rollBack();
            $response['message'] = 'No se pudo ejecutar la inserción en la base de datos.';
        }

    } catch (PDOException $e) {
        // Si ocurre un error, se revierten todos los cambios.
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        $response['message'] = 'Se produjo un error en la base de datos. Es posible que el lote ya exista.';
        error_log("Error en ajax_registrar_entrada: " . $e->getMessage());
    }
}

// --- BLOQUE 5: ENVÍO DE LA RESPUESTA FINAL ---
// Se imprime la respuesta final en formato JSON.
echo json_encode($response);
?>