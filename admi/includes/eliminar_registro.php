<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once('../../include/conexion.php');

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) {
    $_SESSION['mensaje_accion'] = "Acceso no autorizado.";
    $_SESSION['mensaje_accion_tipo'] = "danger";
    header('Location: ' . ($_POST['tipo_registro'] === 'usuario' ? 'ver_usu.php' : 'ver_entidades.php')); // Redirigir apropiadamente
    exit;
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['mensaje_accion'] = "Error de validación (CSRF). Intento de eliminación bloqueado.";
    $_SESSION['mensaje_accion_tipo'] = "danger";
    header('Location: ' . ($_POST['tipo_registro'] === 'usuario' ? 'ver_usu.php' : 'ver_entidades.php'));
    exit;
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_registro'], $_POST['tipo_registro'])) {
    $id_registro = $_POST['id_registro'];
    $tipo_registro = $_POST['tipo_registro'];
    $id_tipo_doc = $_POST['id_tipo_doc'] ?? null; // Específico para usuarios

    $conex = new database();
    $con = $conex->conectar();

    if ($con) {
        try {
            $con->beginTransaction();
            $sql = "";
            $params = [':id_registro' => $id_registro];
            $tabla_afectada = "";
            $pagina_retorno = "ver_entidades.php";


            if ($tipo_registro === 'usuario') {
                if ($id_tipo_doc === null) {
                    throw new Exception("Falta el tipo de documento para eliminar el usuario.");
                }
                $sql = "DELETE FROM usuarios WHERE doc_usu = :id_registro AND id_tipo_doc = :id_tipo_doc";
                $params[':id_tipo_doc'] = $id_tipo_doc;
                $tabla_afectada = "usuarios";
                $pagina_retorno = "ver_usu.php";
            } elseif ($tipo_registro === 'farmacias') {
                $sql = "DELETE FROM farmacias WHERE nit_farm = :id_registro";
                $tabla_afectada = "farmacias";
            } elseif ($tipo_registro === 'eps') {
                $sql = "DELETE FROM eps WHERE nit_eps = :id_registro";
                $tabla_afectada = "eps";
            } elseif ($tipo_registro === 'ips') {
                $sql = "DELETE FROM ips WHERE Nit_IPS = :id_registro"; // Ojo con mayúsculas
                $tabla_afectada = "ips";
            } else {
                throw new Exception("Tipo de registro no válido para eliminación.");
            }

            $stmt = $con->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() > 0) {
                $con->commit();
                $_SESSION['mensaje_accion'] = "Registro (ID: " . htmlspecialchars($id_registro) . ") de " . htmlspecialchars($tipo_registro) . " eliminado exitosamente.";
                $_SESSION['mensaje_accion_tipo'] = "success";
            } else {
                $con->rollBack();
                $_SESSION['mensaje_accion'] = "No se encontró el registro (ID: " . htmlspecialchars($id_registro) . ") en " . htmlspecialchars($tabla_afectada) . " o no se pudo eliminar.";
                $_SESSION['mensaje_accion_tipo'] = "warning";
            }
        } catch (PDOException $e) {
            if ($con->inTransaction()) $con->rollBack();
            error_log("PDOException en eliminar_registro.php: " . $e->getMessage());
            $_SESSION['mensaje_accion'] = "Error de base de datos al intentar eliminar: " . htmlspecialchars($e->getMessage());
            $_SESSION['mensaje_accion_tipo'] = "danger";
        } catch (Exception $e) {
            if ($con && $con->inTransaction()) $con->rollBack();
             error_log("Exception en eliminar_registro.php: " . $e->getMessage());
            $_SESSION['mensaje_accion'] = "Error: " . htmlspecialchars($e->getMessage());
            $_SESSION['mensaje_accion_tipo'] = "danger";
        }
    } else {
        $_SESSION['mensaje_accion'] = "Error de conexión a la base de datos.";
        $_SESSION['mensaje_accion_tipo'] = "danger";
    }
    header('Location: ' . $pagina_retorno);
    exit;
} else {
    $_SESSION['mensaje_accion'] = "Solicitud no válida.";
    $_SESSION['mensaje_accion_tipo'] = "danger";
    header('Location: ver_usu.php'); // Redirigir a una página por defecto
    exit;
}
?>