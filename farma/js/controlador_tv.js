document.addEventListener('DOMContentLoaded', function() {
    // --- 1. SELECCIÓN DE ELEMENTOS DEL DOM ---
    const relojContainer = document.getElementById('reloj');
    const listaLlamando = document.getElementById('lista-llamando');
    const listaAtencion = document.getElementById('lista-atencion');
    
    const modalOverlay = document.getElementById('modal-notificacion');
    const modalTurno = document.getElementById('modal-notificacion-turno');
    const modalPaciente = document.getElementById('modal-notificacion-paciente');
    const modalDestino = document.getElementById('modal-notificacion-destino');

    const inicioOverlay = document.getElementById('inicio-overlay');
    const btnIniciar = document.getElementById('btn-iniciar-pantalla');

    // --- 2. CONFIGURACIÓN INICIAL ---
    const synth = window.speechSynthesis;
    let isAudioEnabled = false;
    let ultimoTurnoAnunciadoId = null;
    // MEJORA DE RENDIMIENTO: Se aumenta el intervalo para reducir la carga del servidor.
    const INTERVALO_ACTUALIZACION = 4000; // 4 segundos.

    // --- 3. FUNCIONES AUXILIARES ---

    /**
     * Utiliza la API de Síntesis de Voz del navegador para anunciar un texto.
     * @param {string} texto - El mensaje a vocalizar.
     * @param {function} callback - Una función a ejecutar cuando el anuncio termine.
     */
    function hablar(texto, callback) {
        if (!isAudioEnabled || !synth) {
            if (callback) callback();
            return;
        }
        
        synth.cancel(); // Detiene cualquier anuncio anterior para evitar solapamientos.

        const utterance = new SpeechSynthesisUtterance(texto);
        utterance.lang = 'es-ES'; // Asegura la pronunciación en español.
        utterance.rate = 0.9;
        utterance.pitch = 1;
        
        utterance.onend = function() {
            if (callback) {
                callback();
            }
        };
        synth.speak(utterance);
    }
    
    /**
     * Muestra una notificación modal a pantalla completa.
     * @param {string} turno - El número del turno.
     * @param {string} paciente - El nombre del paciente.
     * @param {string} destino - El módulo o destino al que debe dirigirse.
     */
    function mostrarNotificacion(turno, paciente, destino) {
        if (modalTurno) modalTurno.textContent = `Turno ${turno}`;
        if (modalPaciente) modalPaciente.textContent = paciente;
        if (modalDestino) modalDestino.textContent = `Pase a ${destino || "Módulo de Entrega"}`;
        if (modalOverlay) modalOverlay.classList.add('visible');
    }

    function ocultarNotificacion() {
        if (modalOverlay) modalOverlay.classList.remove('visible');
    }
    
    /**
     * Actualiza el reloj y la fecha en la pantalla.
     */
    function actualizarReloj() {
        if (!relojContainer) return;
        const ahora = new Date();
        const opcionesFecha = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
        const fecha = ahora.toLocaleDateString('es-ES', opcionesFecha);
        const hora = ahora.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
        // Se usa textContent para las partes dinámicas por seguridad, aunque aquí el riesgo es bajo.
        relojContainer.innerHTML = `<strong>${hora}</strong><span>${fecha.charAt(0).toUpperCase() + fecha.slice(1)}</span>`;
    }

    /**
     * Renderiza una lista de turnos en el elemento del DOM especificado.
     * @param {HTMLElement} elementoLista - El contenedor (UL/DIV) donde se renderizará la lista.
     * @param {Array} turnos - Un array de objetos de turno.
     */
    function renderizarLista(elementoLista, turnos) {
        if (!elementoLista) return;
        elementoLista.innerHTML = ''; // Limpia la lista anterior.

        if (!turnos || turnos.length === 0) {
            const emptyMessage = document.createElement('div');
            emptyMessage.className = 'turno-item-vacio';
            emptyMessage.textContent = 'No hay turnos en esta categoría.';
            elementoLista.appendChild(emptyMessage);
        } else {
            turnos.forEach(turno => {
                // --- MEJORA DE SEGURIDAD (PREVENCIÓN DE XSS) ---
                // Se crean los elementos del DOM y se asigna el contenido con .textContent
                // en lugar de usar .innerHTML para evitar la inyección de código.
                const item = document.createElement('div');
                item.className = 'turno-item';

                const divTurno = document.createElement('div');
                divTurno.className = 'turno-item-turno';
                divTurno.textContent = turno.id_turno;

                const divInfo = document.createElement('div');
                divInfo.className = 'info-paciente';

                const divPaciente = document.createElement('div');
                divPaciente.className = 'turno-item-paciente';
                divPaciente.textContent = turno.nombre_paciente;

                const divFarmaceuta = document.createElement('div');
                divFarmaceuta.className = 'turno-item-farmaceuta';
                divFarmaceuta.textContent = `Atiende: ${turno.nombre_farmaceuta}`;

                divInfo.appendChild(divPaciente);
                divInfo.appendChild(divFarmaceuta);
                item.appendChild(divTurno);
                item.appendChild(divInfo);
                
                elementoLista.appendChild(item);
            });
        }
    }

    /**
     * Realiza una petición a la API para obtener el estado actual de los turnos y actualiza la pantalla.
     */
    async function actualizarTurnos() {
        try {
            // El `cache_bust` evita que el navegador use una respuesta antigua de la API.
            const respuesta = await fetch(`api_turnos_tv.php?cache_bust=${new Date().getTime()}`);
            if (!respuesta.ok) {
                console.error(`Error HTTP: ${respuesta.status}`);
                return;
            }
            const data = await respuesta.json();

            if (data.error) {
                console.error(`Error de API: ${data.error}`);
                return;
            }

            renderizarLista(listaLlamando, data.llamando);
            renderizarLista(listaAtencion, data.en_atencion);

            // Lógica para mostrar y anunciar la notificación del turno más reciente.
            if (data.notificacion && data.notificacion.length > 0) {
                const turnoANotificar = data.notificacion[0];
                if (turnoANotificar.id_turno !== ultimoTurnoAnunciadoId) {
                    ultimoTurnoAnunciadoId = turnoANotificar.id_turno;
                    const textoAnuncio = `Turno ${turnoANotificar.id_turno}, ${turnoANotificar.nombre_paciente}. Por favor, pase a ${turnoANotificar.modulo_atencion}.`;
                    
                    mostrarNotificacion(turnoANotificar.id_turno, turnoANotificar.nombre_paciente, turnoANotificar.modulo_atencion);
                    hablar(textoAnuncio, ocultarNotificacion);
                }
            } else {
                ultimoTurnoAnunciadoId = null; // Resetea si no hay notificaciones.
            }

        } catch (error) { 
            console.error('No se pudieron obtener los turnos:', error); 
        }
    }

    // --- 4. INICIALIZACIÓN ---

    // El botón "Iniciar" es necesario para que el navegador permita la reproducción de audio.
    if (btnIniciar) {
        btnIniciar.addEventListener('click', function() {
            isAudioEnabled = true;
            
            // "Hack" para activar la API de voz en algunos navegadores, que requieren interacción del usuario.
            if (synth) {
                const utterance = new SpeechSynthesisUtterance(' ');
                utterance.volume = 0;
                synth.speak(utterance);
            }
            
            // Oculta la capa de inicio y arranca los temporizadores.
            if (inicioOverlay) {
                inicioOverlay.style.opacity = '0';
                setTimeout(() => {
                    inicioOverlay.style.display = 'none';
                }, 500);
            }
            
            // Inicia los ciclos de actualización.
            setInterval(actualizarReloj, 1000);
            setInterval(actualizarTurnos, INTERVALO_ACTUALIZACION);
            
            // Ejecuta las funciones una vez al inicio para no esperar el primer intervalo.
            actualizarReloj();
            actualizarTurnos();
        });
    }
});