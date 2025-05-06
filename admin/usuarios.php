<?php
session_start();
require_once '../config/db.php';

// Verificar si el usuario está logueado y es administrador (rol_id = 1)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../auth/login.php");
    exit();
}

// Conectar a la base de datos
$conn = connectDB();

// Procesar acciones (activar/desactivar usuario)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'activar') {
        $query = "UPDATE usuarios SET estado = 1 WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $mensaje = "Usuario activado correctamente";
    } elseif ($action === 'desactivar') {
        $query = "UPDATE usuarios SET estado = 0 WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $mensaje = "Usuario desactivado correctamente";
    }
}

// Obtener todos los usuarios con información de roles
$query = "SELECT u.*, r.nombre as rol_nombre 
          FROM usuarios u
          JOIN roles r ON u.rol_id = r.id
          ORDER BY u.fecha_registro DESC";
$result = $conn->query($query);

$conn->close();
?>

<?php include '../includes/header.php'; ?>

<div class="container section-padding">
    <div class="row">
        <!-- Sidebar / Menú lateral -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Panel de Administración</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="usuarios.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-users me-2"></i> Usuarios
                    </a>
                    <a href="entrenadores.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-tie me-2"></i> Entrenadores
                    </a>
                    <a href="planes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-clipboard-list me-2"></i> Planes
                    </a>
                    <a href="servicios.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-dumbbell me-2"></i> Servicios
                    </a>
                    <a href="horarios.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-alt me-2"></i> Horarios
                    </a>
                    <a href="reservas.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-check me-2"></i> Reservas
                    </a>
                    <a href="reportes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar me-2"></i> Reportes
                    </a>
                    <a href="configuracion.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i> Configuración
                    </a>
                    <a href="../auth/logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Contenido principal -->
        <div class="col-lg-9">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i> Gestión de Usuarios</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($mensaje)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Rol</th>
                                    <th>Registro</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($usuario = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $usuario['id']; ?></td>
                                            <td><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                            <td><?php echo htmlspecialchars($usuario['telefono'] ?? 'No disponible'); ?></td>
                                            <td>
                                                <span class="badge <?php echo ($usuario['rol_id'] == 1) ? 'bg-danger' : (($usuario['rol_id'] == 2) ? 'bg-primary' : 'bg-success'); ?>">
                                                    <?php echo htmlspecialchars($usuario['rol_nombre']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></td>
                                            <td>
                                                <?php if ($usuario['estado'] == 1): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($usuario['estado'] == 1): ?>
                                                        <a href="?action=desactivar&id=<?php echo $usuario['id']; ?>" class="btn btn-warning" title="Desactivar">
                                                            <i class="fas fa-user-slash"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?action=activar&id=<?php echo $usuario['id']; ?>" class="btn btn-success" title="Activar">
                                                            <i class="fas fa-user-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No hay usuarios registrados</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas de usuarios -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase">Administradores</h6>
                                    <?php
                                    $conn = connectDB();
                                    $query = "SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 1";
                                    $total = $conn->query($query)->fetch_assoc()['total'];
                                    $conn->close();
                                    ?>
                                    <h2 class="mb-0"><?php echo $total; ?></h2>
                                </div>
                                <i class="fas fa-user-shield fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase">Entrenadores</h6>
                                    <?php
                                    $conn = connectDB();
                                    $query = "SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 2";
                                    $total = $conn->query($query)->fetch_assoc()['total'];
                                    $conn->close();
                                    ?>
                                    <h2 class="mb-0"><?php echo $total; ?></h2>
                                </div>
                                <i class="fas fa-user-tie fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase">Clientes</h6>
                                    <?php
                                    $conn = connectDB();
                                    $query = "SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 3";
                                    $total = $conn->query($query)->fetch_assoc()['total'];
                                    $conn->close();
                                    ?>
                                    <h2 class="mb-0"><?php echo $total; ?></h2>
                                </div>
                                <i class="fas fa-users fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
