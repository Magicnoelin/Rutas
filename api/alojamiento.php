<?php
/**
 * API Endpoint: Obtener Un Alojamiento Específico
 * GET /api/alojamiento.php?id=XXX
 */

require_once 'config.php';

// Solo permitir método GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

// Verificar que se proporcione el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    jsonError('ID de alojamiento requerido', 400);
}

try {
    $pdo = getDBConnection();
    $id = sanitizeInput($_GET['id']);
    
    // Obtener alojamiento por ID
    $sql = "SELECT * FROM " . DB_TABLE . " WHERE ID = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    
    $alojamiento = $stmt->fetch();
    
    if (!$alojamiento) {
        jsonError('Alojamiento no encontrado', 404);
    }
    
    // Procesar datos
    $alojamiento['Plazas'] = intval($alojamiento['Plazas']);
    
    if (!empty($alojamiento['Precio'])) {
        $alojamiento['Precio'] = floatval($alojamiento['Precio']);
    }
    
    // Crear array de fotos
    $fotos = [];
    for ($i = 1; $i <= 4; $i++) {
        $fotoKey = 'Foto' . $i;
        if (!empty($alojamiento[$fotoKey])) {
            $fotos[] = $alojamiento[$fotoKey];
        }
    }
    $alojamiento['Fotos'] = $fotos;
    
    // Extraer localidad y provincia
    if (!empty($alojamiento['Direccion'])) {
        $partes = explode(' ', $alojamiento['Direccion']);
        $alojamiento['Localidad'] = '';
        $alojamiento['Provincia'] = '';
        
        if (count($partes) > 2) {
            $alojamiento['Provincia'] = $partes[count($partes) - 1];
            if (count($partes) > 3) {
                $alojamiento['Localidad'] = $partes[count($partes) - 2];
            }
        }
    }
    
    jsonSuccess($alojamiento, 'Alojamiento obtenido correctamente');
    
} catch (PDOException $e) {
    jsonError('Error al obtener alojamiento: ' . $e->getMessage(), 500);
