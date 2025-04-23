<?php
session_start();
require_once 'config/db.php';

$message = '';
$messageType = '';

// Procesar formulario de contacto
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conn = connectDB();
    
    $nombre = sanitize($conn, $_POST['nombre']);
    $email = sanitize($conn, $_POST['email']);
    $telefono = sanitize($conn, isset($_POST['telefono']) ? $_POST['telefono'] : '');
    $asunto = sanitize($conn, $_POST['asunto']);
    $mensaje = sanitize($conn, $_POST['mensaje']);
    
    // Validar campos
    if (empty($nombre) || empty($email) || empty($asunto) || empty($mensaje)) {
        $message = "Por favor, complete todos los campos obligatorios.";
        $messageType = "danger";
    } else {
        // Aquí normalmente enviarías el email, pero simularemos que se envió correctamente
        // En una implementación real, utilizarías PHPMailer o la función mail()
        $message = "¡Gracias por contactarnos! Hemos recibido tu mensaje y te responderemos lo antes posible.";
        $messageType = "success";
    }
    
    $conn->close();
}
?>

<?php include 'includes/header.php'; ?>

<!-- Cabecera de Contacto -->
<section class="bg-dark text-white py-5">
    <div class="container text-center">
        <h1>Contáctanos</h1>
        <p class="lead">Estamos aquí para responder tus preguntas y ayudarte en todo lo que necesites</p>
    </div>
</section>

<!-- Información de Contacto y Formulario -->
<section class="section-padding">
    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-5 mb-4 mb-md-0">
                <h3>Información de Contacto</h3>
                <p>Si tienes alguna pregunta o necesitas más información sobre nuestros servicios, no dudes en contactarnos.</p>
                
                <div class="contact-info mt-4">
                    <div class="d-flex mb-3">
                        <div class="icon me-3">
                            <i class="fas fa-map-marker-alt text-primary"></i>
                        </div>
                        <div class="text">
                            <h5 class="mb-1">Dirección</h5>
                            <p class="mb-0">Av. Principal #123, Ciudad</p>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-3">
                        <div class="icon me-3">
                            <i class="fas fa-phone text-primary"></i>
                        </div>
                        <div class="text">
                            <h5 class="mb-1">Teléfono</h5>
                            <p class="mb-0">(123) 456-7890</p>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-3">
                        <div class="icon me-3">
                            <i class="fas fa-envelope text-primary"></i>
                        </div>
                        <div class="text">
                            <h5 class="mb-1">Email</h5>
                            <p class="mb-0">info@gymfitpro.com</p>
                        </div>
                    </div>
                    
                    <div class="d-flex mb-3">
                        <div class="icon me-3">
                            <i class="fas fa-clock text-primary"></i>
                        </div>
                        <div class="text">
                            <h5 class="mb-1">Horario de Atención</h5>
                            <p class="mb-0">Lunes a Viernes: 7:00 - 22:00<br>Sábados: 8:00 - 20:00<br>Domingos: 9:00 - 14:00</p>
                        </div>
                    </div>
                </div>
                
                <div class="social-media mt-4">
                    <h5>Síguenos en Redes Sociales</h5>
                    <div class="social-icons">
                        <a href="#" class="me-2 text-dark"><i class="fab fa-facebook-f fa-lg"></i></a>
                        <a href="#" class="me-2 text-dark"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="me-2 text-dark"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="me-2 text-dark"><i class="fab fa-linkedin-in fa-lg"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-7">
                <div class="card">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-4">Envíanos un Mensaje</h3>
                        
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre" class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                    <div class="invalid-feedback">
                                        Por favor ingrese su nombre.
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                    <div class="invalid-feedback">
                                        Por favor ingrese un email válido.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono">
                            </div>
                            
                            <div class="mb-3">
                                <label for="asunto" class="form-label">Asunto *</label>
                                <input type="text" class="form-control" id="asunto" name="asunto" required>
                                <div class="invalid-feedback">
                                    Por favor ingrese el asunto.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="mensaje" class="form-label">Mensaje *</label>
                                <textarea class="form-control" id="mensaje" name="mensaje" rows="5" required></textarea>
                                <div class="invalid-feedback">
                                    Por favor ingrese su mensaje.
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="politica_privacidad" required>
                                <label class="form-check-label" for="politica_privacidad">Acepto la <a href="#">política de privacidad</a> *</label>
                                <div class="invalid-feedback">
                                    Debe aceptar la política de privacidad para continuar.
                                </div>
                            </div>
                            
                            <div>
                                <button type="submit" class="btn btn-primary">Enviar Mensaje</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mapa -->
<section class="mb-5">
    <div class="container">
        <div class="section-title">
            <h2>Ubicación</h2>
            <p>Encuéntranos fácilmente con nuestro mapa</p>
        </div>
        
        <div class="map-container">
            <!-- Aquí normalmente iría un iframe con Google Maps o similar -->
            <div class="ratio ratio-16x9">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3963.952912260219!2d3.375295414770757!3d6.5276300952784755!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNsKwMzEnMzkuNSJOIDPCsDIyJzMxLjEiRQ!5e0!3m2!1sen!2sng!4v1627309371844!5m2!1sen!2sng" allowfullscreen="" loading="lazy"></iframe>
            </div>
            <p class="text-center mt-2">
                <a href="https://goo.gl/maps/2oCimLUwGxGYGX2N7" target="_blank" class="btn btn-outline-primary mt-2">
                    <i class="fas fa-directions me-2"></i>Obtener Indicaciones
                </a>
            </p>
        </div>
    </div>
</section>

<!-- Preguntas Frecuentes -->
<section class="section-padding bg-light">
    <div class="container">
        <div class="section-title">
            <h2>Preguntas Frecuentes</h2>
            <p>Respuestas a las consultas más comunes</p>
        </div>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                ¿Cómo puedo reservar una sesión con un entrenador?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Para reservar una sesión debes iniciar sesión en tu cuenta, dirigirte a la sección de "Reservas" y seleccionar el entrenador, servicio, fecha y hora que desees. Una vez confirmada la disponibilidad, podrás completar tu reserva.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                ¿Cuál es la política de cancelación de reservas?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Puedes cancelar tu reserva hasta 24 horas antes de la sesión programada sin ningún cargo. Las cancelaciones con menos de 24 horas de antelación pueden estar sujetas a un cargo parcial o a la pérdida de la sesión.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                ¿Cómo puedo adquirir un plan?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Para adquirir un plan, debes iniciar sesión en tu cuenta, dirigirte a la sección de "Planes", seleccionar el que más te interese y seguir las instrucciones para completar la compra. Una vez adquirido, tu plan estará activo inmediatamente.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                ¿Ofrecen evaluaciones físicas iniciales?
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Sí, todos nuestros planes incluyen una evaluación física inicial completa. Esto nos permite conocer tu estado actual, objetivos, posibles limitaciones y diseñar un programa personalizado acorde a tus necesidades.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
