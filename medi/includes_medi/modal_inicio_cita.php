<!-- 1. Modal para el Formulario de Consulta Médica -->
<div class="modal fade" id="modalConsulta" tabindex="-1" aria-labelledby="modalConsultaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConsultaLabel">Iniciar Consulta Médica</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <strong id="modalPacienteNombre">Paciente: </strong> <br>
                    <small id="modalPacienteDocumento" class="text-muted">Documento: </small>
                </div>
                <hr>
                <form id="formConsultaMedica" novalidate>
                    <input type="hidden" name="id_cita" id="modalIdCita">
                    <input type="hidden" name="doc_pac_hidden" id="modalDocPacHidden">
                    
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="motivo_de_cons" class="form-label">Motivo de consulta <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="motivo_de_cons" name="motivo_de_cons" rows="3" required></textarea>
                            <div class="invalid-feedback">Solo se permiten letras, números, espacios, puntos y comas.</div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label for="presion" class="form-label">Presión Arterial <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="presion" name="presion" placeholder="ej: 120/80" required>
                            <div class="invalid-feedback">Formato inválido. Use números y "/". Ej: 120/80.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="saturacion" class="form-label">Saturación O<sub>2</sub> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="saturacion" name="saturacion" placeholder="ej: 98" required>
                            <div class="invalid-feedback">Solo se permiten números. Ej: 98.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="peso" class="form-label">Peso <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="peso" name="peso" placeholder="ej: 70.5" required>
                            <div class="invalid-feedback">Solo se permiten números y un punto decimal. Ej: 70.5.</div>
                        </div>
                        <div class="col-md-3">
                            <label for="estatura" class="form-label">Estatura <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="estatura" name="estatura" placeholder="ej: 1.75" required>
                            <div class="invalid-feedback">Solo se permiten números y un punto decimal. Ej: 1.75.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label for="observaciones" class="form-label">Observaciones / Anamnesis <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="4" required></textarea>
                            <div class="invalid-feedback">Solo se permiten letras, números, espacios, puntos y comas.</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" form="formConsultaMedica" class="btn btn-primary" id="btnGuardarConsulta" disabled>Guardar Consulta</button>
            </div>
        </div>
    </div>
</div>

<!-- 2. Modal de Éxito al Guardar la Consulta -->
<div class="modal fade" id="modalExitoConsulta" tabindex="-1" aria-labelledby="modalExitoLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalExitoLabel">Operación Exitosa</h5>
            </div>
            <div class="modal-body text-center p-4">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <p class="h5">La historia clínica ha sido guardada correctamente.</p>
                <p>Será redirigido para agregar los detalles de la consulta.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" id="btnRedirigir">Aceptar</button>
            </div>
        </div>
    </div>
</div>

<!-- 3. Modal para Paciente que No Asistió -->
<div class="modal fade" id="modalNoAsistio" tabindex="-1" aria-labelledby="modalNoAsistioLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalNoAsistioLabel"><i class="fas fa-user-clock me-2"></i>Cita No Atendida</h5>
            </div>
            <div class="modal-body text-center p-4">
                <p class="h5">El paciente no se presentó en el tiempo estipulado.</p>
                <p>La cita ha sido marcada automáticamente como **"No Asistió"** y el horario ha sido liberado.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>