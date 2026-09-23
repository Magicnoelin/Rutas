<?php
// Desactivar impresión de errores directamente en el HTML para no romper el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';

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

$geojson = json_encode([
    'type' => 'FeatureCollection',
    'features' => $features
], JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rutas de Enoturismo y Bodegas | RutasRurales.io</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- MarkerCluster CSS (para agrupar puntos) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />

    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
        .map-container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        #mapa-bodegas { height: 550px; width: 100%; border-radius: 8px; }
        
        /* Estilos del Popup */
        .leaflet-popup-content-wrapper { border-radius: 10px; padding: 0; overflow: hidden; }
        .leaflet-popup-content { margin: 0 !important; width: 260px !important; }
        .popup-card { display: flex; flex-direction: column; }
        .popup-card img { width: 100%; height: 120px; object-fit: cover; background: #eee; }
        .popup-card-body { padding: 12px; }
        .popup-card-title { font-size: 15px; font-weight: bold; margin: 0 0 6px 0; color: #2c3e50; }
        .popup-card-info { font-size: 13px; color: #555; margin-bottom: 8px; line-height: 1.4; }
        .popup-card-btn { display: inline-block; background: #800020; color: #fff; text-decoration: none; padding: 6px 10px; font-size: 12px; border-radius: 4px; text-align: center; }
        .popup-claim { display: block; font-size: 11px; color: #888; text-align: center; margin-top: 8px; text-decoration: underline; }
    </style>
</head>
<body>

<div class="map-container">
    <h2 style="margin-top: 0; color: #800020;">🍷 Mapa Enoturístico de Bodegas</h2>
    <div id="mapa-bodegas"></div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- MarkerCluster JS -->
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>

<script>
// 1. Inicializar el mapa centrado en España
const map = L.map('mapa-bodegas').setView([40.4167, -3.7037], 6);

// 2. Capa base OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap | rutasrurales.io'
}).addTo(map);

// 3. Crear el icono personalizado en forma de copa de vino 🍷
const iconoBodega = L.divIcon({
    className: 'custom-pin',
    html: `<div style="
        background-color: #800020; 
        width: 32px; 
        height: 32px; 
        border-radius: 50%; 
        border: 2px solid #ffffff; 
        box-shadow: 0 2px 6px rgba(0,0,0,0.4); 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        color: white; 
        font-size: 16px;">🍷</div>`,
    iconSize: [32, 32],
    iconAnchor: [16, 16]
});

// 4. Inyectar datos desde PHP
const geojsonData = <?php echo $geojson; ?>;

// 5. Grupo para clustering (agrupar puntos)
const markersCluster = L.markerClusterGroup();

if (geojsonData.features.length > 0) {
    const geoJsonLayer = L.geoJSON(geojsonData, {
        pointToLayer: function (feature, latlng) {
            return L.marker(latlng, { icon: iconoBodega });
        },
        onEachFeature: function (feature, layer) {
            const p = feature.properties;
            
            // Estructura HTML elegante para la ficha del popup
            let popupContent = `
                <div class="popup-card">
                    <img src="${p.foto}" alt="${p.nombre}" onerror="this.src='https://images.unsplash.com/photo-1506377247377-2a5b3b417ebb?auto=format&fit=crop&w=300&q=80'">
                    <div class="popup-card-body">
                        <h4 class="popup-card-title">${p.nombre}</h4>
                        <div class="popup-card-info">
                            ${p.categoria ? '🏷️ ' + p.categoria + '<br>' : ''}
                            ${p.telefono ? '📞 ' + p.telefono + '<br>' : ''}
                        </div>
                        ${p.web ? `<a href="${p.web}" target="_blank" rel="noopener" class="popup-card-btn">Visitar Sitio Web</a>` : ''}
                        <a href="#" class="popup-claim">¿Eres el propietario? Reclama esta ficha</a>
                    </div>
                </div>
            `;
            
            layer.bindPopup(popupContent);
        }
    });

    // Añadir la capa GeoJSON al grupo de agrupamiento y luego al mapa
    markersCluster.addLayer(geoJsonLayer);
    map.addLayer(markersCluster);

    // Encuadre automático del mapa a los marcadores
    map.fitBounds(markersCluster.getBounds());
}
</script>

</body>
</html>