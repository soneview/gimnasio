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

// Procesar formulario de agregar/editar horario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Validar datos comunes
        $entrenador_id = (int)$_POST['entrenador_id'];
        $servicio_id = (int)$_POST['servicio_id'];
        $dia_semana = (int)$_POST['dia_semana'];
        $hora_inicio = sanitize($conn, $_POST['hora_inicio']);
        $hora_fin = sanitize($conn, $_POST['hora_fin']);
        $capacidad_maxima = (int)$_POST['capacidad_maxima'];
        
        // Validaciones básicas
        $errores = [];
        
        if ($dia_semana < 1 || $dia_semana > 7) {
            $errores[] = "El día de la semana debe estar entre 1 (Lunes) y 7 (Domingo).";
        }
        
        if ($hora_inicio >= $hora_fin) {
            $errores[] = "La hora de inicio debe ser anterior a la hora de fin.";
        }
        
        if ($capacidad_maxima < 1) {
            $errores[] = "La capacidad máxima debe ser al menos 1.";
        }
        
        // Verificar si ya existe un horario para el mismo entrenador en el mismo día y hora
        $query = "SELECT id FROM horarios 
                 WHERE entrenador_id = ? AND dia_semana = ? 
                 AND ((hora_inicio <= ? AND hora_fin > ?) OR (hora_inicio < ? AND hora_fin >= ?))";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iissss", $entrenador_id, $dia_semana, $hora_fin, $hora_inicio, $hora_fin, $hora_inicio);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Si estamos editando, excluimos el horario actual de la verificación
        $horario_existente = false;
        if ($_POST['action'] === 'editar' && isset($_POST['horario_id'])) {
            $horario_id = (int)$_POST['horario_id'];
            while ($row = $result->fetch_assoc()) {
                if ($row['id'] != $horario_id) {
                    $horario_existente = true;
                    break;
                }
            }
        } else {
            $horario_existente = $result->num_rows > 0;
        }
        
        if ($horario_existente) {
            $errores[] = "Ya existe un horario para este entrenador en el mismo día y rango de horas.";
        }
        
        // Si hay errores, mostrarlos
        if (!empty($errores)) {
            $mensaje = "Error: " . implode(" ", $errores);
            $tipo_mensaje = "danger";
        } else {
            // Agregar nuevo horario
            if ($_POST['action'] === 'agregar') {
                $query = "INSERT INTO horarios (entrenador_id, servicio_id, dia_semana, hora_inicio, hora_fin, capacidad_maxima, estado) 
                        VALUES (?, ?, ?, ?, ?, ?, 1)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iiissi", $entrenador_id, $servicio_id, $dia_semana, $hora_inicio, $hora_fin, $capacidad_maxima);
                
                if ($stmt->execute()) {
                    $mensaje = "Horario agregado correctamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al agregar horario: " . $conn->error;
                    $tipo_mensaje = "danger";
                }
            } 
            // Editar horario existente
            elseif ($_POST['action'] === 'editar' && isset($_POST['horario_id'])) {
                $horario_id = (int)$_POST['horario_id'];
                
                $query = "UPDATE horarios SET entrenador_id = ?, servicio_id = ?, dia_semana = ?, 
                        hora_inicio = ?, hora_fin = ?, capacidad_maxima = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iiissii", $entrenador_id, $servicio_id, $dia_semana, $hora_inicio, $hora_fin, $capacidad_maxima, $horario_id);
                
                if ($stmt->execute()) {
                    $mensaje = "Horario actualizado correctamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al actualizar horario: " . $conn->error;
                    $tipo_mensaje = "danger";
                }
            }
        }
    }
}

// Procesar acciones (activar/desactivar/eliminar horario)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'activar' || $action === 'desactivar') {
        $estado = ($action === 'activar') ? 1 : 0;
        $query = "UPDATE horarios SET estado = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $estado, $id);
        
        if ($stmt->execute()) {
            $mensaje = ($action === 'activar') ? "Horario activado correctamente." : "Horario desactivado correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar el estado del horario: " . $conn->error;
            $tipo_mensaje = "danger";
        }
    } 
    elseif ($action === 'eliminar') {
        // Verificar si hay reservas asociadas al horario
        $query = "SELECT COUNT(*) as total FROM reservas WHERE horario_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reservas = $result->fetch_assoc()['total'];
        
        if ($reservas > 0) {
            $mensaje = "No se puede eliminar el horario porque tiene reservas asociadas.";
            $tipo_mensaje = "danger";
        } else {
            $query = "DELETE FROM horarios WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $mensaje = "Horario eliminado correctamente.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al eliminar horario: " . $conn->error;
                $tipo_mensaje = "danger";
            }
        }
    }
}

// Obtener horario para editar
$horario_editar = null;
if (isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $query = "SELECT * FROM horarios WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $horario_editar = $result->fetch_assoc();
    }
}

