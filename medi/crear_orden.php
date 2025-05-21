<?php
require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once('../include/conexion.php'); 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 4 || !isset($_SESSION['nombre_usuario'])) {
    header('Location: ../inicio_sesion.php'); 
    exit;
}

$nombre_usuario = $_SESSION['nombre_usuario'];
$pageTitle = "Inicio Médico"; 

?>