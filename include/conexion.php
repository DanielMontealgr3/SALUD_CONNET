<?php

class Database {
    private $hostname_inst = "localhost";
    private $database_inst = "salud";
    private $username_inst = "root";
    private $password_inst = "";
    private $charset_inst = "utf8";
    private $pdo_inst = null;

    private static $hostname_static = "localhost";
    private static $database_static = "salud";
    private static $username_static = "root";
    private static $password_static = "";
    private static $charset_static = "utf8";
    private static $pdo_static_conn = null;

    public function __construct() {
    }
    public function conectar() {
        if ($this->pdo_inst === null) {
            try {
                $connection_string = "mysql:host=" . $this->hostname_inst . ";dbname=" . $this->database_inst . ";charset=" . $this->charset_inst;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                $this->pdo_inst = new PDO($connection_string, $this->username_inst, $this->password_inst, $options);
            } catch (PDOException $e) {
                error_log('Error de conexion (instancia): ' . $e->getMessage());
                return null;
            }
        }
        return $this->pdo_inst;
    }

    public function desconectar() {
        $this->pdo_inst = null;
    }

    public static function connect() {
        if (self::$pdo_static_conn === null) {
            try {
                $connection_string = "mysql:host=" . self::$hostname_static . ";dbname=" . self::$database_static . ";charset=" . self::$charset_static;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                self::$pdo_static_conn = new PDO($connection_string, self::$username_static, self::$password_static, $options);
            } catch (PDOException $e) {
                error_log('Error de conexion (estática): ' . $e->getMessage());
                return null;
            }
        }
        return self::$pdo_static_conn;
    }

    // Método ESTÁTICO para desconectar
    public static function disconnect() {
        self::$pdo_static_conn = null;
    }
}
?>