<style>
    #modalAfiliacionUsuario .modal-dialog {max-width: 800px;}
    #modalAfiliacionUsuario .modal-content {background-color: #f0f2f5; border: 2px solid #87CEEB; border-radius: .5rem;}
    #modalAfiliacionUsuario .modal-header {background-color: #005A9C; color: white; border-bottom: none; padding: 1rem 1.5rem;}
    #modalAfiliacionUsuario .modal-header .btn-close {filter: invert(1) grayscale(100%) brightness(200%);}
    #modalAfiliacionUsuario .info-box {border: 1px solid #87CEEB; background-color: #ffffff; padding: 1.25rem; border-radius: .375rem; margin-bottom: 1.5rem;}
    #modalAfiliacionUsuario .info-box .form-label {color: #005A9C; font-size: 0.875rem;}
    #modalAfiliacionUsuario .form-label {font-weight: 500;}
</style>
<div class="modal fade" id="modalAfiliacionUsuario" tabindex="-1" aria-labelledby="modalAfiliacionUsuarioLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAfiliacionUsuarioLabel"><i class="bi bi-person-plus-fill me-2"></i>Registrar / Actualizar Afiliación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formAfiliacionUsuarioModal" novalidate>
                    <input type="hidden" name="doc_afiliado_modal_hidden" id="doc_afiliado_modal_hidden">
                    <input type="hidden" name="id_tipo_doc_modal_hidden" id="id_tipo_doc_modal_hidden">
                    
                    <div class="row g-3 mb-3 info-box">
                        <div class="col-md-6">
                            <label for="doc_afiliado_modal_display" class="form-label"><i class="bi bi-person-vcard me-2"></i>Documento Usuario:</label>
                            <input type="text" id="doc_afiliado_modal_display" class="form-control" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="fecha_afi_modal_display" class="form-label"><i class="bi bi-calendar-check me-2"></i>Fecha Afiliación (Sistema):</label>
                            <input type="text" id="fecha_afi_modal_display" class="form-control" value="<?php echo date('Y-m-d'); ?>" readonly>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="tipo_entidad_afiliacion_modal" class="form-label"><i class="bi bi-building me-2"></i>Tipo de Entidad <span class="text-danger">(*)</span></label>
                            <select id="tipo_entidad_afiliacion_modal" name="tipo_entidad_afiliacion_modal" class="form-select" required>
                                <option value="">Seleccione tipo...</option>
                                <option value="eps">EPS (Entidad Promotora de Salud)</option>
                                <option value="arl">ARL (Administradora de Riesgos Laborales)</option>
                            </select>
                            <div class="invalid-feedback" id="error-tipo_entidad_afiliacion_modal"></div>
                        </div>
                        <div class="col-md-6" id="contenedor_select_entidad_eps_modal" style="display: none;">
                            <label for="entidad_especifica_eps_modal" class="form-label"><i class="bi bi-hospital me-2"></i>EPS Específica <span class="text-danger">(*)</span></label>
                            <select id="entidad_especifica_eps_modal" name="entidad_especifica_eps_modal" class="form-select"><option value="">Cargando EPS...</option></select>
                            <div class="invalid-feedback" id="error-entidad_especifica_eps_modal"></div>
                        </div>
                         <div class="col-md-6" id="contenedor_select_entidad_arl_modal" style="display: none;">
                            <label for="entidad_especifica_arl_modal" class="form-label"><i class="bi bi-shield-check me-2"></i>ARL Específica <span class="text-danger">(*)</span></label>
                            <select id="entidad_especifica_arl_modal" name="entidad_especifica_arl_modal" class="form-select"><option value="">Cargando ARL...</option></select>
                            <div class="invalid-feedback" id="error-entidad_especifica_arl_modal"></div>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="id_regimen_modal" class="form-label"><i class="bi bi-journal-text me-2"></i>Régimen <span class="text-danger">(*)</span></label>
                            <select id="id_regimen_modal" name="id_regimen_modal" class="form-select" required>
                                <option value="">Seleccione Régimen...</option>
                                <?php foreach ($modal_regimen_list as $reg): ?>
                                    <option value="<?php echo htmlspecialchars($reg['id_regimen']); ?>"><?php echo htmlspecialchars($reg['nom_reg']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="error-id_regimen_modal"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="id_estado_modal" class="form-label"><i class="bi bi-check2-square me-2"></i>Estado Afiliación <span class="text-danger">(*)</span></label>
                            <select id="id_estado_modal" name="id_estado_modal" class="form-select" required>
                                <?php foreach ($modal_estado_list_afiliado as $est): ?>
                                    <option value="<?php echo htmlspecialchars($est['id_est']); ?>" <?php echo ($est['id_est'] == 1) ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($est['nom_est'])); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="error-id_estado_modal"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" form="formAfiliacionUsuarioModal" class="btn btn-primary" id="btnGuardarAfiliacionModal">
                    <i class="bi bi-check-circle me-1"></i>Guardar Afiliación
                </button>
            </div>
        </div>
    </div>
</div>