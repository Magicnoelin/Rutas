<?php
/**
 * API Endpoint: Crear Nuevo Evento Cultural
 * POST /api/crear_evento.php
 * Body: JSON con los datos del evento
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
    error_log('Received JSON: ' . $json);
    error_log('Decoded data: ' . json_encode($data));

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
    $camposRequeridos = ['title', 'description', 'event_date', 'location', 'category'];
    foreach ($camposRequeridos as $campo) {
        if (!isset($data[$campo]) || empty(trim($data[$campo]))) {
            jsonError("El campo '$campo' es requerido", 400);
        }
    }

    // Validar email si se proporciona
    if (!empty($data['contact_email']) && !isValidEmail($data['contact_email'])) {
        jsonError('Email de contacto inválido', 400);
    }

    // Sanitizar todos los datos
    $datosLimpios = [];
    foreach ($data as $key => $value) {
        $datosLimpios[$key] = sanitizeInput($value);
    }

    $pdo = getDBConnection();

    // Usar la tabla 'cultural_events' que ya existe con la estructura correcta
    $tableName = 'cultural_events';

    // Preparar datos para cultural_events usando las columnas correctas de la tabla
    // NOTA: No incluir 'id' porque es AUTO_INCREMENT
    $eventData = [
        'name' => $datosLimpios['title'] ?? '',
        'description' => $datosLimpios['description'] ?? '',
        'start_date' => $datosLimpios['event_date'] ?? '',
        'start_time' => $datosLimpios['event_time'] ?? null,
        'venue_name' => $datosLimpios['location'] ?? '',
        'category_id' => 1, // TODO: Mapear categoría a ID numérico
        'email' => $datosLimpios['contact_email'] ?? null,
        'phone' => $datosLimpios['contact_phone'] ?? null,
        'website' => $datosLimpios['website'] ?? null,
        'ticket_price' => isset($datosLimpios['price']) && $datosLimpios['price'] !== '' ? floatval($datosLimpios['price']) : null,
        'capacity' => isset($datosLimpios['capacity']) && $datosLimpios['capacity'] !== '' ? intval($datosLimpios['capacity']) : null,
        'status' => 'scheduled', // Estado por defecto de la tabla
        'organizer' => $datosLimpios['organizer'] ?? null,
        'municipality' => 'Soria' // Valor por defecto
    ];

    // Construir query INSERT para cultural_events
    $columnas = array_keys($eventData);
    $placeholders = array_map(function($col) { return ":$col"; }, $columnas);

    $sql = "INSERT INTO cultural_events (" . implode(', ', $columnas) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);

    // Bind valores
    foreach ($eventData as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }

    $stmt->execute();

    // Obtener el ID del evento recién creado
    $id = $pdo->lastInsertId();

    // Obtener el evento recién creado
    $sqlSelect = "SELECT * FROM cultural_events WHERE id = :id";
    $stmtSelect = $pdo->prepare($sqlSelect);
    $stmtSelect->bindValue(':id', $id);
    $stmtSelect->execute();
    $nuevoEvento = $stmtSelect->fetch();

    $response = [
        'id' => $nuevoEvento['id'],
        'title' => $nuevoEvento['name'],
        'category' => $nuevoEvento['category_id'],
        'event_date' => $nuevoEvento['start_date'],
        'status' => $nuevoEvento['status'],
        'recaptcha_score' => $recaptchaResult['score']
    ];

    jsonSuccess($response, '¡Evento cultural guardado exitosamente en la base de datos!');

} catch (PDOException $e) {
    // Log detailed error for debugging
    error_log('Database Error in crear_evento.php: ' . $e->getMessage());
    error_log('SQL State: ' . $e->getCode());
    error_log('Event Data: ' . json_encode($eventData ?? []));

    // Temporary detailed error for debugging
    jsonError('Error al guardar el evento: ' . $e->getMessage() . ' (Debug: ' . $e->getCode() . ')', 500);
}
