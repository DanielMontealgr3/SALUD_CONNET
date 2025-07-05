<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) { session_start(); }
require_once '../../../include/conexion.php'; // Ajusta la ruta si es necesario
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Acción no permitida o datos incorrectos.'];

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Error de validación CSRF. Intente de nuevo.']);
        exit;
    }

    $id_registro_original = trim($_POST['id_registro_original'] ?? '');
    $tipo_registro = trim($_POST['tipo_registro'] ?? '');

    if (empty($id_registro_original) || empty($tipo_registro)) {
        echo json_encode(['success' => false, 'message' => 'ID o tipo de registro no especificado.']);
        exit;
    }

    $conex = new database();
    $pdo = $conex->conectar();
    
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
        exit;
    }

    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->beginTransaction();

        if ($tipo_registro === 'departamento') {
            $nom_dep = trim($_POST['nom_dep_edit'] ?? '');
            if (empty($nom_dep)) {
                throw new Exception("El nombre del departamento es obligatorio.");
            }
            if (strlen($nom_dep) < 3 || strlen($nom_dep) > 100) {
                throw new Exception("El nombre del departamento debe tener entre 3 y 100 caracteres.");
            }
             if (!preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/u', $nom_dep)) {
                throw new Exception("El nombre del departamento solo debe contener letras y espacios.");
            }

            // Verificar si ya existe otro departamento con el mismo nombre
            $stmt_check = $pdo->prepare("SELECT id_dep FROM departamento WHERE nom_dep = :nom_dep AND id_dep != :id_dep_original");
            $stmt_check->execute([':nom_dep' => $nom_dep, ':id_dep_original' => $id_registro_original]);
            if ($stmt_check->fetch()) {
                throw new Exception("Ya existe un departamento con el nombre '" . htmlspecialchars($nom_dep) . "'.");
            }

            $sql = "UPDATE departamento SET nom_dep = :nom_dep WHERE id_dep = :id_dep_original";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':nom_dep' => $nom_dep, ':id_dep_original' => $id_registro_original]);
        
        } elseif ($tipo_registro === 'municipio') {
            $nom_mun = trim($_POST['nom_mun_edit'] ?? '');
            $id_dep_mun = trim($_POST['id_dep_edit_mun'] ?? '');
            if (empty($nom_mun) || empty($id_dep_mun)) {
                throw new Exception("Nombre del municipio y departamento son obligatorios.");
            }
            if (strlen($nom_mun) < 3 || strlen($nom_mun) > 100) {
                 throw new Exception("El nombre del municipio debe tener entre 3 y 100 caracteres.");
            }
            if (!preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ\s]+$/u', $nom_mun)) {
                throw new Exception("El nombre del municipio solo debe contener letras y espacios.");
            }
            // Verificar existencia y unicidad
            $stmt_check = $pdo->prepare("SELECT id_mun FROM municipio WHERE nom_mun = :nom_mun AND id_dep = :id_dep AND id_mun != :id_mun_original");
            $stmt_check->execute([':nom_mun' => $nom_mun, ':id_dep' => $id_dep_mun, ':id_mun_original' => $id_registro_original]);
            if ($stmt_check->fetch()) {
                throw new Exception("Ya existe un municipio con ese nombre en el departamento seleccionado.");
            }

            $sql = "UPDATE municipio SET nom_mun = :nom_mun, id_dep = :id_dep WHERE id_mun = :id_mun_original";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':nom_mun' => $nom_mun, ':id_dep' => $id_dep_mun, ':id_mun_original' => $id_registro_original]);

        } elseif ($tipo_registro === 'barrio') {
            $nom_barrio = trim($_POST['nom_barrio_edit'] ?? '');
            $id_mun_barrio = trim($_POST['id_mun_edit_barrio'] ?? '');
            // $id_dep_barrio es solo para la UI del modal, no se usa directamente en el UPDATE de barrio. $id_mun_barrio es la FK.
            if (empty($nom_barrio) || empty($id_mun_barrio)) {
                throw new Exception("Nombre del barrio y municipio son obligatorios.");
            }
            if (strlen($nom_barrio) < 3 || strlen($nom_barrio) > 150) {
                 throw new Exception("El nombre del barrio debe tener entre 3 y 150 caracteres.");
            }
            if (!preg_match('/^[a-zA-ZñÑáéíóúÁÉÍÓÚ0-9\s.,#-]+$/u', $nom_barrio)) { // Misma regex que en crear_barrios
                throw new Exception("El nombre del barrio contiene caracteres no permitidos.");
            }
            // Verificar existencia y unicidad
            $stmt_check = $pdo->prepare("SELECT id_barrio FROM barrio WHERE nom_barrio = :nom_barrio AND id_mun = :id_mun AND id_barrio != :id_barrio_original");
            $stmt_check->execute([':nom_barrio' => $nom_barrio, ':id_mun' => $id_mun_barrio, ':id_barrio_original' => $id_registro_original]);
            if ($stmt_check->fetch()) {
                throw new Exception("Ya existe un barrio con ese nombre en el municipio seleccionado.");
            }

            $sql = "UPDATE barrio SET nom_barrio = :nom_barrio, id_mun = :id_mun WHERE id_barrio = :id_barrio_original";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':nom_barrio' => $nom_barrio, ':id_mun' => $id_mun_barrio, ':id_barrio_original' => $id_registro_original]);
        
        } else {
            throw new Exception("Tipo de registro no válido para edición.");
        }

        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            $_SESSION['mensaje_accion'] = ucfirst($tipo_registro) . " actualizado correctamente."; // Para la recarga de página
            $_SESSION['mensaje_accion_tipo'] = 'success';
            $response = ['success' => true, 'message' => ucfirst($tipo_registro) . " actualizado correctamente."];
        } else {
            // No se afectaron filas, podría ser porque los datos son los mismos o el ID no existe (esto último es menos probable si se cargó el modal)
            $pdo->rollBack(); // O commit si se considera que no cambiar nada es un "éxito" de no error.
            $response = ['success' => true, 'message' => "No se realizaron cambios o el registro no fue encontrado."];
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $response = ['success' => false, 'message' => "Error de base de datos: " . $e->getMessage()];
        error_log("PDOException procesando edición $tipo_registro: " . $e->getMessage());
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $response = ['success' => false, 'message' => $e->getMessage()];
    } finally {
        $pdo = null;
    }

} else {
    $response = ['success' => false, 'message' => 'Método no permitido.'];
}
echo json_encode($response);
exit;
?>