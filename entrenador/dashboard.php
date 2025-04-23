<?php
session_start();
require_once '../config/db.php';

// Verificar si el usuario está logueado y es entrenador (rol_id = 2)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 2) {
    header("Location: ../auth/login.php");
    exit();
}

// Obtener información del entrenador
$conn = connectDB();
$userId = $_SESSION['user_id'];

// Obtener datos del entrenador
$query = "SELECT e.*, u.nombre, u.apellido, u.email, u.telefono 
          FROM entrenadores e 
          JOIN usuarios u ON e.usuario_id = u.id 
          WHERE e.usuario_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$entrenador = $stmt->get_result()->fetch_assoc();

// Si no existe el entrenador, redirigir
if (!$entrenador) {
    header("Location: ../auth/logout.php");
    exit();
}

// Obtener estadísticas: total de sesiones de hoy
$hoy = date('Y-m-d');
$query = "SELECT COUNT(*) as total_hoy 
          FROM reservas r 
          JOIN horarios h ON r.horario_id = h.id 
          WHERE h.entrenador_id = ? AND r.fecha = ? AND r.estado = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $entrenador['id'], $hoy);
$stmt->execute();
$sesiones_hoy = $stmt->get_result()->fetch_assoc()['total_hoy'];

// Obtener estadísticas: total de sesiones de la semana
$inicio_semana = date('Y-m-d', strtotime('monday this week'));
$fin_semana = date('Y-m-d', strtotime('sunday this week'));
$query = "SELECT COUNT(*) as total_semana 
          FROM reservas r 
          JOIN horarios h ON r.horario_id = h.id 
          WHERE h.entrenador_id = ? AND r.fecha BETWEEN ? AND ? AND r.estado = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $entrenador['id'], $inicio_semana, $fin_semana);
$stmt->execute();
$sesiones_semana = $stmt->get_result()->fetch_assoc()['total_semana'];

// Obtener estadísticas: total de clientes únicos
$query = "SELECT COUNT(DISTINCT r.usuario_id) as total_clientes 
          FROM reservas r 
          JOIN horarios h ON r.horario_id = h.id 
          WHERE h.entrenador_id = ? AND r.estado = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $entrenador['id']);
$stmt->execute();
$total_clientes = $stmt->get_result()->fetch_assoc()['total_clientes'];

// Obtener próximas sesiones de hoy
$query = "SELECT r.*, h.hora_inicio, h.hora_fin, s.nombre as servicio_nombre,
          CONCAT(u.nombre, ' ', u.apellido) as cliente_nombre, u.telefono as cliente_telefono
          FROM reservas r 
          JOIN horarios h ON r.horario_id = h.id
          JOIN servicios s ON h.servicio_id = s.id
          JOIN usuarios u ON r.usuario_id = u.id
          WHERE h.entrenador_id = ? AND r.fecha = ? AND r.estado = 1
          ORDER BY h.hora_inicio";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $entrenador['id'], $hoy);
$stmt->execute();
$sesiones_hoy_detalle = $stmt->get_result();

// Obtener próximas sesiones de la semana
$query = "SELECT r.*, h.dia_semana, h.hora_inicio, h.hora_fin, s.nombre as servicio_nombre,
          CONCAT(u.nombre, ' ', u.apellido) as cliente_nombre
          FROM reservas r 
          JOIN horarios h ON r.horario_id = h.id
          JOIN servicios s ON h.servicio_id = s.id
          JOIN usuarios u ON r.usuario_id = u.id
          WHERE h.entrenador_id = ? AND r.fecha BETWEEN ? AND ? AND r.estado = 1
          ORDER BY r.fecha, h.hora_inicio";
$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $entrenador['id'], date('Y-m-d'), date('Y-m-d', strtotime('+7 days')));
$stmt->execute();
$proximas_sesiones = $stmt->get_result();

$conn->close();

// Función para convertir número de día a nombre
function getDiaSemana($dia) {
    $dias = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    return $dias[$dia];
}
?>

