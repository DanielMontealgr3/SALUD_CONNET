
document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.querySelector('.menu-toggle');
    const mainMenu = document.querySelector('#main-menu');

    if (menuToggle && mainMenu) {
        menuToggle.addEventListener('click', () => {
            mainMenu.classList.toggle('menu-open');

            menuToggle.classList.toggle('active');
            const isExpanded = mainMenu.classList.contains('menu-open');
            menuToggle.setAttribute('aria-expanded', isExpanded);
        });

         document.addEventListener('click', (event) => {
            if (mainMenu.classList.contains('menu-open') &&
                !mainMenu.contains(event.target) &&
                !menuToggle.contains(event.target))
            {
                mainMenu.classList.remove('menu-open');
                menuToggle.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });

        const preguntasContainer = document.querySelector('.preguntas-f');
        if (preguntasContainer) {
            const itemsPreguntas = preguntasContainer.querySelectorAll('.pre-f');
            itemsPreguntas.forEach(item => {
                const preguntaDiv = item.querySelector('.pregunta-f');
                const respuestaDiv = item.querySelector('.respuesta-f');
                if (preguntaDiv && respuestaDiv) {
                    preguntaDiv.addEventListener('click', () => {
                        const currentlyOpen = item.classList.contains('open');
                        if (currentlyOpen) {
                            item.classList.remove('open');
                            respuestaDiv.style.maxHeight = null;
                        } else {
                            item.classList.add('open');
                            respuestaDiv.style.maxHeight = respuestaDiv.scrollHeight + "px";
                        }
                    });
                }
            });
        }
    }
});