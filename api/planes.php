<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// Obtener el tipo de plan desde la URL
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'mensual';

// Validar el tipo de plan
$tipos_validos = ['mensual', 'trimestral', 'anual'];
if (!in_array($tipo, $tipos_validos)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de plan no válido']);
    exit;
}

// Consultar los planes según el tipo
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

$query = "SELECT * FROM planes WHERE tipo = ? AND estado = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $tipo);
$stmt->execute();
$result = $stmt->get_result();

$planes = [];
while ($plan = $result->fetch_assoc()) {
    $planes[] = [
        'id' => $plan['id'],
        'nombre' => $plan['nombre'],
        'precio' => number_format($plan['precio'], 2),
        'descripcion' => $plan['descripcion'],
        'duracion' => $plan['duracion_dias']
    ];
}

$conn->close();

// Devolver los planes en formato JSON
echo json_encode($planes);
