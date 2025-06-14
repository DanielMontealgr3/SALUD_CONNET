<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

$db = new database();
$con = $db->conectar();
$nit_farmacia = $_SESSION['nit_farma'] ?? null;
if (!$nit_farmacia) { exit; }

$stock_por_lote_sql = "(SELECT SUM(CASE WHEN id_tipo_mov IN (1, 3, 5) THEN cantidad ELSE -cantidad END) FROM movimientos_inventario WHERE lote = mi.lote AND id_medicamento = mi.id_medicamento AND nit_farm = mi.nit_farm)";
$sql = "SELECT mi.id_medicamento, med.nom_medicamento, med.codigo_barras, mi.lote, $stock_por_lote_sql AS stock_lote FROM movimientos_inventario mi JOIN medicamentos med ON mi.id_medicamento = med.id_medicamento WHERE mi.nit_farm = :nit AND mi.fecha_vencimiento < CURDATE() GROUP BY mi.id_medicamento, mi.lote HAVING stock_lote > 0 ORDER BY med.nom_medicamento, mi.lote";
$stmt = $con->prepare($sql);
$stmt->execute([':nit' => $nit_farmacia]);
$vencidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="modal fade" id="modalRetiroVencidos" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-trash-fill me-2"></i>Retirar Lotes Vencidos del Inventario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning small"><i class="bi bi-info-circle-fill me-2"></i>Para retirar un lote, verifique el código de barras y el número de lote. El sistema registrará la salida de la cantidad total del lote vencido.</div>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead class="table-light">
              <tr>
                <th>Medicamento</th>
                <th style="width: 10%;">Lote</th>
                <th class="text-center" style="width: 8%;">Stock</th>
                <th style="width: 20%;">Validar Código Barras</th>
                <th style="width: 20%;">Validar Lote</th>
                <th class="text-center" style="width: 15%;">Acción</th>
              </tr>
            </thead>
            <tbody id="tabla-retiro-vencidos">
              <?php foreach ($vencidos as $item): ?>
              <tr id="lote-<?php echo htmlspecialchars($item['id_medicamento'] . '-' . preg_replace('/[^a-zA-Z0-9-]/', '_', $item['lote'])); ?>"
                  data-id-medicamento="<?php echo $item['id_medicamento']; ?>"
                  data-lote="<?php echo htmlspecialchars($item['lote']); ?>"
                  data-cantidad="<?php echo $item['stock_lote']; ?>"
                  data-codigo-barras="<?php echo htmlspecialchars($item['codigo_barras']); ?>">
                <td><strong><?php echo htmlspecialchars($item['nom_medicamento']); ?></strong></td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($item['lote']); ?></span></td>
                <td class="text-center"><strong><?php echo $item['stock_lote']; ?></strong></td>
                <td>
                  <div class="input-group input-group-sm">
                    <input type="text" class="form-control input-codigo-barras" placeholder="Escanear producto...">
                    <button class="btn btn-outline-secondary btn-validar-codigo" type="button" title="Validar Código"><i class="bi bi-check-lg"></i></button>
                  </div>
                </td>
                <td>
                   <div class="input-group input-group-sm">
                    <input type="text" class="form-control input-lote" placeholder="Confirmar lote..." disabled>
                    <button class="btn btn-outline-secondary btn-validar-lote" type="button" title="Validar Lote" disabled><i class="bi bi-check-lg"></i></button>
                  </div>
                </td>
                <td class="text-center"><button class="btn btn-danger btn-sm btn-retirar-lote" disabled><i class="bi bi-box-arrow-right"></i> Retirar</button></td>
              </tr>
              <?php endforeach; ?>
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