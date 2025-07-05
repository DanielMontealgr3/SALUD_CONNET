<?php
// --- BLOQUE 1: CONFIGURACIÓN INICIAL Y SEGURIDAD ---
// Se incluye el archivo de configuración central. La ruta __DIR__ . '/../../' sube dos niveles
// desde 'farma/inventario/' para encontrar la carpeta 'include/'.
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

// --- BLOQUE 2: VERIFICACIÓN DE ROL Y OBTENCIÓN DE DATOS DE FARMACIA ---
// La conexión $con ya está disponible desde el archivo config.php
$nombre_farmacia_asignada = "No Asignada";
$nit_farmacia_actual = $_SESSION['nit_farma'] ?? '';
if (empty($nit_farmacia_actual)) {
    // Si no hay farmacia en sesión, redirigir al inicio para que se asigne una.
    header('Location: ' . BASE_URL . '/farma/inicio.php');
    exit;
}

if ($nit_farmacia_actual) {
    try {
        $stmt_nombre = $con->prepare("SELECT nom_farm FROM farmacias WHERE nit_farm = ?");
        $stmt_nombre->execute([$nit_farmacia_actual]);
        $nombre_farmacia_asignada = $stmt_nombre->fetchColumn();
        if (!$nombre_farmacia_asignada) {
            $nombre_farmacia_asignada = "Farmacia no encontrada";
        }
        // Guardar el nombre en sesión para no tener que consultarlo en cada página
        $_SESSION['nombre_farmacia_actual'] = $nombre_farmacia_asignada; 
    } catch(PDOException $e) {
        error_log("Error al obtener nombre de farmacia: " . $e->getMessage());
        // Se mantiene el nombre por defecto 'No Asignada'
    }
}
$pageTitle = "Registrar Entrada de Inventario";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- --- BLOQUE 3: METADATOS Y ENLACES CSS/JS DEL HEAD --- -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
    <!-- Rutas a recursos corregidas con BASE_URL -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/loguito.png">
    <!-- Se incluye el menú usando ROOT_PATH para garantizar una ruta absoluta en el servidor -->
    <?php require_once ROOT_PATH . '/include/menu.php'; ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <main id="contenido-principal" class="centrado">
        <!-- --- BLOQUE 4: CONTENIDO HTML PRINCIPAL --- -->
        <div class="vista-datos-container compact-form">
            <div class="d-flex justify-content-between align-items-center mb-3 header-form-responsive">
                <div>
                    <h3 class="titulo-lista-tabla mb-0">Registrar Entrada</h3>
                    <small class="text-muted">Farmacia: <strong><?php echo htmlspecialchars($nombre_farmacia_asignada); ?></strong></small>
                </div>
                <!-- La ruta de retorno también usa BASE_URL para ser robusta -->
                <a href="<?php echo BASE_URL; ?>/farma/inventario/inventario.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
            </div>
            
            <form id="formBusquedaInicial" class="mb-4">
                <label for="codigo_barras_scan" class="form-label"><i class="bi bi-upc-scan me-2"></i>Escanear Código de Barras:</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="codigo_barras_scan" placeholder="Esperando código..." autofocus autocomplete="off">
                    <button class="btn btn-primary" type="submit" id="btnBuscarCodigo"><i class="bi bi-search"></i></button>
                </div>
                <div id="busqueda-error" class="text-danger mt-2"></div>
            </form>
            
            <div id="area-registro" class="d-none">
                <div class="row">
                    <div class="col-md-5">
                        <div id="info-medicamento-col">
                            <h5 id="nombre-medicamento" class="mb-0"></h5>
                            <p class="stock-label mb-0">Stock Actual:</p>
                            <p class="stock-value" id="stock-actual"></p>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <form id="formRegistrarEntrada">
                            <input type="hidden" id="id_medicamento_encontrado" name="id_medicamento">
                            <input type="hidden" name="notas" value="Ingreso de productos por pedido a proveedor.">
                            
                            <div class="mb-3">
                                <label for="cantidad_entrada" class="form-label"><strong>Cantidad a Ingresar:</strong></label>
                                <input type="text" inputmode="numeric" class="form-control" id="cantidad_entrada" name="cantidad" required>
                                <div class="invalid-feedback">Solo se permiten números enteros positivos.</div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label for="lote" class="form-label">Lote:</label>
                                    <input type="text" class="form-control" id="lote" name="lote" required>
                                    <div class="invalid-feedback">Debe tener al menos 5 caracteres.</div>
                                </div>
                                <div class="col-sm-6">
                                    <label for="fecha_vencimiento" class="form-label">Vencimiento:</label>
                                    <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" required>
                                    <div class="invalid-feedback">La fecha debe ser de al menos 3 meses en el futuro.</div>
                                </div>
                            </div>
                            <div class="text-end mt-4">
                                <button type="submit" id="btnGuardarEntrada" class="btn btn-success" disabled><i class="bi bi-check-circle-fill me-2"></i>Registrar Entrada</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div id="area-placeholder" class="text-center text-muted p-5">
                <i class="bi bi-search fs-1"></i>
                <p class="mt-2">Los detalles del medicamento aparecerán aquí.</p>
            </div>
        </div>
    </main>
    
    <!-- --- BLOQUE 5: SCRIPTS Y FOOTER --- -->
    <!-- Se incluye el footer usando ROOT_PATH -->
    <?php require_once ROOT_PATH . '/include/footer.php'; ?>
    <!-- Se enlaza el script JS usando BASE_URL para que la ruta sea correcta desde el navegador -->
    <script src="<?php echo BASE_URL; ?>/farma/js/insertar_inventario.js?v=<?php echo time(); ?>"></script>
</body>
</html>