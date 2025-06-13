<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

function renderizar_tabla_y_paginacion($con)
{
    $registros_por_pagina = 5;
    $pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina_actual < 1) $pagina_actual = 1;

    $searchTerm = $_GET['q'] ?? '';
    $sql_base = "FROM tipo_de_medicamento";
    $sql_where = "";
    $params = [];

    if (!empty($searchTerm)) {
        $sql_where = " WHERE nom_tipo_medi LIKE :searchTerm";
        $params[':searchTerm'] = "%" . $searchTerm . "%";
    }

    $stmt_total = $con->prepare("SELECT COUNT(*) " . $sql_base . $sql_where);
    $stmt_total->execute($params);
    $total_registros = (int)$stmt_total->fetchColumn();

    $total_paginas = ceil($total_registros / $registros_por_pagina);
    if ($total_paginas == 0) $total_paginas = 1;
    if ($pagina_actual > $total_paginas) $pagina_actual = $total_paginas;
    
    $offset = ($pagina_actual - 1) * $registros_por_pagina;

    $sql_final = "SELECT id_tip_medic, nom_tipo_medi " . $sql_base . $sql_where . " ORDER BY nom_tipo_medi ASC LIMIT :limit OFFSET :offset";
    $stmt = $con->prepare($sql_final);

    if (!empty($searchTerm)) {
        $stmt->bindParam(':searchTerm', $params[':searchTerm'], PDO::PARAM_STR);
    }
    $stmt->bindParam(':limit', $registros_por_pagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tipos_medicamento = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <?php if (!empty($tipos_medicamento)): ?>
                    <?php foreach ($tipos_medicamento as $tipo): ?>
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
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center p-4">No se encontraron resultados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($total_paginas > 1): ?>
        <nav class="mt-3 paginacion-tabla-container">
            <ul class="pagination pagination-sm">
                <li class="page-item <?php echo ($pagina_actual <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="#" data-page="<?php echo $pagina_actual - 1; ?>"><i class="bi bi-chevron-left"></i></a>
                </li>
                <li class="page-item active">
                    <span class="page-link"><?php echo $pagina_actual; ?> / <?php echo $total_paginas; ?></span>
                </li>
                <li class="page-item <?php echo ($pagina_actual >= $total_paginas) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="#" data-page="<?php echo $pagina_actual + 1; ?>"><i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
<?php
    return ob_get_clean();
}

if (class_exists('database') && (!isset($con) || !($con instanceof PDO))) {
    $db = new database();
    $con = $db->conectar();
}

if (isset($_GET['ajax'])) {
    echo renderizar_tabla_y_paginacion($con);
    exit;
}

$pageTitle = "Gestionar Tipos de Medicamento";
?>
<!DOCTYPE html>
<html lang="es">
<head>
     <link rel="icon" type="image/png" href="../../img/loguito.png">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="flex-grow-1 d-flex flex-column">
        <div class="container-fluid mt-3 flex-grow-1 d-flex flex-column">
            <div class="vista-datos-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="titulo-lista-tabla m-0">Tipos de Medicamento</h3>
                    <a href="crear_tipo_medi.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle-fill me-2"></i>Crear Nuevo Tipo</a>
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
    <?php include '../../include/footer.php'; ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const tablaContenedor = document.getElementById('contenedor-tabla');
        let debounceTimer;

        function cargarContenido(page = 1) {
            const query = searchInput.value;
            const url = `ver_tipo_medi.php?ajax=1&q=${encodeURIComponent(query)}&pagina=${page}`;

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
            if (e.target.closest('.btn-editar')) {
                const btn = e.target.closest('.btn-editar');
                inputId.value = btn.dataset.id;
                originalNombre = btn.dataset.nombre;
                inputNombre.value = originalNombre;
                inputNombre.classList.remove('is-valid', 'is-invalid');
                btnGuardar.disabled = true;
                modal.show();
            }

            if (e.target.closest('.btn-eliminar')) {
                const btn = e.target.closest('.btn-eliminar');
                const id = btn.dataset.id;
                const nombre = btn.dataset.nombre;

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
                        fetch('ajax_eliminar_tipo_medi.php', {
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
            fetch('ajax_editar_tipo_medi.php', { method: 'POST', body: formData })
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