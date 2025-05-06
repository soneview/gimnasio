<?php
session_start();
require_once '../config/db.php';

// Redireccionar si ya ha iniciado sesión
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Variables para almacenar errores y valores del formulario
$error = '';
$success = '';
$form_data = [
    'nombre' => '',
    'apellido' => '',
    'email' => '',
    'telefono' => ''
];

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = connectDB();
    
    // Sanitización de datos
    $nombre = filter_var(trim($_POST['nombre']), FILTER_SANITIZE_STRING);
    $apellido = filter_var(trim($_POST['apellido']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $telefono = filter_var(trim($_POST['telefono']), FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // Guardar datos del formulario para repoblar en caso de error
    $form_data = [
        'nombre' => $nombre,
        'apellido' => $apellido,
        'email' => $email,
        'telefono' => $telefono
    ];
    
    // Validar formato de correo
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, ingrese un correo electrónico válido.";
    }
    // Validar campos requeridos
    elseif (empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = "Por favor, complete todos los campos obligatorios.";
    } 
    // Validar longitud del nombre y apellido
    elseif (strlen($nombre) < 2 || strlen($apellido) < 2) {
        $error = "El nombre y apellido deben tener al menos 2 caracteres.";
    }
    // Validar coincidencia de contraseñas
    elseif ($password !== $password_confirm) {
        $error = "Las contraseñas no coinciden.";
    } 
    // Validar complejidad de la contraseña
    elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } 
    // Validar formato de teléfono (opcional)
    elseif (!empty($telefono) && !preg_match('/^[0-9]{10}$/', $telefono)) {
        $error = "El formato del teléfono no es válido. Debe contener 10 dígitos.";
    } 
    else {
        // Verificar si el correo ya existe
        $query = "SELECT id FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Este correo electrónico ya está registrado.";
        } else {
            try {
                // Iniciar transacción
                $conn->begin_transaction();
                
                // Hash de la contraseña con un algoritmo fuerte
                $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                
                // Establecer valor por defecto para estado (1 = activo)
                $estado = 1;
                
                // Fecha de registro
                $fecha_registro = date('Y-m-d H:i:s');
                
                // Insertar nuevo usuario (rol 3 = Cliente)
                $query = "INSERT INTO usuarios (nombre, apellido, email, password, telefono, rol_id, estado, fecha_registro) 
                          VALUES (?, ?, ?, ?, ?, 3, ?, ?)";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssssss", $nombre, $apellido, $email, $hashed_password, $telefono, $estado, $fecha_registro);
                
                if ($stmt->execute()) {
                    // Obtener el ID del usuario recién creado
                    $user_id = $conn->insert_id;
                    
                    // Aquí podrías crear un perfil o registros adicionales si es necesario
                    // Por ejemplo, crear un perfil de cliente con datos adicionales
                    
                    // Confirmar la transacción
                    $conn->commit();
                    
                    // Mensaje de éxito
                    $success = "¡Registro exitoso! Ahora puede iniciar sesión con sus credenciales.";
                    
                    // Limpiar los datos del formulario después del registro exitoso
                    $form_data = [
                        'nombre' => '',
                        'apellido' => '',
                        'email' => '',
                        'telefono' => ''
                    ];
                } else {
                    throw new Exception("Error al insertar usuario");
                }
            } catch (Exception $e) {
                // Revertir cambios en caso de error
                $conn->rollback();
                $error = "Error al registrar usuario: " . $e->getMessage();
                
                // Para desarrollo, mostrar el error específico
                if (isset($conn->error) && !empty($conn->error)) {
                    $error .= " - " . $conn->error;
                }
                
                // Para producción, mensaje genérico
                // $error = "Ha ocurrido un error durante el registro. Por favor, inténtelo de nuevo más tarde.";
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
    <title>Las Bestias Pardas - Crear Cuenta</title>
    <link rel="stylesheet" href="../assets/css/auth-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="login-container register-container">
            <div class="image-container">
                <div class="overlay"></div>
            </div>
            <div class="form-container">
                <div class="logo">
                    <i class="fas fa-dumbbell"></i>
                    <h1>Las Bestias Pardas</h1>
                </div>
                <h2>Crear Cuenta</h2>
                
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
                
                <form id="register-form" method="POST" action="">
                    <div class="row-inputs">
                        <div class="input-group half">
                            <label for="nombre">Nombre</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" id="nombre" name="nombre" placeholder="Tu nombre" value="<?php echo htmlspecialchars($form_data['nombre']); ?>" required>
                            </div>
                            <small class="error-message" id="nombre-error"></small>
                        </div>
                        
                        <div class="input-group half">
                            <label for="apellido">Apellido</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" id="apellido" name="apellido" placeholder="Tu apellido" value="<?php echo htmlspecialchars($form_data['apellido']); ?>" required>
                            </div>
                            <small class="error-message" id="apellido-error"></small>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="email">Correo Electrónico</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="ejemplo@correo.com" value="<?php echo htmlspecialchars($form_data['email']); ?>" required>
                        </div>
                        <small class="error-message" id="email-error"></small>
                    </div>
                    
                    <div class="input-group">
                        <label for="telefono">Teléfono</label>
                        <div class="input-with-icon">
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="telefono" name="telefono" placeholder="Tu teléfono" value="<?php echo htmlspecialchars($form_data['telefono']); ?>">
                        </div>
                        <small class="error-message" id="telefono-error"></small>
                    </div>
                    
                    <div class="input-group">
                        <label for="password">Contraseña</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Crea tu contraseña" required minlength="6">
                            <i class="fas fa-eye toggle-password" id="toggle-password"></i>
                        </div>
                        <small class="error-message" id="password-error"></small>
                    </div>
                    
                    <div class="input-group">
                        <label for="password_confirm">Confirmar Contraseña</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password_confirm" name="password_confirm" placeholder="Confirma tu contraseña" required minlength="6">
                            <i class="fas fa-eye toggle-password" id="toggle-password-confirm"></i>
                        </div>
                        <small class="error-message" id="password-confirm-error"></small>
                    </div>
                    
                    <div class="terms-checkbox">
                        <label>
                            <input type="checkbox" id="terms" name="terms" required>
                            <span>Acepto los <a href="../terminos.php">términos y condiciones</a></span>
                        </label>
                        <small class="error-message" id="terms-error"></small>
                    </div>
                    
                    <button type="submit" class="btn-login">Registrarse</button>
                    
                    <div class="register-link">
                        ¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a>
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
