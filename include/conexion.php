<?php

class Database {
    private $hostname;
    private $database;
    private $username;
    private $password;
    private $charset = "utf8";
    private $pdo = null;

    public function __construct() {
        if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
            //LOCALHOST
            $this->hostname = "localhost";
            $this->database = "salud"; 
            $this->username = "root";
            $this->password = ""; 
        } else {
            //HOSTING
            $this->hostname = "localhost"; 
            $this->database = "u701947995_salud"; 
            $this->username = "u701947995_salud_user";
            $this->password = "AslyBrianDaniel2025*"; 
        }
    }

    public function conectar() {
        if ($this->pdo === null) {
            try {
                $connection_string = "mysql:host=" . $this->hostname . ";dbname=" . $this->database . ";charset=" . $this->charset;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                $this->pdo = new PDO($connection_string, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                // Nunca muestres errores detallados en producción. Guárdalos en un log.
                error_log('Error de conexión: ' . $e->getMessage());
                // Muestra un mensaje genérico al usuario
                die("Error: No se pudo conectar a la base de datos. Por favor, intente más tarde.");
            }
        }
        return $this->pdo;
    }

    public function desconectar() {
        $this->pdo = null;
    }

    // Si usas el método estático en otras partes del código, también debemos actualizarlo,
    // pero lo ideal es usar siempre la instancia ($db->conectar()).
    // Por ahora, lo dejamos como estaba para no romper tu código existente.
    public static function connect() {
        // Esta versión estática no se beneficiará de la detección automática.
        // Es mucho mejor que en tu código uses:
        // $db = new Database();
        // $con = $db->conectar();
        
        // Dejo el código original estático por si lo usas, pero deberías actualizarlo.
        $hostname_static = "localhost";
        $database_static = "salud";
        $username_static = "root";
        $password_static = "";
        $charset_static = "utf8";
        
        try {
            $connection_string = "mysql:host=" . $hostname_static . ";dbname=" . $database_static . ";charset=" . $charset_static;
            $options = [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC ];
            $pdo_static_conn = new PDO($connection_string, $username_static, $password_static, $options);
            return $pdo_static_conn;
        } catch (PDOException $e) {
            error_log('Error de conexion (estática): ' . $e->getMessage());
            return null;
        }
    }
}
?>