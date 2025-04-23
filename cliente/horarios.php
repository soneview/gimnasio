<?php
session_start();
require_once '../config/db.php';

// Verificar si el usuario está logueado y es cliente (rol_id = 3)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 3) {
    header("Location: ../auth/login.php");
    exit();
}

// Obtener listado de entrenadores y servicios para filtros
$conn = connectDB();

// Obtener entrenadores
$query = "SELECT e.id, u.nombre, u.apellido, e.especialidad 
          FROM entrenadores e 
          JOIN usuarios u ON e.usuario_id = u.id 
          WHERE u.estado = 1";
$entrenadores = $conn->query($query);

// Obtener servicios
$query = "SELECT id, nombre, duracion_minutos FROM servicios WHERE estado = 1";
$servicios = $conn->query($query);

// Filtros
$entrenador_id = isset($_GET['entrenador_id']) ? intval($_GET['entrenador_id']) : 0;
$servicio_id = isset($_GET['servicio_id']) ? intval($_GET['servicio_id']) : 0;
$dia = isset($_GET['dia']) ? intval($_GET['dia']) : 0;

// Construir consulta con filtros
$queryHorarios = "SELECT h.*, 
                 s.nombre as servicio_nombre, s.duracion_minutos,
                 CONCAT(u.nombre, ' ', u.apellido) as entrenador_nombre,
                 e.especialidad
                 FROM horarios h
                 JOIN servicios s ON h.servicio_id = s.id
                 JOIN entrenadores e ON h.entrenador_id = e.id
                 JOIN usuarios u ON e.usuario_id = u.id
                 WHERE h.estado = 1";

if ($entrenador_id > 0) {
    $queryHorarios .= " AND h.entrenador_id = " . $entrenador_id;
}

if ($servicio_id > 0) {
    $queryHorarios .= " AND h.servicio_id = " . $servicio_id;
}

if ($dia > 0) {
    $queryHorarios .= " AND h.dia_semana = " . $dia;
}

$queryHorarios .= " ORDER BY h.dia_semana, h.hora_inicio";
$horarios = $conn->query($queryHorarios);

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
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Horarios Disponibles</h5>
                </div>
                <div class="card-body">
                    <form action="" method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label for="entrenador_id" class="form-label">Entrenador</label>
                            <select class="form-select" id="entrenador_id" name="entrenador_id">
                                <option value="0">Todos los entrenadores</option>
                                <?php while ($entrenador = $entrenadores->fetch_assoc()): ?>
                                    <option value="<?php echo $entrenador['id']; ?>" <?php echo ($entrenador_id == $entrenador['id']) ? 'selected' : ''; ?>>
                                        <?php echo $entrenador['nombre'] . ' ' . $entrenador['apellido'] . ' (' . $entrenador['especialidad'] . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="servicio_id" class="form-label">Servicio</label>
                            <select class="form-select" id="servicio_id" name="servicio_id">
                                <option value="0">Todos los servicios</option>
                                <?php while ($servicio = $servicios->fetch_assoc()): ?>
                                    <option value="<?php echo $servicio['id']; ?>" <?php echo ($servicio_id == $servicio['id']) ? 'selected' : ''; ?>>
                                        <?php echo $servicio['nombre'] . ' (' . $servicio['duracion_minutos'] . ' min)'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="dia" class="form-label">Día de la semana</label>
                            <select class="form-select" id="dia" name="dia">
                                <option value="0">Todos los días</option>
                                <?php for ($i = 1; $i <= 7; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($dia == $i) ? 'selected' : ''; ?>>
                                        <?php echo getDiaSemana($i); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="horarios.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Limpiar Filtros
                            </a>
                        </div>
                    </form>
                    
                    <?php if ($horarios->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Día</th>
                                        <th>Horario</th>
                                        <th>Servicio</th>
                                        <th>Duración</th>
                                        <th>Entrenador</th>
                                        <th>Capacidad</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($horario = $horarios->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo getDiaSemana($horario['dia_semana']); ?></td>
                                            <td><?php echo $horario['hora_inicio'] . ' - ' . $horario['hora_fin']; ?></td>
                                            <td><?php echo $horario['servicio_nombre']; ?></td>
                                            <td><?php echo $horario['duracion_minutos'] . ' min'; ?></td>
                                            <td><?php echo $horario['entrenador_nombre']; ?></td>
                                            <td><?php echo $horario['capacidad_maxima']; ?> persona(s)</td>
                                            <td>
                                                <a href="nueva_reserva.php?horario_id=<?php echo $horario['id']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-calendar-plus"></i> Reservar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            No se encontraron horarios disponibles con los filtros seleccionados.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Instrucciones de reserva -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Cómo Reservar</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-1 me-2"></i> Busca tu horario ideal</h6>
                            <p>Utiliza los filtros para encontrar el horario, entrenador y servicio que mejor se adapten a tus necesidades.</p>
                            
                            <h6><i class="fas fa-2 me-2"></i> Selecciona la fecha</h6>
                            <p>Al hacer clic en "Reservar", podrás seleccionar la fecha específica para tu sesión.</p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-3 me-2"></i> Confirma tu reserva</h6>
                            <p>Revisa los detalles y confirma tu reserva. Recibirás una confirmación al instante.</p>
                            
                            <h6><i class="fas fa-4 me-2"></i> Administra tus reservas</h6>
                            <p>Puedes ver y gestionar todas tus reservas desde la sección "Mis Reservas".</p>
                        </div>
                    </div>
                    <div class="alert alert-warning mt-3">
                        <strong>Nota:</strong> Para reservar, debes tener una suscripción activa. Las reservas están sujetas a disponibilidad y deben realizarse con al menos 2 horas de antelación.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
