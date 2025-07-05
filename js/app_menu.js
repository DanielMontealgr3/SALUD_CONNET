// BLOQUE DE MANEJO DEL MODAL DE PERFIL DE USUARIO
// ESTE CÓDIGO SE ENCARGA DE CARGAR, MOSTRAR Y GESTIONAR EL MODAL PARA EDITAR EL PERFIL.
document.addEventListener('DOMContentLoaded', function() {
    const perfilLink = document.getElementById('abrirPerfilModalLink');
    const modalGlobalContainer = document.getElementById('perfilModalPlaceholderContainerGlobal');
    let finalFormValidator = null;

    if (perfilLink && modalGlobalContainer) {
        perfilLink.addEventListener('click', function(e) {
            e.preventDefault();
            modalGlobalContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index: 2000;"><div class="spinner-border text-light" role="status"></div></div>';
            
            // USA LA VARIABLE GLOBAL 'AppConfig' PARA CONSTRUIR LA RUTA CORRECTA AL ARCHIVO PHP.
            fetch(AppConfig.INCLUDE_PATH + 'modal_perfil.php')
                .then(response => {
                    if (!response.ok) throw new Error('Error al cargar el modal del perfil.');
                    return response.text();
                })
                .then(modalHtml => {
                    modalGlobalContainer.innerHTML = modalHtml;
                    const perfilModalElement = document.getElementById('userProfileModal');
                    if (perfilModalElement) {
                        const perfilModal = new bootstrap.Modal(perfilModalElement);
                        perfilModal.show();
                        if (typeof inicializarValidacionesPerfil === "function") {
                            finalFormValidator = inicializarValidacionesPerfil();
                        }
                        attachProfileFormSubmitHandler();
                        attachImagePreviewListener();
                        perfilModalElement.addEventListener('hidden.bs.modal', () => modalGlobalContainer.innerHTML = '');
                    }
                })
                .catch(error => {
                    modalGlobalContainer.innerHTML = `<div class="alert alert-danger fixed-top">${error.message}</div>`;
                });
        });
    }

    function attachProfileFormSubmitHandler() {
        const profileForm = document.getElementById('profileFormModalActual');
        if (profileForm) {
            profileForm.addEventListener('submit', function(event) {
                event.preventDefault();
                handleProfileUpdateSubmitActual(profileForm);
            });
        }
    }

    function attachImagePreviewListener(){
        const fotoInput = document.getElementById('foto_usu_modal');
        if(fotoInput) { fotoInput.addEventListener('change', previewImageModalInForm); }
    }

    function previewImageModalInForm(event) {
        const reader = new FileReader();
        const imagePreview = document.getElementById('imagePreviewModal');
        if (event.target.files[0] && imagePreview) {
            reader.onload = () => { imagePreview.src = reader.result; };
            reader.readAsDataURL(event.target.files[0]);
        }
    }

    function handleProfileUpdateSubmitActual(formElement) {
        const formData = new FormData(formElement);
        const messageDiv = document.getElementById('modalUpdateMessage');
        const saveButton = document.getElementById('saveProfileChangesButton');
        if(saveButton) saveButton.disabled = true;
        if(messageDiv) messageDiv.innerHTML = '<div class="d-flex align-items-center"><div class="spinner-border spinner-border-sm me-2"></div><span>Actualizando...</span></div>';
        
        // USA LA VARIABLE GLOBAL 'AppConfig' PARA LA RUTA DEL FETCH.
        fetch(AppConfig.INCLUDE_PATH + 'mi_perfil.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (messageDiv) { messageDiv.className = `mt-3 alert ${data.success ? 'alert-success' : 'alert-danger'}`; messageDiv.textContent = data.message; }
            if (data.success) {
                if (data.new_nom_usu) document.getElementById('menuUserNameDisplay').textContent = data.new_nom_usu;
                if (data.new_foto_usu_path_for_modal) document.getElementById('imagePreviewModal').src = data.new_foto_usu_path_for_modal + '?' + new Date().getTime();
                setTimeout(() => {
                    const modalEl = document.getElementById('userProfileModal');
                    if(modalEl) bootstrap.Modal.getInstance(modalEl)?.hide();
                }, 2500);
            } else {
                if(saveButton) saveButton.disabled = false;
            }
        })
        .catch(error => {
            if(saveButton) saveButton.disabled = false;
            if (messageDiv) { messageDiv.className = 'mt-3 alert alert-danger'; messageDiv.textContent = 'Error de comunicación.'; }
        });
    }

    // BLOQUE PARA MANEJAR LOS SUBMENÚS DESPLEGABLES DE BOOTSTRAP.
    document.querySelectorAll('.dropdown-menu .dropdown-toggle').forEach(function(element){
        element.addEventListener('click', function (e) {
            if (!this.nextElementSibling) return;
            if (this.nextElementSibling.classList.contains('show')) return;

            this.closest('.dropdown-menu').querySelectorAll('.show').forEach(function(el){
                el.classList.remove('show');
            });
            
            this.nextElementSibling.classList.add('show');
            e.stopPropagation();
        });
    });
});