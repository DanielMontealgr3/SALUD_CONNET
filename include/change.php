<?php
session_start();
require_once('../include/conexion.php');

$conex = new database();
$con = $conex->conectar();

$basePath = '../';
$pageTitle = 'Restablecer Contraseña';

$token = $_GET['token'] ?? null;
$error_message = '';
$flash_message = '';
$flash_type = '';

if (!$token) {
    header('Location: ' . $basePath . 'inicio_sesion.php');
    exit;
}

try {
    $query = $con->prepare("SELECT u.doc_usu, u.correo_usu
        FROM usuarios u
        JOIN recu_contra r ON u.doc_usu = r.id_usuario
        WHERE r.token = ? AND r.expiracion_t > NOW()");
    $query->execute([$token]);
    $user = $query->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error BD validando token: " . $e->getMessage());
    $_SESSION['flash_message'] = 'Error al validar el token. Inténtelo más tarde.';
    $_SESSION['flash_type'] = 'error';
    header('Location: ' . $basePath . 'inicio_sesion.php');
    exit;
}


if (!$user) {
    $_SESSION['flash_message'] = 'El enlace de recuperación es inválido o ha expirado.';
    $_SESSION['flash_type'] = 'error';
    header('Location: ' . $basePath . 'olvide_contra.php');
    exit;
}

$id_usuario = $user['doc_usu'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $contra = $_POST['pass'];
    $contra2 = $_POST['pass2'];

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

    if ($esValida) {
        try {
            $hashedPassword = password_hash($contra, PASSWORD_DEFAULT);

            $update = $con->prepare("UPDATE usuarios SET pass = ? WHERE doc_usu = ?");
            $update->execute([$hashedPassword, $id_usuario]);

            if ($update->rowCount() > 0) {
                $deleteToken = $con->prepare("DELETE FROM recu_contra WHERE id_usuario = ?");
                $deleteToken->execute([$id_usuario]);

                echo '<script>
                        alert("✅ ¡Contraseña actualizada exitosamente!\n\nSerás redirigido para iniciar sesión.");
                        window.location = "' . $basePath . 'inicio_sesion.php";
                      </script>';
                exit;
            } else {
                $error_message = "Error al actualizar la contraseña en la base de datos.";
            }
        } catch (PDOException $e) {
             error_log("Error BD actualizando contraseña: " . $e->getMessage());
             $error_message = "Ocurrió un error interno al actualizar. Inténtelo más tarde.";
        }
    }
}

include $basePath . 'menu_inicio.php';
?>

<div class="overlay"></div>

<main>
    <div class="contraseña">
        <div class="form-inicio">
            <form method="POST" action="change.php?token=<?php echo htmlspecialchars($token); ?>" autocomplete="off" id="formulario-change" novalidate>
                <ul>
                    <h1>Restablecer Contraseña</h1>
                    <li class="texto-explicativo">
                       Ingresa tu nueva contraseña segura. Asegúrate de que cumpla con todos los requisitos.
                    </li>

                    <?php
                        if (!empty($error_message)) {
                            echo '<li class="mensaje-login-servidor">' . htmlspecialchars($error_message) . '</li>';
                        }
                        if (!empty($flash_message)) {
                             $message_class = ($flash_type === 'error') ? 'mensaje-login-servidor' : 'mensaje-login-alerta';
                             echo '<li class="' . $message_class . '">' . htmlspecialchars($flash_message) . '</li>';
                        }
                    ?>

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

                    <li class="campo-contenedor">
                        <label for="pass2">Confirmar Contraseña</label>
                        <input type="password" id="pass2" name="pass2" placeholder="Confirma la nueva contraseña" required>
                        <span class="mensaje-error-cliente" id="error-pass2"></span>
                    </li>

                    <li class="botones-form">
                        <input name="submit" id="submit-btn" type="submit" class="boton_reg" value="Confirmar Contraseña" disabled>
                    </li>
                </ul>
            </form>
        </div>
    </div>
</main>

<?php
include $basePath . 'footer_inicio.php';
?>

<script src="<?php echo $basePath; ?>js/change_validation.js"></script>