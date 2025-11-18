<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

$medico_id = $_GET['id'] ?? 0;

// Obtener datos del médico
$medico = $db->fetchOne("
    SELECT m.*, 
           e.nombre as especialidad_nombre,
           e.color as especialidad_color,
           TIMESTAMPDIFF(YEAR, m.fecha_nacimiento, CURDATE()) as edad
    FROM medicos m
    INNER JOIN especialidades e ON m.especialidad_id = e.id
    WHERE m.id = :id
", ['id' => $medico_id]);

if (!$medico) {
    $_SESSION['error'] = 'Médico no encontrado';
    redirect('lista.php');
}

// Estadísticas del médico
$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_citas,
        COUNT(CASE WHEN estado_cita_id = 5 THEN 1 END) as atendidas,
        MIN(CASE WHEN fecha >= CURDATE() THEN fecha END) as proxima_cita
    FROM citas
    WHERE medico_id = :id
", ['id' => $medico_id]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Médico - Sistema de Clínica</title>
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
                <i class="fas fa-user-md"></i> Médicos
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
                <h4>Información del Médico</h4>
                <small>Detalles completos del médico</small>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="editar.php?id=<?php echo $medico['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="../agenda/configurar.php?medico_id=<?php echo $medico['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-calendar-check"></i> Configurar Agenda
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Header del Médico -->
            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; margin-bottom: 20px;">
                <div class="card-body">
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <div style="width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 48px;">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div>
                            <h2 style="margin: 0 0 10px 0;">Dr(a). <?php echo $medico['nombres'] . ' ' . $medico['apellidos']; ?></h2>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;">
                                <div>
                                    <strong style="display: block; font-size: 12px; opacity: 0.9;">Especialidad</strong>
                                    <?php echo $medico['especialidad_nombre']; ?>
                                </div>
                                <div>
                                    <strong style="display: block; font-size: 12px; opacity: 0.9;">CMP</strong>
                                    <?php echo $medico['nro_colegiatura']; ?>
                                </div>
                                <?php if ($medico['rne']): ?>
                                <div>
                                    <strong style="display: block; font-size: 12px; opacity: 0.9;">RNE</strong>
                                    <?php echo $medico['rne']; ?>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <strong style="display: block; font-size: 12px; opacity: 0.9;">Estado</strong>
                                    <span style="background: <?php echo $medico['estado'] == 'activo' ? '#4CAF50' : '#F44336'; ?>; padding: 2px 10px; border-radius: 10px; font-size: 12px;">
                                        <?php echo ucfirst($medico['estado']); ?>
                                    </span>
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
                        <h3 style="margin: 0; color: var(--success-color);"><?php echo $stats['atendidas']; ?></h3>
                        <small style="color: var(--text-secondary);">Citas Atendidas</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 18px; color: var(--warning-color);">
                            <?php echo $stats['proxima_cita'] ? date('d/m/Y', strtotime($stats['proxima_cita'])) : 'Sin citas'; ?>
                        </div>
                        <small style="color: var(--text-secondary);">Próxima Cita</small>
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
                                <div class="info-value"><?php echo $medico['dni']; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Nombres</div>
                                <div class="info-value"><?php echo $medico['nombres']; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Apellidos</div>
                                <div class="info-value"><?php echo $medico['apellidos']; ?></div>
                            </div>
                            <?php if ($medico['fecha_nacimiento']): ?>
                            <div class="info-item">
                                <div class="info-label">Fecha de Nacimiento</div>
                                <div class="info-value"><?php echo date('d/m/Y', strtotime($medico['fecha_nacimiento'])); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Edad</div>
                                <div class="info-value"><?php echo $medico['edad']; ?> años</div>
                            </div>
                            <?php endif; ?>
                            <?php if ($medico['sexo']): ?>
                            <div class="info-item">
                                <div class="info-label">Sexo</div>
                                <div class="info-value"><?php echo $medico['sexo'] == 'M' ? 'Masculino' : 'Femenino'; ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Información Profesional -->
                    <div class="info-section">
                        <h5><i class="fas fa-user-md"></i> Información Profesional</h5>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Nro. Colegiatura (CMP)</div>
                                <div class="info-value"><?php echo $medico['nro_colegiatura']; ?></div>
                            </div>
                            <?php if ($medico['rne']): ?>
                            <div class="info-item">
                                <div class="info-label">RNE</div>
                                <div class="info-value"><?php echo $medico['rne']; ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <div class="info-label">Especialidad</div>
                                <div class="info-value">
                                    <span style="background: <?php echo $medico['especialidad_color']; ?>20; color: <?php echo $medico['especialidad_color']; ?>; padding: 4px 12px; border-radius: 15px; display: inline-block;">
                                        <?php echo $medico['especialidad_nombre']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Estado</div>
                                <div class="info-value">
                                    <span style="background: <?php echo $medico['estado'] == 'activo' ? '#4CAF50' : '#F44336'; ?>; color: white; padding: 4px 12px; border-radius: 15px; display: inline-block;">
                                        <?php echo ucfirst($medico['estado']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Contacto -->
                    <div class="info-section">
                        <h5><i class="fas fa-phone"></i> Información de Contacto</h5>
                        <div class="info-grid">
                            <?php if ($medico['direccion']): ?>
                            <div class="info-item">
                                <div class="info-label">Dirección</div>
                                <div class="info-value"><?php echo $medico['direccion']; ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <div class="info-label">Celular</div>
                                <div class="info-value"><?php echo $medico['celular']; ?></div>
                            </div>
                            <?php if ($medico['telefono']): ?>
                            <div class="info-item">
                                <div class="info-label">Teléfono</div>
                                <div class="info-value"><?php echo $medico['telefono']; ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?php echo $medico['email']; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones Rápidas -->
                    <div style="display: flex; gap: 10px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                        <a href="../agenda/configurar.php?medico_id=<?php echo $medico['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-calendar-check"></i> Configurar Agenda
                        </a>
                        <a href="../citas/lista.php?medico_id=<?php echo $medico['id']; ?>" class="btn">
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