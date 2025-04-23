<?php
session_start();
require_once 'config/db.php';
?>

<?php include 'includes/header.php'; ?>

<!-- Cabecera de Instructores -->
<section class="bg-dark text-white py-5">
    <div class="container text-center">
        <h1>Nuestros Instructores</h1>
        <p class="lead">Conoce a nuestro equipo de profesionales expertos en fitness y salud</p>
    </div>
</section>

<!-- Sección de Instructores -->
<section class="section-padding">
    <div class="container">
        <div class="row">
            <?php
            $conn = connectDB();
            $query = "SELECT e.*, u.nombre, u.apellido FROM entrenadores e 
                      JOIN usuarios u ON e.usuario_id = u.id 
                      WHERE u.estado = 1";
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                while ($entrenador = $result->fetch_assoc()) {
                    $nombreCompleto = $entrenador['nombre'] . ' ' . $entrenador['apellido'];
                    $foto = !empty($entrenador['foto']) ? $entrenador['foto'] : 'assets/img/default-trainer.jpg';
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="<?php echo $foto; ?>" class="card-img-top" alt="<?php echo $nombreCompleto; ?>">
                        <div class="card-body">
                            <h4 class="card-title"><?php echo $nombreCompleto; ?></h4>
                            <p class="text-muted"><?php echo $entrenador['especialidad']; ?></p>
                            <p class="card-text"><?php echo $entrenador['biografia']; ?></p>
                            <a href="instructor_detalle.php?id=<?php echo $entrenador['id']; ?>" class="btn btn-outline-primary">Ver Horarios</a>
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
            ?>
        </div>
    </div>
</section>

<!-- Sección de Especialidades -->
<section class="section-padding bg-light">
    <div class="container">
        <div class="section-title">
            <h2>Nuestras Especialidades</h2>
            <p>Ofrecemos entrenamiento especializado en diversas áreas</p>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-dumbbell fa-3x mb-3 text-primary"></i>
                        <h4>Entrenamiento de Fuerza</h4>
                        <p>Aumenta tu fuerza y masa muscular con nuestros programas especializados.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-heartbeat fa-3x mb-3 text-primary"></i>
                        <h4>Entrenamiento Cardiovascular</h4>
                        <p>Mejora tu resistencia y salud cardiovascular con nuestras rutinas personalizadas.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-weight fa-3x mb-3 text-primary"></i>
                        <h4>Pérdida de Peso</h4>
                        <p>Programas diseñados específicamente para ayudarte a perder peso de forma saludable.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-spa fa-3x mb-3 text-primary"></i>
                        <h4>Yoga y Flexibilidad</h4>
                        <p>Mejora tu flexibilidad, equilibrio y bienestar general con nuestras clases de yoga.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-running fa-3x mb-3 text-primary"></i>
                        <h4>HIIT y Entrenamiento Funcional</h4>
                        <p>Entrena de forma eficiente con nuestras sesiones de alta intensidad.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-user-injured fa-3x mb-3 text-primary"></i>
                        <h4>Rehabilitación Deportiva</h4>
                        <p>Recuperación de lesiones y fortalecimiento bajo la supervisión de profesionales.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Sección de Certificaciones -->
<section class="section-padding">
    <div class="container">
        <div class="section-title">
            <h2>Certificaciones y Formación</h2>
            <p>Nuestros instructores están altamente cualificados con certificaciones de prestigio</p>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title">Certificaciones Profesionales</h4>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">Certified Personal Trainer (CPT)</li>
                            <li class="list-group-item">Certified Strength and Conditioning Specialist (CSCS)</li>
                            <li class="list-group-item">Certified Group Fitness Instructor</li>
                            <li class="list-group-item">Certified Sports Nutritionist</li>
                            <li class="list-group-item">Certified Exercise Physiologist</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title">Formación Continua</h4>
                        <p>Nuestros entrenadores participan regularmente en:</p>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">Seminarios de actualización en técnicas de entrenamiento</li>
                            <li class="list-group-item">Conferencias internacionales sobre fitness y salud</li>
                            <li class="list-group-item">Cursos de especialización en áreas específicas</li>
                            <li class="list-group-item">Talleres prácticos de nuevas metodologías</li>
                            <li class="list-group-item">Investigación y desarrollo de programas personalizados</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Reserva una Sesión -->
<section class="bg-primary text-white py-5">
    <div class="container text-center">
        <h2 class="mb-4">¿Listo para empezar a entrenar?</h2>
        <p class="lead mb-4">Reserva una sesión con uno de nuestros entrenadores profesionales</p>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="cliente/reservas.php" class="btn btn-light btn-lg">Reservar Ahora</a>
        <?php else: ?>
            <a href="auth/login.php" class="btn btn-light btn-lg">Iniciar Sesión para Reservar</a>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
