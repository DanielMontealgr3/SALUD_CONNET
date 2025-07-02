<?php
require_once('include/conexion.php');
$conex = new database;
$con = $conex->conectar();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$mensaje_error_servidor = '';
$mensaje_alerta_sesion = ''; 
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'inactive') {
        $mensaje_alerta_sesion = "Su sesión caducó por inactividad. Vuelva a ingresar credenciales.";
    } elseif ($_GET['error'] === 'nosession') {
        $mensaje_alerta_sesion = "Debe ingresar sus credenciales para acceder.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])){

    if (!empty($_POST['id_tipo_doc']) && !empty($_POST['doc_usu']) && !empty($_POST['pass'])) {

        $id_tipo_doc = $_POST['id_tipo_doc'];
        $doc_usu     = trim($_POST['doc_usu']);
        $contrasena_ingresada = $_POST['pass'];

        try {
            $sql_login = $con->prepare("SELECT doc_usu, id_tipo_doc, pass, id_rol, nom_usu, id_est FROM usuarios WHERE id_tipo_doc = :tipo_doc AND doc_usu = :doc");

            $sql_login->bindParam(':tipo_doc', $id_tipo_doc, PDO::PARAM_INT);
            $sql_login->bindParam(':doc', $doc_usu, PDO::PARAM_STR);

            $sql_login->execute();

            $usuario = $sql_login->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                if ($usuario['id_est'] == 1) { 
                    if (password_verify($contrasena_ingresada, $usuario['pass'])) {

                        $_SESSION['doc_usu'] = $usuario['doc_usu'];
                        $_SESSION['id_rol'] = $usuario['id_rol'];
                        $_SESSION['nombre_usuario'] = $usuario['nom_usu'];
                        $_SESSION['loggedin'] = true;
                        $_SESSION['time'] = time(); 

                        $rol_usuario = $usuario['id_rol'];

                        if ($rol_usuario == 1) {
                            header('Location: admi/inicio.php');
                            exit;
                        } elseif ($rol_usuario == 2) {
                            header('Location: paci/inicio.php');
                            exit;
                        } elseif ($rol_usuario == 3) {
                            header('Location: farma/inicio.php');
                            exit;
                        } elseif ($rol_usuario == 4) {
                            header('Location: medi/citas.php');
                            exit;
                        } else {
                            $mensaje_error_servidor = "Acceso no configurado para este rol.";
                            session_unset();
                            session_destroy();
                        }

                    } else {
                        $mensaje_error_servidor = "La contraseña ingresada es incorrecta.";
                    }
                } else {
                    $mensaje_error_servidor = "Usted se encuentra inhabilitado. Comuníquese con el administrador.";
                    session_unset();
                    session_destroy();
                }
            } else {
                $mensaje_error_servidor = "Tipo/número de documento no registrado.";
            }

        } catch (PDOException $e) {
            $mensaje_error_servidor = "Error interno del servidor al procesar la solicitud.";
            error_log("Error BD login: " . $e->getMessage()); 
        }

    } else { 
        $mensaje_error_servidor = "Complete todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesión - Salud Connected</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/estilos_inicio.css">
</head>
<body class="index">
    <?php include 'menu_inicio.php'; ?>
    <div class="overlay"></div>
    <main>
        <div class="inicio_sesion">
            <div class="form-inicio">
               <form action="inicio_sesion.php" method="post" id="formulario-login" novalidate>
                   <ul>
                      <h1>Inicio de sesión</h1>
                      <?php
                          if (!empty($mensaje_alerta_sesion)) {
                              echo '<li class="mensaje-login-alerta">' . htmlspecialchars($mensaje_alerta_sesion) . '</li>';
                          }
                          if (!empty($mensaje_error_servidor)) {
                              echo '<li class="mensaje-login-servidor">' . htmlspecialchars($mensaje_error_servidor) . '</li>';
                          }
                      ?>
                      <li class="campo-contenedor">
                         <label for="id_tipo_doc">Tipo de documento</label>
                         <select name="id_tipo_doc" id="id_tipo_doc" tabindex="1">
                             <option value="">Seleccione...</option>
                              <?php
                              try {
                                 $sql_tipos = $con->prepare("SELECT id_tipo_doc, nom_doc FROM tipo_identificacion ORDER BY nom_doc ASC");
                                 $sql_tipos->execute();
                                 while ($fila = $sql_tipos->fetch(PDO::FETCH_ASSOC)) {
                                     $selected = (isset($_POST['id_tipo_doc']) && $_POST['id_tipo_doc'] == $fila['id_tipo_doc']) ? 'selected' : '';
                                     echo "<option value=\"" . htmlspecialchars($fila['id_tipo_doc']) . "\" $selected>" . htmlspecialchars($fila['nom_doc']) . "</option>";
                                 }
                              } catch (PDOException $e) {
                                  echo "<option value='' disabled>Error al cargar tipos</option>";
                                  error_log("Error cargando tipos doc: " . $e->getMessage());
                              }
                              ?>
                         </select>
                         <span class="mensaje-error-cliente" id="error-tipo-doc"></span>
                     </li>
                     <li class="campo-contenedor">
                         <label for="doc_usu">Documento</label>
                         <input type="text" name="doc_usu" id="doc_usu" placeholder="Ingrese su documento" inputmode="numeric" pattern="\d*" tabindex="2" value="<?php echo isset($_POST['doc_usu']) ? htmlspecialchars($_POST['doc_usu']) : ''; ?>">
                         <span class="mensaje-error-cliente" id="error-doc-usu"></span>
                      </li>
                      <li class="campo-contenedor">
                         <label for="pass">Contraseña</label>
                         <input type="password" name="pass" id="pass" value="" maxlength="20" minlength="8" placeholder="Ingrese su contraseña" tabindex="3">
                         <span class="mensaje-error-cliente" id="error-pass"></span>
                      </li>
                      <li class="campo-terminos">
                          <input type="checkbox" id="acepto_terminos" name="acepto_terminos" tabindex="4">
                          <label for="acepto_terminos">Acepto los <a href="#" id="enlace_terminos">Términos y Condiciones</a></label>
                      </li>
                      <div class="links">
                          <a href="include/olvide_contra.php">Olvidé mi contraseña</a>
                      </div>
                      <li>
                          <input type="submit" name="enviar" id="boton-enviar" value="Ingresar" tabindex="5" disabled>
                      </li>
                   </ul>
               </form>
            </div>
        </div>
    </main>

    <?php include 'include/modal_terminos.php'; ?>

    <?php include 'footer_inicio.php'; ?>
    <script src="js/inicio_sesion.js"></script>
    <script src="js/menu_responsivo.js"></script>
</body>
</html>