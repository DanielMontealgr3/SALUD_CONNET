<div class="modal fade" id="modalAfiliacionUsuario" tabindex="-1" aria-labelledby="modalAfiliacionUsuarioLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content" style="background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: .5rem;">
            <div class="modal-header bg-primary text-white" style="border-bottom: 1px solid #0056b3;">
                <h5 class="modal-title" id="modalAfiliacionUsuarioLabel">Registrar / Actualizar Afiliación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modalAfiliacionGlobalError" class="mb-3"></div>
                <div id="modalAfiliacionMessage" class="mb-3"></div>

                <form id="formAfiliacionUsuarioModal" method="POST" novalidate>
                    <input type="hidden" name="doc_afiliado_modal_hidden" id="doc_afiliado_modal_hidden" value="">
                    <input type="hidden" name="id_tipo_doc_modal_hidden" id="id_tipo_doc_modal_hidden" value="">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="doc_afiliado_modal_display" class="form-label">Documento Usuario:</label>
                            <input type="text" id="doc_afiliado_modal_display" class="form-control bg-light" value="" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="fecha_afi_modal_display" class="form-label">Fecha Afiliación (Sistema):</label>
                            <input type="text" id="fecha_afi_modal_display" class="form-control bg-light" value="<?php echo date('Y-m-d'); ?>" readonly>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="tipo_entidad_afiliacion_modal" class="form-label">Tipo de Entidad <span class="text-danger">(*)</span></label>
                            <select id="tipo_entidad_afiliacion_modal" name="tipo_entidad_afiliacion_modal" class="form-select" required>
                                <option value="">Seleccione tipo...</option>
                                <option value="eps">EPS (Entidad Promotora de Salud)</option>
                                <option value="arl">ARL (Administradora de Riesgos Laborales)</option>
                            </select>
                            <div class="invalid-feedback" id="error-tipo_entidad_afiliacion_modal"></div>
                        </div>
                        <div class="col-md-6" id="contenedor_select_entidad_eps_modal" style="display: none;">
                            <label for="entidad_especifica_eps_modal" class="form-label">EPS Específica <span class="text-danger">(*)</span></label>
                            <select id="entidad_especifica_eps_modal" name="entidad_especifica_eps_modal" class="form-select">
                                <option value="">Cargando EPS...</option>
                            </select>
                            <div class="invalid-feedback" id="error-entidad_especifica_eps_modal"></div>
                        </div>
                         <div class="col-md-6" id="contenedor_select_entidad_arl_modal" style="display: none;">
                            <label for="entidad_especifica_arl_modal" class="form-label">ARL Específica <span class="text-danger">(*)</span></label>
                            <select id="entidad_especifica_arl_modal" name="entidad_especifica_arl_modal" class="form-select">
                                <option value="">Cargando ARL...</option>
                            </select>
                            <div class="invalid-feedback" id="error-entidad_especifica_arl_modal"></div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="id_regimen_modal" class="form-label">Régimen <span class="text-danger">(*)</span></label>
                            <select id="id_regimen_modal" name="id_regimen_modal" class="form-select" required>
                                <option value="">Seleccione Régimen...</option>
                                <?php if (isset($modal_regimen_list) && is_array($modal_regimen_list)): ?>
                                    <?php foreach ($modal_regimen_list as $reg): ?>
                                        <option value="<?php echo htmlspecialchars($reg['id_regimen']); ?>">
                                            <?php echo htmlspecialchars($reg['nom_reg']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                     <option value="">No hay regimenes cargados</option>
                                <?php endif; ?>
                            </select>
                            <div class="invalid-feedback" id="error-id_regimen_modal"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="id_estado_modal" class="form-label">Estado Afiliación <span class="text-danger">(*)</span></label>
                            <select id="id_estado_modal" name="id_estado_modal" class="form-select" required>
                                <?php if (isset($modal_estado_list_afiliado) && is_array($modal_estado_list_afiliado)): ?>
                                    <?php foreach ($modal_estado_list_afiliado as $est): ?>
                                        <option value="<?php echo htmlspecialchars($est['id_est']); ?>" <?php echo ($est['id_est'] == 1) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(ucfirst($est['nom_est'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                 <?php else: ?>
                                     <option value="1" selected>Activo</option>
                                     <option value="2">Inactivo</option>
                                <?php endif; ?>
                            </select>
                            <div class="invalid-feedback" id="error-id_estado_modal"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" name="guardar_afiliacion_modal_submit" class="btn btn-primary" form="formAfiliacionUsuarioModal" id="btnGuardarAfiliacionModal">
                    <i class="bi bi-check-circle me-1"></i>Guardar Afiliación
                </button>
            </div>
        </div>
    </div>
</div>