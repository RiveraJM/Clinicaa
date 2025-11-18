<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$medico_id = $_GET['medico_id'] ?? '';
$especialidad_id = $_GET['especialidad_id'] ?? '';

// Obtener filtros
$medicos = $db->fetchAll("SELECT id, nombres, apellidos FROM medicos WHERE estado = 'activo' ORDER BY nombres");
$especialidades = $db->fetchAll("SELECT id, nombre FROM especialidades WHERE estado = 'activo' ORDER BY nombre");

// Consulta de citas
$sql = "SELECT 
            DATE(c.fecha) as fecha,
            e.nombre as especialidad,
            CONCAT(m.nombres, ' ', m.apellidos) as medico,
            COUNT(*) as total_citas,
            SUM(CASE WHEN c.estado_cita_id = 5 THEN 1 ELSE 0 END) as atendidas,
            SUM(CASE WHEN c.estado_cita_id = 7 THEN 1 ELSE 0 END) as canceladas,
            SUM(CASE WHEN c.estado_cita_id IN (1,2) THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN c.estado_cita_id = 5 THEN c.precio ELSE 0 END) as ingresos
        FROM citas c
        INNER JOIN medicos m ON c.medico_id = m.id
        INNER JOIN especialidades e ON c.especialidad_id = e.id
        WHERE c.fecha BETWEEN :desde AND :hasta";

$params = ['desde' => $fecha_desde, 'hasta' => $fecha_hasta];

if ($medico_id) {
    $sql .= " AND c.medico_id = :medico_id";
    $params['medico_id'] = $medico_id;
}

if ($especialidad_id) {
    $sql .= " AND c.especialidad_id = :especialidad_id";
    $params['especialidad_id'] = $especialidad_id;
}

$sql .= " GROUP BY DATE(c.fecha), e.nombre, medico ORDER BY fecha DESC, especialidad";

$reporte = $db->fetchAll($sql, $params);

// Totales
$totales = [
    'citas' => array_sum(array_column($reporte, 'total_citas')),
    'atendidas' => array_sum(array_column($reporte, 'atendidas')),
    'canceladas' => array_sum(array_column($reporte, 'canceladas')),
    'pendientes' => array_sum(array_column($reporte, 'pendientes')),
    'ingresos' => array_sum(array_column($reporte, 'ingresos'))
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Citas - Sistema de Clínica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
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
                <h4>Reporte de Citas</h4>
                <small>Análisis detallado de citas médicas</small>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="exportarExcel()" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </button>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Filtros -->
            <div class="card no-print">
                <div class="card-body">
                    <form method="GET">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                            <div>
                                <label class="form-label">Desde</label>
                                <input type="date" class="form-control" name="fecha_desde" value="<?php echo $fecha_desde; ?>" required>
                            </div>
                            <div>
                                <label class="form-label">Hasta</label>
                                <input type="date" class="form-control" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>" required>
                            </div>
                            <div>
                                <label class="form-label">Especialidad</label>
                                <select class="form-control" name="especialidad_id">
                                    <option value="">Todas</option>
                                    <?php foreach ($especialidades as $esp): ?>
                                        <option value="<?php echo $esp['id']; ?>" <?php echo $especialidad_id == $esp['id'] ? 'selected' : ''; ?>>
                                            <?php echo $esp['nombre']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Médico</label>
                                <select class="form-control" name="medico_id">
                                    <option value="">Todos</option>
                                    <?php foreach ($medicos as $medico): ?>
                                        <option value="<?php echo $medico['id']; ?>" <?php echo $medico_id == $medico['id'] ? 'selected' : ''; ?>>
                                            <?php echo $medico['nombres'] . ' ' . $medico['apellidos']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync"></i> Generar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Header del Reporte -->
            <div style="text-align: center; margin: 30px 0;">
                <h2 style="margin: 0;">REPORTE DE CITAS MÉDICAS</h2>
                <p style="margin: 10px 0; color: var(--text-secondary);">
                    Período: <?php echo date('d/m/Y', strtotime($fecha_desde)); ?> - <?php echo date('d/m/Y', strtotime($fecha_hasta)); ?>
                </p>
            </div>

            <!-- Resumen -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px;">
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <h3 style="margin: 0; color: var(--primary-color);"><?php echo $totales['citas']; ?></h3>
                        <small style="color: var(--text-secondary);">Total Citas</small>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <h3 style="margin: 0; color: var(--success-color);"><?php echo $totales['atendidas']; ?></h3>
                        <small style="color: var(--text-secondary);">Atendidas</small>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <h3 style="margin: 0; color: var(--danger-color);"><?php echo $totales['canceladas']; ?></h3>
                        <small style="color: var(--text-secondary);">Canceladas</small>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <h3 style="margin: 0; color: var(--warning-color);">S/ <?php echo number_format($totales['ingresos'], 2); ?></h3>
                        <small style="color: var(--text-secondary);">Ingresos</small>
                    </div>
                </div>
            </div>

            <!-- Tabla Detallada -->
            <div class="card">
                <div class="card-body">
                    <table class="table" id="tablaCitas">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Especialidad</th>
                                <th>Médico</th>
                                <th>Total</th>
                                <th>Atendidas</th>
                                <th>Canceladas</th>
                                <th>Pendientes</th>
                                <th>Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reporte as $fila): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($fila['fecha'])); ?></td>
                                <td><?php echo $fila['especialidad']; ?></td>
                                <td><?php echo $fila['medico']; ?></td>
                                <td><strong><?php echo $fila['total_citas']; ?></strong></td>
                                <td style="color: var(--success-color);"><?php echo $fila['atendidas']; ?></td>
                                <td style="color: var(--danger-color);"><?php echo $fila['canceladas']; ?></td>
                                <td style="color: var(--warning-color);"><?php echo $fila['pendientes']; ?></td>
                                <td><strong>S/ <?php echo number_format($fila['ingresos'], 2); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="font-weight: bold; background: #f9f9f9;">
                                <td colspan="3">TOTALES</td>
                                <td><?php echo $totales['citas']; ?></td>
                                <td style="color: var(--success-color);"><?php echo $totales['atendidas']; ?></td>
                                <td style="color: var(--danger-color);"><?php echo $totales['canceladas']; ?></td>
                                <td style="color: var(--warning-color);"><?php echo $totales['pendientes']; ?></td>
                                <td>S/ <?php echo number_format($totales['ingresos'], 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function exportarExcel() {
            // Función simple para exportar a Excel (CSV)
            let csv = 'Fecha,Especialidad,Médico,Total,Atendidas,Canceladas,Pendientes,Ingresos\n';
            
            const tabla = document.getElementById('tablaCitas');
            const filas = tabla.querySelectorAll('tbody tr');
            
            filas.forEach(fila => {
                const celdas = fila.querySelectorAll('td');
                let linea = Array.from(celdas).map(celda => celda.textContent.trim()).join(',');
                csv += linea + '\n';
            });
            
            // Descargar
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'reporte_citas_<?php echo date('Ymd'); ?>.csv';
            link.click();
        }
    </script>
</body>
</html>