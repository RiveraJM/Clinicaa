<?php
require_once '../../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../../index.php');
}

$db = getDB();
$consulta_id = $_GET['id'] ?? 0;

// Obtener consulta
$consulta = $db->fetchOne("
    SELECT c.*,
           CONCAT(p.nombres, ' ', p.apellidos) as paciente_nombre,
           p.dni, p.fecha_nacimiento, p.sexo,
           CONCAT(m.nombres, ' ', m.apellidos) as medico_nombre,
           m.nro_colegiatura,
           e.nombre as especialidad
    FROM consultas c
    INNER JOIN pacientes p ON c.paciente_id = p.id
    INNER JOIN medicos m ON c.medico_id = m.id
    INNER JOIN especialidades e ON m.especialidad_id = e.id
    WHERE c.id = :id
", ['id' => $consulta_id]);

if (!$consulta) {
    die('Consulta no encontrada');
}

$edad = date_diff(date_create($consulta['fecha_nacimiento']), date_create('today'))->y;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receta Médica</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 20px; }
        .receta { max-width: 800px; margin: 0 auto; border: 2px solid #00BCD4; padding: 30px; }
        .header { text-align: center; border-bottom: 2px solid #00BCD4; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { color: #00BCD4; font-size: 24px; margin-bottom: 5px; }
        .header p { font-size: 12px; color: #666; }
        .paciente { background: #f0f8ff; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .paciente div { display: inline-block; margin-right: 30px; }
        .seccion { margin: 30px 0; }
        .seccion h3 { color: #00BCD4; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 15px; }
        .prescripcion { background: #f9f9f9; padding: 20px; border-left: 4px solid #00BCD4; line-height: 1.8; white-space: pre-line; }
        .firma { margin-top: 80px; text-align: center; }
        .firma-linea { border-top: 2px solid #333; width: 300px; margin: 0 auto 10px; padding-top: 10px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()" style="position: fixed; top: 20px; right: 20px; padding: 10px 20px; background: #00BCD4; color: white; border: none; border-radius: 5px; cursor: pointer;">
        Imprimir Receta
    </button>

    <div class="receta">
        <!-- Header -->
        <div class="header">
            <h1>🏥 CLÍNICA RODRÍGUEZ Y ESPECIALISTAS</h1>
            <p>Jr. Brasil 262, Tarapoto - San Martín</p>
            <p>Teléfono: (042) 522-123</p>
            <p style="margin-top: 10px;"><strong>RECETA MÉDICA</strong></p>
        </div>

        <!-- Datos del Paciente -->
        <div class="paciente">
            <div><strong>Paciente:</strong> <?php echo $consulta['paciente_nombre']; ?></div>
            <div><strong>Edad:</strong> <?php echo $edad; ?> años</div>
            <div><strong>Sexo:</strong> <?php echo $consulta['sexo'] == 'M' ? 'M' : 'F'; ?></div>
            <div><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($consulta['fecha_consulta'])); ?></div>
        </div>

        <!-- Diagnóstico -->
        <div class="seccion">
            <h3>DIAGNÓSTICO</h3>
            <p><strong><?php echo $consulta['diagnostico_principal']; ?></strong></p>
        </div>

        <!-- Prescripción -->
        <div class="seccion">
            <h3>Rp/ PRESCRIPCIÓN</h3>
            <div class="prescripcion">
<?php echo $consulta['tratamiento']; ?>
            </div>
        </div>

        <?php if ($consulta['examenes_solicitados']): ?>
        <!-- Exámenes -->
        <div class="seccion">
            <h3>EXÁMENES AUXILIARES</h3>
            <p><?php echo nl2br($consulta['examenes_solicitados']); ?></p>
        </div>
        <?php endif; ?>

        <!-- Firma -->
        <div class="firma">
            <div class="firma-linea">
                <strong>Dr(a). <?php echo $consulta['medico_nombre']; ?></strong><br>
                <small>CMP: <?php echo $consulta['nro_colegiatura']; ?></small><br>
                <small><?php echo $consulta['especialidad']; ?></small>
            </div>
        </div>
    </div>

    <script>
        // Imprimir automáticamente al cargar
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>