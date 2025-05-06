<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']));
    }

    try {
        $conn->begin_transaction();
        
        // Validar datos
        $monto = floatval($_POST['monto']);
        $telefono = $_POST['telefono'];
        $cedula_rif = $_POST['cedula_rif'];
        $banco_destino = $_POST['banco_destino'];
        $concepto = $_POST['concepto'];
        
        if ($monto <= 0) {
            throw new Exception('Monto inválido');
        }
        
        // Simular validación Pago Móvil BDV
        if (!preg_match('/^[0-9]{11}$/', $telefono)) {
            throw new Exception('Formato de teléfono inválido');
        }
        
        if (!preg_match('/^[VEJPGvejpg]{1}[0-9]{8,9}$/', $cedula_rif)) {
            throw new Exception('Formato de cédula/RIF inválido');
        }
        
        // Registrar pago
        $stmt = $conn->prepare("INSERT INTO pagos (cliente_id, monto, metodo_pago, telefono, cedula_rif, banco_destino, concepto, estado) 
                               VALUES (?, ?, 'pago_movil', ?, ?, ?, ?, 'pendiente')");
        $stmt->bind_param("idsssss", $_SESSION['user_id'], $monto, $telefono, $cedula_rif, $banco_destino, $concepto);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al registrar el pago');
        }
        
        $pago_id = $conn->insert_id;
        
        // Simular proceso de Pago Móvil
        sleep(2); // Simulación de tiempo de procesamiento
        
        // Generar referencia
        $referencia = 'PM-' . date('Ymd') . '-' . str_pad($pago_id, 6, '0', STR_PAD_LEFT);
        
        // Actualizar estado y referencia
        $stmt = $conn->prepare("UPDATE pagos SET estado = 'aprobado', referencia = ? WHERE id = ?");
        $stmt->bind_param("si", $referencia, $pago_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al actualizar el pago');
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Pago procesado exitosamente',
            'pago_id' => $pago_id,
            'referencia' => $referencia
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } finally {
        $conn->close();
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>
