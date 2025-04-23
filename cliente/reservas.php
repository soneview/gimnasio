<?php
session_start();
require_once '../config/db.php';

// Verificar si el usuario está logueado y es cliente (rol_id = 3)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 3) {
    header("Location: ../auth/login.php");
    exit();
}

// Obtener todas las reservas del cliente
$conn = connectDB();
$userId = $_SESSION['user_id'];

$query = "SELECT r.*, h.dia_semana, h.hora_inicio, h.hora_fin, 
          s.nombre as servicio_nombre, s.duracion_minutos,
          CONCAT(u.nombre, ' ', u.apellido) as entrenador_nombre
          FROM reservas r 
          JOIN horarios h ON r.horario_id = h.id
          JOIN servicios s ON h.servicio_id = s.id
          JOIN entrenadores e ON h.entrenador_id = e.id
          JOIN usuarios u ON e.usuario_id = u.id
          WHERE r.usuario_id = ?
          ORDER BY r.fecha DESC, h.hora_inicio";
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

// Función para obtener estado de la reserva
function getEstadoReserva($estado) {
    switch ($estado) {
        case 1:
            return '<span class="badge bg-success">Activa</span>';
        case 2:
            return '<span class="badge bg-danger">Cancelada</span>';
        case 3:
            return '<span class="badge bg-info">Completada</span>';
        default:
            return '<span class="badge bg-secondary">Desconocido</span>';
    }
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
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="reservas.php" class="list-group-item list-group-item-action active">
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
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mis Reservas</h5>
                    <a href="horarios.php" class="btn btn-light btn-sm">
                        <i class="fas fa-plus"></i> Nueva Reserva
                    </a>
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
                                        <th>Estado</th>
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
                                            <td><?php echo getEstadoReserva($reserva['estado']); ?></td>
                                            <td>
                                                <?php if ($reserva['estado'] == 1 && strtotime($reserva['fecha']) >= strtotime('today')): ?>
                                                    <a href="cancelar_reserva.php?id=<?php echo $reserva['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que deseas cancelar esta reserva?')">
                                                        <i class="fas fa-times"></i> Cancelar
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" disabled>
                                                        <i class="fas fa-times"></i> Cancelar
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No tienes reservas registradas. <a href="horarios.php">Reserva ahora</a>.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Información de política de cancelación -->
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Política de Cancelación</h5>
                </div>
                <div class="card-body">
                    <p><strong>Cancelación anticipada:</strong> Puedes cancelar tu reserva hasta 24 horas antes de la hora programada sin ningún cargo.</p>
                    <p><strong>Cancelación tardía:</strong> Las cancelaciones con menos de 24 horas de antelación pueden estar sujetas a un cargo o a la pérdida de la sesión.</p>
                    <p><strong>No presentación:</strong> Si no te presentas a una sesión reservada sin previo aviso, se considerará como realizada.</p>
                    <p>Para cualquier duda o situación especial, por favor contáctanos directamente.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
