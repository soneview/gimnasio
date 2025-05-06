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

// Verificar si existe la columna precio en la tabla servicios, si no, agregarla
$query = "SHOW COLUMNS FROM servicios LIKE 'precio'";
$result = $conn->query($query);

if ($result->num_rows == 0) {
    // La columna no existe, vamos a agregarla
    $query = "ALTER TABLE servicios ADD COLUMN precio decimal(10,2) DEFAULT 0.00 AFTER duracion_minutos";
    
    if ($conn->query($query) === TRUE) {
        $mensaje = "Se ha agregado el campo de precio a los servicios.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al agregar el campo de precio: " . $conn->error;
        $tipo_mensaje = "danger";
    }
}

// Procesar formulario de agregar/editar servicio
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Validar datos comunes
        $nombre = sanitize($conn, $_POST['nombre']);
        $descripcion = sanitize($conn, $_POST['descripcion']);
        $duracion_minutos = (int)$_POST['duracion_minutos'];
        $precio = floatval(str_replace(',', '.', $_POST['precio']));
        $estado = isset($_POST['estado']) ? 1 : 0;
        
        // Validaciones básicas
        $errores = [];
        
        if (empty($nombre)) {
            $errores[] = "El nombre del servicio es obligatorio.";
        }
        
        if ($duracion_minutos <= 0) {
            $errores[] = "La duración debe ser mayor que cero.";
        }
        
        if ($precio < 0) {
            $errores[] = "El precio no puede ser negativo.";
        }
        
        // Si hay errores, mostrarlos
        if (!empty($errores)) {
            $mensaje = "Error: " . implode(" ", $errores);
            $tipo_mensaje = "danger";
        } else {
            // Agregar nuevo servicio
            if ($_POST['action'] === 'agregar') {
                $query = "INSERT INTO servicios (nombre, descripcion, duracion_minutos, precio, estado) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssidi", $nombre, $descripcion, $duracion_minutos, $precio, $estado);
                
                if ($stmt->execute()) {
                    $mensaje = "Servicio agregado correctamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al agregar servicio: " . $conn->error;
                    $tipo_mensaje = "danger";
                }
            } 
            // Editar servicio existente
            elseif ($_POST['action'] === 'editar' && isset($_POST['servicio_id'])) {
                $servicio_id = (int)$_POST['servicio_id'];
                
                $query = "UPDATE servicios SET nombre = ?, descripcion = ?, duracion_minutos = ?, precio = ?, estado = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssidii", $nombre, $descripcion, $duracion_minutos, $precio, $estado, $servicio_id);
                
                if ($stmt->execute()) {
                    $mensaje = "Servicio actualizado correctamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al actualizar servicio: " . $conn->error;
                    $tipo_mensaje = "danger";
                }
            }
        }
    }
}

// Procesar acciones (activar/desactivar/eliminar servicio)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'activar' || $action === 'desactivar') {
        $estado = ($action === 'activar') ? 1 : 0;
        $query = "UPDATE servicios SET estado = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $estado, $id);
        
        if ($stmt->execute()) {
            $mensaje = ($action === 'activar') ? "Servicio activado correctamente." : "Servicio desactivado correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar el estado del servicio: " . $conn->error;
            $tipo_mensaje = "danger";
        }
    } 
    elseif ($action === 'eliminar') {
        // Verificar si hay horarios asociados al servicio
        $query = "SELECT COUNT(*) as total FROM horarios WHERE servicio_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $horarios = $result->fetch_assoc()['total'];
        
        if ($horarios > 0) {
            $mensaje = "No se puede eliminar el servicio porque tiene horarios asociados.";
            $tipo_mensaje = "danger";
        } else {
            $query = "DELETE FROM servicios WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $mensaje = "Servicio eliminado correctamente.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al eliminar servicio: " . $conn->error;
                $tipo_mensaje = "danger";
            }
        }
    }
}

// Obtener servicio para editar
$servicio_editar = null;
if (isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $query = "SELECT * FROM servicios WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $servicio_editar = $result->fetch_assoc();
    }
}

// Obtener todos los servicios
$query = "SELECT * FROM servicios ORDER BY nombre ASC";
$servicios = $conn->query($query);

