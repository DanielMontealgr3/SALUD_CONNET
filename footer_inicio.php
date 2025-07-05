<?php
// OBTIENE EL AÑO ACTUAL PARA MOSTRARLO EN EL COPYRIGHT.
$currentYear = date("Y");
?>
<footer>
    <!-- ESTRUCTURA PRINCIPAL DEL PIE DE PÁGINA -->
    <div class="footer-details">
        <div class="banner-wrapper">

            <!-- SECCIÓN DE INFORMACIÓN DE CONTACTO Y REDES SOCIALES. -->
            <div class="info-text">
                <h2>Contáctenos</h2>
                <h3>Línea nacional de información general 018000919100 (tel. fijo)</h3>
                <h3>Línea nacional de citas 018000940304 (tel. fijo)</h3>
                <h3>Marcación desde celular #936 (Tigo, Claro, Movistar)</h3>
                <ul class="social">
                   <li><a href="https://www.facebook.com/" target="_blank" aria-label="Facebook"><i class="bi bi-facebook"></i></a></li>
                   <li><a href="https://www.instagram.com/" target="_blank" aria-label="Instagram"><i class="bi bi-instagram"></i></a></li>
                   <li><a href="https://www.youtube.com/" target="_blank" aria-label="YouTube"><i class="bi bi-youtube"></i></a></li>
                </ul>
            </div>

            <!-- SECCIÓN PARA MOSTRAR LOGOTIPOS DE ALIADOS. USA 'BASE_URL' PARA LAS RUTAS DE LAS IMÁGENES. -->
            <div class="logos-footer-container">
                 <div class="logos-inline">
                     <img src="<?php echo BASE_URL; ?>/img/Minsalud.svg" alt="Logo Minsalud" class="logo-minsalud">
                     <img src="<?php echo BASE_URL; ?>/img/sena.png" alt="Logo SENA" class="logo-sena">
                 </div>
            </div>

        </div>
    </div>

    <!-- SECCIÓN FINAL DEL COPYRIGHT CON EL AÑO ACTUALIZADO DINÁMICAMENTE. -->
    <div class="footer-copyright-bottom">
        <p class="copyright">© <?php echo $currentYear; ?> Salud Connected. Todos los derechos reservados.</p>
    </div>
</footer>

</html>