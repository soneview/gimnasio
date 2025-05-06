<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener rol del usuario
$stmt = $conn->prepare("SELECT rol FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$rol = $stmt->get_result()->fetch_assoc()['rol'];

// Obtener horarios disponibles
$stmt = $conn->prepare("SELECT h.*, u.nombre as entrenador_nombre 
                        FROM horarios h 
                        JOIN usuarios u ON h.entrenador_id = u.id 
                        WHERE h.estado = 'disponible' 
                        ORDER BY h.dia, h.hora_inicio");
$stmt->execute();
$horarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Gestión de Horarios</h4>
                </div>
                <div class="card-body">
                    <?php if ($rol === 'entrenador' || $rol === 'administrador'): ?>
                        <div class="mb-4">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalHorario">
                                <i class="fas fa-plus"></i> Agregar Horario
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Entrenador</th>
                                    <th>Día</th>
                                    <th>Hora Inicio</th>
                                    <th>Hora Fin</th>
                                    <th>Estado</th>
                                    <?php if ($rol !== 'cliente'): ?>
                                        <th>Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($horarios as $horario): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($horario['entrenador_nombre']) ?></td>
                                        <td><?= htmlspecialchars($horario['dia']) ?></td>
                                        <td><?= htmlspecialchars($horario['hora_inicio']) ?></td>
                                        <td><?= htmlspecialchars($horario['hora_fin']) ?></td>
                                        <td>
                                            <span class="badge bg-success">
                                                <?= htmlspecialchars($horario['estado']) ?>
                                            </span>
                                        </td>
                                        <?php if ($rol !== 'cliente'): ?>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="editarHorario(<?= $horario['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="eliminarHorario(<?= $horario['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para agregar/editar horario -->
<div class="modal fade" id="modalHorario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestionar Horario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formHorario">
                    <input type="hidden" id="horario_id">
                    <div class="mb-3">
                        <label for="dia" class="form-label">Día</label>
                        <select class="form-select" id="dia" required>
                            <option value="">Seleccione...</option>
                            <option value="lunes">Lunes</option>
                            <option value="martes">Martes</option>
                            <option value="miercoles">Miércoles</option>
                            <option value="jueves">Jueves</option>
                            <option value="viernes">Viernes</option>
                            <option value="sabado">Sábado</option>
                            <option value="domingo">Domingo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="hora_inicio" class="form-label">Hora Inicio</label>
                        <input type="time" class="form-control" id="hora_inicio" required>
                    </div>
                    <div class="mb-3">
                        <label for="hora_fin" class="form-label">Hora Fin</label>
                        <input type="time" class="form-control" id="hora_fin" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarHorario()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
function editarHorario(id) {
    // Implementar función de edición
}

function eliminarHorario(id) {
    // Implementar función de eliminación
}

function guardarHorario() {
    // Implementar función de guardado
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
