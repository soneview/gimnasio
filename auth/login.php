<?php
session_start();
require_once '../config/db.php';

// Función para registrar intentos de inicio de sesión fallidos
function registerLoginAttempt($conn, $email, $success, $error_message = '') {
    // Primero verificamos si la tabla existe
    $tableExists = false;
    $checkTable = $conn->query("SHOW TABLES LIKE 'login_attempts'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $tableExists = true;
    }
    
    // Si la tabla no existe, no intentamos registrar el intento
    if (!$tableExists) {
        return;
    }
    
    // Procedemos si la tabla existe
    $ip = $_SERVER['REMOTE_ADDR'];
    $date = date('Y-m-d H:i:s');
    $success_int = $success ? 1 : 0;
    
    $query = "INSERT INTO login_attempts (email, ip_address, attempt_date, success, error_message) 
              VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("sssss", $email, $ip, $date, $success_int, $error_message);
        $stmt->execute();
    }
}

// Redireccionar si ya ha iniciado sesión
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$error = '';
$email_value = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = connectDB();
    
    // Obtener y sanitizar datos
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $email_value = $email; // Guardar el valor para repoblarlo en el formulario
    $password = $_POST['password'];
    
    // Validar formato de correo
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, ingrese un correo electrónico válido.";
    } 
    // Validar campos vacíos
    elseif (empty($email) || empty($password)) {
        $error = "Por favor, complete todos los campos.";
    } 
    else {
        // Prevenir ataques de fuerza bruta - comprobar intentos recientes
        $check_attempts = false;
        
        if ($check_attempts) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $query = "SELECT COUNT(*) as attempt_count FROM login_attempts 
                     WHERE ip_address = ? AND success = 0 AND attempt_date > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";
            $stmt = $conn->prepare($query);
            
            if ($stmt) {
                $stmt->bind_param("s", $ip);
                $stmt->execute();
                $result = $stmt->get_result();
                $attempts = $result->fetch_assoc()['attempt_count'];
                
                if ($attempts >= 5) {
                    $error = "Demasiados intentos fallidos. Por favor, inténtelo de nuevo en 15 minutos.";
                    registerLoginAttempt($conn, $email, false, "Bloqueo por múltiples intentos");
                    $conn->close();
                    // Puedes agregar un retraso para dificultar los ataques
                    sleep(1);
                }
            }
        }
        
        if (empty($error)) {
            // Consultar usuario
            $query = "SELECT id, nombre, apellido, email, password, rol_id, estado FROM usuarios WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                
                // Verificar si la cuenta está activa
                if ($user['estado'] != 1) {
                    $error = "Esta cuenta no está activa. Por favor, contacte con soporte.";
                    registerLoginAttempt($conn, $email, false, "Cuenta inactiva");
                }
                // Verificar contraseña
                elseif (password_verify($password, $user['password'])) {
                    // Crear sesión segura
                    session_regenerate_id(true); // Previene ataques de fijación de sesión
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nombre'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['rol_id'];
                    $_SESSION['last_activity'] = time(); // Para controlar tiempo de inactividad
                    
                    // Registrar inicio de sesión exitoso
                    registerLoginAttempt($conn, $email, true);
                    
                    // Redireccionar según el rol
                    if ($user['rol_id'] == 1) {
                        header("Location: ../admin/dashboard.php");
                    } elseif ($user['rol_id'] == 2) {
                        header("Location: ../entrenador/dashboard.php");
                    } else {
                        header("Location: ../cliente/dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Credenciales incorrectas. Por favor, verifica tu correo y contraseña.";
                    registerLoginAttempt($conn, $email, false, "Contraseña incorrecta");
                    // Respuesta genérica para prevenir enumeración de usuarios
                }
            } else {
                $error = "Credenciales incorrectas. Por favor, verifica tu correo y contraseña.";
                registerLoginAttempt($conn, $email, false, "Usuario no existe");
                // Respuesta genérica para prevenir enumeración de usuarios
            }
        }
    }
    
    $conn->close();
    
    // Agregar retraso para prevenir timing attacks
    if (!empty($error)) {
        usleep(rand(100000, 300000)); // 100-300ms delay
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Las Bestias Pardas - Iniciar Sesión</title>
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
                <h2>Iniciar Sesión</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="notification error show">
                        <p><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>
                
                <form id="login-form" method="POST" action="">
                    <div class="input-group">
                        <label for="email">Correo Electrónico</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="ejemplo@correo.com" value="<?php echo htmlspecialchars($email_value); ?>" required>
                        </div>
                        <small class="error-message" id="email-error"></small>
                    </div>
                    
                    <div class="input-group">
                        <label for="password">Contraseña</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
                            <i class="fas fa-eye toggle-password" id="toggle-password"></i>
                        </div>
                        <small class="error-message" id="password-error"></small>
                    </div>
                    
                    <div class="forgot-password">
                        <a href="#">¿Olvidaste tu contraseña?</a>
                    </div>
                    
                    <button type="submit" class="btn-login">Iniciar Sesión</button>
                    
                    <div class="register-link">
                        ¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="notification" class="notification">
        <p id="notification-message"></p>
    </div>
    <script src="../assets/js/auth-script.js"></script>
</body>
</html>
