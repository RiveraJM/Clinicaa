<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

$consulta_id = $_GET['id'] ?? 0;

// Obtener consulta completa
$consulta = $db->fetchOne("
    SELECT c.*,
           CONCAT(p.nombres, ' ', p.apellidos) as paciente_nombre,
           p.dni, p.fecha_nacimiento, p.sexo, p.celular,
           p.grupo_sanguineo, p.alergias,
           CONCAT(m.nombres, ' ', m.apellidos) as medico_nombre,
           m.nro_colegiatura,
           e.nombre as especialidad,
           CONCAT(u.username) as registrado_por_nombre
    FROM consultas c
    INNER JOIN pacientes p ON c.paciente_id = p.id
    INNER JOIN medicos m ON c.medico_id = m.id
    INNER JOIN especialidades e ON m.especialidad_id = e.id
    LEFT JOIN usuarios u ON c.registrado_por = u.id
    WHERE c.id = :id
", ['id' => $consulta_id]);

if (!$consulta) {
    $_SESSION['error'] = 'Consulta no encontrada';
    redirect('lista.php');
}

// Calcular edad
$edad = date_diff(date_create($consulta['fecha_nacimiento']), date_create('today'))->y;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta Médica - Sistema de Clínica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <style>
        @media print {
            .sidebar, .topbar, .no-print { display: none !important; }
            .main-content { margin-left: 0 !important; }
            .card { page-break-inside: avoid; }
        }
        .section-print {
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .section-print h4 {
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-color);
            color: var(--primary-color);
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
            <a href="lista.php" class="sidebar-menu-item active">
                <i class="fas fa-notes-medical"></i> Consultas
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
                <h4>Consulta Médica</h4>
                <small><?php echo date('d/m/Y', strtotime($consulta['fecha_consulta'])); ?></small>
            </div>
            <div style="display: flex; gap: 10px;">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Imprimir
                </button>
                <a href="imprimir_receta.php?id=<?php echo $consulta_id; ?>" target="_blank" class="btn btn-success">
                    <i class="fas fa-prescription"></i> Imprimir Receta
                </a>
                <a href="historial.php?paciente_id=<?php echo $consulta['paciente_id']; ?>" class="btn">
                    <i class="fas fa-history"></i> Ver Historial
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Header -->
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="margin: 0; color: var(--primary-color);">CLÍNICA RODRÍGUEZ Y ESPECIALISTAS</h2>
                <p style="margin: 5px 0;">Jr. Brasil 262, Tarapoto - San Martín</p>
                <p style="margin: 0;">Teléfono: (042) 522-123</p>
            </div>

            <!-- Datos del Paciente -->
            <div class="section-print">
                <h4><i class="fas fa-user"></i> DATOS DEL PACIENTE</h4>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <div><strong>Nombre:</strong> <?php echo $consulta['paciente_nombre']; ?></div>
                    <div><strong>DNI:</strong> <?php echo $consulta['dni']; ?></div>
                    <div><strong>Edad:</strong> <?php echo $edad; ?> años</div>
                    <div><strong>Sexo:</strong> <?php echo $consulta['sexo'] == 'M' ? 'Masculino' : 'Femenino'; ?></div>
                    <div><strong>Grupo Sanguíneo:</strong> <?php echo $consulta['grupo_sanguineo'] ?? 'No registrado'; ?></div>
                    <div><strong>Fecha de Consulta:</strong> <?php echo date('d/m/Y H:i', strtotime($consulta['fecha_consulta'])); ?></div>
                </div>
                <?php if ($consulta['alergias']): ?>
                <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-left: 4px solid var(--danger-color);">
                    <strong>⚠️ ALERGIAS:</strong> <?php echo $consulta['alergias']; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Datos del Médico -->
            <div class="section-print">
                <h4><i class="fas fa-user-md"></i> MÉDICO TRATANTE</h4>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                    <div><strong>Nombre:</strong> Dr(a). <?php echo $consulta['medico_nombre']; ?></div>
                    <div><strong>CMP:</strong> <?php echo $consulta['nro_colegiatura']; ?></div>
                    <div><strong>Especialidad:</strong> <?php echo $consulta['especialidad']; ?></div>
                </div>
            </div>

            <!-- Signos Vitales -->
            <?php if ($consulta['presion_arterial'] || $consulta['temperatura'] || $consulta['peso']): ?>
            <div class="section-print">
                <h4><i class="fas fa-heartbeat"></i> SIGNOS VITALES</h4>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                    <?php if ($consulta['presion_arterial']): ?>
                    <div>
                        <small style="color: var(--text-secondary); display: block;">Presión Arterial</small>
                        <strong><?php echo $consulta['presion_arterial']; ?></strong>
                    </div>
                    <?php endif; ?>

                    <?php if ($consulta['frecuencia_cardiaca']): ?>
                    <div>
                        <small style="color: var(--text-secondary); display: block;">Frecuencia Cardíaca</small>
                        <strong><?php echo $consulta['frecuencia_cardiaca']; ?> lpm</strong>
                    </div>
                    <?php endif; ?>

                    <?php if ($consulta['temperatura']): ?>
                    <div>
                        <small style="color: var(--text-secondary); display: block;">Temperatura</small>
                        <strong><?php echo $consulta['temperatura']; ?> °C</strong>
                    </div>
                    <?php endif; ?>

                    <?php if ($consulta['frecuencia_respiratoria']): ?>
                    <div>
                        <small style="color: var(--text-secondary); display: block;">Frecuencia Respiratoria</small>
                        <strong><?php echo $consulta['frecuencia_respiratoria']; ?> rpm</strong>
                    </div>
                    <?php endif; ?>

                    <?php if ($consulta['peso']): ?>
                    <div>
                        <small style="color: var(--text-secondary); display: block;">Peso</small>
                        <strong><?php echo $consulta['peso']; ?> kg</strong>
                    </div>
                    <?php endif; ?>

                    <?php if ($consulta['talla']): ?>
                    <div>
                        <small style="color: var(--text-secondary); display: block;">Talla</small>
                        <strong><?php echo $consulta['talla']; ?> cm</strong>
                    </div>
                    <?php endif; ?>

                    <?php if ($consulta['imc']): ?>
                    <div>
                        <small style="color: var(--text-secondary); display: block;">IMC</small>
                        <strong><?php echo $consulta['imc']; ?></strong>
                    </div>
                    <?php endif; ?>

                    <?php if ($consulta['saturacion_oxigeno']): ?>
                    <div>
                        <small style="color: var(--text-secondary); display: block;">Saturación O2</small>
                        <strong><?php echo $consulta['saturacion_oxigeno']; ?>%</strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Anamnesis -->
            <div class="section-print">
                <h4><i class="fas fa-stethoscope"></i> ANAMNESIS</h4>
                
                <div style="margin-bottom: 15px;">
                    <strong>Motivo de Consulta:</strong><br>
                    <?php echo nl2br($consulta['motivo_consulta']); ?>
                </div>

                <div style="margin-bottom: 15px;">
                    <strong>Enfermedad Actual:</strong><br>
                    <?php echo nl2br($consulta['enfermedad_actual']); ?>
                </div>

                <?php if ($consulta['antecedentes_personales']): ?>
                <div style="margin-bottom: 15px;">
                    <strong>Antecedentes Personales:</strong><br>
                    <?php echo nl2br($consulta['antecedentes_personales']); ?>
                </div>
                <?php endif; ?>

                <?php if ($consulta['antecedentes_familiares']): ?>
                <div>
                    <strong>Antecedentes Familiares:</strong><br>
                    <?php echo nl2br($consulta['antecedentes_familiares']); ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Examen Físico -->
            <div class="section-print">
                <h4><i class="fas fa-user-md"></i> EXAMEN FÍSICO</h4>
                
                <div style="margin-bottom: 15px;">
                    <strong>Examen General:</strong><br>
                    <?php echo nl2br($consulta['examen_fisico_general']); ?>
                </div>

                <?php if ($consulta['examen_fisico_regional']): ?>
                <div>
                    <strong>Examen Regional:</strong><br>
                    <?php echo nl2br($consulta['examen_fisico_regional']); ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Diagnóstico -->
            <div class="section-print" style="background: #f0f8ff;">
                <h4><i class="fas fa-diagnoses"></i> DIAGNÓSTICO</h4>
                
                <div style="margin-bottom: 15px;">
                    <strong>Diagnóstico Principal:</strong><br>
                    <span style="font-size: 18px; color: var(--primary-color);"><?php echo $consulta['diagnostico_principal']; ?></span>
                </div>

                <?php if ($consulta['diagnosticos_secundarios']): ?>
                <div>
                    <strong>Diagnósticos Secundarios:</strong><br>
                    <?php echo nl2br($consulta['diagnosticos_secundarios']); ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tratamiento -->
            <div class="section-print" style="background: #f0fff4;">
                <h4><i class="fas fa-prescription"></i> TRATAMIENTO</h4>
                <?php echo nl2br($consulta['tratamiento']); ?>
            </div>

            <!-- Exámenes Solicitados -->
            <?php if ($consulta['examenes_solicitados']): ?>
            <div class="section-print">
                <h4><i class="fas fa-flask"></i> EXÁMENES AUXILIARES SOLICITADOS</h4>
                <?php echo nl2br($consulta['examenes_solicitados']); ?>
            </div>
            <?php endif; ?>

            <!-- Control -->
            <?php if ($consulta['requiere_control']): ?>
            <div class="section-print" style="background: #fff3cd;">
                <h4><i class="fas fa-calendar-check"></i> SEGUIMIENTO</h4>
                <p><strong>Control requerido en: <?php echo $consulta['dias_control']; ?> días</strong></p>
                <?php if ($consulta['observaciones_control']): ?>
                <p><?php echo nl2br($consulta['observaciones_control']); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Firma -->
            <div style="margin-top: 50px; text-align: center;">
                <div style="border-top: 2px solid #333; width: 300px; margin: 0 auto; padding-top: 10px;">
                    <strong>Dr(a). <?php echo $consulta['medico_nombre']; ?></strong><br>
                    <small>CMP: <?php echo $consulta['nro_colegiatura']; ?></small><br>
                    <small><?php echo $consulta['especialidad']; ?></small>
                </div>
            </div>
        </div>
    </div>
</body>
</html>