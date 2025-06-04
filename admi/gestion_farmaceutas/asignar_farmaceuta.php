<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) { session_start(); }

require_once '../include/validar_sesion.php';
require_once '../include/inactividad.php';
require_once '../include/conexion.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 1 ) {
    header('Location: ../inicio_sesion.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_guardar_asignacion_farmaceuta'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Error desconocido al guardar.'];

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['message'] = 'Error de validación de seguridad (CSRF). Por favor, recargue la página e intente de nuevo.';
        echo json_encode($response);
        exit;
    }

    $doc_farma = trim($_POST['doc_farma_asignar'] ?? '');
    $nit_farma_asignar = trim($_POST['nit_farma_asignar_modal_select'] ?? '');
    $id_estado_asignacion = trim($_POST['id_estado_asignacion_farmaceuta_modal_select'] ?? '');

    if (empty($doc_farma) || empty($nit_farma_asignar) || empty($id_estado_asignacion)) {
        $response['message'] = "Todos los campos son obligatorios: Farmaceuta, Farmacia y Estado.";
        echo json_encode($response);
        exit;
    }
    
    if (!is_numeric($id_estado_asignacion) || !in_array($id_estado_asignacion, [1, 2])) {
        $response['message'] = "El estado de asignación no es válido.";
        echo json_encode($response);
        exit;
    }

    $db = new Database();
    $con = $db->conectar();

    if (!$con) {
        $response['message'] = "Error de conexión a la base de datos.";
        echo json_encode($response);
        exit;
    }

    try {
        $con->beginTransaction();

        if ($id_estado_asignacion == 1) {
            $sql_inactivar_otras = "UPDATE asignacion_farmaceuta 
                                    SET id_estado = 2 
                                    WHERE doc_farma = :doc_farma 
                                    AND id_estado = 1 
                                    AND nit_farma != :nit_farma_actual_asignacion";
            $stmt_inactivar = $con->prepare($sql_inactivar_otras);
            $stmt_inactivar->bindParam(':doc_farma', $doc_farma, PDO::PARAM_STR);
            $stmt_inactivar->bindParam(':nit_farma_actual_asignacion', $nit_farma_asignar, PDO::PARAM_STR);
            $stmt_inactivar->execute();
        }
        
        $sql_check_exist = "SELECT id_asignacion, id_estado FROM asignacion_farmaceuta WHERE doc_farma = :doc_farma AND nit_farma = :nit_farma";
        $stmt_check = $con->prepare($sql_check_exist);
        $stmt_check->bindParam(':doc_farma', $doc_farma, PDO::PARAM_STR);
        $stmt_check->bindParam(':nit_farma', $nit_farma_asignar, PDO::PARAM_STR);
        $stmt_check->execute();
        $asignacion_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($asignacion_existente) {
            if ($asignacion_existente['id_estado'] != $id_estado_asignacion) {
                $sql_update = "UPDATE asignacion_farmaceuta SET id_estado = :id_estado WHERE id_asignacion = :id_asignacion";
                $stmt_update = $con->prepare($sql_update);
                $stmt_update->bindParam(':id_estado', $id_estado_asignacion, PDO::PARAM_INT);
                $stmt_update->bindParam(':id_asignacion', $asignacion_existente['id_asignacion'], PDO::PARAM_INT);
                $stmt_update->execute();
                $response = ['success' => true, 'message' => 'Asignación de farmacia actualizada correctamente.'];
            } else {
                 $response = ['success' => true, 'message' => 'La asignación ya existe con el mismo estado. No se realizaron cambios.'];
            }
        } else {
            $sql_insert = "INSERT INTO asignacion_farmaceuta (doc_farma, nit_farma, id_estado) VALUES (:doc_farma, :nit_farma, :id_estado)";
            $stmt_insert = $con->prepare($sql_insert);
            $stmt_insert->bindParam(':doc_farma', $doc_farma, PDO::PARAM_STR);
            $stmt_insert->bindParam(':nit_farma', $nit_farma_asignar, PDO::PARAM_STR);
            $stmt_insert->bindParam(':id_estado', $id_estado_asignacion, PDO::PARAM_INT);
            $stmt_insert->execute();
            
            if ($stmt_insert->rowCount() > 0) {
                $response = ['success' => true, 'message' => 'Farmaceuta asignado a la farmacia exitosamente.'];
            } else {
                $response['message'] = 'No se pudo registrar la nueva asignación.';
                if ($con->inTransaction()) $con->rollBack();
            }
        }
        
        if ($response['success'] && $con->inTransaction()) {
            $con->commit();
        }

    } catch (PDOException $e) {
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        $response['message'] = "Error en la operación de base de datos: " . $e->getMessage();
        error_log("PDOException en asignar_farmaceuta.php (POST): " . $e->getMessage());
    } finally {
        $con = null;
    }
    echo json_encode($response);
    exit;
}

