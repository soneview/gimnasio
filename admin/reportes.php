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

// Verificar si existe la tabla de reportes, si no, crearla
$query = "SHOW TABLES LIKE 'reportes'";
$result = $conn->query($query);

if ($result->num_rows == 0) {
    // La tabla no existe, vamos a crearla
    $query = "CREATE TABLE `reportes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `titulo` varchar(100) NOT NULL,
        `descripcion` text DEFAULT NULL,
        `tipo` varchar(50) NOT NULL,
        `fecha_creacion` datetime DEFAULT current_timestamp(),
        `fecha_actualizacion` datetime DEFAULT NULL,
        `usuario_id` int(11) DEFAULT NULL,
        `estado` tinyint(1) DEFAULT 1 COMMENT '1-Pendiente, 2-En Proceso, 3-Resuelto, 4-Cancelado',
        PRIMARY KEY (`id`),
        KEY `usuario_id` (`usuario_id`),
        CONSTRAINT `reportes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    if ($conn->query($query) === TRUE) {
        $mensaje = "Tabla de reportes creada correctamente.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al crear la tabla de reportes: " . $conn->error;
        $tipo_mensaje = "danger";
    }
}

// Procesar formulario de agregar/editar reporte
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Validar datos comunes
        $titulo = sanitize($conn, $_POST['titulo']);
        $descripcion = sanitize($conn, $_POST['descripcion']);
        $tipo = sanitize($conn, $_POST['tipo']);
        $usuario_id = (isset($_POST['usuario_id']) && !empty($_POST['usuario_id'])) ? (int)$_POST['usuario_id'] : NULL;
        $estado = (int)$_POST['estado'];
        
        // Validaciones básicas
        $errores = [];
        
        if (empty($titulo)) {
            $errores[] = "El título es obligatorio.";
        }
        
        if (empty($tipo)) {
            $errores[] = "El tipo de reporte es obligatorio.";
        }
        
        // Si hay errores, mostrarlos
        if (!empty($errores)) {
            $mensaje = "Error: " . implode(" ", $errores);
            $tipo_mensaje = "danger";
        } else {
            // Agregar nuevo reporte
            if ($_POST['action'] === 'agregar') {
                $query = "INSERT INTO reportes (titulo, descripcion, tipo, usuario_id, estado, fecha_creacion) 
                        VALUES (?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssii", $titulo, $descripcion, $tipo, $usuario_id, $estado);
                
                if ($stmt->execute()) {
                    $mensaje = "Reporte agregado correctamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al agregar reporte: " . $conn->error;
                    $tipo_mensaje = "danger";
                }
            } 
            // Editar reporte existente
            elseif ($_POST['action'] === 'editar' && isset($_POST['reporte_id'])) {
                $reporte_id = (int)$_POST['reporte_id'];
                
                $query = "UPDATE reportes SET titulo = ?, descripcion = ?, tipo = ?, usuario_id = ?, estado = ?, fecha_actualizacion = NOW() WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sssiii", $titulo, $descripcion, $tipo, $usuario_id, $estado, $reporte_id);
                
                if ($stmt->execute()) {
                    $mensaje = "Reporte actualizado correctamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al actualizar reporte: " . $conn->error;
                    $tipo_mensaje = "danger";
                }
            }
        }
    }
}

// Procesar acciones (cambiar estado/eliminar reporte)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'pendiente' || $action === 'proceso' || $action === 'resuelto' || $action === 'cancelado') {
        $estados = [
            'pendiente' => 1,
            'proceso' => 2,
            'resuelto' => 3,
            'cancelado' => 4
        ];
        
        $estado = $estados[$action];
        $query = "UPDATE reportes SET estado = ?, fecha_actualizacion = NOW() WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $estado, $id);
        
        if ($stmt->execute()) {
            $mensaje = "Estado del reporte actualizado correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar el estado del reporte: " . $conn->error;
            $tipo_mensaje = "danger";
        }
    } 
    elseif ($action === 'eliminar') {
        $query = "DELETE FROM reportes WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $mensaje = "Reporte eliminado correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al eliminar reporte: " . $conn->error;
            $tipo_mensaje = "danger";
        }
    }
}

// Obtener reporte para editar
$reporte_editar = null;
if (isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $query = "SELECT * FROM reportes WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $reporte_editar = $result->fetch_assoc();
    }
}

// Obtener todos los usuarios activos
$query = "SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo, email 
          FROM usuarios 
          WHERE estado = 1 
          ORDER BY nombre ASC";
$usuarios = $conn->query($query);

// Obtener todos los reportes
$query = "SELECT r.*, CONCAT(u.nombre, ' ', u.apellido) as usuario_nombre
          FROM reportes r
          LEFT JOIN usuarios u ON r.usuario_id = u.id
          ORDER BY r.fecha_creacion DESC";
$reportes = $conn->query($query);

// Función para obtener el texto del estado
function getEstadoTexto($estado) {
    switch ($estado) {
        case 1:
            return ['texto' => 'Pendiente', 'clase' => 'warning'];
        case 2:
            return ['texto' => 'En Proceso', 'clase' => 'primary'];
        case 3:
            return ['texto' => 'Resuelto', 'clase' => 'success'];
        case 4:
            return ['texto' => 'Cancelado', 'clase' => 'danger'];
        default:
            return ['texto' => 'Desconocido', 'clase' => 'secondary'];
    }
}

// Obtener estadísticas de reportes
$estadisticas = [];
$query = "SELECT COUNT(*) as total FROM reportes WHERE estado = 1"; // Pendientes
$estadisticas['pendientes'] = $conn->query($query)->fetch_assoc()['total'];

$query = "SELECT COUNT(*) as total FROM reportes WHERE estado = 2"; // En Proceso
$estadisticas['en_proceso'] = $conn->query($query)->fetch_assoc()['total'];

$query = "SELECT COUNT(*) as total FROM reportes WHERE estado = 3"; // Resueltos
$estadisticas['resueltos'] = $conn->query($query)->fetch_assoc()['total'];

$query = "SELECT COUNT(*) as total FROM reportes WHERE estado = 4"; // Cancelados
$estadisticas['cancelados'] = $conn->query($query)->fetch_assoc()['total'];

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
                    <a href="reservas.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-check me-2"></i> Reservas
                    </a>
                    <a href="reportes.php" class="list-group-item list-group-item-action active">
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
            
            <!-- Estadísticas de reportes -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase">Pendientes</h6>
                                    <h2 class="mb-0"><?php echo $estadisticas['pendientes']; ?></h2>
                                </div>
                                <i class="fas fa-clock fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase">En Proceso</h6>
                                    <h2 class="mb-0"><?php echo $estadisticas['en_proceso']; ?></h2>
                                </div>
                                <i class="fas fa-spinner fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3 mb-md-0">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase">Resueltos</h6>
                                    <h2 class="mb-0"><?php echo $estadisticas['resueltos']; ?></h2>
                                </div>
                                <i class="fas fa-check-circle fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-danger text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase">Cancelados</h6>
                                    <h2 class="mb-0"><?php echo $estadisticas['cancelados']; ?></h2>
                                </div>
                                <i class="fas fa-ban fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Formulario para agregar/editar reporte -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <?php echo $reporte_editar ? 'Editar Reporte' : 'Agregar Nuevo Reporte'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form action="reportes.php" method="POST" class="row g-3">
                        <input type="hidden" name="action" value="<?php echo $reporte_editar ? 'editar' : 'agregar'; ?>">
                        
                        <?php if ($reporte_editar): ?>
                        <input type="hidden" name="reporte_id" value="<?php echo $reporte_editar['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="col-md-6">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required 
                                value="<?php echo $reporte_editar ? htmlspecialchars($reporte_editar['titulo']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="tipo" class="form-label">Tipo de Reporte</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="Incidencia" <?php echo ($reporte_editar && $reporte_editar['tipo'] == 'Incidencia') ? 'selected' : ''; ?>>Incidencia</option>
                                <option value="Mantenimiento" <?php echo ($reporte_editar && $reporte_editar['tipo'] == 'Mantenimiento') ? 'selected' : ''; ?>>Mantenimiento</option>
                                <option value="Sugerencia" <?php echo ($reporte_editar && $reporte_editar['tipo'] == 'Sugerencia') ? 'selected' : ''; ?>>Sugerencia</option>
                                <option value="Queja" <?php echo ($reporte_editar && $reporte_editar['tipo'] == 'Queja') ? 'selected' : ''; ?>>Queja</option>
                                <option value="Otro" <?php echo ($reporte_editar && $reporte_editar['tipo'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                            </select>
                        </div>
                        
                        <div class="col-md-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo $reporte_editar ? htmlspecialchars($reporte_editar['descripcion']) : ''; ?></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="usuario_id" class="form-label">Usuario Relacionado (Opcional)</label>
                            <select class="form-select" id="usuario_id" name="usuario_id">
                                <option value="">Ninguno</option>
                                <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                                    <option value="<?php echo $usuario['id']; ?>" <?php echo ($reporte_editar && $reporte_editar['usuario_id'] == $usuario['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($usuario['nombre_completo']); ?> (<?php echo htmlspecialchars($usuario['email']); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="1" <?php echo ($reporte_editar && $reporte_editar['estado'] == 1) ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="2" <?php echo ($reporte_editar && $reporte_editar['estado'] == 2) ? 'selected' : ''; ?>>En Proceso</option>
                                <option value="3" <?php echo ($reporte_editar && $reporte_editar['estado'] == 3) ? 'selected' : ''; ?>>Resuelto</option>
                                <option value="4" <?php echo ($reporte_editar && $reporte_editar['estado'] == 4) ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $reporte_editar ? 'Actualizar Reporte' : 'Agregar Reporte'; ?>
                            </button>
                            
                            <?php if ($reporte_editar): ?>
                            <a href="reportes.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de reportes -->
            <div class="card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Lista de Reportes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Usuario</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($reportes && $reportes->num_rows > 0): ?>
                                    <?php while ($reporte = $reportes->fetch_assoc()): 
                                        $estado = getEstadoTexto($reporte['estado']);
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($reporte['titulo']); ?></td>
                                            <td><?php echo htmlspecialchars($reporte['tipo']); ?></td>
                                            <td><?php echo $reporte['usuario_id'] ? htmlspecialchars($reporte['usuario_nombre']) : 'N/A'; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($reporte['fecha_creacion'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $estado['clase']; ?>">
                                                    <?php echo $estado['texto']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=editar&id=<?php echo $reporte['id']; ?>" class="btn btn-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <?php if ($reporte['estado'] == 1): ?>
                                                        <a href="?action=proceso&id=<?php echo $reporte['id']; ?>" class="btn btn-info" title="Marcar En Proceso" onclick="return confirm('¿Marcar este reporte como En Proceso?')">
                                                            <i class="fas fa-spinner"></i>
                                                        </a>
                                                    <?php elseif ($reporte['estado'] == 2): ?>
                                                        <a href="?action=resuelto&id=<?php echo $reporte['id']; ?>" class="btn btn-success" title="Marcar como Resuelto" onclick="return confirm('¿Marcar este reporte como Resuelto?')">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($reporte['estado'] != 4): ?>
                                                        <a href="?action=cancelado&id=<?php echo $reporte['id']; ?>" class="btn btn-warning" title="Cancelar Reporte" onclick="return confirm('¿Está seguro de cancelar este reporte?')">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="?action=eliminar&id=<?php echo $reporte['id']; ?>" class="btn btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este reporte? Esta acción no se puede deshacer.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No hay reportes registrados</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
