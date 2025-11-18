<?php
/**
 * CONTROLADOR DE MÉDICOS
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
            // Crear nuevo médico
            $dni = sanitize($_POST['dni']);
            $nombres = sanitize($_POST['nombres']);
            $apellidos = sanitize($_POST['apellidos']);
            $especialidad_id = $_POST['especialidad_id'];
            $nro_colegiatura = sanitize($_POST['nro_colegiatura']);
            $rne = sanitize($_POST['rne'] ?? '');
            $telefono = sanitize($_POST['telefono'] ?? '');
            $celular = sanitize($_POST['celular']);
            $email = sanitize($_POST['email']);
            $consultorio = sanitize($_POST['consultorio'] ?? '');
            $tarifa_consulta = $_POST['tarifa_consulta'] ?? 0.00;
            $duracion_consulta = $_POST['duracion_consulta'] ?? 30;
            $biografia = sanitize($_POST['biografia'] ?? '');
            $username = sanitize($_POST['username']);
            $password = $_POST['password'];
            $password_confirm = $_POST['password_confirm'];

            // Validaciones
            if (!validarDNI($dni)) {
                $_SESSION['error'] = 'El DNI debe tener 8 dígitos';
                redirect('../views/medicos/nuevo.php');
            }

            if ($password !== $password_confirm) {
                $_SESSION['error'] = 'Las contraseñas no coinciden';
                redirect('../views/medicos/nuevo.php');
            }

            if (strlen($password) < 6) {
                $_SESSION['error'] = 'La contraseña debe tener al menos 6 caracteres';
                redirect('../views/medicos/nuevo.php');
            }

            // Verificar DNI duplicado
            $existeDNI = $db->fetchOne("SELECT id FROM medicos WHERE dni = :dni", ['dni' => $dni]);
            if ($existeDNI) {
                $_SESSION['error'] = 'Ya existe un médico con ese DNI';
                redirect('../views/medicos/nuevo.php');
            }

            // Verificar CMP duplicado
            $existeCMP = $db->fetchOne("SELECT id FROM medicos WHERE nro_colegiatura = :cmp", ['cmp' => $nro_colegiatura]);
            if ($existeCMP) {
                $_SESSION['error'] = 'Ya existe un médico con ese número de colegiatura';
                redirect('../views/medicos/nuevo.php');
            }

            // Verificar username duplicado
            $existeUsername = $db->fetchOne("SELECT id FROM usuarios WHERE username = :username", ['username' => $username]);
            if ($existeUsername) {
                $_SESSION['error'] = 'El nombre de usuario ya está en uso';
                redirect('../views/medicos/nuevo.php');
            }

            // Verificar email duplicado
            $existeEmail = $db->fetchOne("SELECT id FROM usuarios WHERE email = :email", ['email' => $email]);
            if ($existeEmail) {
                $_SESSION['error'] = 'El email ya está registrado';
                redirect('../views/medicos/nuevo.php');
            }

            // Iniciar transacción
            $db->getConnection()->beginTransaction();

            // 1. Crear usuario
            $sqlUsuario = "INSERT INTO usuarios (username, password, email, rol) 
                          VALUES (:username, :password, :email, 'medico')";
            
            $usuario_id = $db->insert($sqlUsuario, [
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'email' => $email
            ]);

            // 2. Crear médico
            $sqlMedico = "INSERT INTO medicos (
                            usuario_id, especialidad_id, dni, nombres, apellidos,
                            nro_colegiatura, rne, telefono, celular, email,
                            consultorio, tarifa_consulta, duracion_consulta, biografia
                          ) VALUES (
                            :usuario_id, :especialidad_id, :dni, :nombres, :apellidos,
                            :nro_colegiatura, :rne, :telefono, :celular, :email,
                            :consultorio, :tarifa_consulta, :duracion_consulta, :biografia
                          )";

            $db->insert($sqlMedico, [
                'usuario_id' => $usuario_id,
                'especialidad_id' => $especialidad_id,
                'dni' => $dni,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'nro_colegiatura' => $nro_colegiatura,
                'rne' => $rne,
                'telefono' => $telefono,
                'celular' => $celular,
                'email' => $email,
                'consultorio' => $consultorio,
                'tarifa_consulta' => $tarifa_consulta,
                'duracion_consulta' => $duracion_consulta,
                'biografia' => $biografia
            ]);

            // Confirmar transacción
            $db->getConnection()->commit();

            $_SESSION['success'] = 'Médico registrado correctamente';
            redirect('../views/medicos/lista.php');
            break;

        case 'update':
            // Actualizar médico (similar a create pero sin crear usuario)
            $id = $_POST['id'];
            $nombres = sanitize($_POST['nombres']);
            $apellidos = sanitize($_POST['apellidos']);
            $especialidad_id = $_POST['especialidad_id'];
            $telefono = sanitize($_POST['telefono'] ?? '');
            $celular = sanitize($_POST['celular']);
            $email = sanitize($_POST['email']);
            $consultorio = sanitize($_POST['consultorio'] ?? '');
            $tarifa_consulta = $_POST['tarifa_consulta'] ?? 0.00;
            $duracion_consulta = $_POST['duracion_consulta'] ?? 30;
            $biografia = sanitize($_POST['biografia'] ?? '');

            $sql = "UPDATE medicos SET
                        nombres = :nombres,
                        apellidos = :apellidos,
                        especialidad_id = :especialidad_id,
                        telefono = :telefono,
                        celular = :celular,
                        email = :email,
                        consultorio = :consultorio,
                        tarifa_consulta = :tarifa_consulta,
                        duracion_consulta = :duracion_consulta,
                        biografia = :biografia
                    WHERE id = :id";

            $db->execute($sql, [
                'id' => $id,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'especialidad_id' => $especialidad_id,
                'telefono' => $telefono,
                'celular' => $celular,
                'email' => $email,
                'consultorio' => $consultorio,
                'tarifa_consulta' => $tarifa_consulta,
                'duracion_consulta' => $duracion_consulta,
                'biografia' => $biografia
            ]);

            $_SESSION['success'] = 'Médico actualizado correctamente';
            redirect('../views/medicos/lista.php');
            break;

        case 'delete':
            // Eliminar (soft delete)
            $id = $_GET['id'];

            $sql = "UPDATE medicos SET estado = 'inactivo' WHERE id = :id";
            $db->execute($sql, ['id' => $id]);

            $_SESSION['success'] = 'Médico eliminado correctamente';
            redirect('../views/medicos/lista.php');
            break;

        default:
            $_SESSION['error'] = 'Acción no válida';
            redirect('../views/medicos/lista.php');
    }

} catch (Exception $e) {
    if ($db->getConnection()->inTransaction()) {
        $db->getConnection()->rollBack();
    }
    
    error_log("Error en medico_controller: " . $e->getMessage());
    $_SESSION['error'] = 'Error en el sistema: ' . $e->getMessage();
    redirect('../views/medicos/lista.php');
}
?>