$doc_farma_page = $_GET['doc_farma'] ?? '';
$nom_farma_page = $_GET['nom_farma'] ?? 'Farmaceuta no especificado';
$return_to_page = $_GET['return_to'] ?? 'lista_farmaceutas.php';


if (empty($doc_farma_page)) {
    $_SESSION['mensaje_accion'] = 'No se especificó un farmaceuta para la asignación.';
    $_SESSION['mensaje_accion_tipo'] = 'danger';
    header('Location: ' . htmlspecialchars($return_to_page));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Farmacia a Farmaceuta - SaludConnect</title>
    <link rel="icon" type="image/png" href="../img/loguito.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/styles_admin.css">
    <style>
        body { background-color: #e9ecef; }
        #modalContenedorAsignacionFarmaceuta .modal-content.modal-content-asignacion-styled { 
            background-color: #f0f2f5; 
            border: 2px solid #87CEEB;
            border-radius: .5rem; 
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        }
        #modalContenedorAsignacionFarmaceuta .modal-dialog { 
            max-width: 750px;
        }
    </style>
</head>
<body class="d-flex flex-column">
    <?php include '../include/menu.php'; ?>

    <main id="contenido-principal" class="flex-grow-1 d-flex align-items-center justify-content-center visually-hidden">
        <p>Cargando...</p>
    </main>

    <div class="modal fade" id="modalContenedorAsignacionFarmaceuta" tabindex="-1" aria-labelledby="modalContenedorAsignacionFarmaceutaLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content modal-content-asignacion-styled" id="modalDinamicoContentFarmaceuta">
                <div class="modal-body text-center p-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-3">Cargando formulario de asignación...</p>
                </div>
            </div>
        </div>
    </div>

    <?php include '../include/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/asignacion_farmaceuta.js?v=<?php echo time(); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalContenedorElement = document.getElementById('modalContenedorAsignacionFarmaceuta');
            const modalDinamicoContent = document.getElementById('modalDinamicoContentFarmaceuta');
            const bootstrapModal = new bootstrap.Modal(modalContenedorElement);

            const docFarma = '<?php echo htmlspecialchars($doc_farma_page, ENT_QUOTES, 'UTF-8'); ?>';
            const nomFarma = '<?php echo htmlspecialchars($nom_farma_page, ENT_QUOTES, 'UTF-8'); ?>';
            const returnTo = '<?php echo htmlspecialchars($return_to_page, ENT_QUOTES, 'UTF-8'); ?>';


            function cargarYMostrarModalFarmaceuta() {
                fetch(`modal_asignacion_farmaceuta.php?doc_farma=${encodeURIComponent(docFarma)}&nom_farma=${encodeURIComponent(nomFarma)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Error HTTP ${response.status} al cargar el modal.`);
                        }
                        return response.text();
                    })
                    .then(html => {
                        modalDinamicoContent.innerHTML = html;
                        bootstrapModal.show();
                        if (typeof inicializarLogicaFormularioModalFarmaceuta === "function") {
                            inicializarLogicaFormularioModalFarmaceuta();
                        } else {
                            console.error("La función inicializarLogicaFormularioModalFarmaceuta no está definida en asignacion_farmaceuta.js");
                        }
                    })
                    .catch(error => {
                        modalDinamicoContent.innerHTML = `<div class="modal-body p-4"><div class="alert alert-danger">Error al cargar el formulario de asignación: ${error.message}. Por favor, intente recargar la página.</div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="window.location.href='${returnTo}'">Volver</button></div></div>`;
                        bootstrapModal.show();
                        console.error('Error cargando modal de asignación de farmaceuta:', error);
                    });
            }
            
            modalContenedorElement.addEventListener('hidden.bs.modal', function () {
                const successMessageElement = modalDinamicoContent.querySelector('.alert-success');
                if (successMessageElement) {
                    window.location.href = returnTo + (returnTo.includes('?') ? '&' : '?') + 'asignacion_exitosa_farmaceuta=1';
                } else {
                     window.location.href = returnTo;
                }
                modalDinamicoContent.innerHTML = '<div class="modal-body text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
            });

            cargarYMostrarModalFarmaceuta();
        });
    </script>
</body>
</html>