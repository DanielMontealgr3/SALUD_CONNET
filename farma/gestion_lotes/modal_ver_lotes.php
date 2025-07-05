<?php
// =========================================================================
// ==           MODAL_VER_LOTES.PHP - VERSIÓN CORREGIDA Y EXPLICADA         ==
// =========================================================================

// --- BLOQUE 1: CONFIGURACIÓN CENTRAL Y SEGURIDAD ---
// Se incluye el archivo de configuración centralizado. Este es el punto de entrada
// para toda nuestra configuración, asegurando que las sesiones, rutas (ROOT_PATH) y
// la conexión a la base de datos ($con) estén listas y correctamente configuradas
// para el entorno actual (local o producción).
require_once __DIR__ . '/../../include/config.php';

// Se incluye el script de validación de sesión. Verifica que el usuario tenga una sesión
// activa y los permisos necesarios. Utiliza ROOT_PATH para una ruta segura y confiable.
require_once ROOT_PATH . '/include/validar_sesion.php';


// --- BLOQUE 2: RECOLECCIÓN Y VALIDACIÓN DE PARÁMETROS ---
// Se recuperan los datos necesarios enviados a través de la URL (método GET).
// - `id`: El ID del medicamento, se limpia para asegurar que es un entero.
// - `nit_farma`: El NIT de la farmacia, se obtiene de la variable de sesión estandarizada.
$id_medicamento = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$nit_farmacia = $_SESSION['nit_farma'] ?? null; // Usamos la variable de sesión estandarizada.

// Si falta alguno de los datos esenciales, el script no puede generar el modal.
// Se interrumpe la ejecución con un mensaje de error simple.
if (!$id_medicamento || !$nit_farmacia) {
    // Podríamos generar un modal de error aquí, pero un exit es suficiente para este caso.
    exit('<div class="modal-body">Error: Datos insuficientes para cargar los lotes.</div>');
}


// --- BLOQUE 3: CONSULTAS A LA BASE DE DATOS ---
// Se envuelve toda la lógica de base de datos en un bloque `try...catch` para manejar
// errores de forma elegante sin romper la página.
try {
    // IMPORTANTE: Ya no se crea una nueva conexión. Se utiliza la variable global `$con`
    // proporcionada por `config.php`.
    
    // Consulta 1: Obtener el nombre del medicamento para mostrarlo en el título del modal.
    $sql_medicamento = "SELECT nom_medicamento FROM medicamentos WHERE id_medicamento = :id_medicamento";
    $stmt_medicamento = $con->prepare($sql_medicamento);
    $stmt_medicamento->execute([':id_medicamento' => $id_medicamento]);
    $nombre_medicamento = $stmt_medicamento->fetchColumn();

    // Consulta 2: Obtener todos los lotes activos (con stock > 0) para el medicamento.
    // - SUM(CASE...): Calcula el stock real de cada lote sumando entradas y restando salidas.
    // - DATEDIFF(...): Calcula los días restantes hasta la fecha de vencimiento.
    // - HAVING stock_lote > 0: Filtra los resultados para mostrar solo lotes con existencias.
    $stock_por_lote_sql = "SUM(CASE WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad WHEN id_tipo_mov IN (2, 4) THEN -cantidad ELSE 0 END)";
    $sql_lotes = "SELECT lote, fecha_vencimiento, $stock_por_lote_sql AS stock_lote, DATEDIFF(fecha_vencimiento, CURDATE()) as dias_restantes 
                  FROM movimientos_inventario 
                  WHERE id_medicamento = :id_medicamento AND nit_farm = :nit_farm 
                  GROUP BY lote, fecha_vencimiento 
                  HAVING stock_lote > 0 ORDER BY fecha_vencimiento ASC";
    
    // Se prepara y ejecuta la consulta de lotes.
    $stmt_lotes = $con->prepare($sql_lotes);
    $stmt_lotes->execute([':id_medicamento' => $id_medicamento, ':nit_farm' => $nit_farmacia]);
    $lotes = $stmt_lotes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si hay un error de base de datos, se interrumpe la ejecución y se muestra un mensaje
    // de error genérico dentro de lo que sería el modal.
    error_log("Error en modal_ver_lotes.php: " . $e->getMessage()); // Buena práctica
    exit('<div class="modal-body">Error al consultar la base de datos. Por favor, intente de nuevo.</div>');
}


// --- BLOQUE 4: GENERACIÓN DEL CÓDIGO HTML DEL MODAL ---
// Aquí comienza la salida HTML. El PHP anterior ha preparado todas las variables necesarias
// (`$nombre_medicamento`, `$lotes`) para construir el modal dinámicamente.
?>
<div class="modal fade" id="modalListaLotes" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lotes de: <strong><?php echo htmlspecialchars($nombre_medicamento ?? 'Medicamento no encontrado'); ?></strong></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">A continuación se listan todos los lotes con stock disponible para este medicamento.</p>
                <ul class="list-group">
                    <?php if (empty($lotes)): // Si el array de lotes está vacío ?>
                        <li class="list-group-item">No se encontraron lotes con stock para este producto.</li>
                    <?php else: foreach ($lotes as $lote): // Se itera sobre cada lote encontrado ?>
                        <?php
                            // Se determina el color del item de la lista según la fecha de vencimiento.
                            $clase_lote = 'list-group-item-light'; // Color por defecto
                            if ($lote['dias_restantes'] !== null) {
                                if ($lote['dias_restantes'] < 0) {
                                    $clase_lote = 'list-group-item-danger'; // Vencido
                                } elseif ($lote['dias_restantes'] <= 30) {
                                    $clase_lote = 'list-group-item-warning'; // Próximo a vencer
                                } else {
                                    $clase_lote = 'list-group-item-success'; // Buen estado
                                }
                            }
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $clase_lote; ?>">
                            <div>
                                Lote: <strong><?php echo htmlspecialchars($lote['lote']); ?></strong>
                                <small class="d-block">
                                    Vence: <strong><?php echo htmlspecialchars(date('d/m/Y', strtotime($lote['fecha_vencimiento']))); ?></strong> | 
                                    Stock: <strong><?php echo htmlspecialchars($lote['stock_lote']); ?></strong>
                                </small>
                            </div>
                            <!-- Este botón probablemente llama al otro script AJAX (ajax_detalle_lote.php) -->
                            <button class="btn btn-outline-dark btn-sm btn-ver-detalle-lote" 
                                    data-id-medicamento="<?php echo $id_medicamento; ?>" 
                                    data-lote="<?php echo htmlspecialchars($lote['lote']); ?>"
                                    title="Ver movimientos de este lote">
                                <i class="bi bi-eye-fill"></i> Detalles
                            </button>
                        </li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>