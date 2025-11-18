<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

// Período por defecto: mes actual
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

// ==========================================
// ESTADÍSTICAS GENERALES
// ==========================================

// Total de citas
$stats_citas = $db->fetchOne("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado_cita_id = 5 THEN 1 ELSE 0 END) as atendidas,
        SUM(CASE WHEN estado_cita_id = 7 THEN 1 ELSE 0 END) as canceladas,
        SUM(CASE WHEN estado_cita_id IN (1,2) THEN 1 ELSE 0 END) as pendientes
    FROM citas
    WHERE fecha BETWEEN :desde AND :hasta
", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);

// Pacientes
$stats_pacientes = $db->fetchOne("
    SELECT 
        COUNT(DISTINCT c.paciente_id) as atendidos,
        (SELECT COUNT(*) FROM pacientes WHERE estado = 'activo') as total_registrados
    FROM citas c
    WHERE c.fecha BETWEEN :desde AND :hasta
", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);

// Ingresos
$stats_ingresos = $db->fetchOne("
    SELECT 
        COALESCE(SUM(precio), 0) as total_ingresos,
        COALESCE(AVG(precio), 0) as promedio_cita
    FROM citas
    WHERE fecha BETWEEN :desde AND :hasta
    AND estado_cita_id = 5
", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);

// Consultas médicas
$stats_consultas = $db->fetchOne("
    SELECT COUNT(*) as total
    FROM consultas
    WHERE DATE(fecha_consulta) BETWEEN :desde AND :hasta
", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);

// ==========================================
// CITAS POR DÍA (para gráfico)
// ==========================================
$citas_por_dia = $db->fetchAll("
    SELECT 
        DATE(fecha) as fecha,
        COUNT(*) as total
    FROM citas
    WHERE fecha BETWEEN :desde AND :hasta
    GROUP BY DATE(fecha)
    ORDER BY fecha
", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);

// ==========================================
// TOP ESPECIALIDADES
// ==========================================
$top_especialidades = $db->fetchAll("
    SELECT 
        e.nombre,
        e.color,
        COUNT(c.id) as total_citas,
        COALESCE(SUM(c.precio), 0) as ingresos
    FROM citas c
    INNER JOIN especialidades e ON c.especialidad_id = e.id
    WHERE c.fecha BETWEEN :desde AND :hasta
    GROUP BY e.id, e.nombre, e.color
    ORDER BY total_citas DESC
    LIMIT 5
", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);

// ==========================================
// TOP MÉDICOS
// ==========================================
$top_medicos = $db->fetchAll("
    SELECT 
        CONCAT(m.nombres, ' ', m.apellidos) as medico,
        e.nombre as especialidad,
        COUNT(c.id) as total_citas,
        COALESCE(SUM(c.precio), 0) as ingresos
    FROM citas c
    INNER JOIN medicos m ON c.medico_id = m.id
    INNER JOIN especialidades e ON m.especialidad_id = e.id
    WHERE c.fecha BETWEEN :desde AND :hasta
    AND c.estado_cita_id = 5
    GROUP BY m.id, medico, e.nombre
    ORDER BY total_citas DESC
    LIMIT 10
", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);

// ==========================================
// DIAGNÓSTICOS MÁS COMUNES
// ==========================================
$top_diagnosticos = $db->fetchAll("
    SELECT 
        diagnostico_principal,
        COUNT(*) as frecuencia
    FROM consultas
    WHERE DATE(fecha_consulta) BETWEEN :desde AND :hasta
    GROUP BY diagnostico_principal
    ORDER BY frecuencia DESC
    LIMIT 10
", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Sistema de Clínica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            <a href="../citas/lista.php" class="sidebar-menu-item">
                <i class="fas fa-calendar-alt"></i> Citas
            </a>
            <a href="index.php" class="sidebar-menu-item active">
                <i class="fas fa-chart-bar"></i> Reportes
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
                <h4>Reportes y Estadísticas</h4>
                <small>Análisis de datos del sistema</small>
            </div>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Filtro de Período -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                        <div>
                            <label class="form-label">Desde</label>
                            <input type="date" class="form-control" name="fecha_desde" value="<?php echo $fecha_desde; ?>" required>
                        </div>
                        <div>
                            <label class="form-label">Hasta</label>
                            <input type="date" class="form-control" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync"></i> Actualizar
                        </button>
                        <div style="margin-left: auto; display: flex; gap: 10px; flex-wrap: wrap;">
                            <a href="citas.php?fecha_desde=<?php echo $fecha_desde; ?>&fecha_hasta=<?php echo $fecha_hasta; ?>" class="btn">
                                <i class="fas fa-calendar"></i> Reporte de Citas
                            </a>
                            <a href="financiero.php?fecha_desde=<?php echo $fecha_desde; ?>&fecha_hasta=<?php echo $fecha_hasta; ?>" class="btn">
                                <i class="fas fa-dollar-sign"></i> Reporte Financiero
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <h5 style="margin: 20px 0; color: var(--text-secondary);">
                Período: <?php echo date('d/m/Y', strtotime($fecha_desde)); ?> - <?php echo date('d/m/Y', strtotime($fecha_hasta)); ?>
            </h5>

            <!-- Estadísticas Principales -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <!-- Total Citas -->
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="width: 60px; height: 60px; background: rgba(0,188,212,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: var(--primary-color); font-size: 28px;">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h2 style="margin: 0; color: var(--primary-color);"><?php echo $stats_citas['total']; ?></h2>
                        <p style="color: var(--text-secondary); margin: 5px 0 10px 0;">Total Citas</p>
                        <small style="color: var(--success-color);">✓ <?php echo $stats_citas['atendidas']; ?> Atendidas</small> • 
                        <small style="color: var(--danger-color);">✗ <?php echo $stats_citas['canceladas']; ?> Canceladas</small>
                    </div>
                </div>

                <!-- Pacientes -->
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="width: 60px; height: 60px; background: rgba(76,175,80,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: var(--success-color); font-size: 28px;">
                            <i class="fas fa-users"></i>
                        </div>
                        <h2 style="margin: 0; color: var(--success-color);"><?php echo $stats_pacientes['atendidos']; ?></h2>
                        <p style="color: var(--text-secondary); margin: 5px 0 10px 0;">Pacientes Atendidos</p>
                        <small style="color: var(--primary-color);">Total registrados: <?php echo $stats_pacientes['total_registrados']; ?></small>
                    </div>
                </div>

                <!-- Ingresos -->
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="width: 60px; height: 60px; background: rgba(255,193,7,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: var(--warning-color); font-size: 28px;">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <h2 style="margin: 0; color: var(--warning-color);">S/ <?php echo number_format($stats_ingresos['total_ingresos'], 2); ?></h2>
                        <p style="color: var(--text-secondary); margin: 5px 0 10px 0;">Ingresos Totales</p>
                        <small style="color: var(--text-secondary);">Promedio: S/ <?php echo number_format($stats_ingresos['promedio_cita'], 2); ?> por cita</small>
                    </div>
                </div>

                <!-- Consultas -->
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="width: 60px; height: 60px; background: rgba(156,39,176,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: #9C27B0; font-size: 28px;">
                            <i class="fas fa-notes-medical"></i>
                        </div>
                        <h2 style="margin: 0; color: #9C27B0;"><?php echo $stats_consultas['total']; ?></h2>
                        <p style="color: var(--text-secondary); margin: 5px 0;">Consultas Registradas</p>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <!-- Gráfico: Citas por Día -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line"></i> Citas por Día</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($citas_por_dia)): ?>
                            <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                <i class="fas fa-chart-line" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                                <p>No hay citas en este período</p>
                            </div>
                        <?php else: ?>
                            <canvas id="chartCitasDia" style="max-height: 300px;"></canvas>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Gráfico: Top Especialidades -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie"></i> Citas por Especialidad</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_especialidades)): ?>
                            <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                <i class="fas fa-chart-pie" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                                <p>No hay datos de especialidades</p>
                            </div>
                        <?php else: ?>
                            <canvas id="chartEspecialidades" style="max-height: 300px;"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tablas de Top -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
                <!-- Top Médicos -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-user-md"></i> Top 10 Médicos</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_medicos)): ?>
                            <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                <p>No hay datos disponibles</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Médico</th>
                                        <th>Especialidad</th>
                                        <th>Citas</th>
                                        <th>Ingresos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_medicos as $medico): ?>
                                    <tr>
                                        <td><?php echo $medico['medico']; ?></td>
                                        <td><small><?php echo $medico['especialidad']; ?></small></td>
                                        <td><strong><?php echo $medico['total_citas']; ?></strong></td>
                                        <td><span style="color: var(--success-color);">S/ <?php echo number_format($medico['ingresos'], 2); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top Diagnósticos -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-diagnoses"></i> Diagnósticos Más Frecuentes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_diagnosticos)): ?>
                            <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                <p>No hay consultas registradas</p>
                            </div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Diagnóstico</th>
                                        <th>Frecuencia</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_diagnosticos as $diag): ?>
                                    <tr>
                                        <td><?php echo $diag['diagnostico_principal']; ?></td>
                                        <td>
                                            <strong style="color: var(--primary-color);"><?php echo $diag['frecuencia']; ?></strong>
                                            <div style="width: 100%; height: 4px; background: #eee; border-radius: 2px; margin-top: 5px;">
                                                <div style="width: <?php echo ($diag['frecuencia'] / $top_diagnosticos[0]['frecuencia']) * 100; ?>%; height: 100%; background: var(--primary-color); border-radius: 2px;"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        <?php if (!empty($citas_por_dia)): ?>
        // Gráfico: Citas por Día
        const ctxDia = document.getElementById('chartCitasDia');
        if (ctxDia) {
            new Chart(ctxDia.getContext('2d'), {
                type: 'line',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return '"' . date('d/m', strtotime($item['fecha'])) . '"'; }, $citas_por_dia)); ?>],
                    datasets: [{
                        label: 'Citas',
                        data: [<?php echo implode(',', array_column($citas_por_dia, 'total')); ?>],
                        borderColor: '#00BCD4',
                        backgroundColor: 'rgba(0,188,212,0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        }
        <?php endif; ?>

        <?php if (!empty($top_especialidades)): ?>
        // Gráfico: Top Especialidades
        const ctxEsp = document.getElementById('chartEspecialidades');
        if (ctxEsp) {
            new Chart(ctxEsp.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return '"' . $item['nombre'] . '"'; }, $top_especialidades)); ?>],
                    datasets: [{
                        data: [<?php echo implode(',', array_column($top_especialidades, 'total_citas')); ?>],
                        backgroundColor: [<?php echo implode(',', array_map(function($item) { return '"' . $item['color'] . '"'; }, $top_especialidades)); ?>],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>