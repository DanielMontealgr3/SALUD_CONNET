<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$id_medicamento = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$nit_farmacia = $_SESSION['nit_farmacia_asignada_actual'] ?? null;

if (!$id_medicamento || !$nit_farmacia) {
    exit('Datos insuficientes.');
}

try {
    $db = new database();
    $con = $db->conectar();
    
    $sql_medicamento = "SELECT nom_medicamento FROM medicamentos WHERE id_medicamento = :id_medicamento";
    $stmt_medicamento = $con->prepare($sql_medicamento);
    $stmt_medicamento->execute([':id_medicamento' => $id_medicamento]);
    $nombre_medicamento = $stmt_medicamento->fetchColumn();

    $stock_por_lote_sql = "SUM(CASE WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad WHEN id_tipo_mov IN (2, 4) THEN -cantidad ELSE 0 END)";
    $sql_lotes = "SELECT lote, fecha_vencimiento, $stock_por_lote_sql AS stock_lote, DATEDIFF(fecha_vencimiento, CURDATE()) as dias_restantes 
                  FROM movimientos_inventario 
                  WHERE id_medicamento = :id_medicamento AND nit_farm = :nit_farm 
                  GROUP BY lote, fecha_vencimiento 
                  HAVING stock_lote > 0 ORDER BY fecha_vencimiento ASC";
    
    $stmt_lotes = $con->prepare($sql_lotes);
    $stmt_lotes->execute([':id_medicamento' => $id_medicamento, ':nit_farm' => $nit_farmacia]);
    $lotes = $stmt_lotes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    exit('Error de base de datos.');
}
?>

<div class="modal fade" id="modalListaLotes" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lotes de: <strong><?php echo htmlspecialchars($nombre_medicamento); ?></strong></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">A continuación se listan todos los lotes con stock disponible para este medicamento.</p>
                <ul class="list-group">
                    <?php if (empty($lotes)): ?>
                        <li class="list-group-item">No se encontraron lotes con stock.</li>
                    <?php else: foreach ($lotes as $lote): ?>
                        <?php
                            $clase_lote = 'list-group-item-success';
                            if ($lote['dias_restantes'] <= 30 && $lote['dias_restantes'] >= 0) {
                                $clase_lote = 'list-group-item-warning';
                            } elseif ($lote['dias_restantes'] < 0) {
                                $clase_lote = 'list-group-item-danger';
                            }
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $clase_lote; ?>">
                            <div>
                                Lote: <strong><?php echo htmlspecialchars($lote['lote']); ?></strong>
                                <small class="d-block">Vence: <?php echo htmlspecialchars($lote['fecha_vencimiento']); ?> | Stock: <?php echo htmlspecialchars($lote['stock_lote']); ?></small>
                            </div>
                            <button class="btn btn-outline-dark btn-sm btn-ver-detalle-lote" data-id-medicamento="<?php echo $id_medicamento; ?>" data-lote="<?php echo htmlspecialchars($lote['lote']); ?>">
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