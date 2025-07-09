<?php
require_once __DIR__ . '/functions.php';

// Función para registrar usuario
function registerUser($username, $email, $password) {
    $pdo = getConnection();
    
    // Verificar si el usuario o email ya existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'El usuario o email ya existe'];
    }
    
    // Hash de la contraseña
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Insertar nuevo usuario
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword]);
        
        $userId = $pdo->lastInsertId();
        
        // Crear registro de estadísticas
        $stmt = $pdo->prepare("INSERT INTO statistics (user_id) VALUES (?)");
        $stmt->execute([$userId]);
        
        return ['success' => true, 'message' => 'Usuario registrado exitosamente', 'user_id' => $userId];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Error al registrar usuario'];
    }
}

// Función para iniciar sesión
function loginUser($username, $password, $remember = false) {
    $pdo = getConnection();
    
    // Buscar usuario por username o email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Credenciales incorrectas'];
    }
    
    // Iniciar sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    
    // Si el usuario quiere ser recordado
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        setcookie('remember_token', $token, time() + COOKIE_LIFETIME, COOKIE_PATH);
        // Aquí podrías guardar el token en la base de datos para mayor seguridad
    }
    
    return ['success' => true, 'message' => 'Sesión iniciada exitosamente'];
}

// Función para cerrar sesión
function logoutUser() {
    // Destruir sesión
    session_destroy();
    
    // Eliminar cookies
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, COOKIE_PATH);
    }
    
    return true;
}

// Función para verificar autenticación
function requireAuth() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

// Función para obtener usuario actual
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email']
    ];
}
?>