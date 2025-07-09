<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Headers para API REST
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar autenticación (simplificado para API)
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getConnection();
    
    switch ($method) {
        case 'GET':
            handleGet($pdo, $userId, $action);
            break;
            
        case 'POST':
            handlePost($pdo, $userId);
            break;
            
        case 'PUT':
            handlePut($pdo, $userId);
            break;
            
        case 'DELETE':
            handleDelete($pdo, $userId);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}

/**
 * Manejar peticiones GET
 */
function handleGet($pdo, $userId, $action) {
    switch ($action) {
        case 'stats':
            // Obtener estadísticas en tiempo real
            $stats = getUserStatistics($userId);
            
            // Actividades de hoy
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as today_activities, SUM(calories_burned) as today_calories
                FROM activities a
                JOIN user_challenges uc ON a.user_challenge_id = uc.id
                WHERE uc.user_id = ? AND DATE(a.activity_date) = CURDATE()
            ");
            $stmt->execute([$userId]);
            $todayStats = $stmt->fetch();
            
            $stats['today_activities'] = $todayStats['today_activities'] ?? 0;
            $stats['today_calories'] = $todayStats['today_calories'] ?? 0;
            
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        case 'activities':
            // Obtener actividades con paginación
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            $offset = ($page - 1) * $limit;
            
            $stmt = $pdo->prepare("
                SELECT a.*, c.name as challenge_name
                FROM activities a
                JOIN user_challenges uc ON a.user_challenge_id = uc.id
                JOIN challenges c ON uc.challenge_id = c.id
                WHERE uc.user_id = ?
                ORDER BY a.activity_date DESC, a.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $limit, $offset]);
            $activities = $stmt->fetchAll();
            
            // Total de actividades
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM activities a
                JOIN user_challenges uc ON a.user_challenge_id = uc.id
                WHERE uc.user_id = ?
            ");
            $stmt->execute([$userId]);
            $total = $stmt->fetch()['total'];
            
            echo json_encode([
                'success' => true,
                'activities' => $activities,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'challenges':
            // Obtener desafíos activos con progreso
            $challenges = getUserActiveChallenges($userId);
            
            foreach ($challenges as &$challenge) {
                $challenge['progress'] = calculateChallengeProgress($challenge['id']);
            }
            
            echo json_encode(['success' => true, 'challenges' => $challenges]);
            break;
            
        case 'activity':
            // Obtener actividad específica
            $activityId = $_GET['id'] ?? 0;
            
            $stmt = $pdo->prepare("
                SELECT a.*, c.name as challenge_name
                FROM activities a
                JOIN user_challenges uc ON a.user_challenge_id = uc.id
                JOIN challenges c ON uc.challenge_id = c.id
                WHERE a.id = ? AND uc.user_id = ?
            ");
            $stmt->execute([$activityId, $userId]);
            $activity = $stmt->fetch();
            
            if ($activity) {
                echo json_encode(['success' => true, 'activity' => $activity]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Actividad no encontrada']);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
    }
}

/**
 * Manejar peticiones POST
 */
function handlePost($pdo, $userId) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos']);
        return;
    }
    
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'register_activity':
            // Validar datos
            if (!isset($data['user_challenge_id'], $data['activity_type'], $data['duration'], $data['calories'], $data['activity_date'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan datos requeridos']);
                return;
            }
            
            // Verificar que el desafío pertenece al usuario
            $stmt = $pdo->prepare("SELECT id FROM user_challenges WHERE id = ? AND user_id = ? AND status = 'active'");
            $stmt->execute([$data['user_challenge_id'], $userId]);
            
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => 'Desafío no válido']);
                return;
            }
            
            // Insertar actividad
            $stmt = $pdo->prepare("
                INSERT INTO activities (user_challenge_id, activity_type, duration, calories_burned, activity_date, notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['user_challenge_id'],
                sanitize($data['activity_type']),
                (int)$data['duration'],
                (int)$data['calories'],
                $data['activity_date'],
                sanitize($data['notes'] ?? '')
            ]);
            
            $activityId = $pdo->lastInsertId();
            
            // Actualizar progreso y estadísticas
            calculateChallengeProgress($data['user_challenge_id']);
            updateUserStatistics($userId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Actividad registrada exitosamente',
                'activity_id' => $activityId
            ]);
            break;
            
        case 'abandon_challenge':
            if (!isset($data['challenge_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de desafío requerido']);
                return;
            }
            
            $stmt = $pdo->prepare("
                UPDATE user_challenges 
                SET status = 'abandoned', end_date = CURDATE()
                WHERE id = ? AND user_id = ? AND status = 'active'
            ");
            $stmt->execute([$data['challenge_id'], $userId]);
            
            if ($stmt->rowCount() > 0) {
                updateUserStatistics($userId);
                echo json_encode(['success' => true, 'message' => 'Desafío abandonado']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Desafío no encontrado']);
            }
            break;
            
        case 'complete_challenge':
            if (!isset($data['challenge_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de desafío requerido']);
                return;
            }
            
            $stmt = $pdo->prepare("
                UPDATE user_challenges 
                SET status = 'completed', end_date = CURDATE(), progress = 100
                WHERE id = ? AND user_id = ? AND status = 'active'
            ");
            $stmt->execute([$data['challenge_id'], $userId]);
            
            if ($stmt->rowCount() > 0) {
                updateUserStatistics($userId);
                echo json_encode(['success' => true, 'message' => '¡Desafío completado!']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Desafío no encontrado']);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);
    }
}

/**
 * Manejar peticiones PUT
 */
function handlePut($pdo, $userId) {
    $data = json_decode(file_get_contents('php://input'), true);
    $activityId = $_GET['id'] ?? 0;
    
    if (!$data || !$activityId) {
        http_response_code(400);
        echo json_encode(['error' => 'Datos inválidos']);
        return;
    }
    
    // Verificar que la actividad pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT a.id 
        FROM activities a
        JOIN user_challenges uc ON a.user_challenge_id = uc.id
        WHERE a.id = ? AND uc.user_id = ?
    ");
    $stmt->execute([$activityId, $userId]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Actividad no encontrada']);
        return;
    }
    
    // Actualizar actividad
    $updates = [];
    $params = [];
    
    if (isset($data['activity_type'])) {
        $updates[] = "activity_type = ?";
        $params[] = sanitize($data['activity_type']);
    }
    if (isset($data['duration'])) {
        $updates[] = "duration = ?";
        $params[] = (int)$data['duration'];
    }
    if (isset($data['calories'])) {
        $updates[] = "calories_burned = ?";
        $params[] = (int)$data['calories'];
    }
    if (isset($data['notes'])) {
        $updates[] = "notes = ?";
        $params[] = sanitize($data['notes']);
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay datos para actualizar']);
        return;
    }
    
    $params[] = $activityId;
    $sql = "UPDATE activities SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Actualizar estadísticas
    updateUserStatistics($userId);
    
    echo json_encode(['success' => true, 'message' => 'Actividad actualizada']);
}

/**
 * Manejar peticiones DELETE
 */
function handleDelete($pdo, $userId) {
    $activityId = $_GET['id'] ?? 0;
    
    if (!$activityId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de actividad requerido']);
        return;
    }
    
    // Verificar y eliminar actividad
    $stmt = $pdo->prepare("
        DELETE a FROM activities a
        JOIN user_challenges uc ON a.user_challenge_id = uc.id
        WHERE a.id = ? AND uc.user_id = ?
    ");
    $stmt->execute([$activityId, $userId]);
    
    if ($stmt->rowCount() > 0) {
        updateUserStatistics($userId);
        echo json_encode(['success' => true, 'message' => 'Actividad eliminada']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Actividad no encontrada']);
    }
}
?>