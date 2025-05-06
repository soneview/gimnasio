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

// Procesar formulario de agregar/editar plan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Validar datos comunes
        $nombre = sanitize($conn, $_POST['nombre']);
        $descripcion = sanitize($conn, $_POST['descripcion']);
        $precio = floatval(str_replace(',', '.', $_POST['precio']));
        $duracion_dias = (int)$_POST['duracion_dias'];
        $estado = isset($_POST['estado']) ? 1 : 0;
        
        // Validaciones básicas
        $errores = [];
        
        if (empty($nombre)) {
            $errores[] = "El nombre del plan es obligatorio.";
        }
        
        if ($precio <= 0) {
            $errores[] = "El precio debe ser mayor que cero.";
        }
        
        if ($duracion_dias <= 0) {
            $errores[] = "La duración debe ser mayor que cero.";
        }
        
        // Si hay errores, mostrarlos
        if (!empty($errores)) {
            $mensaje = "Error: " . implode(" ", $errores);
            $tipo_mensaje = "danger";
        } else {
            // Agregar nuevo plan
            if ($_POST['action'] === 'agregar') {
                $query = "INSERT INTO planes (nombre, descripcion, precio, duracion_dias, estado) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssdii", $nombre, $descripcion, $precio, $duracion_dias, $estado);
                
                if ($stmt->execute()) {
                    $mensaje = "Plan agregado correctamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al agregar plan: " . $conn->error;
                    $tipo_mensaje = "danger";
                }
            } 
            // Editar plan existente
            elseif ($_POST['action'] === 'editar' && isset($_POST['plan_id'])) {
                $plan_id = (int)$_POST['plan_id'];
                
                $query = "UPDATE planes SET nombre = ?, descripcion = ?, precio = ?, duracion_dias = ?, estado = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssdiii", $nombre, $descripcion, $precio, $duracion_dias, $estado, $plan_id);
                
                if ($stmt->execute()) {
                    $mensaje = "Plan actualizado correctamente.";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al actualizar plan: " . $conn->error;
                    $tipo_mensaje = "danger";
                }
            }
        }
    }
}

// Procesar acciones (activar/desactivar/eliminar plan)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'activar' || $action === 'desactivar') {
        $estado = ($action === 'activar') ? 1 : 0;
        $query = "UPDATE planes SET estado = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $estado, $id);
        
        if ($stmt->execute()) {
            $mensaje = ($action === 'activar') ? "Plan activado correctamente." : "Plan desactivado correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar el estado del plan: " . $conn->error;
            $tipo_mensaje = "danger";
        }
    } 
    elseif ($action === 'eliminar') {
        // Verificar si hay suscripciones asociadas al plan
        $query = "SELECT COUNT(*) as total FROM suscripciones WHERE plan_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $suscripciones = $result->fetch_assoc()['total'];
        
        if ($suscripciones > 0) {
            $mensaje = "No se puede eliminar el plan porque tiene suscripciones asociadas.";
            $tipo_mensaje = "danger";
        } else {
            $query = "DELETE FROM planes WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $mensaje = "Plan eliminado correctamente.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al eliminar plan: " . $conn->error;
                $tipo_mensaje = "danger";
            }
        }
    }
}

// Obtener plan para editar
$plan_editar = null;
if (isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $query = "SELECT * FROM planes WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $plan_editar = $result->fetch_assoc();
    }
}

