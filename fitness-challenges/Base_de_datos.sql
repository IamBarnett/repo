-- Crear base de datos
CREATE DATABASE IF NOT EXISTS fitness_challenges;
USE fitness_challenges;

-- Tabla de usuarios
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de desafíos
CREATE TABLE challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    duration INT NOT NULL, -- en días
    objectives TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de relación usuario-desafío
CREATE TABLE user_challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    challenge_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    status ENUM('active', 'completed', 'abandoned') DEFAULT 'active',
    progress INT DEFAULT 0, -- porcentaje de progreso
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (challenge_id) REFERENCES challenges(id)
);

-- Tabla de actividades
CREATE TABLE activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_challenge_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL, -- correr, levantar pesas, etc.
    duration INT NOT NULL, -- en minutos
    calories_burned INT,
    notes TEXT,
    activity_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_challenge_id) REFERENCES user_challenges(id)
);

-- Tabla de estadísticas
CREATE TABLE statistics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_challenges INT DEFAULT 0,
    completed_challenges INT DEFAULT 0,
    total_calories_burned INT DEFAULT 0,
    total_workout_time INT DEFAULT 0, -- en minutos
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insertar desafíos predefinidos
INSERT INTO challenges (name, description, duration, objectives) VALUES
('Reto de 30 días de Cardio', 'Mejora tu resistencia cardiovascular con 30 días de ejercicio aeróbico', 30, 'Correr 30 minutos diarios, Alcanzar 10km sin parar, Mejorar tiempo en 5km'),
('Fuerza Total - 21 días', 'Desarrolla fuerza muscular con rutinas de pesas durante 21 días', 21, 'Aumentar peso en press banca, Hacer 50 flexiones seguidas, Mejorar técnica'),
('Yoga y Flexibilidad', 'Mejora tu flexibilidad y equilibrio con yoga diario', 30, 'Tocar los pies sin doblar rodillas, Mantener posturas por 1 minuto, Reducir estrés'),
('Reto Abdominales de Acero', 'Consigue un core fuerte en 15 días', 15, 'Plancha 3 minutos, 100 abdominales seguidos, Reducir grasa abdominal'),
('Transformación Total', 'Combina cardio, fuerza y flexibilidad por 60 días', 60, 'Perder 5kg, Ganar masa muscular, Mejorar salud general');