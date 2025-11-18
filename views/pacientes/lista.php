<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

// Búsqueda
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$sql = "SELECT p.*, s.nombre as seguro_nombre 
        FROM pacientes p 
        LEFT JOIN seguros s ON p.seguro_id = s.id 
        WHERE p.estado = 'activo'";

$params = [];

if ($search) {
    $sql .= " AND (p.dni LIKE :search OR p.nombres LIKE :search OR p.apellidos LIKE :search)";
    $params['search'] = "%$search%";
}

$sql .= " ORDER BY p.fecha_registro DESC";

$pacientes = $db->fetchAll($sql, $params);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacientes - Sistema de Clínica</title>
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
                <h4>Gestión de Pacientes</h4>
                <small>Lista de pacientes registrados</small>
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

            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title">
                    <h2>Pacientes</h2>
                    <div class="breadcrumb">
                        <a href="../dashboard.php">Dashboard</a> / Pacientes
                    </div>
                </div>
                <a href="nuevo.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Paciente
                </a>
            </div>

            <!-- Search and Filters -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" style="display: flex; gap: 15px; align-items: center;">
                        <div class="search-box">
                            <input type="text" name="search" placeholder="Buscar por DNI, nombres o apellidos..." value="<?php echo $search; ?>">
                            <i class="fas fa-search"></i>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Buscar
                        </button>
                        <?php if ($search): ?>
                            <a href="lista.php" class="btn" style="background: #6c757d; color: white;">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Tabla de Pacientes -->
            <div class="card">
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>DNI</th>
                                <th>Paciente</th>
                                <th>Edad</th>
                                <th>Sexo</th>
                                <th>Teléfono</th>
                                <th>Seguro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pacientes)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-users" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                                        <p style="color: var(--text-secondary);">No hay pacientes registrados</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pacientes as $paciente): ?>
                                <tr>
                                    <td><strong><?php echo $paciente['dni']; ?></strong></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="avatar" style="width: 35px; height: 35px; font-size: 14px;">
                                                <?php echo strtoupper(substr($paciente['nombres'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo $paciente['nombres'] . ' ' . $paciente['apellidos']; ?></strong><br>
                                                <small style="color: var(--text-secondary);"><?php echo $paciente['email'] ?? 'Sin email'; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo calcularEdad($paciente['fecha_nacimiento']); ?> años</td>
                                    <td>
                                        <span class="badge <?php echo $paciente['sexo'] == 'M' ? 'badge-primary' : 'badge-pink'; ?>">
                                            <?php echo $paciente['sexo'] == 'M' ? 'Masculino' : 'Femenino'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $paciente['celular'] ?? $paciente['telefono'] ?? '-'; ?></td>
                                    <td>
                                        <?php if ($paciente['tiene_seguro']): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-shield-alt"></i> <?php echo $paciente['seguro_nombre']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #e0e0e0; color: #666;">Sin seguro</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="ver.php?id=<?php echo $paciente['id']; ?>" class="btn btn-sm btn-info" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="editar.php?id=<?php echo $paciente['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0);" onclick="eliminar(<?php echo $paciente['id']; ?>)" class="btn btn-sm btn-danger" title="Eliminar">
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
            if (confirm('¿Está seguro de eliminar este paciente?')) {
                window.location.href = '../../controllers/paciente_controller.php?action=delete&id=' + id;
            }
        }
    </script>
</body>
</html>