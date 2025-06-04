<?php
session_start();
require_once('conexion.php');

$conex = new database();
$con = $conex->conectar();

$basePath = '../'; 
$pageTitle = 'Recuperar Contraseña';
$alert_message = '';
$alert_type = '';

$flash_message = '';
$flash_type = '';
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    $flash_type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'info';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $email = trim($_POST['correo_usu']);

    if (empty($email)) {
        $alert_message = 'El correo electrónico no puede estar vacío.';
        $alert_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         $alert_message = 'El formato del correo electrónico no es válido.';
         $alert_type = 'error';
    } else {
        try {
            $sql = $con->prepare("SELECT correo_usu FROM usuarios WHERE correo_usu = :email");
            $sql->bindParam(':email', $email, PDO::PARAM_STR);
            $sql->execute();
            $fila = $sql->fetch(PDO::FETCH_ASSOC);

            if ($fila) {
                echo '<form id="sendForm" action="enviar_recuperacion.php" method="POST" style="display:none;">
                          <input type="hidden" name="correo_usu" value="' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '">
                      </form>
                      <script>
                          document.addEventListener("DOMContentLoaded", function() {
                              document.getElementById("sendForm").submit();
                          });
                      </script>';
                exit;
            } else {
                $alert_message = 'El correo electrónico no se encuentra registrado.';
                $alert_type = 'error';
            }
        } catch (PDOException $e) {
            error_log("Database error in include/olvide_contra.php: " . $e->getMessage());
            $alert_message = 'Ocurrió un error al verificar el correo. Por favor, inténtalo de nuevo más tarde.';
            $alert_type = 'error';
        }
    }
}

include $basePath . 'menu_inicio.php';
?>

<div class="overlay"></div>

<main>
    <div class="contraseña">
        <div class="form-inicio">
            <form action="olvide_contra.php" method="POST" autocomplete="off" id="formulario-recuperacion" novalidate>
                <ul>
                    <h1>¿Olvidaste tu contraseña?</h1>

                    <li class="texto-explicativo">
                       No te preocupes, ingresa tu correo electrónico registrado y te enviaremos instrucciones para restablecerla.
                    </li>

                    <?php
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

                    <li class="campo-contenedor">
                        <label for="correo_usu">Correo electrónico</label>
                        <input type="email" id="correo_usu" name="correo_usu" placeholder="Ingresa tu correo electrónico" required value="<?php echo isset($_POST['correo_usu']) && $flash_type !== 'success' ? htmlspecialchars($_POST['correo_usu']) : ''; ?>" tabindex="1">
                        <span class="mensaje-error-cliente" id="error-correo-usu"></span>
                    </li>

                    <li class="botones-form">
                        <a href="<?php echo $basePath; ?>inicio_sesion.php" class="boton_reg secondary-btn" tabindex="3">Regresar</a>
                        <input type="submit" name="submit" value="Enviar" class="boton_reg" tabindex="2">
                    </li>
                </ul>
            </form>
        </div>
    </div>
</main>

<?php
include $basePath . 'footer_inicio.php';
?>
<script src="<?php echo $basePath; ?>js/olvide_contra.js"></script>

