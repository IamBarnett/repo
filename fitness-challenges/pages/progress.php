<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Verificar autenticación
requireAuth();

$user = getCurrentUser();
$message = '';
$messageType = '';

// Obtener desafíos activos del usuario
$activeChallenges = getUserActiveChallenges($user['id']);

// Si se especifica un desafío en la URL
$selectedChallenge = isset($_GET['challenge']) ? (int)$_GET['challenge'] : null;

// Manejar registro de actividad
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_activity'])) {
    $userChallengeId = (int)$_POST['user_challenge_id'];
    $activityType = sanitize($_POST['activity_type']);
    $duration = (int)$_POST['duration'];
    $calories = (int)$_POST['calories'];
    $activityDate = $_POST['activity_date'];
    $notes = sanitize($_POST['notes']);
    
    $pdo = getConnection();
    
    try {
        // Insertar actividad
        $stmt = $pdo->prepare("
            INSERT INTO activities (user_challenge_id, activity_type, duration, calories_burned, activity_date, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userChallengeId, $activityType, $duration, $calories, $activityDate, $notes]);
        
        // Actualizar progreso del desafío
        calculateChallengeProgress($userChallengeId);
        
        // Actualizar estadísticas del usuario
        updateUserStatistics($user['id']);
        
        // Guardar en cookies la última actividad registrada
        savePreference('last_activity_type', $activityType);
        savePreference('last_duration', $duration);
        
        $message = '¡Actividad registrada exitosamente!';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Error al registrar la actividad';
        $messageType = 'error';
    }
}

// Obtener actividades del usuario
$pdo = getConnection();
$query = "
    SELECT a.*, uc.challenge_id, c.name as challenge_name
    FROM activities a
    JOIN user_challenges uc ON a.user_challenge_id = uc.id
    JOIN challenges c ON uc.challenge_id = c.id
    WHERE uc.user_id = ?
";
$params = [$user['id']];

if ($selectedChallenge) {
    $query .= " AND uc.id = ?";
    $params[] = $selectedChallenge;
}

$query .= " ORDER BY a.activity_date DESC, a.created_at DESC LIMIT 50";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$activities = $stmt->fetchAll();

