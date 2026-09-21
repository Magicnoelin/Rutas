<?php
/**
 * API: Provincias con Eventos Culturales
 * Devuelve JSON con las provincias que tienen eventos activos
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600');

$_BASE = dirname(dirname(__DIR__));
require_once $_BASE . '/api/config.php';

function getProvinciasConEventos(PDO $pdo) {
    $sql = "
        SELECT 
            LOWER(e.province) as province_key,
            e.province as province_label,
            COUNT(*) as eventos_count
        FROM cultural_events e
        WHERE e.is_active = 1
          AND e.moderation_status = 'approved'
          AND (
            e.start_date >= CURDATE() 
            OR (e.end_date IS NOT NULL AND e.end_date != '0000-00-00' AND e.end_date >= CURDATE())
          )
        GROUP BY e.province
        ORDER BY eventos_count DESC
    ";
    
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

try {
    $pdo = getDB();
    $provincias_raw = getProvinciasConEventos($pdo);
    
    require_once dirname(__DIR__) . '/config/filters.php';
    
    $provincias = [];
    foreach ($provincias_raw as $p) {
        $key = strtolower($p['province_key']);
        $key_normalized = strtolower(preg_replace('/[^a-z0-9]/', '', str_replace(['á','é','í','ó','ú','ñ',' '], ['a','e','i','o','u','n','_'], $key)));
        
        $config = EVENTOS_PROVINCIAS[$key_normalized] ?? null;
        
        if ($config) {
            $provincias[] = [
                'key' => $key_normalized,
                'label' => $config['label'],
                'db' => $config['db'],
                'eventos' => (int)$p['eventos_count'],
                'lat' => $config['lat'] ?? null,
                'lng' => $config['lng'] ?? null
            ];
        } else {
            $provincias[] = [
                'key' => $key_normalized,
                'label' => $p['province_label'],
                'db' => $p['province_label'],
                'eventos' => (int)$p['eventos_count'],
                'lat' => null,
                'lng' => null
            ];
        }
    }
    
    echo json_encode([
        'provincias' => $provincias,
        'total' => count($provincias),
        'cache_time' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al obtener provincias',
        'message' => $e->getMessage()
    ]);
}
