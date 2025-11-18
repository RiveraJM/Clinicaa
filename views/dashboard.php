<?php
require_once '../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$user = getCurrentUser();
$db = getDB();

// Obtener estadísticas
$stats = [
    'pacientes' => $db->fetchOne("SELECT COUNT(*) as total FROM pacientes WHERE estado = 'activo'")['total'] ?? 0,
    'medicos' => $db->fetchOne("SELECT COUNT(*) as total FROM medicos WHERE estado = 'activo'")['total'] ?? 0,
    'citas_hoy' => $db->fetchOne("SELECT COUNT(*) as total FROM citas WHERE fecha = CURDATE()")['total'] ?? 0,
    'pendientes' => $db->fetchOne("SELECT COUNT(*) as total FROM citas WHERE estado_cita_id = 1 AND fecha >= CURDATE()")['total'] ?? 0
];

// Datos para gráficos - Citas de la última semana
$citas_semana = $db->fetchAll("
    SELECT 
        DATE(fecha) as fecha,
        COUNT(*) as total
    FROM citas
    WHERE fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND CURDATE()
    GROUP BY DATE(fecha)
    ORDER BY fecha
");

// Rellenar días faltantes con 0
$fechas_semana = [];
for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $encontrado = false;
    foreach ($citas_semana as $cita) {
        if ($cita['fecha'] == $fecha) {
            $fechas_semana[] = ['fecha' => $fecha, 'total' => $cita['total']];
            $encontrado = true;
            break;
        }
    }
    if (!$encontrado) {
        $fechas_semana[] = ['fecha' => $fecha, 'total' => 0];
    }
}

