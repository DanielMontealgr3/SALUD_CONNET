<?php
// BLOQUE 1: VERIFICACIÓN DE SESIÓN VÁLIDA
// ESTE SCRIPT ASUME QUE 'config.php' YA HA SIDO INCLUIDO Y LA SESIÓN YA HA SIDO INICIADA.

// SE VERIFICA SI EL USUARIO NO ESTÁ LOGUEADO.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol'])) {
    
    // BLOQUE 2: DETECCIÓN DE PETICIÓN AJAX
    // SE COMPRUEBA SI LA SOLICITUD FUE HECHA POR JAVASCRIPT (AJAX) O POR EL NAVEGADOR DIRECTAMENTE.
    // ESTO ES IMPORTANTE PARA DAR UNA RESPUESTA ADECUADA A CADA CASO.
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        
        // SI ES UNA PETICIÓN AJAX:
        // NO SE REDIRIGE, SINO QUE SE ENVÍA UNA RESPUESTA JSON CON UN CÓDIGO DE ERROR DE AUTENTICACIÓN (401).
        // EL JAVASCRIPT DEL LADO DEL CLIENTE DEBERÁ INTERPRETAR ESTA RESPUESTA Y REDIRIGIR AL USUARIO.
        header('Content-Type: application/json');
        http_response_code(401); 
        echo json_encode(['error' => 'La sesión ha expirado o no es válida. Por favor, recargue la página.']);
        exit();

    } else {
        
        // SI NO ES UNA PETICIÓN AJAX (ES UNA CARGA DE PÁGINA NORMAL):
        // SE PROCEDE A DESTRUIR CUALQUIER RESIDUO DE SESIÓN Y A REDIRIGIR.
        
        // 1. LIMPIA TODAS LAS VARIABLES DE LA SESIÓN.
        $_SESSION = array();

        // 2. DESTRUYE LA COOKIE DE SESIÓN SI EXISTE.
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // 3. DESTRUYE COMPLETAMENTE LA SESIÓN EN EL SERVIDOR.
        session_destroy();

        // 4. REDIRIGE A LA PÁGINA DE LOGIN.
        // SE UTILIZA LA CONSTANTE 'BASE_URL' DEFINIDA EN 'config.php' PARA CONSTRUIR LA RUTA ABSOLUTA.
        // ESTO GARANTIZA QUE LA REDIRECCIÓN SIEMPRE FUNCIONE, SIN IMPORTAR DESDE DÓNDE SE LLAME EL SCRIPT.
        header('Location: ' . BASE_URL . '/inicio_sesion.php?error=nosession'); 
        exit();
    }
}
?>