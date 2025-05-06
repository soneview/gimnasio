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

// Procesar formulario de agregar/editar entrenador
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Validar datos comunes
        $nombre = sanitize($conn, $_POST['nombre']);
        $apellido = sanitize($conn, $_POST['apellido']);
        $email = sanitize($conn, $_POST['email']);
        $telefono = sanitize($conn, $_POST['telefono']);
        $especialidad = sanitize($conn, $_POST['especialidad']);
        $biografia = sanitize($conn, $_POST['biografia']);
        
        // Validar teléfono (11 números)
        if (!preg_match('/^\d{11}$/', $telefono)) {
            $mensaje = "El número de teléfono debe contener exactamente 11 dígitos.";
            $tipo_mensaje = "danger";
        } else {
            // Agregar nuevo entrenador
            if ($_POST['action'] === 'agregar') {
                // Verificar si el email ya existe
                $query = "SELECT id FROM usuarios WHERE email = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $mensaje = "El email ya está registrado. Por favor, use otro.";
                    $tipo_mensaje = "danger";
                } else {
                    // Generar contraseña aleatoria
                    $password = bin2hex(random_bytes(6)); // 12 caracteres hexadecimales
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Iniciar transacción
                    $conn->begin_transaction();
                    
                    try {
                        // 1. Insertar en la tabla usuarios
                        $query = "INSERT INTO usuarios (nombre, apellido, email, password, telefono, rol_id, estado) 
                                VALUES (?, ?, ?, ?, ?, 2, 1)";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("sssss", $nombre, $apellido, $email, $hashed_password, $telefono);
                        $stmt->execute();
                        
                        // Obtener el ID del usuario insertado
                        $usuario_id = $conn->insert_id;
                        
                        // 2. Insertar en la tabla entrenadores
                        $query = "INSERT INTO entrenadores (usuario_id, especialidad, biografia) 
                                VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("iss", $usuario_id, $especialidad, $biografia);
                        $stmt->execute();
                        
                        // Confirmar transacción
                        $conn->commit();
                        
                        $mensaje = "Entrenador agregado correctamente. Contraseña temporal: $password";
                        $tipo_mensaje = "success";
                    } catch (Exception $e) {
                        // Revertir transacción en caso de error
                        $conn->rollback();
                        $mensaje = "Error al agregar entrenador: " . $e->getMessage();
                        $tipo_mensaje = "danger";
                    }
                }
            } 
            // Editar entrenador existente
            elseif ($_POST['action'] === 'editar') {
                $usuario_id = (int)$_POST['usuario_id'];
                $entrenador_id = (int)$_POST['entrenador_id'];
                
                // Verificar si el email ya existe (excluyendo el usuario actual)
                $query = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("si", $email, $usuario_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $mensaje = "El email ya está registrado por otro usuario. Por favor, use otro.";
                    $tipo_mensaje = "danger";
                } else {
                    // Iniciar transacción
                    $conn->begin_transaction();
                    
                    try {
                        // 1. Actualizar la tabla usuarios
                        $query = "UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, telefono = ? WHERE id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("ssssi", $nombre, $apellido, $email, $telefono, $usuario_id);
                        $stmt->execute();
                        
                        // 2. Actualizar la tabla entrenadores
                        $query = "UPDATE entrenadores SET especialidad = ?, biografia = ? WHERE id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("ssi", $especialidad, $biografia, $entrenador_id);
                        $stmt->execute();
                        
                        // Confirmar transacción
                        $conn->commit();
                        
                        $mensaje = "Entrenador actualizado correctamente.";
                        $tipo_mensaje = "success";
                    } catch (Exception $e) {
                        // Revertir transacción en caso de error
                        $conn->rollback();
                        $mensaje = "Error al actualizar entrenador: " . $e->getMessage();
                        $tipo_mensaje = "danger";
                    }
                }
            }
        }
    }
}

// Procesar acciones (activar/desactivar/eliminar entrenador)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'activar' || $action === 'desactivar') {
        $estado = ($action === 'activar') ? 1 : 0;
        $query = "UPDATE usuarios SET estado = ? WHERE id = ? AND rol_id = 2";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $estado, $id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $mensaje = ($action === 'activar') ? "Entrenador activado correctamente." : "Entrenador desactivado correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "No se pudo realizar la acción.";
            $tipo_mensaje = "danger";
        }
    } 
    elseif ($action === 'eliminar') {
        // Verificar si hay horarios asociados al entrenador
        $query = "SELECT e.id FROM entrenadores e 
                  JOIN horarios h ON e.id = h.entrenador_id 
                  WHERE e.usuario_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $mensaje = "No se puede eliminar el entrenador porque tiene horarios asignados.";
            $tipo_mensaje = "danger";
        } else {
            // Iniciar transacción
            $conn->begin_transaction();
            
            try {
                // 1. Eliminar de la tabla entrenadores
                $query = "DELETE FROM entrenadores WHERE usuario_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                // 2. Eliminar de la tabla usuarios
                $query = "DELETE FROM usuarios WHERE id = ? AND rol_id = 2";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                
                // Confirmar transacción
                $conn->commit();
                
                $mensaje = "Entrenador eliminado correctamente.";
                $tipo_mensaje = "success";
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                $conn->rollback();
                $mensaje = "Error al eliminar entrenador: " . $e->getMessage();
                $tipo_mensaje = "danger";
            }
        }
    }
}

