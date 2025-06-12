<?php
// farma/includes_farm/PhpSpreadsheet-master/src/Bootstrap.php

spl_autoload_register(function ($class) {
    // Verifica que la clase que se intenta cargar pertenece a PhpSpreadsheet
    if (strpos($class, 'PhpOffice\\PhpSpreadsheet\\') === 0) {
        // Reemplaza el namespace con la ruta del directorio
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen('PhpOffice\\PhpSpreadsheet\\'))) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});