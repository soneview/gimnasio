<?php
define('RESERVAS_TABLE', 'reservas');
define('HORARIOS_TABLE', 'horarios');
define('PAGOS_TABLE', 'pagos');

class ReservasConfig {
    public static $horarios = [
        'lunes' => [],
        'martes' => [],
        'miercoles' => [],
        'jueves' => [],
        'viernes' => [],
        'sabado' => [],
        'domingo' => []
    ];

    public static $estados = [
        'pendiente' => 'Pendiente',
        'aprobada' => 'Aprobada',
        'rechazada' => 'Rechazada',
        'cancelada' => 'Cancelada',
        'completada' => 'Completada'
    ];
}
?>
