<?php
require_once '../../../include/validar_sesion.php';
require_once '../../../include/inactividad.php';
require_once '../../../include/conexion.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }

$response = ['success' => false, 'message' => 'Acción no permitida o datos incorrectos.'];
$redirect_page = 'ver_departamentos.php'; 

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['csrf_token']) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $id_registro = trim($_POST['id_registro'] ?? '');
            $tipo_registro = trim($_POST['tipo_registro'] ?? '');

            if (!empty($id_registro) && !empty($tipo_registro)) {
                $conex = new database();
                $con = $conex->conectar();
                $can_delete = true;
                $error_message = '';

                if ($con) {
                    try {
                        $con->beginTransaction();

                        if ($tipo_registro === 'departamento') {
                            $redirect_page = 'ver_departamentos.php';
                            $stmt_check = $con->prepare("SELECT COUNT(*) FROM municipio WHERE id_dep = :id");
                            $stmt_check->bindParam(':id', $id_registro);
                            $stmt_check->execute();
                            if ($stmt_check->fetchColumn() > 0) {
                                $can_delete = false;
                                $error_message = "No se puede eliminar el departamento porque tiene municipios asociados (tabla 'municipio').";
                            } else {
                                $stmt_delete = $con->prepare("DELETE FROM departamento WHERE id_dep = :id");
                            }
                        } elseif ($tipo_registro === 'municipio') {
                            $redirect_page = 'ver_municipios.php';
                            $stmt_check = $con->prepare("SELECT COUNT(*) FROM barrio WHERE id_mun = :id");
                            $stmt_check->bindParam(':id', $id_registro);
                            $stmt_check->execute();
                            if ($stmt_check->fetchColumn() > 0) {
                                $can_delete = false;
                                $error_message = "No se puede eliminar el municipio porque tiene barrios asociados (tabla 'barrio').";
                            } else {
                                $stmt_delete = $con->prepare("DELETE FROM municipio WHERE id_mun = :id");
                            }
                        } elseif ($tipo_registro === 'barrio') {
                            $redirect_page = 'ver_barrios.php';
                            $stmt_check_usuarios = $con->prepare("SELECT COUNT(*) FROM usuarios WHERE id_barrio = :id");
                            $stmt_check_usuarios->bindParam(':id', $id_registro);
                            $stmt_check_usuarios->execute();
                            if ($stmt_check_usuarios->fetchColumn() > 0) {
                                $can_delete = false;
                                $error_message = "No se puede eliminar el barrio porque está asignado a uno o más usuarios (tabla 'usuarios').";
                            } else {
                                $stmt_delete = $con->prepare("DELETE FROM barrio WHERE id_barrio = :id");
                            }
                        } else {
                            $can_delete = false;
                            $error_message = "Tipo de registro no válido para eliminación.";
                        }

                        if ($can_delete && isset($stmt_delete)) {
                            $stmt_delete->bindParam(':id', $id_registro);
                            if ($stmt_delete->execute()) {
                                if ($stmt_delete->rowCount() > 0) {
                                    $con->commit();
                                    $_SESSION['mensaje_accion'] = ucfirst($tipo_registro) . " eliminado correctamente.";
                                    $_SESSION['mensaje_accion_tipo'] = 'success';
                                } else {
                                    $con->rollBack();
                                    $_SESSION['mensaje_accion'] = "No se encontró el " . $tipo_registro . " para eliminar o ya fue eliminado.";
                                    $_SESSION['mensaje_accion_tipo'] = 'warning';
                                }
                            } else {
                                $con->rollBack();
                                $error_info = $stmt_delete->errorInfo();
                                $_SESSION['mensaje_accion'] = "Error al eliminar el " . $tipo_registro . ": " . ($error_info[2] ?? 'Error desconocido');
                                $_SESSION['mensaje_accion_tipo'] = 'danger';
                                error_log("Error SQL eliminando $tipo_registro ID $id_registro: " . ($error_info[2] ?? 'Error desconocido'));
                            }
                        } elseif (!$can_delete) {
                            $con->rollBack();
                            $_SESSION['mensaje_accion'] = $error_message;
                            $_SESSION['mensaje_accion_tipo'] = 'danger';
                        }

                    } catch (PDOException $e) {
                        if ($con->inTransaction()) { $con->rollBack(); }
                        $_SESSION['mensaje_accion'] = "Error de base de datos al eliminar: " . $e->getMessage();
                        $_SESSION['mensaje_accion_tipo'] = 'danger';
                        error_log("PDOException eliminando $tipo_registro ID $id_registro: " . $e->getMessage());
                    } finally {
                        $con = null;
                    }
                } else {
                    $_SESSION['mensaje_accion'] = "Error de conexión a la base de datos.";
                    $_SESSION['mensaje_accion_tipo'] = 'danger';
                }
            } else {
                $_SESSION['mensaje_accion'] = "ID de registro o tipo no especificado.";
                $_SESSION['mensaje_accion_tipo'] = 'warning';
            }
        } else {
            $_SESSION['mensaje_accion'] = "Error de seguridad (CSRF token). Intente de nuevo.";
            $_SESSION['mensaje_accion_tipo'] = 'danger';
            error_log("Fallo CSRF en eliminar_geografica.php. Sesión: " . ($_SESSION['csrf_token'] ?? 'NO SET') . " - POST: " . ($_POST['csrf_token'] ?? 'NO SET'));
        }
    } else {
        $_SESSION['mensaje_accion'] = "Método no permitido.";
        $_SESSION['mensaje_accion_tipo'] = 'danger';
    }
} else {
     $_SESSION['mensaje_accion'] = "Acceso no autorizado.";
     $_SESSION['mensaje_accion_tipo'] = 'danger';
     $redirect_page = '../../inicio_sesion.php'; 
}

header("Location: " . $redirect_page);
exit;
?>