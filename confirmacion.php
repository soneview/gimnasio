<?php
require_once __DIR__ . '/includes/header.php';

// Verificar referencia
if (!isset($_GET['referencia'])) {
    header('Location: pago_membresia.php');
    exit;
}

$referencia = $_GET['referencia'];
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="alert alert-success text-center" role="alert">
                <h4 class="alert-heading">¡Pago Realizado Exitosamente!</h4>
                <p>Referencia de pago: <strong><?= htmlspecialchars($referencia) ?></strong></p>
                <p>Gracias por su pago. Nuestro equipo de administración revisará su pago y activará su membresía en breve.</p>
                <p>Si necesita ayuda, no dude en contactarnos.</p>
                <hr>
                <p class="mb-0">Atentamente,<br>Vitaminada Sport Gym</p>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
