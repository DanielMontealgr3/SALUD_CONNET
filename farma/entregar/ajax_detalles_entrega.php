<?php
// =========================================================================
// ==         AJAX_DETALLES_ENTREGA.PHP - VERSIÓN CORREGIDA Y EXPLICADA     ==
// =========================================================================

// --- BLOQUE 1: CONFIGURACIÓN CENTRAL Y SEGURIDAD ---
// Se incluye el archivo de configuración central. Este paso es CRUCIAL. Se encarga de:
// 1. Iniciar las sesiones de forma segura (necesario para `validar_sesion`).
// 2. Definir las constantes de ruta (ROOT_PATH, BASE_URL).
// 3. Crear la conexión a la base de datos ($con), que se adapta a local o producción.
require_once __DIR__ . '/../../include/config.php';

// Se incluye el script de validación de sesión. Verifica si el usuario está logueado
// y tiene los permisos necesarios para acceder a esta información.
require_once ROOT_PATH . '/include/validar_sesion.php';

// --- BLOQUE 2: DEFINICIÓN DEL TIPO DE RESPUESTA ---
// Se establece la cabecera HTTP para indicar que la respuesta de este script será en formato JSON.
// Esto es esencial para que el código JavaScript que realiza la llamada AJAX pueda
// interpretar correctamente los datos recibidos.
header('Content-Type: application/json');

// --- BLOQUE 3: VALIDACIÓN DE PARÁMETROS DE ENTRADA ---
// Se verifica que se haya recibido un ID de entrega a través de la URL (método GET) y
// que este sea un valor numérico.
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Si no se cumple la condición, se envía una respuesta JSON de error y se detiene el script.
    echo json_encode(['success' => false, 'message' => 'No se proporcionó un ID de entrega válido.']);
    exit;
}

// Se convierte el ID a un número entero para mayor seguridad.
$id_entrega = (int)$_GET['id'];

// --- BLOQUE 4: CONSULTA A LA BASE DE DATOS Y PROCESAMIENTO ---
// Se utiliza un bloque `try...catch` para manejar cualquier posible error de base de datos
// de forma controlada, evitando exponer información sensible.
try {
    // IMPORTANTE: Ya no se crea una nueva conexión a la BD. Se usa la variable global `$con`
    // que fue creada y configurada en `config.php`.

    // Consulta Principal: Obtiene los detalles de una entrega específica.
    // Se unen múltiples tablas para recopilar toda la información necesaria:
    // farmaceuta, paciente, medicamento y la fecha de la entrega.
    // NOTA: La consulta original parece compleja. Asegúrate de que los JOIN y el GROUP BY
    // devuelven exactamente los datos que esperas.
    $sql_entrega = "
        SELECT 
            em.id_entrega, em.cantidad_entregada, em.observaciones,
            tem.fecha_entreg AS fecha_entrega,
            far.nom_usu AS nombre_farmaceuta, 
            pac.nom_usu AS nombre_paciente, 
            pac.doc_usu AS doc_paciente,
            med.nom_medicamento,
            dhc.id_detalle AS id_detalle_histo
        FROM entrega_medicamentos em
        JOIN usuarios far ON em.doc_farmaceuta = far.doc_usu
        JOIN detalles_histo_clini dhc ON em.id_detalle_histo = dhc.id_detalle
        JOIN medicamentos med ON dhc.id_medicam = med.id_medicamento
        JOIN historia_clinica hc ON dhc.id_historia = hc.id_historia
        JOIN citas c ON hc.id_cita = c.id_cita
        JOIN usuarios pac ON c.doc_pac = pac.doc_usu
        LEFT JOIN turno_ent_medic tem ON em.id_entrega = tem.id_entrega AND tem.id_est = 9
        WHERE em.id_entrega = :id_entrega
        LIMIT 1
    "; // Se quita GROUP BY y se usa LIMIT 1, que es más eficiente para buscar por clave primaria.
    
    $stmt = $con->prepare($sql_entrega);
    $stmt->execute([':id_entrega' => $id_entrega]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se verifica si la consulta principal devolvió un resultado.
    if ($data) {
        // Si se encontró la entrega, se construye el array de respuesta inicial.
        // Se usa `htmlspecialchars` para escapar los datos y prevenir ataques XSS.
        $response = [
            'success' => true,
            'data' => [
                'entrega' => [
                    'nombre_farmaceuta' => htmlspecialchars($data['nombre_farmaceuta']),
                    'fecha_entrega' => htmlspecialchars(date('d/m/Y H:i', strtotime($data['fecha_entrega']))),
                    'cantidad_entregada' => htmlspecialchars($data['cantidad_entregada']),
                    'observaciones' => htmlspecialchars($data['observaciones'])
                ],
                'paciente' => [
                    'nombre_paciente' => htmlspecialchars($data['nombre_paciente']),
                    'doc_paciente' => htmlspecialchars($data['doc_paciente'])
                ],
                'medicamento' => [
                    'nom_medicamento' => htmlspecialchars($data['nom_medicamento'])
                ],
                'pendiente' => null // Se inicializa como nulo.
            ]
        ];

        // Consulta Secundaria: Busca si existe una entrega pendiente asociada.
        // Se usa el `id_detalle_histo` obtenido de la primera consulta.
        $sql_pendiente = "SELECT ep.cantidad_pendiente, ep.fecha_generacion FROM entrega_pendiente ep WHERE ep.id_detalle_histo = :id_detalle_histo LIMIT 1";
        $stmt_p = $con->prepare($sql_pendiente);
        $stmt_p->execute([':id_detalle_histo' => $data['id_detalle_histo']]);
        $data_pendiente = $stmt_p->fetch(PDO::FETCH_ASSOC);

        // Si se encontró una entrega pendiente, se añade la información al array de respuesta.
        if ($data_pendiente) {
            $response['data']['pendiente'] = [
                'cantidad_pendiente' => htmlspecialchars($data_pendiente['cantidad_pendiente']),
                'fecha_generacion' => htmlspecialchars(date('d/m/Y', strtotime($data_pendiente['fecha_generacion'])))
            ];
        }

    } else {
        // Si la consulta principal no encontró la entrega, se prepara una respuesta de error.
        $response = ['success' => false, 'message' => 'No se encontró la entrega con el ID proporcionado.'];
    }

} catch (PDOException $e) {
    // Si ocurre un error en cualquiera de las operaciones de base de datos, se captura.
    // Se registra el error en el servidor para depuración y se envía un mensaje genérico al cliente.
    error_log("Error en ajax_detalles_entrega.php: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'Ocurrió un error al consultar la base de datos.'];
}

// --- BLOQUE 5: ENVÍO DE LA RESPUESTA FINAL ---
// Se codifica el array $response a formato JSON y se imprime en la salida.
// Este es el resultado final que recibirá el código JavaScript.
echo json_encode($response);
exit;