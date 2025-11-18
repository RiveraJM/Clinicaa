<?php
/**
 * CONTROLADOR DE PACIENTES
 * Maneja todas las operaciones CRUD
 */

require_once '../config/conexion.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$db = getDB();
$user = getCurrentUser();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        
        case 'create':
            // Crear nuevo paciente
            $dni = sanitize($_POST['dni']);
            $nombres = sanitize($_POST['nombres']);
            $apellidos = sanitize($_POST['apellidos']);
            $fecha_nacimiento = $_POST['fecha_nacimiento'];
            $sexo = $_POST['sexo'];
            $telefono = sanitize($_POST['telefono'] ?? '');
            $celular = sanitize($_POST['celular']);
            $email = sanitize($_POST['email'] ?? '');
            $direccion = sanitize($_POST['direccion'] ?? '');
            $distrito = sanitize($_POST['distrito'] ?? '');
            $provincia = sanitize($_POST['provincia'] ?? '');
            $departamento = sanitize($_POST['departamento'] ?? '');
            $tiene_seguro = $_POST['tiene_seguro'] ?? 0;
            $seguro_id = $_POST['seguro_id'] ?? null;
            $nro_seguro = sanitize($_POST['nro_seguro'] ?? '');
            $grupo_sanguineo = $_POST['grupo_sanguineo'] ?? null;
            $alergias = sanitize($_POST['alergias'] ?? '');
            $observaciones = sanitize($_POST['observaciones'] ?? '');

            // Validar DNI
            if (!validarDNI($dni)) {
                $_SESSION['error'] = 'El DNI debe tener 8 dígitos';
                redirect('../views/pacientes/nuevo.php');
            }

            // Verificar si el DNI ya existe
            $existente = $db->fetchOne("SELECT id FROM pacientes WHERE dni = :dni", ['dni' => $dni]);
            if ($existente) {
                $_SESSION['error'] = 'Ya existe un paciente con ese DNI';
                redirect('../views/pacientes/nuevo.php');
            }

            // Insertar paciente
            $sql = "INSERT INTO pacientes (
                        dni, nombres, apellidos, fecha_nacimiento, sexo,
                        telefono, celular, email, direccion, distrito, provincia, departamento,
                        tiene_seguro, seguro_id, nro_seguro, grupo_sanguineo, alergias, observaciones
                    ) VALUES (
                        :dni, :nombres, :apellidos, :fecha_nacimiento, :sexo,
                        :telefono, :celular, :email, :direccion, :distrito, :provincia, :departamento,
                        :tiene_seguro, :seguro_id, :nro_seguro, :grupo_sanguineo, :alergias, :observaciones
                    )";

            $params = [
                'dni' => $dni,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'fecha_nacimiento' => $fecha_nacimiento,
                'sexo' => $sexo,
                'telefono' => $telefono,
                'celular' => $celular,
                'email' => $email,
                'direccion' => $direccion,
                'distrito' => $distrito,
                'provincia' => $provincia,
                'departamento' => $departamento,
                'tiene_seguro' => $tiene_seguro,
                'seguro_id' => $seguro_id ?: null,
                'nro_seguro' => $nro_seguro,
                'grupo_sanguineo' => $grupo_sanguineo,
                'alergias' => $alergias,
                'observaciones' => $observaciones
            ];

            $id = $db->insert($sql, $params);

            $_SESSION['success'] = 'Paciente registrado correctamente';
            redirect('../views/pacientes/lista.php');
            break;

        case 'update':
            // Actualizar paciente
            $id = $_POST['id'];
            $nombres = sanitize($_POST['nombres']);
            $apellidos = sanitize($_POST['apellidos']);
            $fecha_nacimiento = $_POST['fecha_nacimiento'];
            $sexo = $_POST['sexo'];
            $telefono = sanitize($_POST['telefono'] ?? '');
            $celular = sanitize($_POST['celular']);
            $email = sanitize($_POST['email'] ?? '');
            $direccion = sanitize($_POST['direccion'] ?? '');
            $distrito = sanitize($_POST['distrito'] ?? '');
            $provincia = sanitize($_POST['provincia'] ?? '');
            $departamento = sanitize($_POST['departamento'] ?? '');
            $tiene_seguro = $_POST['tiene_seguro'] ?? 0;
            $seguro_id = $_POST['seguro_id'] ?? null;
            $nro_seguro = sanitize($_POST['nro_seguro'] ?? '');
            $grupo_sanguineo = $_POST['grupo_sanguineo'] ?? null;
            $alergias = sanitize($_POST['alergias'] ?? '');
            $observaciones = sanitize($_POST['observaciones'] ?? '');

            $sql = "UPDATE pacientes SET
                        nombres = :nombres,
                        apellidos = :apellidos,
                        fecha_nacimiento = :fecha_nacimiento,
                        sexo = :sexo,
                        telefono = :telefono,
                        celular = :celular,
                        email = :email,
                        direccion = :direccion,
                        distrito = :distrito,
                        provincia = :provincia,
                        departamento = :departamento,
                        tiene_seguro = :tiene_seguro,
                        seguro_id = :seguro_id,
                        nro_seguro = :nro_seguro,
                        grupo_sanguineo = :grupo_sanguineo,
                        alergias = :alergias,
                        observaciones = :observaciones
                    WHERE id = :id";

            $params = [
                'id' => $id,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'fecha_nacimiento' => $fecha_nacimiento,
                'sexo' => $sexo,
                'telefono' => $telefono,
                'celular' => $celular,
                'email' => $email,
                'direccion' => $direccion,
                'distrito' => $distrito,
                'provincia' => $provincia,
                'departamento' => $departamento,
                'tiene_seguro' => $tiene_seguro,
                'seguro_id' => $seguro_id ?: null,
                'nro_seguro' => $nro_seguro,
                'grupo_sanguineo' => $grupo_sanguineo,
                'alergias' => $alergias,
                'observaciones' => $observaciones
            ];

            $db->execute($sql, $params);

            $_SESSION['success'] = 'Paciente actualizado correctamente';
            redirect('../views/pacientes/lista.php');
            break;

        case 'delete':
            // Eliminar (soft delete)
            $id = $_GET['id'];

            $sql = "UPDATE pacientes SET estado = 'inactivo' WHERE id = :id";
            $db->execute($sql, ['id' => $id]);

            $_SESSION['success'] = 'Paciente eliminado correctamente';
            redirect('../views/pacientes/lista.php');
            break;

        default:
            $_SESSION['error'] = 'Acción no válida';
            redirect('../views/pacientes/lista.php');
    }

} catch (Exception $e) {
    error_log("Error en paciente_controller: " . $e->getMessage());
    $_SESSION['error'] = 'Error en el sistema';
    redirect('../views/pacientes/lista.php');
}
?>