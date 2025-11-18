<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

// Filtros
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$medico_id = $_GET['medico_id'] ?? '';
$paciente_buscar = $_GET['paciente_buscar'] ?? '';

// Obtener médicos para filtro
$medicos = $db->fetchAll("SELECT id, nombres, apellidos FROM medicos WHERE estado = 'activo' ORDER BY nombres");

// Consulta de consultas médicas
$sql = "SELECT c.*,
               CONCAT(p.nombres, ' ', p.apellidos) as paciente_nombre,
               p.dni as paciente_dni,
               CONCAT(m.nombres, ' ', m.apellidos) as medico_nombre,
               e.nombre as especialidad,
               e.color as especialidad_color
        FROM consultas c
        INNER JOIN pacientes p ON c.paciente_id = p.id
        INNER JOIN medicos m ON c.medico_id = m.id
        INNER JOIN especialidades e ON m.especialidad_id = e.id
        WHERE DATE(c.fecha_consulta) BETWEEN :fecha_desde AND :fecha_hasta";

$params = [
    'fecha_desde' => $fecha_desde,
    'fecha_hasta' => $fecha_hasta
];

if ($medico_id) {
    $sql .= " AND c.medico_id = :medico_id";
    $params['medico_id'] = $medico_id;
}

if ($paciente_buscar) {
    $sql .= " AND (p.nombres LIKE :buscar OR p.apellidos LIKE :buscar OR p.dni LIKE :buscar)";
    $params['buscar'] = "%$paciente_buscar%";
}

$sql .= " ORDER BY c.fecha_consulta DESC LIMIT 100";

$consultas = $db->fetchAll($sql, $params);

// Estadísticas
$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total,
        COUNT(DISTINCT c.paciente_id) as pacientes_atendidos,
        COUNT(DISTINCT c.medico_id) as medicos_activos
    FROM consultas c
    WHERE DATE(c.fecha_consulta) BETWEEN :fecha_desde AND :fecha_hasta
", $params);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultas Médicas - Sistema de Clínica</title>
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
                <i class="fas fa-notes-medical"></i> Consultas
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
                <h4>Consultas Médicas</h4>
                <small>Registro de consultas realizadas</small>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title">
                    <h2>Consultas Médicas</h2>
                    <div class="breadcrumb">
                        <a href="../dashboard.php">Dashboard</a> / Consultas
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <h3 style="margin: 0; color: var(--primary-color);"><?php echo $stats['total']; ?></h3>
                        <small style="color: var(--text-secondary);">Total Consultas</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <h3 style="margin: 0; color: var(--success-color);"><?php echo $stats['pacientes_atendidos']; ?></h3>
                        <small style="color: var(--text-secondary);">Pacientes Atendidos</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <h3 style="margin: 0; color: var(--warning-color);"><?php echo $stats['medicos_activos']; ?></h3>
                        <small style="color: var(--text-secondary);">Médicos Activos</small>
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

                        <div>
                            <label class="form-label">Buscar Paciente</label>
                            <input type="text" class="form-control" name="paciente_buscar" value="<?php echo $paciente_buscar; ?>" placeholder="DNI, nombre...">
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

            <!-- Tabla de Consultas -->
            <div class="card">
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Paciente</th>
                                <th>Médico</th>
                                <th>Especialidad</th>
                                <th>Diagnóstico</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($consultas)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-notes-medical" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                                        <p style="color: var(--text-secondary);">No hay consultas registradas</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($consultas as $consulta): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('d/m/Y', strtotime($consulta['fecha_consulta'])); ?></strong><br>
                                        <small style="color: var(--text-secondary);"><?php echo date('H:i', strtotime($consulta['fecha_consulta'])); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo $consulta['paciente_nombre']; ?></strong><br>
                                        <small style="color: var(--text-secondary);">DNI: <?php echo $consulta['paciente_dni']; ?></small>
                                    </td>
                                    <td><?php echo $consulta['medico_nombre']; ?></td>
                                    <td>
                                        <span class="badge" style="background: <?php echo $consulta['especialidad_color']; ?>20; color: <?php echo $consulta['especialidad_color']; ?>;">
                                            <?php echo $consulta['especialidad']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo substr($consulta['diagnostico_principal'], 0, 50); ?>...</small>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="ver.php?id=<?php echo $consulta['id']; ?>" class="btn btn-sm btn-info" title="Ver Completa">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="imprimir_receta.php?id=<?php echo $consulta['id']; ?>" target="_blank" class="btn btn-sm btn-success" title="Receta">
                                                <i class="fas fa-prescription"></i>
                                            </a>
                                            <a href="historial.php?paciente_id=<?php echo $consulta['paciente_id']; ?>" class="btn btn-sm btn-primary" title="Historial">
                                                <i class="fas fa-history"></i>
                                            </a>
                                        </div>
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