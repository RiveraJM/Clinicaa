<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

// Obtener especialidades activas
$especialidades = $db->fetchAll("SELECT * FROM especialidades WHERE estado = 'activo' ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Médico - Sistema de Clínica</title>
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
                <h4>Nuevo Médico</h4>
                <small>Registrar nuevo médico en el sistema</small>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title">
                    <h2>Registrar Médico</h2>
                    <div class="breadcrumb">
                        <a href="../dashboard.php">Dashboard</a> / 
                        <a href="lista.php">Médicos</a> / Nuevo
                    </div>
                </div>
            </div>

            <form action="../../controllers/medico_controller.php" method="POST" id="formMedico">
                <input type="hidden" name="action" value="create">

                <!-- Datos Personales -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-user-md"></i> Datos Personales
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label">DNI <span style="color: var(--danger-color);">*</span></label>
                            <input type="text" class="form-control" name="dni" id="dni" maxlength="8" required>
                        </div>

                        <div>
                            <label class="form-label">Nombres <span style="color: var(--danger-color);">*</span></label>
                            <input type="text" class="form-control" name="nombres" required>
                        </div>

                        <div>
                            <label class="form-label">Apellidos <span style="color: var(--danger-color);">*</span></label>
                            <input type="text" class="form-control" name="apellidos" required>
                        </div>
                    </div>
                </div>

                <!-- Datos Profesionales -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-id-card"></i> Datos Profesionales
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label">Especialidad <span style="color: var(--danger-color);">*</span></label>
                            <select class="form-control" name="especialidad_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($especialidades as $esp): ?>
                                    <option value="<?php echo $esp['id']; ?>"><?php echo $esp['nombre']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Nro. Colegiatura (CMP) <span style="color: var(--danger-color);">*</span></label>
                            <input type="text" class="form-control" name="nro_colegiatura" placeholder="123456" required>
                        </div>

                        <div>
                            <label class="form-label">RNE</label>
                            <input type="text" class="form-control" name="rne" placeholder="Opcional">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label">Consultorio</label>
                            <input type="text" class="form-control" name="consultorio" placeholder="Ej: Consultorio 101">
                        </div>

                        <div>
                            <label class="form-label">Tarifa de Consulta (S/)</label>
                            <input type="number" class="form-control" name="tarifa_consulta" step="0.01" value="0.00">
                        </div>

                        <div>
                            <label class="form-label">Duración Consulta (minutos)</label>
                            <input type="number" class="form-control" name="duracion_consulta" value="30">
                        </div>
                    </div>
                </div>

                <!-- Datos de Contacto -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-phone"></i> Datos de Contacto
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" name="telefono" placeholder="01-1234567">
                        </div>

                        <div>
                            <label class="form-label">Celular <span style="color: var(--danger-color);">*</span></label>
                            <input type="tel" class="form-control" name="celular" placeholder="999999999" required>
                        </div>

                        <div>
                            <label class="form-label">Email <span style="color: var(--danger-color);">*</span></label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                    </div>
                </div>

                <!-- Usuario de Acceso -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-key"></i> Usuario de Acceso al Sistema
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label">Usuario <span style="color: var(--danger-color);">*</span></label>
                            <input type="text" class="form-control" name="username" required>
                            <small style="color: var(--text-secondary);">Para acceder al sistema</small>
                        </div>

                        <div>
                            <label class="form-label">Contraseña <span style="color: var(--danger-color);">*</span></label>
                            <input type="password" class="form-control" name="password" required>
                            <small style="color: var(--text-secondary);">Mínimo 6 caracteres</small>
                        </div>

                        <div>
                            <label class="form-label">Confirmar Contraseña <span style="color: var(--danger-color);">*</span></label>
                            <input type="password" class="form-control" name="password_confirm" required>
                        </div>
                    </div>
                </div>

                <!-- Biografía -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i> Información Adicional
                    </div>
                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Biografía / Experiencia</label>
                            <textarea class="form-control" name="biografia" rows="3" placeholder="Breve descripción profesional..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="lista.php" class="btn" style="background: #6c757d; color: white;">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Médico
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Validar DNI solo números
        document.getElementById('dni').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Validar formulario
        document.getElementById('formMedico').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]').value;
            const passwordConfirm = document.querySelector('input[name="password_confirm"]').value;

            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('Las contraseñas no coinciden');
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres');
                return false;
            }

            const dni = document.getElementById('dni').value;
            if (!/^[0-9]{8}$/.test(dni)) {
                e.preventDefault();
                alert('El DNI debe tener exactamente 8 dígitos');
                return false;
            }
        });
    </script>
</body>
</html>