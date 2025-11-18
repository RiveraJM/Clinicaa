<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

// Filtros
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$especialidad_id = isset($_GET['especialidad_id']) ? $_GET['especialidad_id'] : '';

// Obtener especialidades para el filtro
$especialidades = $db->fetchAll("SELECT * FROM especialidades WHERE estado = 'activo' ORDER BY nombre");

// Consulta de médicos
$sql = "SELECT m.*, 
               e.nombre as especialidad_nombre,
               e.color as especialidad_color,
               u.username,
               u.email as usuario_email
        FROM medicos m
        INNER JOIN especialidades e ON m.especialidad_id = e.id
        INNER JOIN usuarios u ON m.usuario_id = u.id
        WHERE m.estado = 'activo'";

$params = [];

if ($search) {
    $sql .= " AND (m.dni LIKE :search OR m.nombres LIKE :search OR m.apellidos LIKE :search OR m.nro_colegiatura LIKE :search)";
    $params['search'] = "%$search%";
}

if ($especialidad_id) {
    $sql .= " AND m.especialidad_id = :especialidad_id";
    $params['especialidad_id'] = $especialidad_id;
}

$sql .= " ORDER BY m.fecha_registro DESC";

$medicos = $db->fetchAll($sql, $params);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Médicos - Sistema de Clínica</title>
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
            <a href="../citas/lista.php" class="sidebar-menu-item">
                <i class="fas fa-calendar-alt"></i> Citas
            </a>
            <a href="configurar.php" class="sidebar-menu-item active">
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
                <h4>Gestión de Médicos</h4>
                <small>Personal médico registrado</small>
            </div>
            <div class="topbar-right">
                <div class="topbar-icon">
                    <i class="fas fa-bell"></i>
                </div>
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
                    <h2>Médicos</h2>
                    <div class="breadcrumb">
                        <a href="../dashboard.php">Dashboard</a> / Médicos
                    </div>
                </div>
                <a href="nuevo.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Médico
                </a>
            </div>

            <!-- Filtros -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" style="display: grid; grid-template-columns: 1fr auto auto auto; gap: 15px; align-items: end;">
                        <div>
                            <label class="form-label">Buscar</label>
                            <div class="search-box">
                                <input type="text" name="search" placeholder="DNI, nombres, apellidos, CMP..." value="<?php echo $search; ?>">
                                <i class="fas fa-search"></i>
                            </div>
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

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>

                        <?php if ($search || $especialidad_id): ?>
                            <a href="lista.php" class="btn" style="background: #6c757d; color: white;">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Tabla de Médicos -->
            <div class="card">
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>CMP</th>
                                <th>Médico</th>
                                <th>Especialidad</th>
                                <th>Consultorio</th>
                                <th>Contacto</th>
                                <th>Tarifa</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($medicos)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-user-md" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                                        <p style="color: var(--text-secondary);">No hay médicos registrados</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($medicos as $medico): ?>
                                <tr>
                                    <td><strong><?php echo $medico['nro_colegiatura']; ?></strong></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="avatar" style="width: 35px; height: 35px; font-size: 14px;">
                                                <?php echo strtoupper(substr($medico['nombres'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo $medico['nombres'] . ' ' . $medico['apellidos']; ?></strong><br>
                                                <small style="color: var(--text-secondary);">DNI: <?php echo $medico['dni']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: <?php echo $medico['especialidad_color']; ?>20; color: <?php echo $medico['especialidad_color']; ?>;">
                                            <?php echo $medico['especialidad_nombre']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $medico['consultorio'] ?? '-'; ?></td>
                                    <td>
                                        <?php if ($medico['celular']): ?>
                                            <i class="fas fa-phone"></i> <?php echo $medico['celular']; ?><br>
                                        <?php endif; ?>
                                        <?php if ($medico['email']): ?>
                                            <small style="color: var(--text-secondary);">
                                                <i class="fas fa-envelope"></i> <?php echo $medico['email']; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong style="color: var(--primary-color);">
                                            S/ <?php echo number_format($medico['tarifa_consulta'], 2); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="ver.php?id=<?php echo $medico['id']; ?>" class="btn btn-sm btn-info" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="editar.php?id=<?php echo $medico['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0);" onclick="eliminar(<?php echo $medico['id']; ?>)" class="btn btn-sm btn-danger" title="Eliminar">
                                                <i class="fas fa-trash"></i>
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

    <script>
        function eliminar(id) {
            if (confirm('¿Está seguro de eliminar este médico?')) {
                window.location.href = '../../controllers/medico_controller.php?action=delete&id=' + id;
            }
        }
    </script>
</body>
</html>