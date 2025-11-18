<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$user = getCurrentUser();
$db = getDB();

$cita_id = $_GET['cita_id'] ?? 0;

// Obtener datos de la cita
$cita = $db->fetchOne("
    SELECT c.*,
           CONCAT(p.nombres, ' ', p.apellidos) as paciente_nombre,
           p.dni, p.fecha_nacimiento, p.sexo, p.celular, p.email,
           p.grupo_sanguineo, p.alergias,
           CONCAT(m.nombres, ' ', m.apellidos) as medico_nombre,
           e.nombre as especialidad
    FROM citas c
    INNER JOIN pacientes p ON c.paciente_id = p.id
    INNER JOIN medicos m ON c.medico_id = m.id
    INNER JOIN especialidades e ON c.especialidad_id = e.id
    WHERE c.id = :cita_id
", ['cita_id' => $cita_id]);

if (!$cita) {
    $_SESSION['error'] = 'Cita no encontrada';
    redirect('../citas/lista.php');
}

// Calcular edad
$edad = date_diff(date_create($cita['fecha_nacimiento']), date_create('today'))->y;

// Obtener última consulta del paciente
$ultima_consulta = $db->fetchOne("
    SELECT fecha_consulta, diagnostico_principal, tratamiento
    FROM consultas
    WHERE paciente_id = :paciente_id
    ORDER BY fecha_consulta DESC
    LIMIT 1
", ['paciente_id' => $cita['paciente_id']]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Consulta - Sistema de Clínica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <style>
        .info-paciente {
            background: linear-gradient(135deg, #00BCD4 0%, #0097A7 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .info-paciente h3 {
            margin: 0 0 15px 0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .info-item {
            background: rgba(255,255,255,0.1);
            padding: 10px;
            border-radius: 5px;
        }
        .info-item strong {
            display: block;
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
    </style>
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
            <a href="../citas/lista.php" class="sidebar-menu-item">
                <i class="fas fa-calendar-alt"></i> Citas
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
        <div class="topbar">
            <div>
                <h4>Registrar Consulta Médica</h4>
                <small>Cita ID: <?php echo $cita_id; ?> - <?php echo date('d/m/Y', strtotime($cita['fecha'])); ?></small>
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Información del Paciente -->
            <div class="info-paciente">
                <h3><i class="fas fa-user"></i> <?php echo $cita['paciente_nombre']; ?></h3>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>DNI</strong>
                        <?php echo $cita['dni']; ?>
                    </div>
                    <div class="info-item">
                        <strong>Edad</strong>
                        <?php echo $edad; ?> años
                    </div>
                    <div class="info-item">
                        <strong>Sexo</strong>
                        <?php echo $cita['sexo'] == 'M' ? 'Masculino' : 'Femenino'; ?>
                    </div>
                    <div class="info-item">
                        <strong>Grupo Sanguíneo</strong>
                        <?php echo $cita['grupo_sanguineo'] ?? 'No registrado'; ?>
                    </div>
                    <?php if ($cita['alergias']): ?>
                    <div class="info-item">
                        <strong>⚠️ ALERGIAS</strong>
                        <?php echo $cita['alergias']; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($ultima_consulta): ?>
            <div class="card" style="margin-bottom: 20px; border-left: 4px solid var(--warning-color);">
                <div class="card-body">
                    <h5><i class="fas fa-history"></i> Última Consulta</h5>
                    <p style="margin: 5px 0;">
                        <strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($ultima_consulta['fecha_consulta'])); ?>
                    </p>
                    <p style="margin: 5px 0;">
                        <strong>Diagnóstico:</strong> <?php echo $ultima_consulta['diagnostico_principal']; ?>
                    </p>
                    <a href="historial.php?paciente_id=<?php echo $cita['paciente_id']; ?>" class="btn btn-sm" style="margin-top: 10px;">
                        <i class="fas fa-file-medical"></i> Ver Historial Completo
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <form action="../../controllers/consulta_controller.php" method="POST" id="formConsulta">
                <input type="hidden" name="action" value="registrar">
                <input type="hidden" name="cita_id" value="<?php echo $cita_id; ?>">
                <input type="hidden" name="paciente_id" value="<?php echo $cita['paciente_id']; ?>">
                <input type="hidden" name="medico_id" value="<?php echo $cita['medico_id']; ?>">

                <!-- Signos Vitales -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-heartbeat"></i> Signos Vitales
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label">Presión Arterial</label>
                            <input type="text" class="form-control" name="presion_arterial" placeholder="120/80 mmHg">
                        </div>
                        <div>
                            <label class="form-label">Frecuencia Cardíaca</label>
                            <input type="number" class="form-control" name="frecuencia_cardiaca" placeholder="70 lpm">
                        </div>
                        <div>
                            <label class="form-label">Temperatura</label>
                            <input type="number" step="0.1" class="form-control" name="temperatura" placeholder="36.5 °C">
                        </div>
                        <div>
                            <label class="form-label">Frecuencia Respiratoria</label>
                            <input type="number" class="form-control" name="frecuencia_respiratoria" placeholder="18 rpm">
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label">Peso (kg)</label>
                            <input type="number" step="0.1" class="form-control" name="peso" placeholder="70.5">
                        </div>
                        <div>
                            <label class="form-label">Talla (cm)</label>
                            <input type="number" step="0.1" class="form-control" name="talla" placeholder="170">
                        </div>
                        <div>
                            <label class="form-label">IMC</label>
                            <input type="text" class="form-control" name="imc" id="imc" readonly placeholder="Calculado automáticamente">
                        </div>
                        <div>
                            <label class="form-label">Saturación O2 (%)</label>
                            <input type="number" class="form-control" name="saturacion_oxigeno" placeholder="98">
                        </div>
                    </div>
                </div>

                <!-- Motivo y Anamnesis -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-stethoscope"></i> Anamnesis
                    </div>
                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Motivo de Consulta <span style="color: var(--danger-color);">*</span></label>
                            <textarea class="form-control" name="motivo_consulta" rows="2" required><?php echo $cita['motivo_consulta']; ?></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Enfermedad Actual <span style="color: var(--danger-color);">*</span></label>
                            <textarea class="form-control" name="enfermedad_actual" rows="3" required placeholder="Tiempo de enfermedad, forma de inicio, curso, síntomas..."></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Antecedentes Personales</label>
                            <textarea class="form-control" name="antecedentes_personales" rows="2" placeholder="Enfermedades previas, cirugías, alergias, medicamentos actuales..."></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Antecedentes Familiares</label>
                            <textarea class="form-control" name="antecedentes_familiares" rows="2" placeholder="Enfermedades hereditarias, familiares con problemas de salud..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Examen Físico -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-user-md"></i> Examen Físico
                    </div>
                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Examen General <span style="color: var(--danger-color);">*</span></label>
                            <textarea class="form-control" name="examen_fisico_general" rows="3" required placeholder="Estado general, piel, mucosas, estado de hidratación..."></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Examen Regional/Segmentario</label>
                            <textarea class="form-control" name="examen_fisico_regional" rows="3" placeholder="Cabeza, cuello, tórax, abdomen, extremidades..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Diagnóstico -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-diagnoses"></i> Diagnóstico
                    </div>
                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Diagnóstico Principal (CIE-10) <span style="color: var(--danger-color);">*</span></label>
                            <input type="text" class="form-control" name="diagnostico_principal" required placeholder="Ej: J00 - Rinofaringitis aguda (resfriado común)">
                        </div>
                    </div>
                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Diagnósticos Secundarios</label>
                            <textarea class="form-control" name="diagnosticos_secundarios" rows="2" placeholder="Otros diagnósticos relacionados..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Tratamiento -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-prescription"></i> Plan de Tratamiento
                    </div>
                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Tratamiento / Indicaciones <span style="color: var(--danger-color);">*</span></label>
                            <textarea class="form-control" name="tratamiento" rows="5" required placeholder="Medicamentos, dosis, frecuencia, duración...&#10;&#10;Ejemplo:&#10;1. Paracetamol 500mg - 1 tableta cada 8 horas por 5 días&#10;2. Reposo relativo&#10;3. Abundantes líquidos"></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div style="grid-column: 1 / -1;">
                            <label class="form-label">Exámenes Auxiliares Solicitados</label>
                            <textarea class="form-control" name="examenes_solicitados" rows="3" placeholder="Análisis de sangre, radiografías, etc."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Próxima Cita -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-calendar-plus"></i> Seguimiento
                    </div>
                    <div class="form-row">
                        <div>
                            <label class="form-label">¿Requiere Control?</label>
                            <select class="form-control" name="requiere_control" id="requiere_control">
                                <option value="0">No</option>
                                <option value="1">Sí</option>
                            </select>
                        </div>
                        <div id="dias_control_container" style="display: none;">
                            <label class="form-label">Control en (días)</label>
                            <input type="number" class="form-control" name="dias_control" placeholder="7">
                        </div>
                        <div style="grid-column: 1 / -1;" id="observaciones_control_container" style="display: none;">
                            <label class="form-label">Observaciones de Control</label>
                            <textarea class="form-control" name="observaciones_control" rows="2" placeholder="Instrucciones para la próxima consulta..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <a href="../citas/lista.php" class="btn" style="background: #6c757d; color: white;">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar Consulta
                    </button>
                    <button type="button" class="btn btn-primary" onclick="guardarYImprimir()">
                        <i class="fas fa-print"></i> Guardar e Imprimir
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Calcular IMC automáticamente
        const pesoInput = document.querySelector('input[name="peso"]');
        const tallaInput = document.querySelector('input[name="talla"]');
        const imcInput = document.getElementById('imc');

        function calcularIMC() {
            const peso = parseFloat(pesoInput.value);
            const talla = parseFloat(tallaInput.value) / 100; // convertir cm a metros
            
            if (peso && talla) {
                const imc = (peso / (talla * talla)).toFixed(2);
                imcInput.value = imc;
                
                // Clasificación
                let clasificacion = '';
                if (imc < 18.5) clasificacion = 'Bajo peso';
                else if (imc < 25) clasificacion = 'Normal';
                else if (imc < 30) clasificacion = 'Sobrepeso';
                else clasificacion = 'Obesidad';
                
                imcInput.value = imc + ' (' + clasificacion + ')';
            }
        }

        pesoInput.addEventListener('input', calcularIMC);
        tallaInput.addEventListener('input', calcularIMC);

        // Mostrar campos de control
        document.getElementById('requiere_control').addEventListener('change', function() {
            const diasContainer = document.getElementById('dias_control_container');
            const obsContainer = document.getElementById('observaciones_control_container');
            
            if (this.value == '1') {
                diasContainer.style.display = 'block';
                obsContainer.style.display = 'block';
            } else {
                diasContainer.style.display = 'none';
                obsContainer.style.display = 'none';
            }
        });

        // Guardar e imprimir
        function guardarYImprimir() {
            // Agregar campo hidden para indicar que debe imprimir
            const form = document.getElementById('formConsulta');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'imprimir';
            input.value = '1';
            form.appendChild(input);
            form.submit();
        }
    </script>
</body>
</html>