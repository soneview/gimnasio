<?php
// Script para actualizar las contraseñas de los usuarios
require_once 'config/db.php';

$conn = connectDB();
$password = '123456'; // Contraseña común para todos los usuarios
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Actualizar todas las contraseñas
$query = "UPDATE usuarios SET password = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $hashed_password);

if ($stmt->execute()) {
    echo "Contraseñas actualizadas correctamente para todos los usuarios.<br>";
    echo "La contraseña para todos los usuarios es: 123456";
} else {
    echo "Error al actualizar las contraseñas: " . $conn->error;
}

// Verificar la actualización
$query = "SELECT id, nombre, apellido, email, password, rol_id FROM usuarios LIMIT 5";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    echo "<h3>Verificación de usuarios:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nombre'] . " " . $row['apellido'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['rol_id'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Verificar que las contraseñas están hasheadas correctamente
    echo "<p>Comprobación del hashing: " . (password_verify($password, $hashed_password) ? "Correcto" : "Incorrecto") . "</p>";
} else {
    echo "No se encontraron usuarios.";
}

$conn->close();
?>
