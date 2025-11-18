<?php
require_once 'config/conexion.php';

// Redirigir si ya está autenticado
if (isLoggedIn()) {
    redirect('views/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Clínica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="login-page">
    <div class="login-container">
        <!-- Login Side -->
        <div class="login-side">
            <div class="login-logo">
                <i class="fas fa-hospital-symbol"></i>
                <h3>Sistema de Clínica</h3>
                <p>Gestión Médica Moderna</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <form action="controllers/login_controller.php" method="POST" id="loginForm">
                <div class="input-group">
                    <label class="form-label">Usuario</label>
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" class="form-control" name="username" required autofocus>
                </div>

                <div class="input-group">
                    <label class="form-label">Contraseña</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" class="form-control" name="password" id="password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>

                <div style="margin-bottom: 20px;">
                    <label>
                        <input type="checkbox" name="remember"> Recordar sesión
                    </label>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px; padding: 15px; background: rgba(0,188,212,0.05); border-radius: 8px;">
                <small style="color: #666;">
                    <i class="fas fa-info-circle"></i> Demo: <strong>admin</strong> / <strong>password</strong>
                </small>
            </div>
        </div>

        <!-- Info Side -->
        <div class="info-side">
            <h2>Bienvenido</h2>
            <p>Sistema moderno de gestión médica para tu clínica</p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>