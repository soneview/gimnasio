<?php
session_start();
require_once '../config/db.php';

// Redireccionar si ya ha iniciado sesión
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$error = '';

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = connectDB();
    
    $email = sanitize($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // Validar campos
    if (empty($email) || empty($password)) {
        $error = "Por favor, complete todos los campos.";
    } else {
        // Consultar usuario
        $query = "SELECT id, nombre, apellido, email, password, rol_id FROM usuarios WHERE email = ? AND estado = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verificar contraseña
            if (password_verify($password, $user['password'])) {
                // Crear sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nombre'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['rol_id'];
                
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
                $error = "Contraseña incorrecta.";
            }
        } else {
            $error = "No existe una cuenta con este correo electrónico.";
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
                <h2 class="text-center mb-4">Iniciar Sesión</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback">
                            Por favor ingrese un correo electrónico válido.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback">
                            Por favor ingrese su contraseña.
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember">
                        <label class="form-check-label" for="remember">Recordar sesión</label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p>¿No tienes una cuenta? <a href="register.php">Regístrate aquí</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
