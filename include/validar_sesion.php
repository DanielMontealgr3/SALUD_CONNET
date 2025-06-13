<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol'])) {

    // Comprobamos si la petición es de tipo AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        
        // Si es AJAX, no redirigimos. Enviamos un error en formato JSON.
        header('Content-Type: application/json');
        http_response_code(401); // Código de error de autenticación
        echo json_encode(['error' => 'La sesión ha expirado. Por favor, recargue la página.']);
        exit();

    } else {
        
        // Si NO es AJAX, hacemos exactamente lo que tu código original hacía.
        // Tu código de destruir la sesión y redirigir está aquí, intacto.
        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        header('Location: ../inicio_sesion.php?error=nosession'); 
        exit();
    }
}
?>