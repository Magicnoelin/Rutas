<?php
require_once 'config.php';

// Configuración de cabeceras para devolver JSON
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Desactivar visualización de errores HTML para no romper el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    // 1. Leer los datos enviados por el formulario
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido recibido');
    }

    if (!$data || empty($data['id'])) {
        throw new Exception('No se recibieron datos o falta el ID');
    }

    // 2. Conexión a la base de datos usando tus credenciales de config.php
    $pdo = getDBConnection();

    // 3. Preparar la consulta SQL para actualizar la tabla real (places_of_interest)
    $sql = "UPDATE places_of_interest SET 
            name = :name,
            municipality = :municipality,
            province = :province,
            description = :description,
            entry_fee = :entry_fee,
            visit_duration = :visit_duration,
            photo1 = :photo1,
            updated_at = NOW()
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);

    // 4. Vincular parámetros (Sanitizando los datos por seguridad)
    $stmt->bindValue(':name', sanitizeInput($data['name'] ?? ''));
    $stmt->bindValue(':municipality', sanitizeInput($data['municipality'] ?? ''));
    $stmt->bindValue(':province', sanitizeInput($data['province'] ?? ''));
    $stmt->bindValue(':description', sanitizeInput($data['description'] ?? ''));
    
    $entry_fee = isset($data['entry_fee']) && $data['entry_fee'] !== '' ? floatval($data['entry_fee']) : 0.00;
    $stmt->bindValue(':entry_fee', $entry_fee);

    $visit_duration = isset($data['visit_duration']) && $data['visit_duration'] !== '' ? intval($data['visit_duration']) : null;
    $stmt->bindValue(':visit_duration', $visit_duration, PDO::PARAM_INT);

    $stmt->bindValue(':photo1', sanitizeInput($data['photo1'] ?? ''));
    $stmt->bindValue(':id', intval($data['id']), PDO::PARAM_INT);

    // 5. Ejecutar y responder
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Lugar actualizado correctamente en la base de datos',
            'id' => $data['id']
        ]);
    } else {
        throw new Exception('No se pudo actualizar el registro en la base de datos');
    }

} catch (Exception $e) {
    // En caso de error, devolvemos un JSON con el error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>