<!-- Este archivo contiene el HTML del modal y no requiere cambios. -->
<!-- Su estructura de formulario ya es correcta para ser procesada por guarda_consul.php -->
<div class="modal fade" id="modalConsulta" tabindex="-1" aria-labelledby="modalConsultaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalConsultaLabel"><i class="bi bi-clipboard2-pulse-fill me-2"></i>Iniciar Consulta Médica</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border-start border-4 border-primary">
                    <strong id="modalPacienteNombre">Paciente: </strong> <br>
                    <small id="modalPacienteDocumento" class="text-muted">Documento: </small>
                </div>
                <hr>
                <form id="formConsultaMedica" novalidate>
                    <input type="hidden" name="id_cita" id="modalIdCita">
                    <input type="hidden" name="doc_pac_hidden" id="modalDocPacHidden">
                    
                    <div class="mb-3">
                        <label for="motivo_de_cons" class="form-label">Motivo de consulta <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="motivo_de_cons" name="motivo_de_cons" rows="3" required data-validate="texto"></textarea>
                        <div class="invalid-feedback">Este campo es requerido.</div>
                    </div>
                    
                    <h6 class="mt-4">Signos Vitales y Medidas</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label for="presion" class="form-label">Presión Arterial <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="presion" name="presion" placeholder="120/80" required data-validate="presion">
                            <div class="invalid-feedback">Formato inválido. Ej: 120/80.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="saturacion" class="form-label">Saturación O<sub>2</sub> (%) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="saturacion" name="saturacion" placeholder="98" required data-validate="saturacion">
                            <div class="invalid-feedback">Solo números. Ej: 98.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="peso" class="form-label">Peso (kg) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="peso" name="peso" placeholder="70.5" required data-validate="numerodecimal">
                            <div class="invalid-feedback">Solo números y un punto decimal. Ej: 70.5.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="estatura" class="form-label">Estatura (m) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="estatura" name="estatura" placeholder="1.75" required data-validate="numerodecimal">
                            <div class="invalid-feedback">Solo números y un punto decimal. Ej: 1.75.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones / Anamnesis <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="4" required data-validate="texto"></textarea>
                        <div class="invalid-feedback">Este campo es requerido.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" form="formConsultaMedica" class="btn btn-primary" id="btnGuardarConsulta" disabled>
                    <i class="bi bi-save me-2"></i>Guardar y Continuar
                </button>
            </div>
        </div>
    </div>
</div>