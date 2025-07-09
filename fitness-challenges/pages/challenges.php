<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Verificar autenticación
requireAuth();

$user = getCurrentUser();
$message = '';
$messageType = '';

// Manejar unirse a un desafío
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_challenge'])) {
    $challengeId = (int)$_POST['challenge_id'];
    $pdo = getConnection();
    
    // Verificar si ya está en este desafío
    $stmt = $pdo->prepare("
        SELECT id FROM user_challenges 
        WHERE user_id = ? AND challenge_id = ? AND status = 'active'
    ");
    $stmt->execute([$user['id'], $challengeId]);
    
    if ($stmt->fetch()) {
        $message = 'Ya estás participando en este desafío';
        $messageType = 'error';
    } else {
        // Unirse al desafío
        $stmt = $pdo->prepare("
            INSERT INTO user_challenges (user_id, challenge_id, start_date)
            VALUES (?, ?, CURDATE())
        ");
        $stmt->execute([$user['id'], $challengeId]);
        
        $message = '¡Te has unido al desafío exitosamente!';
        $messageType = 'success';
        
        // Actualizar estadísticas
        updateUserStatistics($user['id']);
    }
}

// Obtener todos los desafíos disponibles
$pdo = getConnection();
$stmt = $pdo->prepare("
    SELECT c.*, 
           COUNT(DISTINCT uc.user_id) as participants,
           (SELECT COUNT(*) FROM user_challenges 
            WHERE challenge_id = c.id AND user_id = ? AND status = 'active') as is_joined
    FROM challenges c
    LEFT JOIN user_challenges uc ON c.id = uc.challenge_id
    GROUP BY c.id
    ORDER BY c.created_at DESC
");
$stmt->execute([$user['id']]);
$challenges = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desafíos - Fitness Challenge</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <a href="dashboard.php">Dashboard</a>
                <a href="challenges.php" class="active">Desafíos</a>
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
        <!-- Título -->
        <div class="fade-in" style="margin-bottom: 32px;">
            <h1 style="font-size: 32px; font-weight: 800; color: var(--dark-color); margin-bottom: 8px;">
                Explora Desafíos
            </h1>
            <p style="color: var(--gray-medium); font-size: 16px;">
                Únete a desafíos diseñados para transformar tu vida y alcanzar tus metas fitness
            </p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 24px;">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check' : 'exclamation'; ?>-circle"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="pills-container">
            <button class="pill active" onclick="filterChallenges('all')">
                <i class="fas fa-th"></i> Todos
            </button>
            <button class="pill" onclick="filterChallenges('cardio')">
                <i class="fas fa-running"></i> Cardio
            </button>
            <button class="pill" onclick="filterChallenges('strength')">
                <i class="fas fa-dumbbell"></i> Fuerza
            </button>
            <button class="pill" onclick="filterChallenges('flexibility')">
                <i class="fas fa-spa"></i> Flexibilidad
            </button>
            <button class="pill" onclick="filterChallenges('mixed')">
                <i class="fas fa-random"></i> Mixto
            </button>
        </div>

        <!-- Grid de desafíos -->
        <div class="cards-grid">
            <?php foreach ($challenges as $challenge): 
                // Determinar categoría
                $category = 'mixed';
                $categoryIcon = 'fas fa-trophy';
                $categoryColor = '#FF385C';
                
                if (stripos($challenge['name'], 'cardio') !== false || stripos($challenge['name'], 'corr') !== false) {
                    $category = 'cardio';
                    $categoryIcon = 'fas fa-running';
                    $categoryColor = '#FF385C';
                } elseif (stripos($challenge['name'], 'fuerza') !== false || stripos($challenge['name'], 'pesa') !== false) {
                    $category = 'strength';
                    $categoryIcon = 'fas fa-dumbbell';
                    $categoryColor = '#8B5CF6';
                } elseif (stripos($challenge['name'], 'yoga') !== false || stripos($challenge['name'], 'flexibilidad') !== false) {
                    $category = 'flexibility';
                    $categoryIcon = 'fas fa-spa';
                    $categoryColor = '#00A699';
                }
                
                // Imagen placeholder basada en categoría
                $images = [
                    'cardio' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=400&h=400&fit=crop',
                    'strength' => 'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?w=400&h=400&fit=crop',
                    'flexibility' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=400&h=400&fit=crop',
                    'mixed' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=400&h=400&fit=crop'
                ];
                
                $imageUrl = $images[$category];
            ?>
                <div class="card challenge-item" data-category="<?php echo $category; ?>">
                    <div style="position: relative;">
                        <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($challenge['name']); ?>" class="card-image">
                        <div class="card-heart <?php echo $challenge['is_joined'] ? 'active' : ''; ?>">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                            <h3 class="card-title"><?php echo htmlspecialchars($challenge['name']); ?></h3>
                            <span class="chip" style="background: <?php echo $categoryColor; ?>15; color: <?php echo $categoryColor; ?>;">
                                <i class="<?php echo $categoryIcon; ?>"></i>
                                <?php echo $challenge['duration']; ?> días
                            </span>
                        </div>
                        
                        <p class="card-subtitle" style="margin-bottom: 12px;">
                            <?php echo htmlspecialchars($challenge['description']); ?>
                        </p>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div class="card-rating">
                                <i class="fas fa-users" style="color: var(--gray-medium);"></i>
                                <span><?php echo $challenge['participants']; ?> participantes</span>
                            </div>
                            
                            <?php if ($challenge['is_joined']): ?>
                                <button class="btn btn-secondary" disabled style="padding: 8px 16px; font-size: 14px;">
                                    <i class="fas fa-check"></i> Unido
                                </button>
                            <?php else: ?>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="challenge_id" value="<?php echo $challenge['id']; ?>">
                                    <button type="submit" name="join_challenge" class="btn btn-primary" style="padding: 8px 16px; font-size: 14px;">
                                        Unirme
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Menú desplegable del usuario -->
    <div id="userDropdown" style="display: none; position: absolute; top: 70px; right: 80px; background: var(--white); border-radius: var(--border-radius); box-shadow: var(--shadow-lg); padding: 8px 0; min-width: 200px; z-index: 1000;">
        <a href="profile.php" style="display: block; padding: 12px 16px; color: var(--dark-color); text-decoration: none; font-size: 14px;">
            <i class="fas fa-user-circle" style="margin-right: 12px; width: 16px;"></i>
            Mi Perfil
        </a>
        <a href="settings.php" style="display: block; padding: 12px 16px; color: var(--dark-color); text-decoration: none; font-size: 14px;">
            <i class="fas fa-cog" style="margin-right: 12px; width: 16px;"></i>
            Configuración
        </a>
        <div style="height: 1px; background: var(--border-color); margin: 8px 0;"></div>
        <a href="../logout.php" style="display: block; padding: 12px 16px; color: var(--dark-color); text-decoration: none; font-size: 14px;">
            <i class="fas fa-sign-out-alt" style="margin-right: 12px; width: 16px;"></i>
            Cerrar sesión
        </a>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // Menú de usuario
        document.querySelector('.nav-user-menu').addEventListener('click', function() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });

        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.nav-user-menu') && !e.target.closest('#userDropdown')) {
                document.getElementById('userDropdown').style.display = 'none';
            }
        });

        // Función para filtrar desafíos
        function filterChallenges(category) {
            const challenges = document.querySelectorAll('.challenge-item');
            const pills = document.querySelectorAll('.pill');
            
            // Actualizar botón activo
            pills.forEach(pill => pill.classList.remove('active'));
            event.target.classList.add('active');
            
            // Mostrar/ocultar desafíos
            challenges.forEach(challenge => {
                if (category === 'all' || challenge.dataset.category === category) {
                    challenge.style.display = 'block';
                } else {
                    challenge.style.display = 'none';
                }
            });
        }
        
        // Animar corazones
        document.querySelectorAll('.card-heart').forEach(heart => {
            heart.addEventListener('click', function(e) {
                e.preventDefault();
                this.classList.toggle('active');
            });
        });
    </script>
</body>
</html>