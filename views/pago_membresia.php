<?php
session_start();
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

// Lista de bancos venezolanos
$bancos = [
    'Banco de Venezuela',
    'Banesco',
    'Mercantil',
    'Provincial',
    'Banco del Tesoro',
    'BFC Banco Fondo Común',
    'Banco Exterior',
    'Banco Nacional de Crédito',
    'Banco Plaza',
    'Banco Sofitasa'
];

// Lista de operadoras
$operadoras = ['0412', '0414', '0416', '0424', '0426'];

// Obtener el plan seleccionado
$plan_id = isset($_GET['plan']) ? $_GET['plan'] : null;
$plan = null;

if ($plan_id) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT * FROM planes WHERE id = ? AND estado = 1");
    $stmt->bind_param("i", $plan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $plan = $result->fetch_assoc();
    }
    
    $conn->close();
}

// Planes de membresía por defecto
$planes = [
    'mensual' => [
        'nombre' => 'Mensual',
        'precio_bolivares' => 1000000, // 1 millón de bolívares
        'precio_dolares' => 100, // 100 dólares
        'descripcion' => 'Acceso ilimitado por 30 días'
    ],
    'trimestral' => [
        'nombre' => 'Trimestral',
        'precio_bolivares' => 2500000, // 2.5 millones de bolívares
        'precio_dolares' => 250, // 250 dólares
        'descripcion' => 'Acceso ilimitado por 90 días'
    ],
    'anual' => [
        'nombre' => 'Anual',
        'precio_bolivares' => 8000000, // 8 millones de bolívares
        'precio_dolares' => 800, // 800 dólares
        'descripcion' => 'Acceso ilimitado por 365 días'
    ]
];
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h3 class="card-title"><?= $planes['mensual']['nombre'] ?></h3>
                    <h4 class="mb-3">
                        <span class="text-primary">Bs. <?= number_format($planes['mensual']['precio_bolivares']) ?></span> / 
                        <span class="text-success">$ <?= $planes['mensual']['precio_dolares'] ?></span>
                    </h4>
                    <p class="card-text"><?= $planes['mensual']['descripcion'] ?></p>
                    <button class="btn btn-primary btn-block" onclick="seleccionarPlan('mensual')">
                        Seleccionar Plan
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h3 class="card-title"><?= $planes['trimestral']['nombre'] ?></h3>
                    <h4 class="mb-3">
                        <span class="text-primary">Bs. <?= number_format($planes['trimestral']['precio_bolivares']) ?></span> / 
                        <span class="text-success">$ <?= $planes['trimestral']['precio_dolares'] ?></span>
                    </h4>
                    <p class="card-text"><?= $planes['trimestral']['descripcion'] ?></p>
                    <button class="btn btn-primary btn-block" onclick="seleccionarPlan('trimestral')">
                        Seleccionar Plan
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h3 class="card-title"><?= $planes['anual']['nombre'] ?></h3>
                    <h4 class="mb-3">
                        <span class="text-primary">Bs. <?= number_format($planes['anual']['precio_bolivares']) ?></span> / 
                        <span class="text-success">$ <?= $planes['anual']['precio_dolares'] ?></span>
                    </h4>
                    <p class="card-text"><?= $planes['anual']['descripcion'] ?></p>
                    <button class="btn btn-primary btn-block" onclick="seleccionarPlan('anual')">
                        Seleccionar Plan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="formularioPago" style="display: none;">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Formulario de Pago</h4>
            </div>
            <div class="card-body">
                <form id="pagoForm" action="procesar_pago.php" method="POST">
                    <input type="hidden" id="plan_seleccionado" name="plan" value="">
                    
                    <div class="form-group">
                        <label for="plan">Plan Seleccionado</label>
                        <input type="text" class="form-control" id="plan_nombre" readonly 
                               value="<?= $plan ? $plan['nombre'] : '' ?>">
                        <input type="hidden" id="plan_id" name="plan_id" 
                               value="<?= $plan_id ?>">
                    </div>

                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>

                    <div class="form-group">
                        <label for="apellido">Apellido</label>
                        <input type="text" class="form-control" id="apellido" name="apellido" required>
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <div class="input-group">
                            <select class="form-control" id="operadora" name="operadora" required>
                                <option value="">Selecciona operadora</option>
                                <?php foreach ($operadoras as $op): ?>
                                    <option value="<?= $op ?>"><?= $op ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" class="form-control" id="telefono" name="telefono" 
                                   pattern="[0-9]{7}" required placeholder="Número de 7 dígitos">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="cedula">Cédula</label>
                        <input type="text" class="form-control" id="cedula" name="cedula" 
                               pattern="[0-9]{7,8}" required placeholder="Números solo">
                    </div>

                    <div class="form-group">
                        <label for="banco">Banco</label>
                        <select class="form-control" id="banco" name="banco" required>
                            <option value="">Selecciona banco</option>
                            <?php foreach ($bancos as $banco): ?>
                                <option value="<?= $banco ?>"><?= $banco ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Procesar Pago</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function seleccionarPlan(plan) {
    document.getElementById('plan_seleccionado').value = plan;
    document.getElementById('formularioPago').style.display = 'block';
}

// Validación del formulario
const form = document.getElementById('pagoForm');
form.addEventListener('submit', function(e) {
    const telefono = document.getElementById('telefono').value;
    const cedula = document.getElementById('cedula').value;
    
    if (telefono.length !== 7) {
        alert('El número de teléfono debe tener 7 dígitos');
        e.preventDefault();
        return;
    }
    
    if (cedula.length < 7 || cedula.length > 8) {
        alert('La cédula debe tener entre 7 y 8 dígitos');
        e.preventDefault();
        return;
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
