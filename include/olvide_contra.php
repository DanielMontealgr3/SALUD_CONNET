<?php
// INCLUYE EL ARCHIVO DE CONFIGURACIÓN GLOBAL. ES LO PRIMERO PARA TENER ACCESO A RUTAS Y SESIONES.
require_once __DIR__ . '/config.php';

// INICIALIZACIÓN DE VARIABLES PARA LA PÁGINA.
$pageTitle = 'Recuperar Contraseña';
$alert_message = '';
$alert_type = '';

// GESTIÓN DE MENSAJES FLASH (MENSAJES TEMPORALES QUE SE MUESTRAN UNA SOLA VEZ).
$flash_message = '';
$flash_type = '';
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    $flash_type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'info';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

// PROCESAMIENTO DEL FORMULARIO CUANDO SE ENVÍA (MÉTODO POST).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $email = trim($_POST['correo_usu']);

    // VALIDACIÓN DE LOS DATOS DE ENTRADA.
    if (empty($email)) {
        $alert_message = 'El correo electrónico no puede estar vacío.';
        $alert_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         $alert_message = 'El formato del correo electrónico no es válido.';
         $alert_type = 'error';
    } else {
        try {
            // CONSULTA A LA BASE DE DATOS PARA VERIFICAR SI EL CORREO EXISTE.
            $sql = $con->prepare("SELECT correo_usu FROM usuarios WHERE correo_usu = :email");
            $sql->bindParam(':email', $email, PDO::PARAM_STR);
            $sql->execute();
            $fila = $sql->fetch(PDO::FETCH_ASSOC);

            // SI EL CORREO EXISTE, REDIRIGE A UN SCRIPT PARA ENVIAR EL EMAIL DE RECUPERACIÓN.
            if ($fila) {
                echo '<form id="sendForm" action="' . BASE_URL . '/include/enviar_recuperacion.php" method="POST" style="display:none;">
                          <input type="hidden" name="correo_usu" value="' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '">
                      </form>
                      <script>
                          document.addEventListener("DOMContentLoaded", function() {
                              document.getElementById("sendForm").submit();
                          });
                      </script>';
                exit;
            } else {
                // SI EL CORREO NO EXISTE, MUESTRA UN MENSAJE DE ERROR.
                $alert_message = 'El correo electrónico no se encuentra registrado.';
                $alert_type = 'error';
            }
        } catch (PDOException $e) {
            // MANEJO DE ERRORES DE LA BASE DE DATOS.
            error_log("Database error in include/olvide_contra.php: " . $e->getMessage());
            $alert_message = 'Ocurrió un error al verificar el correo. Por favor, inténtalo de nuevo más tarde.';
            $alert_type = 'error';
        }
    }
}

// INCLUYE EL MENÚ/ENCABEZADO DE LA PÁGINA.
require ROOT_PATH . '/menu_inicio.php';
?>

<!-- CONTENIDO PRINCIPAL DEL FORMULARIO DE RECUPERACIÓN. -->
<div class="overlay"></div>
<main>
    <div class="contraseña">
        <div class="form-inicio">
            <form action="<?php echo BASE_URL; ?>/include/olvide_contra.php" method="POST" autocomplete="off" id="formulario-recuperacion" novalidate>
                <ul>
                    <h1>¿Olvidaste tu contraseña?</h1>
                    <li class="texto-explicativo">
                       No te preocupes, ingresa tu correo electrónico registrado y te enviaremos instrucciones para restablecerla.
                    </li>

                    <?php
                        // BLOQUE PARA MOSTRAR MENSAJES DE ALERTA AL USUARIO (ERRORES, ÉXITO, ETC.).
                        if (!empty($flash_message)) {
                            $message_class = 'mensaje-login-alerta';
                            if ($flash_type === 'error') {
                                $message_class = 'mensaje-login-servidor';
                            } elseif ($flash_type === 'success') {
                                $message_class = 'mensaje-login-exito';
                            }
                            echo '<li class="' . $message_class . '">' . htmlspecialchars($flash_message) . '</li>';
                        }
                         if (!empty($alert_message) && $flash_type !== 'success') {
                            $message_class = ($alert_type === 'error') ? 'mensaje-login-servidor' : 'mensaje-login-alerta';
                            echo '<li class="' . $message_class . '">' . htmlspecialchars($alert_message) . '</li>';
                         }
                         if ($flash_type === 'success') {
                            echo '<li style="text-align: center; margin-top: 5px; margin-bottom: 15px;">';
                            echo '<a href="https://mail.google.com/" target="_blank" rel="noopener noreferrer" class="boton_reg gmail-btn">';
                            echo 'Ir a Gmail</a>';
                            echo '</li>';
                         }
                    ?>

                    <!-- CAMPO DE ENTRADA PARA EL CORREO ELECTRÓNICO. -->
                    <li class="campo-contenedor">
                        <label for="correo_usu">Correo electrónico</label>
                        <input type="email" id="correo_usu" name="correo_usu" placeholder="Ingresa tu correo electrónico" required value="<?php echo isset($_POST['correo_usu']) && $flash_type !== 'success' ? htmlspecialchars($_POST['correo_usu']) : ''; ?>" tabindex="1">
                        <span class="mensaje-error-cliente" id="error-correo-usu"></span>
                    </li>

                    <!-- BOTONES DE ACCIÓN DEL FORMULARIO. -->
                    <li class="botones-form">
                        <a href="<?php echo BASE_URL; ?>/inicio_sesion.php" class="boton_reg secondary-btn" tabindex="3">Regresar</a>
                        <input type="submit" name="submit" value="Enviar" class="boton_reg" tabindex="2">
                    </li>
                </ul>
            </form>
        </div>
    </div>
</main>

<?php 
// INCLUYE EL PIE DE PÁGINA.
require ROOT_PATH . '/footer_inicio.php'; 
?>
<!-- ENLACE AL ARCHIVO JAVASCRIPT ESPECÍFICO PARA LAS VALIDACIONES DE ESTE FORMULARIO. -->
<script src="<?php echo BASE_URL; ?>/js/olvide_contra.js"></script>