document.addEventListener('DOMContentLoaded', () => {
    let currentModalInstance = null;
    const modalEditarContainer = document.getElementById('modalEditarRegistroEnfermedadContainer');
    const modalConfirmacionEliminar = document.getElementById('modalConfirmacionEliminarRegistroEnfermedad');
    const mensajeConfirmacionEliminar = document.getElementById('mensajeConfirmacionRegistroEnfermedad');
    const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminacionRegistroEnfermedad');
    const btnCancelarEliminar = document.getElementById('btnCancelarEliminacionRegistroEnfermedad');
    let formEliminar = null;

    function limpiarFeedbackModal(form) {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.is-valid').forEach(el => el.classList.remove('is-valid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        const globalMsg = form.querySelector('.modal-update-message-placeholder');
        if (globalMsg) globalMsg.innerHTML = '';
    }

    function mostrarErrorCampoModal(inputEl, mensaje) {
        inputEl.classList.add('is-invalid'); inputEl.classList.remove('is-valid');
        const feedbackEl = inputEl.parentElement.querySelector('.invalid-feedback');
        if (feedbackEl) feedbackEl.textContent = mensaje;
    }
    
    function mostrarExitoCampoModal(inputEl) {
        inputEl.classList.add('is-valid'); inputEl.classList.remove('is-invalid');
    }

    function mostrarMensajeGlobalModal(form, mensaje, tipo = 'danger') {
        const globalMsgDiv = form.querySelector('.modal-update-message-placeholder');
        if (globalMsgDiv) {
            globalMsgDiv.innerHTML = `<div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                ${mensaje} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
        }
    }

    function validarFormularioModalEdicion(form, tipoRegistro) {
        let esValido = true;
        limpiarFeedbackModal(form);
        const btnGuardar = form.closest('.modal-content').querySelector('#saveRegistroEnfermedadChangesButton');
        const nombreInput = form.querySelector('#nombre_edit');

        if (!nombreInput.value.trim()) { mostrarErrorCampoModal(nombreInput, 'Nombre es requerido.'); esValido = false; }
        else if (!/^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\s.,()#-]+$/u.test(nombreInput.value.trim())) { mostrarErrorCampoModal(nombreInput, 'Nombre contiene caracteres inválidos.'); esValido = false; }
        else if (nombreInput.value.trim().length < 3 || nombreInput.value.trim().length > 150) { mostrarErrorCampoModal(nombreInput, 'Nombre 3-150 caracteres.'); esValido = false; }
        else { mostrarExitoCampoModal(nombreInput); }

        if (tipoRegistro === 'enfermedad') {
            const tipoFkSelect = form.querySelector('#id_tipo_enfer_fk_edit');
            if (tipoFkSelect && !tipoFkSelect.value) { mostrarErrorCampoModal(tipoFkSelect, 'Tipo es requerido.'); esValido = false; }
            else if (tipoFkSelect) { mostrarExitoCampoModal(tipoFkSelect); }
        }
        
        if(btnGuardar) btnGuardar.disabled = !esValido;
        return esValido;
    }
    
    function setupModalEventListenersEdicion(modalForm, tipoRegistro) {
        const btnGuardar = modalForm.closest('.modal-content').querySelector('#saveRegistroEnfermedadChangesButton');
        if(btnGuardar) btnGuardar.disabled = false;

        const nombreInput = modalForm.querySelector('#nombre_edit');
        nombreInput.addEventListener('input', () => validarFormularioModalEdicion(modalForm, tipoRegistro));
        nombreInput.addEventListener('blur', () => validarFormularioModalEdicion(modalForm, tipoRegistro));

        if (tipoRegistro === 'enfermedad') {
            const tipoFkSelect = modalForm.querySelector('#id_tipo_enfer_fk_edit');
            if(tipoFkSelect){
                tipoFkSelect.addEventListener('change', () => validarFormularioModalEdicion(modalForm, tipoRegistro));
                tipoFkSelect.addEventListener('blur', () => validarFormularioModalEdicion(modalForm, tipoRegistro));
            }
        }
        validarFormularioModalEdicion(modalForm, tipoRegistro); 
    }

    document.querySelectorAll('.btn-editar-registro-enf').forEach(button => {
        button.addEventListener('click', async function () {
            const idRegistro = this.dataset.id;
            const tipoRegistro = this.dataset.tipo; // 'tipo_enfermedad' o 'enfermedad'

            if (modalEditarContainer) {
                modalEditarContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center p-5"><div class="spinner-border text-primary"></div><p class="ms-2 mb-0">Cargando...</p></div>';
                if (currentModalInstance) { currentModalInstance.hide(); }
                try {
                    const response = await fetch(`modal_editar_registro_enfermedad.php?id_registro=${idRegistro}&tipo_registro=${tipoRegistro}`);
                    if (!response.ok) throw new Error('Error al cargar modal.');
                    modalEditarContainer.innerHTML = await response.text();
                    const modalElement = document.getElementById('editRegistroEnfermedadModal');
                    if (modalElement) {
                        currentModalInstance = new bootstrap.Modal(modalElement); currentModalInstance.show();
                        const form = modalElement.querySelector('#editRegistroEnfermedadForm');
                        if (form) {
                            setupModalEventListenersEdicion(form, tipoRegistro);
                            form.addEventListener('submit', async function(e) {
                                e.preventDefault(); if (!validarFormularioModalEdicion(form, tipoRegistro)) return;
                                const btnGuardarModal = form.closest('.modal-content').querySelector('#saveRegistroEnfermedadChangesButton');
                                if(btnGuardarModal) {btnGuardarModal.disabled = true; btnGuardarModal.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';}
                                const formData = new FormData(form);
                                try {
                                    const submitResponse = await fetch('procesar_editar_registro_enfermedad.php', { method: 'POST', body: formData });
                                    const result = await submitResponse.json();
                                    if (result.success) {
                                        mostrarMensajeGlobalModal(form, result.message, 'success');
                                        setTimeout(() => { if(currentModalInstance) currentModalInstance.hide(); window.location.reload(); }, 1500);
                                    } else { mostrarMensajeGlobalModal(form, result.message || 'Error.', 'danger'); }
                                } catch (err) { mostrarMensajeGlobalModal(form, 'Error de conexión.', 'danger');} 
                                finally { if(btnGuardarModal){ btnGuardarModal.innerHTML = 'Guardar Cambios'; validarFormularioModalEdicion(form, tipoRegistro);} }
                            });
                        }
                        modalElement.addEventListener('hidden.bs.modal', () => { if (!modalElement.querySelector('.alert-success')) modalEditarContainer.innerHTML = ''; }, { once: true });
                    } else { modalEditarContainer.innerHTML = '<div class="alert alert-danger">Error al init modal.</div>';}
                } catch (error) { modalEditarContainer.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;}
            }
        });
    });

    document.querySelectorAll('.btn-eliminar-registro-enf').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            const nombre = this.dataset.nombre;
            const tipo = this.dataset.tipo; // 'tipo_enfermedad' o 'enfermedad'
            const csrfTokenInputGlobal = document.getElementById('csrf_token_global_enf_crud'); 
            const csrfToken = csrfTokenInputGlobal ? csrfTokenInputGlobal.value : (document.getElementById('csrf_token_global_tipo_enf')?.value || ''); // Fallback si no encuentra el primero


            if (!csrfToken) { alert('Error de seguridad: No se pudo obtener el token CSRF.'); return;}
            if(mensajeConfirmacionEliminar) mensajeConfirmacionEliminar.textContent = `¿Eliminar ${tipo.replace('_',' ')} '${nombre}' (ID: ${id})?`;
            if (modalConfirmacionEliminar) modalConfirmacionEliminar.style.display = 'flex';
            if (formEliminar && formEliminar.parentNode) formEliminar.parentNode.removeChild(formEliminar);
            
            formEliminar = document.createElement('form');
            formEliminar.method = 'POST'; formEliminar.action = 'eliminar_registro_enfermedad.php';
            formEliminar.innerHTML = `<input type="hidden" name="id_registro" value="${id}">
                                      <input type="hidden" name="tipo_registro_eliminar" value="${tipo}">
                                      <input type="hidden" name="csrf_token" value="${csrfToken}">`;
            document.body.appendChild(formEliminar);
        });
    });
    if (btnConfirmarEliminar) { btnConfirmarEliminar.addEventListener('click', () => { if (formEliminar) formEliminar.submit(); });}
    if (btnCancelarEliminar) { btnCancelarEliminar.addEventListener('click', () => { 
        if (modalConfirmacionEliminar) modalConfirmacionEliminar.style.display = 'none'; 
        if (formEliminar && formEliminar.parentNode) formEliminar.parentNode.removeChild(formEliminar); formEliminar = null;
    });}
    if (modalConfirmacionEliminar){
        window.addEventListener('click', (event) => { if (event.target === modalConfirmacionEliminar && btnCancelarEliminar) btnCancelarEliminar.click();});
    }

    const pageContainer = document.getElementById('page-number-container');
    const pageDisplaySpan = pageContainer?.querySelector('.page-number-display');
    const pageInputField = pageContainer?.querySelector('.page-number-input-field');

    if (pageDisplaySpan && pageInputField) {
        pageDisplaySpan.addEventListener('click', () => {
            pageDisplaySpan.style.display = 'none';
            pageInputField.style.display = 'inline-block';
            pageInputField.focus();
            pageInputField.select();
        });

        const goToPage = () => {
            const totalPages = parseInt(pageInputField.dataset.total, 10) || 1;
            let targetPage = parseInt(pageInputField.value, 10);
            if (isNaN(targetPage) || targetPage < 1) targetPage = 1;
            else if (targetPage > totalPages) targetPage = totalPages;
            
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('pagina', targetPage);
            window.location.href = currentUrl.toString();
        };

        const hidePageInput = () => {
            const totalPgs = parseInt(pageInputField.dataset.total, 10) || 1;
            const currentPageVal = parseInt(pageInputField.value, 10);
            const initialPageArr = pageDisplaySpan.textContent.split(' / ');
            const initialPage = initialPageArr.length > 0 ? initialPageArr[0].trim() : '1';

            if (isNaN(currentPageVal) || currentPageVal < 1) {
                pageDisplaySpan.textContent = initialPage + ' / ' + totalPgs;
                pageInputField.value = initialPage;
            } else if (currentPageVal > totalPgs) {
                pageDisplaySpan.textContent = totalPgs + ' / ' + totalPgs;
                pageInputField.value = totalPgs;
            } else {
                 pageDisplaySpan.textContent = currentPageVal + ' / ' + totalPgs;
            }
            pageDisplaySpan.style.display = 'inline-block';
            pageInputField.style.display = 'none';
        };

        pageInputField.addEventListener('blur', () => { setTimeout(hidePageInput, 150); });
        pageInputField.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); goToPage(); } 
            else if (e.key === 'Escape') { 
                const initialPageArr = pageDisplaySpan.textContent.split(' / ');
                const initialPage = initialPageArr.length > 0 ? initialPageArr[0].trim() : '1';
                pageInputField.value = initialPage; 
                hidePageInput(); 
            }
        });
    }
});