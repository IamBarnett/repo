// Funciones principales de la aplicación

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    initTooltips();
    
    // Inicializar gráficos si existen
    if (document.getElementById('caloriesChart')) {
        initCharts();
    }
    
    // Manejar formularios AJAX
    initAjaxForms();
    
    // Actualizar progreso automáticamente
    updateProgressBars();
    
    // Cargar preferencias del usuario
    loadUserPreferences();
});

// Función para manejar formularios con AJAX
function initAjaxForms() {
    const forms = document.querySelectorAll('.ajax-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const submitBtn = form.querySelector('[type="submit"]');
            const originalText = submitBtn.textContent;
            
            // Mostrar loading
            submitBtn.disabled = true;
            submitBtn.textContent = 'Procesando...';
            
            try {
                const response = await fetch(form.action, {
                    method: form.method,
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    }
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('Error al procesar la solicitud', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    });
}

// Función para mostrar alertas
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} fade-in`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.alert-container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    // Remover alerta después de 5 segundos
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Función para actualizar barras de progreso
function updateProgressBars() {
    const progressBars = document.querySelectorAll('.progress-fill');
    
    progressBars.forEach(bar => {
        const progress = bar.getAttribute('data-progress');
        setTimeout(() => {
            bar.style.width = progress + '%';
        }, 100);
    });
}

// Función para inicializar gráficos
function initCharts() {
    // Gráfico de calorías
    const caloriesCtx = document.getElementById('caloriesChart');
    if (caloriesCtx) {
        const caloriesChart = new Chart(caloriesCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: getLast7Days(),
                datasets: [{
                    label: 'Calorías quemadas',
                    data: [450, 320, 480, 390, 520, 480, 510],
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Gráfico de actividades
    const activitiesCtx = document.getElementById('activitiesChart');
    if (activitiesCtx) {
        const activitiesChart = new Chart(activitiesCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Cardio', 'Pesas', 'Yoga', 'Otros'],
                datasets: [{
                    data: [35, 30, 20, 15],
                    backgroundColor: [
                        '#6366f1',
                        '#8b5cf6',
                        '#10b981',
                        '#f59e0b'
                    ]
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
    }
}

// Función para obtener los últimos 7 días
function getLast7Days() {
    const days = [];
    const today = new Date();
    
    for (let i = 6; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        days.push(date.toLocaleDateString('es-MX', { weekday: 'short' }));
    }
    
    return days;
}

// Función para registrar actividad
async function registerActivity(challengeId) {
    const modal = document.getElementById('activityModal');
    modal.classList.add('show');
    
    // Configurar el formulario con el ID del desafío
    document.getElementById('challengeIdInput').value = challengeId;
}

// Función para cerrar modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('show');
}

// Función para filtrar desafíos
function filterChallenges(category) {
    const challenges = document.querySelectorAll('.challenge-card');
    
    challenges.forEach(challenge => {
        if (category === 'all' || challenge.dataset.category === category) {
            challenge.style.display = 'block';
        } else {
            challenge.style.display = 'none';
        }
    });
    
    // Actualizar botón activo
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
}

// Función para cargar preferencias del usuario
function loadUserPreferences() {
    // Obtener tema preferido
    const theme = getCookie('theme') || 'light';
    document.body.setAttribute('data-theme', theme);
    
    // Obtener vista preferida
    const view = getCookie('view') || 'grid';
    if (document.querySelector('.view-toggle')) {
        setViewMode(view);
    }
}

// Función para cambiar tema
function toggleTheme() {
    const currentTheme = document.body.getAttribute('data-theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    document.body.setAttribute('data-theme', newTheme);
    setCookie('theme', newTheme, 30);
}

// Función para cambiar modo de vista
function setViewMode(mode) {
    const container = document.querySelector('.challenges-container');
    if (container) {
        container.className = `challenges-container ${mode}-view`;
        setCookie('view', mode, 30);
    }
}

// Funciones para cookies
function setCookie(name, value, days) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = "expires=" + date.toUTCString();
    document.cookie = name + "=" + value + ";" + expires + ";path=/";
}

function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

// Función para actualizar estadísticas en tiempo real
async function updateRealTimeStats() {
    try {
        const response = await fetch('/api/rest/activities.php?action=stats');
        const data = await response.json();
        
        if (data.success) {
            // Actualizar contadores
            document.getElementById('totalChallenges').textContent = data.stats.total_challenges;
            document.getElementById('completedChallenges').textContent = data.stats.completed_challenges;
            document.getElementById('totalCalories').textContent = data.stats.total_calories;
            document.getElementById('totalTime').textContent = data.stats.total_time;
        }
    } catch (error) {
        console.error('Error actualizando estadísticas:', error);
    }
}

// Actualizar estadísticas cada 30 segundos
setInterval(updateRealTimeStats, 30000);

// Función para exportar datos
function exportData(format) {
    window.location.href = `/api/export.php?format=${format}`;
}

// Función para inicializar tooltips
function initTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.classList.add('tooltip');
    });
}

// Función para validar formularios
function validateForm(form) {
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
    });
    
    return isValid;
}

// Event listener para validación en tiempo real
document.addEventListener('input', function(e) {
    if (e.target.hasAttribute('required')) {
        if (e.target.value.trim()) {
            e.target.classList.remove('error');
        }
    }
});

// Función para mostrar/ocultar contraseña
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = event.target;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}