<?php
/**
 * API Endpoint: Check if accommodation is in eclipse region
 * GET /api/check_eclipse_region.php?accommodation_id=123
 */

require_once 'config.php';

// Only allow GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Method not allowed', 405);
}

try {
    $pdo = getDBConnection();
    
    // Get accommodation ID from parameter
    $accommodationId = isset($_GET['accommodation_id']) ? (int)$_GET['accommodation_id'] : 0;
    
    if ($accommodationId <= 0) {
        jsonError('Valid accommodation_id required', 400);
    }
    
    // Check if regions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'regions'");
    $regionsTableExists = $stmt->rowCount() > 0;
    
    if (!$regionsTableExists) {
        // Fallback to old logic - check if accommodation is in hardcoded eclipse provinces
        $stmt = $pdo->prepare("SELECT province FROM accommodations WHERE id = ?");
        $stmt->execute([$accommodationId]);
        $accommodation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$accommodation) {
            jsonSuccess(['has_eclipse' => false, 'message' => 'Accommodation not found']);
        }
        
        // Hardcoded eclipse provinces (old logic)
        $eclipseProvinces = [
            'A Coruña', 'La Coruña', 'Almería', 'Cádiz', 'Córdoba', 'Huelva', 'Jaén', 'Málaga', 'Sevilla',
            'Huesca', 'Teruel', 'Zaragoza',
            'Asturias', 'Oviedo', 'Gijón',
            'Islas Baleares', 'Baleares', 'Mallorca', 'Menorca', 'Ibiza',
            'Álava', 'Vizcaya', 'Guipúzcoa', 'Bizkaia', 'Gipuzkoa', 'País Vasco', 'Bilbao', 'San Sebastián',
            'Cantabria', 'Santander',
            'Albacete', 'Ciudad Real', 'Cuenca', 'Guadalajara', 'Toledo', 'Castilla-La Mancha',
            'Ávila', 'Burgos', 'León', 'Palencia', 'Salamanca', 'Segovia', 'Soria', 'Valladolid', 'Zamora', 'Castilla y León',
            'Barcelona', 'Girona', 'Lleida', 'Tarragona', 'Cataluña', 'Gerona',
            'Lugo', 'Ourense', 'Pontevedra', 'Galicia',
            'La Rioja', 'Logroño',
            'Madrid', 'Alcalá de Henares',
            'Navarra', 'Pamplona', 'Navarra',
            'Alicante', 'Castellón', 'Valencia', 'Comunidad Valenciana'
        ];
        
        $province = $accommodation['province'] ?? '';
        $hasEclipse = in_array($province, $eclipseProvinces);
        
        jsonSuccess([
            'has_eclipse' => $hasEclipse,
            'province' => $province,
            'using_fallback' => true,
            'message' => $hasEclipse ? 'Accommodation is in eclipse province (fallback)' : 'Accommodation not in eclipse province (fallback)'
        ]);
    }
    
    // New logic using regions table
    // First, check if there's an "eclipse" region
    $stmt = $pdo->prepare("
        SELECT r.* 
        FROM regions r
        WHERE r.region_type = 'evento_especial' 
        AND (r.name LIKE '%eclipse%' OR r.name LIKE '%Eclipse%' OR r.slug LIKE '%eclipse%')
        AND r.has_active_banner = 1
        LIMIT 1
    ");
    $stmt->execute();
    $eclipseRegion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$eclipseRegion) {
        jsonSuccess(['has_eclipse' => false, 'message' => 'No active eclipse region found']);
    }
    
    // Check if accommodation is linked to this region
    $stmt = $pdo->prepare("
        SELECT re.* 
        FROM region_entities re
        WHERE re.region_id = ? 
        AND re.entity_id = ? 
        AND re.entity_type = 'alojamiento'
    ");
    $stmt->execute([$eclipseRegion['id'], $accommodationId]);
    $regionEntity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $hasEclipse = !empty($regionEntity);
    
    // If not directly linked, check by province
    if (!$hasEclipse) {
        // Get accommodation province
        $stmt = $pdo->prepare("SELECT province FROM accommodations WHERE id = ?");
        $stmt->execute([$accommodationId]);
        $accommodation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($accommodation) {
            // Check if province matches any region metadata (you might need to add province field to regions table)
            // For now, we'll use a simple check
            $province = $accommodation['province'] ?? '';
            // You could add province matching logic here if needed
        }
    }
    
    jsonSuccess([
        'has_eclipse' => $hasEclipse,
        'region' => $hasEclipse ? $eclipseRegion : null,
        'message' => $hasEclipse ? 'Accommodation is in eclipse region' : 'Accommodation not in eclipse region'
    ]);
    
} catch (PDOException $e) {
    jsonError('Database error: ' . $e->getMessage(), 500);
}
?>