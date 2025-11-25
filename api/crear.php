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
    
    // Preparar campos para INSERT
    $campos = [
        'ID', 'Responsable', 'Nickname', 'NRegistro', 'Tipo', 'Nombre', 
        'Direccion', 'Latitud', 'Longitud', 'Telefono1', 'Telefono2', 
        'Email', 'Plazas', 'Discapacitados', 'Precio', 'Contactado',
        'Notaspublicas', 'Notasprivadas', 'Foto1', 'Foto2', 'Foto3', 
        'Foto4', 'Web', 'Airbnb', 'Booking', 'Instagram', 'Usuarioinsta',
        'Google', 'Facebook'
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
        $valor = isset($datosLimpios[$campo]) ? $datosLimpios[$campo] : null;
        $stmt->bindValue(':' . $campo, $valor);
    }
    
    $stmt->execute();
    
    // Obtener el alojamiento recién creado
    $sqlSelect = "SELECT * FROM " . DB_TABLE . " WHERE ID = :id";
    $stmtSelect = $pdo->prepare($sqlSelect);
    $stmtSelect->bindValue(':id', $id);
    $stmtSelect->execute();
    $nuevoAlojamiento = $stmtSelect->fetch();
    
    jsonSuccess($nuevoAlojamiento, 'Alojamiento creado correctamente');
    
} catch (PDOException $e) {
    jsonError('Error al crear alojamiento: ' . $e->getMessage(), 500);
}
