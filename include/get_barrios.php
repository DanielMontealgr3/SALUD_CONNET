<?php
// BLOQUE 1: CONFIGURACIÓN INICIAL
// INCLUYE EL ARCHIVO DE CONFIGURACIÓN GLOBAL. ESTO ES CRUCIAL PARA ESTABLECER LA CONEXIÓN A LA BASE DE DATOS ($con).
require_once __DIR__ . '/config.php';

// ESTABLECE LAS CABECERAS HTTP PARA INDICAR QUE LA RESPUESTA ES EN FORMATO JSON.
// ESTO ES FUNDAMENTAL PARA QUE LAS LLAMADAS AJAX DESDE JAVASCRIPT FUNCIONEN CORRECTAMENTE.
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate'); // EVITA QUE EL NAVEGADOR GUARDE EN CACHÉ LA RESPUESTA.
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // FECHA EN EL PASADO PARA FORZAR LA NO-CACHÉ.


// BLOQUE 2: PROCESAMIENTO DE LA SOLICITUD
// OBTIENE Y SANEPA EL 'id_mun' (ID DEL MUNICIPIO) ENVIADO DESDE LA URL.
$id_mun = trim($_GET['id_mun'] ?? '');
// INICIALIZA UN ARRAY VACÍO PARA GUARDAR LOS BARRIOS.
$barrios = [];

// VERIFICA QUE SE HAYA PROPORCIONADO UN ID DE MUNICIPIO.
if ($id_mun !== '') {
    try {
        // PREPARA LA CONSULTA SQL PARA OBTENER LOS BARRIOS CORRESPONDIENTES AL MUNICIPIO.
        // SE USA LA VARIABLE DE CONEXIÓN '$con' QUE FUE CREADA EN 'config.php'.
        $sql = "SELECT id_barrio, nom_barrio FROM barrio WHERE id_mun = :id_mun ORDER BY nom_barrio ASC";
        $stmt = $con->prepare($sql);
        
        // ASOCIA EL PARÁMETRO ':id_mun' CON LA VARIABLE '$id_mun'.
        $stmt->bindParam(':id_mun', $id_mun, PDO::PARAM_INT);
        // EJECUTA LA CONSULTA.
        $stmt->execute();
        
        // OBTIENE TODOS LOS RESULTADOS COMO UN ARRAY ASOCIATIVO.
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // FORMATEA LOS RESULTADOS EN UN NUEVO ARRAY MÁS LIMPIO ('id' y 'nombre') PARA ENVIAR COMO JSON.
        foreach ($result as $row) {
            $barrios[] = ['id' => $row['id_barrio'], 'nombre' => htmlspecialchars($row['nom_barrio'])];
        }
    } catch (PDOException $e) {
        // MANEJO DE ERRORES: SI ALGO FALLA EN LA BASE DE DATOS, REGISTRA EL ERROR EN EL LOG DEL SERVIDOR.
        // ESTO ES IMPORTANTE PARA DEPURACIÓN SIN EXPONER DETALLES AL USUARIO.
        error_log("PDOException en get_barrios.php: " . $e->getMessage());
        // SE ASEGURA DE QUE LA RESPUESTA JSON SEA UN ARRAY VACÍO EN CASO DE ERROR.
        $barrios = []; 
    }
}

// BLOQUE 3: RESPUESTA JSON
// CONVIERTE EL ARRAY PHP '$barrios' A FORMATO JSON Y LO IMPRIME EN LA SALIDA.
// ESTA SERÁ LA RESPUESTA QUE RECIBIRÁ LA LLAMADA AJAX.
echo json_encode($barrios);

// FINALIZA LA EJECUCIÓN DEL SCRIPT.
exit();
?>