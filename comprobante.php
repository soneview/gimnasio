<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/vendor/autoload.php';

if (!isset($_GET['id'])) {
    die('ID de pago no especificado');
}

$pago_id = $_GET['id'];
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT p.*, u.nombre as cliente_nombre 
                        FROM pagos p 
                        JOIN usuarios u ON p.cliente_id = u.id 
                        WHERE p.id = ?");
$stmt->bind_param("i", $pago_id);
$stmt->execute();
$pago = $stmt->get_result()->fetch_assoc();

if (!$pago) {
    die('Pago no encontrado');
}

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 15,
    'margin_bottom' => 15,
    'margin_header' => 10,
    'margin_footer' => 10
]);

$html = "<div style='text-align: center;'>
            <img src='assets/images/logo.png' style='width: 150px; height: auto; margin-bottom: 20px;'>
            <h2>VITAMINADA SPORT GYM</h2>
            <h3>COMPROBANTE DE PAGO</h3>
        </div>
        
        <div style='margin: 20px;'>
            <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                <tr>
                    <td style='width: 50%; padding: 5px; border: 1px solid #ddd;'>
                        <strong>Cliente:</strong><br>
                        " . htmlspecialchars($pago['cliente_nombre']) . "<br>
                        Cédula/RIF: " . htmlspecialchars($pago['cedula_rif']) . "<br>
                        Teléfono: " . htmlspecialchars($pago['telefono']) . "
                    </td>
                    <td style='width: 50%; padding: 5px; border: 1px solid #ddd;'>
                        <strong>Detalles del Pago:</strong><br>
                        Referencia: " . htmlspecialchars($pago['referencia']) . "<br>
                        Fecha: " . date('d/m/Y H:i', strtotime($pago['fecha'])) . "<br>
                        Banco Destino: " . htmlspecialchars($pago['banco_destino']) . "
                    </td>
                </tr>
            </table>

            <table style='width: 100%; border-collapse: collapse;'>
                <tr style='background-color: #f5f5f5; text-align: center;'>
                    <th style='padding: 10px; border: 1px solid #ddd;'>Concepto</th>
                    <th style='padding: 10px; border: 1px solid #ddd;'>Monto</th>
                    <th style='padding: 10px; border: 1px solid #ddd;'>Estado</th>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; text-align: center;'>" . htmlspecialchars($pago['concepto']) . "</td>
                    <td style='padding: 10px; border: 1px solid #ddd; text-align: center;'>Bs. " . number_format($pago['monto'], 2) . "</td>
                    <td style='padding: 10px; border: 1px solid #ddd; text-align: center; color: " . ($pago['estado'] === 'aprobado' ? 'green' : 'red') . "; font-weight: bold;">
                    " . strtoupper(htmlspecialchars($pago['estado'])) . "
                    </td>
                </tr>
            </table>
        </div>

        <div style='text-align: center; margin-top: 30px;'>
            <p style='font-size: 12px;'>Este comprobante es válido para su membresía en Vitaminada Sport Gym.</p>
            <p style='font-size: 12px;'>Para cualquier consulta, contacte con nuestro servicio al cliente.</p>
        </div>";

$mpdf->WriteHTML($html);
$filename = 'comprobante_' . $pago_id . '_' . date('Y-m-d_H-i-s') . '.pdf';
$mpdf->Output('comprobantes/' . $filename, 'F');

// Enviar comprobante por correo
if (isset($pago['email'])) {
    $to = $pago['email'];
    $subject = 'Comprobante de Pago - Vitaminada Sport Gym';
    $message = "<h2>Gracias por su pago</h2>
               <p>Adjunto encontrará su comprobante de pago.</p>
               <p>Si necesita ayuda, no dude en contactarnos.</p>";
    
    $headers = "From: no-reply@vitaminadasportgym.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=" . md5(time()) . "\r\n";

    $body = "--" . md5(time()) . "\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $message . "\r\n\r\n";
    
    $body .= "--" . md5(time()) . "\r\n";
    $body .= "Content-Type: application/pdf; name=\"" . $filename . "\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\r\n\r\n";
    $body .= chunk_split(base64_encode(file_get_contents('comprobantes/' . $filename))) . "\r\n";
    $body .= "--" . md5(time()) . "--\r\n";

    mail($to, $subject, $body, $headers);
}

// Redirigir a la página de confirmación
header('Location: confirmacion.php?id=' . $pago_id);
exit();
?>
