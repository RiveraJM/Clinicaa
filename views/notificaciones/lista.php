<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

// Filtros
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-7 days'));
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$tipo = $_GET['tipo'] ?? '';

// Consulta de notificaciones
$sql = "SELECT n.*,
               CONCAT(p.nombres, ' ', p.apellidos) as paciente,
               c.fecha as cita_fecha,
               c.hora as cita_hora,
               CONCAT(m.nombres, ' ', m.apellidos) as medico
        FROM notificaciones n
        INNER JOIN citas c ON n.cita_id = c.id
        INNER JOIN pacientes p ON c.paciente_id = p.id
        INNER JOIN medicos m ON c.medico_id = m.id
        WHERE DATE(n.fecha_envio) BETWEEN :fecha_desde AND :fecha_hasta";

$params = [
    'fecha_desde' => $fecha_desde,
    'fecha_hasta' => $fecha_hasta
];

if ($tipo) {
    $sql .= " AND n.tipo = :tipo";
    $params['tipo'] = $tipo;
}

$sql .= " ORDER BY n.fecha_envio DESC LIMIT 100";

$notificaciones = $db->fetchAll($sql, $params);

// Estadísticas
$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total,
        SUM(email_enviado) as emails_enviados,
        SUM(whatsapp_enviado) as whatsapp_enviados,
        SUM(CASE WHEN tipo = 'confirmacion' THEN 1 ELSE 0 END) as confirmaciones,
        SUM(CASE WHEN tipo = 'recordatorio' THEN 1 ELSE 0 END) as recordatorios,
        SUM(CASE WHEN tipo = 'cancelacion' THEN 1 ELSE 0 END) as cancelaciones
    FROM notificaciones
    WHERE DATE(fecha_envio) BETWEEN :fecha_desde AND :fecha_hasta
", $params);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones - Sistema de Clínica</title>
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
            <a href="../especialidades/lista.php" class="sidebar-menu-item">
                <i class="fas fa-stethoscope"></i> Especialidades
            </a>
            <a href="../citas/lista.php" class="sidebar-menu-item">
                <i class="fas fa-calendar-alt"></i> Citas
            </a>
            <a href="lista.php" class="sidebar-menu-item active">
                <i class="fas fa-bell"></i> Notificaciones
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
                <h4>Historial de Notificaciones</h4>
                <small>Notificaciones enviadas por email y WhatsApp</small>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="page-header">
                <div class="page-title">
                    <h2>Notificaciones</h2>
                    <div class="breadcrumb">
                        <a href="../dashboard.php">Dashboard</a> / Notificaciones
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <h3 style="margin: 0; color: var(--primary-color);"><?php echo $stats['total']; ?></h3>
                        <small style="color: var(--text-secondary);">Total Enviadas</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <h3 style="margin: 0; color: var(--success-color);"><?php echo $stats['emails_enviados']; ?></h3>
                        <small style="color: var(--text-secondary);">📧 Emails</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <h3 style="margin: 0; color: #25D366;"><?php echo $stats['whatsapp_enviados']; ?></h3>
                        <small style="color: var(--text-secondary);">💬 WhatsApp</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <h3 style="margin: 0; color: var(--warning-color);"><?php echo $stats['recordatorios']; ?></h3>
                        <small style="color: var(--text-secondary);">Recordatorios</small>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                        <div>
                            <label class="form-label">Desde</label>
                            <input type="date" class="form-control" name="fecha_desde" value="<?php echo $fecha_desde; ?>">
                        </div>

                        <div>
                            <label class="form-label">Hasta</label>
                            <input type="date" class="form-control" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>">
                        </div>

                        <div>
                            <label class="form-label">Tipo</label>
                            <select class="form-control" name="tipo">
                                <option value="">Todos</option>
                                <option value="confirmacion" <?php echo $tipo == 'confirmacion' ? 'selected' : ''; ?>>Confirmación</option>
                                <option value="recordatorio" <?php echo $tipo == 'recordatorio' ? 'selected' : ''; ?>>Recordatorio</option>
                                <option value="cancelacion" <?php echo $tipo == 'cancelacion' ? 'selected' : ''; ?>>Cancelación</option>
                                <option value="reprogramacion" <?php echo $tipo == 'reprogramacion' ? 'selected' : ''; ?>>Reprogramación</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>

                        <a href="lista.php" class="btn" style="background: #6c757d; color: white;">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </form>
                </div>
            </div>

            <!-- Tabla de Notificaciones -->
            <div class="card">
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Paciente</th>
                                <th>Cita</th>
                                <th>Tipo</th>
                                <th>Email</th>
                                <th>WhatsApp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($notificaciones)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-bell-slash" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                                        <p style="color: var(--text-secondary);">No hay notificaciones en el rango seleccionado</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($notificaciones as $notif): ?>
                                <tr>
                                    <td>
                                        <small style="color: var(--text-secondary);">
                                            <?php echo date('d/m/Y H:i', strtotime($notif['fecha_envio'])); ?>
                                        </small>
                                    </td>
                                    <td><?php echo $notif['paciente']; ?></td>
                                    <td>
                                        <small>
                                            <?php echo date('d/m/Y', strtotime($notif['cita_fecha'])); ?> - 
                                            <?php echo date('H:i', strtotime($notif['cita_hora'])); ?><br>
                                            Dr(a). <?php echo $notif['medico']; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: 
                                            <?php 
                                            echo match($notif['tipo']) {
                                                'confirmacion' => 'var(--success-color)',
                                                'recordatorio' => 'var(--warning-color)',
                                                'cancelacion' => 'var(--danger-color)',
                                                'reprogramacion' => 'var(--primary-color)',
                                                default => '#666'
                                            };
                                            ?>; color: white;">
                                            <?php echo ucfirst($notif['tipo']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($notif['email_enviado']): ?>
                                            <i class="fas fa-check-circle" style="color: var(--success-color);"></i> Enviado
                                        <?php else: ?>
                                            <i class="fas fa-times-circle" style="color: var(--text-secondary);"></i> No enviado
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($notif['whatsapp_enviado']): ?>
                                            <i class="fas fa-check-circle" style="color: #25D366;"></i> Enviado
                                        <?php else: ?>
                                            <i class="fas fa-times-circle" style="color: var(--text-secondary);"></i> No enviado
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>