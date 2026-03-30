<?php
/**
 * Test exacto de la petición POST que hace save-preferences.php
 */

// Mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Test POST - Save Preferences</h1>";

// Simular exactamente los datos que envía preferences.html
$jsonData = '{
    "user_id": 1,
    "travel_purpose": "relaxation",
    "accommodation_type": "rural_house",
    "budget_range": "200-500",
    "group_size": "2-4",
    "preferred_activities": ["hiking", "nature"],
    "preferred_locations": ["soria", "burgos"],
    "special_requirements": "Pet friendly",
    "notification_preferences": ["email", "sms"]
}';

echo "<h2>Datos JSON simulados:</h2>";
echo "<pre>$jsonData</pre>";

// Procesar como lo hace save-preferences.php
try {
    echo "<h2>Procesando como save-preferences.php...</h2>";
    echo "<pre>";

    // Paso 1: Decodificar JSON
    echo "1. Decodificando JSON...\n";
    $data = json_decode($jsonData, true);
    if (!$data) {
        throw new Exception('Datos JSON inválidos');
    }
    echo "✅ JSON decodificado correctamente\n";

    // Paso 2: Cargar config
    echo "2. Cargando config.php...\n";
    require_once 'config.php';
    echo "✅ config.php cargado\n";

    // Paso 3: Validar método POST
    echo "3. Validando método POST...\n";
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo "⚠️ Advertencia: Método no es POST (esto es normal en test)\n";
    } else {
        echo "✅ Método POST correcto\n";
    }

    // Paso 4: Validar user_id
    echo "4. Validando user_id...\n";
    if (!isset($data['user_id']) || !is_numeric($data['user_id'])) {
        throw new Exception('user_id requerido y debe ser numérico');
    }
    echo "✅ user_id válido: {$data['user_id']}\n";

    // Paso 5: Conectar a BD
    echo "5. Conectando a base de datos...\n";
    $pdo = getDBConnection();
    echo "✅ Conexión exitosa\n";

    // Paso 6: Verificar que el usuario existe
    echo "6. Verificando que el usuario existe...\n";
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE id = :user_id");
    $stmt->bindValue(':user_id', $data['user_id']);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        throw new Exception('Usuario no encontrado');
    }
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Usuario encontrado: {$user['first_name']} {$user['last_name']}\n";

    // Paso 7: Preparar datos para inserción
    echo "7. Preparando datos para inserción...\n";
    $preferencesData = [
        'user_id' => $data['user_id'],
        'travel_purpose' => $data['travel_purpose'] ?? null,
        'accommodation_type' => $data['accommodation_type'] ?? null,
        'budget_range' => $data['budget_range'] ?? null,
        'group_size' => $data['group_size'] ?? null,
        'preferred_activities' => isset($data['preferred_activities']) ? json_encode($data['preferred_activities']) : null,
        'preferred_locations' => isset($data['preferred_locations']) ? json_encode($data['preferred_locations']) : null,
        'special_requirements' => $data['special_requirements'] ?? null,
        'notification_preferences' => isset($data['notification_preferences']) ? json_encode($data['notification_preferences']) : null,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    echo "✅ Datos preparados\n";

    // Paso 8: Verificar si ya existen preferencias para este usuario
    echo "8. Verificando preferencias existentes...\n";
    $stmtCheck = $pdo->prepare("SELECT id FROM user_preferences WHERE user_id = :user_id");
    $stmtCheck->bindValue(':user_id', $data['user_id']);
    $stmtCheck->execute();

    $existingPreferences = $stmtCheck->rowCount() > 0;
    echo "✅ Preferencias existentes: " . ($existingPreferences ? "SÍ" : "NO") . "\n";

    // Paso 9: Insertar o actualizar preferencias
    echo "9. " . ($existingPreferences ? "Actualizando" : "Insertando") . " preferencias...\n";

    if ($existingPreferences) {
        // UPDATE
        $sql = "UPDATE user_preferences SET
                travel_purpose = :travel_purpose,
                accommodation_type = :accommodation_type,
                budget_range = :budget_range,
                group_size = :group_size,
                preferred_activities = :preferred_activities,
                preferred_locations = :preferred_locations,
                special_requirements = :special_requirements,
                notification_preferences = :notification_preferences,
                updated_at = :updated_at
                WHERE user_id = :user_id";

        $stmt = $pdo->prepare($sql);
        foreach ($preferencesData as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->execute();
        echo "✅ Preferencias actualizadas\n";

    } else {
        // INSERT
        $sql = "INSERT INTO user_preferences
                (user_id, travel_purpose, accommodation_type, budget_range, group_size,
                 preferred_activities, preferred_locations, special_requirements,
                 notification_preferences, updated_at)
                VALUES
                (:user_id, :travel_purpose, :accommodation_type, :budget_range, :group_size,
                 :preferred_activities, :preferred_locations, :special_requirements,
                 :notification_preferences, :updated_at)";

        $stmt = $pdo->prepare($sql);
        foreach ($preferencesData as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->execute();
        echo "✅ Preferencias insertadas con ID: " . $pdo->lastInsertId() . "\n";
    }

    echo "\n🎉 PREFERENCIAS GUARDADAS EXITOSAMENTE\n";

} catch (Exception $e) {
    echo "\n❌ ERROR EN EL PASO ANTERIOR:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
