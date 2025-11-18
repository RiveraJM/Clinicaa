<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

// Filtros
$fecha = $_GET['fecha'] ?? date('Y-m-d');
$medico_id = $_GET['medico_id'] ?? '';
$estado_id = $_GET['estado_id'] ?? '';

// Obtener datos para filtros
$medicos = $db->fetchAll("SELECT id, nombres, apellidos FROM medicos WHERE estado = 'activo' ORDER BY nombres");
$estados = $db->fetchAll("SELECT * FROM estados_cita");

// Consulta de citas
$sql = "SELECT c.*, 
               CONCAT(p.nombres, ' ', p.apellidos) as paciente_nombre,
               p.dni as paciente_dni,
               p.celular as paciente_celular,
               CONCAT(m.nombres, ' ', m.apellidos) as medico_nombre,
               e.nombre as especialidad,
               e.color as especialidad_color,
               tc.nombre as tipo_cita,
               ec.nombre as estado_cita,
               ec.color as estado_color
        FROM citas c
        INNER JOIN pacientes p ON c.paciente_id = p.id
        INNER JOIN medicos m ON c.medico_id = m.id
        INNER JOIN especialidades e ON c.especialidad_id = e.id
        INNER JOIN tipos_cita tc ON c.tipo_cita_id = tc.id
        INNER JOIN estados_cita ec ON c.estado_cita_id = ec.id
        WHERE 1=1";

$params = [];

if ($fecha) {
    $sql .= " AND c.fecha = :fecha";
    $params['fecha'] = $fecha;
}

if ($medico_id) {
    $sql .= " AND c.medico_id = :medico_id";
    $params['medico_id'] = $medico_id;
}

if ($estado_id) {
    $sql .= " AND c.estado_cita_id = :estado_id";
    $params['estado_id'] = $estado_id;
}

$sql .= " ORDER BY c.fecha DESC, c.hora ASC";

$citas = $db->fetchAll($sql, $params);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citas - Sistema de Clínica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
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
            <a href="lista.php" class="sidebar-menu-item active">
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
                <h4>Gestión de Citas</h4>
                <small>Citas médicas programadas</small>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title">
                    <h2>Citas Médicas</h2>
                    <div class="breadcrumb">
                        <a href="../dashboard.php">Dashboard</a> / Citas
                    </div>
                </div>
                <a href="nueva.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva Cita
                </a>
            </div>

            <!-- Filtros -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                        <div>
                            <label class="form-label">Fecha</label>
                            <input type="date" class="form-control" name="fecha" value="<?php echo $fecha; ?>">
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
                            <label class="form-label">Estado</label>
                            <select class="form-control" name="estado_id">
                                <option value="">Todos</option>
                                <?php foreach ($estados as $estado): ?>
                                    <option value="<?php echo $estado['id']; ?>" <?php echo $estado_id == $estado['id'] ? 'selected' : ''; ?>>
                                        <?php echo $estado['nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
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

            <!-- Resumen del día -->
            <?php
            $resumen = $db->fetchOne("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado_cita_id = 2 THEN 1 ELSE 0 END) as confirmadas,
                    SUM(CASE WHEN estado_cita_id = 5 THEN 1 ELSE 0 END) as atendidas,
                    SUM(CASE WHEN estado_cita_id = 1 THEN 1 ELSE 0 END) as pendientes
                FROM citas
                WHERE fecha = :fecha
            ", ['fecha' => $fecha]);
            ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <h3 style="margin: 0; color: var(--primary-color);"><?php echo $resumen['total']; ?></h3>
                        <small style="color: var(--text-secondary);">Total Citas</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <h3 style="margin: 0; color: var(--primary-color);"><?php echo $resumen['confirmadas']; ?></h3>
                        <small style="color: var(--text-secondary);">Confirmadas</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <h3 style="margin: 0; color: var(--success-color);"><?php echo $resumen['atendidas']; ?></h3>
                        <small style="color: var(--text-secondary);">Atendidas</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 15px;">
                        <h3 style="margin: 0; color: var(--warning-color);"><?php echo $resumen['pendientes']; ?></h3>
                        <small style="color: var(--text-secondary);">Pendientes</small>
                    </div>
                </div>
            </div>

            <!-- Tabla de Citas -->
            <div class="card">
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Paciente</th>
                                <th>Médico</th>
                                <th>Especialidad</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($citas)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-calendar-times" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                                        <p style="color: var(--text-secondary);">No hay citas para los filtros seleccionados</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($citas as $cita): ?>
                                <tr>
                                    <td>
                                        <strong style="font-size: 16px;"><?php echo date('H:i', strtotime($cita['hora'])); ?></strong><br>
                                        <small style="color: var(--text-secondary);"><?php echo formatFecha($cita['fecha']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo $cita['paciente_nombre']; ?></strong><br>
                                        <small style="color: var(--text-secondary);">
                                            <i class="fas fa-id-card"></i> <?php echo $cita['paciente_dni']; ?>
                                        </small>
                                    </td>
                                    <td><?php echo $cita['medico_nombre']; ?></td>
                                    <td>
                                        <span class="badge" style="background: <?php echo $cita['especialidad_color']; ?>20; color: <?php echo $cita['especialidad_color']; ?>;">
                                            <?php echo $cita['especialidad']; ?>
                                        </span>
                                   <td>
                                        <div class="btn-group">
                                            <a href="ver.php?id=<?php echo $cita['id']; ?>" class="btn btn-sm btn-info" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($cita['estado_cita_id'] == 2 || $cita['estado_cita_id'] == 4): // Confirmada o En atención ?>
                                                <a href="../consultas/registrar.php?cita_id=<?php echo $cita['id']; ?>" class="btn btn-sm btn-success" title="Registrar Consulta">
                                                    <i class="fas fa-notes-medical"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($cita['estado_cita_id'] <= 2): ?>
                                                <a href="reprogramar.php?id=<?php echo $cita['id']; ?>" class="btn btn-sm btn-warning" title="Reprogramar">
                                                    <i class="fas fa-calendar"></i>
                                                </a>
                                                <a href="javascript:void(0);" onclick="cancelar(<?php echo $cita['id']; ?>)" class="btn btn-sm btn-danger" title="Cancelar">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
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

    <script>
        function cancelar(id) {
            if (confirm('¿Está seguro de cancelar esta cita?')) {
                window.location.href = '../../controllers/cita_controller.php?action=cancelar&id=' + id;
            }
        }
    </script>
</body>
</html>