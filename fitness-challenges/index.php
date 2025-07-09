<?php
require_once 'includes/functions.php';

// Si el usuario está logueado, redirigir al dashboard
if (isLoggedIn()) {
    redirect('pages/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness Challenge - Transforma tu vida</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Estilos específicos para la página de inicio */
        .hero {
            background: linear-gradient(to bottom, rgba(255,255,255,0) 0%, rgba(255,255,255,1) 100%),
                        url('https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=1600&h=800&fit=crop') center/cover;
            min-height: 85vh;
            display: flex;
            align-items: center;
            position: relative;
        }
        
        .hero-content {
            max-width: 600px;
        }
        
        .hero h1 {
            font-size: 56px;
            font-weight: 800;
            color: var(--dark-color);
            margin-bottom: 24px;
            line-height: 1.1;
        }
        
        .hero p {
            font-size: 20px;
            color: var(--gray-dark);
            margin-bottom: 32px;
            line-height: 1.5;
        }
        
        .search-box {
            background: var(--white);
            border-radius: 32px;
            box-shadow: var(--shadow-lg);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            max-width: 500px;
            margin-bottom: 32px;
        }
        
        .search-box input {
            border: none;
            outline: none;
            flex: 1;
            font-size: 16px;
        }
        
        .search-box button {
            background: var(--primary-color);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 24px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .search-box button:hover {
            background: var(--primary-hover);
        }
        
        .category-section {
            padding: 80px 0;
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 48px;
        }
        
        .category-card {
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border-radius: var(--border-radius);
        }
        
        .category-card:hover {
            transform: scale(1.02);
        }
        
        .category-card img {
            width: 100%;
            height: 320px;
            object-fit: cover;
        }
        
        .category-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, transparent 60%, rgba(0,0,0,0.8) 100%);
            display: flex;
            align-items: flex-end;
            padding: 24px;
        }
        
        .category-title {
            color: white;
            font-size: 24px;
            font-weight: 700;
        }
        
        .cta-banner {
            background: var(--primary-color);
            color: white;
            padding: 80px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .cta-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }
        
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 36px;
            }
            .hero p {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar container-fluid">
            <a href="/" class="logo" style="font-size: 28px;">
                <i class="fas fa-dumbbell"></i>
                <span>Fitness</span>
            </a>
            
            <div class="nav-right">
                <a href="pages/login.php" class="btn btn-ghost" style="margin-right: 16px;">
                    Iniciar sesión
                </a>
                <a href="pages/register.php" class="btn btn-primary">
                    Registrarse
                </a>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content fade-in">
                <h1>
                    Encuentra tu desafío fitness perfecto
                </h1>
                <p>
                    Únete a miles de personas que están transformando sus vidas con desafíos diseñados para todos los niveles
                </p>
                
                <div class="search-box">
                    <i class="fas fa-search" style="color: var(--gray-medium);"></i>
                    <input type="text" placeholder="Busca desafíos: cardio, fuerza, yoga...">
                    <button>Buscar</button>
                </div>
                
                <div style="display: flex; gap: 16px; align-items: center;">
                    <a href="pages/register.php" class="btn btn-primary" style="padding: 16px 32px;">
                        <i class="fas fa-rocket"></i>
                        Comenzar gratis
                    </a>
                    <a href="#categories" class="btn btn-ghost">
                        Explorar desafíos
                        <i class="fas fa-arrow-down"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Categorías -->
    <section id="categories" class="category-section">
        <div class="container">
            <h2 style="font-size: 40px; font-weight: 800; text-align: center; margin-bottom: 16px;">
                Explora por categoría
            </h2>
            <p style="text-align: center; color: var(--gray-medium); font-size: 18px;">
                Encuentra el tipo de entrenamiento que mejor se adapte a tus objetivos
            </p>
            
            <div class="category-grid">
                <div class="category-card">
                    <img src="https://images.unsplash.com/photo-1538805060514-97d9cc17730c?w=400&h=400&fit=crop" alt="Cardio">
                    <div class="category-overlay">
                        <div>
                            <h3 class="category-title">Cardio</h3>
                            <p style="color: rgba(255,255,255,0.8); font-size: 14px;">Mejora tu resistencia</p>
                        </div>
                    </div>
                </div>
                
                <div class="category-card">
                    <img src="https://images.unsplash.com/photo-1530822847156-5df684ec5ee1?w=400&h=400&fit=crop" alt="Fuerza">
                    <div class="category-overlay">
                        <div>
                            <h3 class="category-title">Fuerza</h3>
                            <p style="color: rgba(255,255,255,0.8); font-size: 14px;">Desarrolla músculo</p>
                        </div>
                    </div>
                </div>
                
                <div class="category-card">
                    <img src="https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=400&h=400&fit=crop" alt="Yoga">
                    <div class="category-overlay">
                        <div>
                            <h3 class="category-title">Yoga & Flexibilidad</h3>
                            <p style="color: rgba(255,255,255,0.8); font-size: 14px;">Encuentra tu equilibrio</p>
                        </div>
                    </div>
                </div>
                
                <div class="category-card">
                    <img src="https://images.unsplash.com/photo-1549060279-7e168fcee0c2?w=400&h=400&fit=crop" alt="HIIT">
                    <div class="category-overlay">
                        <div>
                            <h3 class="category-title">HIIT</h3>
                            <p style="color: rgba(255,255,255,0.8); font-size: 14px;">Entrenamientos intensivos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Estadísticas -->
    <section style="padding: 80px 0; background: var(--background);">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 48px; text-align: center;">
                <div>
                    <h3 class="stats-number" style="font-size: 48px;">5,000+</h3>
                    <p style="color: var(--gray-medium);">Usuarios activos</p>
                </div>
                <div>
                    <h3 class="stats-number" style="font-size: 48px;">50+</h3>
                    <p style="color: var(--gray-medium);">Desafíos disponibles</p>
                </div>
                <div>
                    <h3 class="stats-number" style="font-size: 48px;">2.5M+</h3>
                    <p style="color: var(--gray-medium);">Calorías quemadas</p>
                </div>
                <div>
                    <h3 class="stats-number" style="font-size: 48px;">98%</h3>
                    <p style="color: var(--gray-medium);">Usuarios satisfechos</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Banner -->
    <section class="cta-banner">
        <div class="container" style="position: relative; z-index: 1;">
            <h2 style="font-size: 48px; font-weight: 800; margin-bottom: 16px;">
                ¿Listo para transformar tu vida?
            </h2>
            <p style="font-size: 20px; margin-bottom: 32px; opacity: 0.9;">
                Comienza hoy mismo, es completamente gratis
            </p>
            <a href="pages/register.php" class="btn" style="background: white; color: var(--primary-color); padding: 16px 48px; font-size: 18px;">
                Crear cuenta gratis
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background: var(--dark-color); color: white; padding: 48px 0;">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 48px;">
                <div>
                    <div class="logo" style="color: white; font-size: 24px; margin-bottom: 16px;">
                        <i class="fas fa-dumbbell"></i>
                        <span>Fitness</span>
                    </div>
                    <p style="color: rgba(255,255,255,0.7);">
                        Tu compañero en el camino hacia una vida más saludable
                    </p>
                </div>
                <div>
                    <h4 style="margin-bottom: 16px;">Enlaces</h4>
                    <ul style="list-style: none;">
                        <li style="margin-bottom: 8px;"><a href="#" style="color: rgba(255,255,255,0.7); text-decoration: none;">Sobre nosotros</a></li>
                        <li style="margin-bottom: 8px;"><a href="#" style="color: rgba(255,255,255,0.7); text-decoration: none;">Desafíos</a></li>
                        <li style="margin-bottom: 8px;"><a href="#" style="color: rgba(255,255,255,0.7); text-decoration: none;">Blog</a></li>
                        <li style="margin-bottom: 8px;"><a href="#" style="color: rgba(255,255,255,0.7); text-decoration: none;">Contacto</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="margin-bottom: 16px;">Legal</h4>
                    <ul style="list-style: none;">
                        <li style="margin-bottom: 8px;"><a href="#" style="color: rgba(255,255,255,0.7); text-decoration: none;">Términos y condiciones</a></li>
                        <li style="margin-bottom: 8px;"><a href="#" style="color: rgba(255,255,255,0.7); text-decoration: none;">Política de privacidad</a></li>
                        <li style="margin-bottom: 8px;"><a href="#" style="color: rgba(255,255,255,0.7); text-decoration: none;">Cookies</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="margin-bottom: 16px;">Síguenos</h4>
                    <div style="display: flex; gap: 16px;">
                        <a href="#" style="color: white; font-size: 20px;"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="color: white; font-size: 20px;"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="color: white; font-size: 20px;"><i class="fab fa-twitter"></i></a>
                        <a href="#" style="color: white; font-size: 20px;"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            
            <div style="border-top: 1px solid rgba(255,255,255,0.1); margin-top: 48px; padding-top: 24px; text-align: center; color: rgba(255,255,255,0.5);">
                <p>&copy; 2024 Fitness Challenge. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Animación de categorías
        document.querySelectorAll('.category-card').forEach(card => {
            card.addEventListener('click', function() {
                window.location.href = 'pages/register.php';
            });
        });
    </script>
</body>
</html>