<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

$paciente_id = $_GET['id'] ?? 0;

// Obtener datos del paciente
$paciente = $db->fetchOne("SELECT * FROM pacientes WHERE id = :id", ['id' => $paciente_id]);

if (!$paciente) {
    $_SESSION['error'] = 'Paciente no encontrado';
    redirect('lista.php');
}

// Obtener seguros
$seguros = $db->fetchAll("SELECT * FROM seguros WHERE estado = 'activo' ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Paciente - Sistema de Clínica</title>
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
                <i class="fas fa-users"></i> Pacientes
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
                <h4>Editar Paciente</h4>
                <small>Actualizar información del paciente</small>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title">
                    <h2>Editar Paciente</h2>
                    <div class="breadcrumb">
                        <a href="../dashboard.php">Dashboard</a> / 
                        <a href="lista.php">Pacientes</a> / 
                        Editar
                    </div>
                </div>
            </div>

            <!-- Formulario -->
            <div class="card">
                <div class="card-body">
                    <form action="../../controllers/paciente_controller.php" method="POST">
                        <input type="hidden" name="action" value="actualizar">
                        <input type="hidden" name="id" value="<?php echo $paciente['id']; ?>">

                        <!-- Sección: Datos Personales -->
                        <div style="margin-bottom: 30px;">
                            <h5 style="color: var(--primary-color); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--primary-color);">
                                <i class="fas fa-user"></i> Datos Personales
                            </h5>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div>
                                    <label class="form-label">DNI <span style="color: red;">*</span></label>
                                    <input type="text" class="form-control" name="dni" value="<?php echo $paciente['dni']; ?>" required maxlength="8" pattern="\d{8}">
                                </div>

                                <div>
                                    <label class="form-label">Nombres <span style="color: red;">*</span></label>
                                    <input type="text" class="form-control" name="nombres" value="<?php echo $paciente['nombres']; ?>" required>
                                </div>

                                <div>
                                    <label class="form-label">Apellidos <span style="color: red;">*</span></label>
                                    <input type="text" class="form-control" name="apellidos" value="<?php echo $paciente['apellidos']; ?>" required>
                                </div>

                                <div>
                                    <label class="form-label">Fecha de Nacimiento <span style="color: red;">*</span></label>
                                    <input type="date" class="form-control" name="fecha_nacimiento" value="<?php echo $paciente['fecha_nacimiento']; ?>" required>
                                </div>

                                <div>
                                    <label class="form-label">Sexo <span style="color: red;">*</span></label>
                                    <select class="form-control" name="sexo" required>
                                        <option value="M" <?php echo $paciente['sexo'] == 'M' ? 'selected' : ''; ?>>Masculino</option>
                                        <option value="F" <?php echo $paciente['sexo'] == 'F' ? 'selected' : ''; ?>>Femenino</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label">Estado Civil</label>
                                    <select class="form-control" name="estado_civil">
                                        <option value="">Seleccionar</option>
                                        <option value="Soltero(a)" <?php echo $paciente['estado_civil'] == 'Soltero(a)' ? 'selected' : ''; ?>>Soltero(a)</option>
                                        <option value="Casado(a)" <?php echo $paciente['estado_civil'] == 'Casado(a)' ? 'selected' : ''; ?>>Casado(a)</option>
                                        <option value="Divorciado(a)" <?php echo $paciente['estado_civil'] == 'Divorciado(a)' ? 'selected' : ''; ?>>Divorciado(a)</option>
                                        <option value="Viudo(a)" <?php echo $paciente['estado_civil'] == 'Viudo(a)' ? 'selected' : ''; ?>>Viudo(a)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Información de Contacto -->
                        <div style="margin-bottom: 30px;">
                            <h5 style="color: var(--success-color); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--success-color);">
                                <i class="fas fa-phone"></i> Información de Contacto
                            </h5>
                            
                            <div style="display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px;">
                                <div>
                                    <label class="form-label">Dirección</label>
                                    <input type="text" class="form-control" name="direccion" value="<?php echo $paciente['direccion']; ?>">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div>
                                    <label class="form-label">Celular <span style="color: red;">*</span></label>
                                    <input type="text" class="form-control" name="celular" value="<?php echo $paciente['celular']; ?>" required maxlength="9" pattern="\d{9}">
                                </div>

                                <div>
                                    <label class="form-label">Teléfono Fijo</label>
                                    <input type="text" class="form-control" name="telefono" value="<?php echo $paciente['telefono']; ?>">
                                </div>

                                <div>
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo $paciente['email']; ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Información Médica -->
                        <div style="margin-bottom: 30px;">
                            <h5 style="color: var(--danger-color); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--danger-color);">
                                <i class="fas fa-heartbeat"></i> Información Médica
                            </h5>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <div>
                                    <label class="form-label">Grupo Sanguíneo</label>
                                    <select class="form-control" name="grupo_sanguineo">
                                        <option value="">Seleccionar</option>
                                        <option value="A+" <?php echo $paciente['grupo_sanguineo'] == 'A+' ? 'selected' : ''; ?>>A+</option>
                                        <option value="A-" <?php echo $paciente['grupo_sanguineo'] == 'A-' ? 'selected' : ''; ?>>A-</option>
                                        <option value="B+" <?php echo $paciente['grupo_sanguineo'] == 'B+' ? 'selected' : ''; ?>>B+</option>
                                        <option value="B-" <?php echo $paciente['grupo_sanguineo'] == 'B-' ? 'selected' : ''; ?>>B-</option>
                                        <option value="AB+" <?php echo $paciente['grupo_sanguineo'] == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                        <option value="AB-" <?php echo $paciente['grupo_sanguineo'] == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                        <option value="O+" <?php echo $paciente['grupo_sanguineo'] == 'O+' ? 'selected' : ''; ?>>O+</option>
                                        <option value="O-" <?php echo $paciente['grupo_sanguineo'] == 'O-' ? 'selected' : ''; ?>>O-</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="form-label">Seguro Médico</label>
                                    <select class="form-control" name="seguro_id">
                                        <option value="">Sin seguro</option>
                                        <?php foreach ($seguros as $seguro): ?>
                                            <option value="<?php echo $seguro['id']; ?>" <?php echo $paciente['seguro_id'] == $seguro['id'] ? 'selected' : ''; ?>>
                                                <?php echo $seguro['nombre']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div style="grid-column: 1 / -1;">
                                    <label class="form-label">Alergias</label>
                                    <textarea class="form-control" name="alergias" rows="2" placeholder="Ingrese alergias conocidas del paciente"><?php echo $paciente['alergias']; ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                            <a href="lista.php" class="btn" style="background: #6c757d; color: white;">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Paciente
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>