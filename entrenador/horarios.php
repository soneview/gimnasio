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

// Obtener ID del entrenador
$query = "SELECT id FROM entrenadores WHERE usuario_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Si no existe el entrenador, redirigir
    header("Location: ../auth/logout.php");
    exit();
}

$entrenadorId = $result->fetch_assoc()['id'];

// Mensajes de alerta
$mensaje = '';
$tipoMensaje = '';

// Procesar eliminación de horario
if (isset($_GET['eliminar']) && !empty($_GET['eliminar'])) {
    $horarioId = intval($_GET['eliminar']);
    
    // Verificar que el horario pertenezca al entrenador
    $query = "SELECT COUNT(*) as existe FROM horarios WHERE id = ? AND entrenador_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $horarioId, $entrenadorId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['existe'] > 0) {
        // Verificar si tiene reservas asociadas
        $query = "SELECT COUNT(*) as reservas FROM reservas WHERE horario_id = ? AND estado = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $horarioId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['reservas'] > 0) {
            $mensaje = "No se puede eliminar este horario porque tiene reservas asociadas.";
            $tipoMensaje = "danger";
        } else {
            // Eliminar horario
            $query = "DELETE FROM horarios WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $horarioId);
            
            if ($stmt->execute()) {
                $mensaje = "Horario eliminado correctamente.";
                $tipoMensaje = "success";
            } else {
                $mensaje = "Error al eliminar el horario: " . $conn->error;
                $tipoMensaje = "danger";
            }
        }
    } else {
        $mensaje = "Horario no encontrado o no pertenece a este entrenador.";
        $tipoMensaje = "danger";
    }
}

// Procesar cambio de estado de horario
if (isset($_GET['cambiarEstado']) && !empty($_GET['cambiarEstado'])) {
    $horarioId = intval($_GET['cambiarEstado']);
    $nuevoEstado = isset($_GET['estado']) ? intval($_GET['estado']) : 0;
    
    // Verificar que el horario pertenezca al entrenador
    $query = "SELECT COUNT(*) as existe FROM horarios WHERE id = ? AND entrenador_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $horarioId, $entrenadorId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['existe'] > 0) {
        // Actualizar estado
        $query = "UPDATE horarios SET estado = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $nuevoEstado, $horarioId);
        
        if ($stmt->execute()) {
            $mensaje = "Estado del horario actualizado correctamente.";
            $tipoMensaje = "success";
        } else {
            $mensaje = "Error al actualizar el estado del horario: " . $conn->error;
            $tipoMensaje = "danger";
        }
    } else {
        $mensaje = "Horario no encontrado o no pertenece a este entrenador.";
        $tipoMensaje = "danger";
    }
}

// Obtener servicios disponibles
$query = "SELECT id, nombre, duracion_minutos FROM servicios WHERE estado = 1";
$servicios = $conn->query($query);

// Obtener horarios del entrenador
$query = "SELECT h.*, s.nombre as servicio_nombre, s.duracion_minutos
          FROM horarios h
          JOIN servicios s ON h.servicio_id = s.id
          WHERE h.entrenador_id = ?
          ORDER BY h.dia_semana, h.hora_inicio";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $entrenadorId);
$stmt->execute();
$horarios = $stmt->get_result();

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
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="horarios.php" class="list-group-item list-group-item-action active">
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
            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipoMensaje; ?> alert-dismissible fade show mb-4">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mis Horarios</h5>
                    <a href="nuevo_horario.php" class="btn btn-light btn-sm">
                        <i class="fas fa-plus"></i> Nuevo Horario
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($horarios->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Día</th>
                                        <th>Horario</th>
                                        <th>Servicio</th>
                                        <th>Duración</th>
                                        <th>Capacidad</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($horario = $horarios->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo getDiaSemana($horario['dia_semana']); ?></td>
                                            <td><?php echo $horario['hora_inicio'] . ' - ' . $horario['hora_fin']; ?></td>
                                            <td><?php echo $horario['servicio_nombre']; ?></td>
                                            <td><?php echo $horario['duracion_minutos'] . ' min'; ?></td>
                                            <td><?php echo $horario['capacidad_maxima']; ?> persona(s)</td>
                                            <td>
                                                <?php if ($horario['estado'] == 1): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="editar_horario.php?id=<?php echo $horario['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <?php if ($horario['estado'] == 1): ?>
                                                        <a href="horarios.php?cambiarEstado=<?php echo $horario['id']; ?>&estado=0" class="btn btn-sm btn-warning" title="Desactivar" onclick="return confirm('¿Desea desactivar este horario?')">
                                                            <i class="fas fa-toggle-off"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="horarios.php?cambiarEstado=<?php echo $horario['id']; ?>&estado=1" class="btn btn-sm btn-success" title="Activar" onclick="return confirm('¿Desea activar este horario?')">
                                                            <i class="fas fa-toggle-on"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="horarios.php?eliminar=<?php echo $horario['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Está seguro de eliminar este horario? Esta acción no se puede deshacer.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No tienes horarios configurados. <a href="nuevo_horario.php" class="alert-link">Crea tu primer horario</a>.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Información sobre horarios -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Información sobre Horarios</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-info-circle me-2"></i> Gestión de Horarios</h6>
                            <p>Los horarios configurados aquí estarán disponibles para que los clientes realicen sus reservas. Puedes crear, editar, activar, desactivar o eliminar horarios según tus necesidades.</p>
                            
                            <h6><i class="fas fa-exclamation-triangle me-2"></i> Horarios con Reservas</h6>
                            <p>Los horarios que ya tienen reservas asociadas no pueden ser eliminados. Si necesitas cancelarlos, primero debes desactivarlos para que no se realicen nuevas reservas.</p>
                        </div>
                        
                        <div class="col-md-6">
                            <h6><i class="fas fa-clock me-2"></i> Conflictos de Horarios</h6>
                            <p>El sistema verificará automáticamente que no existan colisiones entre tus horarios. No podrás crear horarios que se superpongan con otros ya existentes.</p>
                            
                            <h6><i class="fas fa-users me-2"></i> Capacidad</h6>
                            <p>La capacidad máxima determina cuántos clientes pueden reservar en un mismo horario. En general, para entrenamientos personalizados la capacidad debería ser 1.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
