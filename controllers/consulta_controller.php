<?php
/**
 * CONTROLADOR DE CONSULTAS MÉDICAS
 * Maneja registro de historias clínicas y consultas
 */

require_once '../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$db = getDB();
$user = getCurrentUser();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        
        case 'registrar':
            // Registrar nueva consulta
            $cita_id = $_POST['cita_id'];
            $paciente_id = $_POST['paciente_id'];
            $medico_id = $_POST['medico_id'];
            
            // Signos vitales
            $presion_arterial = sanitize($_POST['presion_arterial'] ?? '');
            $frecuencia_cardiaca = $_POST['frecuencia_cardiaca'] ?? null;
            $temperatura = $_POST['temperatura'] ?? null;
            $frecuencia_respiratoria = $_POST['frecuencia_respiratoria'] ?? null;
            $peso = $_POST['peso'] ?? null;
            $talla = $_POST['talla'] ?? null;
            $imc = sanitize($_POST['imc'] ?? '');
            $saturacion_oxigeno = $_POST['saturacion_oxigeno'] ?? null;
            
            // Anamnesis
            $motivo_consulta = sanitize($_POST['motivo_consulta']);
            $enfermedad_actual = sanitize($_POST['enfermedad_actual']);
            $antecedentes_personales = sanitize($_POST['antecedentes_personales'] ?? '');
            $antecedentes_familiares = sanitize($_POST['antecedentes_familiares'] ?? '');
            
            // Examen físico
            $examen_fisico_general = sanitize($_POST['examen_fisico_general']);
            $examen_fisico_regional = sanitize($_POST['examen_fisico_regional'] ?? '');
            
            // Diagnóstico
            $diagnostico_principal = sanitize($_POST['diagnostico_principal']);
            $diagnosticos_secundarios = sanitize($_POST['diagnosticos_secundarios'] ?? '');
            
            // Tratamiento
            $tratamiento = sanitize($_POST['tratamiento']);
            $examenes_solicitados = sanitize($_POST['examenes_solicitados'] ?? '');
            
            // Control
            $requiere_control = $_POST['requiere_control'] ?? 0;
            $dias_control = $_POST['dias_control'] ?? null;
            $observaciones_control = sanitize($_POST['observaciones_control'] ?? '');

            // Iniciar transacción
            $db->getConnection()->beginTransaction();

            // Insertar consulta
            $sql = "INSERT INTO consultas (
                        cita_id, paciente_id, medico_id,
                        presion_arterial, frecuencia_cardiaca, temperatura, 
                        frecuencia_respiratoria, peso, talla, imc, saturacion_oxigeno,
                        motivo_consulta, enfermedad_actual, 
                        antecedentes_personales, antecedentes_familiares,
                        examen_fisico_general, examen_fisico_regional,
                        diagnostico_principal, diagnosticos_secundarios,
                        tratamiento, examenes_solicitados,
                        requiere_control, dias_control, observaciones_control,
                        registrado_por
                    ) VALUES (
                        :cita_id, :paciente_id, :medico_id,
                        :presion_arterial, :frecuencia_cardiaca, :temperatura,
                        :frecuencia_respiratoria, :peso, :talla, :imc, :saturacion_oxigeno,
                        :motivo_consulta, :enfermedad_actual,
                        :antecedentes_personales, :antecedentes_familiares,
                        :examen_fisico_general, :examen_fisico_regional,
                        :diagnostico_principal, :diagnosticos_secundarios,
                        :tratamiento, :examenes_solicitados,
                        :requiere_control, :dias_control, :observaciones_control,
                        :registrado_por
                    )";

            $consulta_id = $db->insert($sql, [
                'cita_id' => $cita_id,
                'paciente_id' => $paciente_id,
                'medico_id' => $medico_id,
                'presion_arterial' => $presion_arterial,
                'frecuencia_cardiaca' => $frecuencia_cardiaca,
                'temperatura' => $temperatura,
                'frecuencia_respiratoria' => $frecuencia_respiratoria,
                'peso' => $peso,
                'talla' => $talla,
                'imc' => $imc,
                'saturacion_oxigeno' => $saturacion_oxigeno,
                'motivo_consulta' => $motivo_consulta,
                'enfermedad_actual' => $enfermedad_actual,
                'antecedentes_personales' => $antecedentes_personales,
                'antecedentes_familiares' => $antecedentes_familiares,
                'examen_fisico_general' => $examen_fisico_general,
                'examen_fisico_regional' => $examen_fisico_regional,
                'diagnostico_principal' => $diagnostico_principal,
                'diagnosticos_secundarios' => $diagnosticos_secundarios,
                'tratamiento' => $tratamiento,
                'examenes_solicitados' => $examenes_solicitados,
                'requiere_control' => $requiere_control,
                'dias_control' => $dias_control,
                'observaciones_control' => $observaciones_control,
                'registrado_por' => $user['id']
            ]);

            // Actualizar estado de la cita a "Atendida"
            $db->execute("
                UPDATE citas 
                SET estado_cita_id = 5, hora_salida = NOW() 
                WHERE id = :cita_id
            ", ['cita_id' => $cita_id]);

            // Confirmar transacción
            $db->getConnection()->commit();

            $_SESSION['success'] = 'Consulta médica registrada correctamente';
            
            // Si pidió imprimir, redirigir a imprimir
            if (isset($_POST['imprimir'])) {
                redirect('../consultas/imprimir.php?id=' . $consulta_id);
            } else {
                redirect('../consultas/ver.php?id=' . $consulta_id);
            }
            break;

        default:
            $_SESSION['error'] = 'Acción no válida';
            redirect('../citas/lista.php');
    }

} catch (Exception $e) {
    if ($db->getConnection()->inTransaction()) {
        $db->getConnection()->rollBack();
    }
    
    error_log("Error en consulta_controller: " . $e->getMessage());
    $_SESSION['error'] = 'Error al registrar consulta: ' . $e->getMessage();
    redirect('../citas/lista.php');
}
?>