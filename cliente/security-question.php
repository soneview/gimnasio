<?php
session_start();
require_once '../config/db.php';
require_once '../includes/recovery_functions.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$usuario_id = $_SESSION['user_id'];
$error = '';
$success = '';
$pregunta_actual = '';
$redirect = '';

// Obtener el parámetro de redirección (opcional)
if (isset($_GET['redirect'])) {
    $redirect = $_GET['redirect'];
}

$conn = connectDB();

// Verificar si el usuario ya tiene una pregunta configurada
$tiene_pregunta = usuarioTienePreguntaSecreta($conn, $usuario_id);

// Obtener la pregunta actual si existe
if ($tiene_pregunta) {
    $query = "SELECT pregunta FROM recovery_keys WHERE usuario_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $pregunta_actual = $result->fetch_assoc()['pregunta'];
    }
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener datos del formulario
    $pregunta = trim($_POST['pregunta']);
    $respuesta = trim($_POST['respuesta']);
    $respuesta_confirmar = trim($_POST['respuesta_confirmar']);
    
    // Validar campos
    if (empty($pregunta) || empty($respuesta) || empty($respuesta_confirmar)) {
        $error = "Por favor, complete todos los campos.";
    } elseif ($respuesta !== $respuesta_confirmar) {
        $error = "Las respuestas no coinciden.";
    } elseif (strlen($respuesta) < 3) {
        $error = "La respuesta debe tener al menos 3 caracteres.";
    } else {
        // Registrar o actualizar la pregunta secreta
        $result = registrarPreguntaSecreta($conn, $usuario_id, $pregunta, $respuesta);
        
        if ($result === true) {
            $success = "Su pregunta de seguridad ha sido guardada exitosamente.";
            
            // Si hay un parámetro de redirección, redirigir después de un tiempo
            if (!empty($redirect)) {
                $success .= " Será redirigido en 3 segundos.";
                echo "<script>
                        setTimeout(function() {
                            window.location.href = '".htmlspecialchars($redirect)."';
                        }, 3000);
                      </script>";
            }
            
            // Actualizar la pregunta actual
            $pregunta_actual = $pregunta;
            $tiene_pregunta = true;
        } else {
            $error = $result;
        }
    }
}

$conn->close();

// Incluir el header
include_once '../includes/header.php';
?>

<div class="container section-padding">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-key me-2"></i> Configurar Pregunta de Seguridad</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($tiene_pregunta): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Ya tiene configurada una pregunta de seguridad. Puede actualizarla si lo desea.
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="security-question-form">
                        <div class="mb-3">
                            <label for="pregunta" class="form-label">Pregunta de Seguridad</label>
                            <input type="text" class="form-control" id="pregunta" name="pregunta" 
                                   placeholder="Ejemplo: Nombre de mi primera mascota" 
                                   value="<?php echo htmlspecialchars($pregunta_actual); ?>" required>
                            <div class="form-text">
                                Cree una pregunta personal que solo usted pueda responder. Esta se utilizará para recuperar su cuenta si olvida su contraseña.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="respuesta" class="form-label">Respuesta</label>
                            <input type="text" class="form-control" id="respuesta" name="respuesta" 
                                   placeholder="Ingrese su respuesta" required>
                            <div class="form-text">
                                La respuesta no distingue entre mayúsculas y minúsculas, pero debe ser exacta.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="respuesta_confirmar" class="form-label">Confirmar Respuesta</label>
                            <input type="text" class="form-control" id="respuesta_confirmar" name="respuesta_confirmar" 
                                   placeholder="Confirme su respuesta" required>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> <strong>Importante:</strong> 
                            Recuerde su respuesta exactamente como la escribe ahora. Será necesaria para recuperar su cuenta si olvida su contraseña.
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> <?php echo $tiene_pregunta ? 'Actualizar' : 'Guardar'; ?> Pregunta de Seguridad
                            </button>
                            <a href="<?php echo empty($redirect) ? 'profile.php' : htmlspecialchars($redirect); ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Volver
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('security-question-form');
    const preguntaInput = document.getElementById('pregunta');
    const respuestaInput = document.getElementById('respuesta');
    const respuestaConfirmarInput = document.getElementById('respuesta_confirmar');
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validar pregunta
        if (preguntaInput.value.trim() === '') {
            showError(preguntaInput, 'La pregunta es requerida');
            isValid = false;
        } else {
            removeError(preguntaInput);
        }
        
        // Validar respuesta
        if (respuestaInput.value.trim() === '') {
            showError(respuestaInput, 'La respuesta es requerida');
            isValid = false;
        } else if (respuestaInput.value.length < 3) {
            showError(respuestaInput, 'La respuesta debe tener al menos 3 caracteres');
            isValid = false;
        } else {
            removeError(respuestaInput);
        }
        
        // Validar confirmación de respuesta
        if (respuestaConfirmarInput.value.trim() === '') {
            showError(respuestaConfirmarInput, 'La confirmación de respuesta es requerida');
            isValid = false;
        } else if (respuestaConfirmarInput.value !== respuestaInput.value) {
            showError(respuestaConfirmarInput, 'Las respuestas no coinciden');
            isValid = false;
        } else {
            removeError(respuestaConfirmarInput);
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
    
    // Validación en tiempo real para la confirmación de respuesta
    respuestaConfirmarInput.addEventListener('input', function() {
        if (this.value.trim() === '') {
            showError(this, 'La confirmación de respuesta es requerida');
        } else if (this.value !== respuestaInput.value) {
            showError(this, 'Las respuestas no coinciden');
        } else {
            removeError(this);
        }
    });
    
    // Función para mostrar error
    function showError(input, message) {
        // Obtener el div.form-text siguiente al input o crear uno nuevo
        let errorElement = input.nextElementSibling;
        if (!errorElement || !errorElement.classList.contains('form-text')) {
            errorElement = document.createElement('div');
            errorElement.className = 'form-text';
            input.parentNode.insertBefore(errorElement, input.nextSibling);
        }
        
        // Agregar mensaje de error y clase
        errorElement.textContent = message;
        errorElement.classList.add('text-danger');
        input.classList.add('is-invalid');
    }
    
    // Función para eliminar error
    function removeError(input) {
        let errorElement = input.nextElementSibling;
        if (errorElement && errorElement.classList.contains('form-text')) {
            errorElement.textContent = '';
            errorElement.classList.remove('text-danger');
        }
        input.classList.remove('is-invalid');
    }
});
</script>

<?php include_once '../includes/footer.php'; ?>
