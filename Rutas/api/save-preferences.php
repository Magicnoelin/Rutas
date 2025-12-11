<?php
/**
 * API Endpoint: Guardar Preferencias de Usuario
 * POST /api/save-preferences.php
 * Body: JSON con las preferencias del usuario
 */

require_once 'config.php';

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    // Obtener datos del body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        jsonError('Datos JSON inválidos', 400);
    }

    // Verificar que el usuario esté autenticado (por ahora usamos una sesión simple)
    // En producción, esto debería usar JWT o sesiones PHP
    session_start();
    if (!isset($_SESSION['user_id'])) {
        jsonError('Usuario no autenticado', 401);
    }

    $userId = $_SESSION['user_id'];

    $pdo = getDBConnection();

    // Crear tabla user_preferences si no existe
    $sqlCheckTable = "SHOW TABLES LIKE 'user_preferences'";
    $result = $pdo->query($sqlCheckTable);
    $tableExists = $result->rowCount() > 0;

    if (!$tableExists) {
        $sqlCreateTable = "
            CREATE TABLE user_preferences (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                interests JSON,
                accommodation_types JSON,
                budget VARCHAR(20),
                group_size VARCHAR(20),
                trip_duration VARCHAR(20),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($sqlCreateTable);
    }

    // Verificar si ya existen preferencias para este usuario
    $sqlCheckPreferences = "SELECT id FROM user_preferences WHERE user_id = :user_id";
    $stmtCheck = $pdo->prepare($sqlCheckPreferences);
    $stmtCheck->bindValue(':user_id', $userId);
    $stmtCheck->execute();

    $preferencesExist = $stmtCheck->rowCount() > 0;

    // Preparar datos para inserción/actualización
    $preferencesData = [
        'user_id' => $userId,
        'interests' => json_encode($data['interests'] ?? []),
        'accommodation_types' => json_encode($data['accommodation_types'] ?? []),
        'budget' => $data['budget'] ?? null,
        'group_size' => $data['group_size'] ?? null,
        'trip_duration' => $data['trip_duration'] ?? null
    ];

    if ($preferencesExist) {
        // Actualizar preferencias existentes
        $columnas = array_keys($preferencesData);
        $setClause = implode(', ', array_map(function($col) { return "$col = :$col"; }, $columnas));

        $sql = "UPDATE user_preferences SET $setClause WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);

        foreach ($preferencesData as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();
        $message = 'Preferencias actualizadas exitosamente';
    } else {
        // Insertar nuevas preferencias
        $columnas = array_keys($preferencesData);
        $placeholders = array_map(function($col) { return ":$col"; }, $columnas);

        $sql = "INSERT INTO user_preferences (" . implode(', ', $columnas) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $pdo->prepare($sql);

        foreach ($preferencesData as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();
        $message = 'Preferencias guardadas exitosamente';
    }

    jsonSuccess([
        'user_id' => $userId,
        'preferences_saved' => true
    ], $message);

} catch (PDOException $e) {
    error_log('Save-preferences.php - Database Error: ' . $e->getMessage());
    jsonError('Error al guardar preferencias: ' . $e->getMessage(), 500);
}
