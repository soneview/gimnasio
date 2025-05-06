<?php
define('MERCADOPAGO_ACCESS_TOKEN', 'TU_ACCESS_TOKEN_AQUI');
define('PAGO_MOVIL_API_KEY', 'TU_API_KEY_AQUI');

class PagoConfig {
    public static $metodos = [
        'pago_movil' => [
            'nombre' => 'Pago Móvil Venezuela',
            'logo' => 'assets/images/pago_movil.png'
        ],
        'mercadopago' => [
            'nombre' => 'Mercado Pago',
            'logo' => 'assets/images/mercadopago.png'
        ]
    ];

    public static $bancos = [
        'banesco' => 'Banesco',
        'mercadobanco' => 'Mercado Banco',
        'banco_de_venezuela' => 'Banco de Venezuela'
    ];
}
?>
