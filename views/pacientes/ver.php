<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

$paciente_id = $_GET['id'] ?? 0;

// Obtener datos del paciente
$paciente = $db->fetchOne("
    SELECT p.*, 
           s.nombre as seguro_nombre,
           TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) as edad
    FROM pacientes p
    LEFT JOIN seguros s ON p.seguro_id = s.id
    WHERE p.id = :id
", ['id' => $paciente_id]);

if (!$paciente) {
    $_SESSION['error'] = 'Paciente no encontrado';
    redirect('lista.php');
}

// Estadísticas del paciente
$stats = $db->fetchOne("
    SELECT 
        COUNT(DISTINCT c.id) as total_citas,
        COUNT(DISTINCT con.id) as total_consultas,
        MAX(c.fecha) as ultima_cita
    FROM pacientes p
    LEFT JOIN citas c ON p.id = c.paciente_id
    LEFT JOIN consultas con ON p.id = con.paciente_id
    WHERE p.id = :id
", ['id' => $paciente_id]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Paciente - Sistema de Clínica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <style>
        .info-section {
            margin-bottom: 30px;
        }
        .info-section h5 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            color: var(--primary-color);
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .info-item {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .info-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            font-weight: 500;
            color: var(--text-primary);
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
            <a href="lista.php" class="sidebar-menu-item active">
                <i class="fas fa-users"></i> Pacientes
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
                <h4>Información del Paciente</h4>
                <small>Detalles completos del paciente</small>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="editar.php?id=<?php echo $paciente['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="../citas/nueva.php?paciente_id=<?php echo $paciente['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-calendar-plus"></i> Agendar Cita
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Header del Paciente -->
            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 20px;">
                <div class="card-body">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <div style="width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 48px;">
                            <i class="fas fa-user"></i>
                        </div>
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
                                    <?php echo $paciente['grupo_sanguineo'] ?? 'No especificado'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <h3 style="margin: 0; color: var(--primary-color);"><?php echo $stats['total_citas']; ?></h3>
                        <small style="color: var(--text-secondary);">Total Citas</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <h3 style="margin: 0; color: var(--success-color);"><?php echo $stats['total_consultas']; ?></h3>
                        <small style="color: var(--text-secondary);">Consultas Médicas</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 18px; color: var(--warning-color);">
                            <?php echo $stats['ultima_cita'] ? date('d/m/Y', strtotime($stats['ultima_cita'])) : 'Sin citas'; ?>
                        </div>
                        <small style="color: var(--text-secondary);">Última Cita</small>
                    </div>
                </div>
            </div>

            <!-- Información Detallada -->
            <div class="card">
                <div class="card-body">
                    <!-- Datos Personales -->
                    <div class="info-section">
                        <h5><i class="fas fa-user"></i> Datos Personales</h5>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">DNI</div>
                                <div class="info-value"><?php echo $paciente['dni']; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Nombres</div>
                                <div class="info-value"><?php echo $paciente['nombres']; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Apellidos</div>
                                <div class="info-value"><?php echo $paciente['apellidos']; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Fecha de Nacimiento</div>
                                <div class="info-value"><?php echo date('d/m/Y', strtotime($paciente['fecha_nacimiento'])); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Edad</div>
                                <div class="info-value"><?php echo $paciente['edad']; ?> años</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Sexo</div>
                                <div class="info-value"><?php echo $paciente['sexo'] == 'M' ? 'Masculino' : 'Femenino'; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Estado Civil</div>
                                <div class="info-value"><?php echo $paciente['estado_civil'] ?? 'No especificado'; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Contacto -->
                    <div class="info-section">
                        <h5><i class="fas fa-phone"></i> Información de Contacto</h5>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Dirección</div>
                                <div class="info-value"><?php echo $paciente['direccion'] ?? 'No especificada'; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Celular</div>
                                <div class="info-value"><?php echo $paciente['celular']; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Teléfono</div>
                                <div class="info-value"><?php echo $paciente['telefono'] ?? 'No especificado'; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?php echo $paciente['email'] ?? 'No especificado'; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Información Médica -->
                    <div class="info-section">
                        <h5><i class="fas fa-heartbeat"></i> Información Médica</h5>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Grupo Sanguíneo</div>
                                <div class="info-value"><?php echo $paciente['grupo_sanguineo'] ?? 'No especificado'; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Seguro Médico</div>
                                <div class="info-value"><?php echo $paciente['seguro_nombre'] ?? 'Sin seguro'; ?></div>
                            </div>
                            <?php if ($paciente['alergias']): ?>
                            <div class="info-item" style="grid-column: 1 / -1; background: #fff3cd; border-left: 4px solid var(--warning-color);">
                                <div class="info-label">⚠️ ALERGIAS</div>
                                <div class="info-value" style="color: var(--danger-color);"><?php echo $paciente['alergias']; ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Acciones Rápidas -->
                    <div style="display: flex; gap: 10px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                        <a href="../consultas/historial.php?paciente_id=<?php echo $paciente['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-history"></i> Ver Historial Médico
                        </a>
                        <a href="../citas/lista.php?paciente_dni=<?php echo $paciente['dni']; ?>" class="btn">
                            <i class="fas fa-calendar-alt"></i> Ver Citas
                        </a>
                        <a href="lista.php" class="btn" style="margin-left: auto;">
                            <i class="fas fa-arrow-left"></i> Volver a Lista
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>