// Obtener todos los planes
$query = "SELECT * FROM planes ORDER BY precio ASC";
$planes = $conn->query($query);

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
                    <a href="planes.php" class="list-group-item list-group-item-action active">
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
            
            <!-- Formulario para agregar/editar plan -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <?php echo $plan_editar ? 'Editar Plan' : 'Agregar Nuevo Plan'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form action="planes.php" method="POST" class="row g-3">
                        <input type="hidden" name="action" value="<?php echo $plan_editar ? 'editar' : 'agregar'; ?>">
                        
                        <?php if ($plan_editar): ?>
                        <input type="hidden" name="plan_id" value="<?php echo $plan_editar['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre del Plan</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required 
                                value="<?php echo $plan_editar ? htmlspecialchars($plan_editar['nombre']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="precio" class="form-label">Precio (USD)</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="precio" name="precio" step="0.01" min="0.01" required 
                                    value="<?php echo $plan_editar ? number_format($plan_editar['precio'], 2) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="precio_bs" class="form-label">Precio (Bs)</label>
                            <div class="input-group">
                                <span class="input-group-text">Bs</span>
                                <input type="text" class="form-control" id="precio_bs" readonly 
                                    value="<?php echo $plan_editar ? number_format($plan_editar['precio'] * $tasa_cambio_bs, 2, ',', '.') : '0,00'; ?>">
                            </div>
                            <small class="text-muted">Calculado automáticamente</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="duracion_dias" class="form-label">Duración (días)</label>
                            <input type="number" class="form-control" id="duracion_dias" name="duracion_dias" min="1" required 
                                value="<?php echo $plan_editar ? $plan_editar['duracion_dias'] : '30'; ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="estado" name="estado" 
                                    <?php echo (!$plan_editar || ($plan_editar && $plan_editar['estado'] == 1)) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="estado">
                                    Plan Activo
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo $plan_editar ? htmlspecialchars($plan_editar['descripcion']) : ''; ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $plan_editar ? 'Actualizar Plan' : 'Agregar Plan'; ?>
                            </button>
                            
                            <?php if ($plan_editar): ?>
                            <a href="planes.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de planes -->
            <div class="card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i> Lista de Planes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Precio (USD)</th>
                                    <th>Precio (Bs)</th>
                                    <th>Duración</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($planes->num_rows > 0): ?>
                                    <?php while ($plan = $planes->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($plan['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($plan['descripcion'], 0, 50)) . (strlen($plan['descripcion']) > 50 ? '...' : ''); ?></td>
                                            <td>$<?php echo number_format($plan['precio'], 2); ?></td>
                                            <td>Bs <?php echo number_format($plan['precio'] * $tasa_cambio_bs, 2, ',', '.'); ?></td>
                                            <td><?php echo $plan['duracion_dias']; ?> días</td>
                                            <td>
                                                <?php if ($plan['estado'] == 1): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=editar&id=<?php echo $plan['id']; ?>" class="btn btn-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <?php if ($plan['estado'] == 1): ?>
                                                        <a href="?action=desactivar&id=<?php echo $plan['id']; ?>" class="btn btn-warning" title="Desactivar" onclick="return confirm('¿Está seguro de desactivar este plan?')">
                                                            <i class="fas fa-ban"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?action=activar&id=<?php echo $plan['id']; ?>" class="btn btn-success" title="Activar">
                                                            <i class="fas fa-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="?action=eliminar&id=<?php echo $plan['id']; ?>" class="btn btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este plan? Esta acción no se puede deshacer.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No hay planes registrados</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Tarjetas de planes (vista previa) -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Vista Previa de Planes</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php 
                                // Reiniciar el resultado para recorrerlo nuevamente
                                $conn = connectDB();
                                $query = "SELECT * FROM planes WHERE estado = 1 ORDER BY precio ASC";
                                $planes_preview = $conn->query($query);
                                $conn->close();
                                
                                if ($planes_preview->num_rows > 0):
                                    while ($plan = $planes_preview->fetch_assoc()):
                                ?>
                                    <div class="col-md-4 mb-4">
                                        <div class="card h-100 border-primary">
                                            <div class="card-header bg-primary text-white text-center">
                                                <h5 class="mb-0"><?php echo htmlspecialchars($plan['nombre']); ?></h5>
                                            </div>
                                            <div class="card-body text-center">
                                                <h3 class="card-title pricing-card-title">
                                                    $<?php echo number_format($plan['precio'], 2); ?>
                                                    <small class="text-muted">/ <?php echo $plan['duracion_dias']; ?> días</small>
                                                </h3>
                                                <p class="text-muted mb-3">Bs <?php echo number_format($plan['precio'] * $tasa_cambio_bs, 2, ',', '.'); ?></p>
                                                <p class="card-text"><?php echo htmlspecialchars($plan['descripcion']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <div class="col-12 text-center">
                                        <p>No hay planes activos para mostrar.</p>
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
