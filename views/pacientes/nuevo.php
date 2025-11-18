<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

// Obtener seguros
$seguros = $db->fetchAll("SELECT * FROM seguros WHERE estado = 'activo' ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Paciente - Sistema de Clínica</title>
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
                <h4>Nuevo Paciente</h4>
                <small>Registrar nuevo paciente en el sistema</small>
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
                    <h2>Registrar Paciente</h2>
                    <div class="breadcrumb">
                        <a href="../dashboard.php">Dashboard</a> / 
                        <a href="lista.php">Pacientes</a> / Nuevo
                    </div>
                </div>
            </div>

            <form action="../../controllers/paciente_controller.php" method="POST" id="formPaciente">
                <input type="hidden" name="action" value="create">

                <!-- Datos Personales -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-user"></i> Datos Personales
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label">DNI <span style="color: var(--danger-color);">*</span></label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" class="form-control" name="dni" id="dni" maxlength="8" required>
                                <button type="button" class="btn btn-primary" id="btnConsultarDNI" style="white-space: nowrap;">
                                    <i class="fas fa-search"></i> Consultar
                                </button>
                            </div>
                            <small style="color: var(--text-secondary);">Presione consultar para obtener datos de RENIEC</small>
                            <div class="loading" id="loading" style="display: none;">
                                <div class="spinner"></div> Consultando...
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Nombres <span style="color: var(--danger-color);">*</span></label>
                            <input type="text" class="form-control" name="nombres" id="nombres" required>
                        </div>

                        <div>
                            <label class="form-label">Apellidos <span style="color: var(--danger-color);">*</span></label>
                            <input type="text" class="form-control" name="apellidos" id="apellidos" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label">Fecha de Nacimiento <span style="color: var(--danger-color);">*</span></label>
                            <input type="date" class="form-control" name="fecha_nacimiento" required>
                        </div>

                        <div>
                            <label class="form-label">Sexo <span style="color: var(--danger-color);">*</span></label>
                            <select class="form-control" name="sexo" required>
                                <option value="">Seleccione...</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Grupo Sanguíneo</label>
                            <select class="form-control" name="grupo_sanguineo">
                                <option value="">Seleccione...</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
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
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="paciente@email.com">
                        </div>
                    </div>

                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Dirección</label>
                            <input type="text" class="form-control" name="direccion" placeholder="Av. Principal 123">
                        </div>
                    </div>

                    <div class="form-row">
                        <div>
                            <label class="form-label">Distrito</label>
                            <input type="text" class="form-control" name="distrito">
                        </div>

                        <div>
                            <label class="form-label">Provincia</label>
                            <input type="text" class="form-control" name="provincia">
                        </div>

                        <div>
                            <label class="form-label">Departamento</label>
                            <input type="text" class="form-control" name="departamento">
                        </div>
                    </div>
                </div>

                <!-- Datos de Seguro -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-shield-alt"></i> Información de Seguro
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label">¿Tiene Seguro?</label>
                            <select class="form-control" name="tiene_seguro" id="tiene_seguro">
                                <option value="0">No</option>
                                <option value="1">Sí</option>
                            </select>
                        </div>

                        <div id="seguro_select_container" style="display: none;">
                            <label class="form-label">Tipo de Seguro</label>
                            <select class="form-control" name="seguro_id" id="seguro_id">
                                <option value="">Seleccione...</option>
                                <?php foreach ($seguros as $seguro): ?>
                                    <option value="<?php echo $seguro['id']; ?>">
                                        <?php echo $seguro['nombre']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="nro_seguro_container" style="display: none;">
                            <label class="form-label">Número de Seguro</label>
                            <input type="text" class="form-control" name="nro_seguro" id="nro_seguro">
                        </div>
                    </div>
                </div>

                <!-- Información Médica -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-notes-medical"></i> Información Médica
                    </div>
                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Alergias</label>
                            <textarea class="form-control" name="alergias" rows="2" placeholder="Ejemplo: Penicilina, mariscos, etc."></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" rows="2" placeholder="Información adicional relevante"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <a href="lista.php" class="btn" style="background: #6c757d; color: white;">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Paciente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Mostrar/Ocultar campos de seguro
        document.getElementById('tiene_seguro').addEventListener('change', function() {
            const seguroSelect = document.getElementById('seguro_select_container');
            const nroSeguro = document.getElementById('nro_seguro_container');
            
            if (this.value == '1') {
                seguroSelect.style.display = 'block';
                nroSeguro.style.display = 'block';
                document.getElementById('seguro_id').required = true;
            } else {
                seguroSelect.style.display = 'none';
                nroSeguro.style.display = 'none';
                document.getElementById('seguro_id').required = false;
                document.getElementById('seguro_id').value = '';
                document.getElementById('nro_seguro').value = '';
            }
        });

        // Consultar DNI
        document.getElementById('btnConsultarDNI').addEventListener('click', function() {
            const dni = document.getElementById('dni').value.trim();
            
            if (dni.length !== 8) {
                alert('El DNI debe tener 8 dígitos');
                return;
            }

            const loading = document.getElementById('loading');
            const btn = this;
            
            loading.style.display = 'block';
            btn.disabled = true;

            fetch('../../api/consultar_dni.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'dni=' + dni
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('nombres').value = data.data.nombres;
                    document.getElementById('apellidos').value = data.data.apellidoPaterno + ' ' + data.data.apellidoMaterno;
                    alert('Datos obtenidos correctamente');
                } else {
                    alert('No se encontraron datos. Ingrese manualmente.');
                }
            })
            .catch(error => {
                alert('Error al consultar. Ingrese datos manualmente.');
            })
            .finally(() => {
                loading.style.display = 'none';
                btn.disabled = false;
            });
        });

        // Validar solo números en DNI
        document.getElementById('dni').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Validación del formulario
        document.getElementById('formPaciente').addEventListener('submit', function(e) {
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