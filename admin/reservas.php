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
$mensaje = '';
$tipo_mensaje = '';

// Procesar formulario de agregar/editar reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Validar datos comunes
        $usuario_id = (int)$_POST['usuario_id'];
        $horario_id = (int)$_POST['horario_id'];
        $fecha = sanitize($conn, $_POST['fecha']);
        $estado = (int)$_POST['estado'];
        
        // Validaciones básicas
        $errores = [];
        
        if ($usuario_id <= 0) {
            $errores[] = "Debe seleccionar un cliente válido.";
        }
        
        if ($horario_id <= 0) {
            $errores[] = "Debe seleccionar un horario válido.";
        }
        
        // Validar que la fecha no sea anterior a hoy
        if (strtotime($fecha) < strtotime(date('Y-m-d'))) {
            $errores[] = "La fecha de reserva no puede ser anterior a hoy.";
        }
        
        // Obtener información del horario seleccionado
        $query = "SELECT dia_semana, capacidad_maxima FROM horarios WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $horario_id);
        $stmt->execute();
        $horario_info = $stmt->get_result()->fetch_assoc();
        
        if (!$horario_info) {
            $errores[] = "El horario seleccionado no existe.";
        } else {
            // Verificar que el día de la semana del horario coincida con el día de la semana de la fecha
            $dia_semana_fecha = date('N', strtotime($fecha)); // 1 (lunes) a 7 (domingo)
            
            if ($dia_semana_fecha != $horario_info['dia_semana']) {
                $errores[] = "La fecha seleccionada no coincide con el día de la semana del horario.";
            }
            
            // Verificar disponibilidad (capacidad máxima)
            $query = "SELECT COUNT(*) as total FROM reservas WHERE horario_id = ? AND fecha = ? AND estado = 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("is", $horario_id, $fecha);
            $stmt->execute();
            $reservas_existentes = $stmt->get_result()->fetch_assoc()['total'];
            
            // Si estamos editando, no contamos la reserva actual
            if ($_POST['action'] === 'editar' && isset($_POST['reserva_id'])) {
                $reserva_id = (int)$_POST['reserva_id'];
                $query = "SELECT id FROM reservas WHERE id = ? AND horario_id = ? AND fecha = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iis", $reserva_id, $horario_id, $fecha);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $reservas_existentes--;
                }
            }
            
            if ($reservas_existentes >= $horario_info['capacidad_maxima']) {
                $errores[] = "No hay disponibilidad para este horario en la fecha seleccionada.";
            }
        }
        
        // Si hay errores, mostrarlos
        if (!empty($errores)) {
            $mensaje = "Error: " . implode(" ", $errores);
            $tipo_mensaje = "danger";
        } else {
            // Agregar nueva reserva
            if ($_POST['action'] === 'agregar') {
                $query = "INSERT INTO reservas (usuario_id, horario_id, fecha, estado, fecha_creacion) 
                        VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iisi", $usuario_id, $horario_id, $fecha, $estado);
                
                if ($stmt->execute()) {
                    $mensaje = "Reserva agregada correctamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al agregar reserva: " . $conn->error;
                    $tipo_mensaje = "danger";
                }
            } 
            // Editar reserva existente
            elseif ($_POST['action'] === 'editar' && isset($_POST['reserva_id'])) {
                $reserva_id = (int)$_POST['reserva_id'];
                
                $query = "UPDATE reservas SET usuario_id = ?, horario_id = ?, fecha = ?, estado = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iisii", $usuario_id, $horario_id, $fecha, $estado, $reserva_id);
                
                if ($stmt->execute()) {
                    $mensaje = "Reserva actualizada correctamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al actualizar reserva: " . $conn->error;
                    $tipo_mensaje = "danger";
                }
            }
        }
    }
}

// Procesar acciones (cancelar/completar/eliminar reserva)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'cancelar') {
        $query = "UPDATE reservas SET estado = 2 WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $mensaje = "Reserva cancelada correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al cancelar la reserva: " . $conn->error;
            $tipo_mensaje = "danger";
        }
    } 
    elseif ($action === 'completar') {
        $query = "UPDATE reservas SET estado = 3 WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $mensaje = "Reserva marcada como completada.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al completar la reserva: " . $conn->error;
            $tipo_mensaje = "danger";
        }
    }
    elseif ($action === 'activar') {
        $query = "UPDATE reservas SET estado = 1 WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $mensaje = "Reserva activada correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al activar la reserva: " . $conn->error;
            $tipo_mensaje = "danger";
        }
    }
    elseif ($action === 'eliminar') {
        $query = "DELETE FROM reservas WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $mensaje = "Reserva eliminada correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al eliminar la reserva: " . $conn->error;
            $tipo_mensaje = "danger";
        }
    }
}

// Obtener reserva para editar
$reserva_editar = null;
if (isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $query = "SELECT * FROM reservas WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $reserva_editar = $result->fetch_assoc();
    }
}

