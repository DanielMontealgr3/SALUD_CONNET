document.addEventListener('DOMContentLoaded', function () {
    const modalEditarContainer = document.getElementById('modalEditarGeograficaContainer');
    const modalConfirmacionEliminar = document.getElementById('modalConfirmacionEliminarGeografica');
    const mensajeConfirmacionEliminar = document.getElementById('mensajeConfirmacionGeografica');
    const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminacionGeografica');
    const btnCancelarEliminar = document.getElementById('btnCancelarEliminacionGeografica');
    let formEliminar = null;
    let currentModalInstance = null; 

    const URL_GET_MUNICIPIOS = '../../ajax/get_municipios.php'; 

    function limpiarFeedback(form) {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.is-valid').forEach(el => el.classList.remove('is-valid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        const globalMsg = form.querySelector('#modalEditGeograficaUpdateMessage');
        if (globalMsg) globalMsg.innerHTML = '';
    }

    function mostrarErrorCampo(inputEl, mensaje) {
        inputEl.classList.add('is-invalid');
        inputEl.classList.remove('is-valid');
        const feedbackEl = inputEl.parentElement.querySelector('.invalid-feedback');
        if (feedbackEl) feedbackEl.textContent = mensaje;
    }

    function mostrarExitoCampo(inputEl) {
        inputEl.classList.add('is-valid');
        inputEl.classList.remove('is-invalid');
    }

    function mostrarMensajeGlobalModal(form, mensaje, tipo = 'danger') {
        const globalMsgDiv = form.querySelector('#modalEditGeograficaUpdateMessage');
        if (globalMsgDiv) {
            globalMsgDiv.innerHTML = `<div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
        }
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

    const filtroDeptoBarrios = document.getElementById('filtro_id_dep_barrio'); 
    const filtroMunBarrios = document.getElementById('filtro_id_mun_barrio');   

    async function cargarMunicipiosFiltro(idDep, selectMunElement, valorSeleccionado = '') {
        selectMunElement.innerHTML = '<option value="">Cargando...</option>';
        selectMunElement.disabled = true;
        if (!idDep) {
            selectMunElement.innerHTML = '<option value="">-- Todos --</option>'; // Si no hay depto, mostrar "Todos" o similar
            if (selectMunElement.id === 'filtro_id_mun_barrio') {
                 selectMunElement.innerHTML = '<option value="">-- Seleccione Depto. --</option>';
            }
            return;
        }
        try {
            const response = await fetch(`${URL_GET_MUNICIPIOS}?id_dep=${idDep}`);
            if (!response.ok) throw new Error('Error en la respuesta de la red');
            const municipios = await response.json();
            
            selectMunElement.innerHTML = '<option value="">-- Todos --</option>'; 
            if(municipios.length > 0){
                municipios.forEach(mun => {
                    const option = document.createElement('option');
                    option.value = mun.id;
                    option.textContent = mun.nombre;
                    if (mun.id === valorSeleccionado) option.selected = true;
                    selectMunElement.appendChild(option);
                });
                 selectMunElement.disabled = false;
            } else {
                selectMunElement.innerHTML = '<option value="">No hay municipios</option>';
            }
        } catch (error) {
            console.error('Error cargando municipios para filtro:', error);
            selectMunElement.innerHTML = '<option value="">Error al cargar</option>';
        }
    }

    if (filtroDeptoBarrios && filtroMunBarrios) {
        if (filtroDeptoBarrios.value) {
            const municipioPreseleccionado = filtroMunBarrios.dataset.valorActual || ''; 
            cargarMunicipiosFiltro(filtroDeptoBarrios.value, filtroMunBarrios, municipioPreseleccionado);
        } else {
            filtroMunBarrios.innerHTML = '<option value="">-- Seleccione Depto. --</option>';
        }

        filtroDeptoBarrios.addEventListener('change', function() {
            filtroMunBarrios.dataset.valorActual = ''; 
            cargarMunicipiosFiltro(this.value, filtroMunBarrios);
        });
    }

    document.querySelectorAll('.btn-eliminar-geografica').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            const nombre = this.dataset.nombre;
            const tipo = this.dataset.tipo;
            
            const csrfTokenInputGlobal = document.getElementById('csrf_token_global');
            const csrfToken = csrfTokenInputGlobal ? csrfTokenInputGlobal.value : '';

            if (!csrfToken) {
                alert('Error de seguridad: No se pudo obtener el token CSRF. Por favor, recargue la página.');
                return;
            }

            mensajeConfirmacionEliminar.textContent = `¿Está seguro de que desea eliminar el ${tipo} '${nombre}' (ID: ${id})? Esta acción podría ser irreversible y afectar registros relacionados.`;
            
            if (modalConfirmacionEliminar) modalConfirmacionEliminar.style.display = 'flex';

            if (formEliminar && formEliminar.parentNode) {
                formEliminar.parentNode.removeChild(formEliminar);
            }
            formEliminar = document.createElement('form');
            formEliminar.method = 'POST';
            formEliminar.action = 'eliminar_geografica.php'; 
            
            const inputId = document.createElement('input');
            inputId.type = 'hidden'; inputId.name = 'id_registro'; inputId.value = id;
            formEliminar.appendChild(inputId);

            const inputTipo = document.createElement('input');
            inputTipo.type = 'hidden'; inputTipo.name = 'tipo_registro'; inputTipo.value = tipo;
            formEliminar.appendChild(inputTipo);
            
            const inputCsrf = document.createElement('input');
            inputCsrf.type = 'hidden'; inputCsrf.name = 'csrf_token'; inputCsrf.value = csrfToken;
            formEliminar.appendChild(inputCsrf);

            document.body.appendChild(formEliminar);
        });
    });

    if (btnConfirmarEliminar) {
        btnConfirmarEliminar.addEventListener('click', () => {
            if (formEliminar) formEliminar.submit();
        });
    }
    if (btnCancelarEliminar) {
        btnCancelarEliminar.addEventListener('click', () => {
            if (modalConfirmacionEliminar) modalConfirmacionEliminar.style.display = 'none';
            if (formEliminar && formEliminar.parentNode) formEliminar.parentNode.removeChild(formEliminar);
            formEliminar = null;
        });
    }
    window.addEventListener('click', (event) => {
        if (event.target == modalConfirmacionEliminar && btnCancelarEliminar) {
            btnCancelarEliminar.click();
        }
    });

    async function cargarMunicipiosModal(idDep, selectMunElement, valorSeleccionado = '') {
        selectMunElement.innerHTML = '<option value="">Cargando municipios...</option>';
        selectMunElement.disabled = true;
        if (!idDep) {
            selectMunElement.innerHTML = '<option value="">Seleccione Departamento</option>';
            return;
        }
        try {
            const response = await fetch(`${URL_GET_MUNICIPIOS}?id_dep=${idDep}`);
            if (!response.ok) throw new Error('Error en la respuesta de la red');
            const municipios = await response.json();
            
            selectMunElement.innerHTML = '<option value="">Seleccione un municipio...</option>';
             if(municipios.length > 0){
                municipios.forEach(mun => {
                    const option = document.createElement('option');
                    option.value = mun.id;
                    option.textContent = mun.nombre;
                    if (mun.id === valorSeleccionado) option.selected = true;
                    selectMunElement.appendChild(option);
                });
                selectMunElement.disabled = false;
            } else {
                selectMunElement.innerHTML = '<option value="">No hay municipios para este departamento</option>';
            }
        } catch (error) {
            console.error('Error cargando municipios para modal:', error);
            selectMunElement.innerHTML = '<option value="">Error al cargar municipios</option>';
        }
    }
    
    function setupModalEventListeners(modalForm) {
        const btnGuardar = modalForm.closest('.modal-content').querySelector('#saveGeograficaChangesButton');
        if(btnGuardar) btnGuardar.disabled = false; 

        const tipoRegistro = modalForm.querySelector('input[name="tipo_registro"]').value;

        if (tipoRegistro === 'barrio') {
            const deptoSelect = modalForm.querySelector('#id_dep_edit_barrio');
            const munSelect = modalForm.querySelector('#id_mun_edit_barrio');
            if (deptoSelect && munSelect) {
                deptoSelect.addEventListener('change', function() {
                    munSelect.value = ''; // Limpiar selección previa de municipio
                    cargarMunicipiosModal(this.value, munSelect);
                    validarFormularioModal(modalForm); // Revalidar al cambiar depto
                });
            }
        }
        
        modalForm.querySelectorAll('input[required], select[required]').forEach(input => {
            const eventType = input.tagName === 'SELECT' ? 'change' : 'input';
            input.addEventListener(eventType, () => {
                validarFormularioModal(modalForm);
            });
             input.addEventListener('blur', () => { // Validar también en blur
                validarFormularioModal(modalForm);
            });
        });
        validarFormularioModal(modalForm); 
    }
    
    function validarFormularioModal(form) {
        let esValido = true;
        limpiarFeedback(form);
        const btnGuardar = form.closest('.modal-content').querySelector('#saveGeograficaChangesButton');

        const tipoRegistro = form.querySelector('input[name="tipo_registro"]').value;

        if (tipoRegistro === 'departamento') {
            const nomDep = form.querySelector('#nom_dep_edit');
            if (!nomDep.value.trim()) { mostrarErrorCampo(nomDep, 'Nombre es requerido.'); esValido = false; }
            else if (nomDep.value.trim().length < 3 || nomDep.value.trim().length > 100) { mostrarErrorCampo(nomDep, 'Nombre entre 3 y 100 caracteres.'); esValido = false; }
            else if (!/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/u.test(nomDep.value.trim())) { mostrarErrorCampo(nomDep, 'Nombre solo letras y espacios.'); esValido = false; }
            else { mostrarExitoCampo(nomDep); }
        } else if (tipoRegistro === 'municipio') {
            const nomMun = form.querySelector('#nom_mun_edit');
            const idDepMun = form.querySelector('#id_dep_edit_mun');
            if (!nomMun.value.trim()) { mostrarErrorCampo(nomMun, 'Nombre es requerido.'); esValido = false; }
            else if (nomMun.value.trim().length < 3 || nomMun.value.trim().length > 100) { mostrarErrorCampo(nomMun, 'Nombre entre 3 y 100 caracteres.'); esValido = false; }
            else if (!/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/u.test(nomMun.value.trim())) { mostrarErrorCampo(nomMun, 'Nombre solo letras y espacios.'); esValido = false; }
            else { mostrarExitoCampo(nomMun); }

            if (!idDepMun.value) { mostrarErrorCampo(idDepMun, 'Departamento es requerido.'); esValido = false; }
            else { mostrarExitoCampo(idDepMun); }
        } else if (tipoRegistro === 'barrio') {
            const nomBarrio = form.querySelector('#nom_barrio_edit');
            const idDepBarrio = form.querySelector('#id_dep_edit_barrio');
            const idMunBarrio = form.querySelector('#id_mun_edit_barrio');

            if (!nomBarrio.value.trim()) { mostrarErrorCampo(nomBarrio, 'Nombre es requerido.'); esValido = false; }
            else if (nomBarrio.value.trim().length < 3 || nomBarrio.value.trim().length > 150) { mostrarErrorCampo(nomBarrio, 'Nombre entre 3 y 150 caracteres.'); esValido = false; }
            else if (!/^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\s.,#-]+$/u.test(nomBarrio.value.trim())) { mostrarErrorCampo(nomBarrio, 'Nombre contiene caracteres inválidos.'); esValido = false; }
            else { mostrarExitoCampo(nomBarrio); }

            if (!idDepBarrio.value) { mostrarErrorCampo(idDepBarrio, 'Departamento es requerido.'); esValido = false; }
            else { mostrarExitoCampo(idDepBarrio); }

            if (!idMunBarrio.value && !idMunBarrio.disabled) { mostrarErrorCampo(idMunBarrio, 'Municipio es requerido.'); esValido = false; }
            else if (idMunBarrio.value && !idMunBarrio.disabled) { mostrarExitoCampo(idMunBarrio); }
        }
        
        if(btnGuardar) btnGuardar.disabled = !esValido;
        return esValido;
    }

    document.querySelectorAll('.btn-editar-geografica').forEach(button => {
        button.addEventListener('click', async function () {
            const idRegistro = this.dataset.id;
            const tipoRegistro = this.dataset.tipo;

            if (modalEditarContainer) {
                modalEditarContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div> <p class="ms-2 mb-0">Cargando datos...</p></div>';
                
                if (currentModalInstance) {
                    currentModalInstance.hide(); 
                    const modalElementOld = document.getElementById('editGeograficaModal');
                    if(modalElementOld) modalElementOld.removeEventListener('hidden.bs.modal', null);
                }

                try {
                    const response = await fetch(`modal_editar_geografica.php?id_registro=${idRegistro}&tipo_registro=${tipoRegistro}`);
                    if (!response.ok) throw new Error('Error al cargar el contenido del modal.');
                    
                    modalEditarContainer.innerHTML = await response.text();
                    const modalElement = document.getElementById('editGeograficaModal');
                    
                    if (modalElement) {
                        currentModalInstance = new bootstrap.Modal(modalElement);
                        currentModalInstance.show();
                        
                        const form = modalElement.querySelector('#editGeograficaForm');
                        if (form) {
                            setupModalEventListeners(form);
                            form.addEventListener('submit', async function(e) {
                                e.preventDefault();
                                if (!validarFormularioModal(form)) return;

                                const btnGuardar = form.closest('.modal-content').querySelector('#saveGeograficaChangesButton');
                                if(btnGuardar) btnGuardar.disabled = true;
                                const spinner = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';
                                const originalText = btnGuardar ? btnGuardar.innerHTML : 'Guardar Cambios';
                                if(btnGuardar) btnGuardar.innerHTML = spinner;

                                const formData = new FormData(form);
                                try {
                                    const submitResponse = await fetch('procesar_editar_geografica.php', {
                                        method: 'POST',
                                        body: formData
                                    });
                                    const result = await submitResponse.json();
                                    if (result.success) {
                                        mostrarMensajeGlobalModal(form, result.message, 'success');
                                        setTimeout(() => {
                                            if(currentModalInstance) currentModalInstance.hide();
                                            // Eliminar el modal del DOM después de ocultarlo para evitar problemas con múltiples aperturas
                                            modalElement.addEventListener('hidden.bs.modal', () => {
                                                modalEditarContainer.innerHTML = ''; // Limpiar contenedor
                                                window.location.reload(); 
                                            }, { once: true });
                                        }, 1500);
                                    } else {
                                        mostrarMensajeGlobalModal(form, result.message || 'Error desconocido al guardar.', 'danger');
                                    }
                                } catch (submitError) {
                                    mostrarMensajeGlobalModal(form, 'Error de conexión al guardar.', 'danger');
                                    console.error('Error en submit de edición:', submitError);
                                } finally {
                                     if(btnGuardar) {
                                        btnGuardar.innerHTML = originalText;
                                        // La validación se encargará de habilitar/deshabilitar
                                        validarFormularioModal(form); 
                                     }
                                }
                            });
                        }
                         modalElement.addEventListener('hidden.bs.modal', () => { // Limpiar si se cierra sin guardar
                            const isSuccess = modalElement.querySelector('.alert-success');
                            if (!isSuccess) { // Solo limpiar si no fue un guardado exitoso
                                 modalEditarContainer.innerHTML = '';
                            }
                        }, { once: true });
                    } else {
                         modalEditarContainer.innerHTML = '<div class="alert alert-danger">Error: No se pudo inicializar el modal.</div>';
                    }
                } catch (error) {
                    console.error('Error al cargar modal:', error);
                    modalEditarContainer.innerHTML = `<div class="alert alert-danger">Error al cargar datos para editar: ${error.message}</div>`;
                }
            }
        });
    });
});