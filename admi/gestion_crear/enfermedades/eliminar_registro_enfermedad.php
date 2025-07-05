<?php
require_once '../../../include/validar_sesion.php';
require_once '../../../include/inactividad.php';
require_once '../../../include/conexion.php';

if (session_status() == PHP_SESSION_NONE) { session_start(); }

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['csrf_token']) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $id_registro = trim($_POST['id_registro'] ?? '');
            $tipo_registro = trim($_POST['tipo_registro_eliminar'] ?? ''); // Diferente nombre para evitar conflicto con edición

            $redirect_page = ($tipo_registro === 'tipo_enfermedad') ? 'ver_tipos_enfermedad.php' : 'ver_enfermedades.php';

            if (!empty($id_registro) && !empty($tipo_registro)) {
                $conex = new database();
                $con = $conex->conectar();
                $can_delete = true;
                $error_message = '';

                if ($con) {
                    try {
                        $con->beginTransaction();

                        if ($tipo_registro === 'tipo_enfermedad') {
                            $stmt_check = $con->prepare("SELECT COUNT(*) FROM enfermedades WHERE id_tipo_enfer = :id");
                            $stmt_check->bindParam(':id', $id_registro);
                            $stmt_check->execute();
                            if ($stmt_check->fetchColumn() > 0) {
                                $can_delete = false;
                                $error_message = "No se puede eliminar: este tipo está asignado a una o más enfermedades (tabla 'enfermedades').";
                            } else {
                                $stmt_delete = $con->prepare("DELETE FROM tipo_enfermedades WHERE id_tipo_enfer = :id");
                            }
                        } elseif ($tipo_registro === 'enfermedad') {
                            // Aquí puedes añadir validaciones si 'id_enferme' es FK en otras tablas.
                            // Ejemplo: $stmt_check_uso = $con->prepare("SELECT COUNT(*) FROM tabla_uso WHERE id_enferme = :id"); ...
                            // if ($stmt_check_uso->fetchColumn() > 0) { $can_delete = false; $error_message = "...en uso en tabla_uso"; }
                            if($can_delete){ // Solo si no hay otras validaciones que lo impidan
                               $stmt_delete = $con->prepare("DELETE FROM enfermedades WHERE id_enferme = :id");
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
                                    $_SESSION['mensaje_accion'] = ucfirst(str_replace('_', ' ', $tipo_registro)) . " eliminado correctamente.";
                                    $_SESSION['mensaje_accion_tipo'] = 'success';
                                } else {
                                    $con->rollBack();
                                    $_SESSION['mensaje_accion'] = "No se encontró el registro para eliminar.";
                                    $_SESSION['mensaje_accion_tipo'] = 'warning';
                                }
                            } else {
                                $con->rollBack(); $_SESSION['mensaje_accion'] = "Error al eliminar."; $_SESSION['mensaje_accion_tipo'] = 'danger';
                            }
                        } elseif (!$can_delete) {
                            $con->rollBack(); $_SESSION['mensaje_accion'] = $error_message; $_SESSION['mensaje_accion_tipo'] = 'danger';
                        }
                    } catch (PDOException $e) {
                        if ($con->inTransaction()) { $con->rollBack(); }
                        $_SESSION['mensaje_accion'] = "Error de BD: " . $e->getMessage(); $_SESSION['mensaje_accion_tipo'] = 'danger';
                    } finally { $con = null; }
                } else { $_SESSION['mensaje_accion'] = "Error de conexión."; $_SESSION['mensaje_accion_tipo'] = 'danger';}
            } else { $_SESSION['mensaje_accion'] = "ID o tipo no especificado."; $_SESSION['mensaje_accion_tipo'] = 'warning';}
        } else { $_SESSION['mensaje_accion'] = "Error de seguridad (CSRF)."; $_SESSION['mensaje_accion_tipo'] = 'danger';}
    } else { $_SESSION['mensaje_accion'] = "Método no permitido."; $_SESSION['mensaje_accion_tipo'] = 'danger';}
} else {
     $_SESSION['mensaje_accion'] = "Acceso no autorizado."; $_SESSION['mensaje_accion_tipo'] = 'danger';
     $redirect_page = '../../inicio_sesion.php'; 
}
header("Location: " . $redirect_page);
exit;
?>