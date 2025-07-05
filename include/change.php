<?php
// INCLUYE EL ARCHIVO DE CONFIGURACIÓN GLOBAL. ES LO PRIMERO PARA TENER ACCESO A RUTAS, SESIONES Y LA BASE DE DATOS.
require_once __DIR__ . '/config.php';

// INICIALIZACIÓN DE VARIABLES PARA LA PÁGINA.
$pageTitle = 'Restablecer Contraseña';
$token = $_GET['token'] ?? null;
$error_message = '';
$flash_message = '';
$flash_type = '';

// SI NO SE PROPORCIONA UN TOKEN EN LA URL, REDIRIGE AL USUARIO A LA PÁGINA DE LOGIN.
if (!$token) {
    header('Location: ' . BASE_URL . '/inicio_sesion.php');
    exit;
}

// BLOQUE PARA VALIDAR EL TOKEN CONTRA LA BASE DE DATOS.
try {
    // CONSULTA PARA VERIFICAR SI EL TOKEN ES VÁLIDO Y NO HA EXPIRADO.
    $query = $con->prepare("SELECT u.doc_usu, u.correo_usu
        FROM usuarios u
        JOIN recu_contra r ON u.doc_usu = r.id_usuario
        WHERE r.token = ? AND r.expiracion_t > NOW()");
    $query->execute([$token]);
    $user = $query->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // MANEJO DE ERRORES DE BASE DE DATOS DURANTE LA VALIDACIÓN DEL TOKEN.
    error_log("Error BD validando token: " . $e->getMessage());
    $_SESSION['flash_message'] = 'Error al validar el token. Inténtelo más tarde.';
    $_SESSION['flash_type'] = 'error';
    header('Location: ' . BASE_URL . '/inicio_sesion.php');
    exit;
}

// SI NO SE ENCUENTRA UN USUARIO CON ESE TOKEN, ES INVÁLIDO O EXPIRÓ.
if (!$user) {
    $_SESSION['flash_message'] = 'El enlace de recuperación es inválido o ha expirado.';
    $_SESSION['flash_type'] = 'error';
    header('Location: ' . BASE_URL . '/include/olvide_contra.php');
    exit;
}

// GUARDA EL ID DEL USUARIO PARA USARLO MÁS ADELANTE.
$id_usuario = $user['doc_usu'];

// PROCESAMIENTO DEL FORMULARIO CUANDO SE ENVÍA (MÉTODO POST).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $contra = $_POST['pass'];
    $contra2 = $_POST['pass2'];

    // VALIDACIÓN DE LA NUEVA CONTRASEÑA SEGÚN LOS REQUISITOS DE SEGURIDAD.
    $esValida = true;
    if (strlen($contra) < 8) {
        $error_message = "La contraseña debe tener al menos 8 caracteres.";
        $esValida = false;
    } elseif (!preg_match('/[a-z]/', $contra)) {
        $error_message = "La contraseña debe contener al menos una minúscula.";
        $esValida = false;
    } elseif (!preg_match('/[A-Z]/', $contra)) {
        $error_message = "La contraseña debe contener al menos una mayúscula.";
        $esValida = false;
    } elseif (!preg_match('/[0-9]/', $contra)) {
        $error_message = "La contraseña debe contener al menos un número.";
        $esValida = false;
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>_+\-=\[\]\\\'\;\/]/', $contra)) {
        $error_message = "La contraseña debe contener al menos un caracter especial.";
        $esValida = false;
    } elseif ($contra !== $contra2) {
        $error_message = "Las contraseñas no coinciden.";
        $esValida = false;
    }

    // SI LA CONTRASEÑA ES VÁLIDA, PROCEDE A ACTUALIZARLA EN LA BASE DE DATOS.
    if ($esValida) {
        try {
            // "HASHEA" LA CONTRASEÑA PARA ALMACENARLA DE FORMA SEGURA.
            $hashedPassword = password_hash($contra, PASSWORD_DEFAULT);

            // ACTUALIZA LA CONTRASEÑA DEL USUARIO EN LA TABLA 'USUARIOS'.
            $update = $con->prepare("UPDATE usuarios SET pass = ? WHERE doc_usu = ?");
            $update->execute([$hashedPassword, $id_usuario]);

            // SI LA ACTUALIZACIÓN FUE EXITOSA, ELIMINA EL TOKEN DE RECUPERACIÓN.
            if ($update->rowCount() > 0) {
                $deleteToken = $con->prepare("DELETE FROM recu_contra WHERE id_usuario = ?");
                $deleteToken->execute([$id_usuario]);

                // MUESTRA UN MENSAJE DE ÉXITO Y REDIRIGE AL LOGIN.
                echo '<script>
                        alert("✅ ¡Contraseña actualizada exitosamente!\n\nSerás redirigido para iniciar sesión.");
                        window.location = "' . BASE_URL . '/inicio_sesion.php";
                      </script>';
                exit;
            } else {
                $error_message = "Error al actualizar la contraseña en la base de datos.";
            }
        } catch (PDOException $e) {
             // MANEJO DE ERRORES DE BASE DE DATOS DURANTE LA ACTUALIZACIÓN.
             error_log("Error BD actualizando contraseña: " . $e->getMessage());
             $error_message = "Ocurrió un error interno al actualizar. Inténtelo más tarde.";
        }
    }
}

