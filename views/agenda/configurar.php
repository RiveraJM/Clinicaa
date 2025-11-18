<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

// Obtener médicos activos
$medicos = $db->fetchAll("
    SELECT m.id, m.nombres, m.apellidos, e.nombre as especialidad
    FROM medicos m
    INNER JOIN especialidades e ON m.especialidad_id = e.id
    WHERE m.estado = 'activo'
    ORDER BY m.nombres
");

// Médico seleccionado
$medico_seleccionado_id = $_GET['medico_id'] ?? null;
$medico_seleccionado = null;
$horarios = [];

if ($medico_seleccionado_id) {
    // Obtener datos del médico
    $medico_seleccionado = $db->fetchOne("
        SELECT m.*, e.nombre as especialidad
        FROM medicos m
        INNER JOIN especialidades e ON m.especialidad_id = e.id
        WHERE m.id = :id
    ", ['id' => $medico_seleccionado_id]);

    // Obtener horarios del médico
    $horarios = $db->fetchAll("
        SELECT * FROM horarios_medicos
        WHERE medico_id = :medico_id
        AND estado = 'activo'
        ORDER BY 
            FIELD(dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'),
            hora_inicio
    ", ['medico_id' => $medico_seleccionado_id]);
}

$dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Agenda - Sistema de Clínica</title>
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
            <a href="../medicos/lista.php" class="sidebar-menu-item">
                <i class="fas fa-user-md"></i> Médicos
            </a>
            <a href="configurar.php" class="sidebar-menu-item active">
                <i class="fas fa-calendar-check"></i> Configurar Agenda
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
                <h4>Configurar Agenda Médica</h4>
                <small>Gestionar horarios de atención</small>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Seleccionar Médico -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-user-md"></i> Seleccionar Médico</h5>
                </div>
                <div class="card-body">
                    <form method="GET" style="display: flex; gap: 15px; align-items: end;">
                        <div style="flex: 1;">
                            <label class="form-label">Médico</label>
                            <select class="form-control" name="medico_id" required onchange="this.form.submit()">
                                <option value="">Seleccione un médico...</option>
                                <?php foreach ($medicos as $medico): ?>
                                    <option value="<?php echo $medico['id']; ?>" <?php echo $medico_seleccionado_id == $medico['id'] ? 'selected' : ''; ?>>
                                        <?php echo $medico['nombres'] . ' ' . $medico['apellidos'] . ' - ' . $medico['especialidad']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($medico_seleccionado): ?>
                <!-- Información del Médico -->
                <div class="card" style="margin-top: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="card-body">
                        <h4 style="margin: 0 0 10px 0;">
                            Dr(a). <?php echo $medico_seleccionado['nombres'] . ' ' . $medico_seleccionado['apellidos']; ?>
                        </h4>
                        <p style="margin: 0;">
                            <i class="fas fa-stethoscope"></i> <?php echo $medico_seleccionado['especialidad']; ?>
                        </p>
                    </div>
                </div>

                <!-- Agregar Nuevo Horario -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header" style="background: var(--success-color); color: white;">
                        <h5><i class="fas fa-plus"></i> Agregar Nuevo Horario</h5>
                    </div>
                    <div class="card-body">
                        <form action="../../controllers/agenda_controller.php" method="POST">
                            <input type="hidden" name="action" value="agregar">
                            <input type="hidden" name="medico_id" value="<?php echo $medico_seleccionado_id; ?>">
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                                <div>
                                    <label class="form-label">Día de la Semana <span style="color: red;">*</span></label>
                                    <select class="form-control" name="dia_semana" required>
                                        <option value="">Seleccionar...</option>
                                        <?php foreach ($dias_semana as $dia): ?>
                                            <option value="<?php echo $dia; ?>"><?php echo $dia; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label">Hora Inicio <span style="color: red;">*</span></label>
                                    <input type="time" class="form-control" name="hora_inicio" required>
                                </div>

                                <div>
                                    <label class="form-label">Hora Fin <span style="color: red;">*</span></label>
                                    <input type="time" class="form-control" name="hora_fin" required>
                                </div>

                                <div>
                                    <label class="form-label">Duración Cita (minutos) <span style="color: red;">*</span></label>
                                    <select class="form-control" name="duracion_cita" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="15">15 minutos</option>
                                        <option value="20">20 minutos</option>
                                        <option value="30" selected>30 minutos</option>
                                        <option value="45">45 minutos</option>
                                        <option value="60">60 minutos</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-success" style="height: 42px;">
                                    <i class="fas fa-plus"></i> Agregar Horario
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Horarios Configurados -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-alt"></i> Horarios Configurados</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($horarios)): ?>
                            <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                                <p>No hay horarios configurados para este médico</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Día</th>
                                        <th>Hora Inicio</th>
                                        <th>Hora Fin</th>
                                        <th>Duración Cita</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($horarios as $horario): ?>
                                    <tr>
                                        <td><strong><?php echo $horario['dia_semana']; ?></strong></td>
                                        <td><?php echo date('H:i', strtotime($horario['hora_inicio'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($horario['hora_fin'])); ?></td>
                                        <td><?php echo $horario['duracion_cita']; ?> min</td>
                                        <td>
                                            <span class="badge" style="background: <?php echo $horario['estado'] == 'activo' ? 'var(--success-color)' : 'var(--danger-color)'; ?>; color: white;">
                                                <?php echo ucfirst($horario['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="../../controllers/agenda_controller.php?action=eliminar&id=<?php echo $horario['id']; ?>&medico_id=<?php echo $medico_seleccionado_id; ?>" 
                                                   onclick="return confirm('¿Está seguro de eliminar este horario?')"
                                                   class="btn btn-sm btn-danger" 
                                                   title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Vista Semanal -->
                <?php if (!empty($horarios)): ?>
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-week"></i> Vista Semanal</h5>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                            <?php
                            // Agrupar horarios por día
                            $horarios_por_dia = [];
                            foreach ($horarios as $horario) {
                                $horarios_por_dia[$horario['dia_semana']][] = $horario;
                            }
                            
                            foreach ($dias_semana as $dia):
                            ?>
                            <div style="padding: 15px; border: 2px solid <?php echo isset($horarios_por_dia[$dia]) ? 'var(--success-color)' : '#e0e0e0'; ?>; border-radius: 8px; text-align: center;">
                                <strong style="display: block; margin-bottom: 10px; color: var(--primary-color);"><?php echo $dia; ?></strong>
                                <?php if (isset($horarios_por_dia[$dia])): ?>
                                    <?php foreach ($horarios_por_dia[$dia] as $horario): ?>
                                        <div style="font-size: 13px; margin: 5px 0; padding: 5px; background: #f0f8ff; border-radius: 4px;">
                                            <?php echo date('H:i', strtotime($horario['hora_inicio'])); ?> - 
                                            <?php echo date('H:i', strtotime($horario['hora_fin'])); ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <small style="color: var(--text-secondary);">Sin horario</small>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>