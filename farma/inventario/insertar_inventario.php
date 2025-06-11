<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}
$nombre_farmacia_asignada = "No Asignada";
$nit_farmacia_actual = $_SESSION['nit_farmacia_asignada_actual'] ?? '';
if ($nit_farmacia_actual) {
    $stmt_nombre = $con->prepare("SELECT nom_farm FROM farmacias WHERE nit_farm = ?");
    $stmt_nombre->execute([$nit_farmacia_actual]);
    $nombre_farmacia_asignada = $stmt_nombre->fetchColumn();
}
$pageTitle = "Registrar Entrada de Inventario";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="centrado">
        <div class="vista-datos-container compact-form">
            <div class="d-flex justify-content-between align-items-center mb-3 header-form-responsive">
                <div>
                    <h3 class="titulo-lista-tabla mb-0">Registrar Entrada</h3>
                    <small class="text-muted">Farmacia: <strong><?php echo htmlspecialchars($nombre_farmacia_asignada); ?></strong></small>
                </div>
                <a href="inventario.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
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
                            <div class="mb-3">
                                <label for="cantidad_entrada" class="form-label"><strong>Cantidad a Ingresar:</strong></label>
                                <input type="number" class="form-control" id="cantidad_entrada" name="cantidad" required min="1">
                                <div class="invalid-feedback">Debe ser > 0.</div>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label for="lote" class="form-label">Lote:</label>
                                    <input type="text" class="form-control" id="lote" name="lote" required>
                                    <div class="invalid-feedback">Obligatorio.</div>
                                </div>
                                <div class="col-sm-6">
                                    <label for="fecha_vencimiento" class="form-label">Vencimiento:</label>
                                    <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" required>
                                    <div class="invalid-feedback">Fecha no válida.</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="notas" class="form-label">Notas (Opcional):</label>
                                <textarea class="form-control" id="notas" name="notas" rows="1"></textarea>
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
    <?php include '../../include/footer.php'; ?>
    <script src="../js/insertar_inventario.js?v=<?php echo time(); ?>"></script>
</body>
</html>