<?php
session_start();
require_once 'config/db.php';
?>

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <h1>Alcanza tu mejor versión con Vitaminadaspor</h1>
                <p class="lead">Entrenadores profesionales, horarios flexibles y planes personalizados para ayudarte a lograr tus objetivos fitness.</p>
                <a href="planes.php" class="btn btn-primary btn-lg">Ver Planes</a>
                <a href="auth/register.php" class="btn btn-outline-light btn-lg ms-2">Registrarse</a>
            </div>
        </div>
    </div>
</section>

<!-- Servicios Destacados -->
<section class="section-padding">
    <div class="container">
        <div class="section-title">
            <h2>Nuestros Servicios</h2>
            <p>Ofrecemos una amplia variedad de servicios para ayudarte a alcanzar tus metas fitness</p>
        </div>
        
        <div class="row">
            <?php
            $conn = connectDB();
            $query = "SELECT * FROM servicios WHERE estado = 1 LIMIT 3";
            $result = $conn->query($query);
            
            if ($result->num_rows > 0) {
                while ($servicio = $result->fetch_assoc()) {
            ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-dumbbell fa-3x mb-3 text-primary"></i>
                            <h4 class="card-title"><?php echo $servicio['nombre']; ?></h4>
                            <p class="card-text"><?php echo $servicio['descripcion']; ?></p>
                            <p><strong>Duración:</strong> <?php echo $servicio['duracion_minutos']; ?> minutos</p>
                            <a href="servicios.php" class="btn btn-outline-primary">Más Información</a>
                        </div>
                    </div>
                </div>
            <?php
                }
            } else {
            ?>
                <div class="col-12 text-center">
                    <p>No hay servicios disponibles en este momento.</p>
                </div>
            <?php
            }
            $conn->close();
            ?>
        </div>
        <div class="text-center mt-4">
            <a href="servicios.php" class="btn btn-primary">Ver Todos los Servicios</a>
        </div>
    </div>
</section>

<!-- Planes -->
<section class="section-padding bg-light">
    <div class="container">
        <div class="section-title">
            <h2>Nuestros Planes</h2>
            <p>Elige el plan que mejor se adapte a tus necesidades y objetivos</p>
        </div>
        
        <div class="row">
            <?php
            $conn = connectDB();
            $query = "SELECT * FROM planes WHERE estado = 1 LIMIT 3";
            $result = $conn->query($query);
            
            if ($result->num_rows > 0) {
                while ($plan = $result->fetch_assoc()) {
            ?>
                <div class="col-md-4">
                    <div class="plan-card h-100">
                        <h3><?php echo $plan['nombre']; ?></h3>
                        <div class="plan-price">$<?php echo number_format($plan['precio'], 2); ?></div>
                        <p><?php echo $plan['descripcion']; ?></p>
                        <p><strong>Duración:</strong> <?php echo $plan['duracion_dias']; ?> días</p>
                        <a href="planes.php" class="btn btn-primary">Seleccionar Plan</a>
                    </div>
                </div>
            <?php
                }
            } else {
            ?>
                <div class="col-12 text-center">
                    <p>No hay planes disponibles en este momento.</p>
                </div>
            <?php
            }
            $conn->close();
            ?>
        </div>
        <div class="text-center mt-4">
            <a href="planes.php" class="btn btn-outline-dark">Ver Todos los Planes</a>
        </div>
    </div>
</section>

<!-- Entrenadores Destacados -->
<section class="section-padding">
    <div class="container">
        <div class="section-title">
            <h2>Nuestros Entrenadores</h2>
            <p>Conoce a nuestro equipo de profesionales expertos en fitness y salud</p>
        </div>
        
        <div class="row">
            <?php
            $conn = connectDB();
            $query = "SELECT e.*, u.nombre, u.apellido FROM entrenadores e 
                      JOIN usuarios u ON e.usuario_id = u.id 
                      WHERE u.estado = 1 
                      LIMIT 3";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                while ($entrenador = $result->fetch_assoc()) {
                    $nombreCompleto = $entrenador['nombre'] . ' ' . $entrenador['apellido'];
                    $foto = !empty($entrenador['foto']) ? $entrenador['foto'] : 'assets/img/default-trainer.jpg';
            ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <img src="<?php echo $foto; ?>" class="card-img-top" alt="<?php echo $nombreCompleto; ?>">
                        <div class="card-body text-center">
                            <h4 class="card-title"><?php echo $nombreCompleto; ?></h4>
                            <p class="text-muted"><?php echo $entrenador['especialidad']; ?></p>
                            <p class="card-text"><?php echo substr($entrenador['biografia'], 0, 100) . '...'; ?></p>
                            <a href="instructores.php" class="btn btn-outline-primary">Ver Perfil</a>
                        </div>
                    </div>
                </div>
            <?php
                }
            } else {
            ?>
                <div class="col-12 text-center">
                    <p>No hay entrenadores disponibles en este momento.</p>
                </div>
            <?php
            }
            $conn->close();
            ?>
        </div>
        <div class="text-center mt-4">
            <a href="instructores.php" class="btn btn-primary">Ver Todos los Entrenadores</a>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="bg-primary text-white py-5">
    <div class="container text-center">
        <h2 class="mb-4">¿Listo para transformar tu vida?</h2>
        <p class="lead mb-4">Únete a nuestra comunidad y comienza tu viaje hacia un estilo de vida más saludable</p>
        <a href="auth/register.php" class="btn btn-light btn-lg">Comienza Ahora</a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
