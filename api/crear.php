<?php
/**
 * API Endpoint: Crear Nuevo Alojamiento
 * POST /api/crear.php
 * Body: JSON con los datos del alojamiento
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

    // Debug: Log received data
    error_log('Crear.php - Received JSON: ' . $json);
    error_log('Crear.php - Decoded data: ' . json_encode($data));

    if (!$data) {
        jsonError('Datos JSON inválidos', 400);
    }
    
    // Validar reCAPTCHA (temporalmente deshabilitado para debugging)
    /*
    if (!isset($data['recaptchaToken'])) {
        jsonError('Token de reCAPTCHA no proporcionado', 400);
    }

    $recaptchaResult = validateRecaptcha($data['recaptchaToken']);
    if (!$recaptchaResult['success']) {
        jsonError($recaptchaResult['error'], 403);
    }
    */
    $recaptchaResult = ['success' => true, 'score' => 1.0]; // Simulado
    
    // Validar campos requeridos
    $camposRequeridos = ['Nombre', 'Tipo', 'Direccion'];
    foreach ($camposRequeridos as $campo) {
        if (!isset($data[$campo]) || empty(trim($data[$campo]))) {
            jsonError("El campo '$campo' es requerido", 400);
        }
    }
    
    // Validar email si se proporciona
    if (!empty($data['Email']) && !isValidEmail($data['Email'])) {
        jsonError('Email inválido', 400);
    }
    
    // Sanitizar todos los datos
    $datosLimpios = [];
    foreach ($data as $key => $value) {
        $datosLimpios[$key] = sanitizeInput($value);
    }

    $pdo = getDBConnection();

    // Verificar y crear tabla accommodations si no existe
    $sqlCheckTable = "SHOW TABLES LIKE 'accommodations'";
    $result = $pdo->query($sqlCheckTable);
    if ($result->rowCount() == 0) {
        // Crear tabla accommodations
        $sqlCreateTable = "
            CREATE TABLE accommodations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(100),
                address TEXT,
                capacity INT DEFAULT 0,
                price DECIMAL(10,2),
                description TEXT,
                phone VARCHAR(50),
                email VARCHAR(255),
                website VARCHAR(255),
                image1 VARCHAR(500),
                image2 VARCHAR(500),
                image3 VARCHAR(500),
                image4 VARCHAR(500),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                status ENUM('active', 'inactive') DEFAULT 'active'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($sqlCreateTable);
    }

    // Preparar datos para accommodations (sin ID ya que es AUTO_INCREMENT)
    $accData = [
        'name' => $datosLimpios['Nombre'] ?? '',
        'type' => $datosLimpios['Tipo'] ?? '',
        'address' => $datosLimpios['Direccion'] ?? '',
        'capacity' => intval($datosLimpios['Plazas'] ?? 0),
        'price' => !empty($datosLimpios['Precio']) ? floatval($datosLimpios['Precio']) : null,
        'description' => $datosLimpios['Notaspublicas'] ?? '',
        'phone' => $datosLimpios['Telefono1'] ?? '',
        'email' => $datosLimpios['Email'] ?? '',
        'website' => $datosLimpios['Web'] ?? '',
        'image1' => $datosLimpios['Foto1'] ?? '',
        'image2' => $datosLimpios['Foto2'] ?? '',
        'image3' => $datosLimpios['Foto3'] ?? '',
        'image4' => $datosLimpios['Foto4'] ?? '',
        'status' => 'active' // Cambiado de 'pending' a 'active' según la tabla
    ];

    // Construir query INSERT para accommodations
    $columnas = array_keys($accData);
    $placeholders = array_map(function($col) { return ":$col"; }, $columnas);

    $sql = "INSERT INTO accommodations (" . implode(', ', $columnas) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);

    // Bind valores
    foreach ($accData as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }

    $stmt->execute();

    // Obtener el ID del alojamiento recién creado
    $id = $pdo->lastInsertId();

    // Obtener el alojamiento recién creado
    $sqlSelect = "SELECT * FROM accommodations WHERE id = :id";
    $stmtSelect = $pdo->prepare($sqlSelect);
    $stmtSelect->bindValue(':id', $id);
    $stmtSelect->execute();
    $nuevoAlojamiento = $stmtSelect->fetch();

    $response = [
        'id' => $id,
        'nombre' => $nuevoAlojamiento['name'],
        'tipo' => $nuevoAlojamiento['type'],
        'estado' => $nuevoAlojamiento['status'],
        'recaptcha_score' => $recaptchaResult['score']
    ];

    jsonSuccess($response, '¡Alojamiento guardado exitosamente en la base de datos! Tu alojamiento está activo y visible en la plataforma.');
    
} catch (PDOException $e) {
    // Log detailed error for debugging
    error_log('Crear.php - Database Error: ' . $e->getMessage());
    error_log('Crear.php - SQL State: ' . $e->getCode());
    error_log('Crear.php - Event Data: ' . json_encode($accData ?? []));

    // Temporary detailed error for debugging
    jsonError('Error al crear alojamiento: ' . $e->getMessage() . ' (Debug: ' . $e->getCode() . ')', 500);
}
