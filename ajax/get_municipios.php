<?php
require_once('../include/conexion.php'); 

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

$id_dep = trim($_GET['id_dep'] ?? '');
$municipios = [];

if ($id_dep !== '') {
    $conex = null; $con = null;
    try {
        $conex = new database(); $con = $conex->conectar();
        if ($con) {
            $sql = "SELECT id_mun, nom_mun FROM municipio WHERE id_dep = :id_dep ORDER BY nom_mun ASC";
            $stmt = $con->prepare($sql);
            if ($stmt === false) { error_log("Fallo al preparar la consulta SQL en get_municipios.php."); $municipios = []; }
            else {
                $stmt->bindParam(':id_dep', $id_dep, PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($result as $row) { $municipios[] = ['id' => $row['id_mun'], 'nombre' => $row['nom_mun']]; }
                $stmt = null;
            }
        } else { error_log("Error conexión BD en get_municipios.php"); }
    } catch (PDOException $e) { error_log("PDOException en get_municipios.php (id_dep=$id_dep): " . $e->getMessage()); $municipios = []; }
    catch (Throwable $e) { error_log("Error general en get_municipios.php (id_dep=$id_dep): " . $e->getMessage()); $municipios = []; }
    finally { if ($con) { $con = null; } }
}

echo json_encode($municipios);
?>