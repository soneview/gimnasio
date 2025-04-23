<?php
session_start();
require_once '../config/db.php';

// Verificar si el usuario está logueado y es cliente (rol_id = 3)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 3) {
    header("Location: ../auth/login.php");
    exit();
}

// Verificar si hay un horario_id en la URL
if (!isset($_GET['horario_id']) || empty($_GET['horario_id'])) {
    header("Location: horarios.php");
    exit();
}

$horario_id = intval($_GET['horario_id']);
$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

$conn = connectDB();

// Verificar si el usuario tiene una suscripción activa
$query = "SELECT COUNT(*) as activa FROM suscripciones 
          WHERE usuario_id = ? AND estado = 1 AND fecha_fin >= CURDATE()";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result['activa'] == 0) {
    $message = "Necesitas tener una suscripción activa para realizar reservas.";
    $messageType = "danger";
}

// Obtener información del horario seleccionado
$query = "SELECT h.*, 
          s.nombre as servicio_nombre, s.duracion_minutos,
          CONCAT(u.nombre, ' ', u.apellido) as entrenador_nombre,
          e.especialidad
          FROM horarios h
          JOIN servicios s ON h.servicio_id = s.id
          JOIN entrenadores e ON h.entrenador_id = e.id
          JOIN usuarios u ON e.usuario_id = u.id
          WHERE h.id = ? AND h.estado = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $horario_id);
$stmt->execute();
$horario = $stmt->get_result()->fetch_assoc();

// Verificar si el horario existe
if (!$horario) {
    header("Location: horarios.php");
    exit();
}

// Procesar el formulario de reserva
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($message)) {
    $fecha = $_POST['fecha'];
    
    // Validar que la fecha sea futura
    if (strtotime($fecha) < strtotime('today')) {
        $message = "La fecha de reserva debe ser igual o posterior a hoy.";
        $messageType = "danger";
    } else {
        // Obtener el día de la semana de la fecha seleccionada (1-7 para lunes a domingo)
        $diaSemana = date('N', strtotime($fecha));
        
        // Verificar que el día de la semana coincida con el horario
        if ($diaSemana != $horario['dia_semana']) {
            $message = "La fecha seleccionada no coincide con el día de la semana del horario.";
            $messageType = "danger";
        } else {
            // Verificar si ya existe una reserva para este usuario en esta fecha y hora
            $query = "SELECT COUNT(*) as existe FROM reservas r
                      JOIN horarios h ON r.horario_id = h.id
                      WHERE r.usuario_id = ? AND r.fecha = ? AND r.estado = 1
                      AND ((h.hora_inicio <= ? AND h.hora_fin > ?) OR
                           (h.hora_inicio < ? AND h.hora_fin >= ?) OR
                           (h.hora_inicio >= ? AND h.hora_fin <= ?))";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isssssss", 
                $userId, 
                $fecha, 
                $horario['hora_fin'], $horario['hora_inicio'],
                $horario['hora_fin'], $horario['hora_inicio'],
                $horario['hora_inicio'], $horario['hora_fin']
            );
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['existe'] > 0) {
                $message = "Ya tienes una reserva que se sobrepone con este horario en la fecha seleccionada.";
                $messageType = "danger";
            } else {
                // Verificar capacidad disponible para ese horario y fecha
                $query = "SELECT COUNT(*) as ocupado FROM reservas 
                          WHERE horario_id = ? AND fecha = ? AND estado = 1";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("is", $horario_id, $fecha);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                
                if ($result['ocupado'] >= $horario['capacidad_maxima']) {
                    $message = "Lo sentimos, este horario ya ha alcanzado su capacidad máxima para la fecha seleccionada.";
                    $messageType = "danger";
                } else {
                    // Crear la reserva
                    $query = "INSERT INTO reservas (usuario_id, horario_id, fecha, estado) 
                              VALUES (?, ?, ?, 1)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("iis", $userId, $horario_id, $fecha);
                    
                    if ($stmt->execute()) {
                        $message = "Reserva creada con éxito.";
                        $messageType = "success";
                        
                        // Redireccionar después de 2 segundos
                        header("refresh:2;url=reservas.php");
                    } else {
                        $message = "Error al crear la reserva: " . $conn->error;
                        $messageType = "danger";
                    }
                }
            }
        }
    }
}

