<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php'); 
include '../include/menu.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 || !isset($_SESSION['nombre_usuario'])) {
    header('Location: ../inicio_sesion.php'); 
    exit;
}

$nombre_usuario = $_SESSION['nombre_usuario'];
$pageTitle = "Inicio Administrador"; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="estilo.css">
</head>
<body class="d-flex flex-column min-vh-100"> 
    <main id="contenido-principal-inicio" class="flex-grow-1 d-flex align-items-center justify-content-center">
        <div class="container"> 
            <div class="contenedor-bienvenida text-center">
                
                <h1 class="mensaje-bienvenida-admin display-5 mb-3">
                    Bienvenido Administrador/a, <strong class="nombre-admin-bienvenida"><?php echo htmlspecialchars($nombre_usuario); ?></strong>
                </h1>
                <p class="submensaje-bienvenida-admin lead mb-4">
                    Desde aquí puedes gestionar todo el sistema Salud Connected.
                </p>
                <div id="ecg-animation-container" class="mb-4">
                    <svg id="ecg-svg" width="300" height="100" viewBox="0 0 300 100"></svg>
                </div>
            </div>
        </div>
    </main>
    <?php include '../include/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const svgNS = "http://www.w3.org/2000/svg";
            const svgContainer = document.getElementById('ecg-svg');
            if (!svgContainer) return;

            const width = svgContainer.getAttribute('width');
            const height = svgContainer.getAttribute('height');
            const midY = height / 2;
            const lineColor = "#e74c3c"; // Tu color rojo
            const lineWidth = 1.5; // Grosor de línea más delgado
            const animationDuration = 3; // segundos

            const path = document.createElementNS(svgNS, 'path');
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke', lineColor);
            path.setAttribute('stroke-width', lineWidth);
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('stroke-linejoin', 'round');
            svgContainer.appendChild(path);

            let x = 0;
            const segmentWidth = 100; // Ancho de un ciclo de ECG
            const numSegments = Math.ceil(width / segmentWidth) + 2; // +2 para cubrir el scroll y que no se vea el final

            function getEcgPathData(startX) {
                let d = `M ${startX} ${midY} `;
                for (let i = 0; i < numSegments; i++) {
                    const currentX = startX + i * segmentWidth;
                    // P wave
                    d += `L ${currentX + 10} ${midY} `;
                    d += `Q ${currentX + 15} ${midY - 10}, ${currentX + 20} ${midY} `;
                    // PQ segment
                    d += `L ${currentX + 25} ${midY} `;
                    // QRS complex
                    d += `L ${currentX + 28} ${midY + 5} `; // Q
                    d += `L ${currentX + 35} ${midY - 35} `; // R
                    d += `L ${currentX + 42} ${midY + 15} `; // S
                    d += `L ${currentX + 45} ${midY} `;
                    // ST segment
                    d += `L ${currentX + 60} ${midY} `;
                    // T wave
                    d += `Q ${currentX + 70} ${midY - 15}, ${currentX + 80} ${midY} `;
                    d += `L ${currentX + segmentWidth} ${midY} `;
                }
                return d;
            }
            
            let startTime = null;
            function animateECG(timestamp) {
                if (!startTime) startTime = timestamp;
                const progress = (timestamp - startTime) / (animationDuration * 1000); // Normalizado a 1
                
                // Mover la línea hacia la izquierda
                const scrollOffset = (progress * segmentWidth) % segmentWidth;
                path.setAttribute('d', getEcgPathData(-scrollOffset));

                // Resetea el tiempo para el loop, pero suaviza
                if (progress >= 1) {
                    startTime = timestamp - ((progress - 1) * animationDuration * 1000) ; 
                }
                
                requestAnimationFrame(animateECG);
            }

            path.setAttribute('d', getEcgPathData(0)); // Dibujo inicial
            requestAnimationFrame(animateECG);
        });
    </script>
</body>
</html>