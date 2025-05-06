<?php
class Pago {
    private $db;

    public function __construct() {
        global $db;
        $this->db = $db;
    }

    public function crearPago($cliente_id, $monto, $metodo_pago, $telefono, $banco) {
        $stmt = $this->db->prepare("INSERT INTO " . PAGOS_TABLE . " (cliente_id, monto, metodo_pago, telefono, banco, estado) VALUES (?, ?, ?, ?, ?, ?)");
        $estado = 'pendiente';
        $stmt->bind_param("idssss", $cliente_id, $monto, $metodo_pago, $telefono, $banco, $estado);
        return $stmt->execute();
    }

    public function generarComprobante($pago_id) {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $stmt = $this->db->prepare("SELECT * FROM " . PAGOS_TABLE . " WHERE id = ?");
        $stmt->bind_param("i", $pago_id);
        $stmt->execute();
        $pago = $stmt->get_result()->fetch_assoc();

        $mpdf = new \Mpdf\Mpdf();
        $html = "<div style='text-align: center;'>
                    <img src='assets/images/logo.png' style='width: 150px; height: auto; margin-bottom: 20px;'>
                    <h2>VITAMINADA SPORT GYM</h2>
                    <h3>Comprobante de Pago</h3>
                </div>
                <div style='margin: 20px;'>
                    <p><strong>Cliente:</strong> " . $pago['nombre_cliente'] . "</p>
                    <p><strong>ID de Membresía:</strong> " . $pago['id_membresia'] . "</p>
                    <p><strong>Monto:</strong> Bs. " . number_format($pago['monto'], 2) . "</p>
                    <p><strong>Fecha:</strong> " . date('d/m/Y H:i', strtotime($pago['fecha'])) . "</p>
                    <p><strong>Método de Pago:</strong> " . $pago['metodo_pago'] . "</p>
                    <p><strong>Referencia:</strong> " . $pago['referencia'] . "</p>
                </div>
                <div style='text-align: center; margin-top: 30px;'>
                    <p>Gracias por su pago. Este comprobante es válido para su membresía.</p>
                </div>";

        $mpdf->WriteHTML($html);
        $mpdf->Output('comprobante_' . $pago_id . '.pdf', 'D');
    }

    public function procesarPagoMP($pago_id, $token) {
        // Lógica para procesar pago con Mercado Pago
        // Esta es una implementación básica
        $data = array(
            'token' => $token,
            'transaction_amount' => $monto,
            'description' => 'Membresía Gym',
            'payment_method_id' => 'pago_movil'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . MERCADOPAGO_ACCESS_TOKEN
        ));

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response);
        
        if ($result->status == 'approved') {
            $this->actualizarEstadoPago($pago_id, 'aprobado');
            return true;
        }
        return false;
    }

    private function actualizarEstadoPago($pago_id, $estado) {
        $stmt = $this->db->prepare("UPDATE " . PAGOS_TABLE . " SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $estado, $pago_id);
        return $stmt->execute();
    }
}
?>
