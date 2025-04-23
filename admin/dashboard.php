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

// Obtener estadísticas del sistema
// Total de usuarios
$query = "SELECT COUNT(*) as total FROM usuarios WHERE estado = 1";
$totalUsuarios = $conn->query($query)->fetch_assoc()['total'];

// Total de clientes
$query = "SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 3 AND estado = 1";
$totalClientes = $conn->query($query)->fetch_assoc()['total'];

// Total de entrenadores
$query = "SELECT COUNT(*) as total FROM usuarios WHERE rol_id = 2 AND estado = 1";
$totalEntrenadores = $conn->query($query)->fetch_assoc()['total'];

// Total de planes
$query = "SELECT COUNT(*) as total FROM planes WHERE estado = 1";
$totalPlanes = $conn->query($query)->fetch_assoc()['total'];

// Total de servicios
$query = "SELECT COUNT(*) as total FROM servicios WHERE estado = 1";
$totalServicios = $conn->query($query)->fetch_assoc()['total'];

// Total de reservas activas
$query = "SELECT COUNT(*) as total FROM reservas WHERE estado = 1 AND fecha >= CURDATE()";
$totalReservas = $conn->query($query)->fetch_assoc()['total'];

// Obtener últimas reservas
$query = "SELECT r.*, 
          CONCAT(u.nombre, ' ', u.apellido) as cliente_nombre,
          s.nombre as servicio_nombre,
          CONCAT(ue.nombre, ' ', ue.apellido) as entrenador_nombre
          FROM reservas r
          JOIN usuarios u ON r.usuario_id = u.id
          JOIN horarios h ON r.horario_id = h.id
          JOIN servicios s ON h.servicio_id = s.id
          JOIN entrenadores e ON h.entrenador_id = e.id
          JOIN usuarios ue ON e.usuario_id = ue.id
          WHERE r.estado = 1
          ORDER BY r.fecha_creacion DESC
          LIMIT 5";
$ultimasReservas = $conn->query($query);

// Obtener últimos usuarios registrados
$query = "SELECT u.*, r.nombre as rol_nombre
          FROM usuarios u
          JOIN roles r ON u.rol_id = r.id
          ORDER BY u.fecha_registro DESC
          LIMIT 5";
$ultimosUsuarios = $conn->query($query);

// Calcular ingresos estimados (suma del precio de todas las suscripciones activas)
$query = "SELECT SUM(p.precio) as total 
          FROM suscripciones s
          JOIN planes p ON s.plan_id = p.id
          WHERE s.estado = 1 AND s.fecha_fin >= CURDATE()";
$ingresosEstimados = $conn->query($query)->fetch_assoc()['total'] ?: 0;

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
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="usuarios.php" class="list-group-item list-group-item-action">
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
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h2>Bienvenido al Panel de Administración</h2>
                            <p class="text-muted">Gestiona todos los aspectos de tu gimnasio desde este panel centralizado.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="dashboard-stat stat-1">
                        <h3><?php echo $totalClientes; ?></h3>
                        <p>Clientes</p>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="dashboard-stat stat-2">
                        <h3><?php echo $totalEntrenadores; ?></h3>
                        <p>Entrenadores</p>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="dashboard-stat stat-3">
                        <h3><?php echo $totalReservas; ?></h3>
                        <p>Reservas Activas</p>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="dashboard-stat stat-4">
                        <h3>$<?php echo number_format($ingresosEstimados, 2); ?></h3>
                        <p>Ingresos Estimados</p>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Estadísticas</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Total Usuarios
                                    <span class="badge bg-primary rounded-pill"><?php echo $totalUsuarios; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Clientes
                                    <span class="badge bg-primary rounded-pill"><?php echo $totalClientes; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Entrenadores
                                    <span class="badge bg-primary rounded-pill"><?php echo $totalEntrenadores; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Planes Activos
                                    <span class="badge bg-primary rounded-pill"><?php echo $totalPlanes; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Servicios
                                    <span class="badge bg-primary rounded-pill"><?php echo $totalServicios; ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Últimas Reservas</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($ultimasReservas->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Cliente</th>
                                                <th>Servicio</th>
                                                <th>Entrenador</th>
                                                <th>Fecha</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($reserva = $ultimasReservas->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $reserva['cliente_nombre']; ?></td>
                                                    <td><?php echo $reserva['servicio_nombre']; ?></td>
                                                    <td><?php echo $reserva['entrenador_nombre']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($reserva['fecha'])); ?></td>
                                                    <td>
                                                        <?php if ($reserva['estado'] == 1): ?>
                                                            <span class="badge bg-success">Activa</span>
                                                        <?php elseif ($reserva['estado'] == 2): ?>
                                                            <span class="badge bg-danger">Cancelada</span>
                                                        <?php elseif ($reserva['estado'] == 3): ?>
                                                            <span class="badge bg-info">Completada</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end">
                                    <a href="reservas.php" class="btn btn-sm btn-outline-success">Ver todas las reservas</a>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No hay reservas recientes.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Últimos Usuarios Registrados -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Últimos Usuarios Registrados</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($ultimosUsuarios->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Nombre</th>
                                                <th>Email</th>
                                                <th>Rol</th>
                                                <th>Fecha Registro</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($usuario = $ultimosUsuarios->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $usuario['id']; ?></td>
                                                    <td><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></td>
                                                    <td><?php echo $usuario['email']; ?></td>
                                                    <td><?php echo $usuario['rol_nombre']; ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></td>
                                                    <td>
                                                        <?php if ($usuario['estado'] == 1): ?>
                                                            <span class="badge bg-success">Activo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Inactivo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end">
                                    <a href="usuarios.php" class="btn btn-sm btn-outline-info">Ver todos los usuarios</a>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No hay usuarios registrados recientemente.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
