<?php
require_once '../includes/auth.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor completa todos los campos';
    } else {
        $result = loginUser($username, $password, $remember);
        
        if ($result['success']) {
            // Guardar última actividad en cookie
            savePreference('last_activity', time());
            redirect('dashboard.php');
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
    <title>Iniciar Sesión - Fitness Challenge</title>
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
            background: url('https://images.unsplash.com/photo-1540497077202-7c8a3999166f?w=800&h=1200&fit=crop') center/cover;
            min-height: 100vh;
            position: relative;
        }
        .auth-image::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.5));
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
        @media (max-width: 768px) {
            .auth-image {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div style="display: grid; grid-template-columns: 1fr 1fr; min-height: 100vh;">
        <!-- Lado izquierdo - Formulario -->
        <div class="auth-container">
            <div style="width: 100%; max-width: 450px;">
                <div class="form-header" style="text-align: left; margin-bottom: 48px;">
                    <a href="../index.php" class="logo" style="font-size: 28px; margin-bottom: 24px; display: inline-block;">
                        <i class="fas fa-dumbbell"></i>
                        <span>Fitness</span>
                    </a>
                    <h1 style="font-size: 32px; font-weight: 800; color: var(--dark-color); margin-bottom: 8px;">
                        Bienvenido de vuelta
                    </h1>
                    <p style="color: var(--gray-medium); font-size: 16px;">
                        Ingresa tus credenciales para continuar
                    </p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-bottom: 24px;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="username">
                            Usuario o Email
                        </label>
                        <input type="text" id="username" name="username" class="form-control" 
                               required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                               placeholder="Ingresa tu usuario o email">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">
                            Contraseña
                        </label>
                        <div style="position: relative;">
                            <input type="password" id="password" name="password" class="form-control" 
                                   required placeholder="Ingresa tu contraseña">
                            <i class="fas fa-eye" onclick="togglePassword('password')" 
                               style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--gray-medium);"></i>
                        </div>
                    </div>
                    
                    <div class="form-group" style="display: flex; align-items: center; justify-content: space-between;">
                        <label style="display: flex; align-items: center; margin-bottom: 0; cursor: pointer;">
                            <input type="checkbox" name="remember" id="remember" style="margin-right: 8px; width: 16px; height: 16px;">
                            <span style="color: var(--gray-dark); font-size: 14px;">Recordarme</span>
                        </label>
                        <a href="#" style="color: var(--dark-color); text-decoration: underline; font-size: 14px;">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        Iniciar sesión
                    </button>
                </form>
                
                <div class="divider-text" style="margin: 32px 0;">
                    <span>o continúa con</span>
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
                    ¿No tienes una cuenta? 
                    <a href="register.php" style="color: var(--dark-color); text-decoration: underline; font-weight: 600;">
                        Regístrate
                    </a>
                </p>
            </div>
        </div>
        
        <!-- Lado derecho - Imagen -->
        <div class="auth-image">
            <div class="auth-content">
                <div class="auth-quote">
                    "El único mal entrenamiento es el que no hiciste"
                </div>
                <p style="font-size: 18px; opacity: 0.9;">
                    Únete a miles de personas transformando sus vidas
                </p>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>