// Tasa de cambio USD a Bolívares (esto podría venir de una tabla de configuración)
$tasa_cambio_bs = 100; // 1 USD = 100 Bs (ejemplo)

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
                    <a href="servicios.php" class="list-group-item list-group-item-action active">
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
            <!-- Mensaje de alerta -->
            <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Formulario para agregar/editar servicio -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <?php echo $servicio_editar ? 'Editar Servicio' : 'Agregar Nuevo Servicio'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form action="servicios.php" method="POST" class="row g-3">
                        <input type="hidden" name="action" value="<?php echo $servicio_editar ? 'editar' : 'agregar'; ?>">
                        
                        <?php if ($servicio_editar): ?>
                        <input type="hidden" name="servicio_id" value="<?php echo $servicio_editar['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre del Servicio</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required 
                                value="<?php echo $servicio_editar ? htmlspecialchars($servicio_editar['nombre']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="duracion_minutos" class="form-label">Duración (minutos)</label>
                            <input type="number" class="form-control" id="duracion_minutos" name="duracion_minutos" min="1" required 
                                value="<?php echo $servicio_editar ? $servicio_editar['duracion_minutos'] : '60'; ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="precio" class="form-label">Precio (USD)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0" required 
                                    value="<?php echo $servicio_editar ? number_format($servicio_editar['precio'], 2) : '0.00'; ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="precio_bs" class="form-label">Precio (Bs)</label>
                            <div class="input-group">
                                <span class="input-group-text">Bs</span>
                                <input type="text" class="form-control" id="precio_bs" readonly 
                                    value="<?php echo $servicio_editar ? number_format($servicio_editar['precio'] * $tasa_cambio_bs, 2, ',', '.') : '0,00'; ?>">
                            </div>
                            <small class="text-muted">Calculado automáticamente</small>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="estado" name="estado" 
                                    <?php echo (!$servicio_editar || ($servicio_editar && $servicio_editar['estado'] == 1)) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="estado">
                                    Servicio Activo
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo $servicio_editar ? htmlspecialchars($servicio_editar['descripcion']) : ''; ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $servicio_editar ? 'Actualizar Servicio' : 'Agregar Servicio'; ?>
                            </button>
                            
                            <?php if ($servicio_editar): ?>
                            <a href="servicios.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de servicios -->
            <div class="card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-dumbbell me-2"></i> Lista de Servicios</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Duración</th>
                                    <th>Precio (USD)</th>
                                    <th>Precio (Bs)</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($servicios->num_rows > 0): ?>
                                    <?php while ($servicio = $servicios->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($servicio['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($servicio['descripcion'], 0, 50)) . (strlen($servicio['descripcion']) > 50 ? '...' : ''); ?></td>
                                            <td><?php echo $servicio['duracion_minutos']; ?> min</td>
                                            <td>$<?php echo number_format($servicio['precio'], 2); ?></td>
                                            <td>Bs <?php echo number_format($servicio['precio'] * $tasa_cambio_bs, 2, ',', '.'); ?></td>
                                            <td>
                                                <?php if ($servicio['estado'] == 1): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=editar&id=<?php echo $servicio['id']; ?>" class="btn btn-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <?php if ($servicio['estado'] == 1): ?>
                                                        <a href="?action=desactivar&id=<?php echo $servicio['id']; ?>" class="btn btn-warning" title="Desactivar" onclick="return confirm('¿Está seguro de desactivar este servicio?')">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?action=activar&id=<?php echo $servicio['id']; ?>" class="btn btn-success" title="Activar">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="?action=eliminar&id=<?php echo $servicio['id']; ?>" class="btn btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este servicio? Esta acción no se puede deshacer.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No hay servicios registrados</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Tarjetas de servicios (vista previa) -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Vista Previa de Servicios</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php 
                                // Reiniciar el resultado para recorrerlo nuevamente
                                $conn = connectDB();
                                $query = "SELECT * FROM servicios WHERE estado = 1 ORDER BY nombre ASC";
                                $servicios_preview = $conn->query($query);
                                $conn->close();
                                
                                if ($servicios_preview->num_rows > 0):
                                    while ($servicio = $servicios_preview->fetch_assoc()):
                                ?>
                                    <div class="col-md-4 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-dumbbell fa-3x mb-3 text-primary"></i>
                                                <h4 class="card-title"><?php echo htmlspecialchars($servicio['nombre']); ?></h4>
                                                <p class="card-text"><?php echo htmlspecialchars($servicio['descripcion']); ?></p>
                                                <p><strong>Duración:</strong> <?php echo $servicio['duracion_minutos']; ?> minutos</p>
                                                <p class="text-primary fw-bold">
                                                    $<?php echo number_format($servicio['precio'], 2); ?> / 
                                                    Bs <?php echo number_format($servicio['precio'] * $tasa_cambio_bs, 2, ',', '.'); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <div class="col-12 text-center">
                                        <p>No hay servicios activos para mostrar.</p>
                                    </div>
                                <?php endif; ?>
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
    // Función para calcular el precio en bolívares
    function calcularPrecioBs() {
        const precioUsd = document.getElementById('precio').value;
        const tasaCambio = <?php echo $tasa_cambio_bs; ?>;
        const precioBs = precioUsd * tasaCambio;
        
        document.getElementById('precio_bs').value = precioBs.toLocaleString('es-VE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).replace('.', ',');
    }
    
    // Calcular precio en bolívares cuando cambia el precio en USD
    document.getElementById('precio').addEventListener('input', calcularPrecioBs);
    
    // Calcular inicialmente
    calcularPrecioBs();
});
</script>
