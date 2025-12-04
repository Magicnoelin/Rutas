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
    
    // Generar ID único
    $id = uniqid();
    
    // Sanitizar todos los datos
    $datosLimpios = [];
    foreach ($data as $key => $value) {
        $datosLimpios[$key] = sanitizeInput($value);
    }
    
    $pdo = getDBConnection();
    
    // Preparar datos para accommodations (tabla principal)
    $accData = [
        'id' => $id,
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
        'status' => 'pending'
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
        'estado' => 'pending',
        'recaptcha_score' => $recaptchaResult['score']
    ];

    jsonSuccess($response, '¡Alojamiento guardado exitosamente en la base de datos! Tu alojamiento está pendiente de verificación y pago para ser publicado.');
    
} catch (PDOException $e) {
    jsonError('Error al crear alojamiento: ' . $e->getMessage(), 500);
}
