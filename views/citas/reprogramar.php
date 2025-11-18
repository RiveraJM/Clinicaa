<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

$id = $_GET['id'] ?? 0;

// Obtener datos de la cita
$cita = $db->fetchOne("
    SELECT c.*, 
           CONCAT(p.nombres, ' ', p.apellidos) as paciente_nombre,
           CONCAT(m.nombres, ' ', m.apellidos) as medico_nombre,
           e.nombre as especialidad
    FROM citas c
    INNER JOIN pacientes p ON c.paciente_id = p.id
    INNER JOIN medicos m ON c.medico_id = m.id
    INNER JOIN especialidades e ON c.especialidad_id = e.id
    WHERE c.id = :id
", ['id' => $id]);

if (!$cita) {
    $_SESSION['error'] = 'Cita no encontrada';
    redirect('lista.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reprogramar Cita - Sistema de Clínica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
<!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="../dashboard.php" class="sidebar-logo">
                <i class="fas fa-hospital-symbol"></i> CLÍNICA
            </a>
            <div class="sidebar-user">
                <div class="avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                <div class="sidebar-user-name"><?php echo $user['username']; ?></div>
                <div class="sidebar-user-role"><?php echo ucfirst($user['rol']); ?></div>
            </div>
        </div>
        
        <nav class="sidebar-menu">
            <a href="../dashboard.php" class="sidebar-menu-item">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="../pacientes/lista.php" class="sidebar-menu-item">
                <i class="fas fa-users"></i> Pacientes
            </a>
            <a href="../medicos/lista.php" class="sidebar-menu-item">
                <i class="fas fa-user-md"></i> Médicos
            </a>
            <a href="../especialidades/lista.php" class="sidebar-menu-item">
                <i class="fas fa-stethoscope"></i> Especialidades
            </a>
            <a href="lista.php" class="sidebar-menu-item active">
                <i class="fas fa-calendar-alt"></i> Citas
            </a>
            <a href="../agenda/configurar.php" class="sidebar-menu-item">
                <i class="fas fa-calendar-check"></i> Configurar Agenda
            </a>
            <?php if ($user['rol'] === 'admin'): ?>
            <a href="../reportes/index.php" class="sidebar-menu-item">
                <i class="fas fa-chart-bar"></i> Reportes
            </a>
            <?php endif; ?>
            <a href="../../controllers/login_controller.php?logout=1" class="sidebar-menu-item" style="color: var(--danger-color); margin-top: 20px;">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div>
                <h4>Reprogramar Cita</h4>
                <small>Cambiar fecha y hora de la cita</small>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="page-header">
                <div class="page-title">
                    <h2>Reprogramar Cita</h2>
                    <div class="breadcrumb">
                        <a href="../dashboard.php">Dashboard</a> / 
                        <a href="lista.php">Citas</a> / Reprogramar
                    </div>
                </div>
            </div>

            <!-- Datos Actuales -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Datos Actuales de la Cita</h5>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <div>
                            <strong>Paciente:</strong><br>
                            <?php echo $cita['paciente_nombre']; ?>
                        </div>
                        <div>
                            <strong>Médico:</strong><br>
                            <?php echo $cita['medico_nombre']; ?>
                        </div>
                        <div>
                            <strong>Especialidad:</strong><br>
                            <?php echo $cita['especialidad']; ?>
                        </div>
                        <div>
                            <strong>Fecha Actual:</strong><br>
                            <span style="color: var(--danger-color); font-weight: 600;">
                                <?php echo formatFecha($cita['fecha']); ?>
                            </span>
                        </div>
                        <div>
                            <strong>Hora Actual:</strong><br>
                            <span style="color: var(--danger-color); font-weight: 600;">
                                <?php echo date('H:i', strtotime($cita['hora'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de Reprogramación -->
            <form action="../../controllers/cita_controller.php" method="POST">
                <input type="hidden" name="action" value="reprogramar">
                <input type="hidden" name="id" value="<?php echo $cita['id']; ?>">

                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-calendar"></i> Nueva Fecha y Hora
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label">Nueva Fecha <span style="color: var(--danger-color);">*</span></label>
                            <input type="date" class="form-control" name="fecha" id="fecha" required min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div>
                            <label class="form-label">Nueva Hora <span style="color: var(--danger-color);">*</span></label>
                            <select class="form-control" name="hora" id="hora" required disabled>
                                <option value="">Primero seleccione fecha...</option>
                            </select>
                            <div id="loading_horas" style="display: none;">
                                <div class="loading">
                                    <div class="spinner"></div> Cargando horarios...
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Motivo del Cambio</label>
                            <input type="text" class="form-control" name="motivo" placeholder="Opcional">
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="lista.php" class="btn" style="background: #6c757d; color: white;">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-check"></i> Reprogramar Cita
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Cargar horarios disponibles
        document.getElementById('fecha').addEventListener('change', function() {
            const fecha = this.value;
            const medicoId = <?php echo $cita['medico_id']; ?>;
            const horaSelect = document.getElementById('hora');
            const loading = document.getElementById('loading_horas');
            
            horaSelect.disabled = true;
            loading.style.display = 'block';
            horaSelect.innerHTML = '<option value="">Cargando...</option>';

            fetch(`../../api/get_horarios.php?medico_id=${medicoId}&fecha=${fecha}`)
                .then(response => response.json())
                .then(data => {
                    loading.style.display = 'none';
                    if (data.success && data.horarios.length > 0) {
                        horaSelect.innerHTML = '<option value="">Seleccione una hora...</option>';
                        data.horarios.forEach(hora => {
                            horaSelect.innerHTML += `<option value="${hora}">${hora}</option>`;
                        });
                        horaSelect.disabled = false;
                    } else {
                        horaSelect.innerHTML = '<option value="">No hay horarios disponibles</option>';
                    }
                })
                .catch(error => {
                    loading.style.display = 'none';
                    horaSelect.innerHTML = '<option value="">Error al cargar horarios</option>';
                });
        });
    </script>
</body>
</html>