// Top 5 especialidades más demandadas (último mes)
$top_especialidades = $db->fetchAll("
    SELECT 
        e.nombre,
        e.color,
        COUNT(c.id) as total
    FROM citas c
    INNER JOIN especialidades e ON c.especialidad_id = e.id
    WHERE c.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY e.id, e.nombre, e.color
    ORDER BY total DESC
    LIMIT 5
");

// Citas de hoy
$citas_hoy = $db->fetchAll("
    SELECT c.*, 
           CONCAT(p.nombres, ' ', p.apellidos) as paciente,
           CONCAT(m.nombres, ' ', m.apellidos) as medico,
           e.nombre as especialidad,
           ec.nombre as estado,
           ec.color as estado_color
    FROM citas c
    INNER JOIN pacientes p ON c.paciente_id = p.id
    INNER JOIN medicos m ON c.medico_id = m.id
    INNER JOIN especialidades e ON c.especialidad_id = e.id
    INNER JOIN estados_cita ec ON c.estado_cita_id = ec.id
    WHERE c.fecha = CURDATE()
    ORDER BY c.hora
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Clínica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo">
                <i class="fas fa-hospital-symbol"></i> CLÍNICA
            </a>
            <div class="sidebar-user">
                <div class="avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                <div class="sidebar-user-name"><?php echo $user['username']; ?></div>
                <div class="sidebar-user-role"><?php echo ucfirst($user['rol']); ?></div>
            </div>
        </div>
        
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-menu-item active">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="pacientes/lista.php" class="sidebar-menu-item">
                <i class="fas fa-users"></i> Pacientes
            </a>
            <a href="medicos/lista.php" class="sidebar-menu-item">
                <i class="fas fa-user-md"></i> Médicos
            </a>
            <a href="especialidades/lista.php" class="sidebar-menu-item">
                <i class="fas fa-stethoscope"></i> Especialidades
            </a>
            <a href="citas/lista.php" class="sidebar-menu-item">
                <i class="fas fa-calendar-alt"></i> Citas
            </a>
            <a href="consultas/lista.php" class="sidebar-menu-item">
                <i class="fas fa-notes-medical"></i> Consultas
            </a>
            <a href="agenda/configurar.php" class="sidebar-menu-item">
                <i class="fas fa-calendar-check"></i> Configurar Agenda
            </a>
            <?php if ($user['rol'] === 'admin'): ?>
            <a href="reportes/index.php" class="sidebar-menu-item">
                <i class="fas fa-chart-bar"></i> Reportes
            </a>
            <?php endif; ?>
            <a href="../controllers/login_controller.php?logout=1" class="sidebar-menu-item" style="color: var(--danger-color); margin-top: 20px;">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Topbar -->
        <div class="topbar">
            <div>
                <h4>Dashboard</h4>
                <small>Bienvenido, <?php echo $user['username']; ?></small>
            </div>
            <div class="topbar-right">
                <div class="topbar-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="topbar-icon">
                    <i class="fas fa-user"></i>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <h2>Estadísticas Generales</h2>
            
            <!-- Tarjetas de Estadísticas -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                <!-- Pacientes -->
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="width: 60px; height: 60px; background: rgba(0,188,212,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: var(--primary-color); font-size: 24px;">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 style="margin: 0; color: var(--text-primary);"><?php echo $stats['pacientes']; ?></h3>
                        <p style="color: var(--text-secondary); margin: 5px 0 0 0;">Total Pacientes</p>
                    </div>
                </div>

                <!-- Médicos -->
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="width: 60px; height: 60px; background: rgba(76,175,80,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: var(--success-color); font-size: 24px;">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <h3 style="margin: 0; color: var(--text-primary);"><?php echo $stats['medicos']; ?></h3>
                        <p style="color: var(--text-secondary); margin: 5px 0 0 0;">Médicos Activos</p>
                    </div>
                </div>

                <!-- Citas Hoy -->
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="width: 60px; height: 60px; background: rgba(255,193,7,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: var(--warning-color); font-size: 24px;">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <h3 style="margin: 0; color: var(--text-primary);"><?php echo $stats['citas_hoy']; ?></h3>
                        <p style="color: var(--text-secondary); margin: 5px 0 0 0;">Citas Hoy</p>
                    </div>
                </div>

                <!-- Pendientes -->
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div style="width: 60px; height: 60px; background: rgba(244,67,54,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: var(--danger-color); font-size: 24px;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 style="margin: 0; color: var(--text-primary);"><?php echo $stats['pendientes']; ?></h3>
                        <p style="color: var(--text-secondary); margin: 5px 0 0 0;">Pendientes</p>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <h5><i class="fas fa-bolt"></i> Acciones Rápidas</h5>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <a href="pacientes/nuevo.php" class="btn btn-primary" style="display: block; text-align: center; text-decoration: none;">
                            <i class="fas fa-user-plus"></i> Nuevo Paciente
                        </a>
                        <a href="medicos/nuevo.php" class="btn btn-primary" style="display: block; text-align: center; text-decoration: none;">
                            <i class="fas fa-user-md"></i> Nuevo Médico
                        </a>
                        <a href="citas/nueva.php" class="btn btn-primary" style="display: block; text-align: center; text-decoration: none;">
                            <i class="fas fa-calendar-plus"></i> Agendar Cita
                        </a>
                        <a href="pacientes/lista.php" class="btn btn-primary" style="display: block; text-align: center; text-decoration: none;">
                            <i class="fas fa-list"></i> Ver Pacientes
                        </a>
                        <a href="citas/lista.php" class="btn btn-primary" style="display: block; text-align: center; text-decoration: none;">
                            <i class="fas fa-calendar-alt"></i> Ver Citas
                        </a>
                        <a href="agenda/configurar.php" class="btn btn-primary" style="display: block; text-align: center; text-decoration: none;">
                            <i class="fas fa-calendar-check"></i> Configurar Agenda
                        </a>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px; margin-top: 30px;">
                <!-- Gráfico: Citas de la Semana -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line"></i> Citas de la Semana</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="chartCitasSemana" style="max-height: 300px;"></canvas>
                    </div>
                </div>

                <!-- Gráfico: Especialidades Más Demandadas -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-pie"></i> Especialidades Más Demandadas</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_especialidades)): ?>
                            <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                <i class="fas fa-chart-pie" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                                <p>No hay datos de especialidades aún</p>
                            </div>
                        <?php else: ?>
                            <canvas id="chartEspecialidades" style="max-height: 300px;"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Citas de Hoy -->
            <?php if (!empty($citas_hoy)): ?>
            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <h5><i class="fas fa-calendar-day"></i> Citas de Hoy</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Paciente</th>
                                <th>Médico</th>
                                <th>Especialidad</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($citas_hoy as $cita): ?>
                            <tr>
                                <td><strong><?php echo date('H:i', strtotime($cita['hora'])); ?></strong></td>
                                <td><?php echo $cita['paciente']; ?></td>
                                <td><?php echo $cita['medico']; ?></td>
                                <td><?php echo $cita['especialidad']; ?></td>
                                <td>
                                    <span class="badge" style="background: <?php echo $cita['estado_color']; ?>; color: white;">
                                        <?php echo $cita['estado']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // ==========================================
        // GRÁFICO: Citas de la Semana
        // ==========================================
        const ctxSemana = document.getElementById('chartCitasSemana');
        if (ctxSemana) {
            new Chart(ctxSemana.getContext('2d'), {
                type: 'line',
                data: {
                    labels: [
                        <?php foreach ($fechas_semana as $item): ?>
                            '<?php echo date('d/m', strtotime($item['fecha'])); ?>',
                        <?php endforeach; ?>
                    ],
                    datasets: [{
                        label: 'Citas',
                        data: [
                            <?php foreach ($fechas_semana as $item): ?>
                                <?php echo $item['total']; ?>,
                            <?php endforeach; ?>
                        ],
                        borderColor: '#00BCD4',
                        backgroundColor: 'rgba(0,188,212,0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#00BCD4',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14
                            },
                            bodyFont: {
                                size: 13
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 12
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // ==========================================
        // GRÁFICO: Especialidades Más Demandadas
        // ==========================================
        const ctxEsp = document.getElementById('chartEspecialidades');
        <?php if (!empty($top_especialidades)): ?>
        if (ctxEsp) {
            new Chart(ctxEsp.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: [
                        <?php foreach ($top_especialidades as $esp): ?>
                            '<?php echo $esp['nombre']; ?>',
                        <?php endforeach; ?>
                    ],
                    datasets: [{
                        data: [
                            <?php foreach ($top_especialidades as $esp): ?>
                                <?php echo $esp['total']; ?>,
                            <?php endforeach; ?>
                        ],
                        backgroundColor: [
                            <?php foreach ($top_especialidades as $esp): ?>
                                '<?php echo $esp['color']; ?>',
                            <?php endforeach; ?>
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                },
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.parsed || 0;
                                    let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    let percentage = ((value / total) * 100).toFixed(1);
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>