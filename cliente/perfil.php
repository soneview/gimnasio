<?php
session_start();
require_once '../config/db.php';

// Verificar si el usuario está logueado y es cliente (rol_id = 3)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 3) {
    header("Location: ../auth/login.php");
    exit();
}

// Obtener información del usuario
$conn = connectDB();
$userId = $_SESSION['user_id'];

// Crear la tabla clientes si no existe
$sql = "CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    fecha_nacimiento DATE,
    genero CHAR(1),
    altura DECIMAL(3,2),
    peso DECIMAL(5,2),
    objetivo VARCHAR(100),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$conn->query($sql);

// Obtener datos del usuario
$query = "SELECT u.* FROM usuarios u WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Obtener datos del cliente (si existen)
$query = "SELECT * FROM clientes WHERE usuario_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();

// Combinar los datos
if ($cliente) {
    $user = array_merge($user, $cliente);
}

// Procesar la actualización del perfil
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    try {
        // Datos básicos del usuario
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $email = trim($_POST['email']);
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $fecha_nacimiento = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : NULL;
        $genero = !empty($_POST['genero']) ? $_POST['genero'] : NULL;
        
        // Datos físicos
        $altura = !empty($_POST['altura']) ? floatval($_POST['altura']) : NULL;
        $peso = !empty($_POST['peso']) ? floatval($_POST['peso']) : NULL;
        $objetivo = trim($_POST['objetivo'] ?? '');
        
        // Actualizar tabla usuarios
        $query = "UPDATE usuarios SET nombre = ?, apellido = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssi", $nombre, $apellido, $email, $userId);
        $resultado1 = $stmt->execute();
        
        // Verificar si ya existe un registro en la tabla clientes
        $query = "SELECT COUNT(*) as count FROM clientes WHERE usuario_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            // Actualizar tabla clientes
            $query = "UPDATE clientes SET telefono = ?, direccion = ?, fecha_nacimiento = ?, 
                      genero = ?, altura = ?, peso = ?, objetivo = ? WHERE usuario_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssddssi", $telefono, $direccion, $fecha_nacimiento, $genero, $altura, $peso, $objetivo, $userId);
        } else {
            // Insertar en tabla clientes
            $query = "INSERT INTO clientes (usuario_id, telefono, direccion, fecha_nacimiento, genero, altura, peso, objetivo) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isssdds", $userId, $telefono, $direccion, $fecha_nacimiento, $genero, $altura, $peso, $objetivo);
        }
        
        $resultado2 = $stmt->execute();
        
        if ($resultado1) {
            $mensaje = "¡Perfil actualizado con éxito!";
            $tipo_mensaje = "success";
            
            // Actualizar los datos en la sesión
            $_SESSION['user_name'] = $nombre;
            
            // Recargar los datos del usuario
            header("Location: perfil.php");
            exit();
        } else {
            $mensaje = "Error al actualizar el perfil. Por favor, inténtalo de nuevo.";
            $tipo_mensaje = "danger";
        }
    } catch (Exception $e) {
        $mensaje = "Ha ocurrido un error al actualizar el perfil. Por favor, inténtalo de nuevo.";
        $tipo_mensaje = "danger";
    }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {
    $password_actual = $_POST['password_actual'];
    $password_nuevo = $_POST['password_nuevo'];
    $password_confirmar = $_POST['password_confirmar'];
    
    // Verificar que la contraseña actual sea correcta
    $query = "SELECT password FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (password_verify($password_actual, $result['password'])) {
        // Verificar que las nuevas contraseñas coincidan
        if ($password_nuevo === $password_confirmar) {
            // Actualizar la contraseña
            $password_hash = password_hash($password_nuevo, PASSWORD_DEFAULT);
            $query = "UPDATE usuarios SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $password_hash, $userId);
            
            if ($stmt->execute()) {
                $mensaje = "¡Contraseña actualizada con éxito!";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar la contraseña. Por favor, inténtalo de nuevo.";
                $tipo_mensaje = "danger";
            }
        } else {
            $mensaje = "Las nuevas contraseñas no coinciden.";
            $tipo_mensaje = "warning";
        }
    } else {
        $mensaje = "La contraseña actual es incorrecta.";
        $tipo_mensaje = "warning";
    }
}

$conn->close();
?>

<?php include '../includes/header.php'; ?>

