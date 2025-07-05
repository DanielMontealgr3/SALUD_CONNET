<?php
/**
 * AJAX Handler para guardar un detalle de diagnóstico en la historia clínica.
 *
 * @version 1.1
 * @author Salud-Connected
 * 
 * Este script valida la sesión y los datos de entrada, inserta un nuevo detalle
 * de diagnóstico en la base de datos y devuelve una respuesta JSON al frontend.
 * Maneja el caso de "No Aplica" y errores de base de datos de forma segura.
 */

// =================================================================
// 1. INICIALIZACIÓN Y SEGURIDAD
// =================================================================

// Incluir archivos esenciales
require_once('../include/validar_sesion.php');
require_once('../include/conexion.php');

// Establecer el tipo de contenido de la respuesta
header('Content-Type: application/json');

// Iniciar sesión y validar rol
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['id_rol'] ?? null) != 4) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Acceso no autorizado.']);
    exit;
}

// Validar que la solicitud sea por método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

// =================================================================
// 2. RECOLECCIÓN Y VALIDACIÓN DE DATOS
// =================================================================

// Usar filter_input para una recolección segura de los datos POST
$id_historia = filter_input(INPUT_POST, 'id_historia', FILTER_VALIDATE_INT);
$id_enferme = filter_input(INPUT_POST, 'id_enferme', FILTER_VALIDATE_INT);
$id_tipo_enfer = filter_input(INPUT_POST, 'id_tipo_enfer', FILTER_VALIDATE_INT);

// Validar que el ID de historia sea válido
if (empty($id_historia)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'ID de Historia Clínica no válido o no proporcionado.']);
    exit;
}

// Si el usuario no seleccionó una enfermedad (dejó "No Aplica", que tiene valor 0),
// no se realiza ninguna acción en la base de datos.
if (empty($id_enferme)) {
    echo json_encode(['status' => 'no_action', 'message' => 'No se seleccionó un diagnóstico para guardar.']);
    exit;
}

// =================================================================
// 3. OPERACIÓN DE BASE DE DATOS
// =================================================================

try {
    // Conexión a la base de datos
    $db = new Database();
    $pdo = $db->conectar();

    // Consulta de inserción preparada para máxima seguridad
    $sql_insert = "INSERT INTO detalles_enfermedades_tipo_enfermedades (id_historia, id_enferme, id_tipo_enfer) VALUES (?, ?, ?)";
    $stmt_insert = $pdo->prepare($sql_insert);
    
    // Ejecutar la inserción
    $stmt_insert->execute([$id_historia, $id_enferme, $id_tipo_enfer]);
    
    // Obtener el ID del nuevo registro insertado
    $new_detail_id = $pdo->lastInsertId();

    // Consulta para obtener los nombres y devolverlos al frontend para la actualización de la UI
    $sql_select_names = "
        SELECT e.nom_enfer, te.tipo_enfermer
        FROM enfermedades e
        LEFT JOIN tipo_enfermedades te ON e.id_tipo_enfer = te.id_tipo_enfer
        WHERE e.id_enferme = ?
    ";
    $stmt_select_names = $pdo->prepare($sql_select_names);
    $stmt_select_names->execute([$id_enferme]);
    $nombres = $stmt_select_names->fetch(PDO::FETCH_ASSOC);

    // =================================================================
    // 4. RESPUESTA DE ÉXITO
    // =================================================================

    // Enviar una respuesta JSON exitosa con los datos del nuevo detalle
    http_response_code(201); // Created
    echo json_encode([
        'status' => 'success',
        'message' => 'Diagnóstico añadido correctamente.',
        'newDetail' => [
            'id_detalle_enfer' => $new_detail_id,
            'nom_enfer'        => $nombres['nom_enfer'] ?? 'N/D',
            'tipo_enfermer'    => $nombres['tipo_enfermer'] ?? 'N/D'
        ]
    ]);

} catch (PDOException $e) {
    // =================================================================
    // 5. MANEJO DE ERRORES DE BASE DE DATOS
    // =================================================================
    
    // Si el error es por una entrada duplicada (violación de clave única)
    if ($e->getCode() == 23000) {
        http_response_code(409); // Conflict
        echo json_encode(['status' => 'error', 'message' => 'Este diagnóstico ya ha sido añadido a esta consulta.']);
    } else {
        // Para cualquier otro error de base de datos
        http_response_code(500); // Internal Server Error
        // Registrar el error detallado en el log del servidor para el desarrollador
        error_log("Error en guardar_diagnostico.php: " . $e->getMessage());
        // Enviar un mensaje genérico al usuario
        echo json_encode(['status' => 'error', 'message' => 'Ocurrió un error inesperado en el servidor.']);
    }
}
?>