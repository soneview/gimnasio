document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const emailError = document.getElementById('email-error');
    const passwordError = document.getElementById('password-error');
    const togglePassword = document.getElementById('toggle-password');
    const togglePasswordConfirm = document.getElementById('toggle-password-confirm');
    const notification = document.getElementById('notification');
    const notificationMessage = document.getElementById('notification-message');

    // Función para mostrar notificaciones
    function showNotification(message, isError = false) {
        notificationMessage.textContent = message;
        notification.classList.add('show');
        
        if (isError) {
            notification.classList.add('error');
        } else {
            notification.classList.remove('error');
        }
        
        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }

    // Función para validar correo electrónico
    function validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Función para validar contraseña
    function validatePassword(password) {
        // La contraseña debe tener al menos 6 caracteres
        if (password.length < 6) {
            return {
                valid: false,
                message: 'La contraseña debe tener al menos 6 caracteres'
            };
        }
        
        return {
            valid: true,
            message: ''
        };
    }

    // Toggle para mostrar/ocultar contraseña
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    }

    // Toggle para mostrar/ocultar confirmación de contraseña
    if (togglePasswordConfirm) {
        const passwordConfirmInput = document.getElementById('password_confirm');
        togglePasswordConfirm.addEventListener('click', function() {
            if (passwordConfirmInput.type === 'password') {
                passwordConfirmInput.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passwordConfirmInput.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    }

    // Validaciones para el formulario de login
    if (loginForm) {
        // Validar correo en tiempo real
        if (emailInput) {
            emailInput.addEventListener('input', function() {
                if (this.value.trim() === '') {
                    emailError.textContent = 'El correo electrónico es requerido';
                } else if (!validateEmail(this.value)) {
                    emailError.textContent = 'Ingresa un correo electrónico válido';
                } else {
                    emailError.textContent = '';
                }
            });
        }

        // Validar contraseña en tiempo real
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const validation = validatePassword(this.value);
                if (this.value.trim() === '') {
                    passwordError.textContent = 'La contraseña es requerida';
                } else if (!validation.valid) {
                    passwordError.textContent = validation.message;
                } else {
                    passwordError.textContent = '';
                }
            });
        }

        // Validación del formulario de login antes de enviar
        loginForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validar correo
            if (emailInput.value.trim() === '') {
                emailError.textContent = 'El correo electrónico es requerido';
                isValid = false;
            } else if (!validateEmail(emailInput.value)) {
                emailError.textContent = 'Ingresa un correo electrónico válido';
                isValid = false;
            } else {
                emailError.textContent = '';
            }
            
            // Validar contraseña
            const passwordValidation = validatePassword(passwordInput.value);
            if (passwordInput.value.trim() === '') {
                passwordError.textContent = 'La contraseña es requerida';
                isValid = false;
            } else if (!passwordValidation.valid) {
                passwordError.textContent = passwordValidation.message;
                isValid = false;
            } else {
                passwordError.textContent = '';
            }
            
            // Si no es válido, prevenir envío del formulario
            if (!isValid) {
                e.preventDefault();
                showNotification('Por favor, corrige los errores en el formulario', true);
            }
        });
    }

    // Validaciones para el formulario de registro
    if (registerForm) {
        const nombreInput = document.getElementById('nombre');
        const apellidoInput = document.getElementById('apellido');
        const telefonoInput = document.getElementById('telefono');
        const passwordConfirmInput = document.getElementById('password_confirm');
        const termsCheckbox = document.getElementById('terms');
        
        const nombreError = document.getElementById('nombre-error');
        const apellidoError = document.getElementById('apellido-error');
        const telefonoError = document.getElementById('telefono-error');
        const passwordConfirmError = document.getElementById('password-confirm-error');
        const termsError = document.getElementById('terms-error');

        // Validar nombre en tiempo real
        if (nombreInput) {
            nombreInput.addEventListener('input', function() {
                if (this.value.trim() === '') {
                    nombreError.textContent = 'El nombre es requerido';
                } else {
                    nombreError.textContent = '';
                }
            });
        }

        // Validar apellido en tiempo real
        if (apellidoInput) {
            apellidoInput.addEventListener('input', function() {
                if (this.value.trim() === '') {
                    apellidoError.textContent = 'El apellido es requerido';
                } else {
                    apellidoError.textContent = '';
                }
            });
        }

        // Validar teléfono en tiempo real
        if (telefonoInput) {
            telefonoInput.addEventListener('input', function() {
                const phoneRegex = /^[0-9]{10}$/;
                if (this.value.trim() !== '' && !phoneRegex.test(this.value)) {
                    telefonoError.textContent = 'Ingresa un número de teléfono válido (10 dígitos)';
                } else {
                    telefonoError.textContent = '';
                }
            });
        }

        // Validar confirmación de contraseña en tiempo real
        if (passwordConfirmInput) {
            passwordConfirmInput.addEventListener('input', function() {
                if (this.value.trim() === '') {
                    passwordConfirmError.textContent = 'La confirmación de contraseña es requerida';
                } else if (this.value !== passwordInput.value) {
                    passwordConfirmError.textContent = 'Las contraseñas no coinciden';
                } else {
                    passwordConfirmError.textContent = '';
                }
            });
        }

        // Validar términos y condiciones
        if (termsCheckbox) {
            termsCheckbox.addEventListener('change', function() {
                if (!this.checked) {
                    termsError.textContent = 'Debes aceptar los términos y condiciones';
                } else {
                    termsError.textContent = '';
                }
            });
        }

        // Validación del formulario de registro antes de enviar
        registerForm.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validar nombre
            if (nombreInput.value.trim() === '') {
                nombreError.textContent = 'El nombre es requerido';
                isValid = false;
            } else {
                nombreError.textContent = '';
            }
            
            // Validar apellido
            if (apellidoInput.value.trim() === '') {
                apellidoError.textContent = 'El apellido es requerido';
                isValid = false;
            } else {
                apellidoError.textContent = '';
            }
            
            // Validar correo
            if (emailInput.value.trim() === '') {
                emailError.textContent = 'El correo electrónico es requerido';
                isValid = false;
            } else if (!validateEmail(emailInput.value)) {
                emailError.textContent = 'Ingresa un correo electrónico válido';
                isValid = false;
            } else {
                emailError.textContent = '';
            }
            
            // Validar teléfono (opcional)
            if (telefonoInput.value.trim() !== '') {
                const phoneRegex = /^[0-9]{10}$/;
                if (!phoneRegex.test(telefonoInput.value)) {
                    telefonoError.textContent = 'Ingresa un número de teléfono válido (10 dígitos)';
                    isValid = false;
                } else {
                    telefonoError.textContent = '';
                }
            }
            
            // Validar contraseña
            const passwordValidation = validatePassword(passwordInput.value);
            if (passwordInput.value.trim() === '') {
                passwordError.textContent = 'La contraseña es requerida';
                isValid = false;
            } else if (!passwordValidation.valid) {
                passwordError.textContent = passwordValidation.message;
                isValid = false;
            } else {
                passwordError.textContent = '';
            }
            
            // Validar confirmación de contraseña
            if (passwordConfirmInput.value.trim() === '') {
                passwordConfirmError.textContent = 'La confirmación de contraseña es requerida';
                isValid = false;
            } else if (passwordConfirmInput.value !== passwordInput.value) {
                passwordConfirmError.textContent = 'Las contraseñas no coinciden';
                isValid = false;
            } else {
                passwordConfirmError.textContent = '';
            }
            
            // Validar términos y condiciones
            if (!termsCheckbox.checked) {
                termsError.textContent = 'Debes aceptar los términos y condiciones';
                isValid = false;
            } else {
                termsError.textContent = '';
            }
            
            // Si no es válido, prevenir envío del formulario
            if (!isValid) {
                e.preventDefault();
                showNotification('Por favor, corrige los errores en el formulario', true);
            }
        });
    }
});
