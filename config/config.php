<?php
dotenv_load(__DIR__ . '/../.env');

// Configuración de la base de datos
$dbConfig = [
    'host' => getenv('DB_HOST'),
    'user' => getenv('DB_USER'),
    'pass' => getenv('DB_PASS'),
    'name' => getenv('DB_NAME')
];

// Configuración de correo
$mailConfig = [
    'host' => getenv('MAIL_HOST'),
    'port' => getenv('MAIL_PORT'),
    'username' => getenv('MAIL_USERNAME'),
    'password' => getenv('MAIL_PASSWORD'),
    'encryption' => getenv('MAIL_ENCRYPTION'),
    'from' => [
        'name' => getenv('MAIL_FROM_NAME'),
        'address' => getenv('MAIL_FROM_ADDRESS')
    ]
];

// Configuración de pagos
$paymentConfig = [
    'pago_movil' => [
        'api_key' => getenv('PAGO_MOVIL_API_KEY'),
        'secret' => getenv('PAGO_MOVIL_SECRET')
    ],
    'mercadopago' => [
        'access_token' => getenv('MERCADOPAGO_ACCESS_TOKEN'),
        'public_key' => getenv('MERCADOPAGO_PUBLIC_KEY')
    ],
    'stripe' => [
        'secret_key' => getenv('STRIPE_SECRET_KEY'),
        'public_key' => getenv('STRIPE_PUBLIC_KEY')
    ]
];

// Configuración de seguridad
$securityConfig = [
    'jwt' => [
        'secret' => getenv('JWT_SECRET'),
        'expiration' => getenv('JWT_EXPIRATION')
    ],
    'password' => [
        'min_length' => 8,
        'max_length' => 255,
        'require_special_chars' => true
    ]
];

// Configuración de caché
$cacheConfig = [
    'driver' => getenv('CACHE_DRIVER'),
    'path' => __DIR__ . '/../storage/cache'
];

// Configuración de logs
$logConfig = [
    'channel' => getenv('LOG_CHANNEL'),
    'level' => getenv('LOG_LEVEL'),
    'path' => __DIR__ . '/../storage/logs/app.log'
];

// Configuración de sesión
$sessionConfig = [
    'driver' => getenv('SESSION_DRIVER'),
    'path' => __DIR__ . '/../storage/session'
];

// Configuración de rutas
$routeConfig = [
    'base_path' => '/',
    'api_prefix' => '/api',
    'version' => 'v1'
];

// Configuración de límites
$limitConfig = [
    'max_attempts' => 3,
    'lockout_time' => 300,
    'max_file_size' => 5242880, // 5MB
    'max_upload_files' => 5
];

return [
    'database' => $dbConfig,
    'mail' => $mailConfig,
    'payment' => $paymentConfig,
    'security' => $securityConfig,
    'cache' => $cacheConfig,
    'log' => $logConfig,
    'session' => $sessionConfig,
    'route' => $routeConfig,
    'limit' => $limitConfig
];
