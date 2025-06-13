<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/conexion.php';

// Añado esta línea para depuración en caso de que validar_sesion.php falle.
// Si hay un error, se creará un archivo log.txt en la carpeta /alertas/
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/log.txt');


$db = new database();
$con = $db->conectar();

$nit_farmacia = $_SESSION['nit_farma'] ?? null;
$umbral_stock_bajo = 10; // Considerar bajo stock si es <= 10 unidades

if (!$nit_farmacia) {
    // Es importante tener la cabecera JSON aquí también
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No se pudo identificar la farmacia.']);
    exit;
}

// Consulta SQL con el nombre de la columna corregido
$sql = "SELECT 
            med.nom_medicamento, -- CORREGIDO: de 'nombre_medicamento' a 'nom_medicamento'
            inv.cantidad_actual,
            med.codigo_barras
        FROM inventario_farmacia inv
        JOIN medicamentos med ON inv.id_medicamento = med.id_medicamento
        WHERE inv.nit_farm = :nit_farmacia
        AND inv.cantidad_actual <= :umbral
        ORDER BY inv.cantidad_actual ASC";

$stmt = $con->prepare($sql);
$stmt->bindParam(':nit_farmacia', $nit_farmacia, PDO::PARAM_STR);
$stmt->bindParam(':umbral', $umbral_stock_bajo, PDO::PARAM_INT);
$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Asegurarse de que la respuesta siempre sea JSON
header('Content-Type: application/json');
echo json_encode($resultados);
?>