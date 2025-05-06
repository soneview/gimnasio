<?php
session_start();
require_once 'config/db.php';
?>

<?php include 'includes/header.php'; ?>

<!-- Estilos específicos para planes -->
<link rel="stylesheet" href="assets/css/planes.css">

<!-- Cabecera de Planes -->
<section class="bg-dark text-white py-5">
    <div class="container text-center">
        <h1>Nuestros Planes</h1>
        <p class="lead">Encuentra el plan perfecto para alcanzar tus objetivos fitness</p>
    </div>
</section>

<!-- Sección de Planes -->
<section class="section-padding">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="display-4 fw-bold">Selecciona tu plan</h2>
                <p class="lead">Elige la opción que mejor se adapte a tus objetivos y presupuesto</p>
            </div>

            <div class="row justify-content-center plan-cards">
                <!-- Plan Mensual -->
                <div class="col-md-4 mb-4 plan-card-animation">
                    <div class="card h-100 shadow-lg border-0 plan-card">
                        <div class="card-header bg-primary text-white text-center py-3">
                            <h3 class="mb-0">Mensual</h3>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="text-center mb-4">
                                <h2 class="card-price">$50</h2>
                                <p class="text-muted">5.000 Bs</p>
                            </div>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Acceso completo al gimnasio</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Asesoría de entrenadores</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Acceso a clases grupales</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Evaluación física inicial</li>
                            </ul>
                            <div class="mt-auto text-center">
                                <?php if(isset($_SESSION['user_id'])): ?>
                                    <a href="views/pago_membresia.php?plan=1" class="btn btn-primary btn-lg w-100">Adquirir Plan</a>
                                <?php else: ?>
                                    <a href="auth/login.php" class="btn btn-primary btn-lg w-100">Inicia Sesión para Adquirir</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Plan Trimestral -->
                <div class="col-md-4 mb-4 plan-card-animation">
                    <div class="card h-100 shadow-lg border-0 plan-card featured">
                        <div class="card-header bg-success text-white text-center py-3">
                            <h3 class="mb-0">Trimestral</h3>
                            <span class="badge bg-warning text-dark position-absolute top-0 end-0 mt-2 me-2">Popular</span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="text-center mb-4">
                                <h2 class="card-price">$150</h2>
                                <p class="text-muted">150.000 Bs</p>
                                <p class="text-success"><small>Ahorra un 10%</small></p>
                            </div>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Acceso completo al gimnasio</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Asesoría de entrenadores</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Acceso a clases grupales</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Evaluación física mensual</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Plan nutricional básico</li>
                            </ul>
                            <div class="mt-auto text-center">
                                <?php if(isset($_SESSION['user_id'])): ?>
                                    <a href="views/pago_membresia.php?plan=2" class="btn btn-success btn-lg w-100">Adquirir Plan</a>
                                <?php else: ?>
                                    <a href="auth/login.php" class="btn btn-success btn-lg w-100">Inicia Sesión para Adquirir</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Plan Anual -->
                <div class="col-md-4 mb-4 plan-card-animation">
                    <div class="card h-100 shadow-lg border-0 plan-card">
                        <div class="card-header bg-dark text-white text-center py-3">
                            <h3 class="mb-0">Anual</h3>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="text-center mb-4">
                                <h2 class="card-price">$500</h2>
                                <p class="text-muted">500.000 Bs</p>
                                <p class="text-success"><small>Ahorra un 20%</small></p>
                            </div>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Acceso completo al gimnasio</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Asesoría de entrenadores premium</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Acceso a todas las clases</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Evaluación física trimestral</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Plan nutricional completo</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>2 sesiones de entrenamiento personal</li>
                            </ul>
                            <div class="mt-auto text-center">
                                <?php if(isset($_SESSION['user_id'])): ?>
                                    <a href="views/pago_membresia.php?plan=3" class="btn btn-dark btn-lg w-100">Adquirir Plan</a>
                                <?php else: ?>
                                    <a href="auth/login.php" class="btn btn-dark btn-lg w-100">Inicia Sesión para Adquirir</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Obtener todos los botones de tipo de plan
    const planButtons = document.querySelectorAll('[data-plan]');
    const planesContainer = document.getElementById('planes-container');

    // Función para cargar los planes
    function cargarPlanes(tipo) {
        fetch(`api/planes.php?tipo=${tipo}`)
            .then(response => response.json())
            .then(planes => {
                planesContainer.innerHTML = '';
                planes.forEach(plan => {
                    const col = document.createElement('div');
                    col.className = 'col-md-4 mb-4';
                    col.innerHTML = `
                        <div class="plan-card h-100">
                            <h3>${plan.nombre}</h3>
                            <div class="plan-price">$${plan.precio}</div>
                            <p>${plan.descripcion}</p>
                            <p><strong>Duración:</strong> ${plan.duracion} días</p>
                            <div class="mt-auto">
                                <?php if(isset($_SESSION['user_id'])): ?>
                                    <a href="views/pago_membresia.php?plan=${plan.id}" class="btn btn-primary">Adquirir Plan</a>
                                <?php else: ?>
                                    <a href="auth/login.php" class="btn btn-primary">Inicia Sesión para Adquirir</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    `;
                    planesContainer.appendChild(col);
                });
            })
            .catch(error => console.error('Error:', error));
    }

    // Event listener para los botones de tipo de plan
    planButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remover clase active de todos los botones
            planButtons.forEach(btn => btn.classList.remove('active'));
            // Agregar clase active al botón clickeado
            this.classList.add('active');
            // Cargar los planes correspondientes
            cargarPlanes(this.dataset.plan);
        });
    });

    // Cargar planes mensuales por defecto
    cargarPlanes('mensual');
});
</script>

