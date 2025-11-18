<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

$medico_id = $_GET['id'] ?? 0;

// Obtener datos del médico
$medico = $db->fetchOne("SELECT * FROM medicos WHERE id = :id", ['id' => $medico_id]);

if (!$medico) {
    $_SESSION['error'] = 'Médico no encontrado';
    redirect('lista.php');
}

// Obtener especialidades
$especialidades = $db->fetchAll("SELECT * FROM especialidades WHERE estado = 'activo' ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Médico - Sistema de Clínica</title>
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
            </div>
        </div>
        
        <nav class="sidebar-menu">
            <a href="../dashboard.php" class="sidebar-menu-item">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="lista.php" class="sidebar-menu-item active">
                <i class="fas fa-user-md"></i> Médicos
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
                <h4>Editar Médico</h4>
                <small>Actualizar información del médico</small>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title">
                    <h2>Editar Médico</h2>
                    <div class="breadcrumb">
                        <a href="../dashboard.php">Dashboard</a> / 
                        <a href="lista.php">Médicos</a> / 
                        Editar
                    </div>
                </div>
            </div>

            <!-- Formulario -->
            <div class="card">
                <div class="card-body">
                    <form action="../../controllers/medico_controller.php" method="POST">
                        <input type="hidden" name="action" value="actualizar">
                        <input type="hidden" name="id" value="<?php echo $medico['id']; ?>">

                        <!-- Sección: Datos Personales -->
                        <div style="margin-bottom: 30px;">
                            <h5 style="color: var(--primary-color); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--primary-color);">
                                <i class="fas fa-user"></i> Datos Personales
                            </h5>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div>
                                    <label class="form-label">DNI <span style="color: red;">*</span></label>
                                    <input type="text" class="form-control" name="dni" value="<?php echo $medico['dni']; ?>" required maxlength="8" pattern="\d{8}">
                                </div>

                                <div>
                                    <label class="form-label">Nombres <span style="color: red;">*</span></label>
                                    <input type="text" class="form-control" name="nombres" value="<?php echo $medico['nombres']; ?>" required>
                                </div>

                                <div>
                                    <label class="form-label">Apellidos <span style="color: red;">*</span></label>
                                    <input type="text" class="form-control" name="apellidos" value="<?php echo $medico['apellidos']; ?>" required>
                                </div>

                                <div>
                                    <label class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control" name="fecha_nacimiento" value="<?php echo $medico['fecha_nacimiento']; ?>">
                                </div>

                                <div>
                                    <label class="form-label">Sexo</label>
                                    <select class="form-control" name="sexo">
                                        <option value="">Seleccionar</option>
                                        <option value="M" <?php echo $medico['sexo'] == 'M' ? 'selected' : ''; ?>>Masculino</option>
                                        <option value="F" <?php echo $medico['sexo'] == 'F' ? 'selected' : ''; ?>>Femenino</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Información Profesional -->
                        <div style="margin-bottom: 30px;">
                            <h5 style="color: var(--success-color); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--success-color);">
                                <i class="fas fa-user-md"></i> Información Profesional
                            </h5>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div>
                                    <label class="form-label">Nro. Colegiatura (CMP) <span style="color: red;">*</span></label>
                                    <input type="text" class="form-control" name="nro_colegiatura" value="<?php echo $medico['nro_colegiatura']; ?>" required>
                                </div>

                                <div>
                                    <label class="form-label">RNE (Registro Nacional de Especialista)</label>
                                    <input type="text" class="form-control" name="rne" value="<?php echo $medico['rne']; ?>">
                                </div>

                                <div>
                                    <label class="form-label">Especialidad <span style="color: red;">*</span></label>
                                    <select class="form-control" name="especialidad_id" required>
                                        <option value="">Seleccionar especialidad</option>
                                        <?php foreach ($especialidades as $esp): ?>
                                            <option value="<?php echo $esp['id']; ?>" <?php echo $medico['especialidad_id'] == $esp['id'] ? 'selected' : ''; ?>>
                                                <?php echo $esp['nombre']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label">Estado</label>
                                    <select class="form-control" name="estado">
                                        <option value="activo" <?php echo $medico['estado'] == 'activo' ? 'selected' : ''; ?>>Activo</option>
                                        <option value="inactivo" <?php echo $medico['estado'] == 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Información de Contacto -->
                        <div style="margin-bottom: 30px;">
                            <h5 style="color: var(--warning-color); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--warning-color);">
                                <i class="fas fa-phone"></i> Información de Contacto
                            </h5>
                            
                            <div style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px;">
                                <div>
                                    <label class="form-label">Dirección</label>
                                    <input type="text" class="form-control" name="direccion" value="<?php echo $medico['direccion']; ?>">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div>
                                    <label class="form-label">Celular <span style="color: red;">*</span></label>
                                    <input type="text" class="form-control" name="celular" value="<?php echo $medico['celular']; ?>" required maxlength="9" pattern="\d{9}">
                                </div>

                                <div>
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" name="telefono" value="<?php echo $medico['telefono']; ?>">
                                </div>

                                <div>
                                    <label class="form-label">Email <span style="color: red;">*</span></label>
                                    <input type="email" class="form-control" name="email" value="<?php echo $medico['email']; ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                            <a href="lista.php" class="btn" style="background: #6c757d; color: white;">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Médico
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>