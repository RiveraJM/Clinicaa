<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

// Obtener datos necesarios
$pacientes = $db->fetchAll("
    SELECT id, dni, nombres, apellidos 
    FROM pacientes 
    WHERE estado = 'activo' 
    ORDER BY nombres, apellidos
");

$especialidades = $db->fetchAll("
    SELECT * FROM especialidades 
    WHERE estado = 'activo' 
    ORDER BY nombre
");

$tipos_cita = $db->fetchAll("
    SELECT * FROM tipos_cita 
    WHERE estado = 'activo'
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Cita - Sistema de Clínica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="../dashboard.php" class="sidebar-logo">
                <i class="fas fa-hospital-symbol"></i> CLÍNICA
            </a>
            <div class="sidebar-user">
                <div class="avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                <div class="sidebar-user-name"><?php echo $user['username']; ?></div>
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
            <a href="lista.php" class="sidebar-menu-item active">
                <i class="fas fa-calendar-alt"></i> Citas
            </a>
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
                <h4>Nueva Cita</h4>
                <small>Agendar nueva cita médica</small>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <div class="page-title">
                    <h2>Agendar Cita</h2>
                    <div class="breadcrumb">
                        <a href="../dashboard.php">Dashboard</a> / 
                        <a href="lista.php">Citas</a> / Nueva
                    </div>
                </div>
            </div>

            <form action="../../controllers/cita_controller.php" method="POST" id="formCita">
                <input type="hidden" name="action" value="create">

                <!-- Datos del Paciente -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-user"></i> Datos del Paciente
                    </div>
                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Paciente <span style="color: var(--danger-color);">*</span></label>
                            <select class="form-control" name="paciente_id" id="paciente_id" required>
                                <option value="">Seleccione un paciente...</option>
                                <?php foreach ($pacientes as $paciente): ?>
                                    <option value="<?php echo $paciente['id']; ?>">
                                        <?php echo $paciente['dni']; ?> - <?php echo $paciente['nombres'] . ' ' . $paciente['apellidos']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color: var(--text-secondary);">
                                ¿Paciente nuevo? <a href="../pacientes/nuevo.php" target="_blank">Registrar paciente</a>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Especialidad y Médico -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-stethoscope"></i> Especialidad y Médico
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label">Especialidad <span style="color: var(--danger-color);">*</span></label>
                            <select class="form-control" name="especialidad_id" id="especialidad_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($especialidades as $esp): ?>
                                    <option value="<?php echo $esp['id']; ?>"><?php echo $esp['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Médico <span style="color: var(--danger-color);">*</span></label>
                            <select class="form-control" name="medico_id" id="medico_id" required disabled>
                                <option value="">Primero seleccione especialidad...</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Tipo de Cita <span style="color: var(--danger-color);">*</span></label>
                            <select class="form-control" name="tipo_cita_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($tipos_cita as $tipo): ?>
                                    <option value="<?php echo $tipo['id']; ?>"><?php echo $tipo['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Fecha y Hora -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-calendar"></i> Fecha y Hora
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label">Fecha <span style="color: var(--danger-color);">*</span></label>
                            <input type="date" class="form-control" name="fecha" id="fecha" required min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div>
                            <label class="form-label">Hora <span style="color: var(--danger-color);">*</span></label>
                            <select class="form-control" name="hora" id="hora" required disabled>
                                <option value="">Primero seleccione fecha y médico...</option>
                            </select>
                            <div id="loading_horas" style="display: none;">
                                <div class="loading">
                                    <div class="spinner"></div> Cargando horarios...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Motivo de Consulta -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-notes-medical"></i> Motivo de Consulta
                    </div>
                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Motivo <span style="color: var(--danger-color);">*</span></label>
                            <textarea class="form-control" name="motivo_consulta" rows="3" required placeholder="Describa brevemente el motivo de la consulta..."></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" rows="2" placeholder="Información adicional (opcional)"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="lista.php" class="btn" style="background: #6c757d; color: white;">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Agendar Cita
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Cargar médicos según especialidad
        document.getElementById('especialidad_id').addEventListener('change', function() {
            const especialidadId = this.value;
            const medicoSelect = document.getElementById('medico_id');
            
            medicoSelect.disabled = true;
            medicoSelect.innerHTML = '<option value="">Cargando médicos...</option>';
            
            if (!especialidadId) {
                medicoSelect.innerHTML = '<option value="">Primero seleccione especialidad...</option>';
                return;
            }

            fetch('../../api/get_medicos.php?especialidad_id=' + especialidadId)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.medicos.length > 0) {
                        medicoSelect.innerHTML = '<option value="">Seleccione un médico...</option>';
                        data.medicos.forEach(medico => {
                            medicoSelect.innerHTML += `<option value="${medico.id}">${medico.nombres} ${medico.apellidos}</option>`;
                        });
                        medicoSelect.disabled = false;
                    } else {
                        medicoSelect.innerHTML = '<option value="">No hay médicos disponibles</option>';
                    }
                })
                .catch(error => {
                    medicoSelect.innerHTML = '<option value="">Error al cargar médicos</option>';
                });
        });

        // Cargar horarios disponibles
        function cargarHorarios() {
            const medicoId = document.getElementById('medico_id').value;
            const fecha = document.getElementById('fecha').value;
            const horaSelect = document.getElementById('hora');
            const loading = document.getElementById('loading_horas');
            
            if (!medicoId || !fecha) {
                horaSelect.disabled = true;
                horaSelect.innerHTML = '<option value="">Primero seleccione médico y fecha...</option>';
                return;
            }

            horaSelect.disabled = true;
            loading.style.display = 'block';
            horaSelect.innerHTML = '<option value="">Cargando horarios...</option>';

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
        }

        document.getElementById('medico_id').addEventListener('change', cargarHorarios);
        document.getElementById('fecha').addEventListener('change', cargarHorarios);
    </script>
</body>
</html>