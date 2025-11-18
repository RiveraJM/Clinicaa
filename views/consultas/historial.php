<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

$paciente_id = $_GET['paciente_id'] ?? 0;

// Obtener datos del paciente
$paciente = $db->fetchOne("
    SELECT *, 
           TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) as edad
    FROM pacientes 
    WHERE id = :id
", ['id' => $paciente_id]);

if (!$paciente) {
    $_SESSION['error'] = 'Paciente no encontrado';
    redirect('../pacientes/lista.php');
}

// Obtener todas las consultas
$consultas = $db->fetchAll("
    SELECT c.*,
           CONCAT(m.nombres, ' ', m.apellidos) as medico_nombre,
           e.nombre as especialidad,
           ci.fecha as cita_fecha
    FROM consultas c
    INNER JOIN medicos m ON c.medico_id = m.id
    INNER JOIN especialidades e ON m.especialidad_id = e.id
    LEFT JOIN citas ci ON c.cita_id = ci.id
    WHERE c.paciente_id = :paciente_id
    ORDER BY c.fecha_consulta DESC
", ['paciente_id' => $paciente_id]);

// Estadísticas
$stats = [
    'total_consultas' => count($consultas),
    'ultima_consulta' => !empty($consultas) ? $consultas[0]['fecha_consulta'] : null,
    'especialidades_atendidas' => count(array_unique(array_column($consultas, 'especialidad')))
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Clínico - Sistema de Clínica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <style>
        .header-paciente {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #ddd;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 3px solid white;
            box-shadow: 0 0 0 2px var(--primary-color);
        }
    </style>
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
            <a href="lista.php" class="sidebar-menu-item active">
                <i class="fas fa-notes-medical"></i> Consultas
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
                <h4>Historial Clínico</h4>
                <small>Consultas médicas del paciente</small>
            </div>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Imprimir Historial
            </button>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Header Paciente -->
            <div class="header-paciente">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <h2 style="margin: 0 0 10px 0;"><?php echo $paciente['nombres'] . ' ' . $paciente['apellidos']; ?></h2>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">
                            <div>
                                <strong style="display: block; font-size: 12px; opacity: 0.9;">DNI</strong>
                                <?php echo $paciente['dni']; ?>
                            </div>
                            <div>
                                <strong style="display: block; font-size: 12px; opacity: 0.9;">Edad</strong>
                                <?php echo $paciente['edad']; ?> años
                            </div>
                            <div>
                                <strong style="display: block; font-size: 12px; opacity: 0.9;">Sexo</strong>
                                <?php echo $paciente['sexo'] == 'M' ? 'Masculino' : 'Femenino'; ?>
                            </div>
                            <div>
                                <strong style="display: block; font-size: 12px; opacity: 0.9;">Grupo Sanguíneo</strong>
                                <?php echo $paciente['grupo_sanguineo'] ?? 'No registrado'; ?>
                            </div>
                        </div>
                        <?php if ($paciente['alergias']): ?>
                        <div style="margin-top: 15px; padding: 10px; background: rgba(244,67,54,0.2); border-radius: 5px;">
                            <strong>⚠️ ALERGIAS:</strong> <?php echo $paciente['alergias']; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <h3 style="margin: 0; color: var(--primary-color);"><?php echo $stats['total_consultas']; ?></h3>
                        <small style="color: var(--text-secondary);">Total Consultas</small>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <h3 style="margin: 0; color: var(--success-color);"><?php echo $stats['especialidades_atendidas']; ?></h3>
                        <small style="color: var(--text-secondary);">Especialidades</small>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 18px; color: var(--warning-color);">
                            <?php echo $stats['ultima_consulta'] ? date('d/m/Y', strtotime($stats['ultima_consulta'])) : 'Sin consultas'; ?>
                        </div>
                        <small style="color: var(--text-secondary);">Última Consulta</small>
                    </div>
                </div>
            </div>

            <!-- Timeline de Consultas -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-history"></i> Historial de Consultas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($consultas)): ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                            <i class="fas fa-file-medical-alt" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                            <p>Este paciente no tiene consultas registradas</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($consultas as $consulta): ?>
                            <div class="timeline-item">
                                <div class="card" style="margin-left: 20px;">
                                    <div class="card-body">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                                            <div>
                                                <h5 style="margin: 0; color: var(--primary-color);">
                                                    <?php echo date('d/m/Y', strtotime($consulta['fecha_consulta'])); ?>
                                                </h5>
                                                <small style="color: var(--text-secondary);">
                                                    <?php echo $consulta['medico_nombre']; ?> - <?php echo $consulta['especialidad']; ?>
                                                </small>
                                            </div>
                                            <a href="ver.php?id=<?php echo $consulta['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> Ver Completa
                                            </a>
                                        </div>

                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                                            <?php if ($consulta['presion_arterial']): ?>
                                            <div>
                                                <small style="color: var(--text-secondary); display: block;">Presión Arterial</small>
                                                <strong><?php echo $consulta['presion_arterial']; ?></strong>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ($consulta['temperatura']): ?>
                                            <div>
                                                <small style="color: var(--text-secondary); display: block;">Temperatura</small>
                                                <strong><?php echo $consulta['temperatura']; ?> °C</strong>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ($consulta['peso']): ?>
                                            <div>
                                                <small style="color: var(--text-secondary); display: block;">Peso</small>
                                                <strong><?php echo $consulta['peso']; ?> kg</strong>
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <div style="padding: 15px; background: #f9f9f9; border-radius: 5px; margin-bottom: 10px;">
                                            <strong style="display: block; margin-bottom: 5px; color: var(--primary-color);">
                                                <i class="fas fa-diagnoses"></i> Diagnóstico:
                                            </strong>
                                            <?php echo $consulta['diagnostico_principal']; ?>
                                        </div>

                                        <div style="padding: 15px; background: #f0f8ff; border-radius: 5px;">
                                            <strong style="display: block; margin-bottom: 5px; color: var(--primary-color);">
                                                <i class="fas fa-prescription"></i> Tratamiento:
                                            </strong>
                                            <?php echo nl2br($consulta['tratamiento']); ?>
                                        </div>

                                        <?php if ($consulta['requiere_control']): ?>
                                        <div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-left: 4px solid var(--warning-color);">
                                            <i class="fas fa-calendar-check"></i> <strong>Control requerido en <?php echo $consulta['dias_control']; ?> días</strong>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>