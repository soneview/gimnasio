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

$query = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Obtener suscripciones activas
$query = "SELECT s.*, p.nombre as plan_nombre, p.descripcion as plan_descripcion, p.precio 
          FROM suscripciones s 
          JOIN planes p ON s.plan_id = p.id 
          WHERE s.usuario_id = ? AND s.estado = 1 AND s.fecha_fin >= CURDATE() 
          ORDER BY s.fecha_fin DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$suscripciones = $stmt->get_result();

// Obtener reservas próximas
$query = "SELECT r.*, h.dia_semana, h.hora_inicio, h.hora_fin, 
          s.nombre as servicio_nombre, s.duracion_minutos,
          CONCAT(u.nombre, ' ', u.apellido) as entrenador_nombre
          FROM reservas r 
          JOIN horarios h ON r.horario_id = h.id
          JOIN servicios s ON h.servicio_id = s.id
          JOIN entrenadores e ON h.entrenador_id = e.id
          JOIN usuarios u ON e.usuario_id = u.id
          WHERE r.usuario_id = ? AND r.estado = 1 AND r.fecha >= CURDATE()
          ORDER BY r.fecha, h.hora_inicio";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$reservas = $stmt->get_result();

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
                    <h5 class="mb-0">Panel de Cliente</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action active">
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
                    <a href="perfil.php" class="list-group-item list-group-item-action">
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
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title">Bienvenido, <?php echo $user['nombre']; ?></h2>
                            <p class="card-text">Este es tu panel de control donde podrás gestionar tus reservas, suscripciones y perfil.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas rápidas -->
            <div class="row mb-4">
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="dashboard-stat stat-1">
                        <h3>
                            <?php
                            $contadorReservas = $reservas->num_rows;
                            echo $contadorReservas;
                            $reservas->data_seek(0); // Resetear el puntero
                            ?>
                        </h3>
                        <p>Reservas Activas</p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4 mb-md-0">
                    <div class="dashboard-stat stat-2">
                        <h3>
                            <?php
                            $contadorSuscripciones = $suscripciones->num_rows;
                            echo $contadorSuscripciones;
                            $suscripciones->data_seek(0); // Resetear el puntero
                            ?>
                        </h3>
                        <p>Suscripciones Activas</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="dashboard-stat stat-3">
                        <h3>
                            <?php
                            // Días desde el registro
                            $fecha_registro = new DateTime($user['fecha_registro']);
                            $hoy = new DateTime();
                            $intervalo = $fecha_registro->diff($hoy);
                            echo $intervalo->days;
                            ?>
                        </h3>
                        <p>Días como miembro</p>
                    </div>
                </div>
            </div>
            
            <!-- Próximas Reservas -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Próximas Reservas</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($reservas->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Servicio</th>
                                                <th>Entrenador</th>
                                                <th>Horario</th>
                                                <th>Duración</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($reserva = $reservas->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y', strtotime($reserva['fecha'])); ?></td>
                                                    <td><?php echo $reserva['servicio_nombre']; ?></td>
                                                    <td><?php echo $reserva['entrenador_nombre']; ?></td>
                                                    <td><?php echo $reserva['hora_inicio'] . ' - ' . $reserva['hora_fin']; ?></td>
                                                    <td><?php echo $reserva['duracion_minutos'] . ' min'; ?></td>
                                                    <td>
                                                        <a href="cancelar_reserva.php?id=<?php echo $reserva['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que deseas cancelar esta reserva?')">
                                                            <i class="fas fa-times"></i> Cancelar
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No tienes reservas próximas. <a href="horarios.php">Reserva ahora</a>.</p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer text-end">
                            <a href="reservas.php" class="btn btn-outline-primary btn-sm">Ver todas las reservas</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Suscripciones Activas -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i> Suscripciones Activas</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($suscripciones->num_rows > 0): ?>
                                <div class="row">
                                    <?php while ($suscripcion = $suscripciones->fetch_assoc()): 
                                        $diasRestantes = ceil((strtotime($suscripcion['fecha_fin']) - time()) / (60 * 60 * 24));
                                    ?>
                                    <div class="col-md-12 mb-3">
                                        <div class="subscription-card p-3 border rounded">
                                            <div class="row align-items-center">
                                                <div class="col-md-3">
                                                    <h5 class="mb-0"><?php echo htmlspecialchars($suscripcion['plan_nombre']); ?></h5>
                                                    <span class="badge bg-success">Activa</span>
                                                </div>
                                                <div class="col-md-3">
                                                    <small class="text-muted d-block">Vigencia:</small>
                                                    <span><?php echo date('d/m/Y', strtotime($suscripcion['fecha_inicio'])); ?> - <?php echo date('d/m/Y', strtotime($suscripcion['fecha_fin'])); ?></span>
                                                </div>
                                                <div class="col-md-2">
                                                    <small class="text-muted d-block">Precio:</small>
                                                    <strong>$<?php echo number_format($suscripcion['precio'], 2); ?></strong>
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="text-center">
                                                        <span class="d-block"><?php echo $diasRestantes; ?> días</span>
                                                        <small class="text-muted">restantes</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-2 text-end">
                                                    <a href="suscripciones.php" class="btn btn-sm btn-outline-primary">Detalles</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                                    <p>No tienes suscripciones activas.</p>
                                    <a href="../planes.php" class="btn btn-primary btn-sm mt-2">Ver planes disponibles</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer text-end">
                            <a href="suscripciones.php" class="btn btn-outline-success btn-sm">Ver todas las suscripciones</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Estilos para las tarjetas de suscripción -->
            <style>
            .subscription-card {
                transition: all 0.3s ease;
                background-color: #fff;
            }
            
            .subscription-card:hover {
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                transform: translateY(-2px);
            }
            </style>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
