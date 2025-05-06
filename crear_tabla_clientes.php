<?php
// Script para crear la tabla clientes
require_once 'config/db.php';

$conn = connectDB();

// Crear la tabla clientes si no existe
$sql = "CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    fecha_nacimiento DATE,
    genero CHAR(1),
    altura DECIMAL(3,2),
    peso DECIMAL(5,2),
    objetivo VARCHAR(100),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql) === TRUE) {
    echo "Tabla 'clientes' creada correctamente o ya existía.";
} else {
    echo "Error al crear la tabla: " . $conn->error;
}

$conn->close();
?>
