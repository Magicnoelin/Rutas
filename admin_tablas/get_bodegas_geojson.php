<?php
// get_bodegas_geojson.php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->query("SELECT osm_id, nombre, slug, latitud, longitud, categoria_label, datos_json FROM auxiliar_poi");
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $registros = [];
}

$features = [];
foreach ($registros as $row) {
    if (empty($row['latitud']) || empty($row['longitud'])) continue;
    
    $extra = json_decode($row['datos_json'] ?? '{}', true);
    $url_web = $extra['web'] ?? '';

    // Generar minifoto automática basada en la web o foto por defecto de viñedo
    $minifoto = !empty($url_web) 
        ? "https://image.thum.io/get/width/300/crop/600/" . $url_web 
        : "https://images.unsplash.com/photo-1506377247377-2a5b3b417ebb?auto=format&fit=crop&w=300&q=80";

    $features[] = [
        'type' => 'Feature',
        'geometry' => [
            'type' => 'Point',
            'coordinates' => [(float)$row['longitud'], (float)$row['latitud']]
        ],
        'properties' => [
            'nombre'    => $row['nombre'],
            'slug'      => $row['slug'] ?? '',
            'telefono'  => $extra['telefono'] ?? '',
            'web'       => $url_web,
            'categoria' => $row['categoria_label'] ?? 'Bodega',
            'foto'      => $minifoto
        ]
    ];
}

echo json_encode([
    'type' => 'FeatureCollection',
    'features' => $features
], JSON_UNESCAPED_UNICODE);