<?php
/**
 * lugar-modular/components/mapa.php
 * Componente para renderizar el mapa interactivo de Leaflet.
 * Recibe $lugar y $t (traducciones) del index.php padre.
 * Estructura idéntica al mapa de evento-modular (evento-detalle.php).
 */

if (!empty($lugar['latitude']) && !empty($lugar['longitude'])): ?>
<div id="lug-map-container" class="lug-card" style="margin-bottom:24px;">
    <div id="map-placeholder" class="map-placeholder" onclick="initMap()">
        <div class="map-icon">🗺️</div>
        <strong style="font-size:1rem;"><?php echo esc($t['ubicacion']); ?></strong>
        <p><?php echo esc($t['click_mapa']); ?></p>
    </div>
    <div id="lug-map" style="display:none;"></div>
    <div class="map-controls" id="map-controls" style="display:none;">
        <button class="map-toggle-btn active" data-layer="alojamientos">🏠 <?php echo esc($t['alojamientos']); ?></button>
        <button class="map-toggle-btn active" data-layer="lugares">🏛️ <?php echo esc($t['lugares']); ?></button>
        <button class="map-toggle-btn active" data-layer="actividades">🎯 <?php echo esc($t['actividades']); ?></button>
        <button class="map-toggle-btn active" data-layer="eventos">🎭 <?php echo esc($t['eventos']); ?></button>
    </div>
</div>
<?php endif; ?>
