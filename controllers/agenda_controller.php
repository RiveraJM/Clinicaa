<?php
require_once '../config/conexion.php';

// DEBUG - Ver qué está llegando
echo "<pre>";
echo "POST Data:\n";
print_r($_POST);
echo "\nGET Data:\n";
print_r($_GET);
echo "\nAction: ";
var_dump($_POST['action'] ?? $_GET['action'] ?? 'NO DEFINIDO');
echo "</pre>";
exit; // TEMPORAL - Quitar después de ver el debug

if (!isLoggedIn()) {
    redirect('../index.php');
}

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'agregar':
            $medico_id = $_POST['medico_id'];
            $dia_semana = $_POST['dia_semana'];
            $hora_inicio = $_POST['hora_inicio'];
            $hora_fin = $_POST['hora_fin'];
            $duracion_cita = $_POST['duracion_cita'];

            // Validar que hora_fin sea mayor que hora_inicio
            if ($hora_inicio >= $hora_fin) {
                $_SESSION['error'] = 'La hora de fin debe ser mayor que la hora de inicio';
                redirect('../views/agenda/configurar.php?medico_id=' . $medico_id);
            }

            // Validar que no exista solapamiento de horarios
            $existe = $db->fetchOne("
                SELECT COUNT(*) as total 
                FROM horarios_medicos 
                WHERE medico_id = :medico_id 
                AND dia_semana = :dia_semana 
                AND estado = 'activo'
                AND (
                    (hora_inicio <= :hora_inicio AND hora_fin > :hora_inicio)
                    OR (hora_inicio < :hora_fin AND hora_fin >= :hora_fin)
                    OR (:hora_inicio <= hora_inicio AND :hora_fin >= hora_fin)
                )
            ", [
                'medico_id' => $medico_id,
                'dia_semana' => $dia_semana,
                'hora_inicio' => $hora_inicio,
                'hora_fin' => $hora_fin
            ]);

            if ($existe['total'] > 0) {
                $_SESSION['error'] = 'Ya existe un horario que se solapa con el horario ingresado';
                redirect('../views/agenda/configurar.php?medico_id=' . $medico_id);
            }

            // Insertar horario
            $db->query("
                INSERT INTO horarios_medicos (medico_id, dia_semana, hora_inicio, hora_fin, duracion_cita, estado)
                VALUES (:medico_id, :dia_semana, :hora_inicio, :hora_fin, :duracion_cita, 'activo')
            ", [
                'medico_id' => $medico_id,
                'dia_semana' => $dia_semana,
                'hora_inicio' => $hora_inicio,
                'hora_fin' => $hora_fin,
                'duracion_cita' => $duracion_cita
            ]);

            $_SESSION['success'] = 'Horario agregado correctamente';
            redirect('../views/agenda/configurar.php?medico_id=' . $medico_id);
            break;

        case 'eliminar':
            $id = $_GET['id'];
            $medico_id = $_GET['medico_id'];

            // Cambiar estado a inactivo en lugar de eliminar
            $db->query("UPDATE horarios_medicos SET estado = 'inactivo' WHERE id = :id", ['id' => $id]);

            $_SESSION['success'] = 'Horario eliminado correctamente';
            redirect('../views/agenda/configurar.php?medico_id=' . $medico_id);
            break;

        default:
            $_SESSION['error'] = 'Acción no válida. Action recibido: ' . ($action ?: 'VACÍO');
            redirect('../views/agenda/configurar.php');
    }

} catch (Exception $e) {
    $_SESSION['error'] = 'Error en el sistema: ' . $e->getMessage();
    $medico_id = $_POST['medico_id'] ?? $_GET['medico_id'] ?? null;
    redirect('../views/agenda/configurar.php' . ($medico_id ? '?medico_id=' . $medico_id : ''));
}
?>