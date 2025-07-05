<?php
require_once '../../include/validar_sesion.php';
require_once '../../include/inactividad.php';
require_once '../../include/conexion.php';

$pageTitle = "Crear Nuevo Medicamento";

$url_anterior = $_SERVER['HTTP_REFERER'] ?? 'inventario.php';
if (strpos($url_anterior, 'crear_medicamento.php') !== false) {
    $url_anterior = 'inventario.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
     <link rel="icon" type="image/png" href="../../img/loguito.png">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Salud Connected</title>
</head>
<body class="d-flex flex-column min-vh-100">
    <?php include '../../include/menu.php'; ?>
    <main id="contenido-principal" class="centrado">
        <div class="vista-datos-container compact-form">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="titulo-lista-tabla mb-0">Nuevo Medicamento</h3>
                <a href="<?php echo htmlspecialchars($url_anterior); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a>
            </div>
            
            <form id="formCrearMedicamento">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nom_medicamento" class="form-label">Nombre y Dosis:</label>
                        <input type="text" class="form-control" id="nom_medicamento" name="nom_medicamento" required placeholder="Ej: Ibuprofeno 400mg">
                        <div class="invalid-feedback">El nombre es obligatorio (mín. 5 caracteres).</div>
                    </div>
                    <div class="col-md-6">
                        <label for="id_tipo_medic" class="form-label">Tipo de Medicamento:</label>
                        <select class="form-select" id="id_tipo_medic" name="id_tipo_medic" required>
                            <option value="">Cargando tipos...</option>
                        </select>
                        <div class="invalid-feedback">Debe seleccionar un tipo.</div>
                    </div>
                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción:</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
                        <div class="invalid-feedback">La descripción es obligatoria (mín. 10 caracteres).</div>
                    </div>
                    <div class="col-12">
                        <label for="codigo_barras" class="form-label">Código de Barras (Opcional):</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="codigo_barras" name="codigo_barras" placeholder="Escanear o ingresar código...">
                            <button class="btn btn-outline-secondary" type="button" id="btnGenerarCodigo">Generar</button>
                        </div>
                    </div>
                </div>

                <!-- Contenedor para centrar solo el botón -->
                <div class="d-grid gap-2 col-6 mx-auto mt-4">
                    <button type="submit" id="btnGuardar" class="btn btn-primary" disabled>
                        <i class="bi bi-plus-circle-fill me-2"></i>Crear Medicamento
                    </button>
                </div>
            </form>
        </div>
    </main>
    <?php include '../../include/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formCrearMedicamento');
        const selectTipo = document.getElementById('id_tipo_medic');
        const btnGuardar = document.getElementById('btnGuardar');
        const btnGenerarCodigo = document.getElementById('btnGenerarCodigo');
        
        const campos = {
            nombre: document.getElementById('nom_medicamento'),
            tipo: selectTipo,
            descripcion: document.getElementById('descripcion')
        };

        fetch('ajax_obtener_tipos_medi.php')
            .then(response => response.json())
            .then(data => {
                selectTipo.innerHTML = '<option value="">Seleccione un tipo...</option>';
                if (data.success) {
                    data.tipos.forEach(tipo => {
                        const option = new Option(tipo.nom_tipo_medi, tipo.id_tip_medic);
                        selectTipo.add(option);
                    });
                } else {
                    selectTipo.innerHTML = '<option value="">Error al cargar</option>';
                }
            })
            .catch(() => {
                selectTipo.innerHTML = '<option value="">Error de conexión</option>';
            });

        function validarFormulario() {
            let esValido = true;
            
            const nombreValido = campos.nombre.value.trim().length >= 5;
            campos.nombre.classList.toggle('is-valid', nombreValido);
            campos.nombre.classList.toggle('is-invalid', !nombreValido);
            if (!nombreValido) esValido = false;

            const tipoValido = campos.tipo.value !== '';
            campos.tipo.classList.toggle('is-valid', tipoValido);
            campos.tipo.classList.toggle('is-invalid', !tipoValido);
            if (!tipoValido) esValido = false;

            const descValida = campos.descripcion.value.trim().length >= 10;
            campos.descripcion.classList.toggle('is-valid', descValida);
            campos.descripcion.classList.toggle('is-invalid', !descValida);
            if (!descValida) esValido = false;

            btnGuardar.disabled = !esValido;
        }

        Object.values(campos).forEach(input => {
            input.addEventListener('input', validarFormulario);
        });
        
        btnGenerarCodigo.addEventListener('click', function() {
            const codigo = Math.floor(100000000000 + Math.random() * 900000000000).toString();
            document.getElementById('codigo_barras').value = codigo;
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            validarFormulario();
            if (btnGuardar.disabled) return;

            btnGuardar.disabled = true;
            btnGuardar.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Guardando...`;

            const formData = new FormData(form);

            fetch('ajax_crear_medicamento.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Éxito!', data.message, 'success');
                    form.reset();
                    Object.values(campos).forEach(input => input.classList.remove('is-valid', 'is-invalid'));
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
                btnGuardar.innerHTML = `<i class="bi bi-plus-circle-fill me-2"></i>Crear Medicamento`;
            });
        });
    });
    </script>
</body>
</html>