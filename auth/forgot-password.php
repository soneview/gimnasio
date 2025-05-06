<?php
session_start();
require_once '../config/db.php';
require_once '../includes/recovery_functions.php';

// Redireccionar si ya ha iniciado sesión
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';
$email_value = '';
$mostrar_pregunta = false;
$pregunta_texto = '';
$usuario_id = null;
$recovery_id = null;

// Primera etapa: Pedir el correo electrónico
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['step']) && $_POST['step'] == 'email') {
    $conn = connectDB();
    
    // Obtener y sanitizar datos
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $email_value = $email; // Guardar para repoblar el formulario
    
    // Verificar formato del correo
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, ingrese un correo electrónico válido.";
    } else {
        // Verificar si hay demasiados intentos desde esta IP
        if (demasiadosIntentosPorIP($conn, $_SERVER['REMOTE_ADDR'])) {
            $error = "Demasiados intentos de recuperación. Por favor, inténtelo de nuevo más tarde.";
            registrarIntentoRecuperacion($conn, null, $email, false, "Bloqueo por múltiples intentos");
        } else {
            // Buscar la pregunta secreta asociada al email
            $info_pregunta = obtenerPreguntaSecretaPorEmail($conn, $email);
            
            if ($info_pregunta) {
                // Verificar si la cuenta está bloqueada
                if ($info_pregunta['bloqueado_hasta'] !== null && strtotime($info_pregunta['bloqueado_hasta']) > time()) {
                    $tiempo_restante = ceil((strtotime($info_pregunta['bloqueado_hasta']) - time()) / 60);
                    $error = "Su cuenta está temporalmente bloqueada. Por favor, inténtelo de nuevo en $tiempo_restante minutos.";
                    registrarIntentoRecuperacion($conn, $info_pregunta['usuario_id'], $email, false, "Cuenta bloqueada");
                } else {
                    // Mostrar la pregunta secreta
                    $mostrar_pregunta = true;
                    $pregunta_texto = $info_pregunta['pregunta'];
                    $usuario_id = $info_pregunta['usuario_id'];
                    $recovery_id = $info_pregunta['recovery_id'];
                    
                    // Guardar en sesión para la siguiente etapa
                    $_SESSION['recovery_email'] = $email;
                    $_SESSION['recovery_usuario_id'] = $usuario_id;
                    $_SESSION['recovery_id'] = $recovery_id;
                }
            } else {
                // No mostrar si el correo existe o no por seguridad
                $error = "Si el correo está registrado en nuestro sistema, recibirá las instrucciones de recuperación.";
                registrarIntentoRecuperacion($conn, null, $email, false, "Email no encontrado");
            }
        }
    }
    
    $conn->close();
} 
// Segunda etapa: Verificar respuesta a la pregunta secreta
elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['step']) && $_POST['step'] == 'respuesta') {
    $conn = connectDB();
    
    // Verificar si tenemos la información necesaria en la sesión
    if (!isset($_SESSION['recovery_email']) || !isset($_SESSION['recovery_usuario_id']) || !isset($_SESSION['recovery_id'])) {
        $error = "Ha ocurrido un error. Por favor, inicie el proceso de recuperación nuevamente.";
    } else {
        $email = $_SESSION['recovery_email'];
        $usuario_id = $_SESSION['recovery_usuario_id'];
        $recovery_id = $_SESSION['recovery_id'];
        $email_value = $email;
        
        // Obtener la respuesta ingresada
        $respuesta = trim($_POST['respuesta']);
        
        if (empty($respuesta)) {
            $error = "Por favor, ingrese su respuesta.";
            
            // Mantener la interfaz de pregunta
            $info_pregunta = obtenerPreguntaSecretaPorEmail($conn, $email);
            if ($info_pregunta) {
                $mostrar_pregunta = true;
                $pregunta_texto = $info_pregunta['pregunta'];
            }
        } else {
            // Obtener la información de recuperación
            $query = "SELECT r.*, u.email 
                      FROM recovery_keys r 
                      JOIN usuarios u ON r.usuario_id = u.id 
                      WHERE r.id = ? AND u.email = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("is", $recovery_id, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $recovery_info = $result->fetch_assoc();
                
                // Verificar si la cuenta está bloqueada
                if ($recovery_info['bloqueado_hasta'] !== null && strtotime($recovery_info['bloqueado_hasta']) > time()) {
                    $tiempo_restante = ceil((strtotime($recovery_info['bloqueado_hasta']) - time()) / 60);
                    $error = "Su cuenta está temporalmente bloqueada. Por favor, inténtelo de nuevo en $tiempo_restante minutos.";
                    registrarIntentoRecuperacion($conn, $usuario_id, $email, false, "Cuenta bloqueada");
                } 
                // Verificar la respuesta
                elseif (verificarRespuestaSecreta($respuesta, $recovery_info['respuesta_hash'], $recovery_info['salt'])) {
                    // Respuesta correcta, generar token de restablecimiento
                    $token = generarTokenRestablecimiento($conn, $usuario_id);
                    
                    if ($token) {
                        // Reiniciar intentos fallidos
                        resetearIntentosFallidos($conn, $recovery_id);
                        
                        // Registrar intento exitoso
                        registrarIntentoRecuperacion($conn, $usuario_id, $email, true);
                        
                        // Redirigir a la página de cambio de contraseña
                        $_SESSION['reset_token'] = $token;
                        $_SESSION['reset_user_id'] = $usuario_id;
                        
                        $success = "Verificación correcta. Ahora puede establecer una nueva contraseña.";
                        header("Location: reset-password.php");
                        exit();
                    } else {
                        $error = "Ha ocurrido un error al generar el token de restablecimiento. Por favor, inténtelo de nuevo.";
                    }
                } else {
                    // Respuesta incorrecta, incrementar contador de intentos fallidos
                    incrementarIntentosFallidos($conn, $recovery_id);
                    
                    // Registrar intento fallido
                    registrarIntentoRecuperacion($conn, $usuario_id, $email, false, "Respuesta incorrecta");
                    
                    $error = "La respuesta proporcionada no es correcta. Por favor, inténtelo de nuevo.";
                    
                    // Obtener información actualizada para ver si se ha bloqueado la cuenta
                    $info_pregunta = obtenerPreguntaSecretaPorEmail($conn, $email);
                    if ($info_pregunta) {
                        if ($info_pregunta['bloqueado_hasta'] !== null && strtotime($info_pregunta['bloqueado_hasta']) > time()) {
                            $tiempo_restante = ceil((strtotime($info_pregunta['bloqueado_hasta']) - time()) / 60);
                            $error = "Demasiados intentos fallidos. Su cuenta ha sido bloqueada temporalmente. Por favor, inténtelo de nuevo en $tiempo_restante minutos.";
                        } else {
                            $mostrar_pregunta = true;
                            $pregunta_texto = $info_pregunta['pregunta'];
                        }
                    }
                }
            } else {
                $error = "Ha ocurrido un error. Por favor, inicie el proceso de recuperación nuevamente.";
            }
        }
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Las Bestias Pardas - Recuperar Contraseña</title>
    <link rel="stylesheet" href="../assets/css/auth-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="image-container">
                <div class="overlay"></div>
            </div>
            <div class="form-container">
                <div class="logo">
                    <i class="fas fa-dumbbell"></i>
                    <h1>Las Bestias Pardas</h1>
                </div>
                <h2>Recuperar Contraseña</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="notification error show">
                        <p><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="notification success show">
                        <p><?php echo $success; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!$mostrar_pregunta): ?>
                <!-- Paso 1: Pedir correo electrónico -->
                <form id="recovery-email-form" method="POST" action="">
                    <input type="hidden" name="step" value="email">
                    
                    <div class="input-group">
                        <label for="email">Correo Electrónico</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="ejemplo@correo.com" value="<?php echo htmlspecialchars($email_value); ?>" required>
                        </div>
                        <small class="error-message" id="email-error"></small>
                    </div>
                    
                    <button type="submit" class="btn-login">Continuar</button>
                    
                    <div class="register-link">
                        <a href="login.php"><i class="fas fa-arrow-left"></i> Volver al Inicio de Sesión</a>
                    </div>
                </form>
                
                <?php else: ?>
                <!-- Paso 2: Responder pregunta secreta -->
                <form id="recovery-question-form" method="POST" action="">
                    <input type="hidden" name="step" value="respuesta">
                    
                    <div class="input-group">
                        <label for="pregunta">Pregunta de Seguridad</label>
                        <div class="question-display">
                            <p><?php echo htmlspecialchars($pregunta_texto); ?></p>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="respuesta">Su Respuesta</label>
                        <div class="input-with-icon">
                            <i class="fas fa-key"></i>
                            <input type="text" id="respuesta" name="respuesta" placeholder="Ingrese su respuesta" required>
                        </div>
                        <small class="error-message" id="respuesta-error"></small>
                    </div>
                    
                    <button type="submit" class="btn-login">Verificar</button>
                    
                    <div class="register-link">
                        <a href="forgot-password.php"><i class="fas fa-arrow-left"></i> Volver</a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Validación del formulario de correo
        const emailForm = document.getElementById('recovery-email-form');
        if (emailForm) {
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('email-error');
            
            emailForm.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Validar correo
                if (emailInput.value.trim() === '') {
                    emailError.textContent = 'El correo electrónico es requerido';
                    isValid = false;
                } else if (!validateEmail(emailInput.value)) {
                    emailError.textContent = 'Ingresa un correo electrónico válido';
                    isValid = false;
                } else {
                    emailError.textContent = '';
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
            // Validar en tiempo real
            emailInput.addEventListener('input', function() {
                if (this.value.trim() === '') {
                    emailError.textContent = 'El correo electrónico es requerido';
                } else if (!validateEmail(this.value)) {
                    emailError.textContent = 'Ingresa un correo electrónico válido';
                } else {
                    emailError.textContent = '';
                }
            });
        }
        
        // Validación del formulario de respuesta
        const questionForm = document.getElementById('recovery-question-form');
        if (questionForm) {
            const respuestaInput = document.getElementById('respuesta');
            const respuestaError = document.getElementById('respuesta-error');
            
            questionForm.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Validar respuesta
                if (respuestaInput.value.trim() === '') {
                    respuestaError.textContent = 'La respuesta es requerida';
                    isValid = false;
                } else {
                    respuestaError.textContent = '';
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
            // Validar en tiempo real
            respuestaInput.addEventListener('input', function() {
                if (this.value.trim() === '') {
                    respuestaError.textContent = 'La respuesta es requerida';
                } else {
                    respuestaError.textContent = '';
                }
            });
        }
        
        // Función para validar correo electrónico
        function validateEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    });
    </script>
</body>
</html>
