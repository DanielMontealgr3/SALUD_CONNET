<?php
// --- BLOQUE 1: CONFIGURACIÓN Y SEGURIDAD ---
// Se incluyen los archivos de configuración y validación de sesión.
header('Content-Type: application/json');
require_once __DIR__ . '/../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';

// Respuesta por defecto en caso de error.
$response = ['success' => false, 'message' => 'Petición inválida.'];

// Se verifica que la petición sea de tipo POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- BLOQUE 2: VALIDACIÓN Y SANITIZACIÓN DE DATOS ---
    // Se recogen y limpian los datos del formulario para prevenir XSS y otros ataques.
    $id_cita = filter_input(INPUT_POST, 'id_cita', FILTER_VALIDATE_INT);
    $motivo_de_cons = filter_input(INPUT_POST, 'motivo_de_cons', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $presion = filter_input(INPUT_POST, 'presion', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $saturacion = filter_input(INPUT_POST, 'saturacion', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $peso = filter_input(INPUT_POST, 'peso', FILTER_VALIDATE_FLOAT);
    $estatura = filter_input(INPUT_POST, 'estatura', FILTER_VALIDATE_FLOAT);
    $observaciones = filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    // Se verifica que los datos obligatorios no estén vacíos.
    if (!$id_cita || empty($motivo_de_cons) || empty($presion) || empty($saturacion) || !$peso || !$estatura || empty($observaciones)) {
        $response['message'] = 'Todos los campos son obligatorios.';
        echo json_encode($response);
        exit;
    }
    
    // ID del estado "En Proceso"
    define('ID_ESTADO_EN_PROCESO', 11);

    // --- BLOQUE 3: TRANSACCIÓN DE BASE DE DATOS ---
    // Se utiliza una transacción para asegurar que ambas operaciones (INSERT y UPDATE) 
    // se completen con éxito o ninguna se aplique.
    try {
        $con->beginTransaction();

        // 1. Crear el registro en historia_clinica.
        // Los campos doc_pac y doc_med no se intentan guardar aquí, como se corrigió.
        $sql_historia = "INSERT INTO historia_clinica (id_cita, motivo_de_cons, presion, saturacion, peso, estatura, observaciones) 
                         VALUES (:id_cita, :motivo, :presion, :saturacion, :peso, :estatura, :obs)";
        $stmt_historia = $con->prepare($sql_historia);
        $stmt_historia->execute([
            ':id_cita' => $id_cita,
            ':motivo' => $motivo_de_cons,
            ':presion' => $presion,
            ':saturacion' => $saturacion,
            ':peso' => $peso,
            ':estatura' => $estatura,
            ':obs' => $observaciones
        ]);
        
        // Se obtiene el ID de la historia recién creada para la redirección.
        $id_historia_creada = $con->lastInsertId();

        // 2. Actualizar el estado de la cita a "En Proceso".
        $sql_cita = "UPDATE citas SET id_est = :id_est WHERE id_cita = :id_cita";
        $stmt_cita = $con->prepare($sql_cita);
        $stmt_cita->execute([
            ':id_est' => ID_ESTADO_EN_PROCESO,
            ':id_cita' => $id_cita
        ]);

        // 3. Si todo fue bien, se confirman los cambios.
        $con->commit();
        
        // --- BLOQUE 4: RESPUESTA EXITOSA ---
        // Se construye la URL de redirección y se envía la respuesta JSON al frontend.
        $redirect_url = BASE_URL . '/medi/deta_historia_clini.php?id=' . $id_historia_creada;
        $response = [
            'success' => true,
            'message' => 'Historia clínica guardada con éxito.',
            'redirect_url' => $redirect_url
        ];

    } catch (PDOException $e) {
        // En caso de error, se revierten todos los cambios.
        $con->rollBack();
        $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
        // Para depuración: error_log($e->getMessage());
    }
}

// Se imprime la respuesta final en formato JSON.
echo json_encode($response);
exit;
?>