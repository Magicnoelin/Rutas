<?php
/**
 * API Endpoint: Actualizar Alojamiento Existente
 * PUT /api/actualizar.php
 * Body: JSON con ID y campos a actualizar
 */

require_once 'config.php';

// Permitir métodos PUT y POST
if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    // Obtener datos del body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        jsonError('Datos JSON inválidos', 400);
    }
    
    // Verificar que se proporcione el ID
    if (!isset($data['ID']) || empty($data['ID'])) {
        jsonError('ID de alojamiento requerido', 400);
    }
    
    $id = sanitizeInput($data['ID']);
    $pdo = getDBConnection();
    
    // Verificar que el alojamiento existe
    $sqlCheck = "SELECT ID FROM " . DB_TABLE . " WHERE ID = :id";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->bindValue(':id', $id);
    $stmtCheck->execute();
    
    if (!$stmtCheck->fetch()) {
        jsonError('Alojamiento no encontrado', 404);
    }
    
    // Validar email si se proporciona
    if (!empty($data['Email']) && !isValidEmail($data['Email'])) {
        jsonError('Email inválido', 400);
    }
    
    // Campos permitidos para actualizar
    $camposPermitidos = [
        'Responsable', 'Nickname', 'NRegistro', 'Tipo', 'Nombre', 
        'Direccion', 'Latitud', 'Longitud', 'Telefono1', 'Telefono2', 
        'Email', 'Plazas', 'Discapacitados', 'Precio', 'Contactado',
        'Notaspublicas', 'Notasprivadas', 'Foto1', 'Foto2', 'Foto3', 
        'Foto4', 'Web', 'Airbnb', 'Booking', 'Instagram', 'Usuarioinsta',
        'Google', 'Facebook'
    ];
    
    // Construir SET clause dinámicamente
    $setClauses = [];
    $params = [':id' => $id];
    
    foreach ($camposPermitidos as $campo) {
        if (isset($data[$campo])) {
            $setClauses[] = "$campo = :$campo";
            $params[":$campo"] = sanitizeInput($data[$campo]);
        }
    }
    
    if (empty($setClauses)) {
        jsonError('No hay campos para actualizar', 400);
    }
    
    // Construir y ejecutar query UPDATE
    $setClause = implode(', ', $setClauses);
    $sql = "UPDATE " . DB_TABLE . " SET $setClause WHERE ID = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Obtener el alojamiento actualizado
    $sqlSelect = "SELECT * FROM " . DB_TABLE . " WHERE ID = :id";
    $stmtSelect = $pdo->prepare($sqlSelect);
    $stmtSelect->bindValue(':id', $id);
    $stmtSelect->execute();
    $alojamientoActualizado = $stmtSelect->fetch();
    
    jsonSuccess($alojamientoActualizado, 'Alojamiento actualizado correctamente');
    
} catch (PDOException $e) {
    jsonError('Error al actualizar alojamiento: ' . $e->getMessage(), 500);
}
