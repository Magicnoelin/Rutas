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
    
    // Preparar campos para INSERT (agregando Estado)
    $campos = [
        'ID', 'Responsable', 'Nickname', 'NRegistro', 'Tipo', 'Nombre', 
        'Direccion', 'Latitud', 'Longitud', 'Telefono1', 'Telefono2', 
        'Email', 'Plazas', 'Discapacitados', 'Precio', 'Contactado',
        'Notaspublicas', 'Notasprivadas', 'Foto1', 'Foto2', 'Foto3', 
        'Foto4', 'Web', 'Airbnb', 'Booking', 'Instagram', 'Usuarioinsta',
        'Google', 'Facebook', 'Estado'
    ];
    
    // Construir query INSERT
    $camposStr = implode(', ', $campos);
    $placeholders = ':' . implode(', :', $campos);
    
    $sql = "INSERT INTO " . DB_TABLE . " ($camposStr) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    
    // Bind valores
    $stmt->bindValue(':ID', $id);
    foreach ($campos as $campo) {
        if ($campo === 'ID') continue;
        
        // Estado siempre es 'pendiente' para nuevos alojamientos
        if ($campo === 'Estado') {
            $stmt->bindValue(':Estado', 'pendiente');
            continue;
        }
        
        $valor = isset($datosLimpios[$campo]) ? $datosLimpios[$campo] : null;
        $stmt->bindValue(':' . $campo, $valor);
    }
    
    $stmt->execute();

    // También guardar en tabla accommodations si existe
    $sqlAccommodations = null;
    try {
        // Verificar si existe la tabla accommodations
        $stmtCheck = $pdo->query("SHOW TABLES LIKE 'accommodations'");
        if ($stmtCheck->rowCount() > 0) {
            // Preparar datos para accommodations
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

            // Generar SQL INSERT statement
            $columnas = implode(', ', array_keys($accData));
            $valores = implode(', ', array_map(function($valor) {
                return $valor === null ? 'NULL' : "'" . addslashes($valor) . "'";
            }, array_values($accData)));

            $updateParts = [];
            foreach (array_keys($accData) as $columna) {
                if ($columna !== 'id') {
                    $updateParts[] = "$columna = VALUES($columna)";
                }
            }
            $updateClause = implode(', ', $updateParts);

            $sqlAccommodations = "INSERT INTO accommodations ($columnas) VALUES ($valores) ON DUPLICATE KEY UPDATE $updateClause;";

            // Ejecutar INSERT en accommodations
            $stmtAcc = $pdo->prepare("INSERT INTO accommodations ($columnas) VALUES (" . str_repeat('?,', count($accData)-1) . "?) ON DUPLICATE KEY UPDATE $updateClause");
            $stmtAcc->execute(array_values($accData));
        }
    } catch (Exception $e) {
        // Si falla accommodations, continuar (no es crítico)
        error_log('Error guardando en accommodations: ' . $e->getMessage());
    }

    // Obtener el alojamiento recién creado
    $sqlSelect = "SELECT * FROM " . DB_TABLE . " WHERE ID = :id";
    $stmtSelect = $pdo->prepare($sqlSelect);
    $stmtSelect->bindValue(':id', $id);
    $stmtSelect->execute();
    $nuevoAlojamiento = $stmtSelect->fetch();

    $response = [
        'id' => $id,
        'nombre' => $nuevoAlojamiento['Nombre'],
        'estado' => 'pendiente',
        'recaptcha_score' => $recaptchaResult['score']
    ];

    // Incluir SQL generado si se creó
    if ($sqlAccommodations) {
        $response['sql_generated'] = $sqlAccommodations;
    }

    jsonSuccess($response, '¡Alojamiento guardado exitosamente! Tu alojamiento está pendiente de verificación y pago para ser publicado.');
    
} catch (PDOException $e) {
    jsonError('Error al crear alojamiento: ' . $e->getMessage(), 500);
}
