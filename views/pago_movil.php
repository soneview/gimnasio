<?php
session_start();
require_once __DIR__ . '/../config/database.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Pago de Membresía - Pago Móvil BDV</h4>
                </div>
                <div class="card-body">
                    <form id="pagoForm" method="POST" action="api/pago_movil.php">
                        <div class="form-group">
                            <label for="monto">Monto (Bs.)</label>
                            <input type="number" class="form-control" id="monto" name="monto" required min="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="telefono">Número de Teléfono</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                   pattern="[0-9]{11}" required placeholder="04121234567">
                        </div>

                        <div class="form-group">
                            <label for="cedula">Cédula o RIF</label>
                            <input type="text" class="form-control" id="cedula" name="cedula_rif" 
                                   pattern="[VEJPGvejpg]{1}[0-9]{8,9}" required placeholder="V12345678">
                        </div>

                        <div class="form-group">
                            <label for="banco">Banco Destino</label>
                            <select class="form-control" id="banco" name="banco_destino" required>
                                <option value="">Seleccione...</option>
                                <option value="banco_de_venezuela">Banco de Venezuela</option>
                                <option value="banesco">Banesco</option>
                                <option value="mercadobanco">Mercado Banco</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="concepto">Concepto</label>
                            <input type="text" class="form-control" id="concepto" name="concepto" 
                                   required placeholder="Pago de membresía Vitaminada Sport">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Procesar Pago</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('pagoForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const response = await fetch('api/pago_movil.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
        window.location.href = 'comprobante.php?id=' + result.pago_id;
    } else {
        alert(result.message);
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
