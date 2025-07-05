<?php
// BLOQUE 0: MANEJO DE ERRORES (ÚTIL PARA DEPURACIÓN)
ini_set('display_errors', 0); // Cambiar a 1 para ver errores en desarrollo
ini_set('log_errors', 1);
error_reporting(E_ALL);

// BLOQUE 1: CONFIGURACIÓN DE RUTAS
define('ROOT_PATH', __DIR__ . '/..');

if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
    define('BASE_URL', '/SALUDCONNECT');
    $cookie_domain = 'localhost';
} else {
    define('BASE_URL', '');
    $cookie_domain = '.saludconnected.com'; // El punto al inicio es importante para subdominios
}

// BLOQUE 2: CONFIGURACIÓN ROBUSTA DE SESIONES
// ESTO ASEGURA QUE LAS COOKIES DE SESIÓN FUNCIONEN CORRECTAMENTE EN HOSTINGER.
session_set_cookie_params([
    'lifetime' => 0, // La cookie dura hasta que se cierre el navegador
    'path' => '/',
    'domain' => $cookie_domain,
    'secure' => isset($_SERVER['HTTPS']), // True si es HTTPS
    'httponly' => true, // La cookie no es accesible por JavaScript
    'samesite' => 'Lax' // Protección contra ataques CSRF
]);
session_start();


// BLOQUE 3: CONEXIÓN A LA BASE DE DATOS
require_once ROOT_PATH . '/include/conexion.php';
$db = new Database();
$con = $db->conectar();

?>