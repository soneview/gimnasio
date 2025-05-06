<?php
session_start();
require_once 'config/db.php';
?>

<?php include 'includes/header.php'; ?>

<!-- Cabecera de Servicios -->
<section class="bg-dark text-white py-5">
    <div class="container text-center">
        <h1>Nuestros Servicios</h1>
        <p class="lead">Descubre todos los servicios que tenemos para ayudarte a lograr tus objetivos</p>
    </div>
</section>

<!-- Sección de Servicios -->
<section class="section-padding">
    <div class="container">
        <div class="row">
            <?php
            $conn = connectDB();
            $query = "SELECT * FROM servicios WHERE estado = 1";
            $result = $conn->query($query);
            
            if ($result->num_rows > 0) {
                while ($servicio = $result->fetch_assoc()) {
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-dumbbell fa-3x mb-3 text-primary"></i>
                            <h4 class="card-title"><?php echo $servicio['nombre']; ?></h4>
                            <p class="card-text"><?php echo $servicio['descripcion']; ?></p>
                            <p><strong>Duración:</strong> <?php echo $servicio['duracion_minutos']; ?> minutos</p>
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
    </div>
</section>

<!-- Beneficios de los Servicios -->
<section class="section-padding bg-light">
    <div class="container">
        <div class="section-title">
            <h2>Beneficios de Nuestros Servicios</h2>
            <p>¿Por qué elegir nuestros servicios de entrenamiento?</p>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title">Entrenamiento Personalizado</h4>
                        <p class="card-text">Nuestros programas de entrenamiento personalizado ofrecen:</p>
                        <ul>
                            <li>Evaluación inicial completa para conocer tus necesidades</li>
                            <li>Programas adaptados específicamente a tus objetivos</li>
                            <li>Atención one-to-one con un entrenador profesional</li>
                            <li>Seguimiento constante de tu progreso</li>
                            <li>Modificación del programa según tus avances</li>
                            <li>Asesoramiento nutricional complementario</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title">Entrenamiento en Grupo</h4>
                        <p class="card-text">Nuestras clases grupales te ofrecen:</p>
                        <ul>
                            <li>Ambiente motivador y energético</li>
                            <li>Variedad de disciplinas y estilos de entrenamiento</li>
                            <li>Horarios flexibles que se adaptan a tu agenda</li>
                            <li>Interacción social y trabajo en equipo</li>
                            <li>Instructores especializados en cada disciplina</li>
                            <li>Diferentes niveles de intensidad para todos los perfiles</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title">Asesoramiento Nutricional</h4>
                        <p class="card-text">Nuestro servicio de nutrición incluye:</p>
                        <ul>
                            <li>Análisis de composición corporal</li>
                            <li>Plan nutricional personalizado</li>
                            <li>Seguimiento y ajustes periódicos</li>
                            <li>Educación sobre hábitos alimenticios saludables</li>
                            <li>Combinación estratégica con tu plan de entrenamiento</li>
                            <li>Consejos prácticos para tu día a día</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title">Rehabilitación Física</h4>
                        <p class="card-text">Nuestro programa de rehabilitación ofrece:</p>
                        <ul>
                            <li>Evaluación detallada de lesiones o limitaciones</li>
                            <li>Programas específicos para recuperación</li>
                            <li>Técnicas avanzadas de rehabilitación</li>
                            <li>Prevención de futuras lesiones</li>
                            <li>Trabajo coordinado con otros profesionales de la salud</li>
                            <li>Transición gradual al entrenamiento regular</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Horarios de Servicios -->
<section class="section-padding">
    <div class="container">
        <div class="section-title">
            <h2>Horarios de Servicios</h2>
            <p>Planifica tu semana con nuestros horarios de clases y servicios</p>
        </div>
        
        <div class="schedule-table">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>Lunes</th>
                        <th>Martes</th>
                        <th>Miércoles</th>
                        <th>Jueves</th>
                        <th>Viernes</th>
                        <th>Sábado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>8:00 - 9:00</td>
                        <td>Entrenamiento Funcional</td>
                        <td>Yoga</td>
                        <td>HIIT</td>
                        <td>Pilates</td>
                        <td>Entrenamiento Funcional</td>
                        <td>Spinning</td>
                    </tr>
                    <tr>
                        <td>9:30 - 10:30</td>
                        <td>Spinning</td>
                        <td>Body Pump</td>
                        <td>Spinning</td>
                        <td>Body Pump</td>
                        <td>Zumba</td>
                        <td>Yoga</td>
                    </tr>
                    <tr>
                        <td>11:00 - 12:00</td>
                        <td>Pilates</td>
                        <td>HIIT</td>
                        <td>Pilates</td>
                        <td>Zumba</td>
                        <td>Pilates</td>
                        <td>Body Pump</td>
                    </tr>
                    <tr>
                        <td>17:00 - 18:00</td>
                        <td>Body Pump</td>
                        <td>Spinning</td>
                        <td>Body Pump</td>
                        <td>Entrenamiento Funcional</td>
                        <td>HIIT</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>18:30 - 19:30</td>
                        <td>HIIT</td>
                        <td>Zumba</td>
                        <td>Entrenamiento Funcional</td>
                        <td>Spinning</td>
                        <td>Yoga</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>20:00 - 21:00</td>
                        <td>Zumba</td>
                        <td>Yoga</td>
                        <td>Zumba</td>
                        <td>HIIT</td>
                        <td>Spinning</td>
                        <td>-</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="text-center mt-4">
            <p>Los horarios pueden estar sujetos a cambios. Por favor, verifica la disponibilidad al momento de reservar.</p>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="cliente/reservas.php" class="btn btn-primary">Reservar un Servicio</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn btn-primary">Iniciar Sesión para Reservar</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="bg-primary text-white py-5">
    <div class="container text-center">
        <h2 class="mb-4">¿Listo para experimentar nuestros servicios?</h2>
        <p class="lead mb-4">Reserva ahora y comienza tu transformación</p>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="cliente/reservas.php" class="btn btn-light btn-lg">Reservar Ahora</a>
        <?php else: ?>
            <a href="auth/register.php" class="btn btn-light btn-lg">Registrarse</a>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
