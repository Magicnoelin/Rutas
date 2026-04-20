<?php 
// Verificar que todas las variables necesarias existan
if (isset($alojamiento) && $alojamiento && isset($t)):
?>
<div class="alojamiento-nearby">
    <h2 class="section-title"><i class="fas fa-compass"></i> <?php echo isset($t['cercanos']) ? $t['cercanos'] : '¿Qué hay cerca?'; ?></h2>
    
    <div class="nearby-sections">
        <div class="nearby-section">
            <h3><?php echo isset($t['alojamientos_cercanos']) ? $t['alojamientos_cercanos'] : '🏠 Alojamientos cercanos'; ?></h3>
            <div id="nearby-accommodations" class="nearby-grid">
                <div class="loading-placeholder">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Cargando alojamientos cercanos...</p>
                </div>
            </div>
        </div>
        
        <div class="nearby-section">
            <h3><?php echo isset($t['lugares_cercanos']) ? $t['lugares_cercanos'] : '🏛️ Lugares de interés cercanos'; ?></h3>
            <div id="nearby-places" class="nearby-grid">
                <div class="loading-placeholder">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Cargando lugares de interés...</p>
                </div>
            </div>
        </div>
        
        <div class="nearby-section">
            <h3><?php echo isset($t['eventos_cercanos']) ? $t['eventos_cercanos'] : '🎭 Eventos culturales cercanos'; ?></h3>
            <div id="nearby-events" class="nearby-grid">
                <div class="loading-placeholder">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Cargando eventos culturales...</p>
                </div>
            </div>
        </div>
        
        <div class="nearby-section">
            <h3><?php echo isset($t['actividades_cercanas']) ? $t['actividades_cercanas'] : '🎯 Actividades turísticas cercanas'; ?></h3>
            <div id="nearby-activities" class="nearby-grid">
                <div class="loading-placeholder">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Cargando actividades turísticas...</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
