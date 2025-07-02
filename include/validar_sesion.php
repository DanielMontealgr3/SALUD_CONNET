<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica si el usuario NO está logueado o si falta el rol
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol'])) {
    
    // Define el nombre base de tu proyecto. ¡Asegúrate de que coincida con tu estructura!
    $nombre_proyecto_base = 'SALUDCONNECT';

    // Construye la URL de redirección absoluta a la página de inicio de sesión en la raíz.
    // $_SERVER['HTTPS'] verifica si se usa SSL, 'HTTP_HOST' obtiene el dominio (ej. localhost).
    $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $url_base_proyecto = $protocolo . '://' . $host . '/' . $nombre_proyecto_base;
    
    // La URL final a la que se redirigirá si la sesión no es válida.
    $url_redireccion_login = $url_base_proyecto . '/inicio_sesion.php?error=nosession';

    // Comprobamos si la petición es de tipo AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        
        // Si es AJAX, no redirigimos. Enviamos un error en formato JSON.
        header('Content-Type: application/json');
        http_response_code(401); // Código de error de autenticación
        echo json_encode(['error' => 'La sesión ha expirado o no es válida. Por favor, recargue la página para iniciar sesión.']);
        exit();

    } else {
        
        // Si NO es una petición AJAX, limpiamos la sesión y redirigimos.
        
        // 1. Limpiar todas las variables de sesión
        $_SESSION = array();

        // 2. Destruir la cookie de sesión si existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // 3. Destruir la sesión completamente
        session_destroy();

        // 4. Redirigir a la URL de login absoluta que construimos.
        //    Esto funcionará sin importar desde qué subcarpeta se llame este script.
        header('Location: ' . $url_redireccion_login); 
        exit();
    }
}
?>