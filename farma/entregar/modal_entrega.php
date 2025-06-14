<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

if (!isset($_GET['id_historia']) || !isset($_GET['id_turno'])) {
    http_response_code(400); exit;
}

$id_historia = filter_var($_GET['id_historia'], FILTER_VALIDATE_INT);
$id_turno = filter_var($_GET['id_turno'], FILTER_VALIDATE_INT);
$id_detalle_unico = filter_input(INPUT_GET, 'id_detalle_unico', FILTER_VALIDATE_INT);
$id_entrega_pendiente = filter_input(INPUT_GET, 'id_entrega_pendiente', FILTER_VALIDATE_INT);

$cantidad_a_entregar_especifica = 0;

try {
    $db = new database(); $con = $db->conectar();
    
    // Obtener datos del paciente
    $sql_paciente = "SELECT u.nom_usu, u.doc_usu FROM historia_clinica hc JOIN citas c ON hc.id_cita = c.id_cita JOIN usuarios u ON c.doc_pac = u.doc_usu WHERE hc.id_historia = :id_historia";
    $stmt_paciente = $con->prepare($sql_paciente);
    $stmt_paciente->execute([':id_historia' => $id_historia]);
    $paciente = $stmt_paciente->fetch(PDO::FETCH_ASSOC);

    // Si es un pendiente, obtenemos la cantidad específica a entregar
    if ($id_entrega_pendiente && $id_detalle_unico) {
        $stmt_cant_pendiente = $con->prepare("SELECT cantidad_pendiente FROM entrega_pendiente WHERE id_entrega_pendiente = :id_pendiente AND id_detalle_histo = :id_detalle");
        $stmt_cant_pendiente->execute([':id_pendiente' => $id_entrega_pendiente, ':id_detalle' => $id_detalle_unico]);
        $cantidad_a_entregar_especifica = (int)$stmt_cant_pendiente->fetchColumn();
    }

    // Obtener medicamentos. Siempre se filtra por el detalle único si viene.
    $sql_medicamentos = "SELECT dh.id_detalle, m.id_medicamento, m.nom_medicamento, m.codigo_barras, dh.can_medica FROM detalles_histo_clini dh JOIN medicamentos m ON dh.id_medicam = m.id_medicamento WHERE dh.id_historia = :id_historia";
    if ($id_detalle_unico) {
        $sql_medicamentos .= " AND dh.id_detalle = :id_detalle_unico";
    }
    $stmt_medicamentos = $con->prepare($sql_medicamentos);
    $params_meds = [':id_historia' => $id_historia];
    if ($id_detalle_unico) {
        $params_meds[':id_detalle_unico'] = $id_detalle_unico;
    }
    $stmt_medicamentos->execute($params_meds);
    $medicamentos = $stmt_medicamentos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    http_response_code(500); exit;
}
?>
<style>
    .fila-procesada { background-color: #f8f9fa !important; }
    .fila-procesada .form-control { background-color: #e9ecef; border-color: #ced4da; }
    .fila-pendiente-lista { background-color: #fffbe6 !important; }
    .celda-accion { text-align: left !important; }
</style>

<div class="modal fade" id="modalRealizarEntrega" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" data-id-entrega-pendiente="<?php echo htmlspecialchars($id_entrega_pendiente ?? ''); ?>">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-box-seam-fill me-2"></i>Realizar Entrega de Medicamentos</h5>
                <button type="button" class="btn-close" id="btn-cerrar-modal-entrega" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row mb-4 bg-light p-3 rounded">
                    <div class="col-md-8">
                        <small class="text-muted d-block">Paciente</small>
                        <strong><?php echo htmlspecialchars($paciente['nom_usu'] ?? 'N/A'); ?></strong> (Doc: <?php echo htmlspecialchars($paciente['doc_usu'] ?? 'N/A'); ?>)
                    </div>
                    <div class="col-md-4 text-md-end">
                        <small class="text-muted d-block">Turno / Pendiente</small>
                        #<strong><?php echo htmlspecialchars($id_turno); ?></strong>
                    </div>
                </div>

                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:5%;"></th>
                            <th>Medicamento</th>
                            <th class="text-center" style="width:8%;">Cant.</th>
                            <th style="width:25%;">Verificar Código</th>
                            <th style="width:25%;">Ingresar Lotes</th>
                            <th style="width:20%;">Acción Final</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-tabla-entrega">
                        <?php foreach ($medicamentos as $med): 
                            // Determinamos la cantidad final a usar en la fila
                            $cantidad_requerida = ($id_entrega_pendiente && $cantidad_a_entregar_especifica > 0) ? $cantidad_a_entregar_especifica : $med['can_medica'];
                        ?>
                        <tr id="med-fila-<?php echo $med['id_detalle']; ?>" data-id-turno="<?php echo $id_turno; ?>" data-id-detalle="<?php echo $med['id_detalle']; ?>" data-id-medicamento="<?php echo $med['id_medicamento']; ?>" data-codigo-barras="<?php echo $med['codigo_barras']; ?>" data-cantidad-requerida="<?php echo $cantidad_requerida; ?>">
                            <td class="text-center estado-verificacion">
                                <i class="bi bi-hourglass-split text-warning fs-4" title="Pendiente"></i>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($med['nom_medicamento']); ?></strong>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary rounded-pill fs-6"><?php echo htmlspecialchars($cantidad_requerida); ?></span>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" placeholder="Escanear..." name="codigo_barras_verif" required>
                                    <button class="btn btn-outline-primary btn-verificar-codigo" type="button" title="Verificar"><i class="bi bi-check2-circle"></i></button>
                                </div>
                            </td>
                            <td class="celda-lotes">
                            </td>
                            <td class="celda-accion">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" id="btn-generar-pendientes-lote" style="display: none;">
                    <i class="bi bi-file-earmark-plus-fill me-2"></i> Generar Todos los Pendientes
                </button>
                <button type="button" class="btn btn-secondary" id="btn-cancelar-entrega" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-finalizar-entrega-completa" disabled>
                    <i class="bi bi-truck me-2"></i> Finalizar
                </button>
            </div>
        </div>
    </div>
</div>