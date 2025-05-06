<?php
require_once __DIR__ . '/config/database.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Verificar si la tabla pagos existe
$stmt = $conn->prepare("SHOW TABLES LIKE 'pagos'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "La tabla pagos no existe. Creando...\n";
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
        die("Error al crear la tabla: " . $conn->error);
    }
    echo "Tabla pagos creada exitosamente.\n";
} else {
    echo "Tabla pagos ya existe.\n";
}

$conn->close();
?>
