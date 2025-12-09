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

    if (!$data) {
        jsonError('Datos JSON inválidos', 400);
    }

    // Validar reCAPTCHA
    if (!isset($data['recaptchaToken'])) {
        jsonError('Token de reCAPTCHA no proporcionado', 400);
    }

    $recaptchaResult = validateRecaptcha($data['recaptchaToken']);
    if (!$recaptchaResult['success']) {
        jsonError($recaptchaResult['error'], 403);
    }

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

    // Generar ID único
    $id = uniqid('event_');

    // Sanitizar todos los datos
    $datosLimpios = [];
    foreach ($data as $key => $value) {
        $datosLimpios[$key] = sanitizeInput($value);
    }

    $pdo = getDBConnection();

    // Preparar datos para cultural_events
    $eventData = [
        'id' => $id,
        'title' => $datosLimpios['title'] ?? '',
        'description' => $datosLimpios['description'] ?? '',
        'event_date' => $datosLimpios['event_date'] ?? '',
        'event_time' => !empty($datosLimpios['event_time']) ? $datosLimpios['event_time'] : null,
        'location' => $datosLimpios['location'] ?? '',
        'category' => $datosLimpios['category'] ?? '',
        'image' => $datosLimpios['image'] ?? '',
        'organizer' => $datosLimpios['organizer'] ?? '',
        'contact_email' => $datosLimpios['contact_email'] ?? '',
        'contact_phone' => $datosLimpios['contact_phone'] ?? '',
        'website' => $datosLimpios['website'] ?? '',
        'price' => !empty($datosLimpios['price']) ? floatval($datosLimpios['price']) : null,
        'capacity' => !empty($datosLimpios['capacity']) ? intval($datosLimpios['capacity']) : null,
        'status' => 'active'
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

    // Obtener el evento recién creado
    $sqlSelect = "SELECT * FROM cultural_events WHERE id = :id";
    $stmtSelect = $pdo->prepare($sqlSelect);
    $stmtSelect->bindValue(':id', $id);
    $stmtSelect->execute();
    $nuevoEvento = $stmtSelect->fetch();

    $response = [
        'id' => $id,
        'title' => $nuevoEvento['title'],
        'category' => $nuevoEvento['category'],
        'event_date' => $nuevoEvento['event_date'],
        'status' => 'active',
        'recaptcha_score' => $recaptchaResult['score']
    ];

    jsonSuccess($response, '¡Evento cultural guardado exitosamente en la base de datos!');

} catch (PDOException $e) {
    jsonError('Error al crear evento: ' . $e->getMessage(), 500);
}