<!-- Beneficios de Membresía -->
<section class="section-padding bg-light">
    <div class="container">
        <div class="section-title">
            <h2>Beneficios de Nuestros Planes</h2>
            <p>Todos nuestros planes incluyen estos increíbles beneficios</p>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-user-friends fa-3x mb-3 text-primary"></i>
                        <h4>Entrenadores Profesionales</h4>
                        <p>Acceso a nuestro equipo de entrenadores certificados que te guiarán en tu camino fitness.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-alt fa-3x mb-3 text-primary"></i>
                        <h4>Horarios Flexibles</h4>
                        <p>Programa tus sesiones en horarios que se adapten a tu rutina diaria.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-3x mb-3 text-primary"></i>
                        <h4>Seguimiento de Progreso</h4>
                        <p>Monitoreo constante de tu progreso para asegurar que estás alcanzando tus metas.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-utensils fa-3x mb-3 text-primary"></i>
                        <h4>Consejos Nutricionales</h4>
                        <p>Recomendaciones personalizadas para complementar tu entrenamiento con una nutrición adecuada.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                        <h4>Comunidad de Apoyo</h4>
                        <p>Forma parte de una comunidad que te motivará y apoyará en todo momento.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-clipboard-check fa-3x mb-3 text-primary"></i>
                        <h4>Evaluación Inicial</h4>
                        <p>Evaluación completa para diseñar un programa personalizado según tus necesidades.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Preguntas Frecuentes -->
<section class="section-padding">
    <div class="container">
        <div class="section-title">
            <h2>Preguntas Frecuentes</h2>
            <p>Resolvemos tus dudas sobre nuestros planes y servicios</p>
        </div>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                ¿Puedo cambiar de plan una vez suscrito?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Sí, puedes cambiar de plan en cualquier momento. Si cambias a un plan de mayor valor, se te cobrará la diferencia. Si cambias a uno de menor valor, el cambio se aplicará en tu próxima renovación.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                ¿Cómo puedo cancelar mi suscripción?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Puedes cancelar tu suscripción en cualquier momento desde tu perfil de usuario o contactando con nuestro servicio de atención al cliente. La cancelación se hará efectiva al finalizar el período de suscripción activo.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                ¿Hay algún período mínimo de permanencia?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                No, no exigimos período mínimo de permanencia. Puedes cancelar tu suscripción cuando lo desees, aunque recomendamos mantener al menos tres meses para ver resultados significativos.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                ¿Puedo congelar mi suscripción?
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Sí, ofrecemos la posibilidad de congelar tu suscripción por un período máximo de 30 días al año. Esto es útil si vas de vacaciones o tienes alguna circunstancia que te impida asistir temporalmente.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="bg-primary text-white py-5">
    <div class="container text-center">
        <h2 class="mb-4">¿Listo para comenzar tu transformación?</h2>
        <p class="lead mb-4">Elige el plan que mejor se adapte a tus objetivos y comienza hoy mismo</p>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="#" class="btn btn-light btn-lg">Ver Planes</a>
        <?php else: ?>
            <a href="auth/register.php" class="btn btn-light btn-lg">Registrarse Ahora</a>
        <?php endif; ?>
    </div>
</section>

<!-- Script para animaciones al hacer scroll -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Función para animar elementos al hacer scroll
        function animateOnScroll() {
            const elements = document.querySelectorAll('.plan-card-animation');
            
            elements.forEach(element => {
                const elementPosition = element.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.2;
                
                if (elementPosition < screenPosition) {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }
            });
        }
        
        // Ejecutar la animación al cargar la página
        animateOnScroll();
        
        // Ejecutar la animación al hacer scroll
        window.addEventListener('scroll', animateOnScroll);
    });
</script>

<?php include 'includes/footer.php'; ?>
