<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'vitaminada_sport');

// Conexión a la base de datos
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Crear base de datos si no existe
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . ";";
if ($conn->query($sql) === TRUE) {
    $conn->select_db(DB_NAME);
} else {
    die("Error al crear la base de datos: " . $conn->error);
}

// Crear tabla de pagos
$sql = "CREATE TABLE IF NOT EXISTS pagos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    operadora VARCHAR(4) NOT NULL,
    telefono VARCHAR(7) NOT NULL,
    cedula VARCHAR(8) NOT NULL,
    banco VARCHAR(50) NOT NULL,
    plan VARCHAR(20) NOT NULL,
    referencia VARCHAR(20) NOT NULL UNIQUE,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente'
)";

if ($conn->query($sql) !== TRUE) {
    die("Error al crear la tabla de pagos: " . $conn->error);
}

$conn->close();
?>
