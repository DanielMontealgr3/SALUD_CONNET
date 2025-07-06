<?php
// --- BLOQUE 1: CONFIGURACIÓN INICIAL Y SEGURIDAD ---
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

// --- BLOQUE 2: VERIFICACIÓN DE ROL Y OBTENCIÓN DE DATOS DE FARMACIA ---
$nombre_farmacia_asignada = "No Asignada";
// Se usa la variable de sesión estandarizada
$nit_farmacia_actual = $_SESSION['nit_farma'] ?? '';

if (empty($nit_farmacia_actual)) {
    // Si no hay farmacia en sesión, redirigir al inicio
    header('Location: ' . BASE_URL . '/farma/inicio.php');
    exit;
}

try {
    // Se usa la conexión global $con de config.php
    $stmt_nombre = $con->prepare("SELECT nom_farm FROM farmacias WHERE nit_farm = ?");
    $stmt_nombre->execute([$nit_farmacia_actual]);
    $nombre_farma_obtenido = $stmt_nombre->fetchColumn();
    if ($nombre_farma_obtenido) {
        $nombre_farmacia_asignada = $nombre_farma_obtenido;
    }
} catch(PDOException $e) {
    error_log("Error al obtener nombre de farmacia: " . $e->getMessage());
}

$pageTitle = "Registrar Entrada de Inventario";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
    <!-- Se usa BASE_URL para la ruta del ícono -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/loguito.png">
    <!-- Se usa ROOT_PATH para la ruta del menú -->
    <?php require_once ROOT_PATH . '/include/menu.php'; ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <main id="contenido-principal" class="centrado">
        <!-- Tu HTML original se mantiene intacto -->
        <div class="vista-datos-container compact-form">
            <div class="d-flex justify-content-between align-items-center mb-3 header-form-responsive">
                <div>
                    <h3 class="titulo-lista-tabla mb-0">Registrar Entrada</h3>
                    <small class="text-muted">Farmacia: <strong><?php echo htmlspecialchars($nombre_farmacia_asignada); ?></strong></small>
                </div>
                <!-- Se usa BASE_URL para el enlace de retorno -->
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
    <?php require_once ROOT_PATH . '/include/footer.php'; ?>
    <!-- Se usa BASE_URL para la ruta del script JS -->
    <script src="<?php echo BASE_URL; ?>/farma/js/insertar_inventario.js?v=<?php echo time(); ?>"></script>
</body>
</html>