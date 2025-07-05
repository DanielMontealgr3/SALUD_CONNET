<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$db = new database();
$con = $db->conectar();
$nit_farmacia = $_SESSION['nit_farma'] ?? null;
$tipo_retiro = $_GET['tipo'] ?? 'vencidos';

if (!$nit_farmacia) { exit; }

$stock_por_lote_sql = "(SELECT SUM(CASE WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad WHEN id_tipo_mov IN (2, 4) THEN -cantidad ELSE 0 END) FROM movimientos_inventario WHERE lote = mi.lote AND id_medicamento = mi.id_medicamento AND nit_farm = mi.nit_farm)";

if ($tipo_retiro === 'vencidos') {
    $sql = "SELECT mi.id_medicamento, med.nom_medicamento, med.codigo_barras, mi.lote, mi.fecha_vencimiento, DATEDIFF(mi.fecha_vencimiento, CURDATE()) AS dias_restantes, $stock_por_lote_sql AS stock_lote FROM movimientos_inventario mi JOIN medicamentos med ON mi.id_medicamento = med.id_medicamento WHERE mi.nit_farm = :nit AND mi.fecha_vencimiento < CURDATE() GROUP BY mi.id_medicamento, mi.lote HAVING stock_lote > 0 ORDER BY mi.fecha_vencimiento ASC";
} else { // por_vencer
    $sql = "SELECT mi.id_medicamento, med.nom_medicamento, med.codigo_barras, mi.lote, mi.fecha_vencimiento, DATEDIFF(mi.fecha_vencimiento, CURDATE()) AS dias_restantes, $stock_por_lote_sql AS stock_lote FROM movimientos_inventario mi JOIN medicamentos med ON mi.id_medicamento = med.id_medicamento WHERE mi.nit_farm = :nit AND mi.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) GROUP BY mi.id_medicamento, mi.lote HAVING stock_lote > 0 ORDER BY mi.fecha_vencimiento ASC";
}

$stmt = $con->prepare($sql);
$stmt->execute([':nit' => $nit_farmacia]);
$items_a_retirar = $stmt->fetchAll(PDO::FETCH_ASSOC);

$modal_title = $tipo_retiro === 'vencidos' ? 'Retirar Lotes Vencidos' : 'Retirar Lotes Próximos a Vencer';
$alert_class = $tipo_retiro === 'vencidos' ? 'alert-danger' : 'alert-warning';
$alert_text = $tipo_retiro === 'vencidos' ? 'Estos lotes ya han superado su fecha de vencimiento y deben ser retirados inmediatamente.' : 'Estos lotes están próximos a vencer. Solo se pueden retirar aquellos con 15 días o menos restantes.';
?>

<div class="modal fade" id="modalRetiroInventario" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-trash-fill me-2"></i><?php echo $modal_title; ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert <?php echo $alert_class; ?> small"><i class="bi bi-info-circle-fill me-2"></i><?php echo $alert_text; ?></div>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th>Medicamento</th>
                <th style="width: 10%;">Lote</th>
                <th class="text-center" style="width: 8%;">Vence en</th>
                <th class="text-center" style="width: 8%;">Stock</th>
                <th style="width: 20%;">Validar Código Barras</th>
                <th style="width: 20%;">Validar Lote</th>
                <th class="text-center" style="width: 15%;">Acción</th>
              </tr>
            </thead>
            <tbody id="tabla-retiro-inventario">
              <?php foreach ($items_a_retirar as $item): 
                $motivo_retiro = ($item['dias_restantes'] < 0) ? 'vencido' : 'proximo_vencer';
                $es_retirable = ($motivo_retiro === 'vencido' || $item['dias_restantes'] <= 15);
                $row_class = $motivo_retiro === 'vencido' ? 'table-danger-light' : 'table-warning-light';
              ?>
              <tr id="lote-<?php echo htmlspecialchars($item['id_medicamento'] . '-' . preg_replace('/[^a-zA-Z0-9-]/', '_', $item['lote'])); ?>"
                  class="<?php echo $es_retirable ? $row_class : ''; ?>"
                  data-id-medicamento="<?php echo $item['id_medicamento']; ?>"
                  data-lote="<?php echo htmlspecialchars($item['lote']); ?>"
                  data-cantidad="<?php echo $item['stock_lote']; ?>"
                  data-codigo-barras="<?php echo htmlspecialchars($item['codigo_barras']); ?>"
                  data-motivo-retiro="<?php echo $motivo_retiro; ?>">
                <td><strong><?php echo htmlspecialchars($item['nom_medicamento']); ?></strong></td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($item['lote']); ?></span></td>
                <td class="text-center">
                    <?php if ($item['dias_restantes'] < 0): ?>
                        <span class="badge bg-danger">Vencido</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><?php echo $item['dias_restantes']; ?> días</span>
                    <?php endif; ?>
                </td>
                <td class="text-center"><strong><?php echo $item['stock_lote']; ?></strong></td>
                <td>
                  <?php if ($es_retirable): ?>
                  <div class="input-group input-group-sm">
                    <input type="text" class="form-control input-codigo-barras" placeholder="Escanear...">
                    <button class="btn btn-outline-primary btn-validar-codigo" type="button" title="Validar Código"><i class="bi bi-check-lg"></i></button>
                  </div>
                  <?php endif; ?>
                </td>
                <td>
                   <?php if ($es_retirable): ?>
                   <div class="input-group input-group-sm">
                    <input type="text" class="form-control input-lote" placeholder="Confirmar lote..." disabled>
                    <button class="btn btn-outline-primary btn-validar-lote" type="button" title="Validar Lote" disabled><i class="bi bi-check-lg"></i></button>
                  </div>
                   <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if ($es_retirable): ?>
                        <button class="btn btn-sm btn-retirar-lote" disabled><i class="bi bi-box-arrow-right"></i> Retirar</button>
                    <?php else: ?>
                        <span class="badge bg-info text-dark">Aún no es retirable</span>
                    <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
               <?php if (empty($items_a_retirar)): ?>
                <tr><td colspan="7" class="text-center p-4">No hay lotes que cumplan los criterios para mostrar.</td></tr>
               <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-cerrar-retiro" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>