// INCLUYE EL MENÚ/ENCABEZADO DE LA PÁGINA.
require ROOT_PATH . '/menu_inicio.php';
?>

<!-- CONTENIDO HTML DEL FORMULARIO PARA CAMBIAR LA CONTRASEÑA. -->
<div class="overlay"></div>

<main>
    <div class="contraseña">
        <div class="form-inicio">
            <form method="POST" action="<?php echo BASE_URL; ?>/include/change.php?token=<?php echo htmlspecialchars($token); ?>" autocomplete="off" id="formulario-change" novalidate>
                <ul>
                    <h1>Restablecer Contraseña</h1>
                    <li class="texto-explicativo">
                       Ingresa tu nueva contraseña segura. Asegúrate de que cumpla con todos los requisitos.
                    </li>

                    <?php
                        // BLOQUE PARA MOSTRAR MENSAJES DE ERROR AL USUARIO.
                        if (!empty($error_message)) {
                            echo '<li class="mensaje-login-servidor">' . htmlspecialchars($error_message) . '</li>';
                        }
                        if (!empty($flash_message)) {
                             $message_class = ($flash_type === 'error') ? 'mensaje-login-servidor' : 'mensaje-login-alerta';
                             echo '<li class="' . $message_class . '">' . htmlspecialchars($flash_message) . '</li>';
                        }
                    ?>

                    <!-- CAMPO PARA LA NUEVA CONTRASEÑA, CON LISTA DE REQUISITOS. -->
                    <li class="campo-contenedor">
                        <label for="pass">Nueva Contraseña</label>
                        <input type="password" id="pass" name="pass" placeholder="Ingresa la nueva contraseña" required>
                         <span class="mensaje-error-cliente" id="error-pass"></span>
                         <ul id="requisitos-lista" class="requisitos-password">
                             <li id="req-length">Mínimo 8 caracteres</li>
                             <li id="req-lower">Una letra minúscula</li>
                             <li id="req-upper">Una letra mayúscula</li>
                             <li id="req-number">Un número</li>
                             <li id="req-special">Un caracter especial (!@#...)</li>
                         </ul>
                    </li>

                    <!-- CAMPO PARA CONFIRMAR LA NUEVA CONTRASEÑA. -->
                    <li class="campo-contenedor">
                        <label for="pass2">Confirmar Contraseña</label>
                        <input type="password" id="pass2" name="pass2" placeholder="Confirma la nueva contraseña" required>
                        <span class="mensaje-error-cliente" id="error-pass2"></span>
                    </li>

                    <!-- BOTÓN DE ENVÍO DEL FORMULARIO. -->
                    <li class="botones-form">
                        <input name="submit" id="submit-btn" type="submit" class="boton_reg" value="Confirmar Contraseña" disabled>
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

<!-- ENLACE AL ARCHIVO JAVASCRIPT PARA LAS VALIDACIONES DE ESTE FORMULARIO. -->
<script src="<?php echo BASE_URL; ?>/js/change_validation.js"></script>