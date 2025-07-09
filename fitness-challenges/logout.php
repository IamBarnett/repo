<?php
require_once 'includes/auth.php';

// Cerrar sesión
logoutUser();

// Redirigir a la página de inicio
redirect('index.php');
?>