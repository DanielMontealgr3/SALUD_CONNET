<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }

$pagina_retorno_default_usuario = '../gestion_crear/ver_usu.php'; 
$pagina_retorno_default_entidad = '../gestion_entidades/ver_entidades.php';
$pagina_retorno_error_csrf = '../gestion_entidades/ver_entidades.php'; 

if (isset($_POST['tipo_registro']) && $_POST['tipo_registro'] === 'usuario') {
    $pagina_retorno_error_csrf = $pagina_retorno_default_usuario;
}


if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) {
    $_SESSION['mensaje_accion'] = "Acceso no autorizado.";
    $_SESSION['mensaje_accion_tipo'] = "danger";
    header('Location: ' . $pagina_retorno_error_csrf); 
    exit;
}

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['mensaje_accion'] = "Error de validación (CSRF). Intento de eliminación bloqueado.";
    $_SESSION['mensaje_accion_tipo'] = "danger";
    header('Location: ' . $pagina_retorno_error_csrf);
    exit;
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_registro'], $_POST['tipo_registro'])) {
    $id_registro = $_POST['id_registro'];
    $tipo_registro = $_POST['tipo_registro'];
    $id_tipo_doc = $_POST['id_tipo_doc'] ?? null; 

    $conex = new database();
    $con = $conex->conectar();
    $nombre_entidad_para_mensaje = "";

    if ($con) {
        try {
            $params_check = [':id_registro' => $id_registro];
            $dependencia_encontrada = false;
            $mensaje_dependencia = "";
            $nombre_campo_entidad = "";
            $tabla_entidad_principal = "";

            if ($tipo_registro === 'ips') {
                $nombre_campo_entidad = "nom_IPS"; $tabla_entidad_principal = "ips"; $campo_pk_entidad = "Nit_IPS";
                $stmt_nombre = $con->prepare("SELECT {$nombre_campo_entidad} FROM {$tabla_entidad_principal} WHERE {$campo_pk_entidad} = :id_registro");
                $stmt_nombre->execute([':id_registro' => $id_registro]);
                $nombre_entidad_para_mensaje = $stmt_nombre->fetchColumn();

                $stmt_check_dep = $con->prepare("SELECT COUNT(*) FROM detalle_eps_ips WHERE nit_ips = :id_registro");
                $stmt_check_dep->execute($params_check);
                if ($stmt_check_dep->fetchColumn() > 0) {
                    $dependencia_encontrada = true;
                    $mensaje_dependencia = "la IPS '" . htmlspecialchars($nombre_entidad_para_mensaje ?: $id_registro) . "' está asignada en detalles EPS-IPS.";
                }
            } elseif ($tipo_registro === 'eps') {
                $nombre_campo_entidad = "nombre_eps"; $tabla_entidad_principal = "eps"; $campo_pk_entidad = "nit_eps";
                $stmt_nombre = $con->prepare("SELECT {$nombre_campo_entidad} FROM {$tabla_entidad_principal} WHERE {$campo_pk_entidad} = :id_registro");
                $stmt_nombre->execute([':id_registro' => $id_registro]);
                $nombre_entidad_para_mensaje = $stmt_nombre->fetchColumn();

                $stmt_check_dep = $con->prepare("SELECT COUNT(*) FROM detalle_eps_ips WHERE nit_eps = :id_registro");
                $stmt_check_dep->execute($params_check);
                if ($stmt_check_dep->fetchColumn() > 0) {
                    $dependencia_encontrada = true;
                    $mensaje_dependencia = "la EPS '" . htmlspecialchars($nombre_entidad_para_mensaje ?: $id_registro) . "' está asignada en detalles EPS-IPS.";
                }
            } elseif ($tipo_registro === 'farmacias') {
                $nombre_campo_entidad = "nom_farm"; $tabla_entidad_principal = "farmacias"; $campo_pk_entidad = "nit_farm";
                $stmt_nombre = $con->prepare("SELECT {$nombre_campo_entidad} FROM {$tabla_entidad_principal} WHERE {$campo_pk_entidad} = :id_registro");
                $stmt_nombre->execute([':id_registro' => $id_registro]);
                $nombre_entidad_para_mensaje = $stmt_nombre->fetchColumn();
                
                $stmt_check_dep_farmaceutas = $con->prepare("SELECT COUNT(*) FROM usuarios WHERE id_farmacia_asignada = :id_registro AND id_rol = 3");
                $stmt_check_dep_farmaceutas->execute($params_check);
                if ($stmt_check_dep_farmaceutas->fetchColumn() > 0) {
                    $dependencia_encontrada = true;
                    $mensaje_dependencia = "la Farmacia '" . htmlspecialchars($nombre_entidad_para_mensaje ?: $id_registro) . "' tiene farmaceutas asignados.";
                }
            }

            if ($dependencia_encontrada) {
                $_SESSION['mensaje_accion'] = "Eliminación no permitida: " . $mensaje_dependencia . " Debe desasignarla/eliminarlas primero.";
                $_SESSION['mensaje_accion_tipo'] = "warning";
                header('Location: ' . ($tipo_registro === 'usuario' ? $pagina_retorno_default_usuario : $pagina_retorno_default_entidad));
                exit;
            }

            $con->beginTransaction();
            $sql = "";
            $params_delete = [':id_registro' => $id_registro];
            $tabla_afectada = "";
            $pagina_retorno = $pagina_retorno_default_entidad;


            if ($tipo_registro === 'usuario') {
                if ($id_tipo_doc === null) {
                    throw new Exception("Falta el tipo de documento para eliminar el usuario.");
                }
                $sql = "DELETE FROM usuarios WHERE doc_usu = :id_registro AND id_tipo_doc = :id_tipo_doc";
                $params_delete[':id_tipo_doc'] = $id_tipo_doc;
                $tabla_afectada = "usuarios";
                $pagina_retorno = $pagina_retorno_default_usuario;
            } elseif ($tipo_registro === 'farmacias') {
                $sql = "DELETE FROM farmacias WHERE nit_farm = :id_registro";
                $tabla_afectada = "farmacias";
            } elseif ($tipo_registro === 'eps') {
                $sql = "DELETE FROM eps WHERE nit_eps = :id_registro";
                $tabla_afectada = "eps";
            } elseif ($tipo_registro === 'ips') {
                $sql = "DELETE FROM ips WHERE Nit_IPS = :id_registro"; 
                $tabla_afectada = "ips";
            } else {
                throw new Exception("Tipo de registro no válido para eliminación.");
            }

            $stmt = $con->prepare($sql);
            $stmt->execute($params_delete);

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
            if ($e->getCode() == '23000') {
                 $_SESSION['mensaje_accion'] = "Eliminación no permitida: La entidad '" . htmlspecialchars($nombre_entidad_para_mensaje ?: $id_registro) . "' está siendo referenciada en otras tablas. Verifique las asignaciones o registros relacionados.";
                 $_SESSION['mensaje_accion_tipo'] = "danger";
            } else {
                $_SESSION['mensaje_accion'] = "Error de base de datos al intentar eliminar: " . htmlspecialchars($e->getMessage());
                $_SESSION['mensaje_accion_tipo'] = "danger";
            }
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
    header('Location: ' . ($tipo_registro === 'usuario' ? $pagina_retorno_default_usuario : $pagina_retorno_default_entidad));
    exit;
} else {
    $_SESSION['mensaje_accion'] = "Solicitud no válida.";
    $_SESSION['mensaje_accion_tipo'] = "danger";
    header('Location: ' . $pagina_retorno_default_usuario); 
    exit;
}
?>