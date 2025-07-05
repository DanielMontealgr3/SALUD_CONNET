<?php
// INCLUYE EL ARCHIVO DE CONFIGURACIÓN GLOBAL. ES LO PRIMERO PARA TENER ACCESO A RUTAS, SESIONES Y LA BASE DE DATOS.
require_once __DIR__ . '/include/config.php';

// INICIALIZACIÓN DE VARIABLES PARA MOSTRAR MENSAJES AL USUARIO.
$mensaje_error_servidor = '';
$mensaje_alerta_sesion = ''; 
// VERIFICA SI HAY MENSAJES DE ERROR EN LA URL, COMO CUANDO UNA SESIÓN CADUCA.
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'inactive') {
        $mensaje_alerta_sesion = "Su sesión caducó por inactividad. Vuelva a ingresar credenciales.";
    } elseif ($_GET['error'] === 'nosession') {
        $mensaje_alerta_sesion = "Debe ingresar sus credenciales para acceder.";
    }
}

// PROCESAMIENTO DEL FORMULARIO CUANDO SE ENVÍA (MÉTODO POST).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar'])){

    // VALIDACIÓN BÁSICA PARA ASEGURAR QUE LOS CAMPOS NO ESTÉN VACÍOS.
    if (!empty($_POST['id_tipo_doc']) && !empty($_POST['doc_usu']) && !empty($_POST['pass'])) {

        // RECOGE Y LIMPIA LOS DATOS DEL FORMULARIO.
        $id_tipo_doc = $_POST['id_tipo_doc'];
        $doc_usu     = trim($_POST['doc_usu']);
        $contrasena_ingresada = $_POST['pass'];

        try {
            // PREPARA Y EJECUTA LA CONSULTA PARA BUSCAR AL USUARIO EN LA BASE DE DATOS.
            $sql_login = $con->prepare("SELECT doc_usu, id_tipo_doc, pass, id_rol, nom_usu, id_est FROM usuarios WHERE id_tipo_doc = :tipo_doc AND doc_usu = :doc");
            $sql_login->bindParam(':tipo_doc', $id_tipo_doc, PDO::PARAM_INT);
            $sql_login->bindParam(':doc', $doc_usu, PDO::PARAM_STR);
            $sql_login->execute();
            $usuario = $sql_login->fetch(PDO::FETCH_ASSOC);

            // SI SE ENCUENTRA UN USUARIO.
            if ($usuario) {
                // VERIFICA SI EL ESTADO DEL USUARIO ES ACTIVO (ID_EST = 1).
                if ($usuario['id_est'] == 1) { 
                    // VERIFICA SI LA CONTRASEÑA INGRESADA COINCIDE CON LA ALMACENADA (HASHED).
                    if (password_verify($contrasena_ingresada, $usuario['pass'])) {

                        // SI LAS CREDENCIALES SON CORRECTAS, SE CREAN LAS VARIABLES DE SESIÓN.
                        $_SESSION['doc_usu'] = $usuario['doc_usu'];
                        $_SESSION['id_rol'] = $usuario['id_rol'];
                        $_SESSION['nombre_usuario'] = $usuario['nom_usu'];
                        $_SESSION['loggedin'] = true;
                        $_SESSION['time'] = time(); 

                        // REDIRIGE AL USUARIO A SU PANEL CORRESPONDIENTE SEGÚN SU ROL.
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
                            header('Location: medi/citas_hoy.php');
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
            // MANEJO DE ERRORES DE CONEXIÓN O CONSULTA A LA BASE DE DATOS.
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
    <!-- CONFIGURACIÓN DEL HEAD DEL DOCUMENTO HTML. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de sesión - Salud Connected</title>
    <!-- ICONO DE LA PESTAÑA DEL NAVEGADOR. -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/loguito.png">
    <!-- ENLACES A HOJAS DE ESTILO. -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/estilos_inicio.css">
</head>
<body class="index">
    <?php 
    // INCLUYE EL MENÚ DE NAVEGACIÓN.
    require ROOT_PATH . '/menu_inicio.php'; 
    ?>
    <div class="overlay"></div>
    <main>
        <!-- CONTENEDOR PRINCIPAL DEL FORMULARIO DE LOGIN. -->
        <div class="inicio_sesion">
            <div class="form-inicio">
               <form action="<?php echo BASE_URL; ?>/inicio_sesion.php" method="post" id="formulario-login" novalidate>
                   <ul>
                      <h1>Inicio de sesión</h1>
                      <?php
                          // BLOQUE PARA MOSTRAR MENSAJES DE ERROR O ALERTA GENERADOS POR EL SERVIDOR.
                          if (!empty($mensaje_alerta_sesion)) {
                              echo '<li class="mensaje-login-alerta">' . htmlspecialchars($mensaje_alerta_sesion) . '</li>';
                          }
                          if (!empty($mensaje_error_servidor)) {
                              echo '<li class="mensaje-login-servidor">' . htmlspecialchars($mensaje_error_servidor) . '</li>';
                          }
                      ?>
                      <!-- CAMPO PARA SELECCIONAR EL TIPO DE DOCUMENTO. -->
                      <li class="campo-contenedor">
                         <label for="id_tipo_doc">Tipo de documento</label>
                         <select name="id_tipo_doc" id="id_tipo_doc" tabindex="1">
                             <option value="">Seleccione...</option>
                              <?php
                              // CARGA DINÁMICAMENTE LOS TIPOS DE DOCUMENTO DESDE LA BASE DE DATOS.
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
                     <!-- CAMPO PARA INGRESAR EL NÚMERO DE DOCUMENTO. -->
                     <li class="campo-contenedor">
                         <label for="doc_usu">Documento</label>
                         <input type="text" name="doc_usu" id="doc_usu" placeholder="Ingrese su documento" inputmode="numeric" pattern="\d*" tabindex="2" value="<?php echo isset($_POST['doc_usu']) ? htmlspecialchars($_POST['doc_usu']) : ''; ?>">
                         <span class="mensaje-error-cliente" id="error-doc-usu"></span>
                      </li>
                      <!-- CAMPO PARA INGRESAR LA CONTRASEÑA. -->
                      <li class="campo-contenedor">
                         <label for="pass">Contraseña</label>
                         <input type="password" name="pass" id="pass" value="" maxlength="20" minlength="8" placeholder="Ingrese su contraseña" tabindex="3">
                         <span class="mensaje-error-cliente" id="error-pass"></span>
                      </li>
                      <!-- CHECKBOX PARA ACEPTAR TÉRMINOS Y CONDICIONES. -->
                      <li class="campo-terminos">
                          <input type="checkbox" id="acepto_terminos" name="acepto_terminos" tabindex="4">
                          <label for="acepto_terminos">Acepto los <a href="#" id="enlace_terminos">Términos y Condiciones</a></label>
                      </li>
                      <!-- ENLACE PARA LA RECUPERACIÓN DE CONTRASEÑA. -->
                      <div class="links">
                          <a href="<?php echo BASE_URL; ?>/include/olvide_contra.php">Olvidé mi contraseña</a>
                      </div>
                      <!-- BOTÓN DE ENVÍO DEL FORMULARIO. -->
                      <li>
                          <input type="submit" name="enviar" id="boton-enviar" value="Ingresar" tabindex="5" disabled>
                      </li>
                   </ul>
               </form>
            </div>
        </div>
    </main>

    <?php 
    // INCLUYE EL CONTENIDO HTML DEL MODAL DE TÉRMINOS Y CONDICIONES.
    require ROOT_PATH . '/include/modal_terminos.php'; 
    ?>

    <?php 
    // INCLUYE EL PIE DE PÁGINA.
    require ROOT_PATH . '/footer_inicio.php'; 
    ?>
    <!-- ENLACES A LOS ARCHIVOS JAVASCRIPT PARA VALIDACIONES Y FUNCIONALIDADES DE LA PÁGINA. -->
    <script src="<?php echo BASE_URL; ?>/js/inicio_sesion.js"></script>
    <script src="<?php echo BASE_URL; ?>/js/menu_responsivo.js"></script>
</body>
</html>