// Obtener todos los clientes (usuarios con rol_id = 3)
$query = "SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo, email 
          FROM usuarios 
          WHERE rol_id = 3 AND estado = 1 
          ORDER BY nombre ASC";
$clientes = $conn->query($query);

// Obtener todos los horarios activos con información de entrenadores y servicios
$query = "SELECT h.id, h.dia_semana, h.hora_inicio, h.hora_fin, 
          CONCAT(u.nombre, ' ', u.apellido) as entrenador_nombre,
          s.nombre as servicio_nombre
          FROM horarios h
          JOIN entrenadores e ON h.entrenador_id = e.id
          JOIN usuarios u ON e.usuario_id = u.id
          JOIN servicios s ON h.servicio_id = s.id
          WHERE h.estado = 1
          ORDER BY h.dia_semana ASC, h.hora_inicio ASC";
$horarios = $conn->query($query);

// Obtener todas las reservas con información de clientes, horarios, entrenadores y servicios
$query = "SELECT r.*, 
          CONCAT(u.nombre, ' ', u.apellido) as cliente_nombre,
          u.email as cliente_email,
          h.dia_semana, h.hora_inicio, h.hora_fin,
          CONCAT(ue.nombre, ' ', ue.apellido) as entrenador_nombre,
          s.nombre as servicio_nombre
          FROM reservas r
          JOIN usuarios u ON r.usuario_id = u.id
          JOIN horarios h ON r.horario_id = h.id
          JOIN entrenadores e ON h.entrenador_id = e.id
          JOIN usuarios ue ON e.usuario_id = ue.id
          JOIN servicios s ON h.servicio_id = s.id
          ORDER BY r.fecha DESC, h.hora_inicio ASC";
$reservas = $conn->query($query);

// Función para convertir el número de día a texto
function getDiaTexto($dia) {
    $dias = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo'
    ];
    
    return isset($dias[$dia]) ? $dias[$dia] : 'Desconocido';
}

