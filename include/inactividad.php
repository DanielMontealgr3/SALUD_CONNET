<?php
// BLOQUE 1: VERIFICACIÓN DE SESIÓN
// SE ASEGURA DE QUE HAYA UNA SESIÓN ACTIVA ANTES DE CONTINUAR. SI NO, DETIENE LA EJECUCIÓN.
// ESTO PREVIENE ERRORES SI EL ARCHIVO ES INCLUIDO EN UN CONTEXTO INCORRECTO.
if (session_status() == PHP_SESSION_NONE) {
    // ESTA LÍNEA ES PARA DEPURACIÓN. EN PRODUCCIÓN, PODRÍA SIMPLEMENTE NO HACER NADA.
    exit('Error crítico: La sesión no fue iniciada antes de verificar la inactividad.'); 
}

// BLOQUE 2: LÓGICA DE INACTIVIDAD
// DEFINE EL TIEMPO MÁXIMO DE INACTIVIDAD EN SEGUNDOS (POR EJEMPLO, 900 SEGUNDOS = 15 MINUTOS).
$tiempo_inactivo = 900; 

// VERIFICA SI EXISTE LA VARIABLE DE SESIÓN 'time', QUE ALMACENA LA ÚLTIMA ACTIVIDAD.
if (isset($_SESSION['time'])) {
    // CALCULA EL TIEMPO TRANSCURRIDO DESDE LA ÚLTIMA ACTIVIDAD.
    $vida_session = time() - $_SESSION['time'];

    // SI EL TIEMPO TRANSCURRIDO SUPERA EL LÍMITE DE INACTIVIDAD.
    if ($vida_session > $tiempo_inactivo) {
        
        // ---- PROCESO DE CIERRE DE SESIÓN SEGURO ----
        // 1. BORRA TODAS LAS VARIABLES DE LA SESIÓN.
        $_SESSION = array();

        // 2. SI SE USAN COOKIES, BORRA LA COOKIE DE SESIÓN DEL NAVEGADOR.
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // 3. DESTRUYE COMPLETAMENTE LA SESIÓN EN EL SERVIDOR.
        session_destroy();
        
        // 4. REDIRIGE AL USUARIO A LA PÁGINA DE LOGIN CON UN MENSAJE DE ERROR.
        // SE USA LA CONSTANTE 'BASE_URL' PARA CONSTRUIR UNA RUTA ABSOLUTA Y SEGURA.
        header("Location: " . BASE_URL . "/inicio_sesion.php?error=inactive");
        exit();
    }
}

// BLOQUE 3: ACTUALIZACIÓN DEL TIEMPO DE ACTIVIDAD
// SI LA SESIÓN NO HA EXPIRADO, ACTUALIZA LA MARCA DE TIEMPO DE LA ÚLTIMA ACTIVIDAD AL MOMENTO ACTUAL.
// ESTO "REINICIA" EL CONTADOR DE INACTIVIDAD CON CADA CARGA DE PÁGINA.
$_SESSION['time'] = time();
?>