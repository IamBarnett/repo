<?php
require_once __DIR__ . '/../config/database.php';

// Función para sanitizar entrada de datos
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para verificar si el usuario está autenticado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para redirigir
function redirect($url) {
    // Si la URL empieza con /, quitarlo para hacerlo relativo
    if (strpos($url, '/') === 0) {
        $url = substr($url, 1);
    }
    
    // Obtener la URL base del proyecto
    $baseUrl = dirname($_SERVER['SCRIPT_NAME']);
    if ($baseUrl === '/' || $baseUrl === '\\') {
        $baseUrl = '';
    }
    
    header("Location: " . $baseUrl . "/" . $url);
    exit();
}

// Función para obtener estadísticas del usuario
function getUserStatistics($userId) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM statistics WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    
    if (!$stats) {
        // Crear estadísticas si no existen
        $stmt = $pdo->prepare("INSERT INTO statistics (user_id) VALUES (?)");
        $stmt->execute([$userId]);
        return getUserStatistics($userId);
    }
    
    return $stats;
}

// Función para actualizar estadísticas
function updateUserStatistics($userId) {
    $pdo = getConnection();
    
    // Obtener totales
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT uc.id) as total_challenges,
            COUNT(DISTINCT CASE WHEN uc.status = 'completed' THEN uc.id END) as completed_challenges,
            COALESCE(SUM(a.calories_burned), 0) as total_calories,
            COALESCE(SUM(a.duration), 0) as total_time
        FROM user_challenges uc
        LEFT JOIN activities a ON a.user_challenge_id = uc.id
        WHERE uc.user_id = ?
    ");
    $stmt->execute([$userId]);
    $data = $stmt->fetch();
    
    // Actualizar estadísticas
    $stmt = $pdo->prepare("
        UPDATE statistics 
        SET total_challenges = ?, 
            completed_challenges = ?, 
            total_calories_burned = ?,
            total_workout_time = ?
        WHERE user_id = ?
    ");
    $stmt->execute([
        $data['total_challenges'],
        $data['completed_challenges'],
        $data['total_calories'],
        $data['total_time'],
        $userId
    ]);
}

// Función para calcular progreso del desafío
function calculateChallengeProgress($userChallengeId) {
    $pdo = getConnection();
    
    // Obtener información del desafío
    $stmt = $pdo->prepare("
        SELECT uc.*, c.duration 
        FROM user_challenges uc
        JOIN challenges c ON c.id = uc.challenge_id
        WHERE uc.id = ?
    ");
    $stmt->execute([$userChallengeId]);
    $challenge = $stmt->fetch();
    
    if (!$challenge) return 0;
    
    // Calcular días transcurridos
    $startDate = new DateTime($challenge['start_date']);
    $today = new DateTime();
    $daysPassed = $startDate->diff($today)->days;
    
    // Contar días con actividad
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT activity_date) as active_days
        FROM activities
        WHERE user_challenge_id = ?
    ");
    $stmt->execute([$userChallengeId]);
    $result = $stmt->fetch();
    $activeDays = $result['active_days'];
    
    // Calcular progreso
    $progress = min(100, round(($activeDays / $challenge['duration']) * 100));
    
    // Actualizar progreso
    $stmt = $pdo->prepare("UPDATE user_challenges SET progress = ? WHERE id = ?");
    $stmt->execute([$progress, $userChallengeId]);
    
    return $progress;
}

// Función para obtener desafíos activos del usuario
function getUserActiveChallenges($userId) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT uc.*, c.name, c.description, c.duration
        FROM user_challenges uc
        JOIN challenges c ON c.id = uc.challenge_id
        WHERE uc.user_id = ? AND uc.status = 'active'
        ORDER BY uc.start_date DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Función para formatear fecha
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Función para tiempo relativo
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Hace ' . $diff . ' segundos';
    } elseif ($diff < 3600) {
        return 'Hace ' . round($diff / 60) . ' minutos';
    } elseif ($diff < 86400) {
        return 'Hace ' . round($diff / 3600) . ' horas';
    } else {
        return 'Hace ' . round($diff / 86400) . ' días';
    }
}

// Función para guardar preferencias en cookies
function savePreference($name, $value) {
    setcookie($name, $value, time() + COOKIE_LIFETIME, COOKIE_PATH);
}

// Función para obtener preferencia de cookie
function getPreference($name, $default = null) {
    return isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
}
?>