// Función para obtener el texto del estado
function getEstadoTexto($estado) {
    switch ($estado) {
        case 1:
            return ['texto' => 'Activa', 'clase' => 'success'];
        case 2:
            return ['texto' => 'Cancelada', 'clase' => 'danger'];
        case 3:
            return ['texto' => 'Completada', 'clase' => 'info'];
        default:
            return ['texto' => 'Desconocido', 'clase' => 'secondary'];
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
                    <h5 class="mb-0">Panel de Administración</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action">
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
                    <a href="reservas.php" class="list-group-item list-group-item-action active">
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
            <!-- Mensaje de alerta -->
            <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Formulario para agregar/editar reserva -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <?php echo $reserva_editar ? 'Editar Reserva' : 'Agregar Nueva Reserva'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form action="reservas.php" method="POST" class="row g-3">
                        <input type="hidden" name="action" value="<?php echo $reserva_editar ? 'editar' : 'agregar'; ?>">
                        
                        <?php if ($reserva_editar): ?>
                        <input type="hidden" name="reserva_id" value="<?php echo $reserva_editar['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="col-md-6">
                            <label for="usuario_id" class="form-label">Cliente</label>
                            <select class="form-select" id="usuario_id" name="usuario_id" required>
                                <option value="">Seleccionar cliente</option>
                                <?php while ($cliente = $clientes->fetch_assoc()): ?>
                                    <option value="<?php echo $cliente['id']; ?>" <?php echo ($reserva_editar && $reserva_editar['usuario_id'] == $cliente['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cliente['nombre_completo']); ?> (<?php echo htmlspecialchars($cliente['email']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="horario_id" class="form-label">Horario</label>
                            <select class="form-select" id="horario_id" name="horario_id" required>
                                <option value="">Seleccionar horario</option>
                                <?php while ($horario = $horarios->fetch_assoc()): ?>
                                    <option value="<?php echo $horario['id']; ?>" <?php echo ($reserva_editar && $reserva_editar['horario_id'] == $horario['id']) ? 'selected' : ''; ?> 
                                            data-dia="<?php echo $horario['dia_semana']; ?>">
                                        <?php echo getDiaTexto($horario['dia_semana']); ?> | 
                                        <?php echo date('h:i A', strtotime($horario['hora_inicio'])); ?> - 
                                        <?php echo date('h:i A', strtotime($horario['hora_fin'])); ?> | 
                                        <?php echo htmlspecialchars($horario['servicio_nombre']); ?> con 
                                        <?php echo htmlspecialchars($horario['entrenador_nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="fecha" class="form-label">Fecha</label>
                            <input type="date" class="form-control" id="fecha" name="fecha" required 
                                min="<?php echo date('Y-m-d'); ?>"
                                value="<?php echo $reserva_editar ? $reserva_editar['fecha'] : date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="1" <?php echo ($reserva_editar && $reserva_editar['estado'] == 1) ? 'selected' : ''; ?>>Activa</option>
                                <option value="2" <?php echo ($reserva_editar && $reserva_editar['estado'] == 2) ? 'selected' : ''; ?>>Cancelada</option>
                                <option value="3" <?php echo ($reserva_editar && $reserva_editar['estado'] == 3) ? 'selected' : ''; ?>>Completada</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $reserva_editar ? 'Actualizar Reserva' : 'Agregar Reserva'; ?>
                            </button>
                            
                            <?php if ($reserva_editar): ?>
                            <a href="reservas.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de reservas -->
            <div class="card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i> Lista de Reservas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Horario</th>
                                    <th>Servicio</th>
                                    <th>Entrenador</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($reservas->num_rows > 0): ?>
                                    <?php while ($reserva = $reservas->fetch_assoc()): 
                                        $estado = getEstadoTexto($reserva['estado']);
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($reserva['cliente_nombre']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($reserva['fecha'])); ?></td>
                                            <td>
                                                <?php 
                                                    echo getDiaTexto($reserva['dia_semana']) . ' ';
                                                    echo date('h:i A', strtotime($reserva['hora_inicio'])); 
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($reserva['servicio_nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($reserva['entrenador_nombre']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $estado['clase']; ?>">
                                                    <?php echo $estado['texto']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=editar&id=<?php echo $reserva['id']; ?>" class="btn btn-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <?php if ($reserva['estado'] == 1): ?>
                                                        <a href="?action=cancelar&id=<?php echo $reserva['id']; ?>" class="btn btn-warning" title="Cancelar" onclick="return confirm('¿Está seguro de cancelar esta reserva?')">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                        <a href="?action=completar&id=<?php echo $reserva['id']; ?>" class="btn btn-success" title="Marcar como completada" onclick="return confirm('¿Está seguro de marcar esta reserva como completada?')">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php elseif ($reserva['estado'] == 2): ?>
                                                        <a href="?action=activar&id=<?php echo $reserva['id']; ?>" class="btn btn-info" title="Activar" onclick="return confirm('¿Está seguro de activar esta reserva?')">
                                                            <i class="fas fa-redo"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="?action=eliminar&id=<?php echo $reserva['id']; ?>" class="btn btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar esta reserva? Esta acción no se puede deshacer.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No hay reservas registradas</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas de reservas -->
            <div class="row mt-4">
                <div class="col-md-4 mb-4">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase">Reservas Activas</h6>
                                    <?php
                                    $conn = connectDB();
                                    $query = "SELECT COUNT(*) as total FROM reservas WHERE estado = 1";
                                    $total = $conn->query($query)->fetch_assoc()['total'];
                                    $conn->close();
                                    ?>
                                    <h2 class="mb-0"><?php echo $total; ?></h2>
                                </div>
                                <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase">Reservas Completadas</h6>
                                    <?php
                                    $conn = connectDB();
                                    $query = "SELECT COUNT(*) as total FROM reservas WHERE estado = 3";
                                    $total = $conn->query($query)->fetch_assoc()['total'];
                                    $conn->close();
                                    ?>
                                    <h2 class="mb-0"><?php echo $total; ?></h2>
                                </div>
                                <i class="fas fa-check-circle fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card bg-danger text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase">Reservas Canceladas</h6>
                                    <?php
                                    $conn = connectDB();
                                    $query = "SELECT COUNT(*) as total FROM reservas WHERE estado = 2";
                                    $total = $conn->query($query)->fetch_assoc()['total'];
                                    $conn->close();
                                    ?>
                                    <h2 class="mb-0"><?php echo $total; ?></h2>
                                </div>
                                <i class="fas fa-ban fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const horarioSelect = document.getElementById('horario_id');
    const fechaInput = document.getElementById('fecha');
    
    // Función para validar que la fecha coincida con el día de la semana del horario
    function validarFechaHorario() {
        if (horarioSelect.value && fechaInput.value) {
            const horarioOption = horarioSelect.options[horarioSelect.selectedIndex];
            const diaSemanaHorario = parseInt(horarioOption.dataset.dia);
            
            const fecha = new Date(fechaInput.value);
            // getDay() devuelve 0 para domingo, 1 para lunes, etc.
            // Convertimos a nuestro formato donde 1 es lunes y 7 es domingo
            let diaSemanaFecha = fecha.getDay();
            diaSemanaFecha = diaSemanaFecha === 0 ? 7 : diaSemanaFecha;
            
            if (diaSemanaFecha !== diaSemanaHorario) {
                fechaInput.setCustomValidity(`Esta fecha no coincide con el día ${getDiaTexto(diaSemanaHorario)} del horario seleccionado.`);
            } else {
                fechaInput.setCustomValidity('');
            }
        }
    }
    
    // Función para obtener el nombre del día
    function getDiaTexto(dia) {
        const dias = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        return dias[dia] || 'Desconocido';
    }
    
    horarioSelect.addEventListener('change', validarFechaHorario);
    fechaInput.addEventListener('change', validarFechaHorario);
});
</script>
