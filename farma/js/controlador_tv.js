document.addEventListener('DOMContentLoaded', function() {
    const relojContainer = document.getElementById('reloj');
    const listaLlamando = document.getElementById('lista-llamando');
    const listaAtencion = document.getElementById('lista-atencion');
    
    const modalOverlay = document.getElementById('modal-notificacion');
    const modalTurno = document.getElementById('modal-notificacion-turno');
    const modalPaciente = document.getElementById('modal-notificacion-paciente');
    const modalDestino = document.getElementById('modal-notificacion-destino');

    const inicioOverlay = document.getElementById('inicio-overlay');
    const btnIniciar = document.getElementById('btn-iniciar-pantalla');

    const synth = window.speechSynthesis;
    let isAudioEnabled = false;
    let ultimoTurnoAnunciadoId = null;

    function hablar(texto, callback) {
        if (!isAudioEnabled || !synth) {
            if (callback) callback();
            return;
        }
        
        synth.cancel();

        const utterance = new SpeechSynthesisUtterance(texto);
        utterance.lang = 'es-ES';
        utterance.rate = 0.9;
        utterance.pitch = 1;
        
        utterance.onend = function() {
            if (callback) {
                callback();
            }
        };

        synth.speak(utterance);
    }
    
    function mostrarNotificacion(turno, paciente, destino) {
        if (modalTurno) modalTurno.textContent = `Turno ${turno}`;
        if (modalPaciente) modalPaciente.textContent = paciente;
        let textoDestino = `Pase a ${destino || "Módulo de Entrega"}`;
        if (modalDestino) modalDestino.textContent = textoDestino;
        if (modalOverlay) modalOverlay.classList.add('visible');
    }

    function ocultarNotificacion() {
        if (modalOverlay) modalOverlay.classList.remove('visible');
    }
    
    function actualizarReloj() {
        const ahora = new Date();
        const opcionesFecha = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
        const fecha = ahora.toLocaleDateString('es-ES', opcionesFecha);
        const hora = ahora.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
        if(relojContainer) {
            relojContainer.innerHTML = `<strong>${hora}</strong><span>${fecha.charAt(0).toUpperCase() + fecha.slice(1)}</span>`;
        }
    }

    function renderizarLista(elementoLista, turnos) {
        if (!elementoLista) return;
        elementoLista.innerHTML = '';
        if (!turnos || turnos.length === 0) {
            const emptyMessage = document.createElement('div');
            emptyMessage.className = 'turno-item-vacio';
            emptyMessage.textContent = 'No hay turnos en esta categoría.';
            elementoLista.appendChild(emptyMessage);
        } else {
            turnos.forEach(turno => {
                const item = document.createElement('div');
                item.className = 'turno-item';
                item.innerHTML = `
                    <div class="turno-item-turno">${turno.id_turno}</div>
                    <div class="info-paciente">
                        <div class="turno-item-paciente">${turno.nombre_paciente}</div>
                        <div class="turno-item-farmaceuta">Atiende: ${turno.nombre_farmaceuta}</div>
                    </div>
                `;
                elementoLista.appendChild(item);
            });
        }
    }

    async function actualizarTurnos() {
        try {
            const respuesta = await fetch(`api_turnos_tv.php?cache_bust=${new Date().getTime()}`);
            if (!respuesta.ok) return;
            const data = await respuesta.json();

            if (data.error) return;

            renderizarLista(listaLlamando, data.llamando);
            renderizarLista(listaAtencion, data.en_atencion);

            if (data.notificacion && data.notificacion.length > 0) {
                const turnoANotificar = data.notificacion[0];
                if (turnoANotificar.id_turno !== ultimoTurnoAnunciadoId) {
                    ultimoTurnoAnunciadoId = turnoANotificar.id_turno;

                    let textoAnuncio = `Turno ${turnoANotificar.id_turno}, ${turnoANotificar.nombre_paciente}. Por favor, pase a ${turnoANotificar.modulo_atencion}.`;
                    
                    mostrarNotificacion(turnoANotificar.id_turno, turnoANotificar.nombre_paciente, turnoANotificar.modulo_atencion);
                    hablar(textoAnuncio, ocultarNotificacion);
                }
            } else {
                ultimoTurnoAnunciadoId = null;
            }

        } catch (error) { console.error('No se pudieron obtener los turnos:', error); }
    }

    btnIniciar.addEventListener('click', function() {
        isAudioEnabled = true;
        
        if (synth) {
            const utterance = new SpeechSynthesisUtterance('Sistema de turnos activado.');
            utterance.volume = 0;
            synth.speak(utterance);
        }
        
        inicioOverlay.style.opacity = '0';
        setTimeout(() => {
            inicioOverlay.style.display = 'none';
        }, 500);
        
        setInterval(actualizarReloj, 1000);
        setInterval(actualizarTurnos, 1500);
        
        actualizarReloj();
        actualizarTurnos();
    });
});