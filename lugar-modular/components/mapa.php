<?php 
// Verificar que todas las variables necesarias existan
if (isset($lugar) && $lugar && isset($t)):
    // Preparar parámetros para la redirección al mapa de rutas
    $lat = !empty($lugar['latitude']) ? $lugar['latitude'] : '';
    $lng = !empty($lugar['longitude']) ? $lugar['longitude'] : '';
    $provincia = !empty($lugar['province']) ? $lugar['province'] : '';
?>
<?php if (!empty($lat) && !empty($lng)): ?>
<div class="lugar-map" style="margin-bottom: 24px;">
    <a href="/rutas.php?lat=<?php echo htmlspecialchars($lat, ENT_QUOTES, 'UTF-8'); ?>&lng=<?php echo htmlspecialchars($lng, ENT_QUOTES, 'UTF-8'); ?>&provincia=<?php echo urlencode($provincia); ?>&radius=30" 
       target="_blank" 
       rel="noopener noreferrer"
       class="map-placeholder" 
       style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 200px; background: linear-gradient(135deg, #e8f0e8, #d4e8d4); color: #2F5233; gap: 12px; cursor: pointer; border-radius: 12px; text-decoration: none; transition: background 0.2s;">
        <i class="fas fa-map-marked-alt" style="font-size: 2.5rem;"></i>
        <h3 style="margin: 0; font-size: 1.2rem; font-weight: 700; text-align: center;">🗺️ Explorar mapa interactivo y rutas cerca</h3>
        <p style="margin: 0; font-size: 0.9rem; color: #666; text-align: center;">Haz clic para ver la ubicación exacta y alojamientos recomendados en la zona</p>
    </a>
</div>
<?php endif; ?>
<?php endif; ?>
