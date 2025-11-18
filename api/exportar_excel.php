<?php
/**
 * API PARA EXPORTAR REPORTES A EXCEL
 * Genera archivos CSV con datos de reportes
 */

require_once '../config/conexion.php';

if (!isLoggedIn()) {
    die('No autorizado');
}

$db = getDB();
$tipo = $_GET['tipo'] ?? 'citas';
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

// Configurar headers para descarga
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="reporte_' . $tipo . '_' . date('Ymd') . '.csv"');

// Crear archivo CSV
$output = fopen('php://output', 'w');

// BOM para UTF-8 (para que Excel reconozca acentos)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

try {
    switch ($tipo) {
        
        case 'citas':
            // Reporte de Citas
            fputcsv($output, ['REPORTE DE CITAS MÉDICAS']);
            fputcsv($output, ['Período', date('d/m/Y', strtotime($fecha_desde)) . ' - ' . date('d/m/Y', strtotime($fecha_hasta))]);
            fputcsv($output, []);
            fputcsv($output, ['Fecha', 'Hora', 'Paciente', 'DNI', 'Médico', 'Especialidad', 'Estado', 'Precio']);
            
            $citas = $db->fetchAll("
                SELECT 
                    c.fecha,
                    c.hora,
                    CONCAT(p.nombres, ' ', p.apellidos) as paciente,
                    p.dni,
                    CONCAT(m.nombres, ' ', m.apellidos) as medico,
                    e.nombre as especialidad,
                    ec.nombre as estado,
                    c.precio
                FROM citas c
                INNER JOIN pacientes p ON c.paciente_id = p.id
                INNER JOIN medicos m ON c.medico_id = m.id
                INNER JOIN especialidades e ON c.especialidad_id = e.id
                INNER JOIN estados_cita ec ON c.estado_cita_id = ec.id
                WHERE c.fecha BETWEEN :desde AND :hasta
                ORDER BY c.fecha, c.hora
            ", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);
            
            foreach ($citas as $cita) {
                fputcsv($output, [
                    date('d/m/Y', strtotime($cita['fecha'])),
                    date('H:i', strtotime($cita['hora'])),
                    $cita['paciente'],
                    $cita['dni'],
                    $cita['medico'],
                    $cita['especialidad'],
                    $cita['estado'],
                    'S/ ' . number_format($cita['precio'], 2)
                ]);
            }
            break;

        case 'financiero':
            // Reporte Financiero
            fputcsv($output, ['REPORTE FINANCIERO']);
            fputcsv($output, ['Período', date('d/m/Y', strtotime($fecha_desde)) . ' - ' . date('d/m/Y', strtotime($fecha_hasta))]);
            fputcsv($output, []);
            fputcsv($output, ['Fecha', 'Especialidad', 'Médico', 'Total Citas', 'Ingresos']);
            
            $finanzas = $db->fetchAll("
                SELECT 
                    DATE(c.fecha) as fecha,
                    e.nombre as especialidad,
                    CONCAT(m.nombres, ' ', m.apellidos) as medico,
                    COUNT(*) as total_citas,
                    SUM(c.precio) as ingresos
                FROM citas c
                INNER JOIN medicos m ON c.medico_id = m.id
                INNER JOIN especialidades e ON c.especialidad_id = e.id
                WHERE c.fecha BETWEEN :desde AND :hasta
                AND c.estado_cita_id = 5
                GROUP BY DATE(c.fecha), e.nombre, medico
                ORDER BY fecha, especialidad
            ", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);
            
            foreach ($finanzas as $fila) {
                fputcsv($output, [
                    date('d/m/Y', strtotime($fila['fecha'])),
                    $fila['especialidad'],
                    $fila['medico'],
                    $fila['total_citas'],
                    'S/ ' . number_format($fila['ingresos'], 2)
                ]);
            }
            break;

        case 'consultas':
            // Reporte de Consultas
            fputcsv($output, ['REPORTE DE CONSULTAS MÉDICAS']);
            fputcsv($output, ['Período', date('d/m/Y', strtotime($fecha_desde)) . ' - ' . date('d/m/Y', strtotime($fecha_hasta))]);
            fputcsv($output, []);
            fputcsv($output, ['Fecha', 'Paciente', 'DNI', 'Médico', 'Especialidad', 'Diagnóstico']);
            
            $consultas = $db->fetchAll("
                SELECT 
                    c.fecha_consulta,
                    CONCAT(p.nombres, ' ', p.apellidos) as paciente,
                    p.dni,
                    CONCAT(m.nombres, ' ', m.apellidos) as medico,
                    e.nombre as especialidad,
                    c.diagnostico_principal
                FROM consultas c
                INNER JOIN pacientes p ON c.paciente_id = p.id
                INNER JOIN medicos m ON c.medico_id = m.id
                INNER JOIN especialidades e ON m.especialidad_id = e.id
                WHERE DATE(c.fecha_consulta) BETWEEN :desde AND :hasta
                ORDER BY c.fecha_consulta DESC
            ", ['desde' => $fecha_desde, 'hasta' => $fecha_hasta]);
            
            foreach ($consultas as $consulta) {
                fputcsv($output, [
                    date('d/m/Y H:i', strtotime($consulta['fecha_consulta'])),
                    $consulta['paciente'],
                    $consulta['dni'],
                    $consulta['medico'],
                    $consulta['especialidad'],
                    $consulta['diagnostico_principal']
                ]);
            }
            break;

        case 'pacientes':
            // Reporte de Pacientes
            fputcsv($output, ['REPORTE DE PACIENTES']);
            fputcsv($output, []);
            fputcsv($output, ['DNI', 'Nombres', 'Apellidos', 'Edad', 'Sexo', 'Celular', 'Email', 'Fecha Registro']);
            
            $pacientes = $db->fetchAll("
                SELECT 
                    dni,
                    nombres,
                    apellidos,
                    TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) as edad,
                    sexo,
                    celular,
                    email,
                    created_at
                FROM pacientes
                WHERE estado = 'activo'
                ORDER BY created_at DESC
            ");
            
            foreach ($pacientes as $paciente) {
                fputcsv($output, [
                    $paciente['dni'],
                    $paciente['nombres'],
                    $paciente['apellidos'],
                    $paciente['edad'],
                    $paciente['sexo'] == 'M' ? 'Masculino' : 'Femenino',
                    $paciente['celular'],
                    $paciente['email'],
                    date('d/m/Y', strtotime($paciente['created_at']))
                ]);
            }
            break;

        default:
            fputcsv($output, ['Error: Tipo de reporte no válido']);
    }

} catch (Exception $e) {
    fputcsv($output, ['Error al generar reporte', $e->getMessage()]);
}

fclose($output);
exit;
?>