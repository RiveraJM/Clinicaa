<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

// ==========================================
// RESUMEN FINANCIERO
// ==========================================
$resumen = $db->fetchOne("
    SELECT 
        COUNT(*) as total_citas,
        SUM(CASE WHEN estado_cita_id = 5 THEN 1 ELSE 0 END) as citas_cobradas,
        SUM(CASE WHEN estado_cita_id = 5 THEN precio ELSE 0 END) as ingresos_totales,
        AVG(CASE WHEN estado_cita_id = 5 THEN precio ELSE NULL END) as precio_promedio,
        MIN(CASE WHEN estado_cita_id = 5 THEN precio ELSE NULL END) as precio_minimo,
        MAX(CASE WHEN estado_cita_id = 5 THEN precio ELSE NULL END) as precio_maximo
    FROM citas
    WHERE fecha BETWEEN :desde AND :hasta
", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);

// ==========================================
// INGRESOS POR DÍA
// ==========================================
$ingresos_dia = $db->fetchAll("
    SELECT 
        DATE(fecha) as fecha,
        COUNT(*) as total_citas,
        SUM(precio) as ingresos
    FROM citas
    WHERE fecha BETWEEN :desde AND :hasta
    AND estado_cita_id = 5
    GROUP BY DATE(fecha)
    ORDER BY fecha
", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);

// ==========================================
// INGRESOS POR ESPECIALIDAD
// ==========================================
$ingresos_especialidad = $db->fetchAll("
    SELECT 
        e.nombre,
        e.color,
        COUNT(c.id) as total_citas,
        SUM(c.precio) as ingresos,
        AVG(c.precio) as precio_promedio
    FROM citas c
    INNER JOIN especialidades e ON c.especialidad_id = e.id
    WHERE c.fecha BETWEEN :desde AND :hasta
    AND c.estado_cita_id = 5
    GROUP BY e.id, e.nombre, e.color
    ORDER BY ingresos DESC
", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);

// ==========================================
// INGRESOS POR MÉDICO
// ==========================================
$ingresos_medico = $db->fetchAll("
    SELECT 
        CONCAT(m.nombres, ' ', m.apellidos) as medico,
        e.nombre as especialidad,
        COUNT(c.id) as total_citas,
        SUM(c.precio) as ingresos,
        AVG(c.precio) as precio_promedio
    FROM citas c
    INNER JOIN medicos m ON c.medico_id = m.id
    INNER JOIN especialidades e ON m.especialidad_id = e.id
    WHERE c.fecha BETWEEN :desde AND :hasta
    AND c.estado_cita_id = 5
    GROUP BY m.id, medico, e.nombre
    ORDER BY ingresos DESC
", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);

// ==========================================
// INGRESOS POR TIPO DE CITA
// ==========================================
$ingresos_tipo = $db->fetchAll("
    SELECT 
        tc.nombre,
        COUNT(c.id) as total_citas,
        SUM(c.precio) as ingresos,
        AVG(c.precio) as precio_promedio
    FROM citas c
    INNER JOIN tipos_cita tc ON c.tipo_cita_id = tc.id
    WHERE c.fecha BETWEEN :desde AND :hasta
    AND c.estado_cita_id = 5
    GROUP BY tc.id, tc.nombre
    ORDER BY ingresos DESC
", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);

// ==========================================
// INGRESOS POR SEGURO
// ==========================================
$ingresos_seguro = $db->fetchAll("
    SELECT 
        COALESCE(s.nombre, 'Sin Seguro') as seguro,
        COUNT(c.id) as total_citas,
        SUM(c.precio) as ingresos
    FROM citas c
    INNER JOIN pacientes p ON c.paciente_id = p.id
    LEFT JOIN seguros s ON p.seguro_id = s.id
    WHERE c.fecha BETWEEN :desde AND :hasta
    AND c.estado_cita_id = 5
    GROUP BY s.nombre
    ORDER BY ingresos DESC
", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Financiero - Sistema de Clínica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @media print {
            .sidebar, .topbar, .no-print { display: none !important; }
            .main-content { margin-left: 0 !important; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar no-print">
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
        <div class="topbar no-print">
            <div>
                <h4>Reporte Financiero</h4>
                <small>Análisis de ingresos y facturación</small>
            </div>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Filtro -->
            <div class="card no-print">
                <div class="card-body">
                    <form method="GET" style="display: flex; gap: 15px; align-items: end;">
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
                    </form>
                </div>
            </div>

            <!-- Header -->
            <div style="text-align: center; margin: 30px 0;">
                <h2 style="margin: 0;">REPORTE FINANCIERO</h2>
                <p style="margin: 10px 0; color: var(--text-secondary);">
                    Período: <?php echo date('d/m/Y', strtotime($fecha_desde)); ?> - <?php echo date('d/m/Y', strtotime($fecha_hasta)); ?>
                </p>
            </div>

            <!-- Resumen Principal -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 20px;">
                        <div style="font-size: 32px; font-weight: bold; color: var(--success-color); margin-bottom: 5px;">
                            S/ <?php echo number_format($resumen['ingresos_totales'], 2); ?>
                        </div>
                        <small style="color: var(--text-secondary);">Ingresos Totales</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 20px;">
                        <div style="font-size: 32px; font-weight: bold; color: var(--primary-color); margin-bottom: 5px;">
                            <?php echo $resumen['citas_cobradas']; ?>
                        </div>
                        <small style="color: var(--text-secondary);">Citas Cobradas</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 20px;">
                        <div style="font-size: 32px; font-weight: bold; color: var(--warning-color); margin-bottom: 5px;">
                            S/ <?php echo number_format($resumen['precio_promedio'], 2); ?>
                        </div>
                        <small style="color: var(--text-secondary);">Precio Promedio</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 20px;">
                        <div style="font-size: 16px; margin-bottom: 5px;">
                            <strong>Min:</strong> S/ <?php echo number_format($resumen['precio_minimo'], 2); ?><br>
                            <strong>Max:</strong> S/ <?php echo number_format($resumen['precio_maximo'], 2); ?>
                        </div>
                        <small style="color: var(--text-secondary);">Rango de Precios</small>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Ingresos por Día -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line"></i> Ingresos Diarios</h5>
                </div>
                <div class="card-body">
                    <canvas id="chartIngresosDia" style="max-height: 300px;"></canvas>
                </div>
            </div>

            <!-- Ingresos por Especialidad -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header">
                    <h5><i class="fas fa-stethoscope"></i> Ingresos por Especialidad</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Especialidad</th>
                                <th>Total Citas</th>
                                <th>Ingresos</th>
                                <th>Precio Promedio</th>
                                <th>% del Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ingresos_especialidad as $esp): ?>
                            <tr>
                                <td>
                                    <span style="display: inline-block; width: 12px; height: 12px; background: <?php echo $esp['color']; ?>; border-radius: 50%; margin-right: 8px;"></span>
                                    <?php echo $esp['nombre']; ?>
                                </td>
                                <td><?php echo $esp['total_citas']; ?></td>
                                <td><strong style="color: var(--success-color);">S/ <?php echo number_format($esp['ingresos'], 2); ?></strong></td>
                                <td>S/ <?php echo number_format($esp['precio_promedio'], 2); ?></td>
                                <td>
                                    <?php 
                                    $porcentaje = ($esp['ingresos'] / $resumen['ingresos_totales']) * 100;
                                    echo number_format($porcentaje, 1); 
                                    ?>%
                                    <div style="width: 100%; height: 6px; background: #eee; border-radius: 3px; margin-top: 5px;">
                                        <div style="width: <?php echo $porcentaje; ?>%; height: 100%; background: <?php echo $esp['color']; ?>; border-radius: 3px;"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="font-weight: bold; background: #f9f9f9;">
                                <td>TOTAL</td>
                                <td><?php echo array_sum(array_column($ingresos_especialidad, 'total_citas')); ?></td>
                                <td style="color: var(--success-color);">S/ <?php echo number_format($resumen['ingresos_totales'], 2); ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Ingresos por Médico -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header">
                    <h5><i class="fas fa-user-md"></i> Ingresos por Médico</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Médico</th>
                                <th>Especialidad</th>
                                <th>Citas</th>
                                <th>Ingresos</th>
                                <th>Promedio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ingresos_medico as $medico): ?>
                            <tr>
                                <td><?php echo $medico['medico']; ?></td>
                                <td><small><?php echo $medico['especialidad']; ?></small></td>
                                <td><?php echo $medico['total_citas']; ?></td>
                                <td><strong style="color: var(--success-color);">S/ <?php echo number_format($medico['ingresos'], 2); ?></strong></td>
                                <td>S/ <?php echo number_format($medico['precio_promedio'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ingresos por Tipo de Cita y Seguro -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
                <!-- Por Tipo de Cita -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-clipboard-list"></i> Por Tipo de Cita</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Citas</th>
                                    <th>Ingresos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ingresos_tipo as $tipo): ?>
                                <tr>
                                    <td><?php echo $tipo['nombre']; ?></td>
                                    <td><?php echo $tipo['total_citas']; ?></td>
                                    <td><strong>S/ <?php echo number_format($tipo['ingresos'], 2); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Por Seguro -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-shield-alt"></i> Por Tipo de Seguro</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Seguro</th>
                                    <th>Citas</th>
                                    <th>Ingresos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ingresos_seguro as $seguro): ?>
                                <tr>
                                    <td><?php echo $seguro['seguro']; ?></td>
                                    <td><?php echo $seguro['total_citas']; ?></td>
                                    <td><strong>S/ <?php echo number_format($seguro['ingresos'], 2); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gráfico de Ingresos Diarios
        const ctxIngresos = document.getElementById('chartIngresosDia').getContext('2d');
        new Chart(ctxIngresos, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($item) { return '"' . date('d/m', strtotime($item['fecha'])) . '"'; }, $ingresos_dia)); ?>],
                datasets: [{
                    label: 'Ingresos (S/)',
                    data: [<?php echo implode(',', array_column($ingresos_dia, 'ingresos')); ?>],
                    backgroundColor: 'rgba(76, 175, 80, 0.7)',
                    borderColor: 'rgba(76, 175, 80, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'S/ ' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>