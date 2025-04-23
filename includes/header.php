<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GymFitPro - Gestión de Gimnasio</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/gimnasio/assets/css/style.css">
</head>
<body>
    <!-- Navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/gimnasio/index.php">GymFitPro</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/gimnasio/index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/gimnasio/planes.php">Planes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/gimnasio/instructores.php">Instructores</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/gimnasio/servicios.php">Servicios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/gimnasio/sobre-nosotros.php">Sobre Nosotros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/gimnasio/contacto.php">Contáctanos</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i><?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <?php if($_SESSION['user_role'] == 1): ?>
                                    <li><a class="dropdown-item" href="/gimnasio/admin/dashboard.php">Panel de Administración</a></li>
                                <?php elseif($_SESSION['user_role'] == 2): ?>
                                    <li><a class="dropdown-item" href="/gimnasio/entrenador/dashboard.php">Panel de Entrenador</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="/gimnasio/cliente/dashboard.php">Mi Perfil</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="/gimnasio/cliente/reservas.php">Mis Reservas</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/gimnasio/auth/logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/gimnasio/auth/login.php">Iniciar Sesión</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/gimnasio/auth/register.php">Registrarse</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
