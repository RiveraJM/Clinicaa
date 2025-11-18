<?php
/**
 * CONTROLADOR DE CITAS (CON NOTIFICACIONES)
 * Maneja creación, edición, cancelación y reprogramación
 */

require_once '../config/conexion.php';
require_once '../includes/NotificacionService.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$db = getDB();
$user = getCurrentUser();
$notificacionService = new NotificacionService($db);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        
        case 'create':
            // Crear nueva cita
            $paciente_id = $_POST['paciente_id'];
            $medico_id = $_POST['medico_id'];
            $especialidad_id = $_POST['especialidad_id'];
            $tipo_cita_id = $_POST['tipo_cita_id'];
            $fecha = $_POST['fecha'];
            $hora = $_POST['hora'] . ':00';
            $motivo_consulta = sanitize($_POST['motivo_consulta']);
            $observaciones = sanitize($_POST['observaciones'] ?? '');

            // Validar que la fecha no sea pasada
            if (strtotime($fecha) < strtotime(date('Y-m-d'))) {
                $_SESSION['error'] = 'No se puede agendar citas en fechas pasadas';
                redirect('../views/citas/nueva.php');
            }

            // Obtener datos del médico para el precio
            $medico = $db->fetchOne("SELECT tarifa_consulta FROM medicos WHERE id = :id", ['id' => $medico_id]);
            $precio = $medico['tarifa_consulta'] ?? 0;

            // Verificar disponibilidad
            $dia_semana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'][date('w', strtotime($fecha))];
            
            $agenda = $db->fetchOne("
                SELECT cupos_por_hora
                FROM agenda_medicos
                WHERE medico_id = :medico_id
                AND dia_semana = :dia_semana
                AND hora_inicio <= :hora
                AND hora_fin > :hora
                AND estado = 'activo'
            ", [
                'medico_id' => $medico_id,
                'dia_semana' => $dia_semana,
                'hora' => $hora
            ]);

            if (!$agenda) {
                $_SESSION['error'] = 'El médico no tiene disponibilidad en este horario';
                redirect('../views/citas/nueva.php');
            }

            // Contar citas ya agendadas
            $citas_count = $db->fetchOne("
                SELECT COUNT(*) as total
                FROM citas
                WHERE medico_id = :medico_id
                AND fecha = :fecha
                AND hora = :hora
                AND estado_cita_id NOT IN (6, 7)
            ", [
                'medico_id' => $medico_id,
                'fecha' => $fecha,
                'hora' => $hora
            ])['total'];

            if ($citas_count >= $agenda['cupos_por_hora']) {
                $_SESSION['error'] = 'No hay cupos disponibles en este horario';
                redirect('../views/citas/nueva.php');
            }

            // Insertar cita
            $sql = "INSERT INTO citas (
                        paciente_id, medico_id, especialidad_id, tipo_cita_id,
                        fecha, hora, motivo_consulta, observaciones, precio,
                        estado_cita_id, creado_por
                    ) VALUES (
                        :paciente_id, :medico_id, :especialidad_id, :tipo_cita_id,
                        :fecha, :hora, :motivo_consulta, :observaciones, :precio,
                        1, :creado_por
                    )";

            $cita_id = $db->insert($sql, [
                'paciente_id' => $paciente_id,
                'medico_id' => $medico_id,
                'especialidad_id' => $especialidad_id,
                'tipo_cita_id' => $tipo_cita_id,
                'fecha' => $fecha,
                'hora' => $hora,
                'motivo_consulta' => $motivo_consulta,
                'observaciones' => $observaciones,
                'precio' => $precio,
                'creado_por' => $user['id']
            ]);

            // ENVIAR NOTIFICACIÓN DE CONFIRMACIÓN
            try {
                $notificacionService->notificarConfirmacionCita($cita_id);
                $_SESSION['success'] = 'Cita agendada correctamente. Notificación enviada al paciente.';
            } catch (Exception $e) {
                error_log("Error al enviar notificación: " . $e->getMessage());
                $_SESSION['success'] = 'Cita agendada correctamente. (No se pudo enviar notificación)';
            }

            redirect('../views/citas/lista.php');
            break;

        case 'cancelar':
            // Cancelar cita
            $id = $_GET['id'];

            $sql = "UPDATE citas SET estado_cita_id = 7 WHERE id = :id";
            $db->execute($sql, ['id' => $id]);

            // ENVIAR NOTIFICACIÓN DE CANCELACIÓN
            try {
                $notificacionService->notificarCancelacionCita($id);
                $_SESSION['success'] = 'Cita cancelada correctamente. Notificación enviada al paciente.';
            } catch (Exception $e) {
                error_log("Error al enviar notificación: " . $e->getMessage());
                $_SESSION['success'] = 'Cita cancelada correctamente. (No se pudo enviar notificación)';
            }

            redirect('../views/citas/lista.php');
            break;

        case 'reprogramar':
            // Reprogramar cita
            $id = $_POST['id'];
            $nueva_fecha = $_POST['fecha'];
            $nueva_hora = $_POST['hora'] . ':00';
            $motivo = sanitize($_POST['motivo'] ?? 'Reprogramación');

            // Obtener datos de la cita actual
            $cita_actual = $db->fetchOne("
                SELECT fecha, hora, medico_id 
                FROM citas 
                WHERE id = :id
            ", ['id' => $id]);

            // Validaciones de disponibilidad (similar a create)...
            
            // Registrar reprogramación
            $db->insert("
                INSERT INTO reprogramaciones (
                    cita_id, fecha_anterior, hora_anterior, 
                    fecha_nueva, hora_nueva, motivo, usuario_id
                ) VALUES (
                    :cita_id, :fecha_anterior, :hora_anterior,
                    :fecha_nueva, :hora_nueva, :motivo, :usuario_id
                )
            ", [
                'cita_id' => $id,
                'fecha_anterior' => $cita_actual['fecha'],
                'hora_anterior' => $cita_actual['hora'],
                'fecha_nueva' => $nueva_fecha,
                'hora_nueva' => $nueva_hora,
                'motivo' => $motivo,
                'usuario_id' => $user['id']
            ]);

            // Actualizar cita
            $db->execute("
                UPDATE citas 
                SET fecha = :fecha, hora = :hora, estado_cita_id = 8
                WHERE id = :id
            ", [
                'fecha' => $nueva_fecha,
                'hora' => $nueva_hora,
                'id' => $id
            ]);

            // ENVIAR NOTIFICACIÓN DE REPROGRAMACIÓN
            try {
                $notificacionService->notificarReprogramacionCita($id, $nueva_fecha, $nueva_hora);
                $_SESSION['success'] = 'Cita reprogramada correctamente. Notificación enviada al paciente.';
            } catch (Exception $e) {
                error_log("Error al enviar notificación: " . $e->getMessage());
                $_SESSION['success'] = 'Cita reprogramada correctamente. (No se pudo enviar notificación)';
            }

            redirect('../views/citas/lista.php');
            break;

        case 'cambiar_estado':
            // Cambiar estado de cita
            $id = $_POST['id'];
            $nuevo_estado = $_POST['estado_cita_id'];

            $db->execute("
                UPDATE citas 
                SET estado_cita_id = :estado
                WHERE id = :id
            ", [
                'estado' => $nuevo_estado,
                'id' => $id
            ]);

            // Si marca como "En Atención", registrar hora
            if ($nuevo_estado == 4) {
                $db->execute("UPDATE citas SET hora_atencion = NOW() WHERE id = :id", ['id' => $id]);
            }

            // Si marca como "Atendida", registrar hora de salida
            if ($nuevo_estado == 5) {
                $db->execute("UPDATE citas SET hora_salida = NOW() WHERE id = :id", ['id' => $id]);
            }

            $_SESSION['success'] = 'Estado actualizado correctamente';
            redirect('../views/citas/ver.php?id=' . $id);
            break;

        default:
            $_SESSION['error'] = 'Acción no válida';
            redirect('../views/citas/lista.php');
    }

} catch (Exception $e) {
    error_log("Error en cita_controller: " . $e->getMessage());
    $_SESSION['error'] = 'Error en el sistema: ' . $e->getMessage();
    redirect('../views/citas/lista.php');
}
?>