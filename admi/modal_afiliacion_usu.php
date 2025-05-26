<style>
    #modalAfiliacionUsuario .modal-dialog {
        max-width: 800px;
    }
    #modalAfiliacionUsuario .modal-content {
        background-color: #f0f2f5;
        border: 2px solid #87CEEB; 
        border-radius: .5rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    #modalAfiliacionUsuario .modal-header {
        background-color: #005A9C; 
        color: white;
        border-bottom: none;
        border-top-left-radius: calc(.5rem - 2px);
        border-top-right-radius: calc(.5rem - 2px);
        padding: 1rem 1.5rem;
    }
    #modalAfiliacionUsuario .modal-header .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
        padding: 0.75rem;
        margin: -0.75rem -0.75rem -0.75rem auto;
    }
    #modalAfiliacionUsuario .modal-title {
        font-size: 1.25rem;
        font-weight: 500;
    }
    #modalAfiliacionUsuario .modal-body {
        padding: 1.5rem;
    }
    #modalAfiliacionUsuario .info-box {
        border: 1px solid #87CEEB; 
        background-color: #ffffff; 
        padding: 1.25rem;
        border-radius: .375rem; 
        margin-bottom: 1.5rem;
    }
    #modalAfiliacionUsuario .info-box .form-label {
        color: #005A9C;
        margin-bottom: .25rem;
        font-size: 0.875rem;
    }
     #modalAfiliacionUsuario .info-box .form-control[readonly] {
        background-color: #e9ecef;
        border: 1px solid #ced4da;
    }
    #modalAfiliacionUsuario .form-label {
        font-weight: 600;
        margin-bottom: .5rem;
        color: #343a40;
    }
    #modalAfiliacionUsuario .form-select,
    #modalAfiliacionUsuario .form-control {
        border-radius: .375rem;
    }
    #modalAfiliacionUsuario .invalid-feedback {
        color: #dc3545;
        font-size: 0.875em;
        display: block;
        margin-top: .25rem;
    }
    #modalAfiliacionUsuario .text-danger {
        color: #dc3545 !important;
    }
    #modalAfiliacionUsuario .modal-footer {
        background-color: #f0f2f5;
        border-top: 1px solid #dee2e6;
        padding: 1rem 1.5rem;
        border-bottom-left-radius: calc(.5rem - 2px);
        border-bottom-right-radius: calc(.5rem - 2px);
    }
    #modalAfiliacionUsuario .modal-footer .btn-primary {
        background-color: #005A9C;
        border-color: #005A9C;
        font-weight: 500;
        padding: .5rem 1rem;
    }
    #modalAfiliacionUsuario .modal-footer .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
        color: white;
        font-weight: 500;
        padding: .5rem 1rem;
    }
</style>

<div class="modal fade" id="modalAfiliacionUsuario" tabindex="-1" aria-labelledby="modalAfiliacionUsuarioLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAfiliacionUsuarioLabel">Registrar / Actualizar Afiliación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modalAfiliacionGlobalError" class="mb-3"></div>
                <div id="modalAfiliacionMessage" class="mb-3"></div>

                <form id="formAfiliacionUsuarioModal" method="POST" novalidate>
                    <input type="hidden" name="doc_afiliado_modal_hidden" id="doc_afiliado_modal_hidden" value="">
                    <input type="hidden" name="id_tipo_doc_modal_hidden" id="id_tipo_doc_modal_hidden" value="">
                    
                    <div class="row g-3 mb-3 info-box">
                        <div class="col-md-6">
                            <label for="doc_afiliado_modal_display" class="form-label">Documento Usuario:</label>
                            <input type="text" id="doc_afiliado_modal_display" class="form-control" value="" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="fecha_afi_modal_display" class="form-label">Fecha Afiliación (Sistema):</label>
                            <input type="text" id="fecha_afi_modal_display" class="form-control" value="<?php echo date('Y-m-d'); ?>" readonly>
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
                    
                    <div class="row g-3">
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" name="guardar_afiliacion_modal_submit" class="btn btn-primary" form="formAfiliacionUsuarioModal" id="btnGuardarAfiliacionModal">
                    <i class="bi bi-check-circle me-1"></i>Guardar Afiliación
                </button>
            </div>
        </div>
    </div>
</div>