<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Validación de datos
function validarDatos($data) {
    $errores = [];
    
    // Validar teléfono
    if (!isset($data['operadora']) || !isset($data['telefono'])) {
        $errores[] = 'Número de teléfono incompleto';
    } else {
        $telefono = $data['operadora'] . $data['telefono'];
        if (!preg_match('/^(0412|0414|0416|0424|0426)[0-9]{7}$/', $telefono)) {
            $errores[] = 'Formato de teléfono inválido';
        }
    }
    
    // Validar cédula
    if (!isset($data['cedula']) || !preg_match('/^[0-9]{7,8}$/', $data['cedula'])) {
        $errores[] = 'Formato de cédula inválido';
    }
    
    // Validar banco
    $bancos_permitidos = [
        'Banco de Venezuela', 'Banesco', 'Mercantil', 'Provincial', 
        'Banco del Tesoro', 'BFC Banco Fondo Común', 'Banco Exterior',
        'Banco Nacional de Crédito', 'Banco Plaza', 'Banco Sofitasa'
    ];
    
    if (!isset($data['banco']) || !in_array($data['banco'], $bancos_permitidos)) {
        $errores[] = 'Banco no válido';
    }
    
    return $errores;
}

// Procesar el pago
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errores = validarDatos($_POST);
    
    if (!empty($errores)) {
        echo json_encode([
            'success' => false,
            'errors' => $errores
        ]);
        exit;
    }
    
    // Generar referencia única
    $referencia = 'REF-' . date('Ymd') . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    
    // Preparar datos para notificación al administrador
    $datos_admin = [
        'nombre' => $_POST['nombre'] . ' ' . $_POST['apellido'],
        'telefono' => $_POST['operadora'] . $_POST['telefono'],
        'cedula' => $_POST['cedula'],
        'banco' => $_POST['banco'],
        'plan' => $_POST['plan'],
        'referencia' => $referencia,
        'fecha' => date('Y-m-d H:i:s')
    ];
    
    // Enviar notificación al administrador
    $to = 'admin@vitaminadasportgym.com';
    $subject = 'Nuevo Pago de Membresía - Referencia: ' . $referencia;
    $message = "Nuevo pago recibido:\n\n";
    foreach ($datos_admin as $key => $value) {
        $message .= ucfirst($key) . ': ' . $value . "\n";
    }
    
    mail($to, $subject, $message);
    
    // Redirigir a la página de confirmación
    header('Location: confirmacion.php?referencia=' . $referencia);
    exit;
}
?>
