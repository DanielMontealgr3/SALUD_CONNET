<?php
// BLOQUE DE INICIALIZACIÓN DEL FOOTER
// OBTIENE EL AÑO ACTUAL PARA MOSTRARLO EN EL COPYRIGHT.
$currentYear = date("Y");
?>

<!--
ESTILOS CSS ESPECÍFICOS PARA EL FOOTER.
ESTÁN DIRECTAMENTE AQUÍ PARA NO CREAR UN ARCHIVO CSS EXTRA SOLO PARA ESTO,
PERO PODRÍAN MOVERSE A UNA HOJA DE ESTILOS GENERAL SI SE DESEA.
-->
<style>
    .footer-collapsible {
        padding-top: 0.8rem;
        padding-bottom: 0.8rem;
        position: relative;
        flex-shrink: 0;
        background-color: rgb(0, 117, 201);
        color: #ffffff;
        margin-top: auto;
    }
    .footer-visible-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    }
    .footer-toggler {
        color: #ffffff;
        background: none;
        border: none;
        font-size: 1.2rem;
        padding: 0.25rem 0.5rem;
        transition: transform 0.3s ease-in-out;
    }
    .footer-visible-bar[aria-expanded="true"] .footer-toggler {
        transform: rotate(180deg);
    }
    #footerCollapseContent {
        padding-top: 1rem;
        padding-bottom: 1rem;
    }
    #footerCollapseContent hr {
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        margin-top: 0;
    }
    #footerCollapseContent h5 {
        color: #ffffff;
    }
    #footerCollapseContent p.small {
        color: rgba(255, 255, 255, 0.85);
        line-height: 1.6;
    }
    #footerCollapseContent a {
        transition: opacity 0.2s ease-in-out;
    }
    #footerCollapseContent a:hover {
        opacity: 0.8;
    }
</style>

<!-- ESTRUCTURA HTML DEL FOOTER COLAPSABLE -->
<footer class="footer-collapsible text-white mt-auto"> 
    <!-- BARRA VISIBLE QUE CONTIENE EL COPYRIGHT Y EL BOTÓN PARA DESPLEGAR -->
    <div class="footer-visible-bar" data-bs-toggle="collapse" data-bs-target="#footerCollapseContent" aria-expanded="false" aria-controls="footerCollapseContent">
        <span class="footer-copyright small"> 
            © <?php echo $currentYear; ?> Salud Connected. Todos los derechos reservados.
        </span>
        <button class="footer-toggler" type="button" aria-label="Mostrar/ocultar detalles del pie de página">
            <i class="bi bi-chevron-down"></i> 
        </button>
    </div>

    <!-- CONTENIDO COLAPSABLE DEL FOOTER CON DETALLES DE CONTACTO Y LOGOS -->
    <div class="collapse" id="footerCollapseContent">
        <div class="container-fluid px-lg-5">
             <hr>
            <div class="row gy-3 align-items-center">
                <!-- SECCIÓN DE INFORMACIÓN DE CONTACTO -->
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

                <!-- SECCIÓN DE LOGOTIPOS DE ALIADOS -->
                <!-- SE UTILIZA LA CONSTANTE 'BASE_URL' PARA ASEGURAR QUE LAS RUTAS A LAS IMÁGENES SEAN CORRECTAS EN AMBOS ENTORNOS. -->
                <div class="col-lg-5 col-md-12 d-flex align-items-center justify-content-center justify-content-lg-end mt-3 mt-lg-0">
                    <img src="<?php echo BASE_URL; ?>/img/Minsalud.svg" alt="Logo Minsalud" style="height: 50px; width: auto;" class="me-4">
                    <img src="<?php echo BASE_URL; ?>/img/sena.png" alt="Logo SENA" style="height: 40px; width: auto;">
                </div>
            </div>
        </div>
    </div>
</footer>