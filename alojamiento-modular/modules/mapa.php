<?php 
// Verificar que todas las variables necesarias existan
if (isset($alojamiento) && $alojamiento && isset($t)):
?>
<div class="alojamiento-map">
    <h2 class="section-title"><i class="fas fa-map"></i> <?php echo isset($t['ubicacion']) ? $t['ubicacion'] : 'Ubicación'; ?></h2>
    
    <div id="map-container" class="map-container">
        <div id="map-placeholder" class="map-placeholder">
            <i class="fas fa-map-marked-alt map-icon"></i>
            <h3><?php echo isset($t['ver_mapa']) ? $t['ver_mapa'] : 'Ver en el mapa'; ?></h3>
            <p><?php echo isset($t['click_mapa']) ? $t['click_mapa'] : 'Haz clic para cargar el mapa interactivo'; ?></p>
        </div>
        <div id="map" class="map" style="display: none;"></div>
    </div>
    
    <?php if (!empty($alojamiento['latitude']) && !empty($alojamiento['longitude'])): ?>
    <script>
        // Datos para el mapa (lazy load)
        const mapData = {
            lat: <?php echo $alojamiento['latitude']; ?>,
            lng: <?php echo $alojamiento['longitude']; ?>,
            title: "<?php echo addslashes($alojamiento['name']); ?>",
            address: "<?php echo addslashes($alojamiento['address'] ?? ''); ?>"
        };
    </script>
    <?php endif; ?>
</div>
<?php endif; ?>