<div class="container section-padding">
    <div class="row">
        <!-- Sidebar / Menú lateral -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Panel de Cliente</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="reservas.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-alt me-2"></i> Mis Reservas
                    </a>
                    <a href="horarios.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-clock me-2"></i> Horarios Disponibles
                    </a>
                    <a href="suscripciones.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-credit-card me-2"></i> Mis Suscripciones
                    </a>
                    <a href="perfil.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-user me-2"></i> Mi Perfil
                    </a>
                    <a href="../auth/logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Contenido principal -->
        <div class="col-lg-9">
            <!-- Mensaje de alerta -->
            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title">Mi Perfil</h2>
                            <p class="card-text">Gestiona tu información personal y preferencias.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Información del perfil -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card profile-card h-100">
                        <div class="card-body text-center">
                            <div class="profile-image mb-3">
                                <img src="<?php echo !empty($user['foto']) ? '../' . $user['foto'] : '../assets/img/default-avatar.jpg'; ?>" alt="Foto de perfil" class="img-fluid rounded-circle">
                            </div>
                            <h4><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></h4>
                            <p class="text-muted">
                                <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?>
                            </p>
                            <p class="text-muted">
                                <i class="fas fa-phone me-2"></i><?php echo !empty($user['telefono']) ? htmlspecialchars($user['telefono']) : 'No especificado'; ?>
                            </p>
                            <p class="text-muted">
                                <i class="fas fa-calendar me-2"></i>Miembro desde: <?php echo date('d/m/Y', strtotime($user['fecha_registro'])); ?>
                            </p>
                            <button type="button" class="btn btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#cambiarFotoModal">
                                <i class="fas fa-camera me-2"></i>Cambiar foto
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <!-- Tabs de navegación -->
                    <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="datos-tab" data-bs-toggle="tab" data-bs-target="#datos" type="button" role="tab" aria-controls="datos" aria-selected="true">
                                <i class="fas fa-user-edit me-2"></i>Datos Personales
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="seguridad-tab" data-bs-toggle="tab" data-bs-target="#seguridad" type="button" role="tab" aria-controls="seguridad" aria-selected="false">
                                <i class="fas fa-lock me-2"></i>Seguridad
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="fisico-tab" data-bs-toggle="tab" data-bs-target="#fisico" type="button" role="tab" aria-controls="fisico" aria-selected="false">
                                <i class="fas fa-dumbbell me-2"></i>Datos Físicos
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Contenido de los tabs -->
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Tab Datos Personales -->
                        <div class="tab-pane fade show active" id="datos" role="tabpanel" aria-labelledby="datos-tab">
                            <div class="card">
                                <div class="card-body">
                                    <form action="perfil.php" method="post">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="nombre" class="form-label">Nombre</label>
                                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="apellido" class="form-label">Apellido</label>
                                                <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo htmlspecialchars($user['apellido']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="telefono" class="form-label">Teléfono</label>
                                            <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($user['telefono'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="direccion" class="form-label">Dirección</label>
                                            <textarea class="form-control" id="direccion" name="direccion" rows="2"><?php echo htmlspecialchars($user['direccion'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo $user['fecha_nacimiento'] ?? ''; ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="genero" class="form-label">Género</label>
                                                <select class="form-select" id="genero" name="genero">
                                                    <option value="">Seleccionar...</option>
                                                    <option value="M" <?php echo ($user['genero'] ?? '') === 'M' ? 'selected' : ''; ?>>Masculino</option>
                                                    <option value="F" <?php echo ($user['genero'] ?? '') === 'F' ? 'selected' : ''; ?>>Femenino</option>
                                                    <option value="O" <?php echo ($user['genero'] ?? '') === 'O' ? 'selected' : ''; ?>>Otro</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="objetivo" class="form-label">Objetivo Fitness</label>
                                            <select class="form-select" id="objetivo" name="objetivo">
                                                <option value="">Seleccionar...</option>
                                                <option value="Pérdida de peso" <?php echo ($user['objetivo'] ?? '') === 'Pérdida de peso' ? 'selected' : ''; ?>>Pérdida de peso</option>
                                                <option value="Ganancia muscular" <?php echo ($user['objetivo'] ?? '') === 'Ganancia muscular' ? 'selected' : ''; ?>>Ganancia muscular</option>
                                                <option value="Tonificación" <?php echo ($user['objetivo'] ?? '') === 'Tonificación' ? 'selected' : ''; ?>>Tonificación</option>
                                                <option value="Resistencia" <?php echo ($user['objetivo'] ?? '') === 'Resistencia' ? 'selected' : ''; ?>>Resistencia</option>
                                                <option value="Bienestar general" <?php echo ($user['objetivo'] ?? '') === 'Bienestar general' ? 'selected' : ''; ?>>Bienestar general</option>
                                            </select>
                                        </div>
                                        <button type="submit" name="actualizar_perfil" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Guardar Cambios
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab Seguridad -->
                        <div class="tab-pane fade" id="seguridad" role="tabpanel" aria-labelledby="seguridad-tab">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Cambiar Contraseña</h5>
                                    <form action="perfil.php" method="post">
                                        <div class="mb-3">
                                            <label for="password_actual" class="form-label">Contraseña Actual</label>
                                            <input type="password" class="form-control" id="password_actual" name="password_actual" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="password_nuevo" class="form-label">Nueva Contraseña</label>
                                            <input type="password" class="form-control" id="password_nuevo" name="password_nuevo" required>
                                            <div class="form-text">La contraseña debe tener al menos 8 caracteres.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="password_confirmar" class="form-label">Confirmar Nueva Contraseña</label>
                                            <input type="password" class="form-control" id="password_confirmar" name="password_confirmar" required>
                                        </div>
                                        <button type="submit" name="cambiar_password" class="btn btn-primary">
                                            <i class="fas fa-key me-2"></i>Cambiar Contraseña
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab Datos Físicos -->
                        <div class="tab-pane fade" id="fisico" role="tabpanel" aria-labelledby="fisico-tab">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Datos Físicos y Objetivos</h5>
                                    <form action="perfil.php" method="post">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="altura" class="form-label">Altura (m)</label>
                                                <input type="number" step="0.01" min="0" max="3" class="form-control" id="altura" name="altura" value="<?php echo htmlspecialchars($user['altura'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="peso" class="form-label">Peso (kg)</label>
                                                <input type="number" step="0.1" min="0" max="300" class="form-control" id="peso" name="peso" value="<?php echo htmlspecialchars($user['peso'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($user['altura']) && !empty($user['peso'])): 
                                            $imc = $user['peso'] / ($user['altura'] * $user['altura']);
                                            $categoria = '';
                                            $color = '';
                                            
                                            if ($imc < 18.5) {
                                                $categoria = 'Bajo peso';
                                                $color = 'text-warning';
                                            } elseif ($imc >= 18.5 && $imc < 25) {
                                                $categoria = 'Peso normal';
                                                $color = 'text-success';
                                            } elseif ($imc >= 25 && $imc < 30) {
                                                $categoria = 'Sobrepeso';
                                                $color = 'text-warning';
                                            } else {
                                                $categoria = 'Obesidad';
                                                $color = 'text-danger';
                                            }
                                        ?>
                                        <div class="mb-4">
                                            <div class="card bg-light">
                                                <div class="card-body">
                                                    <h6 class="card-title">Tu Índice de Masa Corporal (IMC)</h6>
                                                    <div class="d-flex align-items-center">
                                                        <div class="display-6 me-3 <?php echo $color; ?>"><?php echo number_format($imc, 1); ?></div>
                                                        <div>
                                                            <p class="mb-0 <?php echo $color; ?>"><strong><?php echo $categoria; ?></strong></p>
                                                            <small class="text-muted">Este valor es solo referencial y no sustituye una evaluación médica profesional.</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="mb-3">
                                            <label for="objetivo" class="form-label">Objetivo Fitness</label>
                                            <select class="form-select" id="objetivo" name="objetivo">
                                                <option value="">Seleccionar...</option>
                                                <option value="Pérdida de peso" <?php echo ($user['objetivo'] ?? '') === 'Pérdida de peso' ? 'selected' : ''; ?>>Pérdida de peso</option>
                                                <option value="Ganancia muscular" <?php echo ($user['objetivo'] ?? '') === 'Ganancia muscular' ? 'selected' : ''; ?>>Ganancia muscular</option>
                                                <option value="Tonificación" <?php echo ($user['objetivo'] ?? '') === 'Tonificación' ? 'selected' : ''; ?>>Tonificación</option>
                                                <option value="Resistencia" <?php echo ($user['objetivo'] ?? '') === 'Resistencia' ? 'selected' : ''; ?>>Resistencia</option>
                                                <option value="Bienestar general" <?php echo ($user['objetivo'] ?? '') === 'Bienestar general' ? 'selected' : ''; ?>>Bienestar general</option>
                                            </select>
                                        </div>
                                        
                                        <button type="submit" name="actualizar_perfil" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Guardar Cambios
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para cambiar foto de perfil -->
<div class="modal fade" id="cambiarFotoModal" tabindex="-1" aria-labelledby="cambiarFotoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cambiarFotoModalLabel">Cambiar Foto de Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="actualizar_foto.php" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="foto" class="form-label">Selecciona una nueva foto</label>
                        <input class="form-control" type="file" id="foto" name="foto" accept="image/*" required>
                        <div class="form-text">Formatos aceptados: JPG, PNG. Tamaño máximo: 2MB.</div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Subir Foto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Estilos adicionales -->
<style>
.profile-card {
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.profile-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.profile-image {
    width: 120px;
    height: 120px;
    margin: 0 auto;
    overflow: hidden;
    border-radius: 50%;
    border: 3px solid #f8f9fa;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.profile-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.nav-tabs .nav-link {
    color: #495057;
    font-weight: 500;
}

.nav-tabs .nav-link.active {
    color: #007bff;
    font-weight: 600;
}

.tab-content {
    padding-top: 1rem;
}
</style>

<?php include '../includes/footer.php'; ?>