<?php include '../includes/header.php'; ?>

<div class="container section-padding">
    <div class="row">
        <!-- Sidebar / Menú lateral -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Panel de Entrenador</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="horarios.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-alt me-2"></i> Mis Horarios
                    </a>
                    <a href="sesiones.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i> Mis Sesiones
                    </a>
                    <a href="clientes.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-friends me-2"></i> Mis Clientes
                    </a>
                    <a href="perfil.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-circle me-2"></i> Mi Perfil
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
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <?php if (!empty($entrenador['foto'])): ?>
                                        <img src="<?php echo $entrenador['foto']; ?>" alt="Foto de perfil" class="rounded-circle" width="100">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; font-size: 2.5rem;">
                                            <?php echo substr($entrenador['nombre'], 0, 1); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h2 class="mb-1">Bienvenido, <?php echo $entrenador['nombre']; ?></h2>
                                    <p class="text-muted mb-0"><?php echo $entrenador['especialidad']; ?></p>
                                </div>
                                <div class="text-end">
                                    <p class="mb-1"><i class="fas fa-calendar-day"></i> <?php echo date('d/m/Y'); ?></p>
                                    <p class="mb-0"><i class="fas fa-clock"></i> <?php echo date('H:i'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas rápidas -->
            <div class="row mb-4">
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="dashboard-stat stat-1">
                        <h3><?php echo $sesiones_hoy; ?></h3>
                        <p>Sesiones Hoy</p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="dashboard-stat stat-2">
                        <h3><?php echo $sesiones_semana; ?></h3>
                        <p>Sesiones Esta Semana</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="dashboard-stat stat-3">
                        <h3><?php echo $total_clientes; ?></h3>
                        <p>Clientes Activos</p>
                    </div>
                </div>
            </div>
            
            <!-- Sesiones de Hoy -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Sesiones de Hoy (<?php echo date('d/m/Y'); ?>)</h5>
                            <?php if ($sesiones_hoy > 0): ?>
                                <span class="badge bg-light text-primary"><?php echo $sesiones_hoy; ?> sesiones</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if ($sesiones_hoy_detalle->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Hora</th>
                                                <th>Servicio</th>
                                                <th>Cliente</th>
                                                <th>Contacto</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($sesion = $sesiones_hoy_detalle->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $sesion['hora_inicio'] . ' - ' . $sesion['hora_fin']; ?></td>
                                                    <td><?php echo $sesion['servicio_nombre']; ?></td>
                                                    <td><?php echo $sesion['cliente_nombre']; ?></td>
                                                    <td><?php echo $sesion['cliente_telefono']; ?></td>
                                                    <td>
                                                        <?php if (strtotime($sesion['hora_inicio']) > time()): ?>
                                                            <span class="badge bg-primary">Pendiente</span>
                                                        <?php elseif (strtotime($sesion['hora_fin']) < time()): ?>
                                                            <a href="completar_sesion.php?id=<?php echo $sesion['id']; ?>" class="btn btn-sm btn-success">Completar</a>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">En progreso</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No tienes sesiones programadas para hoy.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Próximas Sesiones -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Próximas Sesiones</h5>
                            <a href="sesiones.php" class="btn btn-sm btn-light">Ver Todas</a>
                        </div>
                        <div class="card-body">
                            <?php if ($proximas_sesiones->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th>Servicio</th>
                                                <th>Cliente</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $count = 0;
                                            while ($sesion = $proximas_sesiones->fetch_assoc()):
                                                if ($count >= 5) break; // Limitar a 5 próximas sesiones
                                                $count++;
                                            ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y', strtotime($sesion['fecha'])); ?></td>
                                                    <td><?php echo $sesion['hora_inicio'] . ' - ' . $sesion['hora_fin']; ?></td>
                                                    <td><?php echo $sesion['servicio_nombre']; ?></td>
                                                    <td><?php echo $sesion['cliente_nombre']; ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No tienes sesiones programadas para los próximos días.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
