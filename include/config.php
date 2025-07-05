<?php

// ... (tu código existente de config.php: session_start, define BASE_URL, ROOT_PATH, conexión a BD) ...

// =================================================================
// ===     CONFIGURACIÓN CENTRALIZADA DE ENVÍO DE CORREOS (SMTP)   ===
// =================================================================
// Coloca aquí las credenciales del servidor de correo que quieres usar.
// Si estás en Hostinger, usa las de Hostinger. Si quieres probar con Gmail, usa las de Gmail.
define('SMTP_HOST', 'smtp.hostinger.com');      // Para Hostinger. Para Gmail: 'smtp.gmail.com'
define('SMTP_PORT', 465);                      // Para Hostinger (SSL). Para Gmail (TLS): 587
define('SMTP_USERNAME', 'soporte@saludconnected.com'); // Tu correo real
define('SMTP_PASSWORD', 'Saludconnected2025*');     // Tu contraseña real o de aplicación
define('SMTP_SECURE', 'ssl');                  // Para Hostinger. Para Gmail: 'tls'
define('SMTP_FROM_EMAIL', 'soporte@saludconnected.com'); // El correo que aparecerá como remitente
define('SMTP_FROM_NAME', 'Soporte Salud Connected'); // El nombre que aparecerá como remitente

// =================================================================
// ARCHIVO DE CONFIGURACIÓN CENTRAL (config.php)
// =================================================================

// BLOQUE 0: MANEJO DE ERRORES (ÚTIL PARA DEPURACIÓN)
// En producción, es mejor tener display_errors en 0 por seguridad.
ini_set('display_errors', 0); 
ini_set('log_errors', 1);
// Opcional: define una ruta para el archivo de log de errores
// ini_set('error_log', ROOT_PATH . '/php-errors.log');
error_reporting(E_ALL);

// BLOQUE 1: CONFIGURACIÓN DE RUTAS
// Define la ruta raíz del proyecto en el servidor. __DIR__ es el directorio actual (include),
// por lo que '/..' sube un nivel al directorio raíz del proyecto.
define('ROOT_PATH', __DIR__ . '/..');

// DETECCIÓN DEL ENTORNO (Local vs Producción)
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
    // Entorno Local (XAMPP)
    define('BASE_URL', '/SALUDCONNECT'); // Ruta base para URLs en HTML/JS
    $cookie_domain = 'localhost';
    $cookie_secure = false; // HTTP en local
} else {
    // Entorno de Producción (Hostinger)
    define('BASE_URL', ''); // En el dominio raíz, la base es vacía.
    $cookie_domain = '.saludconnected.com'; // El punto inicial cubre www. y otros subdominios.
    $cookie_secure = true; // Forzar HTTPS en producción.
}

// BLOQUE 2: CONFIGURACIÓN ROBUSTA DE SESIONES
// Esto soluciona el problema de pérdida de sesión en llamadas AJAX en Hostinger.
session_set_cookie_params([
    'lifetime' => 0, // La cookie expira cuando se cierra el navegador.
    'path' => '/',   // La cookie es válida para todo el sitio.
    'domain' => $cookie_domain, // Dominio correcto para local o producción.
    'secure' => $cookie_secure, // Debe ser `true` en producción (HTTPS).
    'httponly' => true, // Protege contra ataques XSS.
    'samesite' => 'Lax' // Buena protección contra CSRF.
]);

// Inicia la sesión si no hay una activa.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// BLOQUE 3: CONEXIÓN A LA BASE DE DATOS
// Requiere la clase de conexión y la instancia.
// La variable $con estará disponible globalmente en los scripts que incluyan config.php.
require_once ROOT_PATH . '/include/conexion.php';
$db = new Database();
$con = $db->conectar();
?>