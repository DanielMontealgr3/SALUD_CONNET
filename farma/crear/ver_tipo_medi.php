<?php
// --- BLOQUE 1: CONFIGURACIÓN INICIAL Y SEGURIDAD ---
// Se incluye el archivo de configuración central. La ruta __DIR__ . '/../../' sube dos niveles
// desde 'farma/crear/' para encontrar la carpeta 'include/'.
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

// --- BLOQUE 2: FUNCIÓN PARA GENERAR EL CONTENIDO DE LA TABLA Y PAGINACIÓN ---
// Esta función encapsula toda la lógica para buscar, filtrar y paginar los resultados de los tipos de medicamentos.
function renderizar_tabla_y_paginacion($con)
{
    $registros_por_pagina = 5;
    $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina_actual < 1) $pagina_actual = 1;

    // Recolección y limpieza de los filtros.
    $searchTerm = $_GET['q'] ?? '';
    $sql_base = "FROM tipo_de_medicamento";
    $sql_where = "";
    $params = [];

    if (!empty($searchTerm)) {
        $sql_where = " WHERE nom_tipo_medi LIKE :searchTerm";
        $params[':searchTerm'] = "%" . $searchTerm . "%";
    }

    // Se cuenta el total de registros que coinciden con los filtros para la paginación.
    $stmt_total = $con->prepare("SELECT COUNT(*) " . $sql_base . $sql_where);
    $stmt_total->execute($params);
    $total_registros = (int)$stmt_total->fetchColumn();

    $total_paginas = ceil($total_registros / $registros_por_pagina);
    if ($total_paginas == 0) $total_paginas = 1;
    if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;
    
    $offset = ($pagina_actual - 1) * $registros_por_pagina;
    
    // Se construye la consulta final para obtener solo los registros de la página actual.
    $sql_final = "SELECT id_tip_medic, nom_tipo_medi " . $sql_base . $sql_where . " ORDER BY nom_tipo_medi ASC LIMIT :limit OFFSET :offset";
    $stmt = $con->prepare($sql_final);

    if (!empty($searchTerm)) {
        $stmt->bindParam(':searchTerm', $params[':searchTerm'], PDO::PARAM_STR);
    }
    $stmt->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tipos_medicamento = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Se genera el HTML para las filas de la tabla.
    ob_start();
?>
    <div class="table-responsive">
        <table class="tabla-admin-mejorada">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre del Tipo</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-tipos-body">
                <?php if (!empty($tipos_medicamento)): foreach ($tipos_medicamento as $tipo): ?>
                    <tr>
                        <td><?php echo $tipo['id_tip_medic']; ?></td>
                        <td><?php echo htmlspecialchars($tipo['nom_tipo_medi']); ?></td>
                        <td class="text-center">
                            <button class="btn btn-warning btn-sm btn-editar" data-id="<?php echo $tipo['id_tip_medic']; ?>" data-nombre="<?php echo htmlspecialchars($tipo['nom_tipo_medi']); ?>">
                                <i class="bi bi-pencil-fill"></i> Editar
                            </button>
                            <button class="btn btn-danger btn-sm btn-eliminar" data-id="<?php echo $tipo['id_tip_medic']; ?>" data-nombre="<?php echo htmlspecialchars($tipo['nom_tipo_medi']); ?>">
                                <i class="bi bi-trash-fill"></i> Eliminar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="3" class="text-center p-4">No se encontraron resultados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($total_paginas > 1): ?>
        <nav class="mt-3 paginacion-tabla-container">
            <ul class="pagination pagination-sm">
                <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>"><a class="page-link" href="#" data-page="<?php echo $pagina_actual - 1; ?>"><i class="bi bi-chevron-left"></i></a></li>
                <li class="page-item active"><span class="page-link"><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></span></li>
                <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>"><a class="page-link" href="#" data-page="<?php echo $pagina_actual + 1; ?>"><i class="bi bi-chevron-right"></i></a></li>
            </ul>
        </nav>
    <?php endif; ?>
<?php
    return ob_get_clean();
}

// --- BLOQUE 3: LÓGICA PRINCIPAL DE LA PÁGINA ---
// La conexión $con ya viene lista desde config.php.
// Si la solicitud es por AJAX, se devuelve solo el HTML de la tabla y la paginación.
if (isset($_GET['ajax'])) {
    header('Content-Type: text/html');
    echo renderizar_tabla_y_paginacion($con);
    exit;
}