// Obtener fechas disponibles (próximos 30 días)
$fechasDisponibles = array();
$hoy = new DateTime();
$limite = new DateTime('+30 days');

while ($hoy <= $limite) {
    // Si el día de la semana coincide con el del horario (1-7 para lunes a domingo)
    if ($hoy->format('N') == $horario['dia_semana']) {
        // Verificar disponibilidad para esta fecha
        $fecha = $hoy->format('Y-m-d');
        
        $query = "SELECT COUNT(*) as ocupado FROM reservas 
                  WHERE horario_id = ? AND fecha = ? AND estado = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $horario_id, $fecha);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        // Si hay capacidad disponible, agregar a las fechas disponibles
        if ($result['ocupado'] < $horario['capacidad_maxima']) {
            $fechasDisponibles[] = $fecha;
        }
    }
    
    $hoy->modify('+1 day');
}

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
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                    <a href="reservas.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-alt me-2"></i> Mis Reservas
                    </a>
                    <a href="horarios.php" class="list-group-item list-group-item-action active">
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
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Nueva Reserva</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?> mb-4">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($message) || $messageType != "success"): ?>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Detalles del Horario</h5>
                                <ul class="list-group">
                                    <li class="list-group-item"><strong>Día:</strong> <?php echo getDiaSemana($horario['dia_semana']); ?></li>
                                    <li class="list-group-item"><strong>Horario:</strong> <?php echo $horario['hora_inicio'] . ' - ' . $horario['hora_fin']; ?></li>
                                    <li class="list-group-item"><strong>Servicio:</strong> <?php echo $horario['servicio_nombre']; ?></li>
                                    <li class="list-group-item"><strong>Duración:</strong> <?php echo $horario['duracion_minutos'] . ' minutos'; ?></li>
                                    <li class="list-group-item"><strong>Entrenador:</strong> <?php echo $horario['entrenador_nombre']; ?></li>
                                    <li class="list-group-item"><strong>Especialidad:</strong> <?php echo $horario['especialidad']; ?></li>
                                </ul>
                            </div>
                            
                            <div class="col-md-6">
                                <?php if ($result['activa'] > 0): ?>
                                    <h5>Selecciona una Fecha</h5>
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label for="fecha" class="form-label">Fecha de Reserva</label>
                                            <select class="form-select" id="fecha" name="fecha" required>
                                                <option value="">Selecciona una fecha</option>
                                                <?php foreach ($fechasDisponibles as $fecha): ?>
                                                    <option value="<?php echo $fecha; ?>">
                                                        <?php echo date('d/m/Y', strtotime($fecha)); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if (empty($fechasDisponibles)): ?>
                                                <div class="text-danger mt-2">
                                                    No hay fechas disponibles para los próximos 30 días.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-success" <?php echo empty($fechasDisponibles) ? 'disabled' : ''; ?>>
                                                Confirmar Reserva
                                            </button>
                                            <a href="horarios.php" class="btn btn-outline-secondary">Volver a Horarios</a>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <h5>Suscripción Requerida</h5>
                                        <p>Necesitas tener una suscripción activa para poder realizar reservas.</p>
                                        <a href="suscripciones.php" class="btn btn-primary">Ver Planes</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (empty($message) || $messageType != "success"): ?>
                <!-- Instrucciones y política de reservas -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Política de Reservas</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Llegada puntual:</strong> Te recomendamos llegar al menos 10 minutos antes de tu sesión programada.</p>
                        <p><strong>Cancelación:</strong> Puedes cancelar tu reserva hasta 24 horas antes sin ningún cargo. Las cancelaciones tardías pueden estar sujetas a cargos.</p>
                        <p><strong>Equipo necesario:</strong> Recuerda traer ropa cómoda, toalla y botella de agua para tu sesión.</p>
                        <p><strong>Duración:</strong> La duración de la sesión es exacta. Si llegas tarde, no podremos extender el tiempo asignado.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