// Obtener resumen de actividades por tipo
$stmt = $pdo->prepare("
    SELECT activity_type, COUNT(*) as count, SUM(duration) as total_duration, SUM(calories_burned) as total_calories
    FROM activities a
    JOIN user_challenges uc ON a.user_challenge_id = uc.id
    WHERE uc.user_id = ?
    GROUP BY activity_type
    ORDER BY count DESC
");
$stmt->execute([$user['id']]);
$activitySummary = $stmt->fetchAll();

// Obtener preferencias de cookies
$lastActivityType = getPreference('last_activity_type', 'Correr');
$lastDuration = getPreference('last_duration', 30);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Progreso - Fitness Challenge</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar container">
            <a href="dashboard.php" class="logo">💪 Fitness Challenge</a>
            <ul class="nav-links">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="challenges.php"><i class="fas fa-trophy"></i> Desafíos</a></li>
                <li><a href="progress.php" class="active"><i class="fas fa-chart-line"></i> Progreso</a></li>
                <li><a href="statistics.php"><i class="fas fa-chart-bar"></i> Estadísticas</a></li>
                <li>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span style="color: #6b7280;">Hola, <?php echo htmlspecialchars($user['username']); ?></span>
                        <a href="../logout.php" class="btn btn-secondary" style="padding: 0.5rem 1rem;">
                            <i class="fas fa-sign-out-alt"></i> Salir
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <!-- Contenido principal -->
    <main class="container" style="margin-top: 2rem;">
        <!-- Título -->
        <div class="fade-in">
            <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">
                <i class="fas fa-chart-line"></i> Mi Progreso
            </h1>
            <p style="color: #6b7280; font-size: 1.125rem;">
                Registra tus actividades y observa tu evolución
            </p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>" style="margin-top: 1rem;">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check' : 'exclamation'; ?>-circle"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; margin-top: 2rem;">
            <!-- Formulario de registro de actividad -->
            <div>
                <div class="card">
                    <h2 style="margin-bottom: 1.5rem;">
                        <i class="fas fa-plus-circle"></i> Registrar Actividad
                    </h2>
                    
                    <?php if (empty($activeChallenges)): ?>
                        <p style="text-align: center; color: #6b7280; padding: 2rem 0;">
                            No tienes desafíos activos. 
                            <a href="challenges.php" style="color: var(--primary-color);">Únete a uno ahora</a>
                        </p>
                    <?php else: ?>
                        <form method="POST" class="activity-form" style="background: transparent; padding: 0;">
                            <div class="form-group">
                                <label class="form-label" for="user_challenge_id">
                                    <i class="fas fa-trophy"></i> Desafío
                                </label>
                                <select name="user_challenge_id" id="user_challenge_id" class="form-control" required>
                                    <?php foreach ($activeChallenges as $challenge): ?>
                                        <option value="<?php echo $challenge['id']; ?>" 
                                                <?php echo $selectedChallenge == $challenge['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($challenge['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="activity_type">
                                    <i class="fas fa-running"></i> Tipo de actividad
                                </label>
                                <select name="activity_type" id="activity_type" class="form-control" required>
                                    <option value="Correr" <?php echo $lastActivityType === 'Correr' ? 'selected' : ''; ?>>🏃 Correr</option>
                                    <option value="Caminar" <?php echo $lastActivityType === 'Caminar' ? 'selected' : ''; ?>>🚶 Caminar</option>
                                    <option value="Ciclismo" <?php echo $lastActivityType === 'Ciclismo' ? 'selected' : ''; ?>>🚴 Ciclismo</option>
                                    <option value="Natación" <?php echo $lastActivityType === 'Natación' ? 'selected' : ''; ?>>🏊 Natación</option>
                                    <option value="Pesas" <?php echo $lastActivityType === 'Pesas' ? 'selected' : ''; ?>>🏋️ Pesas</option>
                                    <option value="Yoga" <?php echo $lastActivityType === 'Yoga' ? 'selected' : ''; ?>>🧘 Yoga</option>
                                    <option value="CrossFit" <?php echo $lastActivityType === 'CrossFit' ? 'selected' : ''; ?>>💪 CrossFit</option>
                                    <option value="Otro" <?php echo $lastActivityType === 'Otro' ? 'selected' : ''; ?>>🏃 Otro</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="activity_date">
                                    <i class="fas fa-calendar"></i> Fecha
                                </label>
                                <input type="date" name="activity_date" id="activity_date" class="form-control" 
                                       required max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label class="form-label" for="duration">
                                        <i class="fas fa-clock"></i> Duración (min)
                                    </label>
                                    <input type="number" name="duration" id="duration" class="form-control" 
                                           required min="1" max="300" value="<?php echo $lastDuration; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="calories">
                                        <i class="fas fa-fire"></i> Calorías
                                    </label>
                                    <input type="number" name="calories" id="calories" class="form-control" 
                                           required min="0" max="2000" placeholder="Estimado">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="notes">
                                    <i class="fas fa-sticky-note"></i> Notas (opcional)
                                </label>
                                <textarea name="notes" id="notes" class="form-control" rows="3" 
                                          placeholder="¿Cómo te sentiste? ¿Algún logro especial?"></textarea>
                            </div>
                            
                            <button type="submit" name="register_activity" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Registrar actividad
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <!-- Resumen de actividades -->
                <div class="card" style="margin-top: 2rem;">
                    <h3 style="margin-bottom: 1rem;">
                        <i class="fas fa-chart-pie"></i> Resumen por tipo
                    </h3>
                    <?php if (empty($activitySummary)): ?>
                        <p style="color: #6b7280; text-align: center;">
                            No hay actividades registradas aún
                        </p>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <?php foreach ($activitySummary as $summary): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem; background: #f3f4f6; border-radius: 8px;">
                                    <span style="font-weight: 600;">
                                        <?php echo htmlspecialchars($summary['activity_type']); ?>
                                    </span>
                                    <div style="display: flex; gap: 1rem; font-size: 0.875rem;">
                                        <span data-tooltip="Sesiones">
                                            <i class="fas fa-hashtag"></i> <?php echo $summary['count']; ?>
                                        </span>
                                        <span data-tooltip="Tiempo total">
                                            <i class="fas fa-clock"></i> <?php echo round($summary['total_duration'] / 60); ?>h
                                        </span>
                                        <span data-tooltip="Calorías totales">
                                            <i class="fas fa-fire"></i> <?php echo number_format($summary['total_calories']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Lista de actividades -->
            <div>
                <div class="card">
                    <h2 style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fas fa-list"></i> Actividades Recientes</span>
                        <button class="btn btn-secondary" onclick="exportData('csv')" style="font-size: 0.875rem;">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </h2>
                    
                    <?php if (empty($activities)): ?>
                        <p style="text-align: center; color: #6b7280; padding: 2rem 0;">
                            <i class="fas fa-info-circle"></i> No hay actividades registradas
                        </p>
                    <?php else: ?>
                        <div class="activity-list">
                            <?php foreach ($activities as $activity): ?>
                                <div class="activity-item" style="border-left: 4px solid var(--primary-color);">
                                    <div style="flex: 1;">
                                        <div style="display: flex; justify-content: space-between; align-items: start;">
                                            <div>
                                                <h4 style="margin-bottom: 0.25rem; color: var(--primary-color);">
                                                    <?php echo htmlspecialchars($activity['activity_type']); ?>
                                                </h4>
                                                <p style="font-size: 0.875rem; color: #6b7280;">
                                                    <?php echo htmlspecialchars($activity['challenge_name']); ?>
                                                </p>
                                            </div>
                                            <span style="font-size: 0.875rem; color: #6b7280;">
                                                <?php echo formatDate($activity['activity_date']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="activity-stats" style="margin-top: 0.5rem;">
                                            <span><i class="fas fa-clock"></i> <?php echo $activity['duration']; ?> min</span>
                                            <span><i class="fas fa-fire"></i> <?php echo $activity['calories_burned']; ?> cal</span>
                                            <span><i class="fas fa-calendar-check"></i> <?php echo timeAgo($activity['created_at']); ?></span>
                                        </div>
                                        
                                        <?php if ($activity['notes']): ?>
                                            <p style="margin-top: 0.5rem; font-size: 0.875rem; color: #6b7280; font-style: italic;">
                                                <i class="fas fa-quote-left"></i> 
                                                <?php echo htmlspecialchars($activity['notes']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
    <script>
        // Auto-calcular calorías basado en actividad y duración
        document.getElementById('activity_type').addEventListener('change', calculateCalories);
        document.getElementById('duration').addEventListener('input', calculateCalories);
        
        function calculateCalories() {
            const activity = document.getElementById('activity_type').value;
            const duration = parseInt(document.getElementById('duration').value) || 0);
            
            // Calorías aproximadas por minuto según actividad
            const caloriesPerMinute = {
                'Correr': 10,
                'Caminar': 4,
                'Ciclismo': 8,
                'Natación': 11,
                'Pesas': 6,
                'Yoga': 3,
                'CrossFit': 12,
                'Otro': 7
            };
            
            const calories = Math.round(duration * (caloriesPerMinute[activity] || 7));
            document.getElementById('calories').value = calories;
        }
        
        // Calcular calorías iniciales
        calculateCalories();
    </script>
</body>
</html>