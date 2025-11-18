<?php
require_once '../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    // Validar campos vacíos
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = 'Complete todos los campos';
        redirect('../index.php');
    }

    try {
        $db = getDB();
        
        // Buscar usuario
        $sql = "SELECT * FROM usuarios WHERE username = :username AND estado = 'activo'";
        $user = $db->fetchOne($sql, ['username' => $username]);

        if ($user && password_verify($password, $user['password'])) {
            // Login exitoso
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['rol'] = $user['rol'];
            
            // Actualizar último acceso
            $db->execute(
                "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id",
                ['id' => $user['id']]
            );

            // Cookie si marcó recordar
            if ($remember) {
                setcookie('username', $username, time() + (86400 * 30), '/');
            }

            redirect('../views/dashboard.php');
        } else {
            $_SESSION['error'] = 'Usuario o contraseña incorrectos';
            redirect('../index.php');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error del sistema';
        redirect('../index.php');
    }
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    setcookie('username', '', time() - 3600, '/');
    $_SESSION['success'] = 'Sesión cerrada correctamente';
    redirect('../index.php');
}
?>
