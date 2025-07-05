<?php
// /include/config.php

// 1. INICIAR SESIÓN
session_start();

// 2. DEFINIR LA RUTA BASE (BASE_URL)
// Esta constante nos servirá para la "magia" del reemplazo.
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
    // ESTÁS EN LOCALHOST
    // Cambia 'SALUD_CONNET' por el nombre de tu carpeta de proyecto si es diferente.
    define('BASE_URL', '/SALUD_CONNET'); 
} else {
    // ESTÁS EN PRODUCCIÓN (HOSTINGER)
    // En el servidor la ruta base es la raíz, por eso va vacío.
    define('BASE_URL', ''); 
}

// 3. LA MAGIA: REEMPLAZO AUTOMÁTICO DE RUTAS
// Esta función se ejecutará justo antes de que la página se envíe al navegador.
function reemplazar_rutas($buffer) {
    // Solo hacemos el reemplazo si BASE_URL no está vacía (es decir, si estamos en localhost).
    if (BASE_URL !== '') {
        // Busca todas las rutas relativas en href y src (que no empiecen con http, //, o #)
        // y les añade el BASE_URL al principio.
        $buffer = preg_replace('/(href|src)="(?!https?:\/\/|\/\/|#)([^"]*)"/', '$1="' . BASE_URL . '/$2"', $buffer);
    }
    return $buffer;
}

// Inicia el búfer de salida con nuestra función de reemplazo.
ob_start("reemplazar_rutas");


// 4. CONEXIÓN A LA BASE DE DATOS (el código inteligente que ya teníamos)
require_once 'conexion.php';

$db = new Database();
$con = $db->conectar();

?>