<?php
$currentYear = date("Y");
?>

<footer class="footer-collapsible navbar-custom-blue text-white mt-auto"> 
    <div class="footer-visible-bar" data-bs-toggle="collapse" data-bs-target="#footerCollapseContent" aria-expanded="false" aria-controls="footerCollapseContent">
        <span class="footer-copyright small"> 
            © <?php echo $currentYear; ?> Salud Connected. Todos los derechos reservados.
        </span>
        <button class="footer-toggler" type="button" aria-label="Mostrar/ocultar detalles del pie de página">
            <i class="bi bi-chevron-down"></i> 
        </button>
    </div>
    <div class="collapse" id="footerCollapseContent">
        <div class="container-fluid px-lg-5">
             <hr>

            <div class="row gy-3 align-items-center">
                <!-- Columna 1: Contáctenos -->
                <div class="col-lg-7 col-md-12">
                    <h5 class="text-uppercase fw-bold mb-2">Contáctenos</h5>
                    <p class="small mb-1">Línea nacional de información general 018000919100 (tel. fijo)</p>
                    <p class="small mb-1">Línea nacional de citas 018000940304 (tel. fijo)</p>
                    <p class="small mb-2">Marcación desde celular #936 (Tigo, Claro, Movistar)</p>
                    <div class="mt-2">
                        <a href="https://www.facebook.com/" target="_blank" class="text-white me-3 fs-5 text-decoration-none" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="https://www.instagram.com/" target="_blank" class="text-white me-3 fs-5 text-decoration-none" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="https://www.youtube.com/" target="_blank" class="text-white fs-5 text-decoration-none" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>

                <!-- Columna 2: Logos -->
                <div class="col-lg-5 col-md-12 d-flex align-items-center justify-content-center justify-content-lg-end mt-3 mt-lg-0">
                    <img src="../img/Minsalud.svg" alt="Logo Minsalud" style="height: 50px; width: auto;" class="me-4">
                    <img src="../img/sena.png" alt="Logo SENA" style="height: 40px; width: auto;">
                </div>
            </div>
        </div>
    </div>

</footer>