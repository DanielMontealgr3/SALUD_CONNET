<?php
// --- BLOQUE 1: CONFIGURACIÓN Y SEGURIDAD ---
// Se usa la nueva estructura de inclusión robusta.
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// --- BLOQUE 2: VALIDACIÓN DE PARÁMETROS ---
$id_historia = filter_input(INPUT_GET, 'id_historia', FILTER_VALIDATE_INT);
$id_turno = filter_input(INPUT_GET, 'id_turno', FILTER_VALIDATE_INT);

// Si los IDs principales no son válidos, se detiene la ejecución.
if (!$id_historia || !$id_turno) {
    http_response_code(400); // Bad Request
    exit('<div class="modal-body">Error: Faltan parámetros esenciales para cargar la entrega.</div>');
}

// Se obtienen parámetros opcionales para entregas específicas (pendientes).
$id_detalle_unico = filter_input(INPUT_GET, 'id_detalle_unico', FILTER_VALIDATE_INT);
$id_entrega_pendiente = filter_input(INPUT_GET, 'id_entrega_pendiente', FILTER_VALIDATE_INT);

// --- BLOQUE 3: CONSULTAS A LA BASE DE DATOS ---
$cantidad_a_entregar_especifica = 0;

try {
    // Se usa la conexión global $con de config.php.
    
    // Consulta para obtener los datos del paciente.
    $sql_paciente = "SELECT u.nom_usu, u.doc_usu FROM historia_clinica hc JOIN citas c ON hc.id_cita = c.id_cita JOIN usuarios u ON c.doc_pac = u.doc_usu WHERE hc.id_historia = :id_historia LIMIT 1";
    $stmt_paciente = $con->prepare($sql_paciente);
    $stmt_paciente->execute([':id_historia' => $id_historia]);
    $paciente = $stmt_paciente->fetch(PDO::FETCH_ASSOC);
    
    // Si se trata de un pendiente, se busca la cantidad específica a entregar.
    if ($id_entrega_pendiente && $id_detalle_unico) {
        $stmt_cant = $con->prepare("SELECT cantidad_pendiente FROM entrega_pendiente WHERE id_entrega_pendiente = :id_p AND id_detalle_histo = :id_d");
        $stmt_cant->execute([':id_p' => $id_entrega_pendiente, ':id_d' => $id_detalle_unico]);
        $cantidad_a_entregar_especifica = (int)$stmt_cant->fetchColumn();
    }

    // Consulta para obtener los medicamentos de la fórmula.
    $sql_medicamentos = "SELECT dh.id_detalle, m.id_medicamento, m.nom_medicamento, m.codigo_barras, dh.can_medica FROM detalles_histo_clini dh JOIN medicamentos m ON dh.id_medicam = m.id_medicamento WHERE dh.id_historia = :id_historia";
    $params_meds = [':id_historia' => $id_historia];
    
    // Si es una entrega de un solo ítem (pendiente), se filtra por ese detalle.
    if ($id_detalle_unico) {
        $sql_medicamentos .= " AND dh.id_detalle = :id_detalle_unico";
        $params_meds[':id_detalle_unico'] = $id_detalle_unico;
    }
    
    $stmt_medicamentos = $con->prepare($sql_medicamentos);
    $stmt_medicamentos->execute($params_meds);
    $medicamentos = $stmt_medicamentos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error en modal_entrega.php: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    exit('<div class="modal-body">Error al conectar con la base de datos.</div>');
}

// --- BLOQUE 4: GENERACIÓN DEL HTML DEL MODAL ---
?>
<!-- Estilos CSS restaurados de la versión funcional -->
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
                <!-- Información del Paciente y Turno -->
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

                <!-- Tabla de Medicamentos a Entregar -->
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:5%;">Estado</th>
                            <th>Medicamento</th>
                            <th class="text-center" style="width:8%;">Cant.</th>
                            <th style="width:20%;">Verificar Código</th>
                            <th style="width:30%;">Ingresar Lotes</th>
                            <th style="width:17%;">Acción Final</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpo-tabla-entrega">
                        <?php foreach ($medicamentos as $med): 
                            // Determina la cantidad requerida, priorizando la del pendiente si existe.
                            $cantidad_requerida = ($id_entrega_pendiente && $cantidad_a_entregar_especifica > 0) ? $cantidad_a_entregar_especifica : $med['can_medica'];
                        ?>
                        <tr id="med-fila-<?php echo $med['id_detalle']; ?>" data-id-turno="<?php echo $id_turno; ?>" data-id-detalle="<?php echo $med['id_detalle']; ?>" data-id-medicamento="<?php echo $med['id_medicamento']; ?>" data-codigo-barras="<?php echo htmlspecialchars($med['codigo_barras']); ?>" data-cantidad-requerida="<?php echo $cantidad_requerida; ?>">
                            <td class="text-center estado-verificacion">
                                <i class="bi bi-hourglass-split text-warning fs-4" title="Pendiente de verificación"></i>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($med['nom_medicamento']); ?></strong>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary rounded-pill fs-6"><?php echo htmlspecialchars($cantidad_requerida); ?></span>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" placeholder="Escanear o escribir..." name="codigo_barras_verif" required>
                                    <button class="btn btn-outline-primary btn-verificar-codigo" type="button" title="Verificar código"><i class="bi bi-check2-circle"></i></button>
                                </div>
                            </td>
                            <td class="celda-lotes">
                                <!-- El contenido de esta celda se cargará con AJAX -->
                            </td>
                            <td class="celda-accion text-center">
                                <!-- El contenido de esta celda se cargará con AJAX -->
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <!-- Botón de pendientes restaurado -->
                <button type="button" class="btn btn-warning" id="btn-generar-pendientes-lote" style="display: none;">
                    <i class="bi bi-file-earmark-plus-fill me-2"></i> Generar Todos los Pendientes
                </button>
                <button type="button" class="btn btn-secondary" id="btn-cancelar-entrega" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-finalizar-entrega-completa" disabled>
                    <i class="bi bi-truck me-2"></i> Finalizar Entrega
                </button>
            </div>
        </div>
    </div>
</div>