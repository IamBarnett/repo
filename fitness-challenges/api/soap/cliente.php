<?php
// Cliente SOAP de ejemplo para probar el servicio

try {
    // Configurar el cliente SOAP
    $options = [
        'location' => 'http://localhost/fitness-challenges/api/soap/server.php',
        'uri' => 'http://localhost/fitness-challenges/api/soap/',
        'trace' => 1,
        'exceptions' => true
    ];
    
    $client = new SoapClient(null, $options);
    
    // Ejemplo 1: Obtener información de ejercicios
    echo "<h2>Información de Ejercicios de Cardio</h2>";
    $exercises = $client->getExerciseInfo('cardio');
    echo "<pre>";
    print_r($exercises);
    echo "</pre>";
    
    // Ejemplo 2: Obtener rutina de entrenamiento
    echo "<h2>Rutina para Pérdida de Peso - Principiante</h2>";
    $routine = $client->getWorkoutRoutines('weight_loss', 'beginner');
    echo "<pre>";
    print_r($routine);
    echo "</pre>";
    
    // Ejemplo 3: Calcular calorías
    echo "<h2>Cálculo de Calorías</h2>";
    $calories = $client->calculateCalories('Correr', 30, 75);
    echo "<pre>";
    print_r($calories);
    echo "</pre>";
    
    // Ejemplo 4: Recomendaciones nutricionales
    echo "<h2>Recomendaciones Nutricionales</h2>";
    $nutrition = $client->getNutritionRecommendations('weight_loss', 75, 'moderate');
    echo "<pre>";
    print_r($nutrition);
    echo "</pre>";
    
} catch (SoapFault $e) {
    echo "Error SOAP: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>