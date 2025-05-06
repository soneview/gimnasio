<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Verificar si se recibió el ID del plan
if (!isset($_GET['id'])) {
    header('Location: ../planes.php');
    exit;
}

// Obtener el ID del plan
$plan_id = intval($_GET['id']);

// Redirigir al formulario de pago
header('Location: ../views/pago_membresia.php?plan_id=' . $plan_id);
exit;
?>
