<?php
session_start();
require_once '../config/db.php';

// Redireccionar si ya ha iniciado sesión
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

// Procesar formulario de registro
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = connectDB();
    
    $nombre = sanitize($conn, $_POST['nombre']);
    $apellido = sanitize($conn, $_POST['apellido']);
    $email = sanitize($conn, $_POST['email']);
    $telefono = sanitize($conn, $_POST['telefono']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    
    // Validar campos
    if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = "Por favor, complete todos los campos obligatorios.";
    } elseif ($password !== $password_confirm) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } else {
        // Verificar si el correo ya existe
        $query = "SELECT id FROM usuarios WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Este correo electrónico ya está registrado.";
        } else {
            // Hash de la contraseña
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar nuevo usuario (rol 3 = Cliente)
            $query = "INSERT INTO usuarios (nombre, apellido, email, password, telefono, rol_id) VALUES (?, ?, ?, ?, ?, 3)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssss", $nombre, $apellido, $email, $hashed_password, $telefono);
            
            if ($stmt->execute()) {
                $success = "Registro exitoso. Ahora puede iniciar sesión.";
            } else {
                $error = "Error al registrar usuario: " . $conn->error;
            }
        }
    }
    
    $conn->close();
}
?>

<?php include '../includes/header.php'; ?>

<div class="container section-padding">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="auth-form">
                <h2 class="text-center mb-4">Crear Cuenta</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                            <div class="invalid-feedback">
                                Por favor ingrese su nombre.
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="apellido" class="form-label">Apellido</label>
                            <input type="text" class="form-control" id="apellido" name="apellido" required>
                            <div class="invalid-feedback">
                                Por favor ingrese su apellido.
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback">
                            Por favor ingrese un correo electrónico válido.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono">
                        <div class="invalid-feedback">
                            Por favor ingrese un número de teléfono válido.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        <div class="invalid-feedback">
                            La contraseña debe tener al menos 6 caracteres.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">Confirmar Contraseña</label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="6">
                        <div class="invalid-feedback">
                            Las contraseñas deben coincidir.
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" required>
                        <label class="form-check-label" for="terms">Acepto los <a href="../terminos.php">términos y condiciones</a></label>
                        <div class="invalid-feedback">
                            Debe aceptar los términos y condiciones para continuar.
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Registrarse</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
