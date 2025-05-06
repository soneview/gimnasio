<?php
session_start();
require_once '../config/db.php';

// Verificar si el usuario está logueado y es cliente (rol_id = 3)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 3) {
    header("Location: ../auth/login.php");
    exit();
}

// Obtener información del usuario
$conn = connectDB();
$userId = $_SESSION['user_id'];

// Obtener todas las suscripciones (activas e históricas)
$query = "SELECT s.*, p.nombre as plan_nombre, p.descripcion as plan_descripcion, p.precio, p.duracion_dias
          FROM suscripciones s 
          JOIN planes p ON s.plan_id = p.id 
          WHERE s.usuario_id = ? 
          ORDER BY s.estado DESC, s.fecha_fin DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Obtener información del usuario
$queryUser = "SELECT * FROM usuarios WHERE id = ?";
$stmtUser = $conn->prepare($queryUser);
$stmtUser->bind_param("i", $userId);
$stmtUser->execute();
$user = $stmtUser->get_result()->fetch_assoc();

$conn->close();
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
                    <a href="horarios.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-clock me-2"></i> Horarios Disponibles
                    </a>
                    <a href="suscripciones.php" class="list-group-item list-group-item-action active">
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
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="card-title">Mis Suscripciones</h2>
                            <p class="card-text">Gestiona tus suscripciones activas e históricas.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Suscripciones Activas -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i> Suscripciones Activas</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $hasActive = false;
                            $result->data_seek(0); // Resetear el puntero
                            
                            while ($suscripcion = $result->fetch_assoc()) {
                                if ($suscripcion['estado'] == 1 && strtotime($suscripcion['fecha_fin']) >= time()) {
                                    $hasActive = true;
                                    $diasRestantes = ceil((strtotime($suscripcion['fecha_fin']) - time()) / (60 * 60 * 24));
                                    $porcentajeTranscurrido = 100 - (($diasRestantes / $suscripcion['duracion_dias']) * 100);
                                    ?>
                                    <div class="subscription-card mb-4">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h4><?php echo htmlspecialchars($suscripcion['plan_nombre']); ?></h4>
                                                <p class="text-muted mb-2"><?php echo htmlspecialchars(substr($suscripcion['plan_descripcion'], 0, 100)); ?><?php echo (strlen($suscripcion['plan_descripcion']) > 100) ? '...' : ''; ?></p>
                                                <div class="d-flex flex-wrap">
                                                    <div class="me-4 mb-2">
                                                        <small class="text-muted">Fecha de inicio:</small>
                                                        <p class="mb-0"><strong><?php echo date('d/m/Y', strtotime($suscripcion['fecha_inicio'])); ?></strong></p>
                                                    </div>
                                                    <div class="me-4 mb-2">
                                                        <small class="text-muted">Fecha de vencimiento:</small>
                                                        <p class="mb-0"><strong><?php echo date('d/m/Y', strtotime($suscripcion['fecha_fin'])); ?></strong></p>
                                                    </div>
                                                    <div class="mb-2">
                                                        <small class="text-muted">Precio:</small>
                                                        <p class="mb-0"><strong>$<?php echo number_format($suscripcion['precio'], 2); ?></strong></p>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <p class="mb-1"><strong><?php echo $diasRestantes; ?> días restantes</strong></p>
                                                    <div class="progress" style="height: 10px;">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $porcentajeTranscurrido; ?>%;" aria-valuenow="<?php echo $porcentajeTranscurrido; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                                <a href="../views/pago_membresia.php" class="btn btn-outline-primary mb-2">
                                                    <i class="fas fa-sync-alt me-1"></i> Renovar
                                                </a>
                                                <a href="../comprobante.php?id=<?php echo $suscripcion['id']; ?>" class="btn btn-outline-secondary">
                                                    <i class="fas fa-file-pdf me-1"></i> Ver Comprobante
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            
                            if (!$hasActive) {
                                echo '<div class="alert alert-info text-center">
                                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                                        <h5>No tienes suscripciones activas</h5>
                                        <p>Adquiere un plan para disfrutar de nuestros servicios.</p>
                                        <a href="../planes.php" class="btn btn-primary mt-2">Ver Planes Disponibles</a>
                                      </div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Historial de Suscripciones -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i> Historial de Suscripciones</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $hasHistory = false;
                            $result->data_seek(0); // Resetear el puntero
                            
                            while ($suscripcion = $result->fetch_assoc()) {
                                if ($suscripcion['estado'] == 0 || strtotime($suscripcion['fecha_fin']) < time()) {
                                    $hasHistory = true;
                                    $estado = (strtotime($suscripcion['fecha_fin']) < time()) ? 'Vencida' : 'Cancelada';
                                    $estadoClass = (strtotime($suscripcion['fecha_fin']) < time()) ? 'text-warning' : 'text-danger';
                                    ?>
                                    <div class="subscription-history-item mb-3 p-3 border-bottom">
                                        <div class="row align-items-center">
                                            <div class="col-md-7">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($suscripcion['plan_nombre']); ?></h5>
                                                <div class="d-flex flex-wrap">
                                                    <div class="me-3">
                                                        <small class="text-muted">Periodo:</small>
                                                        <p class="mb-0"><?php echo date('d/m/Y', strtotime($suscripcion['fecha_inicio'])); ?> - <?php echo date('d/m/Y', strtotime($suscripcion['fecha_fin'])); ?></p>
                                                    </div>
                                                    <div>
                                                        <small class="text-muted">Estado:</small>
                                                        <p class="mb-0 <?php echo $estadoClass; ?>"><strong><?php echo $estado; ?></strong></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <small class="text-muted">Precio:</small>
                                                <p class="mb-0">$<?php echo number_format($suscripcion['precio'], 2); ?></p>
                                            </div>
                                            <div class="col-md-2 text-md-end mt-2 mt-md-0">
                                                <a href="../comprobante.php?id=<?php echo $suscripcion['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-file-pdf"></i> Comprobante
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            
                            if (!$hasHistory) {
                                echo '<p class="text-center text-muted">No tienes suscripciones anteriores.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilos adicionales -->
<style>
.subscription-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s ease;
    background-color: #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.subscription-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.subscription-history-item {
    transition: all 0.3s ease;
}

.subscription-history-item:hover {
    background-color: #f9f9f9;
}

.progress {
    border-radius: 20px;
    background-color: #e9ecef;
    overflow: hidden;
}

.progress-bar {
    border-radius: 20px;
}
</style>

<?php include '../includes/footer.php'; ?>
