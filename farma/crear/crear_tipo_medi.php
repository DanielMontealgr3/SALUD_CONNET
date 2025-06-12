<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

$pageTitle = "Crear Tipo de Medicamento";

// Lógica para el botón "Volver"
$url_anterior = $_SERVER['HTTP_REFERER'] ?? 'inventario.php';
if (strpos($url_anterior, 'crear_tipo_medi.php') !== false) {
    $url_anterior = 'inventario.php'; // Evitar bucles si se recarga la página
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
    <style>
        .form-control.is-invalid, .form-control.is-valid {
            background-position: right calc(0.375em + 0.1875rem) center;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="centrado">
        <!-- Añadimos la clase text-center para centrar el contenido -->
        <div class="vista-datos-container compact-form text-center">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="titulo-lista-tabla mb-0 mx-auto">Nuevo Tipo de Medicamento</h3>
                <a href="<?php echo htmlspecialchars($url_anterior); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
            </div>
            
            <form id="formCrearTipo">
                <!-- El label ahora está centrado por la clase del div padre -->
                <div class="mb-3">
                    <label for="nom_tipo_medi" class="form-label">Nombre del Tipo de Medicamento:</label>
                    <input type="text" class="form-control" id="nom_tipo_medi" name="nom_tipo_medi" required autocomplete="off" placeholder="Ej: Analgésicos">
                    <div class="invalid-feedback">Debe contener solo letras y espacios, y tener más de 4 caracteres.</div>
                </div>

                <!-- Contenedor para centrar el botón con d-grid -->
                <div class="d-grid gap-2 col-6 mx-auto mt-4">
                    <button type="submit" id="btnGuardarTipo" class="btn btn-primary" disabled>
                        <i class="bi bi-plus-circle-fill me-2"></i>Crear Tipo
                    </button>
                </div>
            </form>
        </div>
    </main>
    <?php include '../../include/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formCrearTipo');
        const inputNombre = document.getElementById('nom_tipo_medi');
        const btnGuardar = document.getElementById('btnGuardarTipo');

        function validarNombre() {
            const valor = inputNombre.value.trim();
            // Permite paréntesis y letras/espacios. Mínimo 5 caracteres.
            const esValido = /^[a-zA-Z\s()]{5,}$/.test(valor);

            inputNombre.classList.toggle('is-valid', esValido);
            inputNombre.classList.toggle('is-invalid', !esValido);
            btnGuardar.disabled = !esValido;
            return esValido;
        }

        inputNombre.addEventListener('input', validarNombre);

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!validarNombre()) return;

            btnGuardar.disabled = true;
            btnGuardar.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Guardando...`;

            const formData = new FormData(form);

            fetch('ajax_crear_tipo_medi.php', {
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