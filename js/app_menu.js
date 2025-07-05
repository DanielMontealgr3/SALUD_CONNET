// FUNCIÓN AUXILIAR PARA CARGAR UN SCRIPT DE FORMA DINÁMICA.
function loadScript(url, callback) {
    // Previene la carga duplicada del mismo script.
    const existingScript = document.querySelector(`script[src^="${url.split('?')[0]}"]`);
    if (existingScript) {
        // Si el script ya existe, simplemente ejecuta el callback.
        if (callback) callback();
        return;
    }

    const script = document.createElement('script');
    script.src = url; // La URL ya incluye el timestamp para evitar caché.
    script.onload = callback; // Se ejecuta cuando el script se carga y ejecuta.
    script.onerror = () => console.error(`Error al cargar el script: ${url}`);
    document.body.appendChild(script); // Añadir al body es una práctica común.
}

// BLOQUE PRINCIPAL QUE SE EJECUTA CUANDO EL DOCUMENTO HTML ESTÁ LISTO.
document.addEventListener('DOMContentLoaded', function() {
    const perfilLink = document.getElementById('abrirPerfilModalLink');
    const modalGlobalContainer = document.getElementById('perfilModalPlaceholderContainerGlobal');
    
    if (perfilLink && modalGlobalContainer) {
        perfilLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Muestra un indicador de carga centralizado.
            modalGlobalContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index: 2000;"><div class="spinner-border text-light" role="status"></div></div>';
            
            // Carga el contenido del modal.
            fetch(`${AppConfig.INCLUDE_PATH}modal_perfil.php?v=${new Date().getTime()}`)
                .then(response => {
                    if (!response.ok) throw new Error('Error de red al cargar el modal del perfil.');
                    return response.text();
                })
                .then(modalHtml => {
                    modalGlobalContainer.innerHTML = modalHtml;
                    const perfilModalElement = document.getElementById('userProfileModal');
                    if (perfilModalElement) {
                        const perfilModal = new bootstrap.Modal(perfilModalElement);
                        perfilModal.show();
                        
                        // Carga dinámicamente el script de validación y ejecuta su función principal.
                        const scriptUrl = `${AppConfig.BASE_URL}/js/editar_perfil.js?v=${new Date().getTime()}`;
                        loadScript(scriptUrl, () => {
                            if (typeof inicializarValidacionesPerfil === "function") {
                                inicializarValidacionesPerfil();
                            }
                        });

                        // Limpia el contenido del modal cuando se cierra para liberar memoria.
                        perfilModalElement.addEventListener('hidden.bs.modal', () => {
                            modalGlobalContainer.innerHTML = '';
                        });
                    }
                })
                .catch(error => {
                    console.error(error);
                    modalGlobalContainer.innerHTML = ''; // Limpia el spinner
                    // Muestra una alerta amigable usando SweetAlert2
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'No se pudo cargar tu perfil. Por favor, intenta de nuevo más tarde.',
                        confirmButtonColor: '#005A9C'
                    });
                });
        });
    }

    // Lógica para los submenús desplegables.
    document.querySelectorAll('.dropdown-menu .dropdown-toggle').forEach(function(element){
        element.addEventListener('click', function (e) {
            e.stopPropagation(); // Evita que el menú principal se cierre
            const nextEl = this.nextElementSibling;
            if (nextEl && nextEl.classList.contains('dropdown-menu')) {
                // Cierra otros submenús abiertos en el mismo nivel
                this.closest('.dropdown-menu').querySelectorAll('.show').forEach(function(el){
                    if (el !== nextEl) el.classList.remove('show');
                });
                nextEl.classList.toggle('show');
            }
        });
    });
});