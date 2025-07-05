<?php
// --- BLOQUE 1: CONFIGURACIÓN INICIAL Y SEGURIDAD ---
// Se incluye el archivo de configuración central. La ruta __DIR__ . '/../../' sube dos niveles
// desde 'farma/crear/' para encontrar la carpeta 'include/'.
require_once __DIR__ . '/../../include/config.php';
require_once ROOT_PATH . '/include/validar_sesion.php';
require_once ROOT_PATH . '/include/inactividad.php';

$pageTitle = "Crear Tipo de Medicamento";

// --- BLOQUE 2: LÓGICA DE NAVEGACIÓN ---
// Se obtiene la URL de la página anterior para el botón "Volver".
// Se asegura que si el usuario recarga, no quede atrapado en un bucle de redirección.
// Se usa BASE_URL para construir una ruta segura en caso de que HTTP_REFERER no exista.
$url_anterior = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/farma/inventario/inventario.php';
if (strpos($url_anterior, 'crear_tipo_medi.php') !== false) {
    $url_anterior = BASE_URL . '/farma/inventario/inventario.php'; 
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- --- BLOQUE 3: METADATOS Y ENLACES CSS/JS DEL HEAD --- -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
    <!-- Ruta al favicon corregida con BASE_URL -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/img/loguito.png">
    <!-- Se incluye el menú usando ROOT_PATH para garantizar una ruta absoluta en el servidor -->
    <?php require_once ROOT_PATH . '/include/menu.php'; ?>
    <style>
        .form-control.is-invalid, .form-control.is-valid {
            background-position: right calc(0.375em + 0.1875rem) center;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <main id="contenido-principal" class="centrado">
        <!-- --- BLOQUE 4: CONTENIDO HTML PRINCIPAL --- -->
        <div class="vista-datos-container compact-form text-center">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="titulo-lista-tabla mb-0 mx-auto">Nuevo Tipo de Medicamento</h3>
                <a href="<?php echo htmlspecialchars($url_anterior); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
            </div>
            
            <form id="formCrearTipo">
                <div class="mb-3">
                    <label for="nom_tipo_medi" class="form-label">Nombre del Tipo de Medicamento:</label>
                    <input type="text" class="form-control" id="nom_tipo_medi" name="nom_tipo_medi" required autocomplete="off" placeholder="Ej: Analgésicos">
                    <div class="invalid-feedback">Debe contener solo letras y espacios, y tener más de 4 caracteres.</div>
                </div>

                <div class="d-grid gap-2 col-6 mx-auto mt-4">
                    <button type="submit" id="btnGuardarTipo" class="btn btn-primary" disabled>
                        <i class="bi bi-plus-circle-fill me-2"></i>Crear Tipo
                    </button>
                </div>
            </form>
        </div>
    </main>
    
    <!-- --- BLOQUE 5: SCRIPTS Y FOOTER --- -->
    <!-- Se incluye el footer usando ROOT_PATH -->
    <?php require_once ROOT_PATH . '/include/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formCrearTipo');
        const inputNombre = document.getElementById('nom_tipo_medi');
        const btnGuardar = document.getElementById('btnGuardarTipo');

        // Función para validar el campo de nombre en tiempo real.
        function validarNombre() {
            const valor = inputNombre.value.trim();
            const esValido = /^[a-zA-Z\s()]{5,}$/.test(valor);

            inputNombre.classList.toggle('is-valid', esValido);
            inputNombre.classList.toggle('is-invalid', !esValido);
            btnGuardar.disabled = !esValido;
            return esValido;
        }

        inputNombre.addEventListener('input', validarNombre);
        
        // --- RUTA CORREGIDA ---
        // Se utiliza la constante global AppConfig.BASE_URL para construir las rutas a los archivos AJAX.
        const urlTipos = `<?php echo BASE_URL; ?>/farma/crear/ajax_obtener_tipos_medi.php`;
        const urlCrear = `<?php echo BASE_URL; ?>/farma/crear/ajax_crear_tipo_medi.php`;

        // Carga los tipos de medicamentos existentes (si fuera necesario para validación o autocompletado, aunque aquí no se usa).
        fetch(urlTipos)
            .then(response => response.json())
            .catch(() => console.error('Error al precargar tipos de medicamento.'));

        // Evento para enviar el formulario.
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!validarNombre()) return;

            btnGuardar.disabled = true;
            btnGuardar.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Guardando...`;

            const formData = new FormData(form);

            fetch(urlCrear, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: data.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    form.reset();
                    inputNombre.classList.remove('is-valid', 'is-invalid');
                    btnGuardar.disabled = true;
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error de Conexión', 'No se pudo completar la solicitud.', 'error');
            })
            .finally(() => {
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = `<i class="bi bi-plus-circle-fill me-2"></i>Crear Tipo`;
            });
        });
    });
    </script>
</body>
</html>