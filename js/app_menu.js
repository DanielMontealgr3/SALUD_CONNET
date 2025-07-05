// FUNCIÓN AUXILIAR PARA CARGAR UN SCRIPT DE FORMA DINÁMICA.
// RECIBE LA URL DEL SCRIPT Y UNA FUNCIÓN 'CALLBACK' QUE SE EJECUTARÁ CUANDO EL SCRIPT HAYA CARGADO.
function loadScript(url, callback) {
    // VERIFICA SI EL SCRIPT YA HA SIDO CARGADO PARA NO DUPLICARLO.
    if (document.querySelector(`script[src^="${url}"]`)) {
        if (callback) callback();
        return;
    }
    const script = document.createElement('script');
    script.src = url;
    script.onload = callback;
    script.onerror = () => console.error(`Error al cargar el script: ${url}`);
    document.head.appendChild(script);
}

// BLOQUE PRINCIPAL QUE SE EJECUTA CUANDO EL DOCUMENTO HTML ESTÁ LISTO.
document.addEventListener('DOMContentLoaded', function() {
    // SELECCIÓN DE ELEMENTOS PRINCIPALES DEL MENÚ Y EL CONTENEDOR DEL MODAL.
    const perfilLink = document.getElementById('abrirPerfilModalLink');
    const modalGlobalContainer = document.getElementById('perfilModalPlaceholderContainerGlobal');
    
    // VERIFICA QUE EL ENLACE DE PERFIL Y EL CONTENEDOR DEL MODAL EXISTAN EN LA PÁGINA.
    if (perfilLink && modalGlobalContainer) {
        // AÑADE EL EVENTO DE CLIC AL ENLACE "MI PERFIL".
        perfilLink.addEventListener('click', function(e) {
            e.preventDefault();
            // MUESTRA UN SPINNER DE CARGA MIENTRAS SE OBTIENE EL CONTENIDO DEL MODAL.
            modalGlobalContainer.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index: 2000;"><div class="spinner-border text-light" role="status"></div></div>';
            
            // UTILIZA 'FETCH' Y LA VARIABLE GLOBAL 'AppConfig' PARA CARGAR EL HTML DEL MODAL DESDE EL SERVIDOR.
            fetch(AppConfig.INCLUDE_PATH + 'modal_perfil.php')
                .then(response => {
                    if (!response.ok) throw new Error('Error al cargar el modal del perfil.');
                    return response.text();
                })
                .then(modalHtml => {
                    // INSERTA EL HTML DEL MODAL EN EL CONTENEDOR.
                    modalGlobalContainer.innerHTML = modalHtml;
                    const perfilModalElement = document.getElementById('userProfileModal');
                    if (perfilModalElement) {
                        const perfilModal = new bootstrap.Modal(perfilModalElement);
                        perfilModal.show();
                        
                        // CARGA DINÁMICAMENTE EL SCRIPT DE VALIDACIÓN 'editar_perfil.js'.
                        // UNA VEZ CARGADO, EJECUTA LA FUNCIÓN 'inicializarValidacionesPerfil' QUE ESTÁ DENTRO DE ESE ARCHIVO.
                        loadScript(AppConfig.BASE_URL + '/js/editar_perfil.js?v=' + new Date().getTime(), () => {
                            if (typeof inicializarValidacionesPerfil === "function") {
                                inicializarValidacionesPerfil();
                            }
                        });

                        // SE LIMPIA EL CONTENIDO DEL MODAL CUANDO ESTE SE CIERRA PARA LIBERAR MEMORIA.
                        perfilModalElement.addEventListener('hidden.bs.modal', () => modalGlobalContainer.innerHTML = '');
                    }
                })
                .catch(error => {
                    modalGlobalContainer.innerHTML = `<div class="alert alert-danger fixed-top">${error.message}</div>`;
                });
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