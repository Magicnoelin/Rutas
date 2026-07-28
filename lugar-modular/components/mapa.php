<?php
/**
 * lugar-modular/components/mapa.php
 * Componente para renderizar el mapa interactivo de Leaflet.
 * Recibe $lugar y $t (traducciones) del index.php padre.
 */

if (!empty($lugar['latitude']) && !empty($lugar['longitude'])): ?>
<div class="lug-card lug-map-section" id="lug-map-container">
    <div class="lug-card-body">
        <h2 class="lug-card-title">🗺️ <?php echo esc($t['ubicacion']); ?></h2>
        <div class="map-placeholder" id="map-placeholder" onclick="initMap()" style="cursor:pointer;">
            <div class="map-ph-icon">🗺️</div>
            <strong><?php echo esc($t['click_mapa']); ?></strong>
            <span class="map-ph-hint"><?php echo esc($t['mapa_hint']); ?></span>
        </div>
        <div id="lug-map" style="height: 400px; width: 100%; display: none;"></div>
        <div class="map-controls" id="map-controls" style="display:none;">
            <div class="map-toggle-group">
                <button class="map-toggle-btn active" data-layer="alojamientos">🏠 <?php echo esc($t['alojamientos']); ?></button>
                <button class="map-toggle-btn active" data-layer="lugares">🏛️ <?php echo esc($t['lugares']); ?></button>
                <button class="map-toggle-btn active" data-layer="actividades">🎯 <?php echo esc($t['actividades']); ?></button>
                <button class="map-toggle-btn active" data-layer="eventos">🎭 <?php echo esc($t['eventos']); ?></button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>