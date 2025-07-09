<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Verificar autenticación
requireAuth();

$user = getCurrentUser();
$stats = getUserStatistics($user['id']);
$activeChallenges = getUserActiveChallenges($user['id']);

// Obtener actividades recientes
$pdo = getConnection();
$stmt = $pdo->prepare("
    SELECT a.*, c.name as challenge_name
    FROM activities a
    JOIN user_challenges uc ON a.user_challenge_id = uc.id
    JOIN challenges c ON uc.challenge_id = c.id
    WHERE uc.user_id = ?
    ORDER BY a.activity_date DESC, a.created_at DESC
    LIMIT 5
");
$stmt->execute([$user['id']]);
$recentActivities = $stmt->fetchAll();

// Actualizar estadísticas
updateUserStatistics($user['id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Fitness Challenge</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar container-fluid">
            <a href="dashboard.php" class="logo">
                <i class="fas fa-dumbbell"></i>
                <span>Fitness</span>
            </a>
            
            <div class="nav-center">
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="challenges.php">Desafíos</a>
                <a href="progress.php">Progreso</a>
                <a href="statistics.php">Estadísticas</a>
            </div>
            
            <div class="nav-right">
                <div class="nav-user-menu">
                    <i class="fas fa-bars" style="color: var(--gray-dark);"></i>
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Contenido principal -->
    <main class="container" style="margin-top: 40px; margin-bottom: 40px;">
        <!-- Saludo personalizado -->
        <div class="fade-in" style="margin-bottom: 32px;">
            <h1 style="font-size: 32px; font-weight: 800; color: var(--dark-color); margin-bottom: 8px;">
                Hola, <?php echo htmlspecialchars($user['username']); ?> 👋
            </h1>
            <p style="color: var(--gray-medium); font-size: 16px;">
                <?php
                $hora = date('H');
                if ($hora < 12) {
                    echo "Buenos días, ¿listo para entrenar?";
                } elseif ($hora < 18) {
                    echo "Buenas tardes, ¡mantén el ritmo!";
                } else {
                    echo "Buenas noches, ¿ya completaste tu entrenamiento de hoy?";
                }
                ?>
            </p>
        </div>

        <!-- Estadísticas principales -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 48px;">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stats-number"><?php echo $stats['total_challenges']; ?></div>
                <div class="stats-label">Desafíos Totales</div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--secondary-color);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stats-number"><?php echo $stats['completed_challenges']; ?></div>
                <div class="stats-label">Desafíos Completados</div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="stats-number"><?php echo number_format($stats['total_calories_burned']); ?></div>
                <div class="stats-label">Calorías Quemadas</div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stats-number"><?php echo round($stats['total_workout_time'] / 60); ?>h</div>
                <div class="stats-label">Tiempo de Ejercicio</div>
            </div>
        </div>

        <!-- Sección de desafíos activos -->
        <div style="margin-bottom: 48px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2 style="font-size: 24px; font-weight: 700; color: var(--dark-color);">Desafíos Activos</h2>
                <a href="challenges.php" class="btn btn-secondary">
                    Ver todos
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <?php if (empty($activeChallenges)): ?>
                <div class="empty-state" style="background: var(--white); border-radius: var(--border-radius); padding: 48px;">
                    <div class="empty-state-icon">
                        <i class="fas fa-trophy" style="font-size: 48px;"></i>
                    </div>
                    <h3 class="empty-state-title">No tienes desafíos activos</h3>
                    <p class="empty-state-text">Explora nuestra colección de desafíos y comienza tu transformación</p>
                    <a href="challenges.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Explorar desafíos
                    </a>
                </div>
            <?php else: ?>
                <div class="cards-grid">
                    <?php foreach ($activeChallenges as $challenge): 
                        $progress = calculateChallengeProgress($challenge['id']);
                        $daysLeft = max(0, $challenge['duration'] - floor((time() - strtotime($challenge['start_date'])) / 86400));
                    ?>
                        <div class="card">
                            <div style="padding: 24px;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
                                    <h3 class="card-title"><?php echo htmlspecialchars($challenge['name']); ?></h3>
                                    <span class="chip">
                                        <i class="fas fa-clock"></i>
                                        <?php echo $daysLeft; ?> días
                                    </span>
                                </div>
                                
                                <p class="card-subtitle" style="margin-bottom: 16px;">
                                    <?php echo htmlspecialchars(substr($challenge['description'], 0, 100)) . '...'; ?>
                                </p>
                                
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                                    <span style="font-size: 14px; color: var(--gray-medium);">
                                        <?php echo $progress; ?>% completado
                                    </span>
                                    <a href="progress.php?challenge=<?php echo $challenge['id']; ?>" class="btn btn-primary" style="padding: 8px 16px; font-size: 14px;">
                                        <i class="fas fa-plus"></i>
                                        Registrar actividad
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Actividades recientes y gráfico -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <!-- Actividades recientes -->
            <div style="background: var(--white); border-radius: var(--border-radius); padding: 24px;">
                <h3 style="font-size: 18px; font-weight: 700; color: var(--dark-color); margin-bottom: 24px;">
                    Actividades Recientes
                </h3>
                
                <?php if (empty($recentActivities)): ?>
                    <div class="empty-state">
                        <p class="empty-state-text">No hay actividades registradas aún</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <?php foreach ($recentActivities as $activity): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 16px; border-bottom: 1px solid var(--border-color);">
                                <div>
                                    <h4 style="font-size: 16px; font-weight: 600; color: var(--dark-color); margin-bottom: 4px;">
                                        <?php echo htmlspecialchars($activity['activity_type']); ?>
                                    </h4>
                                    <p style="font-size: 14px; color: var(--gray-medium);">
                                        <?php echo htmlspecialchars($activity['challenge_name']); ?>
                                    </p>
                                </div>