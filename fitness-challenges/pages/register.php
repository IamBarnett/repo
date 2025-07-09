<?php
require_once '../includes/auth.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validaciones
    if (strlen($username) < 3) {
        $error = 'El nombre de usuario debe tener al menos 3 caracteres';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($password !== $confirmPassword) {
        $error = 'Las contraseñas no coinciden';
    } else {
        $result = registerUser($username, $email, $password);
        
        if ($result['success']) {
            $success = $result['message'];
            // Auto login después del registro
            loginUser($username, $password);
            header('Refresh: 2; URL=dashboard.php');
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Fitness Challenge</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: var(--white);
        }
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .auth-image {
            background: url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=800&h=1200&fit=crop') center/cover;
            min-height: 100vh;
            position: relative;
        }
        .auth-image::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(rgba(255, 56, 92, 0.3), rgba(255, 56, 92, 0.5));
        }
        .auth-content {
            position: relative;
            z-index: 1;
            color: white;
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }
        .auth-quote {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 16px;
            line-height: 1.2;
        }
        .benefit-list {
            margin-top: 32px;
        }
        .benefit-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            font-size: 16px;
        }
        .benefit-icon {
            width: 24px;
            height: 24px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        @media (max-width: 768px) {
            .auth-image {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div style="display: grid; grid-template-columns: 1fr 1fr; min-height: 100vh;">
        <!-- Lado izquierdo - Imagen -->
        <div class="auth-image">
            <div class="auth-content">
                <div class="auth-quote">
                    Comienza tu transformación hoy
                </div>
                <p style="font-size: 18px; opacity: 0.9; margin-bottom: 32px;">
                    Únete a la comunidad fitness más motivadora
                </p>
                
                <div class="benefit-list">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <span>Desafíos personalizados para todos los niveles</span>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <span>Seguimiento detallado de tu progreso</span>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <span>Comunidad de apoyo y motivación</span>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <span>100% gratis, sin tarjeta de crédito</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lado derecho - Formulario -->
        <div class="auth-container">
            <div style="width: 100%; max-width: 450px;">
                <div class="form-header" style="text-align: left; margin-bottom: 48px;">
                    <a href="../index.php" class="logo" style="font-size: 28px; margin-bottom: 24px; display: inline-block;">
                        <i class="fas fa-dumbbell"></i>
                        <span>Fitness</span>
                    </a>
                    <h1 style="font-size: 32px; font-weight: 800; color: var(--dark-color); margin-bottom: 8px;">
                        Crea tu cuenta
                    </h1>
                    <p style="color: var(--gray-medium); font-size: 16px;">
                        Es rápido y fácil
                    </p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-bottom: 24px;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin-bottom: 24px;">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="username">
                            Nombre de usuario
                        </label>
                        <input type="text" id="username" name="username" class="form-control" 
                               required minlength="3" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               placeholder="Elige un nombre de usuario">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">
                            Email
                        </label>
                        <input type="email" id="email" name="email" class="form-control" 
                               required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               placeholder="tu@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">
                            Contraseña
                        </label>
                        <div style="position: relative;">
                            <input type="password" id="password" name="password" class="form-control" 
                                   required minlength="6" placeholder="Mínimo 6 caracteres">
                            <i class="fas fa-eye" onclick="togglePassword('password')" 
                               style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--gray-medium);"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">
                            Confirmar contraseña
                        </label>
                        <div style="position: relative;">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   required minlength="6" placeholder="Repite tu contraseña">
                            <i class="fas fa-eye" onclick="togglePassword('confirm_password')" 
                               style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--gray-medium);"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: start; cursor: pointer; font-size: 14px;">
                            <input type="checkbox" required style="margin-right: 8px; margin-top: 2px; width: 16px; height: 16px;">
                            <span style="color: var(--gray-dark);">
                                Acepto los <a href="#" style="color: var(--dark-color); text-decoration: underline;">términos y condiciones</a> 
                                y la <a href="#" style="color: var(--dark-color); text-decoration: underline;">política de privacidad</a>
                            </span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        Crear cuenta
                    </button>
                </form>
                
                <div class="divider-text" style="margin: 32px 0;">
                    <span>o regístrate con</span>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                    <button class="btn btn-secondary">
                        <i class="fab fa-google"></i>
                    </button>
                    <button class="btn btn-secondary">
                        <i class="fab fa-facebook"></i>
                    </button>
                    <button class="btn btn-secondary">
                        <i class="fab fa-apple"></i>
                    </button>
                </div>
                
                <p style="text-align: center; margin-top: 32px; color: var(--gray-medium);">
                    ¿Ya tienes una cuenta? 
                    <a href="login.php" style="color: var(--dark-color); text-decoration: underline; font-weight: 600;">
                        Inicia sesión
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>