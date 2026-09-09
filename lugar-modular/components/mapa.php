<?php 
// Verificar que todas las variables necesarias existan
if (isset($lugar) && $lugar && isset($t)):
?>
<div class="lugar-map">
    <h2 class="section-title"><i class="fas fa-map"></i> <?php echo isset($t['ubicacion']) ? $t['ubicacion'] : 'Ubicación'; ?></h2>
    
    <div id="map-container" class="map-container">
        <div id="map-placeholder" class="map-placeholder" onclick="initMap()">
            <i class="fas fa-map-marked-alt map-icon"></i>
            <h3><?php echo isset($t['ver_mapa']) ? $t['ver_mapa'] : 'Ver en el mapa'; ?></h3>
            <p><?php echo isset($t['click_mapa']) ? $t['click_mapa'] : 'Haz clic para cargar el mapa interactivo'; ?></p>
        </div>
        <div id="map" class="map" style="display: none;"></div>
    </div>
    
    <?php if (!empty($lugar['latitude']) && !empty($lugar['longitude'])): ?>
    <script>
        // Los datos del lugar están disponibles en window.LUG_DATA (inyectado por head.php)
        // y la función initMap() en lugar.js los utiliza directamente
    </script>
    <?php endif; ?>
</div>
<?php endif; ?>
