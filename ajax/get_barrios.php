<?php
require_once('../include/conexion.php');

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
$id_mun = trim($_GET['id_mun'] ?? '');
$barrios = [];

if ($id_mun !== '') {
    $conex = null; $con = null;
    try {
        $conex = new database(); $con = $conex->conectar();
        if ($con) {
            $sql = "SELECT id_barrio, nom_barrio FROM barrio WHERE id_mun = :id_mun ORDER BY nom_barrio ASC";
            $stmt = $con->prepare($sql);
             if ($stmt === false) { error_log("Fallo al preparar la consulta SQL de barrios."); $barrios = []; }
             else {
                $stmt->bindParam(':id_mun', $id_mun, PDO::PARAM_STR);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($result as $row) { $barrios[] = ['id' => $row['id_barrio'], 'nombre' => $row['nom_barrio']]; }
                $stmt = null;
             }
        } else { error_log("Error conexión BD en get_barrios.php"); }
    } catch (PDOException $e) { error_log("PDOException en get_barrios.php (id_mun=$id_mun): " . $e->getMessage()); $barrios = []; }
    catch (Throwable $e) { error_log("Error general en get_barrios.php (id_mun=$id_mun): " . $e->getMessage()); $barrios = []; }
    finally { if ($con) { $con = null; } }
}

echo json_encode($barrios);
?>