$pageTitle = "Gestionar Tipos de Medicamento";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- --- BLOQUE 4: METADATOS Y ENLACES CSS/JS DEL HEAD --- -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
    <!-- Rutas a recursos corregidas con BASE_URL -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/loguito.png">
    <?php require_once ROOT_PATH . '/include/menu.php'; ?>
    <style>
        .barcode-cell svg { height: 30px; width: auto; max-width: 150px; display: block; }
        .barcode-detail { width: 100%; max-width: 300px; height: auto; }
        .filtros-tabla-container .form-label { font-size: 0.8rem; margin-bottom: 0.2rem; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <!-- --- BLOQUE 5: CONTENIDO HTML PRINCIPAL --- -->
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="titulo-lista-tabla m-0">Tipos de Medicamento</h3>
                    <a href="<?php echo BASE_URL; ?>/farma/crear/crear_tipo_medi.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle-fill me-2"></i>Crear Nuevo Tipo</a>
                </div>
                
                <form id="formBusqueda" class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" class="form-control" id="searchInput" name="q" placeholder="Buscar por nombre..." value="">
                    </div>
                </form>

                <div id="contenedor-tabla">
                    <?php echo renderizar_tabla_y_paginacion($con); ?>
                </div>
            </div>
        </div>
    </main>

    <!-- --- BLOQUE 6: MODALES Y SCRIPTS FINALES --- -->
    <div class="modal fade" id="modalEditarTipo" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formEditarTipo">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Tipo de Medicamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit-id" name="id_tip_medic">
                        <div class="mb-3">
                            <label for="edit-nombre" class="form-label">Nombre:</label>
                            <input type="text" class="form-control" id="edit-nombre" name="nom_tipo_medi" required>
                            <div class="invalid-feedback">Debe contener solo letras y espacios (mín. 5 caracteres).</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardarCambios" disabled>Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Se incluye el footer usando ROOT_PATH -->
    <?php require_once ROOT_PATH . '/include/footer.php'; ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const tablaContenedor = document.getElementById('contenedor-tabla');
        let debounceTimer;

        function cargarContenido(page = 1) {
            const query = searchInput.value;
            // --- RUTA CORREGIDA ---
            // Se usa BASE_URL para asegurar la ruta al endpoint AJAX.
            const url = `<?php echo BASE_URL; ?>/farma/crear/ver_tipo_medi.php?ajax=1&q=${encodeURIComponent(query)}&pagina=${page}`;

            fetch(url)
                .then(response => response.text())
                .then(html => {
                    tablaContenedor.innerHTML = html;
                })
                .catch(error => console.error('Error al cargar la tabla:', error));
        }

        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                cargarContenido(1);
            }, 300);
        });

        tablaContenedor.addEventListener('click', function(e) {
            if (e.target.closest('.page-link')) {
                e.preventDefault();
                const page = e.target.closest('.page-link').dataset.page;
                if (page) {
                    cargarContenido(page);
                }
            }
        });

        const modalElement = document.getElementById('modalEditarTipo');
        const modal = new bootstrap.Modal(modalElement);
        const formEditar = document.getElementById('formEditarTipo');
        const inputId = document.getElementById('edit-id');
        const inputNombre = document.getElementById('edit-nombre');
        const btnGuardar = document.getElementById('btnGuardarCambios');
        let originalNombre = '';

        function validarNombreEdicion() {
            const valor = inputNombre.value.trim();
            const esValido = /^[a-zA-Z\s()]{5,}$/.test(valor);
            inputNombre.classList.toggle('is-valid', esValido);
            inputNombre.classList.toggle('is-invalid', !esValido);
            const haCambiado = valor !== originalNombre;
            btnGuardar.disabled = !(esValido && haCambiado);
        }

        tablaContenedor.addEventListener('click', function(e) {
            const btnEditar = e.target.closest('.btn-editar');
            const btnEliminar = e.target.closest('.btn-eliminar');

            if (btnEditar) {
                inputId.value = btnEditar.dataset.id;
                originalNombre = btnEditar.dataset.nombre;
                inputNombre.value = originalNombre;
                inputNombre.classList.remove('is-valid', 'is-invalid');
                btnGuardar.disabled = true;
                modal.show();
            }

            if (btnEliminar) {
                const id = btnEliminar.dataset.id;
                const nombre = btnEliminar.dataset.nombre;
                
                const urlEliminar = `<?php echo BASE_URL; ?>/farma/crear/ajax_eliminar_tipo_medi.php`;

                Swal.fire({
                    title: '¿Está seguro?',
                    text: `Realmente desea eliminar el tipo "${nombre}"? Esta acción no se puede deshacer.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(urlEliminar, {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: `id_tip_medic=${id}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Eliminado', data.message, 'success').then(() => cargarContenido());
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(() => Swal.fire('Error', 'No se pudo completar la solicitud.', 'error'));
                    }
                });
            }
        });

        inputNombre.addEventListener('input', validarNombreEdicion);

        formEditar.addEventListener('submit', function(e) {
            e.preventDefault();
            validarNombreEdicion();
            if (btnGuardar.disabled) return;

            const formData = new FormData(formEditar);
            const urlEditar = `<?php echo BASE_URL; ?>/farma/crear/ajax_editar_tipo_medi.php`;
            
            fetch(urlEditar, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                modal.hide();
                if (data.success) {
                    Swal.fire('¡Actualizado!', data.message, 'success').then(() => cargarContenido());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'No se pudo completar la solicitud.', 'error'));
        });
    });
    </script>
</body>
</html>