// Archivo JavaScript principal para GymFitPro

document.addEventListener('DOMContentLoaded', function() {
    // Activar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Validación de formularios
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Manejo de reservas de horarios
    setupReservationSystem();
});

// Sistema de reservas
function setupReservationSystem() {
    const reservationButtons = document.querySelectorAll('.btn-reservar');
    
    if(reservationButtons.length > 0) {
        reservationButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const horarioId = this.getAttribute('data-horario-id');
                const fecha = document.getElementById('fecha_reserva').value;
                
                if(!fecha) {
                    alert('Por favor selecciona una fecha para la reserva');
                    return;
                }
                
                // Enviar datos mediante AJAX
                fetch('/gimnasio/cliente/procesar_reserva.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `horario_id=${horarioId}&fecha=${fecha}`
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert('Reserva realizada con éxito');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ha ocurrido un error al procesar la reserva');
                });
            });
        });
    }
}

// Función para verificar disponibilidad de horarios
function checkAvailability(horarioId, fecha) {
    return fetch(`/gimnasio/cliente/verificar_disponibilidad.php?horario_id=${horarioId}&fecha=${fecha}`)
        .then(response => response.json())
        .then(data => {
            return data.available;
        })
        .catch(error => {
            console.error('Error:', error);
            return false;
        });
}

// Función para actualizar el calendario de horarios
function updateSchedule(entrenadorId = 0, servicioId = 0) {
    const params = new URLSearchParams();
    if(entrenadorId > 0) params.append('entrenador_id', entrenadorId);
    if(servicioId > 0) params.append('servicio_id', servicioId);
    
    fetch(`/gimnasio/cliente/obtener_horarios.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            const scheduleContainer = document.getElementById('schedule-container');
            if(scheduleContainer) {
                scheduleContainer.innerHTML = data.html;
                setupReservationSystem(); // Reiniciar los eventos después de actualizar el DOM
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}