// Obtener todos los entrenadores activos
$query = "SELECT e.id, CONCAT(u.nombre, ' ', u.apellido) as nombre_completo, e.especialidad
          FROM entrenadores e
          JOIN usuarios u ON e.usuario_id = u.id
          WHERE u.estado = 1
          ORDER BY u.nombre ASC";
$entrenadores = $conn->query($query);

// Obtener todos los servicios activos
$query = "SELECT id, nombre, duracion_minutos FROM servicios WHERE estado = 1 ORDER BY nombre ASC";
$servicios = $conn->query($query);

// Obtener todos los horarios con información de entrenadores y servicios
$query = "SELECT h.*, 
          CONCAT(u.nombre, ' ', u.apellido) as entrenador_nombre,
          s.nombre as servicio_nombre
          FROM horarios h
          JOIN entrenadores e ON h.entrenador_id = e.id
          JOIN usuarios u ON e.usuario_id = u.id
          JOIN servicios s ON h.servicio_id = s.id
          ORDER BY h.dia_semana ASC, h.hora_inicio ASC";
$horarios = $conn->query($query);

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
                    <a href="horarios.php" class="list-group-item list-group-item-action active">
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
            <!-- Mensaje de alerta -->
            <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Formulario para agregar/editar horario -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <?php echo $horario_editar ? 'Editar Horario' : 'Agregar Nuevo Horario'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form action="horarios.php" method="POST" class="row g-3">
                        <input type="hidden" name="action" value="<?php echo $horario_editar ? 'editar' : 'agregar'; ?>">
                        
                        <?php if ($horario_editar): ?>
                        <input type="hidden" name="horario_id" value="<?php echo $horario_editar['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="col-md-6">
                            <label for="entrenador_id" class="form-label">Entrenador</label>
                            <select class="form-select" id="entrenador_id" name="entrenador_id" required>
                                <option value="">Seleccionar entrenador</option>
                                <?php while ($entrenador = $entrenadores->fetch_assoc()): ?>
                                    <option value="<?php echo $entrenador['id']; ?>" <?php echo ($horario_editar && $horario_editar['entrenador_id'] == $entrenador['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($entrenador['nombre_completo']); ?> (<?php echo htmlspecialchars($entrenador['especialidad']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="servicio_id" class="form-label">Servicio</label>
                            <select class="form-select" id="servicio_id" name="servicio_id" required>
                                <option value="">Seleccionar servicio</option>
                                <?php while ($servicio = $servicios->fetch_assoc()): ?>
                                    <option value="<?php echo $servicio['id']; ?>" <?php echo ($horario_editar && $horario_editar['servicio_id'] == $servicio['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($servicio['nombre']); ?> (<?php echo $servicio['duracion_minutos']; ?> min)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="dia_semana" class="form-label">Día de la semana</label>
                            <select class="form-select" id="dia_semana" name="dia_semana" required>
                                <option value="1" <?php echo ($horario_editar && $horario_editar['dia_semana'] == 1) ? 'selected' : ''; ?>>Lunes</option>
                                <option value="2" <?php echo ($horario_editar && $horario_editar['dia_semana'] == 2) ? 'selected' : ''; ?>>Martes</option>
                                <option value="3" <?php echo ($horario_editar && $horario_editar['dia_semana'] == 3) ? 'selected' : ''; ?>>Miércoles</option>
                                <option value="4" <?php echo ($horario_editar && $horario_editar['dia_semana'] == 4) ? 'selected' : ''; ?>>Jueves</option>
                                <option value="5" <?php echo ($horario_editar && $horario_editar['dia_semana'] == 5) ? 'selected' : ''; ?>>Viernes</option>
                                <option value="6" <?php echo ($horario_editar && $horario_editar['dia_semana'] == 6) ? 'selected' : ''; ?>>Sábado</option>
                                <option value="7" <?php echo ($horario_editar && $horario_editar['dia_semana'] == 7) ? 'selected' : ''; ?>>Domingo</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="hora_inicio" class="form-label">Hora de inicio</label>
                            <input type="time" class="form-control" id="hora_inicio" name="hora_inicio" required 
                                value="<?php echo $horario_editar ? $horario_editar['hora_inicio'] : ''; ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="hora_fin" class="form-label">Hora de fin</label>
                            <input type="time" class="form-control" id="hora_fin" name="hora_fin" required 
                                value="<?php echo $horario_editar ? $horario_editar['hora_fin'] : ''; ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="capacidad_maxima" class="form-label">Capacidad</label>
                            <input type="number" class="form-control" id="capacidad_maxima" name="capacidad_maxima" min="1" required 
                                value="<?php echo $horario_editar ? $horario_editar['capacidad_maxima'] : '1'; ?>">
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $horario_editar ? 'Actualizar Horario' : 'Agregar Horario'; ?>
                            </button>
                            
                            <?php if ($horario_editar): ?>
                            <a href="horarios.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de horarios -->
            <div class="card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i> Lista de Horarios</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Día</th>
                                    <th>Horario</th>
                                    <th>Entrenador</th>
                                    <th>Servicio</th>
                                    <th>Capacidad</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($horarios->num_rows > 0): ?>
                                    <?php while ($horario = $horarios->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo getDiaTexto($horario['dia_semana']); ?></td>
                                            <td>
                                                <?php 
                                                    echo date('h:i A', strtotime($horario['hora_inicio'])); 
                                                    echo ' - '; 
                                                    echo date('h:i A', strtotime($horario['hora_fin'])); 
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($horario['entrenador_nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($horario['servicio_nombre']); ?></td>
                                            <td><?php echo $horario['capacidad_maxima']; ?></td>
                                            <td>
                                                <?php if ($horario['estado'] == 1): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=editar&id=<?php echo $horario['id']; ?>" class="btn btn-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <?php if ($horario['estado'] == 1): ?>
                                                        <a href="?action=desactivar&id=<?php echo $horario['id']; ?>" class="btn btn-warning" title="Desactivar" onclick="return confirm('¿Está seguro de desactivar este horario?')">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?action=activar&id=<?php echo $horario['id']; ?>" class="btn btn-success" title="Activar">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="?action=eliminar&id=<?php echo $horario['id']; ?>" class="btn btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este horario? Esta acción no se puede deshacer.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No hay horarios registrados</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Vista semanal de horarios -->
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i> Vista Semanal de Horarios</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr class="bg-light">
                                    <th>Hora</th>
                                    <th>Lunes</th>
                                    <th>Martes</th>
                                    <th>Miércoles</th>
                                    <th>Jueves</th>
                                    <th>Viernes</th>
                                    <th>Sábado</th>
                                    <th>Domingo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Reiniciar el puntero del resultado
                                $conn = connectDB();
                                $query = "SELECT h.*, 
                                        CONCAT(u.nombre, ' ', u.apellido) as entrenador_nombre,
                                        s.nombre as servicio_nombre
                                        FROM horarios h
                                        JOIN entrenadores e ON h.entrenador_id = e.id
                                        JOIN usuarios u ON e.usuario_id = u.id
                                        JOIN servicios s ON h.servicio_id = s.id
                                        WHERE h.estado = 1
                                        ORDER BY h.hora_inicio ASC";
                                $horarios_result = $conn->query($query);
                                
                                // Organizar horarios por día y hora
                                $horarios_por_dia = [];
                                while ($h = $horarios_result->fetch_assoc()) {
                                    $horarios_por_dia[$h['dia_semana']][] = $h;
                                }
                                
                                // Horas de operación (6:00 AM a 10:00 PM)
                                $horas = [];
                                for ($i = 6; $i <= 22; $i++) {
                                    $hora = sprintf("%02d:00:00", $i);
                                    $horas[] = $hora;
                                }
                                
                                foreach ($horas as $hora) {
                                    echo '<tr>';
                                    echo '<td>' . date('h:i A', strtotime($hora)) . '</td>';
                                    
                                    // Para cada día de la semana (1-7)
                                    for ($dia = 1; $dia <= 7; $dia++) {
                                        echo '<td>';
                                        if (isset($horarios_por_dia[$dia])) {
                                            foreach ($horarios_por_dia[$dia] as $h) {
                                                // Si la hora actual está dentro del rango del horario
                                                if ($hora >= substr($h['hora_inicio'], 0, 5) . ':00' && $hora < substr($h['hora_fin'], 0, 5) . ':00') {
                                                    echo '<div class="p-1 mb-1 bg-primary text-white rounded">';
                                                    echo '<small><strong>' . $h['servicio_nombre'] . '</strong><br>';
                                                    echo date('h:i A', strtotime($h['hora_inicio'])) . ' - ' . date('h:i A', strtotime($h['hora_fin'])) . '<br>';
                                                    echo $h['entrenador_nombre'] . '</small>';
                                                    echo '</div>';
                                                }
                                            }
                                        }
                                        echo '</td>';
                                    }
                                    
                                    echo '</tr>';
                                }
                                
                                $conn->close();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Validación adicional para horas
document.addEventListener('DOMContentLoaded', function() {
    const horaInicioInput = document.getElementById('hora_inicio');
    const horaFinInput = document.getElementById('hora_fin');
    
    function validarHoras() {
        if (horaInicioInput.value && horaFinInput.value) {
            if (horaInicioInput.value >= horaFinInput.value) {
                horaFinInput.setCustomValidity('La hora de fin debe ser posterior a la hora de inicio');
            } else {
                horaFinInput.setCustomValidity('');
            }
        }
    }
    
    horaInicioInput.addEventListener('change', validarHoras);
    horaFinInput.addEventListener('change', validarHoras);
});
</script>
