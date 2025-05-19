<?php

if (session_status() == PHP_SESSION_NONE) {
    exit('Error: La sesión no está iniciada antes de verificar inactividad.'); 
}

$inactivo = 60000; 

if (isset($_SESSION['time'])) {
    $vida_session = time() - $_SESSION['time'];

    if ($vida_session > $inactivo) {
        
        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        
        header("Location: ../inicio_sesion.php?error=inactive");
        exit();
    }
}

$_SESSION['time'] = time();
?>