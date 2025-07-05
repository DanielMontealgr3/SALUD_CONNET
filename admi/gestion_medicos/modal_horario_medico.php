
<div class="modal fade" id="modalHorariosMedico" tabindex="-1" aria-labelledby="modalHorariosMedicoLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content"> 
            <div class="modal-header"> 
                <h5 class="modal-title" id="modalHorariosMedicoLabel">Horarios del Médico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <div class="text-center mb-3">
                    <h5 id="modalNombreMedico" class="mb-1 nombre-medico-titulo"></h5>
                    <small id="modalDocMedicoHorario" class="text-muted d-block"></small>
                </div>
                
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="selectFechaHorarioModal" class="form-label">Seleccione una fecha para ver el horario:</label>
                        <select id="selectFechaHorarioModal" class="form-select form-select-sm">
                            <option value="">Cargando fechas...</option>
                        </select>
                    </div>
                </div>
                <hr class="my-3">
                <h6 class="text-center mb-3 subtitulo-horas">Horas de trabajo para la fecha seleccionada:</h6>
                <div id="detalleHorarioDia" class="mt-2 detalle-horario-dia-container">
                    <p class="text-center text-muted mensaje-seleccione-fecha">Seleccione una fecha para ver los horarios.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>