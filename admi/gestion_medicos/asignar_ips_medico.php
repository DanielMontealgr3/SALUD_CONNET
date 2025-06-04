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

$json_response_for_ajax = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion_guardar_asignacion'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Error desconocido al guardar.'];

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['message'] = 'Error de validación de seguridad (CSRF). Por favor, recargue la página e intente de nuevo.';
        echo json_encode($response);
        exit;
    }

    $doc_medico = trim($_POST['doc_medico_asignar'] ?? '');
    $nit_ips = trim($_POST['nit_ips_asignar'] ?? '');
    $id_estado_asignacion = trim($_POST['id_estado_asignacion'] ?? '');

    if (empty($doc_medico) || empty($nit_ips) || empty($id_estado_asignacion)) {
        $response['message'] = "Todos los campos son obligatorios: Médico, IPS y Estado.";
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

        if ($id_estado_asignacion == 1) { // Si la nueva asignación es para ACTIVAR
            // Inactivar TODAS las otras asignaciones ACTIVAS de este médico a CUALQUIER OTRA IPS
            $sql_inactivar_otras = "UPDATE asignacion_medico 
                                    SET id_estado = 2 
                                    WHERE doc_medico = :doc_medico 
                                    AND id_estado = 1 
                                    AND nit_ips != :nit_ips_actual_asignacion"; // Solo inactivar las de OTRAS IPS
            $stmt_inactivar = $con->prepare($sql_inactivar_otras);
            $stmt_inactivar->bindParam(':doc_medico', $doc_medico, PDO::PARAM_STR);
            $stmt_inactivar->bindParam(':nit_ips_actual_asignacion', $nit_ips, PDO::PARAM_STR);
            $stmt_inactivar->execute();
        }
        
        $sql_check_exist = "SELECT id_asignacion, id_estado FROM asignacion_medico WHERE doc_medico = :doc_medico AND nit_ips = :nit_ips";
        $stmt_check = $con->prepare($sql_check_exist);
        $stmt_check->bindParam(':doc_medico', $doc_medico, PDO::PARAM_STR);
        $stmt_check->bindParam(':nit_ips', $nit_ips, PDO::PARAM_STR);
        $stmt_check->execute();
        $asignacion_existente = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($asignacion_existente) {
            if ($asignacion_existente['id_estado'] != $id_estado_asignacion) {
                $sql_update = "UPDATE asignacion_medico SET id_estado = :id_estado WHERE id_asignacion = :id_asignacion";
                $stmt_update = $con->prepare($sql_update);
                $stmt_update->bindParam(':id_estado', $id_estado_asignacion, PDO::PARAM_INT);
                $stmt_update->bindParam(':id_asignacion', $asignacion_existente['id_asignacion'], PDO::PARAM_INT);
                $stmt_update->execute();
                $response = ['success' => true, 'message' => 'Asignación actualizada correctamente.'];
            } else {
                 $response = ['success' => true, 'message' => 'La asignación ya existe con el mismo estado. No se realizaron cambios.'];
            }
        } else {
            $sql_insert = "INSERT INTO asignacion_medico (doc_medico, nit_ips, id_estado) VALUES (:doc_medico, :nit_ips, :id_estado)";
            $stmt_insert = $con->prepare($sql_insert);
            $stmt_insert->bindParam(':doc_medico', $doc_medico, PDO::PARAM_STR);
            $stmt_insert->bindParam(':nit_ips', $nit_ips, PDO::PARAM_STR);
            $stmt_insert->bindParam(':id_estado', $id_estado_asignacion, PDO::PARAM_INT);
            $stmt_insert->execute();
            
            if ($stmt_insert->rowCount() > 0) {
                $response = ['success' => true, 'message' => 'Médico asignado a la IPS exitosamente.'];
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
        error_log("PDOException en asignar_ips_medico.php (POST): " . $e->getMessage());
    } finally {
        $con = null;
    }
    echo json_encode($response);
    exit;
}

$doc_medico_page = $_GET['doc_medico'] ?? '';
$nom_medico_page = $_GET['nom_medico'] ?? 'Médico no especificado';

if (empty($doc_medico_page)) {
    $_SESSION['mensaje_accion'] = 'No se especificó un médico para la asignación.';
    $_SESSION['mensaje_accion_tipo'] = 'danger';
    header('Location: lista_medicos.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar IPS a Médico - SaludConnect</title>
    <link rel="icon" type="image/png" href="../img/loguito.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/styles_admin.css">
    <style>
        body { background-color: #e9ecef; }
        #modalContenedorAsignacion .modal-content.modal-content-asignacion-styled { 
            background-color: #f0f2f5; 
            border: 3px solid #87CEEB; 
            border-radius: .5rem; 
        }
        #modalContenedorAsignacion .modal-dialog { 
            max-width: 750px;
        }
    </style>
</head>
<body class="d-flex flex-column">
    <?php include '../include/menu.php'; ?>

    <main id="contenido-principal" class="flex-grow-1 d-flex align-items-center justify-content-center visually-hidden">
        <p>Cargando...</p>
    </main>

    <div class="modal fade" id="modalContenedorAsignacion" tabindex="-1" aria-labelledby="modalContenedorAsignacionLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content modal-content-asignacion-styled" id="modalDinamicoContent">
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalContenedorElement = document.getElementById('modalContenedorAsignacion');
            const modalDinamicoContent = document.getElementById('modalDinamicoContent');
            const bootstrapModal = new bootstrap.Modal(modalContenedorElement);

            const docMedico = '<?php echo htmlspecialchars($doc_medico_page, ENT_QUOTES, 'UTF-8'); ?>';
            const nomMedico = '<?php echo htmlspecialchars($nom_medico_page, ENT_QUOTES, 'UTF-8'); ?>';

            function cargarYMostrarModal() {
                fetch(`modal_asignacion.php?doc_medico=${encodeURIComponent(docMedico)}&nom_medico=${encodeURIComponent(nomMedico)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Error HTTP ${response.status} al cargar el modal.`);
                        }
                        return response.text();
                    })
                    .then(html => {
                        modalDinamicoContent.innerHTML = html;
                        bootstrapModal.show();
                        inicializarLogicaFormularioModal();
                    })
                    .catch(error => {
                        modalDinamicoContent.innerHTML = `<div class="modal-body p-4"><div class="alert alert-danger">Error al cargar el formulario de asignación: ${error.message}. Por favor, intente recargar la página.</div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="window.location.href='lista_medicos.php'">Volver a la lista</button></div></div>`;
                        bootstrapModal.show();
                        console.error('Error cargando modal:', error);
                    });
            }

            function inicializarLogicaFormularioModal() {
                const formAsignarIPSModalInterno = document.getElementById('formAsignarIPSModalInterno');
                const btnGuardarAsignacionModalInterno = document.getElementById('btnGuardarAsignacionModalInterno');
                const modalAsignacionMessageInterno = document.getElementById('modalAsignacionMessageInterno');
                const modalAsignacionGlobalErrorInterno = document.getElementById('modalAsignacionGlobalErrorInterno');

                if (!formAsignarIPSModalInterno || !btnGuardarAsignacionModalInterno) {
                    if(modalAsignacionGlobalErrorInterno) modalAsignacionGlobalErrorInterno.innerHTML = '<div class="alert alert-danger">Error interno: No se pudieron inicializar los controles del formulario.</div>';
                    return;
                }

                formAsignarIPSModalInterno.addEventListener('submit', function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    let isValid = true;
                    if (modalAsignacionMessageInterno) modalAsignacionMessageInterno.innerHTML = '';
                    if (modalAsignacionGlobalErrorInterno) modalAsignacionGlobalErrorInterno.innerHTML = '';
                    
                    const nitIpsSelect = document.getElementById('nit_ips_asignar_modal');
                    const estadoSelect = document.getElementById('id_estado_asignacion_modal');

                    if (nitIpsSelect && !nitIpsSelect.value) {
                        nitIpsSelect.classList.add('is-invalid');
                        const errorDivNit = document.getElementById('error-nit_ips_asignar_modal');
                        if (errorDivNit) errorDivNit.style.display = 'block';
                        isValid = false;
                    } else if (nitIpsSelect) {
                        nitIpsSelect.classList.remove('is-invalid');
                        const errorDivNit = document.getElementById('error-nit_ips_asignar_modal');
                        if (errorDivNit) errorDivNit.style.display = 'none';
                    }

                    if (estadoSelect && !estadoSelect.value) {
                        estadoSelect.classList.add('is-invalid');
                        const errorDivEstado = document.getElementById('error-id_estado_asignacion_modal');
                        if (errorDivEstado) errorDivEstado.style.display = 'block';
                        isValid = false;
                    } else if (estadoSelect) {
                        estadoSelect.classList.remove('is-invalid');
                        const errorDivEstado = document.getElementById('error-id_estado_asignacion_modal');
                        if (errorDivEstado) errorDivEstado.style.display = 'none';
                    }
                    
                    if (!isValid) return;

                    btnGuardarAsignacionModalInterno.disabled = true;
                    btnGuardarAsignacionModalInterno.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

                    const formData = new FormData(formAsignarIPSModalInterno);
                    
                    fetch(window.location.href, { 
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (modalAsignacionMessageInterno) modalAsignacionMessageInterno.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                            setTimeout(() => {
                                bootstrapModal.hide();
                                window.location.href = 'lista_medicos.php?asignacion_exitosa=1'; 
                            }, 2000);
                        } else {
                             if (modalAsignacionMessageInterno) modalAsignacionMessageInterno.innerHTML = `<div class="alert alert-danger">${data.message || 'Error desconocido al guardar.'}</div>`;
                        }
                    })
                    .catch(error => {
                        if (modalAsignacionGlobalErrorInterno) modalAsignacionGlobalErrorInterno.innerHTML = `<div class="alert alert-danger">Error de conexión o del servidor: ${error}. Intente de nuevo.</div>`;
                        console.error('Error en fetch (guardado):', error);
                    })
                    .finally(() => {
                        btnGuardarAsignacionModalInterno.disabled = false;
                        btnGuardarAsignacionModalInterno.innerHTML = '<i class="bi bi-building-add me-1"></i>Guardar Asignación';
                    });
                });
            }
            
            modalContenedorElement.addEventListener('hidden.bs.modal', function () {
                const successMessageElement = modalDinamicoContent.querySelector('.alert-success');
                if (!successMessageElement) {
                    window.location.href = 'lista_medicos.php';
                }
                modalDinamicoContent.innerHTML = '<div class="modal-body text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';
            });

            cargarYMostrarModal();
        });
    </script>
</body>
</html>