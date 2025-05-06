<?php
session_start();
require_once '../config/db.php';
require_once '../includes/recovery_functions.php';

// Verificar si el usuario tiene un token válido de restablecimiento
if (!isset($_SESSION['reset_token']) || !isset($_SESSION['reset_user_id'])) {
    header("Location: forgot-password.php");
    exit();
}

$error = '';
$success = '';
$usuario_id = $_SESSION['reset_user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = connectDB();
    
    // Obtener y validar la nueva contraseña
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // Validar contraseñas
    if (empty($password) || empty($password_confirm)) {
        $error = "Por favor, complete todos los campos.";
    } elseif ($password !== $password_confirm) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        // Cambiar la contraseña
        $result = cambiarPassword($conn, $usuario_id, $password);
        
        if ($result === true) {
            // Marcar el token como usado
            $query = "UPDATE reset_tokens SET usado = 1 WHERE usuario_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            
            // Limpiar las variables de sesión de recuperación
            unset($_SESSION['reset_token']);
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['recovery_email']);
            unset($_SESSION['recovery_usuario_id']);
            unset($_SESSION['recovery_id']);
            
            $success = "¡Su contraseña ha sido actualizada exitosamente! Ahora puede iniciar sesión con su nueva contraseña.";
        } else {
            $error = $result;
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
    <title>Las Bestias Pardas - Restablecer Contraseña</title>
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
                <h2>Establecer Nueva Contraseña</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="notification error show">
                        <p><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="notification success show">
                        <p><?php echo $success; ?></p>
                        <div class="login-redirect">
                            <p>Será redirigido a la página de inicio de sesión en <span id="countdown">5</span> segundos.</p>
                        </div>
                    </div>
                    
                    <script>
                        // Redireccionar después de 5 segundos
                        let count = 5;
                        const countdown = document.getElementById('countdown');
                        
                        const interval = setInterval(function() {
                            count--;
                            countdown.textContent = count;
                            
                            if (count <= 0) {
                                clearInterval(interval);
                                window.location.href = 'login.php';
                            }
                        }, 1000);
                    </script>
                <?php else: ?>
                    <form id="reset-password-form" method="POST" action="">
                        <div class="input-group">
                            <label for="password">Nueva Contraseña</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="password" name="password" placeholder="Ingrese su nueva contraseña" required>
                                <i class="fas fa-eye toggle-password" id="toggle-password"></i>
                            </div>
                            <small class="error-message" id="password-error"></small>
                        </div>
                        
                        <div class="input-group">
                            <label for="password_confirm">Confirmar Contraseña</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="password_confirm" name="password_confirm" placeholder="Confirme su nueva contraseña" required>
                                <i class="fas fa-eye toggle-password" id="toggle-password-confirm"></i>
                            </div>
                            <small class="error-message" id="password-confirm-error"></small>
                        </div>
                        
                        <button type="submit" class="btn-login">Actualizar Contraseña</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle para mostrar/ocultar contraseña
        const togglePassword = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');
        const togglePasswordConfirm = document.getElementById('toggle-password-confirm');
        const passwordConfirmInput = document.getElementById('password_confirm');
        
        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            });
        }
        
        if (togglePasswordConfirm && passwordConfirmInput) {
            togglePasswordConfirm.addEventListener('click', function() {
                if (passwordConfirmInput.type === 'password') {
                    passwordConfirmInput.type = 'text';
                    this.classList.remove('fa-eye');
                    this.classList.add('fa-eye-slash');
                } else {
                    passwordConfirmInput.type = 'password';
                    this.classList.remove('fa-eye-slash');
                    this.classList.add('fa-eye');
                }
            });
        }
        
        // Validación del formulario
        const resetForm = document.getElementById('reset-password-form');
        if (resetForm) {
            const passwordError = document.getElementById('password-error');
            const passwordConfirmError = document.getElementById('password-confirm-error');
            
            resetForm.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Validar contraseña
                if (passwordInput.value.trim() === '') {
                    passwordError.textContent = 'La contraseña es requerida';
                    isValid = false;
                } else if (passwordInput.value.length < 6) {
                    passwordError.textContent = 'La contraseña debe tener al menos 6 caracteres';
                    isValid = false;
                } else {
                    passwordError.textContent = '';
                }
                
                // Validar confirmación de contraseña
                if (passwordConfirmInput.value.trim() === '') {
                    passwordConfirmError.textContent = 'La confirmación de contraseña es requerida';
                    isValid = false;
                } else if (passwordConfirmInput.value !== passwordInput.value) {
                    passwordConfirmError.textContent = 'Las contraseñas no coinciden';
                    isValid = false;
                } else {
                    passwordConfirmError.textContent = '';
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
            // Validar contraseña en tiempo real
            passwordInput.addEventListener('input', function() {
                if (this.value.trim() === '') {
                    passwordError.textContent = 'La contraseña es requerida';
                } else if (this.value.length < 6) {
                    passwordError.textContent = 'La contraseña debe tener al menos 6 caracteres';
                } else {
                    passwordError.textContent = '';
                }
                
                // Verificar la coincidencia si ya se ha ingresado la confirmación
                if (passwordConfirmInput.value.trim() !== '') {
                    if (passwordConfirmInput.value !== this.value) {
                        passwordConfirmError.textContent = 'Las contraseñas no coinciden';
                    } else {
                        passwordConfirmError.textContent = '';
                    }
                }
            });
            
            // Validar confirmación de contraseña en tiempo real
            passwordConfirmInput.addEventListener('input', function() {
                if (this.value.trim() === '') {
                    passwordConfirmError.textContent = 'La confirmación de contraseña es requerida';
                } else if (this.value !== passwordInput.value) {
                    passwordConfirmError.textContent = 'Las contraseñas no coinciden';
                } else {
                    passwordConfirmError.textContent = '';
                }
            });
        }
    });
    </script>
</body>
</html>
