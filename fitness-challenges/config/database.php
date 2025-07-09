<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'fitness_challenges');
define('DB_USER', 'root');
define('DB_PASS', '');

// Función para conectar a la base de datos
function getConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuración de cookies
define('COOKIE_LIFETIME', 60 * 60 * 24 * 30); // 30 días
define('COOKIE_PATH', '/');
?>