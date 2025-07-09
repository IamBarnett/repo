<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Verificar autenticación
requireAuth();

$user = getCurrentUser();
$stats = getUserStatistics($user['id']);

$pdo = getConnection();

// Obtener estadísticas detalladas
// Progreso mensual
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(a.activity_date, '%Y-%m') as month,
        COUNT(DISTINCT a.id) as activities,
        SUM(a.duration) as total_duration,
        SUM(a.calories_burned) as total_calories
    FROM activities a
    JOIN user_challenges uc ON a.user_challenge_id = uc.id
    WHERE uc.user_id = ?
    GROUP BY DATE_FORMAT(a.activity_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
$stmt->execute([$user['id']]);
$monthlyProgress = array_reverse($stmt->fetchAll());

// Top 5 días con más actividad
$stmt = $pdo->prepare("
    SELECT 
        a.activity_date,
        COUNT(*) as activity_count,
        SUM(a.duration) as total_duration,
        SUM(a.calories_burned) as total_calories
    FROM activities a
    JOIN user_challenges uc ON a.user_challenge_id = uc.id
    WHERE uc.user_id = ?
    GROUP BY a.activity_date
    ORDER BY total_calories DESC
    LIMIT 5
");
$stmt->execute([$user['id']]);
$topDays = $stmt->fetchAll();

// Desafíos completados vs abandonados
$stmt = $pdo->prepare("
    SELECT 
        status,
        COUNT(*) as count
    FROM user_challenges
    WHERE user_id = ?
    GROUP BY status
");
$stmt->execute([$user['id']]);
$challengeStats = $stmt->fetchAll();

// Actividad por día de la semana
$stmt = $pdo->prepare("
    SELECT 
        DAYNAME(a.activity_date) as day_name,
        DAYOFWEEK(a.activity_date) as day_num,
        COUNT(*) as activity_count,
        AVG(a.duration) as avg_duration
    FROM activities a
    JOIN user_challenges uc ON a.user_challenge_id = uc.id
    WHERE uc.user_id = ?
    GROUP BY DAYOFWEEK(a.activity_date)
    ORDER BY DAYOFWEEK(a.activity_date)
");
$stmt->execute([$user['id']]);
$weekdayStats = $stmt->fetchAll();

// Evolución del peso (si se registrara)
// Progreso en tipos de actividad específicos
$stmt = $pdo->prepare("
    SELECT 
        a.activity_type,
        MIN(a.activity_date) as first_activity,
        MAX(a.activity_date) as last_activity,
        COUNT(*) as total_sessions,
        AVG(a.duration) as avg_duration,
        MAX(a.duration) as max_duration
    FROM activities a
    JOIN user_challenges uc ON a.user_challenge_id = uc.id
    WHERE uc.user_id = ?
    GROUP BY a.activity_type
    ORDER BY total_sessions DESC
");
$stmt->execute([$user['id']]);
$activityProgress = $stmt->fetchAll();

// Configuración de visualización preferida (desde cookies)
$preferredView = getPreference('stats_view', 'charts');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Fitness Challenge</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar container">
            <a href="dashboard.php" class="logo">💪 Fitness Challenge</a>
            <ul class="nav-links">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="challenges.php"><i class="fas fa-trophy"></i> Desafíos</a></li>
                <li><a href="progress.php"><i class="fas fa-chart-line"></i> Progreso</a></li>
                <li><a href="statistics.php" class="active"><i class="fas fa-chart-bar"></i> Estadísticas</a></li>
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
                <i class="fas fa-chart-bar"></i> Estadísticas Detalladas
            </h1>
            <p style="color: #6b7280; font-size: 1.125rem;">
                Analiza tu rendimiento y mejora continuamente
            </p>
        </div>

        <!-- Resumen general -->
        <div class="stats-grid" style="margin-top: 2rem;">
            <div class="stat-card">
                <i class="fas fa-trophy" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 0.5rem;"></i>
                <div class="stat-number"><?php echo $stats['total_challenges']; ?></div>
                <div class="stat-label">Desafíos Totales</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-percentage" style="font-size: 2rem; color: var(--success-color); margin-bottom: 0.5rem;"></i>
                <div class="stat-number">
                    <?php 
                    $completionRate = $stats['total_challenges'] > 0 
                        ? round(($stats['completed_challenges'] / $stats['total_challenges']) * 100) 
                        : 0;
                    echo $completionRate;
                    ?>%
                </div>
                <div class="stat-label">Tasa de Completación</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-fire" style="font-size: 2rem; color: var(--danger-color); margin-bottom: 0.5rem;"></i>
                <div class="stat-number"><?php echo number_format($stats['total_calories_burned']); ?></div>
                <div class="stat-label">Calorías Totales</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-clock" style="font-size: 2rem; color: var(--warning-color); margin-bottom: 0.5rem;"></i>
                <div class="stat-number"><?php echo round($stats['total_workout_time'] / 60); ?>h</div>
                <div class="stat-label">Horas de Ejercicio</div>
            </div>
        </div>

        <!-- Gráficos principales -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
            <!-- Progreso mensual -->
            <div class="chart-container">
                <h3 style="margin-bottom: 1.5rem;">
                    <i class="fas fa-calendar-alt"></i> Progreso Mensual
                </h3>
                <canvas id="monthlyChart"></canvas>
            </div>
            
            <!-- Estado de desafíos -->
            <div class="chart-container">
                <h3 style="margin-bottom: 1.5rem;">
                    <i class="fas fa-tasks"></i> Estado de Desafíos
                </h3>
                <canvas id="challengeStatusChart"></canvas>
            </div>
        </div>

        <!-- Actividad por día de la semana -->
        <div class="chart-container" style="margin-top: 2rem;">
            <h3 style="margin-bottom: 1.5rem;">
                <i class="fas fa-calendar-week"></i> Actividad por Día de la Semana
            </h3>
            <canvas id="weekdayChart" style="max-height: 300px;"></canvas>
        </div>

        <!-- Tablas de datos -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
            <!-- Top días -->
            <div class="card">
                <h3 style="margin-bottom: 1rem;">
                    <i class="fas fa-medal"></i> Top 5 Días Más Activos
                </h3>
                <?php if (empty($topDays)): ?>
                    <p style="text-align: center; color: #6b7280;">No hay datos disponibles</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid #e5e7eb;">
                                    <th style="padding: 0.75rem; text-align: left;">Fecha</th>
                                    <th style="padding: 0.75rem; text-align: center;">Actividades</th>
                                    <th style="padding: 0.75rem; text-align: center;">Tiempo</th>
                                    <th style="padding: 0.75rem; text-align: center;">Calorías</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topDays as $index => $day): ?>
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 0.75rem;">
                                            <span style="display: flex; align-items: center; gap: 0.5rem;">
                                                <?php if ($index === 0): ?>
                                                    <i class="fas fa-trophy" style="color: #fbbf24;"></i>
                                                <?php elseif ($index === 1): ?>
                                                    <i class="fas fa-medal" style="color: #cbd5e1;"></i>
                                                <?php elseif ($index === 2): ?>
                                                    <i class="fas fa-medal" style="color: #f59e0b;"></i>
                                                <?php endif; ?>
                                                <?php echo formatDate($day['activity_date']); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center;"><?php echo $day['activity_count']; ?></td>
                                        <td style="padding: 0.75rem; text-align: center;"><?php echo $day['total_duration']; ?> min</td>
                                        <td style="padding: 0.75rem; text-align: center; font-weight: 600; color: var(--danger-color);">
                                            <?php echo number_format($day['total_calories']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Progreso por tipo de actividad -->
            <div class="card">
                <h3 style="margin-bottom: 1rem;">
                    <i class="fas fa-dumbbell"></i> Progreso por Actividad
                </h3>
                <?php if (empty($activityProgress)): ?>
                    <p style="text-align: center; color: #6b7280;">No hay datos disponibles</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 2px solid #e5e7eb;">
                                    <th style="padding: 0.75rem; text-align: left;">Tipo</th>
                                    <th style="padding: 0.75rem; text-align: center;">Sesiones</th>
                                    <th style="padding: 0.75rem; text-align: center;">Promedio</th>
                                    <th style="padding: 0.75rem; text-align: center;">Máximo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activityProgress as $activity): ?>
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 0.75rem; font-weight: 600;">
                                            <?php echo htmlspecialchars($activity['activity_type']); ?>
                                        </td>
                                        <td style="padding: 0.75rem; text-align: center;"><?php echo $activity['total_sessions']; ?></td>
                                        <td style="padding: 0.75rem; text-align: center;"><?php echo round($activity['avg_duration']); ?> min</td>
                                        <td style="padding: 0.75rem; text-align: center; color: var(--primary-color); font-weight: 600;">
                                            <?php echo $activity['max_duration']; ?> min
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Opciones de exportación -->
        <div class="card" style="margin-top: 2rem; text-align: center;">
            <h3 style="margin-bottom: 1rem;">
                <i class="fas fa-download"></i> Exportar Datos
            </h3>
            <p style="color: #6b7280; margin-bottom: 1.5rem;">
                Descarga tus datos para análisis externo o respaldo
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button class="btn btn-primary" onclick="exportData('pdf')">
                    <i class="fas fa-file-pdf"></i> Exportar PDF
                </button>
                <button class="btn btn-secondary" onclick="exportData('csv')">
                    <i class="fas fa-file-csv"></i> Exportar CSV
                </button>
                <button class="btn btn-secondary" onclick="exportData('json')">
                    <i class="fas fa-file-code"></i> Exportar JSON
                </button>
            </div>
        </div>
    </main>

    <script src="../assets/js/main.js"></script>
    <script>
        // Datos para los gráficos
        const monthlyData = <?php echo json_encode($monthlyProgress); ?>;
        const challengeData = <?php echo json_encode($challengeStats); ?>;
        const weekdayData = <?php echo json_encode($weekdayStats); ?>;
        
        // Gráfico de progreso mensual
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => {
                    const [year, month] = d.month.split('-');
                    return new Date(year, month - 1).toLocaleDateString('es-MX', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Calorías',
                    data: monthlyData.map(d => d.total_calories),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    yAxisID: 'y',
                    tension: 0.4
                }, {
                    label: 'Actividades',
                    data: monthlyData.map(d => d.activities),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    yAxisID: 'y1',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Calorías'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Actividades'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                }
            }
        });
        
        // Gráfico de estado de desafíos
        const challengeCtx = document.getElementById('challengeStatusChart').getContext('2d');
        const challengeLabels = {
            'active': 'Activos',
            'completed': 'Completados',
            'abandoned': 'Abandonados'
        };
        const challengeColors = {
            'active': '#10b981',
            'completed': '#6366f1',
            'abandoned': '#ef4444'
        };
        
        new Chart(challengeCtx, {
            type: 'doughnut',
            data: {
                labels: challengeData.map(d => challengeLabels[d.status] || d.status),
                datasets: [{
                    data: challengeData.map(d => d.count),
                    backgroundColor: challengeData.map(d => challengeColors[d.status] || '#6b7280')
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Gráfico de actividad por día de la semana
        const weekdayCtx = document.getElementById('weekdayChart').getContext('2d');
        const weekdayLabels = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        const weekdayChartData = new Array(7).fill(0);
        
        weekdayData.forEach(day => {
            weekdayChartData[day.day_num - 1] = day.activity_count;
        });
        
        new Chart(weekdayCtx, {
            type: 'bar',
            data: {
                labels: weekdayLabels,
                datasets: [{
                    label: 'Actividades',
                    data: weekdayChartData,
                    backgroundColor: '#6366f1',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        
        // Guardar preferencia de visualización
        function saveViewPreference(view) {
            setCookie('stats_view', view, 30);
        }
    </script>
</body>
</html>