// Obtener entrenador para editar
$entrenador_editar = null;
if (isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $query = "SELECT u.*, e.id as entrenador_id, e.especialidad, e.biografia, e.foto
              FROM usuarios u
              JOIN entrenadores e ON u.id = e.usuario_id
              WHERE u.id = ? AND u.rol_id = 2";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $entrenador_editar = $result->fetch_assoc();
    }
}

// Obtener todos los entrenadores
$query = "SELECT u.*, e.id as entrenador_id, e.especialidad, e.biografia, e.foto
          FROM usuarios u
          JOIN entrenadores e ON u.id = e.usuario_id
          WHERE u.rol_id = 2
          ORDER BY u.nombre ASC";
$entrenadores = $conn->query($query);

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
                    <a href="entrenadores.php" class="list-group-item list-group-item-action active">
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
            
            <!-- Formulario para agregar/editar entrenador -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <?php echo $entrenador_editar ? 'Editar Entrenador' : 'Agregar Nuevo Entrenador'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form action="entrenadores.php" method="POST" class="row g-3">
                        <input type="hidden" name="action" value="<?php echo $entrenador_editar ? 'editar' : 'agregar'; ?>">
                        
                        <?php if ($entrenador_editar): ?>
                        <input type="hidden" name="usuario_id" value="<?php echo $entrenador_editar['id']; ?>">
                        <input type="hidden" name="entrenador_id" value="<?php echo $entrenador_editar['entrenador_id']; ?>">
                        <?php endif; ?>
                        
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required 
                                value="<?php echo $entrenador_editar ? htmlspecialchars($entrenador_editar['nombre']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="apellido" class="form-label">Apellido</label>
                            <input type="text" class="form-control" id="apellido" name="apellido" required 
                                value="<?php echo $entrenador_editar ? htmlspecialchars($entrenador_editar['apellido']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                value="<?php echo $entrenador_editar ? htmlspecialchars($entrenador_editar['email']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="telefono" class="form-label">Teléfono (11 dígitos)</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" 
                                pattern="[0-9]{11}" title="Debe contener 11 dígitos" required
                                value="<?php echo $entrenador_editar ? htmlspecialchars($entrenador_editar['telefono']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-12">
                            <label for="especialidad" class="form-label">Especialidad</label>
                            <input type="text" class="form-control" id="especialidad" name="especialidad" required 
                                value="<?php echo $entrenador_editar ? htmlspecialchars($entrenador_editar['especialidad']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-12">
                            <label for="biografia" class="form-label">Biografía</label>
                            <textarea class="form-control" id="biografia" name="biografia" rows="3"><?php echo $entrenador_editar ? htmlspecialchars($entrenador_editar['biografia']) : ''; ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $entrenador_editar ? 'Actualizar Entrenador' : 'Agregar Entrenador'; ?>
                            </button>
                            
                            <?php if ($entrenador_editar): ?>
                            <a href="entrenadores.php" class="btn btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de entrenadores -->
            <div class="card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i> Lista de Entrenadores</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Especialidad</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($entrenadores->num_rows > 0): ?>
                                    <?php while ($entrenador = $entrenadores->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($entrenador['nombre'] . ' ' . $entrenador['apellido']); ?></td>
                                            <td><?php echo htmlspecialchars($entrenador['email']); ?></td>
                                            <td><?php echo htmlspecialchars($entrenador['telefono']); ?></td>
                                            <td><?php echo htmlspecialchars($entrenador['especialidad']); ?></td>
                                            <td>
                                                <?php if ($entrenador['estado'] == 1): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?action=editar&id=<?php echo $entrenador['id']; ?>" class="btn btn-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <?php if ($entrenador['estado'] == 1): ?>
                                                        <a href="?action=desactivar&id=<?php echo $entrenador['id']; ?>" class="btn btn-warning" title="Desactivar" onclick="return confirm('¿Está seguro de desactivar este entrenador?')">
                                                            <i class="fas fa-user-slash"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?action=activar&id=<?php echo $entrenador['id']; ?>" class="btn btn-success" title="Activar">
                                                            <i class="fas fa-user-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="?action=eliminar&id=<?php echo $entrenador['id']; ?>" class="btn btn-danger" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este entrenador? Esta acción no se puede deshacer.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No hay entrenadores registrados</td>
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

<script>
// Validación adicional para el teléfono
document.addEventListener('DOMContentLoaded', function() {
    const telefonoInput = document.getElementById('telefono');
    
    telefonoInput.addEventListener('input', function(e) {
        // Eliminar cualquier carácter que no sea número
        this.value = this.value.replace(/[^0-9]/g, '');
        
        // Limitar a 11 dígitos
        if (this.value.length > 11) {
            this.value = this.value.slice(0, 11);
        }
    });
});
</script>
