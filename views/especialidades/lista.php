<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

$especialidades = $db->fetchAll("
    SELECT e.*, 
           COUNT(m.id) as total_medicos
    FROM especialidades e
    LEFT JOIN medicos m ON e.id = m.especialidad_id AND m.estado = 'activo'
    WHERE e.estado = 'activo'
    GROUP BY e.id
    ORDER BY e.nombre
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Especialidades - Sistema de Clínica</title>
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
            <a href="lista.php" class="sidebar-menu-item active">
                <i class="fas fa-stethoscope"></i> Especialidades
            </a>
            <a href="../citas/lista.php" class="sidebar-menu-item">
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
                <h4>Especialidades Médicas</h4>
                <small>Gestión de especialidades</small>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="page-header">
                <div class="page-title">
                    <h2>Especialidades</h2>
                    <div class="breadcrumb">
                        <a href="../dashboard.php">Dashboard</a> / Especialidades
                    </div>
                </div>
            </div>

            <!-- Grid de Especialidades -->
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                <?php foreach ($especialidades as $esp): ?>
                <div class="card">
                    <div class="card-body">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 60px; height: 60px; background: <?php echo $esp['color']; ?>20; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: <?php echo $esp['color']; ?>; font-size: 28px;">
                                <i class="<?php echo $esp['icono']; ?>"></i>
                            </div>
                            <div style="flex: 1;">
                                <h5 style="margin: 0; color: var(--text-primary);"><?php echo $esp['nombre']; ?></h5>
                                <p style="margin: 5px 0 0 0; color: var(--text-secondary); font-size: 14px;">
                                    <i class="fas fa-user-md"></i> <?php echo $esp['total_medicos']; ?> médicos
                                </p>
                            </div>
                        </div>
                        <?php if ($esp['descripcion']): ?>
                        <p style="margin-top: 15px; color: var(--text-secondary); font-size: 13px;">
                            <?php echo $esp['descripcion']; ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>